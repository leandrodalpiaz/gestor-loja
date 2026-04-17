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
                    echo 'Nenhuma gestao cadastrada para consolidar o relatorio financeiro.';
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
                $obreiroFinanceiroId = trim((string) ($obreiroFinanceiro['id'] ?? $session['usuario_id'] ?? ''));
                if ($obreiroFinanceiroId === '' || $obreiroFinanceiroId === '0') {
                    http_response_code(403);
                    echo 'Nao foi possivel identificar o obreiro para consultar suas obrigacoes.';
                    exit;
                }
                $requirePermission('financeiro.self', 'Acesso restrito ao financeiro do obreiro.');
                $obrigacaoModel = new ObrigacaoFinanceira();
                $resumoObreiro = $obrigacaoModel->obterResumoObreiro($obreiroFinanceiroId);
                $obrigacoesObreiro = $obrigacaoModel->listarPorObreiro($obreiroFinanceiroId);
                require __DIR__ . '/../../Views/minhas_obrigacoes.php';
                return true;

            default:
                return false;
        }
    }
}
