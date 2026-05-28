<?php

namespace App\Controllers;

use App\Models\Balaustre;
use App\Models\Cargo;
use App\Models\ConviteExterno;
use App\Models\Obreiro;
use App\Models\Sessao;
use App\Models\TesourariaExecutive;

class VeneravelController
{
    public function index(): void
    {
        $sessaoModel = new Sessao();
        $balaustreModel = new Balaustre();
        $cargoModel = new Cargo();
        $obreiroModel = new Obreiro();
        $conviteModel = new ConviteExterno();
        $tesourariaExec = new TesourariaExecutive();

        $proximaSessao = $sessaoModel->obterProximaSessao();
        $sessoes = $sessaoModel->listarFuturas(8);
        $sessaoSelecionadaId = (int) ($_GET['sessao_id'] ?? 0);
        $sessaoEmFoco = null;
        $balaustresRecentes = $balaustreModel->listarRecentes(20);
        $nominata = $cargoModel->listarResumoCargos();
        $resumoCadastros = $obreiroModel->obterResumoSecretaria();
        $obreirosComPendencia = array_values(array_filter(
            $obreiroModel->listarParaSecretaria(['ordenacao' => 'alerta']),
            static fn (array $item): bool => !empty($item['alertas_cadastro'])
        ));

        if ($sessaoSelecionadaId > 0) {
            $sessaoEmFoco = $sessaoModel->findById($sessaoSelecionadaId);
        }
        if (!$sessaoEmFoco && $proximaSessao && !empty($proximaSessao['id'])) {
            $sessaoEmFoco = $sessaoModel->findById((int) $proximaSessao['id']);
        }

        $tz = new \DateTimeZone('America/Sao_Paulo');
        $mesSelecionado = (int) ($_GET['mes'] ?? date('n'));
        $anoSelecionado = (int) ($_GET['ano'] ?? date('Y'));
        $mesSelecionado = max(1, min(12, $mesSelecionado));
        $anoSelecionado = max(2000, min(2100, $anoSelecionado));
        $inicioMes = new \DateTimeImmutable(sprintf('%04d-%02d-01', $anoSelecionado, $mesSelecionado), $tz);
        $fimMes = $inicioMes->modify('last day of this month');

        $sessoesDoMes = $sessaoModel->listarPorPeriodo($inicioMes->format('Y-m-d'), $fimMes->format('Y-m-d'));
        $convitesDoMes = $conviteModel->listarPorPeriodo($inicioMes->format('Y-m-d'), $fimMes->format('Y-m-d'));
        $tesourariaResumo = $tesourariaExec->resumoMes($mesSelecionado, $anoSelecionado);
        $tesourariaSerie = $tesourariaExec->serieComparativaTresAnos($anoSelecionado);
        $tesourariaSomatorios = $tesourariaExec->somatoriosAnoPorGrupo($anoSelecionado);

        $balaustresAptos = array_values(array_filter(
            $balaustresRecentes,
            static fn (array $item): bool => (string) ($item['status'] ?? '') === 'apto_votacao'
        ));
        $balaustresEmVotacao = array_values(array_filter(
            $balaustresRecentes,
            static fn (array $item): bool => (string) ($item['status'] ?? '') === 'em_votacao'
        ));
        $balaustresPendentesDecisao = array_values(array_filter(
            $balaustresRecentes,
            static fn (array $item): bool => in_array((string) ($item['status'] ?? ''), ['apto_votacao', 'em_votacao'], true)
        ));
        $sessoesPendentesAtencao = array_values(array_filter(
            $sessoes,
            static fn (array $item): bool =>
                in_array((string) ($item['status'] ?? ''), ['planejada', 'alterada', 'cancelada'], true)
                || ((int) ($item['total_confirmados'] ?? 0) < 5)
        ));

        $codigosNominataPrincipal = [
            'VENERAVEL',
            'PRIMEIRO_VIGILANTE',
            'SEGUNDO_VIGILANTE',
            'ORADOR',
            'SECRETARIO',
            'TESOUREIRO',
            'CHANCELER',
            'MESTRE_BANQUETES',
            'GUARDA_DA_LEI',
            'ARQUITETO',
            'MESTRE_DE_HARMONIA',
            'HOSPITALEIRO',
        ];
        $nominataPrincipal = array_values(array_filter(
            $nominata,
            static fn (array $item): bool => in_array((string) ($item['codigo'] ?? ''), $codigosNominataPrincipal, true)
        ));
        $cargosCriticosPendentes = array_values(array_filter(
            $nominataPrincipal,
            static fn (array $item): bool => trim((string) ($item['titular_nome'] ?? '')) === ''
        ));
        $obreirosPendentesCriticos = array_map(function (array $item): array {
            return [
                'nome' => (string) ($item['nome_historico'] ?? $item['nome'] ?? 'Obreiro'),
                'cim' => (string) ($item['cim'] ?? ''),
                'alertas' => array_values($item['alertas_cadastro'] ?? []),
            ];
        }, array_slice($obreirosComPendencia, 0, 8));

        $obreirosAtrasoFraterno = $this->listarAtrasosFraternos($anoSelecionado, $mesSelecionado);

        $formatarPercentual = static fn (float $valor): string => number_format($valor, 1, ',', '.') . '%';
        $balaustresConsolidados = array_values(array_filter(
            $balaustresRecentes,
            static fn (array $item): bool => in_array(
                strtolower(trim((string) ($item['status'] ?? ''))),
                ['finalizado', 'encerrado', 'aprovado'],
                true
            )
        ));
        if ($balaustresConsolidados === []) {
            $balaustresConsolidados = $balaustresRecentes;
        }
        $totalSessoesRealizadas = max(1, count($balaustresConsolidados));
        $sessoesPorGrau = [];
        $totalConfirmados = 0;
        $totalAgape = 0;
        $sessaoSemGrau = 0;
        $agapePago = 0;
        $agapeGratuito = 0;
        $agapePorFaixa = [
            'Até R$ 20' => 0,
            'R$ 20 a R$ 40' => 0,
            'Acima de R$ 40' => 0,
        ];

        foreach ($balaustresConsolidados as $balaustre) {
            $grau = trim((string) ($balaustre['grau_sessao'] ?? ''));
            if ($grau === '') {
                $sessaoSemGrau++;
                $grau = 'Não informado';
            }
            $sessoesPorGrau[$grau] = ($sessoesPorGrau[$grau] ?? 0) + 1;
            $totalConfirmados += (int) ($balaustre['total_confirmados'] ?? 0);
            $totalAgape += (int) ($balaustre['total_agape'] ?? 0);
            $modalidadeAgape = strtolower(trim((string) ($balaustre['agape_modalidade'] ?? '')));
            $valorAgape = (float) ($balaustre['agape_valor'] ?? 0);
            if ($modalidadeAgape === 'pago') {
                $agapePago++;
                if ($valorAgape <= 20) {
                    $agapePorFaixa['Até R$ 20']++;
                } elseif ($valorAgape <= 40) {
                    $agapePorFaixa['R$ 20 a R$ 40']++;
                } else {
                    $agapePorFaixa['Acima de R$ 40']++;
                }
            } elseif ($modalidadeAgape === 'gratuito') {
                $agapeGratuito++;
            }
        }

        arsort($sessoesPorGrau);
        arsort($agapePorFaixa);
        $frequenciaMedia = $totalConfirmados / $totalSessoesRealizadas;
        $aderenciaAgape = $totalConfirmados > 0 ? ($totalAgape / $totalConfirmados) * 100 : 0;
        $inadimplentes = (int) ($resumoCadastros['com_alerta'] ?? 0);
        $baseObreiros = max(1, (int) ($resumoCadastros['ativos'] ?? 0));
        $inadimplenciaPercentual = ($inadimplentes / $baseObreiros) * 100;

        $dashboard = [
            'title' => 'Dashboard estratégico do Venerável',
            'subtitle' => 'Supervisão institucional com foco analítico e leitura consolidada de sessões finalizadas.',
            'meta' => [
                'Fonte oficial: balaustres finalizados',
                'Financeiro executivo: leitura resumida',
                'Perfil: estratégico e de supervisão',
            ],
            'actions' => [
                ['label' => 'Abrir votação', 'href' => '/secretaria/votacao'],
                ['label' => 'Encerrar votação', 'href' => '/secretaria/votacao'],
                ['label' => 'Painel da Secretaria', 'href' => '/secretaria'],
            ],
            'blocks' => [
                [
                    'title' => 'Sessões por grau',
                    'subtitle' => 'Apenas sessões realizadas com balaústre finalizado.',
                    'span' => 'half',
                    'metrics' => [
                        ['label' => 'Sessões consolidadas', 'value' => (string) count($balaustresConsolidados)],
                        ['label' => 'Grau dominante', 'value' => (string) (array_key_first($sessoesPorGrau) ?? 'Não definido')],
                        ['label' => 'Sem grau informado', 'value' => (string) $sessaoSemGrau],
                    ],
                    'list' => array_map(static fn (string $grau, int $total): array => [
                        'item' => $grau,
                        'meta' => 'Sessões realizadas',
                        'status' => (string) $total,
                    ], array_keys(array_slice($sessoesPorGrau, 0, 6, true)), array_values(array_slice($sessoesPorGrau, 0, 6, true))),
                ],
                [
                    'title' => 'Frequência',
                    'subtitle' => 'Indicador institucional de adesão.',
                    'span' => 'half',
                    'metrics' => [
                        ['label' => 'Média de presença', 'value' => number_format($frequenciaMedia, 1, ',', '.'), 'hint' => 'Confirmados por sessão finalizada'],
                        ['label' => 'Total confirmados', 'value' => (string) $totalConfirmados],
                        ['label' => 'Aderência ao ágape', 'value' => $formatarPercentual($aderenciaAgape)],
                    ],
                    'list' => array_map(static fn (array $item): array => [
                        'item' => (string) ($item['nome'] ?? 'Obreiro'),
                        'meta' => 'CIM ' . (string) ($item['cim'] ?? '-'),
                        'status' => 'Baixa frequência',
                    ], array_slice($obreirosPendentesCriticos, 0, 5)),
                ],
                [
                    'title' => 'Ágape',
                    'subtitle' => 'Leitura de adesão e modelo adotado, sem arrecadação.',
                    'span' => 'half',
                    'metrics' => [
                        ['label' => 'Sessões com ágape pago', 'value' => (string) $agapePago],
                        ['label' => 'Sessões com ágape gratuito', 'value' => (string) $agapeGratuito],
                    ],
                    'list' => array_map(static fn (string $faixa, int $total): array => [
                        'item' => $faixa,
                        'meta' => 'Quantidade de sessões',
                        'status' => (string) $total,
                    ], array_keys($agapePorFaixa), array_values($agapePorFaixa)),
                ],
                [
                    'title' => 'Financeiro executivo',
                    'subtitle' => 'Resumo institucional com base em dados de tesouraria.',
                    'span' => 'half',
                    'metrics' => [
                        ['label' => 'Base ativa', 'value' => (string) ($resumoCadastros['ativos'] ?? 0)],
                        ['label' => 'Inadimplentes', 'value' => (string) $inadimplentes],
                        ['label' => 'Inadimplência', 'value' => $formatarPercentual($inadimplenciaPercentual)],
                    ],
                    'list' => [
                        ['item' => 'Dados operacionais detalhados', 'meta' => 'Disponíveis em Tesouraria', 'status' => 'Contextual'],
                        ['item' => 'Caixa e comprovantes', 'meta' => 'Consumidos como fonte oficial', 'status' => 'Resumo'],
                    ],
                ],
            ],
            'alerts' => [
                ['title' => 'Baixa frequência', 'text' => 'Obreiros com alertas cadastrais exigem ação conjunta com Secretaria.', 'tone' => 'warning'],
                ['title' => 'Inadimplência', 'text' => 'Percentual de inadimplência em ' . $formatarPercentual($inadimplenciaPercentual) . '.', 'tone' => $inadimplenciaPercentual >= 20 ? 'danger' : 'warning'],
                ['title' => 'Ritmo por grau', 'text' => $sessaoSemGrau > 0 ? 'Existem sessões finalizadas sem grau classificado.' : 'Não há ausência de classificação de grau.', 'tone' => $sessaoSemGrau > 0 ? 'warning' : 'success'],
            ],
            'activity' => array_map(static fn (array $item): array => [
                'item' => (string) ($item['sessao_titulo'] ?? $item['numero_balaustre'] ?? 'Balaústre'),
                'meta' => 'Status: ' . (string) ($item['status'] ?? 'indefinido'),
            ], array_slice($balaustresPendentesDecisao, 0, 6)),
            'links' => [
                ['label' => 'Dashboard geral', 'href' => '/dashboard'],
                ['label' => 'Secretaria', 'href' => '/secretaria'],
                ['label' => 'Tesouraria', 'href' => '/tesouraria/caixa'],
            ],
        ];

        // View-model explícito: mantém o controller como fonte e evita dependências
        // em variáveis soltas com nomes históricos de outras telas.
        $view = [
            'mes' => $mesSelecionado,
            'ano' => $anoSelecionado,
            'inicio_mes' => $inicioMes,
            'fim_mes' => $fimMes,
            'sessoes_mes' => $sessoesDoMes,
            'convites_mes' => $convitesDoMes,
            'tesouraria_resumo' => $tesourariaResumo,
            'tesouraria_serie' => $tesourariaSerie,
            'tesouraria_somatorios' => $tesourariaSomatorios,
            'balaustres_aptos' => $balaustresAptos,
            'balaustres_em_votacao' => $balaustresEmVotacao,
            'balaustres_pendentes_decisao' => $balaustresPendentesDecisao,
            'obreiros_atraso_fraterno' => $obreirosAtrasoFraterno,
            'nominata_principal' => $nominataPrincipal,
            'cargos_criticos_pendentes' => $cargosCriticosPendentes,
            'obreiros_pendentes_criticos' => $obreirosPendentesCriticos,
            'dashboard' => $dashboard,
            'auxilios_pendentes' => array_values(array_filter(
                (new \App\Models\OcorrenciaAssistencial())->listarRecentes(150),
                static fn (array $item): bool => 
                    !empty($item['necessita_apoio_financeiro']) 
                    && (string) ($item['status'] ?? '') === 'aberta'
            )),
        ];

        require_once __DIR__ . '/../Views/veneravel/index.php';
    }

    public function visualizarBalaustre(): void
    {
        $balaustreId = (int) ($_GET['id'] ?? 0);
        if ($balaustreId <= 0) {
            $_SESSION['mensagem_erro'] = 'Informe o Balaústre para visualizar.';
            header('Location: /veneravel');
            exit;
        }

        $balaustre = (new Balaustre())->buscarPorId($balaustreId);
        if (!$balaustre) {
            $_SESSION['mensagem_erro'] = 'Balaústre não encontrado ou indisponível para esta Loja.';
            header('Location: /veneravel');
            exit;
        }

        require_once __DIR__ . '/../Views/veneravel/visualizar_balaustre.php';
    }

    public function editarBalaustre(): void
    {
        // "Editar" é exceção; a rotina segue na Secretaria.
        $sessaoId = (int) ($_GET['sessao_id'] ?? 0);
        $balaustreSemSessao = (int) ($_GET['balaustre_sem_sessao'] ?? 0);

        if ($sessaoId > 0) {
            header('Location: /secretaria?sessao_id=' . urlencode((string) $sessaoId));
            exit;
        }

        if ($balaustreSemSessao > 0) {
            header('Location: /secretaria?balaustre_sem_sessao=1');
            exit;
        }

        header('Location: /secretaria');
        exit;
    }

    public function sugerirEdicaoBalaustre(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /veneravel');
            exit;
        }

        $balaustreId = (int) ($_POST['balaustre_id'] ?? 0);
        $observacao = trim((string) ($_POST['observacao'] ?? ''));
        if ($balaustreId <= 0 || $observacao === '') {
            $_SESSION['mensagem_erro'] = 'Informe o Balaústre e a observação para seguir.';
            header('Location: /veneravel');
            exit;
        }

        $autorId = (string) ($_SESSION['usuario_id'] ?? '');
        $autorId = $autorId !== '' ? $autorId : null;
        $ok = (new Balaustre())->registrarSugestaoEdicao($balaustreId, $observacao, $autorId);
        $_SESSION[$ok ? 'mensagem_sucesso' : 'mensagem_erro'] = $ok
            ? 'Sugestão de edição registrada e encaminhada para a Secretaria.'
            : 'Não foi possível registrar a sugestão de edição.';
        header('Location: /veneravel');
        exit;
    }

    public function enviarBalaustreParaVotacao(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /veneravel');
            exit;
        }

        $balaustreId = (int) ($_POST['balaustre_id'] ?? 0);
        if ($balaustreId <= 0) {
            $_SESSION['mensagem_erro'] = 'Balaústre inválido para envio à votação.';
            header('Location: /veneravel');
            exit;
        }

        $autorId = (string) ($_SESSION['usuario_id'] ?? '');
        $autorId = $autorId !== '' ? $autorId : null;
        $resultado = (new Balaustre())->abrirVotacao($balaustreId, $autorId);
        $ok = (bool) ($resultado['ok'] ?? false);
        $_SESSION[$ok ? 'mensagem_sucesso' : 'mensagem_erro'] = $ok
            ? 'Balaústre enviado para votação.'
            : (string) ($resultado['erro'] ?? 'Não foi possível enviar o Balaústre para votação.');
        header('Location: /veneravel');
        exit;
    }

    public function publicarSessao(): void
    {
        $this->executarAcaoSessao('publicar');
    }

    public function cancelarSessao(): void
    {
        $this->executarAcaoSessao('cancelar');
    }

    public function reabrirSessao(): void
    {
        $this->executarAcaoSessao('reabrir');
    }

    public function realizarSessao(): void
    {
        $this->executarAcaoSessao('realizar');
    }

    public function abrirVotacaoBalaustre(): void
    {
        $this->executarAcaoBalaustre('abrir');
    }

    public function encerrarVotacaoBalaustre(): void
    {
        $this->executarAcaoBalaustre('encerrar');
    }

    public function montarPayloadMiniapp(?int $sessaoId = null): array
    {
        $sessaoModel = new Sessao();
        $balaustreModel = new Balaustre();
        $cargoModel = new Cargo();
        $obreiroModel = new Obreiro();

        $sessoes = $sessaoModel->listarFuturas(8);
        $proximaSessao = $sessaoModel->obterProximaSessao();
        $sessaoFoco = null;

        if ($sessaoId !== null && $sessaoId > 0) {
            $sessaoFoco = $sessaoModel->findById($sessaoId);
        }
        if (!$sessaoFoco && $proximaSessao && !empty($proximaSessao['id'])) {
            $sessaoFoco = $sessaoModel->findById((int) $proximaSessao['id']);
        }

        $balaustresRecentes = $balaustreModel->listarRecentes(20);
        $balaustresAptos = array_values(array_filter(
            $balaustresRecentes,
            static fn (array $item): bool => (string) ($item['status'] ?? '') === 'apto_votacao'
        ));
        $balaustresEmVotacao = array_values(array_filter(
            $balaustresRecentes,
            static fn (array $item): bool => (string) ($item['status'] ?? '') === 'em_votacao'
        ));
        $nominata = $cargoModel->listarResumoCargos();
        $nominataPrincipal = array_values(array_filter(
            $nominata,
            static fn (array $item): bool => in_array((string) ($item['codigo'] ?? ''), [
                'VENERAVEL',
                'PRIMEIRO_VIGILANTE',
                'SEGUNDO_VIGILANTE',
                'ORADOR',
                'SECRETARIO',
                'TESOUREIRO',
                'CHANCELER',
                'MESTRE_BANQUETES',
                'GUARDA_DA_LEI',
                'ARQUITETO',
                'MESTRE_DE_HARMONIA',
                'HOSPITALEIRO',
            ], true)
        ));
        $cargosCriticosPendentes = array_values(array_filter(
            $nominataPrincipal,
            static fn (array $item): bool => trim((string) ($item['titular_nome'] ?? '')) === ''
        ));
        $resumoCadastros = $obreiroModel->obterResumoSecretaria();
        $obreirosPendentesCriticos = array_values(array_filter(
            $obreiroModel->listarParaSecretaria(['ordenacao' => 'alerta']),
            static fn (array $item): bool => !empty($item['alertas_cadastro'])
        ));

        return [
            'proxima_sessao' => $proximaSessao ? $this->mapearSessao($proximaSessao) : null,
            'sessao_foco' => $sessaoFoco ? $this->mapearSessao($sessaoFoco) : null,
            'sessoes' => array_map(fn (array $sessao): array => $this->mapearSessao($sessao), $sessoes),
            'balaustres_aptos' => array_map(fn (array $item): array => $this->mapearBalaustre($item), $balaustresAptos),
            'balaustres_em_votacao' => array_map(fn (array $item): array => $this->mapearBalaustre($item), $balaustresEmVotacao),
            'nominata_principal' => array_map(static function (array $item): array {
                return [
                    'codigo' => (string) ($item['codigo'] ?? ''),
                    'nome_exibicao' => (string) ($item['nome_exibicao'] ?? 'Cargo'),
                    'titular_nome' => trim((string) ($item['titular_nome'] ?? '')),
                ];
            }, $nominataPrincipal),
            'cargos_criticos_pendentes' => array_map(static function (array $item): array {
                return [
                    'codigo' => (string) ($item['codigo'] ?? ''),
                    'nome_exibicao' => (string) ($item['nome_exibicao'] ?? 'Cargo'),
                ];
            }, $cargosCriticosPendentes),
            'resumo_cadastros' => $resumoCadastros,
            'obreiros_pendentes_criticos' => array_map(static function (array $item): array {
                return [
                    'nome' => (string) ($item['nome_historico'] ?? $item['nome'] ?? 'Obreiro'),
                    'cim' => (string) ($item['cim'] ?? ''),
                    'alertas' => array_values($item['alertas_cadastro'] ?? []),
                ];
            }, array_slice($obreirosPendentesCriticos, 0, 8)),
        ];
    }

    public function executarAcaoSessaoMiniapp(string $acao, int $sessaoId, ?string $autorId = null): array
    {
        if ($sessaoId <= 0) {
            return ['ok' => false, 'erro' => 'Sessão inválida para a ação solicitada.'];
        }

        $sessaoModel = new Sessao();
        $ok = false;
        $erro = 'Não foi possível concluir a ação na sessão.';

        switch ($acao) {
            case 'publicar':
                $ok = $sessaoModel->marcarPublicada($sessaoId, $autorId, 'Publicação autorizada pelo Venerável Mestre.');
                $erro = 'Não foi possível publicar a sessão.';
                break;
            case 'cancelar':
                $ok = $sessaoModel->cancelar($sessaoId, $autorId, 'Cancelamento determinado pelo Venerável Mestre.');
                $erro = 'Não foi possível cancelar a sessão.';
                break;
            case 'reabrir':
                $ok = $sessaoModel->reabrir($sessaoId, $autorId, 'Reabertura determinada pelo Venerável Mestre.');
                $erro = 'Não foi possível reabrir a sessão.';
                break;
            case 'realizar':
                $ok = $sessaoModel->marcarRealizada($sessaoId, $autorId, 'Sessão marcada como realizada pelo Venerável Mestre.');
                $erro = 'Não foi possível marcar a sessão como realizada.';
                break;
        }

        return ['ok' => $ok, 'erro' => $ok ? null : $erro];
    }

    public function executarAcaoBalaustreMiniapp(string $acao, int $balaustreId, ?string $autorId = null): array
    {
        if ($balaustreId <= 0) {
            return ['ok' => false, 'erro' => 'Balaústre inválido para a ação solicitada.'];
        }

        $model = new Balaustre();
        if ($acao === 'abrir') {
            $resultado = $model->abrirVotacao($balaustreId, $autorId);
            return [
                'ok' => (bool) ($resultado['ok'] ?? false),
                'erro' => ($resultado['ok'] ?? false) ? null : (string) ($resultado['erro'] ?? 'Não foi possível abrir votação.'),
            ];
        }

        $resultado = $model->encerrarVotacaoPorBalaustre($balaustreId);
        return [
            'ok' => (bool) ($resultado['ok'] ?? false),
            'erro' => ($resultado['ok'] ?? false) ? null : (string) ($resultado['erro'] ?? 'Não foi possível encerrar votação.'),
        ];
    }

    private function listarAtrasosFraternos(int $ano, int $mes): array
    {
        $ano = max(2000, min(2100, $ano));
        $mes = max(1, min(12, $mes));

        $tz = new \DateTimeZone('America/Sao_Paulo');
        $inicioMes = new \DateTimeImmutable(sprintf('%04d-%02d-01', $ano, $mes), $tz);
        $limite = $this->primeiroDiaUtilMesSeguinte($inicioMes);
        $hoje = new \DateTimeImmutable('today', $tz);
        if ($hoje < $limite) {
            return [];
        }

        $obreiroModel = new Obreiro();
        $lista = $obreiroModel->listarParaSecretaria(['ordenacao' => 'alerta']);
        $atrasos = [];
        foreach ($lista as $item) {
            if (!empty($item['financeiro_pendente']) || !empty($item['pendencias_financeiras'])) {
                $atrasos[] = [
                    'id' => (string) ($item['id'] ?? ''),
                    'nome' => (string) ($item['nome_historico'] ?? $item['nome'] ?? 'Obreiro'),
                ];
            }
        }
        return array_slice($atrasos, 0, 8);
    }

    private function primeiroDiaUtilMesSeguinte(\DateTimeImmutable $inicioMes): \DateTimeImmutable
    {
        $proximo = $inicioMes->modify('first day of next month');
        $dow = (int) $proximo->format('N');
        if ($dow === 6) {
            return $proximo->modify('+2 days');
        }
        if ($dow === 7) {
            return $proximo->modify('+1 day');
        }
        return $proximo;
    }

    private function executarAcaoSessao(string $acao): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /veneravel');
            exit;
        }

        $sessaoId = (int) ($_POST['sessao_id'] ?? 0);
        if ($sessaoId <= 0) {
            $_SESSION['mensagem_erro'] = 'Sessão inválida para a ação solicitada.';
            header('Location: /veneravel');
            exit;
        }

        $sessaoModel = new Sessao();
        $autorId = $this->currentUserUuidOrNull();

        $ok = false;
        $mensagemSucesso = 'Ação concluída com sucesso.';
        $mensagemErro = 'Não foi possível concluir a ação na sessão.';

        switch ($acao) {
            case 'publicar':
                $ok = $sessaoModel->marcarPublicada($sessaoId, $autorId, 'Publicação autorizada pelo Venerável Mestre.');
                $mensagemSucesso = 'Sessão publicada com sucesso.';
                $mensagemErro = 'Não foi possível publicar a sessão.';
                break;
            case 'cancelar':
                $ok = $sessaoModel->cancelar($sessaoId, $autorId, 'Cancelamento determinado pelo Venerável Mestre.');
                $mensagemSucesso = 'Sessão cancelada com sucesso.';
                $mensagemErro = 'Não foi possível cancelar a sessão.';
                break;
            case 'reabrir':
                $ok = $sessaoModel->reabrir($sessaoId, $autorId, 'Reabertura determinada pelo Venerável Mestre.');
                $mensagemSucesso = 'Sessão reaberta com sucesso.';
                $mensagemErro = 'Não foi possível reabrir a sessão.';
                break;
            case 'realizar':
                $ok = $sessaoModel->marcarRealizada($sessaoId, $autorId, 'Sessão marcada como realizada pelo Venerável Mestre.');
                $mensagemSucesso = 'Sessão marcada como realizada.';
                $mensagemErro = 'Não foi possível marcar a sessão como realizada.';
                break;
        }

        $_SESSION[$ok ? 'mensagem_sucesso' : 'mensagem_erro'] = $ok ? $mensagemSucesso : $mensagemErro;
        header('Location: /veneravel');
        exit;
    }

    private function currentUserUuidOrNull(): ?string
    {
        $id = trim((string) ($_SESSION['usuario_id'] ?? ''));
        if ($id === '' || $id === '0') {
            return null;
        }
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $id) === 1 ? $id : null;
    }

    private function executarAcaoBalaustre(string $acao): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /veneravel');
            exit;
        }

        $balaustreId = (int) ($_POST['balaustre_id'] ?? 0);
        $autorId = (string) ($_SESSION['usuario_id'] ?? '');
        $resultado = $this->executarAcaoBalaustreMiniapp($acao, $balaustreId, $autorId !== '' ? $autorId : null);

        $_SESSION[$resultado['ok'] ? 'mensagem_sucesso' : 'mensagem_erro'] = $resultado['ok']
            ? ($acao === 'abrir' ? 'Votação aberta com sucesso pelo Venerável Mestre.' : 'Votação encerrada com sucesso pelo Venerável Mestre.')
            : (string) ($resultado['erro'] ?? 'Não foi possível concluir a ação do balaustre.');
        header('Location: /veneravel');
        exit;
    }

    private function mapearSessao(array $sessao): array
    {
        return [
            'id' => (int) ($sessao['id'] ?? 0),
            'titulo' => (string) ($sessao['titulo'] ?? ''),
            'data_hora_inicio' => (string) ($sessao['data_hora_inicio'] ?? ''),
            'status' => (string) ($sessao['status'] ?? ''),
            'tipo_sessao' => (string) ($sessao['tipo_sessao'] ?? ''),
            'grau_sessao' => (string) ($sessao['grau_sessao'] ?? ''),
            'total_confirmados' => (int) ($sessao['total_confirmados'] ?? 0),
            'total_agape' => (int) ($sessao['total_agape'] ?? 0),
        ];
    }

    private function mapearBalaustre(array $item): array
    {
        return [
            'id' => (int) ($item['id'] ?? 0),
            'numero_balaustre' => (string) ($item['numero_balaustre'] ?? ''),
            'sessao_titulo' => (string) ($item['sessao_titulo'] ?? ''),
            'data_hora_inicio' => (string) ($item['data_hora_inicio'] ?? ''),
            'status' => (string) ($item['status'] ?? ''),
        ];
    }

    public function decidirApoioHospitaleiro(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /veneravel');
            exit;
        }

        $id = (int) ($_POST['ocorrencia_id'] ?? 0);
        $acao = trim((string) ($_POST['acao'] ?? ''));
        $valorStr = trim((string) ($_POST['valor_aprovado'] ?? '0'));
        $justificativa = trim((string) ($_POST['justificativa'] ?? ''));

        if ($id <= 0 || !in_array($acao, ['aprovar', 'recusar'], true)) {
            $_SESSION['mensagem_erro'] = 'Dados inválidos para decidir sobre a solicitação.';
            header('Location: /veneravel');
            exit;
        }

        $valorAprovado = 0.0;
        $status = 'cancelada';
        if ($acao === 'aprovar') {
            $valorStr = str_replace(' ', '', $valorStr);
            if (str_contains($valorStr, ',') && str_contains($valorStr, '.')) {
                $valorStr = str_replace('.', '', $valorStr);
                $valorStr = str_replace(',', '.', $valorStr);
            } elseif (str_contains($valorStr, ',')) {
                $valorStr = str_replace(',', '.', $valorStr);
            }
            $valorAprovado = is_numeric($valorStr) ? (float) $valorStr : 0.0;

            if ($valorAprovado <= 0) {
                $_SESSION['mensagem_erro'] = 'Para aprovar, informe um valor maior que zero.';
                header('Location: /veneravel');
                exit;
            }
            $status = 'em_acompanhamento';
        }

        $autorId = $this->currentUserUuidOrNull();
        $model = new \App\Models\OcorrenciaAssistencial();
        
        $ok = $model->decidirApoio($id, $status, $valorAprovado, $justificativa !== '' ? $justificativa : null, $autorId);
        
        $_SESSION[$ok ? 'mensagem_sucesso' : 'mensagem_erro'] = $ok
            ? 'Decisão sobre auxílio assistencial gravada com sucesso.'
            : 'Não foi possível gravar a decisão sobre o auxílio.';

        header('Location: /veneravel');
        exit;
    }
}
