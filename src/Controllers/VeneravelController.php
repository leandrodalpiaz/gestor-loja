<?php

namespace App\Controllers;

use App\Models\Balaustre;
use App\Models\Cargo;
use App\Models\Obreiro;
use App\Models\Sessao;

class VeneravelController
{
    public function index(): void
    {
        $sessaoModel = new Sessao();
        $balaustreModel = new Balaustre();
        $cargoModel = new Cargo();
        $obreiroModel = new Obreiro();

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
            'Ate R$ 20' => 0,
            'R$ 20 a R$ 40' => 0,
            'Acima de R$ 40' => 0,
        ];

        foreach ($balaustresConsolidados as $balaustre) {
            $grau = trim((string) ($balaustre['grau_sessao'] ?? ''));
            if ($grau === '') {
                $sessaoSemGrau++;
                $grau = 'Nao informado';
            }
            $sessoesPorGrau[$grau] = ($sessoesPorGrau[$grau] ?? 0) + 1;
            $totalConfirmados += (int) ($balaustre['total_confirmados'] ?? 0);
            $totalAgape += (int) ($balaustre['total_agape'] ?? 0);
            $modalidadeAgape = strtolower(trim((string) ($balaustre['agape_modalidade'] ?? '')));
            $valorAgape = (float) ($balaustre['agape_valor'] ?? 0);
            if ($modalidadeAgape === 'pago') {
                $agapePago++;
                if ($valorAgape <= 20) {
                    $agapePorFaixa['Ate R$ 20']++;
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
            'title' => 'Dashboard estrategico do Veneravel',
            'subtitle' => 'Supervisao institucional com foco analitico e leitura consolidada de sessoes finalizadas.',
            'meta' => [
                'Fonte oficial: balaustres finalizados',
                'Financeiro executivo: leitura resumida',
                'Perfil: estrategico e de supervisao',
            ],
            'actions' => [
                ['label' => 'Abrir votacao', 'href' => '/secretaria/votacao'],
                ['label' => 'Encerrar votacao', 'href' => '/secretaria/votacao'],
                ['label' => 'Painel da Secretaria', 'href' => '/secretaria'],
            ],
            'blocks' => [
                [
                    'title' => 'Sessoes por grau',
                    'subtitle' => 'Apenas sessoes realizadas com balaustre finalizado.',
                    'span' => 'half',
                    'metrics' => [
                        ['label' => 'Sessoes consolidadas', 'value' => (string) count($balaustresConsolidados)],
                        ['label' => 'Grau dominante', 'value' => (string) (array_key_first($sessoesPorGrau) ?? 'Nao definido')],
                        ['label' => 'Sem grau informado', 'value' => (string) $sessaoSemGrau],
                    ],
                    'list' => array_map(static fn (string $grau, int $total): array => [
                        'item' => $grau,
                        'meta' => 'Sessoes realizadas',
                        'status' => (string) $total,
                    ], array_keys(array_slice($sessoesPorGrau, 0, 6, true)), array_values(array_slice($sessoesPorGrau, 0, 6, true))),
                ],
                [
                    'title' => 'Frequencia',
                    'subtitle' => 'Indicador institucional de adesao.',
                    'span' => 'half',
                    'metrics' => [
                        ['label' => 'Media de presenca', 'value' => number_format($frequenciaMedia, 1, ',', '.'), 'hint' => 'Confirmados por sessao finalizada'],
                        ['label' => 'Total confirmados', 'value' => (string) $totalConfirmados],
                        ['label' => 'Aderencia ao agape', 'value' => $formatarPercentual($aderenciaAgape)],
                    ],
                    'list' => array_map(static fn (array $item): array => [
                        'item' => (string) ($item['nome'] ?? 'Obreiro'),
                        'meta' => 'CIM ' . (string) ($item['cim'] ?? '-'),
                        'status' => 'Baixa frequencia',
                    ], array_slice($obreirosPendentesCriticos, 0, 5)),
                ],
                [
                    'title' => 'Agape',
                    'subtitle' => 'Leitura de adesao e modelo adotado, sem arrecadacao.',
                    'span' => 'half',
                    'metrics' => [
                        ['label' => 'Sessoes com agape pago', 'value' => (string) $agapePago],
                        ['label' => 'Sessoes com agape gratuito', 'value' => (string) $agapeGratuito],
                    ],
                    'list' => array_map(static fn (string $faixa, int $total): array => [
                        'item' => $faixa,
                        'meta' => 'Quantidade de sessoes',
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
                        ['label' => 'Inadimplencia', 'value' => $formatarPercentual($inadimplenciaPercentual)],
                    ],
                    'list' => [
                        ['item' => 'Dados operacionais detalhados', 'meta' => 'Disponiveis em Tesouraria', 'status' => 'Contextual'],
                        ['item' => 'Caixa e comprovantes', 'meta' => 'Consumidos como fonte oficial', 'status' => 'Resumo'],
                    ],
                ],
            ],
            'alerts' => [
                ['title' => 'Baixa frequencia', 'text' => 'Obreiros com alertas cadastrais exigem acao conjunta com Secretaria.', 'tone' => 'warning'],
                ['title' => 'Inadimplencia', 'text' => 'Percentual de inadimplencia em ' . $formatarPercentual($inadimplenciaPercentual) . '.', 'tone' => $inadimplenciaPercentual >= 20 ? 'danger' : 'warning'],
                ['title' => 'Ritmo por grau', 'text' => $sessaoSemGrau > 0 ? 'Existem sessoes finalizadas sem grau classificado.' : 'Nao ha ausencia de classificacao de grau.', 'tone' => $sessaoSemGrau > 0 ? 'warning' : 'success'],
            ],
            'activity' => array_map(static fn (array $item): array => [
                'item' => (string) ($item['sessao_titulo'] ?? $item['numero_balaustre'] ?? 'Balaustre'),
                'meta' => 'Status: ' . (string) ($item['status'] ?? 'indefinido'),
            ], array_slice($balaustresPendentesDecisao, 0, 6)),
            'links' => [
                ['label' => 'Dashboard geral', 'href' => '/dashboard'],
                ['label' => 'Secretaria', 'href' => '/secretaria'],
                ['label' => 'Tesouraria', 'href' => '/tesouraria/caixa'],
            ],
        ];

        require_once __DIR__ . '/../Views/veneravel/index.php';
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
            return ['ok' => false, 'erro' => 'Sessao invalida para a acao solicitada.'];
        }

        $sessaoModel = new Sessao();
        $ok = false;
        $erro = 'Nao foi possivel concluir a acao na sessao.';

        switch ($acao) {
            case 'publicar':
                $ok = $sessaoModel->marcarPublicada($sessaoId, $autorId, 'Publicacao autorizada pelo Veneravel Mestre.');
                $erro = 'Nao foi possivel publicar a sessao.';
                break;
            case 'cancelar':
                $ok = $sessaoModel->cancelar($sessaoId, $autorId, 'Cancelamento determinado pelo Veneravel Mestre.');
                $erro = 'Nao foi possivel cancelar a sessao.';
                break;
            case 'reabrir':
                $ok = $sessaoModel->reabrir($sessaoId, $autorId, 'Reabertura determinada pelo Veneravel Mestre.');
                $erro = 'Nao foi possivel reabrir a sessao.';
                break;
            case 'realizar':
                $ok = $sessaoModel->marcarRealizada($sessaoId, $autorId, 'Sessao marcada como realizada pelo Veneravel Mestre.');
                $erro = 'Nao foi possivel marcar a sessao como realizada.';
                break;
        }

        return ['ok' => $ok, 'erro' => $ok ? null : $erro];
    }

    public function executarAcaoBalaustreMiniapp(string $acao, int $balaustreId, ?string $autorId = null): array
    {
        if ($balaustreId <= 0) {
            return ['ok' => false, 'erro' => 'Balaustre invalido para a acao solicitada.'];
        }

        $model = new Balaustre();
        if ($acao === 'abrir') {
            $resultado = $model->abrirVotacao($balaustreId, $autorId);
            return [
                'ok' => (bool) ($resultado['ok'] ?? false),
                'erro' => ($resultado['ok'] ?? false) ? null : (string) ($resultado['erro'] ?? 'Nao foi possivel abrir votacao.'),
            ];
        }

        $resultado = $model->encerrarVotacaoPorBalaustre($balaustreId);
        return [
            'ok' => (bool) ($resultado['ok'] ?? false),
            'erro' => ($resultado['ok'] ?? false) ? null : (string) ($resultado['erro'] ?? 'Nao foi possivel encerrar votacao.'),
        ];
    }

    private function executarAcaoSessao(string $acao): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /veneravel');
            exit;
        }

        $sessaoId = (int) ($_POST['sessao_id'] ?? 0);
        if ($sessaoId <= 0) {
            $_SESSION['mensagem_erro'] = 'Sessao invalida para a acao solicitada.';
            header('Location: /veneravel');
            exit;
        }

        $sessaoModel = new Sessao();
        $autorId = (string) ($_SESSION['usuario_id'] ?? '');
        $autorId = $autorId !== '' ? $autorId : null;

        $ok = false;
        $mensagemSucesso = 'Acao concluida com sucesso.';
        $mensagemErro = 'Nao foi possivel concluir a acao na sessao.';

        switch ($acao) {
            case 'publicar':
                $ok = $sessaoModel->marcarPublicada($sessaoId, $autorId, 'Publicacao autorizada pelo Veneravel Mestre.');
                $mensagemSucesso = 'Sessao publicada com sucesso.';
                $mensagemErro = 'Nao foi possivel publicar a sessao.';
                break;
            case 'cancelar':
                $ok = $sessaoModel->cancelar($sessaoId, $autorId, 'Cancelamento determinado pelo Veneravel Mestre.');
                $mensagemSucesso = 'Sessao cancelada com sucesso.';
                $mensagemErro = 'Nao foi possivel cancelar a sessao.';
                break;
            case 'reabrir':
                $ok = $sessaoModel->reabrir($sessaoId, $autorId, 'Reabertura determinada pelo Veneravel Mestre.');
                $mensagemSucesso = 'Sessao reaberta com sucesso.';
                $mensagemErro = 'Nao foi possivel reabrir a sessao.';
                break;
            case 'realizar':
                $ok = $sessaoModel->marcarRealizada($sessaoId, $autorId, 'Sessao marcada como realizada pelo Veneravel Mestre.');
                $mensagemSucesso = 'Sessao marcada como realizada.';
                $mensagemErro = 'Nao foi possivel marcar a sessao como realizada.';
                break;
        }

        $_SESSION[$ok ? 'mensagem_sucesso' : 'mensagem_erro'] = $ok ? $mensagemSucesso : $mensagemErro;
        header('Location: /veneravel');
        exit;
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
            ? ($acao === 'abrir' ? 'Votacao aberta com sucesso pelo Veneravel Mestre.' : 'Votacao encerrada com sucesso pelo Veneravel Mestre.')
            : (string) ($resultado['erro'] ?? 'Nao foi possivel concluir a acao do balaustre.');
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
}
