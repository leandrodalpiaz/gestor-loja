<?php

namespace App\Services;

use App\Models\MensagemComplementar;

/**
 * Compositor de mensagens diárias de efemérides.
 *
 * Ordem de montagem (idêntica ao pipeline Python event_manager.py):
 *   1. Evento histórico fixo/banco da Ordem
 *   2. Eventos especiais: Posse GM, Membro Honorário, Filiação
 *   3. Aniversários (agrupados por tratamento)
 *   4. Cerimônias maçônicas: Iniciação, Elevação, Exaltação, Instalação (agrupadas por tipo)
 *   5. Oriente Eterno
 *   Fallback: curiosidade rotativa quando não há eventos
 *
 * Fonte única de dados: efemerides_registros (tabela do banco).
 * Parse mode: HTML (o TelegramService usa parse_mode HTML).
 */
class EfemeridesComposer
{
    private MensagemComplementar $comp;

    public function __construct()
    {
        $this->comp = new MensagemComplementar();
    }

    /**
     * Ponto de entrada principal.
     *
     * @param array $registros  Resultado de EfemerideRegistro::getRegistrosDoDia()
     */
    public function composeDailyPreview(array $registros): string
    {
        $hoje = $this->today();

        // Agrupa por tipo para facilitar processamento
        $porTipo = [];
        foreach ($registros as $r) {
            $porTipo[$r['tipo']][] = $r;
        }

        $mensagens = [];

        // 1. Evento histórico da Ordem (banco prevalece sobre dicionário fixo)
        $historico = HistoricoEventos::getParaHoje($porTipo['História'] ?? [], $hoje);
        if ($historico !== '') {
            $mensagens[] = "📜 <b>Memória Maçônica:</b> " . $historico;
        }

        // 2. Eventos especiais
        foreach (['Posse Grão Mestre', 'Concessão de Membro Honorário', 'Filiação'] as $tipo) {
            foreach (($porTipo[$tipo] ?? []) as $r) {
                $msg = $this->formatarEspecial($r, $tipo);
                if ($msg !== '') {
                    $mensagens[] = $msg;
                }
            }
        }

        // 3. Aniversários agrupados por tratamento
        if (!empty($porTipo['Aniversário'])) {
            $mensagens = array_merge(
                $mensagens,
                $this->formatarAniversariosAgrupados($porTipo['Aniversário'])
            );
        }

        // 4. Cerimônias maçônicas
        foreach (['Iniciação', 'Elevação', 'Exaltação', 'Instalação'] as $tipo) {
            if (!empty($porTipo[$tipo])) {
                $msg = $this->formatarCerimoniasAgrupadas($porTipo[$tipo], $tipo);
                if ($msg !== '') {
                    $mensagens[] = $msg;
                }
            }
        }

        // 5. Oriente Eterno
        foreach (($porTipo['Oriente Eterno'] ?? []) as $r) {
            $msg = $this->formatarOrienteEterno($r);
            if ($msg !== '') {
                $mensagens[] = $msg;
            }
        }

        $mensagens = array_values(array_filter(array_map('trim', $mensagens)));

        if (empty($mensagens)) {
            return $this->fallbackSemEventos();
        }

        $cabecalho = "🏛️ <b>A:.R:.L:.S:. Renascença</b> – Efemérides do dia";
        return $cabecalho . "\n\n" . implode("\n\n", $mensagens);
    }

    // ---------------------------------------------------------------
    // Aniversários
    // ---------------------------------------------------------------

    private function formatarAniversariosAgrupados(array $eventos): array
    {
        // Agrupa por tratamento (Irmão / Cunhada / Sobrinha / Sobrinho)
        $porTratamento = [];
        foreach ($eventos as $r) {
            $tratamento = $this->tratamentoPorVinculo((int) ($r['cod_vinculo'] ?? 0));
            $porTratamento[$tratamento][] = $r;
        }

        $mensagens = [];
        foreach ($porTratamento as $tratamento => $grupo) {
            $tipoComp = 'aniversario_' . $this->normalizarTipo($tratamento);
            $complementar = $this->comp->sortear($tipoComp);

            if (count($grupo) === 1) {
                $mensagens[] = $this->formatarAniversario($grupo[0], $tratamento, $complementar);
            } else {
                $mensagens[] = $this->formatarAniversarioGrupo($grupo, $tratamento, $complementar);
            }
        }

        return $mensagens;
    }

    private function formatarAniversario(array $r, string $tratamento, string $complementar): string
    {
        $nome       = trim((string) ($r['nome'] ?? '')) ?: 'Nome não informado';
        $strAnos    = $this->textoAnos($this->calcularAnos((string) ($r['data_evento'] ?? '')));
        $vinculo    = trim((string) ($r['vinculo'] ?? '')) ?: 'vínculo não informado';
        $parentesco = trim((string) ($r['parentesco'] ?? '')) ?: 'parentesco não informado';
        $tNorm      = $this->normalizarTipo($tratamento);
        $artigo     = in_array($tNorm, ['cunhada', 'sobrinha', 'filha', 'enteada', 'esposa'], true) ? 'nossa' : 'nosso';

        if ($tNorm === 'irmao') {
            $msg = "🎉 Com fraterna alegria, hoje celebramos os <b>{$strAnos}</b> de vida do nosso Irmão <b>{$nome}</b>.";
        } elseif (in_array($tNorm, ['cunhada', 'esposa'], true)) {
            // Oculta idade para Cunhadas/Esposas
            $msg = "Hoje celebramos, com fraterna alegria, o aniversário de {$artigo} {$tratamento} <b>{$nome}</b>, {$vinculo} do nosso Irmão {$parentesco}.";
        } else {
            $msg = "Hoje celebramos, com fraterna alegria, os <b>{$strAnos}</b> de vida de {$artigo} {$tratamento} <b>{$nome}</b>, {$vinculo} do nosso Irmão {$parentesco}.";
        }

        if ($complementar !== '') {
            $msg .= "\n<i>{$complementar}</i>";
        }

        return $msg;
    }

    private function formatarAniversarioGrupo(array $grupo, string $tratamento, string $complementar): string
    {
        $tNorm = $this->normalizarTipo($tratamento);

        if ($tNorm === 'irmao') {
            $partes = ['🎉 <b>Com fraterna alegria, hoje celebramos os aniversários de nossos Irmãos:</b>'];
            foreach ($grupo as $r) {
                $nome = trim((string) ($r['nome'] ?? '')) ?: 'Nome não informado';
                $strAnos = $this->textoAnos($this->calcularAnos((string) ($r['data_evento'] ?? '')));
                $partes[] = "🎂 Irmão <b>{$nome}</b> ({$strAnos} de vida)";
            }
            if ($complementar !== '') {
                $partes[] = "\n<i>{$complementar}</i>";
            }
            return implode("\n", $partes);
        }

        // Outros tratamentos: uma mensagem por registro
        return implode("\n\n", array_map(
            fn(array $r) => $this->formatarAniversario($r, $tratamento, $complementar),
            $grupo
        ));
    }

    // ---------------------------------------------------------------
    // Cerimônias maçônicas
    // ---------------------------------------------------------------

    private function formatarCerimoniasAgrupadas(array $eventos, string $tipo): string
    {
        $tipoComp    = $this->normalizarTipo($tipo);
        $complementar = $this->comp->sortear($tipoComp);

        if (count($eventos) === 1) {
            return $this->formatarCerimonia($eventos[0], $tipo, $complementar);
        }

        // UX: Agrupamento em lista para leitura dinâmica
        $partes = ["Neste dia, registramos com honra marcos fundamentais em nossas colunas:"];
        foreach ($eventos as $r) {
            $nome  = trim((string) ($r['nome'] ?? '')) ?: 'Nome não informado';
            $strAnos = $this->textoAnos($this->calcularAnos((string) ($r['data_evento'] ?? '')));
            $data  = $this->formatarData((string) ($r['data_evento'] ?? ''));
            $local = trim((string) ($r['local'] ?? ''));
            $sufixoLocal = $local !== '' ? " ({$local})" : '';

            $partes[] = "🔹 <b>{$strAnos}</b> da {$tipo} do querido Irmão <b>{$nome}</b> - {$data}{$sufixoLocal}";
        }

        if ($complementar !== '') {
            $partes[] = "\n<i>{$complementar}</i>";
        }

        return implode("\n", $partes);
    }

    private function formatarCerimonia(array $r, string $tipo, string $complementar): string
    {
        $nome  = trim((string) ($r['nome'] ?? '')) ?: 'Nome não informado';
        $strAnos = $this->textoAnos($this->calcularAnos((string) ($r['data_evento'] ?? '')));
        $data  = $this->formatarData((string) ($r['data_evento'] ?? ''));
        $local = trim((string) ($r['local'] ?? ''));
        $sufixoLocal = $local !== '' ? " (Loja {$local})" : '';
        $custom = trim((string) ($r['mensagem_custom'] ?? ''));

        if ($custom !== '') {
            return $custom;
        }

        $msg = "Neste dia, registramos com honra <b>{$strAnos}</b> da {$tipo} do querido Irmão <b>{$nome}</b> - {$data}{$sufixoLocal}.";

        if ($complementar !== '') {
            $msg .= "\n<i>{$complementar}</i>";
        }

        return $msg;
    }

    // ---------------------------------------------------------------
    // Eventos especiais
    // ---------------------------------------------------------------

    private function formatarEspecial(array $r, string $tipo): string
    {
        $custom = trim((string) ($r['mensagem_custom'] ?? ''));
        if ($custom !== '') {
            return $custom;
        }

        $nome  = trim((string) ($r['nome'] ?? '')) ?: 'Nome não informado';
        $strAnos = $this->textoAnos($this->calcularAnos((string) ($r['data_evento'] ?? '')));
        $data  = $this->formatarData((string) ($r['data_evento'] ?? ''));
        $local = trim((string) ($r['local'] ?? ''));
        $sufixoLocal = $local !== '' ? " do {$local}" : '';

        switch ($tipo) {
            case 'Posse Grão Mestre':
                return "Recordamos hoje com profundo respeito a data magna em que o Malhete da Grande Loja foi confiado às vossas mãos. Há <b>{$strAnos}</b>, em {$data}, a Maçonaria celebrava a vossa Posse como Grão Mestre, querido Irmão <b>{$nome}</b>, um momento que fortaleceu as colunas{$sufixoLocal} e de toda a nossa Jurisdição.";

            case 'Concessão de Membro Honorário':
                return "Com imensa alegria e o coração grato celebramos hoje o aniversário de um dia de grande honra para nossa Oficina. Há exatamente <b>{$strAnos}</b>, em {$data}, tivemos o privilégio de realizar a Concessão do Título de Membro Honorário ao nosso estimado Irmão <b>{$nome}</b>.";

            case 'Filiação':
                $sufixoLocalFil = $local !== '' ? " nas colunas da {$local}" : ' em nossa Oficina';
                return "Neste dia, celebramos <b>{$strAnos}</b> do estreitamento de nossos laços fraternais: em {$data}, ocorria a Filiação do querido Irmão <b>{$nome}</b>{$sufixoLocalFil}.";
        }

        return '';
    }

    // ---------------------------------------------------------------
    // Oriente Eterno
    // ---------------------------------------------------------------

    private function formatarOrienteEterno(array $r): string
    {
        $nome = trim((string) ($r['nome'] ?? '')) ?: 'Nome não informado';
        $data = $this->formatarData((string) ($r['data_evento'] ?? ''));
        return "🌿 <i>Com profundo pesar e saudade, lembramos de nosso Irmão <b>{$nome}</b>, que partiu para o Oriente Eterno em {$data}. Que o G:.A:.D:.U:. o tenha em Sua glória.</i>";
    }

    // ---------------------------------------------------------------
    // Fallback sem eventos
    // ---------------------------------------------------------------

    private function fallbackSemEventos(): string
    {
        $curiosidade = $this->comp->sortear('fallback');
        if ($curiosidade !== '') {
            return "🔨 <b>Um Toque da Trolha:</b>\n\n{$curiosidade}\n\n<i>Que este toque provoque a busca que suaviza nossas arestas.</i>";
        }
        return 'Nenhuma efeméride para hoje.';
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    private function textoAnos(int $anos): string
    {
        return $anos === 1 ? '1 ano' : "{$anos} anos";
    }

    private function normalizarTipo(string $tipo): string
    {
        $tipo = $this->toLower(trim($tipo));
        return strtr($tipo, [
            'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a',
            'é' => 'e', 'ê' => 'e',
            'í' => 'i',
            'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
            'ú' => 'u',
            'ç' => 'c',
            ' ' => '', '-' => '', '_' => '',
        ]);
    }

    private function tratamentoPorVinculo(int $codVinculo): string
    {
        return [
            1 => 'Irmão',
            2 => 'Cunhada',
            3 => 'Sobrinha',
            4 => 'Sobrinho',
            5 => 'Sobrinha',
            6 => 'Sobrinho',
        ][$codVinculo] ?? 'familiar';
    }

    private function calcularAnos(string $dataEvento): int
    {
        if ($dataEvento === '') {
            return 0;
        }
        $base = \DateTimeImmutable::createFromFormat('Y-m-d', $dataEvento);
        if ($base === false) {
            return 0;
        }
        $today = $this->today();
        $anos = $today->format('Y') - $base->format('Y');
        // Subtrai 1 só se hoje for antes do aniversário
        if ($today->format('m-d') < $base->format('m-d')) {
            $anos--;
        }
        // Garante que não retorna negativo
        return max(0, $anos);
    }

    private function formatarData(string $dataEvento): string
    {
        $base = \DateTimeImmutable::createFromFormat('Y-m-d', $dataEvento);
        if ($base === false) {
            return $dataEvento !== '' ? $dataEvento : 'data não informada';
        }
        return $base->format('d/m/Y');
    }

    private function toLower(string $value): string
    {
        if (function_exists('mb_strtolower')) {
            return mb_strtolower($value, 'UTF-8');
        }

        return strtolower($value);
    }

    private function today(): \DateTimeImmutable
    {
        $timezone = trim((string) ($_ENV['APP_TIMEZONE'] ?? 'America/Sao_Paulo'));
        try {
            return new \DateTimeImmutable('today', new \DateTimeZone($timezone));
        } catch (\Throwable $e) {
            return new \DateTimeImmutable('today', new \DateTimeZone('America/Sao_Paulo'));
        }
    }
}