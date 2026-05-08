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
                    WebGuards::forbidHtml('Acesso restrito aos responsáveis pelos conteúdos da Chancelaria.');
                }
                (new ChancelerSessaoController())->index();
                return true;

            case '/chanceler/sessao/presenca':
                self::requirePermission($openTestAccess, $session, $sessionHasPermission, 'chancelaria.manage', 'Acesso restrito ao Chanceler, Venerável Mestre ou Administrador.');
                (new ChancelerSessaoController())->registrarPresenca();
                return true;

            case '/chanceler/sessao/visitante':
                self::requirePermission($openTestAccess, $session, $sessionHasPermission, 'chancelaria.manage', 'Acesso restrito ao Chanceler, Venerável Mestre ou Administrador.');
                (new ChancelerSessaoController())->registrarVisitante();
                return true;

            case '/veneravel/balaustre/visualizar':
                self::requirePermission($openTestAccess, $session, $sessionHasPermission, 'veneravel.manage', 'Acesso restrito ao Venerável Mestre ou Administrador.');
                (new VeneravelController())->visualizarBalaustre();
                return true;

            case '/veneravel/balaustre/enviar-votacao':
                self::requirePermission($openTestAccess, $session, $sessionHasPermission, 'veneravel.manage', 'Acesso restrito ao Venerável Mestre ou Administrador.');
                (new VeneravelController())->enviarBalaustreParaVotacao();
                return true;

            case '/veneravel/balaustre/sugerir-edicao':
                self::requirePermission($openTestAccess, $session, $sessionHasPermission, 'veneravel.manage', 'Acesso restrito ao Venerável Mestre ou Administrador.');
                (new VeneravelController())->sugerirEdicaoBalaustre();
                return true;

            case '/veneravel/balaustre/editar':
                self::requirePermission($openTestAccess, $session, $sessionHasPermission, 'veneravel.manage', 'Acesso restrito ao Venerável Mestre ou Administrador.');
                (new VeneravelController())->editarBalaustre();
                return true;

            case '/chancelaria/efemerides/salvar-previa':
                self::requirePermission($openTestAccess, $session, $sessionHasPermission, 'chancelaria.manage', 'Acesso restrito ao Chanceler, Venerável Mestre ou Administrador.');
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
                self::requirePermission($openTestAccess, $session, $sessionHasPermission, 'chancelaria.manage', 'Acesso restrito ao Chanceler, Venerável Mestre ou Administrador.');
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
                self::requirePermission($openTestAccess, $session, $sessionHasPermission, 'chancelaria.manage', 'Acesso restrito ao Chanceler, Venerável Mestre ou Administrador.');
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

            case '/chancelaria/efemerides/cards-aprovar-todos':
                self::requirePermission($openTestAccess, $session, $sessionHasPermission, 'chancelaria.manage', 'Acesso restrito ao Chanceler, VenerÃ¡vel Mestre ou Administrador.');
                if ($method !== 'POST') {
                    $redirectEfemerides();
                }
                $dadosEfemerides = $buildEfemeridesPreview();
                $registrosHoje = is_array($dadosEfemerides['registrosHoje'] ?? null) ? $dadosEfemerides['registrosHoje'] : [];
                $okCards = true;
                if (!empty($registrosHoje)) {
                    $hojeRef = $appToday()->format('Y-m-d');
                    (new \App\Services\EfemeridesCardService())->buildCardsForDate($hojeRef, $registrosHoje);
                }
                $redirectEfemerides($okCards ? ['sucesso' => 'cards_aprovados'] : ['erro' => 'falha_cards_aprovar']);

            case '/chancelaria/efemerides/cards-preview':
                self::requirePermission($openTestAccess, $session, $sessionHasPermission, 'chancelaria.manage', 'Acesso restrito ao Chanceler, VenerÃ¡vel Mestre ou Administrador.');
                $registroId = (int) ($_GET['registro_id'] ?? 0);
                if ($registroId <= 0) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['ok' => false, 'error' => 'registro_invalido']);
                    exit;
                }
                $hojeRef = $appToday()->format('Y-m-d');
                $registro = (new EfemerideRegistro())->findById($registroId, true);
                if (!$registro) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['ok' => false, 'error' => 'registro_nao_encontrado']);
                    exit;
                }
                $card = (new \App\Services\EfemeridesCardService())->buildCardForRegistro($hojeRef, $registro);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => true, 'card' => $card], JSON_UNESCAPED_UNICODE);
                exit;

            case '/chancelaria/efemerides/cards-configurar':
                self::requirePermission($openTestAccess, $session, $sessionHasPermission, 'chancelaria.manage', 'Acesso restrito ao Chanceler, VenerÃ¡vel Mestre ou Administrador.');
                if ($method !== 'POST') {
                    $redirectEfemerides();
                }
                $registroId = (int) ($_POST['registro_id'] ?? 0);
                $ocultarIdade = !empty($_POST['ocultar_idade']);
                $textoCustom = trim((string) ($_POST['texto_custom_card'] ?? ''));
                $templateCard = trim((string) ($_POST['template_card'] ?? ''));
                $hojeRef = $appToday()->format('Y-m-d');
                $registro = (new EfemerideRegistro())->findById($registroId, true);
                if (!$registro) {
                    $redirectEfemerides(['erro' => 'registro_invalido']);
                }
                (new \App\Services\EfemeridesCardService())->buildCardForRegistro($hojeRef, $registro, $ocultarIdade, $textoCustom !== '' ? $textoCustom : null);
                $card = (new \App\Services\EfemeridesCardService())->buildCardForRegistro($hojeRef, $registro, $ocultarIdade, $textoCustom !== '' ? $textoCustom : null, $templateCard !== '' ? $templateCard : null);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => true, 'card' => $card], JSON_UNESCAPED_UNICODE);
                exit;

            case '/chancelaria/efemerides/cards-template-categorias':
                self::requirePermission($openTestAccess, $session, $sessionHasPermission, 'chancelaria.manage', 'Acesso restrito ao Chanceler, VenerÃ¡vel Mestre ou Administrador.');
                if ($method !== 'POST') {
                    $redirectEfemerides();
                }
                $categorias = isset($_POST['categorias']) && is_array($_POST['categorias']) ? $_POST['categorias'] : [];
                $template = trim((string) ($_POST['template_slug'] ?? ''));
                if ($template === '' || empty($categorias)) {
                    $redirectEfemerides(['erro' => 'categorias_template_invalidas']);
                }
                $model = new \App\Models\EfemerideCardCategoriaTemplate();
                $ok = true;
                foreach ($categorias as $categoria) {
                    $cat = trim((string) $categoria);
                    if ($cat === '') {
                        continue;
                    }
                    $ok = $ok && $model->salvar($cat, $template);
                }
                $redirectEfemerides($ok ? ['sucesso' => 'template_categoria_salvo'] : ['erro' => 'falha_template_categoria']);

            case '/chancelaria/efemerides/salvar':
                self::requirePermission($openTestAccess, $session, $sessionHasPermission, 'chancelaria.manage', 'Acesso restrito ao Chanceler, Venerável Mestre ou Administrador.');
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
                self::requirePermission($openTestAccess, $session, $sessionHasPermission, 'chancelaria.manage', 'Acesso restrito ao Chanceler, Venerável Mestre ou Administrador.');
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
                self::requirePermission($openTestAccess, $session, $sessionHasPermission, 'chancelaria.manage', 'Acesso restrito ao Chanceler, Venerável Mestre ou Administrador.');
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
                    WebGuards::forbidHtml('Acesso restrito ao Chanceler, Venerável Mestre ou Administrador.');
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
                self::requireManageCategory($openTestAccess, $session, $canManageContentCategory, 'historia', 'Acesso restrito aos responsáveis por História Maçônica.');
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
                self::requireManageCategory($openTestAccess, $session, $canManageContentCategory, 'historia', 'Acesso restrito aos responsáveis por História Maçônica.');
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
                self::requireManageCategory($openTestAccess, $session, $canManageContentCategory, 'historia', 'Acesso restrito aos responsáveis por História Maçônica.');
                if ($method !== 'POST') {
                    $redirectEfemerides();
                }
                $historiaId = (int) ($_POST['id'] ?? 0);
                $ok = $historiaId > 0 ? (new HistoriaMaconica())->toggleAtivo($historiaId) : false;
                $redirectEfemerides($ok ? ['sucesso' => 'historia_status'] : ['erro' => 'falha_status_historia']);

            case '/chancelaria/historias/excluir':
                self::requireManageCategory($openTestAccess, $session, $canManageContentCategory, 'historia', 'Acesso restrito aos responsáveis por História Maçônica.');
                if ($method !== 'POST') {
                    $redirectEfemerides();
                }
                $historiaId = (int) ($_POST['id'] ?? 0);
                $ok = $historiaId > 0 ? (new HistoriaMaconica())->excluir($historiaId) : false;
                $redirectEfemerides($ok ? ['sucesso' => 'historia_excluida'] : ['erro' => 'falha_excluir_historia']);

            case '/chancelaria/palavra-dia/salvar':
                self::requireManageCategory($openTestAccess, $session, $canManageContentCategory, 'palavra_dia', 'Acesso restrito aos responsáveis por Palavra do Dia.');
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
                self::requireManageCategory($openTestAccess, $session, $canManageContentCategory, 'palavra_dia', 'Acesso restrito aos responsáveis por Palavra do Dia.');
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
                self::requireManageCategory($openTestAccess, $session, $canManageContentCategory, 'palavra_dia', 'Acesso restrito aos responsáveis por Palavra do Dia.');
                if ($method !== 'POST') {
                    $redirectEfemerides();
                }
                $palavraId = (int) ($_POST['id'] ?? 0);
                $ok = $palavraId > 0 ? (new PalavraDia())->toggleAtivo($palavraId) : false;
                $redirectEfemerides($ok ? ['sucesso' => 'palavra_status'] : ['erro' => 'falha_status_palavra']);

            case '/chancelaria/palavra-dia/excluir':
                self::requireManageCategory($openTestAccess, $session, $canManageContentCategory, 'palavra_dia', 'Acesso restrito aos responsáveis por Palavra do Dia.');
                if ($method !== 'POST') {
                    $redirectEfemerides();
                }
                $palavraId = (int) ($_POST['id'] ?? 0);
                $ok = $palavraId > 0 ? (new PalavraDia())->excluir($palavraId) : false;
                $redirectEfemerides($ok ? ['sucesso' => 'palavra_excluida'] : ['erro' => 'falha_excluir_palavra']);

            case '/chancelaria/conteudo-permissoes/salvar':
                self::requirePermission($openTestAccess, $session, $sessionHasPermission, 'chancelaria.manage', 'Acesso restrito ao Chanceler, Venerável Mestre ou Administrador.');
                if ($method !== 'POST') {
                    $redirectEfemerides();
                }
                $okHistoria = $contentPermissionService()->salvarDelegacoes('historia', $_POST['delegacoes_historia'] ?? []);
                $okPalavra = $contentPermissionService()->salvarDelegacoes('palavra_dia', $_POST['delegacoes_palavra_dia'] ?? []);
                $redirectEfemerides(($okHistoria && $okPalavra) ? ['sucesso' => 'permissoes_salvas'] : ['erro' => 'falha_salvar_permissoes']);

            case '/chancelaria/efemerides':
                self::requirePermission($openTestAccess, $session, $sessionHasPermission, 'chancelaria.manage', 'Acesso restrito ao Chanceler, Venerável Mestre ou Administrador.');

                $sucessoMensagem = match ((string) ($_GET['sucesso'] ?? '')) {
                    'previa_salva' => 'Prévia salva com sucesso.',
                    'previa_enviada' => 'Prévia enviada no privado do Chanceler.',
                    'enviado' => 'Mensagem enviada ao grupo oficial.',
                    'registro_salvo' => 'Registro salvo com sucesso.',
                    'registro_atualizado' => 'Registro atualizado com sucesso.',
                    'registro_desativado' => 'Registro desativado com sucesso.',
                    'historia_salva' => 'História salva com sucesso.',
                    'historia_atualizada' => 'História atualizada com sucesso.',
                    'historia_status' => 'Status da história atualizado com sucesso.',
                    'historia_excluida' => 'História excluída com sucesso.',
                    'palavra_salva' => 'Palavra do Dia salva com sucesso.',
                    'palavra_atualizada' => 'Palavra do Dia atualizada com sucesso.',
                    'palavra_status' => 'Status da Palavra do Dia atualizado com sucesso.',
                    'palavra_excluida' => 'Palavra do Dia excluída com sucesso.',
                    'permissoes_salvas' => 'Permissões por categoria atualizadas com sucesso.',
                    default => null,
                };

                $erroMensagem = match ((string) ($_GET['erro'] ?? '')) {
                    'previa_vazia' => 'A mensagem da prévia não pode ficar vazia.',
                    'falha_salvar_previa' => 'Não foi possível salvar a prévia.',
                    'falha_enviar_previa' => 'Falha ao enviar a prévia no privado.' . (!empty($_GET['detalhe']) ? ' Detalhe: ' . (string) $_GET['detalhe'] : ''),
                    'falha_enviar_grupo' => 'Falha ao enviar no grupo oficial.' . (!empty($_GET['detalhe']) ? ' Detalhe: ' . (string) $_GET['detalhe'] : ''),
                    'registro_invalido' => 'Preencha nome, tipo e data do evento corretamente.',
                    'falha_salvar_registro' => 'Não foi possível salvar o registro.',
                    'falha_atualizar_registro' => 'Não foi possível atualizar o registro.',
                    'id_invalido' => 'Registro inválido para desativação.',
                    'falha_desativar' => 'Não foi possível desativar o registro.',
                    'historia_invalida' => 'Preencha os dados da história corretamente.',
                    'falha_salvar_historia' => 'Não foi possível salvar a história.',
                    'falha_atualizar_historia' => 'Não foi possível atualizar a história.',
                    'falha_status_historia' => 'Não foi possível atualizar o status da história.',
                    'falha_excluir_historia' => 'Não foi possível excluir a história.',
                    'palavra_invalida' => 'Preencha os dados da Palavra do Dia corretamente.',
                    'falha_salvar_palavra' => 'Não foi possível salvar a Palavra do Dia.',
                    'falha_atualizar_palavra' => 'Não foi possível atualizar a Palavra do Dia.',
                    'falha_status_palavra' => 'Não foi possível atualizar o status da Palavra do Dia.',
                    'falha_excluir_palavra' => 'Não foi possível excluir a Palavra do Dia.',
                    'falha_salvar_permissoes' => 'Não foi possível salvar as permissões por categoria.',
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


