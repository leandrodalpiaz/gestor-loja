<?php

namespace App\Services;

class EfemeridesComposer
{
    public function composeDailyPreview(array $obreiros, array $registros): string
    {
        $mensagens = [];

        foreach ($obreiros as $ob) {
            $nome = $ob['nome_historico'] ?: ($ob['nome'] ?? 'Irmão');
            $grau = !empty($ob['grau']) ? ' (' . ucfirst((string) $ob['grau']) . ')' : '';

            if (!empty($ob['is_aniversario_civil'])) {
                $mensagens[] = "Hoje é o aniversário natalício do nosso Amado Ir. <b>{$nome}</b>{$grau}! Desejamos muita saúde, paz e prosperidade!";
            }

            if (!empty($ob['is_aniversario_maconico'])) {
                $mensagens[] = "Hoje nosso Amado Ir. <b>{$nome}</b>{$grau} comemora seu Aniversário de Iniciação Maçônica! Parabéns pela caminhada na Ordem!";
            }
        }

        foreach ($registros as $registro) {
            $mensagens[] = $this->formatarRegistro((array) $registro);
        }

        $mensagens = array_values(array_filter(array_map('trim', $mensagens)));

        if (empty($mensagens)) {
            return 'Nenhuma efeméride para hoje.';
        }

        $cabecalho = "🏛️ <b>A:.R:.L:.S:. Renascença</b> - Prévia de Efemérides";
        return $cabecalho . "\n\n" . implode("\n\n", $mensagens);
    }

    private function formatarRegistro(array $r): string
    {
        $tipo = $this->normalizarTipo((string) ($r['tipo'] ?? ''));
        $nome = trim((string) ($r['nome'] ?? '')) ?: 'Nome não informado';
        $dataEvento = (string) ($r['data_evento'] ?? '');
        $local = trim((string) ($r['local'] ?? '')) ?: 'Loja Renascença nº 270';
        $mensagemCustom = trim((string) ($r['mensagem_custom'] ?? ''));

        if (in_array($tipo, ['historia', 'historico'], true)) {
            return $mensagemCustom !== '' ? $mensagemCustom : 'Mensagem histórica sem texto definido.';
        }

        $anos = $this->calcularAnos($dataEvento);
        $dataFormatada = $this->formatarData($dataEvento);

        if ($tipo === 'aniversario') {
            $codVinculo = isset($r['cod_vinculo']) ? (int) $r['cod_vinculo'] : 0;
            $tratamento = $this->tratamentoPorVinculo($codVinculo);
            $vinculo = trim((string) ($r['vinculo'] ?? '')) ?: 'vínculo não informado';
            $parentesco = trim((string) ($r['parentesco'] ?? '')) ?: 'parentesco não informado';
            $tratamentoNormalizado = $this->normalizarTipo($tratamento);
            $artigo = in_array($tratamentoNormalizado, ['cunhada', 'sobrinha', 'filha', 'enteada', 'esposa'], true) ? 'nossa' : 'nosso';

            if (in_array($tratamentoNormalizado, ['cunhada', 'esposa'], true)) {
                $msg = "Hoje celebramos, com fraterna alegria, o aniversário de {$artigo} {$tratamento} {$nome}, {$vinculo} do nosso Irmão {$parentesco}.";
            } else {
                $msg = "Hoje celebramos, com fraterna alegria, os {$anos} ano(s) de vida de {$artigo} {$tratamento} {$nome}, {$vinculo} do nosso Irmão {$parentesco}.";
            }

            if ($mensagemCustom !== '') {
                $msg .= ' ' . $mensagemCustom;
            }

            return $msg;
        }

        if (in_array($tipo, ['iniciacao', 'elevacao', 'exaltacao', 'instalacao'], true)) {
            $tipoLabel = $this->labelTipo($tipo);
            $msg = "Neste dia, registramos com honra, {$anos} ano(s) de um passo fundamental em vossa jornada: em {$dataFormatada}, ocorria a vossa {$tipoLabel}, querido Irmão {$nome}, nas colunas da {$local}.";
            if ($mensagemCustom !== '') {
                $msg .= ' ' . $mensagemCustom;
            }
            return $msg;
        }

        if ($tipo === 'orienteeterno') {
            return "Com profundo pesar, lembramos de nosso Irmão {$nome} que partiu para o Oriente Eterno em {$dataFormatada}.";
        }

        if ($mensagemCustom !== '') {
            return $mensagemCustom;
        }

        return "Registro de efeméride sem template para o tipo {$r['tipo']}.";
    }

    private function normalizarTipo(string $tipo): string
    {
        $tipo = strtolower(trim($tipo));
        return strtr($tipo, [
            'á' => 'a',
            'à' => 'a',
            'â' => 'a',
            'ã' => 'a',
            'é' => 'e',
            'ê' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ô' => 'o',
            'õ' => 'o',
            'ú' => 'u',
            'ç' => 'c',
            ' ' => '',
            '-' => '',
            '_' => '',
        ]);
    }

    private function labelTipo(string $tipoNormalizado): string
    {
        $map = [
            'iniciacao' => 'Iniciação',
            'elevacao' => 'Elevação',
            'exaltacao' => 'Exaltação',
            'instalacao' => 'Instalação',
        ];

        return $map[$tipoNormalizado] ?? ucfirst($tipoNormalizado);
    }

    private function tratamentoPorVinculo(int $codVinculo): string
    {
        $map = [
            1 => 'Irmão',
            2 => 'Cunhada',
            3 => 'Sobrinha',
            4 => 'Sobrinho',
            5 => 'Sobrinha',
            6 => 'Sobrinho',
        ];

        return $map[$codVinculo] ?? 'familiar';
    }

    private function calcularAnos(string $dataEvento): int
    {
        if ($dataEvento === '') {
            return 0;
        }

        $base = \DateTimeImmutable::createFromFormat('Y-m-d', $dataEvento);
        if (!$base) {
            return 0;
        }

        $hoje = new \DateTimeImmutable('today');
        return (int) $hoje->diff($base)->y;
    }

    private function formatarData(string $dataEvento): string
    {
        $base = \DateTimeImmutable::createFromFormat('Y-m-d', $dataEvento);
        if (!$base) {
            return $dataEvento !== '' ? $dataEvento : 'data não informada';
        }

        return $base->format('d/m/Y');
    }
}
