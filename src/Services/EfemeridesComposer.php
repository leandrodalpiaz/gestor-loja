<?php

namespace App\Services;

use App\Models\MensagemComplementar;

/**
 * Compositor de mensagens diarias de efemerides.
 *
 * Ordem de montagem:
 * 1. Eventos especiais: Posse Grão Mestre, Membro Honorário, Filiação
 * 2. Aniversários agrupados por tratamento
 * 3. Cerimônias maçônicas: Iniciação, Elevação, Exaltação, Instalação
 * 4. Oriente Eterno
 */
class EfemeridesComposer
{
    private MensagemComplementar $comp;

    public function __construct()
    {
        $this->comp = new MensagemComplementar();
    }

    public function gerarMensagemParaDia(?string $dataYmd = null): string
    {
        require_once __DIR__ . '/../Models/EfemerideRegistro.php';
        require_once __DIR__ . '/../Models/HistoriaMaconica.php';

        $efemModel = new \App\Models\EfemerideRegistro();
        $historiaModel = new \App\Models\HistoriaMaconica();

        $diaMes = null;
        if ($dataYmd) {
            $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $dataYmd);
            if ($dt !== false) {
                $diaMes = $dt->format('d/m');
                $registros = $efemModel->getRegistrosPorDiaMes($diaMes);
            } else {
                $registros = [];
            }
        } else {
            $registros = $efemModel->getRegistrosDoDia();
            $diaMes = date('d/m');
        }

        // Injetar as histórias na mesma lista se tivermos o diaMes extraído
        if ($diaMes !== null) {
            $dmParts = explode('/', $diaMes);
            $dia = (int)($dmParts[0] ?? 0);
            $mes = (int)($dmParts[1] ?? 0);
            if ($dia > 0 && $mes > 0) {
                $historias = $historiaModel->buscarPorDiaMes($dia, $mes);
                foreach ($historias as $hist) {
                    $registros[] = [
                        'nome' => $hist['titulo'] ?? 'História da Loja',
                        'tipo' => 'História',
                        'mensagem_custom' => $hist['texto'] ?? '',
                        'local' => $hist['fonte'] ?? ''
                    ];
                }
            }
        }

        return $this->composeDailyPreview($registros);
    }

    /**
     * @param array<int, array<string, mixed>> $registros
     */
    public function composeDailyPreview(array $registros): string
    {
        $porTipo = [];
        foreach ($registros as $r) {
            $tipo = (string) ($r['tipo'] ?? '');
            $porTipo[$tipo][] = $r;
        }

        $mensagens = [];

        foreach (($porTipo['História'] ?? []) as $r) {
            $msg = $this->formatarHistoria($r);
            if ($msg !== '') {
                $mensagens[] = $msg;
            }
        }

        foreach (['Posse Grão Mestre', 'Concessão de Membro Honorário', 'Filiação'] as $tipo) {
            foreach (($porTipo[$tipo] ?? []) as $r) {
                $msg = $this->formatarEspecial($r, $tipo);
                if ($msg !== '') {
                    $mensagens[] = $msg;
                }
            }
        }

        if (!empty($porTipo['Aniversário'])) {
            $mensagens = array_merge(
                $mensagens,
                $this->formatarAniversariosAgrupados($porTipo['Aniversário'])
            );
        }

        foreach (['Iniciação', 'Elevação', 'Exaltação', 'Instalação'] as $tipo) {
            if (!empty($porTipo[$tipo])) {
                $msg = $this->formatarCerimoniasAgrupadas($porTipo[$tipo], $tipo);
                if ($msg !== '') {
                    $mensagens[] = $msg;
                }
            }
        }

        foreach (($porTipo['Oriente Eterno'] ?? []) as $r) {
            $msg = $this->formatarOrienteEterno($r);
            if ($msg !== '') {
                $mensagens[] = $msg;
            }
        }

        $mensagens = array_values(array_filter(array_map('trim', $mensagens)));

        if ($mensagens === []) {
            return 'Nenhuma efeméride para hoje.';
        }

        $cabecalho = "🔨 <b>A:.R:.L:.S:. Renascença</b> - Efemérides do dia";
        return $cabecalho . "\n\n" . implode("\n\n", $mensagens);
    }

    private function formatarHistoria(array $r): string
    {
        $titulo = trim((string) ($r['nome'] ?? 'Fato Histórico'));
        $texto = trim((string) ($r['mensagem_custom'] ?? ''));
        if ($texto === '') return '';
        
        $msg = "📜 <b>Neste dia na História: {$titulo}</b>\n";
        $msg .= "<i>{$texto}</i>";
        return $msg;
    }

    /**
     * @param array<int, array<string, mixed>> $eventos
     * @return array<int, string>
     */
    private function formatarAniversariosAgrupados(array $eventos): array
    {
        $porTratamento = [];

        foreach ($eventos as $r) {
            $tratamento = $this->tratamentoPorVinculo(
                (int) ($r['cod_vinculo'] ?? 0),
                (string) ($r['vinculo'] ?? ''),
                (string) ($r['parentesco'] ?? '')
            );
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

    /**
     * @param array<string, mixed> $r
     */
    private function formatarAniversario(array $r, string $tratamento, string $complementar): string
    {
        $nome = trim((string) ($r['nome'] ?? '')) ?: 'Nome não informado';
        $strAnos = $this->textoAnos($this->calcularAnos((string) ($r['data_evento'] ?? '')));
        $vinculo = trim((string) ($r['vinculo'] ?? '')) ?: 'vínculo não informado';
        $parentesco = $this->normalizarParentesco((string) ($r['parentesco'] ?? ''));
        $tNorm = $this->normalizarTipo($tratamento);
        $artigo = in_array($tNorm, ['cunhada', 'sobrinha'], true) ? 'nossa' : 'nosso';

        if ($tNorm === '') {
            $msg = "Hoje celebramos, com fraterna alegria, os <b>{$strAnos}</b> de vida de <b>{$nome}</b>.";
        } elseif ($tNorm === 'irmao') {
            $msg = "🎉 Com fraterna alegria, hoje celebramos os <b>{$strAnos}</b> de vida do nosso Irmão <b>{$nome}</b>.";
        } elseif ($tNorm === 'cunhada') {
            $msg = "Hoje celebramos, com fraterna alegria, o aniversário de {$artigo} {$tratamento} <b>{$nome}</b>, {$vinculo}{$this->formatarReferenciaIrmao($parentesco)}.";
        } else {
            $msg = "Hoje celebramos, com fraterna alegria, os <b>{$strAnos}</b> de vida de {$artigo} {$tratamento} <b>{$nome}</b>, {$vinculo}{$this->formatarReferenciaIrmao($parentesco)}.";
        }

        if ($complementar !== '') {
            $msg .= "\n<i>{$complementar}</i>";
        }

        return $msg;
    }

    /**
     * @param array<int, array<string, mixed>> $grupo
     */
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

        return implode(
            "\n\n",
            array_map(
                fn(array $r): string => $this->formatarAniversario($r, $tratamento, $complementar),
                $grupo
            )
        );
    }

    private function normalizarParentesco(string $parentesco): string
    {
        $parentesco = trim($parentesco);
        if ($parentesco === '') {
            return '';
        }

        $parentesco = preg_replace('/^\s*irm[aã]o\s+/iu', '', $parentesco) ?? $parentesco;
        return trim($parentesco);
    }

    private function formatarReferenciaIrmao(string $parentesco): string
    {
        if ($parentesco === '') {
            return '';
        }

        return " do nosso Irmão {$parentesco}";
    }

    /**
     * @param array<int, array<string, mixed>> $eventos
     */
    private function formatarCerimoniasAgrupadas(array $eventos, string $tipo): string
    {
        $tipoComp = $this->normalizarTipo($tipo);
        $complementar = $this->comp->sortear($tipoComp);

        if (count($eventos) === 1) {
            return $this->formatarCerimonia($eventos[0], $tipo, $complementar);
        }

        $partes = ['Neste dia, registramos com honra marcos fundamentais em nossas colunas:'];
        foreach ($eventos as $r) {
            $nome = trim((string) ($r['nome'] ?? '')) ?: 'Nome não informado';
            $strAnos = $this->textoAnos($this->calcularAnos((string) ($r['data_evento'] ?? '')));
            $data = $this->formatarData((string) ($r['data_evento'] ?? ''));
            $local = trim((string) ($r['local'] ?? ''));
            $sufixoLocal = $local !== '' ? " ({$local})" : '';

            $partes[] = "🔹 <b>{$strAnos}</b> da {$tipo} do querido Irmão <b>{$nome}</b> - {$data}{$sufixoLocal}";
        }

        if ($complementar !== '') {
            $partes[] = "\n<i>{$complementar}</i>";
        }

        return implode("\n", $partes);
    }

    /**
     * @param array<string, mixed> $r
     */
    private function formatarCerimonia(array $r, string $tipo, string $complementar): string
    {
        $nome = trim((string) ($r['nome'] ?? '')) ?: 'Nome não informado';
        $strAnos = $this->textoAnos($this->calcularAnos((string) ($r['data_evento'] ?? '')));
        $data = $this->formatarData((string) ($r['data_evento'] ?? ''));
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

    /**
     * @param array<string, mixed> $r
     */
    private function formatarEspecial(array $r, string $tipo): string
    {
        $custom = trim((string) ($r['mensagem_custom'] ?? ''));
        if ($custom !== '') {
            return $custom;
        }

        $nome = trim((string) ($r['nome'] ?? '')) ?: 'Nome não informado';
        $strAnos = $this->textoAnos($this->calcularAnos((string) ($r['data_evento'] ?? '')));
        $data = $this->formatarData((string) ($r['data_evento'] ?? ''));
        $local = trim((string) ($r['local'] ?? ''));
        $sufixoLocal = $local !== '' ? " do {$local}" : '';

        return match ($tipo) {
            'Posse Grão Mestre' => "Recordamos hoje com profundo respeito a data magna em que o Malhete da Grande Loja foi confiado às vossas mãos. Há <b>{$strAnos}</b>, em {$data}, a Maçonaria celebrava a vossa Posse como Grão Mestre, querido Irmão <b>{$nome}</b>, um momento que fortaleceu as colunas{$sufixoLocal} e de toda a nossa Jurisdição.",
            'Concessão de Membro Honorário' => "Com imensa alegria e o coração grato celebramos hoje o aniversário de um dia de grande honra para nossa Oficina. Há exatamente <b>{$strAnos}</b>, em {$data}, tivemos o privilégio de realizar a Concessão do Título de Membro Honorário ao nosso estimado Irmão <b>{$nome}</b>.",
            'Filiação' => "Neste dia, celebramos <b>{$strAnos}</b> do estreitamento de nossos laços fraternais: em {$data}, ocorria a Filiação do querido Irmão <b>{$nome}</b>" . ($local !== '' ? " nas colunas da {$local}." : ' em nossa Oficina.'),
            default => '',
        };
    }

    /**
     * @param array<string, mixed> $r
     */
    private function formatarOrienteEterno(array $r): string
    {
        $nome = trim((string) ($r['nome'] ?? '')) ?: 'Nome não informado';
        $data = $this->formatarData((string) ($r['data_evento'] ?? ''));

        return "🌿 <i>Com profundo pesar e saudade, lembramos de nosso Irmão <b>{$nome}</b>, que partiu para o Oriente Eterno em {$data}. Que o G:.A:.D:.U:. o tenha em Sua glória.</i>";
    }

    private function textoAnos(int $anos): string
    {
        return $anos === 1 ? '1 ano' : "{$anos} anos";
    }

    private function normalizarTipo(string $tipo): string
    {
        $tipo = $this->toLower(trim($tipo));
        $tipo = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $tipo) ?: $tipo;
        $tipo = preg_replace('/[^a-z0-9]+/', '', $tipo) ?? '';

        return $tipo;
    }

    private function tratamentoPorVinculo(int $codVinculo, string $vinculo = '', string $parentesco = ''): string
    {
        $porCodigo = [
            1 => 'Irmão',
            2 => 'Cunhada',
            3 => 'Sobrinha',
            4 => 'Sobrinho',
            5 => 'Sobrinha',
            6 => 'Sobrinho',
        ];

        if (isset($porCodigo[$codVinculo])) {
            return $porCodigo[$codVinculo];
        }

        $vNorm = $this->normalizarTipo($vinculo);

        return match ($vNorm) {
            'irmao' => 'Irmão',
            'esposa' => 'Cunhada',
            'filho', 'enteado' => 'Sobrinho',
            'filha', 'enteada' => 'Sobrinha',
            default => $this->inferirTratamentoSemVinculo($parentesco),
        };
    }

    private function inferirTratamentoSemVinculo(string $parentesco): string
    {
        $pNorm = $this->normalizarTipo($parentesco);
        if ($pNorm === '' || $pNorm === 'irmao') {
            return 'Irmão';
        }

        return '';
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
        $anos = (int) $today->format('Y') - (int) $base->format('Y');
        if ($today->format('m-d') < $base->format('m-d')) {
            $anos--;
        }

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
