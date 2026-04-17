<?php

namespace App\Core\Http;

use App\Controllers\ChancelerSessaoController;
use App\Controllers\VeneravelController;
use App\Models\Cargo;
use App\Models\EfemeridePreviaDiaria;
use App\Models\EfemerideRegistro;
use App\Models\HistoriaMaconica;
use App\Models\Obreiro;
use App\Models\PalavraDia;
use App\Services\TelegramService;

class ChancelariaRoutes
{
    public static function dispatch(
        string $requestUri,
        string $method,
        bool $openTestAccess,
        array $session,
        callable $sessionHasPermission,
        callable $appToday,
        callable $buildEfemeridesPreview,
        callable $redirectEfemerides,
        callable $contentPermissionService,
        callable $canManageContentCategory
    ): bool {
        switch ($requestUri) {
            case '/chanceler/sessao':
            case '/chanceler/sessao/dashboard':
                WebGuards::requireLogin($openTestAccess, $session);
                if (
                    !$sessionHasPermission('chancelaria.manage')
                    && !$sessionHasPermission('veneravel.manage')
                    &&
                    !$canManageContentCategory('efemerides')
                    && !$canManageContentCategory('historia')
                    && !$canManageContentCategory('palavra_dia')
                ) {
                    WebGuards::forbidHtml('Acesso restrito aos responsaveis pelos conteudos da Chancelaria.');
                }
                (new ChancelerSessaoController())->index();
                return true;

            case '/chanceler/sessao/presenca':
                self::requirePermission($openTestAccess, $session, $sessionHasPermission, 'chancelaria.manage', 'Acesso restrito ao Chanceler, Veneravel Mestre ou Administrador.');
                (new ChancelerSessaoController())->registrarPresenca();
                return true;

            case '/veneravel/sessoes/publicar':
                self::requirePermission($openTestAccess, $session, $sessionHasPermission, 'veneravel.manage', 'Acesso restrito ao Veneravel Mestre ou Administrador.');
                (new VeneravelController())->publicarSessao();
                return true;

            case '/veneravel/sessoes/cancelar':
                self::requirePermission($openTestAccess, $session, $sessionHasPermission, 'veneravel.manage', 'Acesso restrito ao Veneravel Mestre ou Administrador.');
                (new VeneravelController())->cancelarSessao();
                return true;

            case '/veneravel/sessoes/reabrir':
                self::requirePermission($openTestAccess, $session, $sessionHasPermission, 'veneravel.manage', 'Acesso restrito ao Veneravel Mestre ou Administrador.');
                (new VeneravelController())->reabrirSessao();
                return true;

            case '/veneravel/sessoes/realizar':
                self::requirePermission($openTestAccess, $session, $sessionHasPermission, 'veneravel.manage', 'Acesso restrito ao Veneravel Mestre ou Administrador.');
                (new VeneravelController())->realizarSessao();
                return true;

            case '/veneravel/balaustres/abrir-votacao':
                self::requirePermission($openTestAccess, $session, $sessionHasPermission, 'veneravel.manage', 'Acesso restrito ao Veneravel Mestre ou Administrador.');
                (new VeneravelController())->abrirVotacaoBalaustre();
                return true;

            case '/veneravel/balaustres/encerrar-votacao':
                self::requirePermission($openTestAccess, $session, $sessionHasPermission, 'veneravel.manage', 'Acesso restrito ao Veneravel Mestre ou Administrador.');
                (new VeneravelController())->encerrarVotacaoBalaustre();
                return true;

            case '/chancelaria/efemerides/salvar-previa':
                self::requirePermission($openTestAccess, $session, $sessionHasPermission, 'chancelaria.manage', 'Acesso restrito ao Chanceler, Veneravel Mestre ou Administrador.');
                if ($method !== 'POST') {
                    $redirectEfemerides();
                }

                $mensagemPreview = trim((string) ($_POST['mensagem_preview'] ?? ''));
                if ($mensagemPreview === '') {
                    $redirectEfemerides(['erro' => 'previa_vazia']);
                }

                $ok = (new EfemeridePreviaDiaria())->salvarOuAtualizar(
                    $appToday()->format('Y-m-d'),
                    $mensagemPreview,
                    false
                );

                $redirectEfemerides($ok ? ['sucesso' => 'previa_salva'] : ['erro' => 'falha_salvar_previa']);

            case '/chancelaria/efemerides/enviar-previa':
                self::requirePermission($openTestAccess, $session, $sessionHasPermission, 'chancelaria.manage', 'Acesso restrito ao Chanceler, Veneravel Mestre ou Administrador.');
                if ($method !== 'POST') {
                    $redirectEfemerides();
                }

                $dadosEfemerides = $buildEfemeridesPreview();
                $mensagemPreview = trim((string) ($dadosEfemerides['mensagemPreview'] ?? ''));
                if ($mensagemPreview === '') {
                    $redirectEfemerides(['erro' => 'previa_vazia']);
                }

                $telegramService = new TelegramService();
                $chatPrivadoDestino = trim((string) ($session['usuario_logado']['telegram_id'] ?? ''));
                if ($chatPrivadoDestino === '') {
                    $chatPrivadoDestino = trim((string) ($_ENV['TELEGRAM_CHAT_ID_CHANCELER'] ?? ''));
                }
                $ok = $telegramService->sendMessageToChat($chatPrivadoDestino, $mensagemPreview);
                $redirectEfemerides($ok
                    ? ['sucesso' => 'previa_enviada']
                    : ['erro' => 'falha_enviar_previa', 'detalhe' => $telegramService->getLastError()]);

            case '/chancelaria/efemerides/enviar-grupo':
                self::requirePermission($openTestAccess, $session, $sessionHasPermission, 'chancelaria.manage', 'Acesso restrito ao Chanceler, Veneravel Mestre ou Administrador.');
                if ($method !== 'POST') {
                    $redirectEfemerides();
                }

                $dadosEfemerides = $buildEfemeridesPreview();
                $mensagemPreview = trim((string) ($dadosEfemerides['mensagemPreview'] ?? ''));
                if ($mensagemPreview === '') {
                    $redirectEfemerides(['erro' => 'previa_vazia']);
                }

                $telegramService = new TelegramService();
                $ok = $telegramService->sendMessageToGroup($mensagemPreview);
                $redirectEfemerides($ok
                    ? ['sucesso' => 'enviado']
                    : ['erro' => 'falha_enviar_grupo', 'detalhe' => $telegramService->getLastError()]);

            case '/chancelaria/efemerides/salvar':
                self::requirePermission($openTestAccess, $session, $sessionHasPermission, 'chancelaria.manage', 'Acesso restrito ao Chanceler, Veneravel Mestre ou Administrador.');
                if ($method !== 'POST') {
                    $redirectEfemerides();
                }

                $nome = trim((string) ($_POST['nome'] ?? ''));
                $tipo = trim((string) ($_POST['tipo'] ?? ''));
                $dataEvento = trim((string) ($_POST['data_evento'] ?? ''));
                $dataValida = \DateTimeImmutable::createFromFormat('Y-m-d', $dataEvento) !== false;
                if ($nome === '' || $tipo === '' || $dataEvento === '' || !$dataValida) {
                    $redirectEfemerides(['erro' => 'registro_invalido']);
                }

                $createdBy = isset($session['usuario_id']) ? (int) $session['usuario_id'] : null;
                $ok = (new EfemerideRegistro())->create($_POST, $createdBy);
                $redirectEfemerides($ok ? ['sucesso' => 'registro_salvo'] : ['erro' => 'falha_salvar_registro']);

            case '/chancelaria/efemerides/atualizar':
                self::requirePermission($openTestAccess, $session, $sessionHasPermission, 'chancelaria.manage', 'Acesso restrito ao Chanceler, Veneravel Mestre ou Administrador.');
                if ($method !== 'POST') {
                    $redirectEfemerides();
                }

                $registroId = (int) ($_POST['registro_id'] ?? 0);
                $nome = trim((string) ($_POST['nome'] ?? ''));
                $tipo = trim((string) ($_POST['tipo'] ?? ''));
                $dataEvento = trim((string) ($_POST['data_evento'] ?? ''));
                $dataValida = \DateTimeImmutable::createFromFormat('Y-m-d', $dataEvento) !== false;

                if ($registroId <= 0 || $nome === '' || $tipo === '' || $dataEvento === '' || !$dataValida) {
                    $redirectEfemerides(['erro' => 'registro_invalido']);
                }

                $ok = (new EfemerideRegistro())->atualizar($registroId, $_POST);
                $redirectEfemerides($ok ? ['sucesso' => 'registro_atualizado'] : ['erro' => 'falha_atualizar_registro']);

            case '/chancelaria/efemerides/desativar':
                self::requirePermission($openTestAccess, $session, $sessionHasPermission, 'chancelaria.manage', 'Acesso restrito ao Chanceler, Veneravel Mestre ou Administrador.');
                if ($method !== 'POST') {
                    $redirectEfemerides();
                }

                $registroId = (int) ($_POST['id'] ?? 0);
                if ($registroId <= 0) {
                    $redirectEfemerides(['erro' => 'id_invalido']);
                }

                $ok = (new EfemerideRegistro())->desativar($registroId);
                $redirectEfemerides($ok ? ['sucesso' => 'registro_desativado'] : ['erro' => 'falha_desativar']);

            case '/chancelaria/efemerides/excluir':
                WebGuards::requireLogin($openTestAccess, $session);
                if (!$canManageContentCategory('efemerides')) {
                    WebGuards::forbidHtml('Acesso restrito ao Chanceler, Veneravel Mestre ou Administrador.');
                }
                if ($method !== 'POST') {
                    $redirectEfemerides();
                }

                $registroId = (int) ($_POST['id'] ?? 0);
                if ($registroId <= 0) {
                    $redirectEfemerides(['erro' => 'id_invalido']);
                }

                $ok = (new EfemerideRegistro())->excluir($registroId);
                $redirectEfemerides($ok ? ['sucesso' => 'registro_desativado'] : ['erro' => 'falha_desativar']);

            case '/chancelaria/historias/salvar':
                self::requireManageCategory($openTestAccess, $session, $canManageContentCategory, 'historia', 'Acesso restrito aos responsaveis por Historia Maconica.');
                if ($method !== 'POST') {
                    $redirectEfemerides();
                }
                $titulo = trim((string) ($_POST['titulo'] ?? ''));
                $texto = trim((string) ($_POST['texto'] ?? ''));
                $dia = (int) ($_POST['dia'] ?? 0);
                $mes = (int) ($_POST['mes'] ?? 0);
                if ($titulo === '' || $texto === '' || $dia <= 0 || $mes <= 0) {
                    $redirectEfemerides(['erro' => 'historia_invalida']);
                }
                $ok = (new HistoriaMaconica())->create($_POST, isset($session['usuario_id']) ? (int) $session['usuario_id'] : null);
                $redirectEfemerides($ok ? ['sucesso' => 'historia_salva'] : ['erro' => 'falha_salvar_historia']);

            case '/chancelaria/historias/atualizar':
                self::requireManageCategory($openTestAccess, $session, $canManageContentCategory, 'historia', 'Acesso restrito aos responsaveis por Historia Maconica.');
                if ($method !== 'POST') {
                    $redirectEfemerides();
                }
                $historiaId = (int) ($_POST['historia_id'] ?? 0);
                $titulo = trim((string) ($_POST['titulo'] ?? ''));
                $texto = trim((string) ($_POST['texto'] ?? ''));
                $dia = (int) ($_POST['dia'] ?? 0);
                $mes = (int) ($_POST['mes'] ?? 0);
                if ($historiaId <= 0 || $titulo === '' || $texto === '' || $dia <= 0 || $mes <= 0) {
                    $redirectEfemerides(['erro' => 'historia_invalida']);
                }
                $ok = (new HistoriaMaconica())->atualizar($historiaId, $_POST);
                $redirectEfemerides($ok ? ['sucesso' => 'historia_atualizada'] : ['erro' => 'falha_atualizar_historia']);

            case '/chancelaria/historias/toggle':
                self::requireManageCategory($openTestAccess, $session, $canManageContentCategory, 'historia', 'Acesso restrito aos responsaveis por Historia Maconica.');
                if ($method !== 'POST') {
                    $redirectEfemerides();
                }
                $historiaId = (int) ($_POST['id'] ?? 0);
                $ok = $historiaId > 0 ? (new HistoriaMaconica())->toggleAtivo($historiaId) : false;
                $redirectEfemerides($ok ? ['sucesso' => 'historia_status'] : ['erro' => 'falha_status_historia']);

            case '/chancelaria/historias/excluir':
                self::requireManageCategory($openTestAccess, $session, $canManageContentCategory, 'historia', 'Acesso restrito aos responsaveis por Historia Maconica.');
                if ($method !== 'POST') {
                    $redirectEfemerides();
                }
                $historiaId = (int) ($_POST['id'] ?? 0);
                $ok = $historiaId > 0 ? (new HistoriaMaconica())->excluir($historiaId) : false;
                $redirectEfemerides($ok ? ['sucesso' => 'historia_excluida'] : ['erro' => 'falha_excluir_historia']);

            case '/chancelaria/palavra-dia/salvar':
                self::requireManageCategory($openTestAccess, $session, $canManageContentCategory, 'palavra_dia', 'Acesso restrito aos responsaveis por Palavra do Dia.');
                if ($method !== 'POST') {
                    $redirectEfemerides();
                }
                $mensagem = trim((string) ($_POST['mensagem'] ?? ''));
                if ($mensagem === '') {
                    $redirectEfemerides(['erro' => 'palavra_invalida']);
                }
                $ok = (new PalavraDia())->create($_POST, isset($session['usuario_id']) ? (int) $session['usuario_id'] : null);
                $redirectEfemerides($ok ? ['sucesso' => 'palavra_salva'] : ['erro' => 'falha_salvar_palavra']);

            case '/chancelaria/palavra-dia/atualizar':
                self::requireManageCategory($openTestAccess, $session, $canManageContentCategory, 'palavra_dia', 'Acesso restrito aos responsaveis por Palavra do Dia.');
                if ($method !== 'POST') {
                    $redirectEfemerides();
                }
                $palavraId = (int) ($_POST['palavra_id'] ?? 0);
                $mensagem = trim((string) ($_POST['mensagem'] ?? ''));
                if ($palavraId <= 0 || $mensagem === '') {
                    $redirectEfemerides(['erro' => 'palavra_invalida']);
                }
                $ok = (new PalavraDia())->atualizar($palavraId, $_POST);
                $redirectEfemerides($ok ? ['sucesso' => 'palavra_atualizada'] : ['erro' => 'falha_atualizar_palavra']);

            case '/chancelaria/palavra-dia/toggle':
                self::requireManageCategory($openTestAccess, $session, $canManageContentCategory, 'palavra_dia', 'Acesso restrito aos responsaveis por Palavra do Dia.');
                if ($method !== 'POST') {
                    $redirectEfemerides();
                }
                $palavraId = (int) ($_POST['id'] ?? 0);
                $ok = $palavraId > 0 ? (new PalavraDia())->toggleAtivo($palavraId) : false;
                $redirectEfemerides($ok ? ['sucesso' => 'palavra_status'] : ['erro' => 'falha_status_palavra']);

            case '/chancelaria/palavra-dia/excluir':
                self::requireManageCategory($openTestAccess, $session, $canManageContentCategory, 'palavra_dia', 'Acesso restrito aos responsaveis por Palavra do Dia.');
                if ($method !== 'POST') {
                    $redirectEfemerides();
                }
                $palavraId = (int) ($_POST['id'] ?? 0);
                $ok = $palavraId > 0 ? (new PalavraDia())->excluir($palavraId) : false;
                $redirectEfemerides($ok ? ['sucesso' => 'palavra_excluida'] : ['erro' => 'falha_excluir_palavra']);

            case '/chancelaria/conteudo-permissoes/salvar':
                self::requirePermission($openTestAccess, $session, $sessionHasPermission, 'chancelaria.manage', 'Acesso restrito ao Chanceler, Veneravel Mestre ou Administrador.');
                if ($method !== 'POST') {
                    $redirectEfemerides();
                }
                $okHistoria = $contentPermissionService()->salvarDelegacoes('historia', $_POST['delegacoes_historia'] ?? []);
                $okPalavra = $contentPermissionService()->salvarDelegacoes('palavra_dia', $_POST['delegacoes_palavra_dia'] ?? []);
                $redirectEfemerides(($okHistoria && $okPalavra) ? ['sucesso' => 'permissoes_salvas'] : ['erro' => 'falha_salvar_permissoes']);

            case '/chancelaria/efemerides':
                self::requirePermission($openTestAccess, $session, $sessionHasPermission, 'chancelaria.manage', 'Acesso restrito ao Chanceler, Veneravel Mestre ou Administrador.');

                $sucessoMensagem = match ((string) ($_GET['sucesso'] ?? '')) {
                    'previa_salva' => 'Previa salva com sucesso.',
                    'previa_enviada' => 'Previa enviada no privado do Chanceler.',
                    'enviado' => 'Mensagem enviada ao grupo oficial.',
                    'registro_salvo' => 'Registro salvo com sucesso.',
                    'registro_atualizado' => 'Registro atualizado com sucesso.',
                    'registro_desativado' => 'Registro desativado com sucesso.',
                    'historia_salva' => 'Historia salva com sucesso.',
                    'historia_atualizada' => 'Historia atualizada com sucesso.',
                    'historia_status' => 'Status da historia atualizado com sucesso.',
                    'historia_excluida' => 'Historia excluida com sucesso.',
                    'palavra_salva' => 'Palavra do Dia salva com sucesso.',
                    'palavra_atualizada' => 'Palavra do Dia atualizada com sucesso.',
                    'palavra_status' => 'Status da Palavra do Dia atualizado com sucesso.',
                    'palavra_excluida' => 'Palavra do Dia excluida com sucesso.',
                    'permissoes_salvas' => 'Permissoes por categoria atualizadas com sucesso.',
                    default => null,
                };

                $erroMensagem = match ((string) ($_GET['erro'] ?? '')) {
                    'previa_vazia' => 'A mensagem da previa nao pode ficar vazia.',
                    'falha_salvar_previa' => 'Nao foi possivel salvar a previa.',
                    'falha_enviar_previa' => 'Falha ao enviar a previa no privado.' . (!empty($_GET['detalhe']) ? ' Detalhe: ' . (string) $_GET['detalhe'] : ''),
                    'falha_enviar_grupo' => 'Falha ao enviar no grupo oficial.' . (!empty($_GET['detalhe']) ? ' Detalhe: ' . (string) $_GET['detalhe'] : ''),
                    'registro_invalido' => 'Preencha nome, tipo e data do evento corretamente.',
                    'falha_salvar_registro' => 'Nao foi possivel salvar o registro.',
                    'falha_atualizar_registro' => 'Nao foi possivel atualizar o registro.',
                    'id_invalido' => 'Registro invalido para desativacao.',
                    'falha_desativar' => 'Nao foi possivel desativar o registro.',
                    'historia_invalida' => 'Preencha os dados da historia corretamente.',
                    'falha_salvar_historia' => 'Nao foi possivel salvar a historia.',
                    'falha_atualizar_historia' => 'Nao foi possivel atualizar a historia.',
                    'falha_status_historia' => 'Nao foi possivel atualizar o status da historia.',
                    'falha_excluir_historia' => 'Nao foi possivel excluir a historia.',
                    'palavra_invalida' => 'Preencha os dados da Palavra do Dia corretamente.',
                    'falha_salvar_palavra' => 'Nao foi possivel salvar a Palavra do Dia.',
                    'falha_atualizar_palavra' => 'Nao foi possivel atualizar a Palavra do Dia.',
                    'falha_status_palavra' => 'Nao foi possivel atualizar o status da Palavra do Dia.',
                    'falha_excluir_palavra' => 'Nao foi possivel excluir a Palavra do Dia.',
                    'falha_salvar_permissoes' => 'Nao foi possivel salvar as permissoes por categoria.',
                    default => null,
                };

                $dadosEfemerides = $buildEfemeridesPreview();
                $registrosHoje = $dadosEfemerides['registrosHoje'];
                $filtroIrmaoRef = trim((string) ($_GET['irmao_ref'] ?? ''));
                $filtroTermo = trim((string) ($_GET['termo'] ?? ''));
                $filtroTipo = trim((string) ($_GET['tipo'] ?? ''));
                $filtroVinculo = trim((string) ($_GET['vinculo'] ?? ''));
                $filtroAtivo = trim((string) ($_GET['ativo'] ?? '1'));
                $filtroDataIni = trim((string) ($_GET['data_ini'] ?? ''));
                $filtroDataFim = trim((string) ($_GET['data_fim'] ?? ''));
                $focoEfemeride = trim((string) ($_GET['foco'] ?? ''));
                $filtrosEfemeride = [
                    'irmao_ref' => $filtroIrmaoRef,
                    'termo' => $filtroTermo,
                    'tipo' => $filtroTipo,
                    'vinculo' => $filtroVinculo,
                    'ativo' => $filtroAtivo,
                    'data_ini' => $filtroDataIni,
                    'data_fim' => $filtroDataFim,
                ];
                $registroModel = new EfemerideRegistro();
                $registrosRecentes = $registroModel->buscarComFiltros($filtrosEfemeride, 300);
                $vinculosPadrao = $registroModel->getVinculosPadrao();
                $tiposEfemeride = [
                    'Aniversario',
                    'Iniciacao',
                    'Elevacao',
                    'Exaltacao',
                    'Instalacao',
                    'Oriente Eterno',
                    'Historia',
                    'Posse Grao Mestre',
                    'Concessao de Membro Honorario',
                    'Filiacao',
                ];
                $mensagemBase = $dadosEfemerides['mensagemBase'];
                $mensagemPreview = $dadosEfemerides['mensagemPreview'];
                $obreirosFiltro = (new Obreiro())->getAllAtivos();
                $historiasRecentes = (new HistoriaMaconica())->listar([
                    'termo' => $filtroTermo,
                    'ativo' => $filtroAtivo,
                    'data_ini' => $filtroDataIni,
                    'data_fim' => $filtroDataFim,
                ], 300);
                $palavrasDia = (new PalavraDia())->listar([
                    'termo' => $filtroTermo,
                    'ativo' => $filtroAtivo,
                ], 300);
                $cargosDisponiveisConteudo = array_values(array_filter(array_map(static function (array $item): ?array {
                    $codigo = strtoupper(trim((string) ($item['codigo'] ?? '')));
                    $slug = Cargo::codigoParaSlug($codigo);
                    if ($slug === null || in_array($slug, ['admin', 'veneravel', 'chanceler'], true)) {
                        return null;
                    }

                    return [
                        'slug' => $slug,
                        'label' => (string) ($item['nome_exibicao'] ?? $codigo),
                    ];
                }, (new Cargo())->listarResumoCargos())));
                $delegacoesHistoria = $contentPermissionService()->getAllowedRoles('historia');
                $delegacoesPalavraDia = $contentPermissionService()->getAllowedRoles('palavra_dia');
                $podeGerirEfemerides = $canManageContentCategory('efemerides');
                $podeGerirHistoria = $canManageContentCategory('historia');
                $podeGerirPalavraDia = $canManageContentCategory('palavra_dia');

                require __DIR__ . '/../../Views/efemerides_chanceler.php';
                return true;

            default:
                return false;
        }
    }

    private static function requirePermission(
        bool $openTestAccess,
        array $session,
        callable $sessionHasPermission,
        string $permission,
        string $message,
    ): void {
        WebGuards::requireLogin($openTestAccess, $session);
        if (!$sessionHasPermission($permission)) {
            WebGuards::forbidHtml($message);
        }
    }

    private static function requireManageCategory(
        bool $openTestAccess,
        array $session,
        callable $canManageContentCategory,
        string $category,
        string $message
    ): void {
        WebGuards::requireLogin($openTestAccess, $session);
        if (!$canManageContentCategory($category)) {
            WebGuards::forbidHtml($message);
        }
    }
}
