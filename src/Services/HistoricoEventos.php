<?php

namespace App\Services;

/**
 * Port fiel do historical_events.py.
 * Eventos fixos da Ordem gravados em código.
 * O chanceler pode adicionar/sobrescrever via banco (tipo="História" em efemerides_registros).
 * Parse mode: HTML (o projeto PHP usa HTML, não Markdown).
 */
class HistoricoEventos
{
    /**
     * Eventos fixos indexados por "MM-DD".
     * formato: ['titulo' => string, 'ano_ref' => int|null, 'texto' => string (HTML)]
     */
    private static array $fixos = [
        '01-08' => [
            'titulo'  => '08 de Janeiro – Fundação da Grande Loja Maçônica do Estado do Rio Grande do Sul',
            'ano_ref' => 1928,
            'texto'   => 'Hoje celebramos o nascimento da nossa Grande Loja, um marco que redefiniu os rumos da Ordem no Rio Grande do Sul. Em 8 de janeiro de 1928, a cidade de Bagé tornou-se o berço de uma nova era de soberania para os graus simbólicos.' . "\n\n" . '<i>Fonte: Registros históricos da Grande Loja do RS e Ato de Fundação de 1928.</i>',
        ],
        '01-27' => [
            'titulo'  => '27 de Janeiro – Nascimento de Mário Behring',
            'ano_ref' => 1876,
            'texto'   => 'Nascido em Minas Gerais em 1876, Mário Behring ingressou na Maçonaria aos 22 anos na Loja União Cosmopolita, onde rapidamente se destacou como defensor da liberdade de pensamento e crítico dos radicalismos religiosos.' . "\n\n" . 'Em 1927, diante da concentração irregular de poder no GOB, Behring liderou a histórica fundação do Sistema de Grandes Lojas Brasileiras, restaurando a regularidade e o respeito internacional da Maçonaria no país.' . "\n\n" . '<i>Fonte: Site da GLRS – Documentos Históricos.</i>',
        ],
        '08-20' => [
            'titulo'  => 'Dia do Maçom',
            'ano_ref' => null,
            'texto'   => '📜 Nesta data comemoramos o <b>Dia do Maçom</b>. Que este dia nos convide à reflexão sobre os princípios que nos unem: Liberdade, Igualdade e Fraternidade.',
        ],
        '09-07' => [
            'titulo'  => 'Independência do Brasil',
            'ano_ref' => null,
            'texto'   => '📜 Nesta data celebramos a <b>Independência do Brasil</b>. Lembramos com orgulho que muitos dos construtores da nossa nação foram Irmãos da Ordem.',
        ],
    ];

    /**
     * Retorna a mensagem histórica para hoje.
     * Registros do banco (tipo=História) têm prioridade sobre os fixos.
     *
     * @param array $registrosBancoDoDia Resultado de EfemerideRegistro::getRegistrosDoDia()
     *                                   já filtrado pelo chamador para tipo=História
     * @param \DateTimeImmutable $hoje
     */
    public static function getParaHoje(array $registrosBancoDoDia, \DateTimeImmutable $hoje): string
    {
        // Banco prevalece: primeiro registro Historia com mensagem_custom preenchida
        foreach ($registrosBancoDoDia as $r) {
            $custom = trim((string) ($r['mensagem_custom'] ?? ''));
            if ($custom !== '') {
                return $custom;
            }
        }

        // Fallback: dicionário fixo
        $chave = $hoje->format('m-d');
        if (!isset(self::$fixos[$chave])) {
            return '';
        }

        $ev = self::$fixos[$chave];
        $anoAtual = (int) $hoje->format('Y');
        $anos = ($ev['ano_ref'] !== null) ? ($anoAtual - $ev['ano_ref']) : null;

        $header = '<b>' . $ev['titulo'] . '</b>';
        if ($anos !== null) {
            $header .= "\n<i>{$anos} anos ({$ev['ano_ref']})</i>";
        }

        return $header . "\n\n" . $ev['texto'];
    }

    /**
     * Retorna todos os eventos fixos para exibição no painel
     * (usado pela listagem do Mini App de Histórico).
     */
    public static function getFixos(): array
    {
        return self::$fixos;
    }
}
