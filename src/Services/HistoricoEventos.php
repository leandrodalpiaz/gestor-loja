<?php

namespace App\Services;

/**
 * Port fiel do historical_events.py.
 * Eventos fixos da Ordem gravados em código.
 * O chanceler pode adicionar ou sobrescrever via banco (tipo="História" em efemerides_registros).
 * Parse mode: HTML (o projeto PHP usa HTML, não Markdown).
 */
class HistoricoEventos
{
    /**
     * Eventos fixos indexados por "MM-DD".
     * Formato: ['titulo' => string, 'ano_ref' => int|null, 'texto' => string (HTML)]
     */
    private static array $fixos = [
        '01-08' => [
            'titulo'  => '08 de Janeiro – Fundação da Grande Loja Maçônica do Estado do Rio Grande do Sul',
            'ano_ref' => 1928,
            'texto'   => 'Hoje celebramos o nascimento da nossa Grande Loja, um marco que redefiniu os rumos da Ordem no Rio Grande do Sul. Em 8 de janeiro de 1928, a cidade de Bagé tornou-se o berço de uma nova era de soberania para os graus simbólicos.'
                . "\n\n" . '<i>Fonte: Registros históricos da Grande Loja do RS e Ato de Fundação de 1928.</i>',
        ],
        '01-27' => [
            'titulo'  => '27 de Janeiro – Nascimento de Mário Behring',
            'ano_ref' => 1876,
            'texto'   => 'Nascido em Minas Gerais em 1876, Mário Behring ingressou na Maçonaria aos 22 anos na Loja União Cosmopolita, onde rapidamente se destacou como defensor da liberdade de pensamento e crítico dos radicalismos religiosos.'
                . "\n\n" . 'Em 1927, diante da concentração irregular de poder no GOB, Behring liderou a histórica fundação do Sistema de Grandes Lojas Brasileiras, restaurando a regularidade e o respeito internacional da Maçonaria no país.'
                . "\n\n" . '<i>Fonte: Site da GLRS – Documentos Históricos.</i>',
        ],
        '02-28' => [
            'titulo'  => '28 de Fevereiro – O Número 270',
            'ano_ref' => 2018,
            'texto'   => 'Em comitiva histórica à Grande Loja, os fundadores protocolaram o pedido de instalação. Foi nesta data que nossa Oficina foi oficialmente acolhida sob o número 270, selando nosso compromisso com a Maçonaria regular gaúcha.'
                . "\n\n" . '<i>Fonte: HISTÓRICO DA LOJA RENASCENÇA Nº 270 no site da Grande Loja do Rio Grande do Sul.</i>',
        ],
        '03-28' => [
            'titulo'  => '28 de Março – Aniversário da A.R.L.S. Renascença nº 270',
            'ano_ref' => 2018,
            'texto'   => 'Hoje celebramos o aniversário de Instalação e Posse da nossa Oficina, um marco de compromisso com a excelência maçônica no Oriente de Arroio do Sal.'
                . "\n\n" . '<b>O Nascimento de um Ideal</b>'
                . "\n" . 'A Loja Renascença nasceu do desejo fraternal de irmãos dedicados a preservar a pureza dos rituais e a rigorosa observância dos Landmarks da nossa Ordem. O nome "Renascença" simboliza justamente o renascer do estudo profundo e da prática ritualística precisa no R.:E.:A.:A.:.'
                . "\n\n" . '<b>Trajetória de Luz</b>'
                . "\n" . 'Desde as primeiras conversas em maio de 2017 até a solene Instalação em 28 de março de 2018, cada passo foi guiado pela harmonia. Sob os auspícios do Grão-Mestrado da GLMERGS, erguemos colunas fortes que hoje abrigam obreiros comprometidos com o aperfeiçoamento moral e espiritual.'
                . "\n\n" . '<b>Nossa Missão</b>'
                . "\n" . 'Ao longo destes anos, a Renascença nº 270 tem se destacado como um ambiente de instrução e fraternidade, honrando o legado de seus fundadores e contribuindo para o engrandecimento da Maçonaria gaúcha.'
                . "\n\n" . 'Parabéns a todos os Obreiros da Renascença nº 270!'
                . "\n" . 'Que nossa Luz continue brilhando com retidão e vigor!'
                . "\n\n" . '<i>Fonte: HISTÓRICO DA LOJA RENASCENÇA Nº 270 no site da Grande Loja do Rio Grande do Sul.</i>',
        ],
        '05-06' => [
            'titulo'  => '06 de Maio – O Despertar de um Novo Ideal',
            'ano_ref' => 2017,
            'texto'   => 'Nesta data, os Irmãos De Latorre, Peixouto, Victor e Vilson, inspirados pela busca da excelência, deram início ao sonho que viria a ser a Loja Renascença.'
                . "\n\n" . 'Após uma sessão no Oriente de Osório, surgiu a visão de fundar uma nova Oficina focada na estrita obediência aos rituais e regulamentos. O objetivo era criar um ambiente dedicado à prática ritualística precisa, traçando ali o planejamento para este nobre intento.'
                . "\n\n" . '<i>Fonte: HISTÓRICO DA LOJA RENASCENÇA Nº 270 no site da Grande Loja do Rio Grande do Sul.</i>',
        ],
        '05-12' => [
            'titulo'  => '12 de Maio – A Primeira Reunião de Planejamento',
            'ano_ref' => 2017,
            'texto'   => 'Neste dia, em Arroio do Sal, os fundadores realizaram a primeira reunião oficial. Com a união de irmãos que comungavam dos mesmos ideais de retidão, definiu-se que a Oficina se chamaria Aug.: Resp.: Loj.: Simb.: Renascença. Um passo decisivo para o fortalecimento da nossa identidade.'
                . "\n\n" . '<i>Fonte: HISTÓRICO DA LOJA RENASCENÇA Nº 270 no site da Grande Loja do Rio Grande do Sul.</i>',
        ],
        '05-25' => [
            'titulo'  => '25 de Maio – O Fortalecimento da Ideia',
            'ano_ref' => 2017,
            'texto'   => 'Em reunião realizada em Tramandaí, o projeto da futura Loja Renascença recebeu o apoio institucional necessário para prosseguir. Com entusiasmo e foco na retidão, definiu-se que a Oficina iniciaria seus trabalhos em Osório, ponto estratégico de união para os Irmãos do litoral.'
                . "\n\n" . '<i>Fonte: HISTÓRICO DA LOJA RENASCENÇA Nº 270 no site da Grande Loja do Rio Grande do Sul.</i>',
        ],
        '08-20' => [
            'titulo'  => 'Dia do Maçom',
            'ano_ref' => null,
            'texto'   => 'Nesta data comemoramos o <b>Dia do Maçom</b>. Que este dia nos convide à reflexão sobre os princípios que nos unem: Liberdade, Igualdade e Fraternidade.',
        ],
        '09-07' => [
            'titulo'  => 'Independência do Brasil',
            'ano_ref' => null,
            'texto'   => 'Nesta data celebramos a <b>Independência do Brasil</b>. Lembramos com orgulho que muitos dos construtores da nossa nação foram Irmãos da Ordem.',
        ],
        '10-25' => [
            'titulo'  => '25 de Outubro – Aprovação de Estatutos e Estandarte',
            'ano_ref' => 2017,
            'texto'   => 'Um marco administrativo. Nesta data, foram aprovados por unanimidade o Estatuto, o Regimento Interno e o Estandarte da Loja. Também foi apresentada a primeira administração, consolidando as bases sólidas sobre as quais nossa Oficina foi erguida.'
                . "\n\n" . '<i>Fonte: HISTÓRICO DA LOJA RENASCENÇA Nº 270 no site da Grande Loja do Rio Grande do Sul.</i>',
        ],
        '12-20' => [
            'titulo'  => '20 de Dezembro – Fundação da Novel Loja',
            'ano_ref' => 2017,
            'texto'   => 'Nesta data solene, foi realizada a Sessão de Fundação da Loja Renascença. Com o quadro de obreiros definido e os ideais renovados, a Oficina preparava-se para sua sagração, marcando o início de suas atividades em prol da Ordem e da sociedade.'
                . "\n\n" . '<i>Fonte: HISTÓRICO DA LOJA RENASCENÇA Nº 270 no site da Grande Loja do Rio Grande do Sul.</i>',
        ],
    ];

    /**
     * Retorna a mensagem histórica para a data consultada.
     * Registros do banco (tipo=História) têm prioridade sobre os fixos.
     *
     * @param array<int, array<string, mixed>> $registrosBancoDoDia
     */
    public static function getParaHoje(array $registrosBancoDoDia, \DateTimeImmutable $hoje): string
    {
        foreach ($registrosBancoDoDia as $registro) {
            $custom = trim((string) ($registro['mensagem_custom'] ?? ''));
            if ($custom !== '') {
                return $custom;
            }
        }

        $chave = $hoje->format('m-d');
        if (!isset(self::$fixos[$chave])) {
            return '';
        }

        $evento = self::$fixos[$chave];
        $header = '<b>' . $evento['titulo'] . '</b>';

        if ($evento['ano_ref'] !== null) {
            $anoAtual = (int) $hoje->format('Y');
            $anos = $anoAtual - (int) $evento['ano_ref'];
            $header .= "\n<i>{$anos} anos ({$evento['ano_ref']})</i>";
        }

        return $header . "\n\n" . $evento['texto'];
    }

    /**
        * Retorna todos os eventos fixos para exibição no painel
        * (usado pela listagem do miniapp de Histórico).
     *
     * @return array<string, array<string, int|string|null>>
     */
    public static function getFixos(): array
    {
        return self::$fixos;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function getFixosComoRegistros(): array
    {
        $registros = [];

        foreach (self::$fixos as $diaMes => $evento) {
            [$mes, $dia] = array_map('intval', explode('-', $diaMes));
            $anoRef = $evento['ano_ref'] !== null ? (int) $evento['ano_ref'] : 2000;
            $dataEvento = sprintf('%04d-%02d-%02d', $anoRef, $mes, $dia);

            $registros[] = [
                'id' => null,
                'nome' => (string) ($evento['titulo'] ?? 'Evento histórico'),
                'tipo' => 'História',
                'data_evento' => $dataEvento,
                'cod_vinculo' => null,
                'vinculo' => null,
                'parentesco' => null,
                'local' => 'Histórico fixo',
                'mensagem_custom' => (string) ($evento['texto'] ?? ''),
                'ativo' => true,
                'origem_fixa' => true,
            ];
        }

        usort($registros, static function (array $a, array $b): int {
            $dataA = (string) ($a['data_evento'] ?? '');
            $dataB = (string) ($b['data_evento'] ?? '');
            $nomeA = (string) ($a['nome'] ?? '');
            $nomeB = (string) ($b['nome'] ?? '');

            $ordemData = strcmp(substr($dataA, 5, 5), substr($dataB, 5, 5));
            if ($ordemData !== 0) {
                return $ordemData;
            }

            return strcmp($nomeA, $nomeB);
        });

        return $registros;
    }
}
