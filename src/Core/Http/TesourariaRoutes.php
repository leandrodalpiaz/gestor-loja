<?php

namespace App\Core\Http;

use App\Config\Database;
use App\Controllers\TesourariaSessaoController;
use App\Core\Authorization\Authorizer;
use App\Models\CategoriaFinanceira;
use App\Models\ConfiguracaoLoja;
use App\Models\Gestao;
use App\Models\ObrigacaoFinanceira;
use App\Models\RelatorioTesourariaGestao;

class TesourariaRoutes
{
    public static function dispatch(
        string $requestUri,
        string $method,
        bool $openTestAccess,
        array $session,
        Authorizer $authorizer,
        callable $requireTesourariaAccess,
        callable $resolveObreiroByInitData,
        callable $loginTelegramObreiroInSession,
        callable $requirePermission
    ): bool {
        switch ($requestUri) {
            case '/miniapp/tesouraria':
                requireMiniappAuth(['tesoureiro', 'veneravel', 'admin'], 'tesouraria.manage');
                require __DIR__ . '/../../Views/miniapp/tesouraria.php';
                return true;

            case '/tesouraria/caixa':
                $requireTesourariaAccess();
                require __DIR__ . '/../../Views/tesouraria_caixa.php';
                return true;

            case '/tesouraria/sessoes':
                $requireTesourariaAccess();
                (new TesourariaSessaoController())->index();
                return true;

            case '/tesouraria/comprovantes':
                $requireTesourariaAccess();
                $configuracaoLoja = (new ConfiguracaoLoja())->obter();
                $categoriasEntrada = (new CategoriaFinanceira())->obterPorTipo('entrada');
                require __DIR__ . '/../../Views/tesouraria_comprovantes.php';
                return true;

            case '/tesouraria/regularidade':
                $requireTesourariaAccess();
                require __DIR__ . '/../../Views/tesouraria_regularidade.php';
                return true;

            case '/tesouraria/fechamento':
                $requireTesourariaAccess();
                require __DIR__ . '/../../Views/tesouraria_fechamento.php';
                return true;

            case '/tesouraria/relatorio-gestao':
                $requireTesourariaAccess();
                $gestaoModel = new Gestao();
                $gestoes = $gestaoModel->listar();
                $gestaoAtual = $gestaoModel->obterAberta();
                $gestaoIdSelecionada = (int) ($_GET['gestao_id'] ?? ($gestaoAtual['id'] ?? ($gestoes[0]['id'] ?? 0)));
                if ($gestaoIdSelecionada <= 0) {
                    http_response_code(404);
                    echo 'Nenhuma gestÃ£o cadastrada para consolidar o relatÃ³rio financeiro.';
                    exit;
                }
                $encerramentoInformado = trim((string) ($_GET['encerramento_em'] ?? ''));
                $relatorio = (new RelatorioTesourariaGestao())->montar(
                    $gestaoIdSelecionada,
                    $encerramentoInformado !== '' ? $encerramentoInformado : null
                );
                require __DIR__ . '/../../Views/tesouraria_relatorio_gestao.php';
                return true;

            case '/tesouraria/obrigacoes':
                $requireTesourariaAccess();
                $obrigacaoModel = new ObrigacaoFinanceira();
                $categoriaModel = new CategoriaFinanceira();
                $configuracaoLoja = (new ConfiguracaoLoja())->obter();
                $obreirosPainel = $obrigacaoModel->listarResumoTesouraria([
                    'busca' => trim((string) ($_GET['busca'] ?? '')),
                    'somente_em_aberto' => !empty($_GET['somente_em_aberto']),
                ]);
                $obreirosCadastro = (new \App\Models\Obreiro())->getAllAtivos();
                $selectedObreiroId = trim((string) ($_GET['obreiro_id'] ?? ($obreirosPainel[0]['id'] ?? '')));
                $selectedObreiroNome = 'Selecione um obreiro';
                foreach ($obreirosCadastro as $obreiroCadastro) {
                    if ((string) ($obreiroCadastro['id'] ?? '') === $selectedObreiroId) {
                        $selectedObreiroNome = (string) ($obreiroCadastro['nome_historico'] ?? $obreiroCadastro['nome'] ?? 'Obreiro');
                        break;
                    }
                }
                $resumoObreiro = $selectedObreiroId !== '' ? $obrigacaoModel->obterResumoObreiro($selectedObreiroId) : [];
                $obrigacoesObreiro = $selectedObreiroId !== '' ? $obrigacaoModel->listarPorObreiro($selectedObreiroId) : [];
                $categoriasEntrada = $categoriaModel->obterPorTipo('entrada');
                require __DIR__ . '/../../Views/tesouraria_obrigacoes.php';
                return true;

            case '/tesouraria/obrigacoes/criar':
                if ($method !== 'POST') {
                    http_response_code(405);
                    exit;
                }
                $requireTesourariaAccess();
                $ok = (new ObrigacaoFinanceira())->criar($_POST, $session['usuario_id'] ?? null);
                $destinoObreiro = trim((string) ($_POST['obreiro_id'] ?? ''));
                header(
                    'Location: /tesouraria/obrigacoes'
                    . ($destinoObreiro !== '' ? '?obreiro_id=' . urlencode($destinoObreiro) : '')
                    . ($destinoObreiro !== '' ? '&' : '?')
                    . ($ok ? 'sucesso=1' : 'erro=1')
                );
                exit;

            case '/tesouraria/obrigacoes/parcela/quitar':
                if ($method !== 'POST') {
                    http_response_code(405);
                    exit;
                }
                $requireTesourariaAccess();
                $parcelaId = (int) ($_POST['parcela_id'] ?? 0);
                $obreiroIdRetorno = trim((string) ($_POST['obreiro_id'] ?? ''));
                $ok = $parcelaId > 0
                    ? (new ObrigacaoFinanceira())->quitarParcela($parcelaId, $_POST, $session['usuario_id'] ?? null)
                    : false;
                header(
                    'Location: /tesouraria/obrigacoes'
                    . ($obreiroIdRetorno !== '' ? '?obreiro_id=' . urlencode($obreiroIdRetorno) : '')
                    . ($obreiroIdRetorno !== '' ? '&' : '?')
                    . ($ok ? 'sucesso=1' : 'erro=1')
                );
                exit;

            case '/tesouraria/obrigacoes/parcela/atualizar':
                if ($method !== 'POST') {
                    http_response_code(405);
                    exit;
                }
                $requireTesourariaAccess();
                $parcelaId = (int) ($_POST['parcela_id'] ?? 0);
                $obreiroIdRetorno = trim((string) ($_POST['obreiro_id'] ?? ''));
                $ok = $parcelaId > 0 ? (new ObrigacaoFinanceira())->atualizarParcela($parcelaId, $_POST) : false;
                header(
                    'Location: /tesouraria/obrigacoes'
                    . ($obreiroIdRetorno !== '' ? '?obreiro_id=' . urlencode($obreiroIdRetorno) : '')
                    . ($obreiroIdRetorno !== '' ? '&' : '?')
                    . ($ok ? 'sucesso=1' : 'erro=1')
                );
                exit;

            case '/tesouraria/obrigacoes/parcela/excluir':
                if ($method !== 'POST') {
                    http_response_code(405);
                    exit;
                }
                $requireTesourariaAccess();
                $parcelaId = (int) ($_POST['parcela_id'] ?? 0);
                $obreiroIdRetorno = trim((string) ($_POST['obreiro_id'] ?? ''));
                $ok = $parcelaId > 0 ? (new ObrigacaoFinanceira())->excluirParcela($parcelaId) : false;
                header(
                    'Location: /tesouraria/obrigacoes'
                    . ($obreiroIdRetorno !== '' ? '?obreiro_id=' . urlencode($obreiroIdRetorno) : '')
                    . ($obreiroIdRetorno !== '' ? '&' : '?')
                    . ($ok ? 'sucesso=1' : 'erro=1')
                );
                exit;

            case '/tesouraria/obrigacoes/parcela/recibo':
                $requireTesourariaAccess();
                $parcelaId = (int) ($_GET['id'] ?? 0);
                $parcelaRecibo = $parcelaId > 0 ? (new ObrigacaoFinanceira())->obterParcelaPorId($parcelaId) : null;
                if (!$parcelaRecibo || (string) ($parcelaRecibo['status'] ?? '') !== 'pago') {
                    http_response_code(404);
                    echo 'Recibo indisponivel para esta parcela.';
                    exit;
                }
                $configuracaoLoja = (new ConfiguracaoLoja())->obter();
                $tesoureiroNome = (string) ($session['usuario_nome'] ?? ($session['usuario_logado']['nome_historico'] ?? 'Tesoureiro'));
                require __DIR__ . '/../../Views/tesouraria_recibo.php';
                exit;

            case '/tesouraria/obrigacoes/mensalidades/gerar':
                if ($method !== 'POST') {
                    http_response_code(405);
                    exit;
                }
                $requireTesourariaAccess();
                $anoGeracao = max(2020, (int) ($_POST['ano_ref'] ?? date('Y')));
                $resultadoGeracao = (new ObrigacaoFinanceira())->gerarMensalidadesAno($anoGeracao, $session['usuario_id'] ?? null);
                $_SESSION['mensagem_sucesso'] = sprintf(
                    'Mensalidades %d: %d geradas, %d ignoradas e %d isentas.',
                    $anoGeracao,
                    $resultadoGeracao['geradas'],
                    $resultadoGeracao['ignoradas'],
                    $resultadoGeracao['isentas']
                );
                header('Location: /tesouraria/obrigacoes');
                exit;

            case '/tesouraria/obrigacoes/biblioteca/designar':
                if ($method !== 'POST') {
                    http_response_code(405);
                    exit;
                }
                $requireTesourariaAccess();
                $obreirosBiblioteca = array_values(array_filter((array) ($_POST['obreiros_biblioteca'] ?? [])));
                $resultadoBiblioteca = (new ObrigacaoFinanceira())->designarBibliotecaMes(
                    max(1, min(12, (int) ($_POST['mes_ref'] ?? date('n')))),
                    max(2020, (int) ($_POST['ano_ref'] ?? date('Y'))),
                    $obreirosBiblioteca,
                    trim((string) ($_POST['observacao'] ?? '')),
                    $session['usuario_id'] ?? null
                );
                $_SESSION['mensagem_sucesso'] = sprintf(
                    'Biblioteca: %d geradas, %d ignoradas e %d isentas.',
                    $resultadoBiblioteca['geradas'],
                    $resultadoBiblioteca['ignoradas'],
                    $resultadoBiblioteca['isentas']
                );
                header('Location: /tesouraria/obrigacoes');
                exit;

            case '/tesouraria/obrigacoes/biblioteca/programar-renascenca':
                if ($method !== 'POST') {
                    http_response_code(405);
                    exit;
                }
                $requireTesourariaAccess();
                $anoBiblioteca = max(2020, (int) ($_POST['ano_ref'] ?? date('Y')));
                $resultadoBiblioteca = (new ObrigacaoFinanceira())->programarBibliotecaRenascencaAno($anoBiblioteca, $session['usuario_id'] ?? null);
                $naoEncontrados = $resultadoBiblioteca['nao_encontrados'] ?? [];
                $_SESSION['mensagem_sucesso'] = sprintf(
                    'Biblioteca %d: %d geradas, %d ignoradas e %d isentas.%s',
                    $anoBiblioteca,
                    $resultadoBiblioteca['geradas'],
                    $resultadoBiblioteca['ignoradas'],
                    $resultadoBiblioteca['isentas'],
                    $naoEncontrados ? ' NÃ£o encontrados: ' . implode(', ', $naoEncontrados) . '.' : ''
                );
                header('Location: /tesouraria/obrigacoes');
                exit;

            case '/tesouraria/obrigacoes/isencao/criar':
                if ($method !== 'POST') {
                    http_response_code(405);
                    exit;
                }
                $requireTesourariaAccess();
                $ok = (new ObrigacaoFinanceira())->registrarIsencao($_POST, $session['usuario_id'] ?? null);
                $obreiroIdRetorno = trim((string) ($_POST['obreiro_id'] ?? ''));
                header(
                    'Location: /tesouraria/obrigacoes'
                    . ($obreiroIdRetorno !== '' ? '?obreiro_id=' . urlencode($obreiroIdRetorno) : '')
                    . ($obreiroIdRetorno !== '' ? '&' : '?')
                    . ($ok ? 'sucesso=1' : 'erro=1')
                );
                exit;
            case '/financeiro/minhas-obrigacoes':
                $obreiroFinanceiro = $session['usuario_logado'] ?? null;
                $isSystemAdmin = !empty($session['is_system_admin'])
                    || !empty($session['force_system_admin'])
                    || (string) ($session['usuario_id'] ?? '') === '0';
                if (!$openTestAccess && !$obreiroFinanceiro) {
                    $initData = trim((string) ($_GET['init_data'] ?? ''));
                    if ($initData !== '') {
                        $obreiroFinanceiro = $resolveObreiroByInitData($initData);
                        if ($obreiroFinanceiro) {
                            $loginTelegramObreiroInSession($obreiroFinanceiro);
                        }
                    }
                }
                if (!$obreiroFinanceiro) {
                    header('Location: /login');
                    exit;
                }

                if (!$isSystemAdmin) {
                    $requirePermission('financeiro.self', 'Acesso restrito ao financeiro do obreiro.');
                }

                $obreiroFinanceiroId = trim((string) ($obreiroFinanceiro['id'] ?? $session['usuario_id'] ?? ''));
                $aba_ativa = trim((string) ($_GET['aba'] ?? 'financeiro'));
                $abas_disponiveis = ['financeiro', 'cadastro', 'familia', 'agenda_trabalhos', 'presencas_eventos', 'alertas_recados'];
                if (!in_array($aba_ativa, $abas_disponiveis, true)) {
                    $aba_ativa = 'financeiro';
                }

                $dados_financeiro = ['resumo' => [], 'obrigacoes' => []];
                $dados_cadastro = [];
                $dados_familia = [];
                $dados_agenda_trabalhos = ['sessoes_futuras' => [], 'trabalhos' => []];
                $dados_presencas_eventos = ['confirmacoes' => []];
                $dados_alertas_recados = ['alertas' => []];
                $estados_vazios = [];
                $mensagens_contextuais = [];
                $obreiroTesouraria = null;
                $acessos_hub = [
                    'dashboard' => $isSystemAdmin || $authorizer->hasPermission('dashboard.view'),
                    'obreiros' => $isSystemAdmin || $authorizer->hasPermission('obreiros.view') || $authorizer->hasPermission('obreiros.manage'),
                    'secretaria' => $isSystemAdmin || $authorizer->hasPermission('secretaria.manage'),
                    'chancelaria' => $isSystemAdmin || $authorizer->hasPermission('chancelaria.manage'),
                    'tesouraria_manage' => $isSystemAdmin || $authorizer->hasPermission('tesouraria.manage'),
                ];

                $obrigacaoModel = new ObrigacaoFinanceira();
                if ($obreiroFinanceiroId !== '' && $obreiroFinanceiroId !== '0') {
                    $obreiroTesouraria = (new \App\Models\Obreiro())->findById($obreiroFinanceiroId);
                }

                if (!$obreiroTesouraria && !$isSystemAdmin) {
                    http_response_code(403);
                    echo 'Não foi possível identificar seu vínculo de obreiro. Atualize seu cadastro com a Secretaria.';
                    exit;
                }

                if ($isSystemAdmin && !$obreiroTesouraria) {
                    $mensagens_contextuais['financeiro'] = 'Administrador do sistema sem vínculo de obreiro. Use o painel da Tesouraria para consultar um obreiro específico.';
                    $estados_vazios['financeiro'] = true;
                    $resumoObreiro = [];
                    $obrigacoesObreiro = [];
                } else {
                    $resumoObreiro = $obrigacaoModel->obterResumoObreiro((string) ($obreiroTesouraria['id'] ?? ''));
                    $obrigacoesObreiro = $obrigacaoModel->listarPorObreiro((string) ($obreiroTesouraria['id'] ?? ''));
                    $dados_financeiro = ['resumo' => $resumoObreiro, 'obrigacoes' => $obrigacoesObreiro];
                    $estados_vazios['financeiro'] = $obrigacoesObreiro === [];
                    if ($estados_vazios['financeiro']) {
                        $mensagens_contextuais['financeiro'] = 'Você não possui obrigações financeiras ativas no momento.';
                    }

                    $dados_cadastro = [
                        'nome' => (string) ($obreiroTesouraria['nome_historico'] ?? $obreiroTesouraria['nome'] ?? ''),
                        'cim' => (string) ($obreiroTesouraria['cim'] ?? ''),
                        'email' => (string) ($obreiroTesouraria['email'] ?? ''),
                        'telefone' => (string) ($obreiroTesouraria['telefone'] ?? ''),
                        'data_nascimento' => (string) ($obreiroTesouraria['data_nascimento'] ?? ''),
                        'estado_civil' => (string) ($obreiroTesouraria['estado_civil'] ?? ''),
                        'endereco' => (string) ($obreiroTesouraria['endereco'] ?? ''),
                        'cidade' => (string) ($obreiroTesouraria['cidade'] ?? ''),
                        'uf' => (string) ($obreiroTesouraria['uf'] ?? ''),
                    ];
                    $pendenciasCadastro = [];
                    foreach (['nome', 'cim', 'email', 'telefone', 'data_nascimento'] as $campo) {
                        if (trim((string) ($dados_cadastro[$campo] ?? '')) === '') {
                            $pendenciasCadastro[] = $campo;
                        }
                    }
                    $dados_cadastro['pendencias'] = $pendenciasCadastro;
                    $estados_vazios['cadastro'] = false;
                    if ($pendenciasCadastro !== []) {
                        $mensagens_contextuais['cadastro'] = 'Seu cadastro tem campos essenciais pendentes.';
                    }

                    $dados_familia = [
                        'estado_civil' => (string) ($obreiroTesouraria['estado_civil'] ?? ''),
                        'conjuge' => (string) ($obreiroTesouraria['nome_conjuge'] ?? ''),
                        'filhos' => (string) ($obreiroTesouraria['filhos'] ?? ''),
                    ];
                    $estados_vazios['familia'] = trim((string) ($dados_familia['conjuge'] . $dados_familia['filhos'])) === '';
                    if ($estados_vazios['familia']) {
                        $mensagens_contextuais['familia'] = 'Dados familiares ainda não informados.';
                    }

                    $sessoesFuturas = (new \App\Models\Sessao())->listarFuturas(8);
                    $trabalhosObreiro = array_values(array_filter(
                        (new \App\Models\TrabalhoSessao())->listarRecentes(30),
                        static fn (array $item): bool => (string) ($item['autor_id'] ?? '') === (string) ($obreiroTesouraria['id'] ?? '')
                    ));
                    $dados_agenda_trabalhos = ['sessoes_futuras' => $sessoesFuturas, 'trabalhos' => $trabalhosObreiro];
                    $estados_vazios['agenda_trabalhos'] = ($sessoesFuturas === [] && $trabalhosObreiro === []);
                    if ($estados_vazios['agenda_trabalhos']) {
                        $mensagens_contextuais['agenda_trabalhos'] = 'Sem compromissos ou trabalhos previstos.';
                    }

                    $confirmacoes = [];
                    $presencaModel = new \App\Models\Presenca();
                    foreach (array_slice($sessoesFuturas, 0, 6) as $sessaoItem) {
                        $sessaoId = (int) ($sessaoItem['id'] ?? 0);
                        if ($sessaoId <= 0) {
                            continue;
                        }
                        foreach ($presencaModel->listarConfirmadosPorSessao($sessaoId) as $confirmado) {
                            if ((string) ($confirmado['id'] ?? '') === (string) ($obreiroTesouraria['id'] ?? '')) {
                                $confirmacoes[] = [
                                    'sessao_titulo' => (string) ($sessaoItem['titulo'] ?? 'Sessão'),
                                    'data_hora_inicio' => (string) ($sessaoItem['data_hora_inicio'] ?? ''),
                                    'status_confirmacao' => 'confirmado',
                                ];
                                break;
                            }
                        }
                    }
                    $dados_presencas_eventos = ['confirmacoes' => $confirmacoes];
                    $estados_vazios['presencas_eventos'] = $confirmacoes === [];
                    if ($estados_vazios['presencas_eventos']) {
                        $mensagens_contextuais['presencas_eventos'] = 'Sem confirmações de presença registradas.';
                    }

                    $alertas = [];
                    if ($pendenciasCadastro !== []) {
                        $alertas[] = 'Pendências cadastrais essenciais.';
                    }
                    if (!empty($resumoObreiro['parcelas_atrasadas'])) {
                        $alertas[] = 'Existem parcelas financeiras em atraso.';
                    }
                    if ($confirmacoes === []) {
                        $alertas[] = 'Sem confirmações recentes de presença.';
                    }
                    $dados_alertas_recados = ['alertas' => $alertas];
                    $estados_vazios['alertas_recados'] = $alertas === [];
                    if ($estados_vazios['alertas_recados']) {
                        $mensagens_contextuais['alertas_recados'] = 'Nenhum alerta crítico no momento.';
                    }
                }

                require __DIR__ . '/../../Views/minhas_obrigacoes.php';
                return true;

            default:
                return false;
        }
    }
}
