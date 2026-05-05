<?php
declare(strict_types=1);

namespace App\Models;

final class BalaustreComposer
{
    public static function build(array $dados): string
    {
        $blocos = is_array($dados['blocos'] ?? null) ? $dados['blocos'] : $dados;
        $linhas = [];

        $cabecalho = trim((string) ($dados['cabecalho'] ?? ''));
        if ($cabecalho !== '') {
            $linhas[] = $cabecalho;
        }

        $linhas[] = self::secao('ABERTURA', self::valor($blocos, 'abertura'));
        $linhas[] = self::secao('BALAÚSTRE', self::valor($blocos, 'balaustre'));
        $linhas[] = self::secao('EXPEDIENTE', self::valor($blocos, 'expediente'));
        $linhas[] = self::secao('SACO DE PROPOSTAS E INFORMAÇÕES', self::valor($blocos, 'saco_propostas'));
        $linhas[] = self::secao('ORDEM DO DIA', self::valor($blocos, 'ordem_dia'));
        $linhas[] = self::secao('TRONCO DE SOLIDARIEDADE', self::valor($blocos, 'tronco_solidariedade'));
        $linhas[] = self::secao(
            'PALAVRA A BEM DA ORDEM EM GERAL E DO QUADRO EM PARTICULAR',
            self::palavraBemOrdem($dados)
        );
        $linhas[] = self::secao('CONCLUSÕES DO ORADOR', self::valor($blocos, 'conclusoes_orador'));
        $linhas[] = self::secao('ENCERRAMENTO', self::valor($blocos, 'encerramento'));
        $linhas[] = self::secao('ASSINATURAS', self::valor($blocos, 'assinaturas'));

        return trim(implode("\n\n", array_filter($linhas, static fn (string $linha): bool => trim($linha) !== '')));
    }

    private static function secao(string $titulo, string $texto): string
    {
        $texto = trim($texto);
        return $titulo . "\n" . ($texto !== '' ? $texto : 'Sem registro.');
    }

    private static function valor(array $blocos, string $chave): string
    {
        return trim((string) ($blocos[$chave] ?? $blocos['bloco_' . $chave] ?? ''));
    }

    private static function palavraBemOrdem(array $dados): string
    {
        $palavra = is_array($dados['palavra_bem_ordem'] ?? null) ? $dados['palavra_bem_ordem'] : [];
        $obreiros = is_array($palavra['obreiros'] ?? null) ? $palavra['obreiros'] : ($dados['palavra_obreiros'] ?? []);
        $visitantes = is_array($palavra['visitantes'] ?? null) ? $palavra['visitantes'] : ($dados['palavra_visitantes'] ?? []);
        $linhas = [];

        foreach ($obreiros as $item) {
            if (!is_array($item)) {
                continue;
            }
            $nome = trim((string) ($item['nome'] ?? ''));
            $cargo = trim((string) ($item['cargo_no_momento'] ?? $item['cargo'] ?? ''));
            $fala = trim((string) ($item['fala_resumida'] ?? $item['fala'] ?? ''));
            if ($nome === '' && $fala === '') {
                continue;
            }
            $linhas[] = trim(($nome !== '' ? $nome : 'Obreiro') . ($cargo !== '' ? ' (' . $cargo . ')' : '') . ': ' . ($fala !== '' ? $fala : 'fez uso da palavra.'));
        }

        foreach ($visitantes as $item) {
            if (!is_array($item)) {
                continue;
            }
            $nome = trim((string) ($item['nome'] ?? ''));
            $loja = trim((string) ($item['loja'] ?? ''));
            $fala = trim((string) ($item['fala_resumida'] ?? $item['fala'] ?? ''));
            if ($nome === '' && $fala === '') {
                continue;
            }
            $linhas[] = trim(($nome !== '' ? $nome : 'Visitante') . ($loja !== '' ? ' - ' . $loja : '') . ': ' . ($fala !== '' ? $fala : 'fez uso da palavra.'));
        }

        return $linhas !== [] ? implode("\n", $linhas) : 'Não houve manifestação registrada.';
    }
}
