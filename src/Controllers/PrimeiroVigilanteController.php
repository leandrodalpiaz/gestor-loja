<?php

namespace App\Controllers;

use App\Models\Acervo;
use App\Models\Cargo;
use App\Models\Obreiro;
use App\Models\PrimeiroVigilanteAcompanhamento;
use App\Models\TrabalhoSubmissao;
use App\Models\TrilhaAprendiz;

class PrimeiroVigilanteController
{
    public function index(): void
    {
        $obreiroModel = new Obreiro();
        $cargoModel = new Cargo();
        $trilhaModel = new TrilhaAprendiz();
        $acompanhamentoModel = new PrimeiroVigilanteAcompanhamento();

        $aprendizes = array_values(array_filter(
            $obreiroModel->getAllAtivos(),
            static fn (array $obreiro): bool => strtolower(trim((string) ($obreiro['grau'] ?? ''))) === 'aprendiz'
        ));

        usort($aprendizes, static function (array $a, array $b): int {
            $nomeA = trim((string) ($a['nome_historico'] ?? '')) !== '' ? (string) $a['nome_historico'] : (string) ($a['nome'] ?? '');
            $nomeB = trim((string) ($b['nome_historico'] ?? '')) !== '' ? (string) $b['nome_historico'] : (string) ($b['nome'] ?? '');
            return strcasecmp($nomeA, $nomeB);
        });

        $aprendizIds = array_values(array_filter(array_map(
            static fn (array $aprendiz): string => (string) ($aprendiz['id'] ?? ''),
            $aprendizes
        )));

        $trilhaDisponivel = $trilhaModel->trilhaDisponivel();
        $avisoInfra = null;
        if ($trilhaDisponivel) {
            $trilhaModel->garantirTrilhaBaseParaAprendizes($aprendizIds);
            $resumoAtualPorAprendiz = $trilhaModel->listarResumoAtualPorAprendizIds($aprendizIds);
        } else {
            $resumoAtualPorAprendiz = [];
            $avisoInfra = 'A trilha individual ainda não foi criada no banco. Execute: php scripts/run_migration_009.php e php scripts/run_migration_010.php.';
        }

        foreach ($aprendizes as &$aprendiz) {
            $resumoAtual = $resumoAtualPorAprendiz[(string) ($aprendiz['id'] ?? '')] ?? null;
            $aprendiz['trilha_etapa_atual'] = (int) ($resumoAtual['etapa_ordem'] ?? 1);
            $aprendiz['trilha_status_atual'] = (string) ($resumoAtual['status'] ?? 'nao_iniciado');
            $aprendiz['trilha_titulo_atual'] = (string) ($resumoAtual['titulo_etapa'] ?? TrilhaAprendiz::etapas()[1]);
            $aprendiz['trilha_proxima_acao'] = $this->resolverProximaAcao(
                $aprendiz['trilha_etapa_atual'],
                $aprendiz['trilha_status_atual']
            );
        }
        unset($aprendiz);

        $nominata = $cargoModel->listarResumoCargos();
        $titularCargo = null;
        foreach ($nominata as $item) {
            if ((string) ($item['codigo'] ?? '') === 'PRIMEIRO_VIGILANTE') {
                $titularCargo = $item;
                break;
            }
        }

        $resumo = [
            'aprendizes_ativos' => count($aprendizes),
            'etapa_inicial' => count(array_filter($aprendizes, static fn (array $aprendiz): bool => (int) ($aprendiz['trilha_etapa_atual'] ?? 0) === 1)),
            'trabalhos_aguardando_recebimento' => $trilhaDisponivel ? $trilhaModel->contarPorStatus($aprendizIds, 'aguardando_entrega') : 0,
            'aptos_certificado' => $trilhaDisponivel ? $trilhaModel->contarPorStatus($aprendizIds, 'apto_para_certificado') : 0,
            'leituras_sugeridas' => count(array_filter($aprendizIds, static function (string $aprendizId) use ($acompanhamentoModel): bool {
                $acompanhamento = $acompanhamentoModel->obterPorAprendiz($aprendizId);
                return !empty($acompanhamento['leitura_acervo_id']) || !empty($acompanhamento['leitura_observacao']);
            })),
        ];

        $trilhaEstudo = TrilhaAprendiz::etapas();

        require_once __DIR__ . '/../Views/primeiro_vigilante/index.php';
    }

    public function aprendiz(?string $aprendizId = null, bool $somenteProprio = false): void
    {
        $aprendizId = trim((string) $aprendizId);
        if ($aprendizId === '') {
            http_response_code(404);
            echo 'Aprendiz não informado.';
            return;
        }

        $obreiroModel = new Obreiro();
        $trilhaModel = new TrilhaAprendiz();
        $acervoModel = new Acervo();
        $acompanhamentoModel = new PrimeiroVigilanteAcompanhamento();

        $aprendiz = $obreiroModel->findById($aprendizId);
        if (!$aprendiz || strtolower(trim((string) ($aprendiz['grau'] ?? ''))) !== 'aprendiz') {
            http_response_code(404);
            echo 'Aprendiz não encontrado.';
            return;
        }

        $trilhaDisponivel = $trilhaModel->trilhaDisponivel();
        $avisoInfra = null;

        if ($trilhaDisponivel) {
            $trilhaModel->garantirTrilhaBaseParaAprendizes([$aprendizId]);
            $resumoTrilha = $trilhaModel->obterResumoDoAprendiz($aprendizId);
        } else {
            $resumoTrilha = $this->montarResumoFallback();
            $avisoInfra = 'A trilha individual ainda não foi criada no banco. Execute: php scripts/run_migration_009.php e php scripts/run_migration_010.php.';
        }

        if ($resumoTrilha === null) {
            $resumoTrilha = $this->montarResumoFallback();
        }

        $etapas = $resumoTrilha['etapas'] ?? [];
        $etapaAtual = $resumoTrilha['etapa_atual'] ?? null;
        $statusDisponiveis = [
            'nao_iniciado' => 'Não iniciado',
            'disponibilizado' => 'Disponibilizado',
            'aguardando_entrega' => 'Aguardando entrega',
            'recebido' => 'Recebido',
            'revisado' => 'Revisado',
            'concluido' => 'Concluído',
            'apto_para_certificado' => 'Apto para certificado',
            'certificado_solicitado' => 'Certificado solicitado',
        ];
        $acoesRapidasPorEtapa = [];
        foreach ($etapas as $etapaItem) {
            $ordem = (int) ($etapaItem['etapa_ordem'] ?? 0);
            $statusEtapa = (string) ($etapaItem['status'] ?? 'nao_iniciado');
            $acoesRapidasPorEtapa[$ordem] = $this->montarAcoesRapidas($statusEtapa, $ordem);
        }
        $acompanhamento = $acompanhamentoModel->obterPorAprendiz($aprendizId);
        $historicoFormativo = $acompanhamentoModel->listarHistoricoFormativo($aprendizId);
        $leiturasDisponiveis = array_values(array_filter(
            $acervoModel->listarTodos(),
            static fn (array $item): bool => in_array((string) ($item['grau_recomendado'] ?? 'Livre'), ['Livre', 'Aprendiz'], true)
        ));

        $tituloPagina = $somenteProprio ? 'Meu acompanhamento' : 'Acompanhamento do Aprendiz';

        require_once __DIR__ . '/../Views/primeiro_vigilante/aprendiz.php';
    }

    public function atualizarEtapa(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /primeiro-vigilante');
            exit;
        }

        $aprendizId = trim((string) ($_POST['aprendiz_id'] ?? ''));
        $etapaOrdem = (int) ($_POST['etapa_ordem'] ?? 0);
        $status = trim((string) ($_POST['status'] ?? ''));
        $observacao = trim((string) ($_POST['observacao_vigilante'] ?? ''));
        $publicarBiblioteca = !empty($_POST['publicar_biblioteca']);
        $retorno = '/primeiro-vigilante/aprendiz?id=' . urlencode($aprendizId);

        if ($aprendizId === '' || $etapaOrdem <= 0 || $status === '') {
            $_SESSION['mensagem_erro'] = 'Informe aprendiz, etapa e status para atualizar a trilha.';
            header('Location: ' . $retorno);
            exit;
        }

        $trilhaModel = new TrilhaAprendiz();
        if (!$trilhaModel->trilhaDisponivel()) {
            $_SESSION['mensagem_erro'] = 'A trilha ainda não foi criada no banco. Execute as migrations 009 e 010.';
            header('Location: ' . $retorno);
            exit;
        }

        $ok = $trilhaModel->atualizarEtapa(
            $aprendizId,
            $etapaOrdem,
            $status,
            $observacao !== '' ? $observacao : null,
            (string) ($_SESSION['usuario_id'] ?? ''),
            null,
            $publicarBiblioteca
        );

        $_SESSION[$ok ? 'mensagem_sucesso' : 'mensagem_erro'] = $ok
            ? 'Etapa atualizada com sucesso.'
            : 'Não foi possível atualizar a etapa da trilha.';

        header('Location: ' . $retorno);
        exit;
    }

    public function acaoRapidaEtapa(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /primeiro-vigilante');
            exit;
        }

        $aprendizId = trim((string) ($_POST['aprendiz_id'] ?? ''));
        $etapaOrdem = (int) ($_POST['etapa_ordem'] ?? 0);
        $status = trim((string) ($_POST['status'] ?? ''));
        $observacao = trim((string) ($_POST['observacao_vigilante'] ?? ''));
        $retorno = '/primeiro-vigilante/aprendiz?id=' . urlencode($aprendizId);

        if ($aprendizId === '' || $etapaOrdem <= 0 || $status === '') {
            $_SESSION['mensagem_erro'] = 'Não foi possível executar a ação rápida da trilha.';
            header('Location: ' . $retorno);
            exit;
        }

        $trilhaModel = new TrilhaAprendiz();
        if (!$trilhaModel->trilhaDisponivel()) {
            $_SESSION['mensagem_erro'] = 'A trilha ainda não foi criada no banco. Execute as migrations 009 e 010.';
            header('Location: ' . $retorno);
            exit;
        }

        $ok = $trilhaModel->atualizarEtapa(
            $aprendizId,
            $etapaOrdem,
            $status,
            $observacao !== '' ? $observacao : null,
            (string) ($_SESSION['usuario_id'] ?? '')
        );

        $_SESSION[$ok ? 'mensagem_sucesso' : 'mensagem_erro'] = $ok
            ? 'Ação rápida aplicada com sucesso.'
            : 'Não foi possível aplicar a ação rápida.';

        header('Location: ' . $retorno . '#etapa-' . $etapaOrdem);
        exit;
    }

    public function trabalhosPendentes(): void
    {
        $itens = (new TrabalhoSubmissao())->listarPendentesMentor('primeiro_vigilante', 200);
        require_once __DIR__ . '/../Views/primeiro_vigilante/trabalhos.php';
    }

    public function decidirTrabalho(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /primeiro-vigilante/trabalhos');
            exit;
        }
        $id = trim((string) ($_POST['id'] ?? ''));
        $acao = trim((string) ($_POST['acao'] ?? ''));
        $obs = trim((string) ($_POST['observacao'] ?? ''));
        $ok = $id !== '' && in_array($acao, ['aprovar', 'rejeitar'], true)
            ? (new TrabalhoSubmissao())->decidirMentor($id, (string) ($_SESSION['usuario_id'] ?? ''), $acao, $obs !== '' ? $obs : null)
            : false;

        $_SESSION[$ok ? 'mensagem_sucesso' : 'mensagem_erro'] = $ok
            ? 'Decisão registrada.'
            : 'Não foi possível registrar agora.';

        header('Location: /primeiro-vigilante/trabalhos');
        exit;
    }

    public function salvarLeituraSugerida(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /primeiro-vigilante');
            exit;
        }

        $aprendizId = trim((string) ($_POST['aprendiz_id'] ?? ''));
        $retorno = '/primeiro-vigilante/aprendiz?id=' . urlencode($aprendizId);
        $acervoId = (int) ($_POST['acervo_id'] ?? 0);
        $observacao = trim((string) ($_POST['observacao_leitura'] ?? ''));
        $autorId = trim((string) ($_SESSION['usuario_id'] ?? ''));

        if ($aprendizId === '') {
            $_SESSION['mensagem_erro'] = 'Aprendiz inválido para registrar leitura sugerida.';
            header('Location: /primeiro-vigilante');
            exit;
        }

        $ok = (new PrimeiroVigilanteAcompanhamento())->salvarLeituraSugerida(
            $aprendizId,
            $acervoId > 0 ? $acervoId : null,
            $observacao !== '' ? $observacao : null,
            $autorId !== '' ? $autorId : null
        );

        $_SESSION[$ok ? 'mensagem_sucesso' : 'mensagem_erro'] = $ok
            ? 'Leitura sugerida registrada com sucesso.'
            : 'Não foi possível registrar a leitura sugerida.';
        header('Location: ' . $retorno . '#leitura-sugerida');
        exit;
    }

    public function solicitarCertificado(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /primeiro-vigilante');
            exit;
        }

        $aprendizId = trim((string) ($_POST['aprendiz_id'] ?? ''));
        $retorno = '/primeiro-vigilante/aprendiz?id=' . urlencode($aprendizId);
        $observacao = trim((string) ($_POST['observacao_certificado'] ?? ''));
        $autorId = trim((string) ($_SESSION['usuario_id'] ?? ''));

        if ($aprendizId === '') {
            $_SESSION['mensagem_erro'] = 'Aprendiz inválido para solicitar certificado.';
            header('Location: /primeiro-vigilante');
            exit;
        }

        $trilhaModel = new TrilhaAprendiz();
        if ($trilhaModel->trilhaDisponivel()) {
            $trilhaModel->atualizarEtapa($aprendizId, 8, 'certificado_solicitado', $observacao !== '' ? $observacao : null, $autorId !== '' ? $autorId : null);
        }

        $ok = (new PrimeiroVigilanteAcompanhamento())->solicitarCertificado(
            $aprendizId,
            $observacao !== '' ? $observacao : null,
            $autorId !== '' ? $autorId : null
        );

        $_SESSION[$ok ? 'mensagem_sucesso' : 'mensagem_erro'] = $ok
            ? 'Solicitação formal de certificado registrada.'
            : 'Não foi possível registrar a solicitação do certificado.';
        header('Location: ' . $retorno . '#certificado');
        exit;
    }

    public function salvarLeituraSugeridaMiniapp(string $aprendizId, ?int $acervoId, ?string $observacao, ?string $autorId = null): array
    {
        $aprendizId = trim($aprendizId);
        if ($aprendizId === '') {
            return ['ok' => false, 'erro' => 'Aprendiz inválido para registrar leitura sugerida.'];
        }

        $ok = (new PrimeiroVigilanteAcompanhamento())->salvarLeituraSugerida(
            $aprendizId,
            $acervoId !== null && $acervoId > 0 ? $acervoId : null,
            $observacao,
            $autorId
        );

        return ['ok' => $ok, 'erro' => $ok ? null : 'Não foi possível registrar a leitura sugerida.'];
    }

    public function solicitarCertificadoMiniapp(string $aprendizId, ?string $observacao, ?string $autorId = null): array
    {
        $aprendizId = trim($aprendizId);
        if ($aprendizId === '') {
            return ['ok' => false, 'erro' => 'Aprendiz inválido para solicitar certificado.'];
        }

        $trilhaModel = new TrilhaAprendiz();
        if ($trilhaModel->trilhaDisponivel()) {
            $trilhaModel->atualizarEtapa($aprendizId, 8, 'certificado_solicitado', $observacao, $autorId);
        }

        $ok = (new PrimeiroVigilanteAcompanhamento())->solicitarCertificado($aprendizId, $observacao, $autorId);

        return ['ok' => $ok, 'erro' => $ok ? null : 'Não foi possível registrar a solicitação do certificado.'];
    }

    public function atualizarEtapaMiniapp(string $aprendizId, int $etapaOrdem, string $status, ?string $observacao = null, ?string $autorId = null, bool $publicarBiblioteca = false): array
    {
        $aprendizId = trim($aprendizId);
        if ($aprendizId === '' || $etapaOrdem <= 0 || trim($status) === '') {
            return ['ok' => false, 'erro' => 'Dados insuficientes para atualizar a trilha.'];
        }

        $trilhaModel = new TrilhaAprendiz();
        if (!$trilhaModel->trilhaDisponivel()) {
            return ['ok' => false, 'erro' => 'A trilha ainda não foi criada no banco.'];
        }

        $arquivoEntrega = null;
        if (isset($_FILES['trabalho']) && (int) ($_FILES['trabalho']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $file = $_FILES['trabalho'];
            
            // Validar extensões
            $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'];
            $fileInfo = pathinfo((string) $file['name']);
            $extension = strtolower((string) ($fileInfo['extension'] ?? ''));
            
            if (!in_array($extension, $allowedExtensions, true)) {
                return ['ok' => false, 'erro' => 'Formato de arquivo inválido. Permitido: PDF ou Imagem.'];
            }
            
            // Diretório de destino
            $targetDir = __DIR__ . '/../../public/assets/uploads/trabalhos/';
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
            
            $newFileName = 'trabalho_aprendiz_' . $aprendizId . '_' . $etapaOrdem . '_' . time() . '.' . $extension;
            $targetPath = $targetDir . $newFileName;
            
            if (move_uploaded_file((string) $file['tmp_name'], $targetPath)) {
                $arquivoEntrega = '/assets/uploads/trabalhos/' . $newFileName;
            } else {
                return ['ok' => false, 'erro' => 'Falha ao salvar o arquivo enviado.'];
            }
        }

        $ok = $trilhaModel->atualizarEtapa($aprendizId, $etapaOrdem, trim($status), $observacao, $autorId, $arquivoEntrega, $publicarBiblioteca);
        return ['ok' => $ok, 'erro' => $ok ? null : 'Não foi possível atualizar a etapa da trilha.'];
    }

    public function enviarMensagemMiniapp(string $obreiroId, int $etapaOrdem, string $mensagem, ?string $autorId = null): array
    {
        $obreiroId = trim($obreiroId);
        $mensagem = trim($mensagem);
        if ($obreiroId === '' || $etapaOrdem <= 0 || $mensagem === '') {
            return ['ok' => false, 'erro' => 'Dados inválidos para enviar mensagem.'];
        }

        $autorId = $autorId ?: (string) ($_SESSION['usuario_id'] ?? '');
        if ($autorId === '') {
            return ['ok' => false, 'erro' => 'Autor não identificado.'];
        }

        $model = new \App\Models\MensagemTrilha();
        $ok = $model->enviar($obreiroId, 1, $etapaOrdem, $autorId, $mensagem);
        return ['ok' => $ok, 'erro' => $ok ? null : 'Não foi possível enviar a mensagem.'];
    }

    public function recomendarElevacaoMiniapp(string $aprendizId, ?string $observacao = null, ?string $autorId = null): array
    {
        $aprendizId = trim($aprendizId);
        if ($aprendizId === '') {
            return ['ok' => false, 'erro' => 'Aprendiz inválido.'];
        }

        $autorId = $autorId ?: (string) ($_SESSION['usuario_id'] ?? '');
        $model = new PrimeiroVigilanteAcompanhamento();
        $ok = $model->recomendarElevacao($aprendizId, $observacao, $autorId);
        return ['ok' => $ok, 'erro' => $ok ? null : 'Não foi possível recomendar a elevação.'];
    }

    private function resolverProximaAcao(int $etapa, string $status): string
    {
        return match ($status) {
            'nao_iniciado' => 'Passar a etapa ao Aprendiz',
            'disponibilizado' => 'Aguardar início do trabalho',
            'aguardando_entrega' => 'Receber o trabalho do Aprendiz',
            'recebido' => 'Revisar o trabalho recebido',
            'revisado' => 'Concluir a etapa e orientar o próximo passo',
            'concluido' => $etapa >= 8 ? 'Avaliar solicitação do certificado' : 'Liberar a próxima etapa',
            'apto_para_certificado' => 'Solicitar o certificado de conclusão',
            'certificado_solicitado' => 'Aguardar emissão do certificado',
            default => 'Acompanhar o progresso da etapa',
        };
    }

    private function montarAcoesRapidas(string $status, int $etapa): array
    {
        return match ($status) {
            'nao_iniciado' => [
                ['status' => 'disponibilizado', 'label' => 'Passar etapa'],
                ['status' => 'aguardando_entrega', 'label' => 'Aguardar entrega'],
            ],
            'disponibilizado' => [
                ['status' => 'aguardando_entrega', 'label' => 'Marcar aguardando entrega'],
                ['status' => 'recebido', 'label' => 'Marcar recebido'],
            ],
            'aguardando_entrega' => [
                ['status' => 'recebido', 'label' => 'Receber trabalho'],
            ],
            'recebido' => [
                ['status' => 'revisado', 'label' => 'Revisar trabalho'],
            ],
            'revisado' => [
                ['status' => $etapa === 8 ? 'apto_para_certificado' : 'concluido', 'label' => $etapa === 8 ? 'Marcar apto para certificado' : 'Concluir etapa'],
            ],
            'concluido' => $etapa === 8
                ? [['status' => 'apto_para_certificado', 'label' => 'Marcar apto para certificado']]
                : [['status' => 'disponibilizado', 'label' => 'Reabrir etapa']],
            'apto_para_certificado' => [
                ['status' => 'certificado_solicitado', 'label' => 'Solicitar certificado'],
            ],
            'certificado_solicitado' => [],
            default => [],
        };
    }

    public function montarPayloadMiniapp(string $aprendizId): ?array
    {
        $obreiroModel = new Obreiro();
        $trilhaModel = new TrilhaAprendiz();
        $acompanhamentoModel = new PrimeiroVigilanteAcompanhamento();

        $aprendiz = $obreiroModel->findById($aprendizId);
        if (!$aprendiz || strtolower(trim((string) ($aprendiz['grau'] ?? ''))) !== 'aprendiz') {
            return null;
        }

        if ($trilhaModel->trilhaDisponivel()) {
            $trilhaModel->garantirTrilhaBaseParaAprendizes([$aprendizId]);
            $resumoTrilha = $trilhaModel->obterResumoDoAprendiz($aprendizId);
            if ($resumoTrilha === null) {
                $resumoTrilha = $this->montarResumoFallback();
            }
        } else {
            $resumoTrilha = $this->montarResumoFallback();
        }

        $etapaAtual = $resumoTrilha['etapa_atual'] ?? null;
        $acompanhamento = $acompanhamentoModel->obterPorAprendiz($aprendizId);
        $historicoFormativo = $acompanhamentoModel->listarHistoricoFormativo($aprendizId);

        $db = \App\Config\Database::getConnection();
        $stmtPub = $db->prepare("SELECT nota_instrucao FROM acervo WHERE nota_instrucao LIKE :pattern AND ativo = TRUE");
        $stmtPub->execute(['pattern' => "trilha_aprendiz:{$aprendizId}:%"]);
        $publicados = $stmtPub->fetchAll(\PDO::FETCH_COLUMN) ?: [];

        return [
            'aprendiz' => [
                'id' => (string) ($aprendiz['id'] ?? ''),
                'nome' => trim((string) ($aprendiz['nome_historico'] ?? '')) !== '' ? (string) $aprendiz['nome_historico'] : (string) ($aprendiz['nome'] ?? 'Aprendiz'),
                'cim' => (string) ($aprendiz['cim'] ?? ''),
                'data_iniciacao' => (string) ($aprendiz['data_iniciacao'] ?? ''),
            ],
            'resumo' => [
                'total_etapas' => (int) ($resumoTrilha['total_etapas'] ?? 0),
                'total_concluidas' => (int) ($resumoTrilha['total_concluidas'] ?? 0),
                'percentual_conclusao' => (int) ($resumoTrilha['percentual_conclusao'] ?? 0),
                'etapa_atual' => $etapaAtual ? [
                    'ordem' => (int) ($etapaAtual['etapa_ordem'] ?? 0),
                    'titulo' => (string) ($etapaAtual['titulo_etapa'] ?? ''),
                    'status' => (string) ($etapaAtual['status'] ?? ''),
                ] : null,
            ],
            'etapas' => array_map(static function (array $etapa) use ($aprendizId, $publicados): array {
                $mensagens = (new \App\Models\MensagemTrilha())->obterPorEtapa($aprendizId, (int) ($etapa['etapa_ordem'] ?? 0));
                return [
                    'ordem' => (int) ($etapa['etapa_ordem'] ?? 0),
                    'titulo' => (string) ($etapa['titulo_etapa'] ?? ''),
                    'status' => (string) ($etapa['status'] ?? ''),
                    'is_oral' => \App\Models\TrilhaAprendiz::isEtapaOral((int) ($etapa['etapa_ordem'] ?? 0)),
                    'publicado_biblioteca' => in_array("trilha_aprendiz:{$aprendizId}:" . ($etapa['etapa_ordem'] ?? 0), $publicados, true),
                    'data_disponibilizacao' => (string) ($etapa['data_disponibilizacao'] ?? ''),
                    'data_entrega' => (string) ($etapa['data_entrega'] ?? ''),
                    'data_revisao' => (string) ($etapa['data_revisao'] ?? ''),
                    'observacao_vigilante' => (string) ($etapa['observacao_vigilante'] ?? ''),
                    'arquivo_entrega' => (string) ($etapa['arquivo_entrega'] ?? ''),
                    'chat' => array_map(static function (array $msg): array {
                        return [
                            'id' => (int) ($msg['id'] ?? 0),
                            'mensagem' => (string) ($msg['mensagem'] ?? ''),
                            'autor_id' => (string) ($msg['autor_id'] ?? ''),
                            'autor_name' => (string) ($msg['autor_nome'] ?? 'Obreiro'),
                            'created_at' => (string) ($msg['created_at'] ?? ''),
                        ];
                    }, $mensagens),
                ];
            }, $resumoTrilha['etapas'] ?? []),
            'leitura_sugerida' => [
                'acervo_id' => (int) ($acompanhamento['leitura_acervo_id'] ?? 0),
                'titulo' => (string) ($acompanhamento['leitura_titulo'] ?? $acompanhamento['leitura_titulo_snapshot'] ?? ''),
                'autor' => (string) ($acompanhamento['leitura_autor'] ?? ''),
                'observacao' => (string) ($acompanhamento['leitura_observacao'] ?? ''),
            ],
            'certificado' => [
                'status' => (string) ($acompanhamento['certificado_status'] ?? 'nao_solicitado'),
                'observacao' => (string) ($acompanhamento['certificado_observacao'] ?? ''),
                'solicitado_em' => (string) ($acompanhamento['certificado_solicitado_em'] ?? ''),
            ],
            'elevacao' => [
                'status' => (string) ($acompanhamento['elevacao_status'] ?? 'nao_indicada'),
                'observacao' => (string) ($acompanhamento['elevacao_observacao'] ?? ''),
                'autorizada_em' => (string) ($acompanhamento['elevacao_autorizada_em'] ?? ''),
            ],
            'historico_formativo' => $historicoFormativo,
        ];
    }

    public function montarPayloadPainelMiniapp(?string $aprendizId = null): array
    {
        $obreiroModel = new Obreiro();
        $acervoModel = new Acervo();

        $aprendizes = array_values(array_filter(
            $obreiroModel->getAllAtivos(),
            static fn (array $obreiro): bool => strtolower(trim((string) ($obreiro['grau'] ?? ''))) === 'aprendiz'
        ));
        usort($aprendizes, static function (array $a, array $b): int {
            $nomeA = trim((string) ($a['nome_historico'] ?? '')) !== '' ? (string) $a['nome_historico'] : (string) ($a['nome'] ?? '');
            $nomeB = trim((string) ($b['nome_historico'] ?? '')) !== '' ? (string) $b['nome_historico'] : (string) ($b['nome'] ?? '');
            return strcasecmp($nomeA, $nomeB);
        });

        $aprendizFocoId = trim((string) ($aprendizId ?? ''));
        if ($aprendizFocoId === '' && isset($aprendizes[0]['id'])) {
            $aprendizFocoId = (string) $aprendizes[0]['id'];
        }

        return [
            'aprendizes' => array_map(static function (array $aprendiz): array {
                return [
                    'id' => (string) ($aprendiz['id'] ?? ''),
                    'nome' => trim((string) ($aprendiz['nome_historico'] ?? '')) !== '' ? (string) $aprendiz['nome_historico'] : (string) ($aprendiz['nome'] ?? 'Aprendiz'),
                    'cim' => (string) ($aprendiz['cim'] ?? ''),
                ];
            }, $aprendizes),
            'aprendiz_foco' => $this->montarPayloadMiniapp($aprendizFocoId),
            'leituras_disponiveis' => array_map(static function (array $item): array {
                return [
                    'id' => (int) ($item['id'] ?? 0),
                    'titulo' => (string) ($item['titulo'] ?? ''),
                    'autor' => (string) ($item['autor'] ?? ''),
                ];
            }, array_values(array_filter(
                $acervoModel->listarTodos(),
                static fn (array $item): bool => in_array((string) ($item['grau_recomendado'] ?? 'Livre'), ['Livre', 'Aprendiz'], true)
            ))),
        ];
    }

    private function montarResumoFallback(): array
    {
        $etapas = [];
        foreach (TrilhaAprendiz::etapas() as $ordem => $titulo) {
            $etapas[] = [
                'id' => null,
                'aprendiz_id' => null,
                'etapa_ordem' => $ordem,
                'titulo_etapa' => $titulo,
                'status' => 'nao_iniciado',
                'data_disponibilizacao' => null,
                'data_entrega' => null,
                'data_revisao' => null,
                'observacao_vigilante' => null,
                'arquivo_entrega' => null,
                'revisado_por' => null,
                'created_at' => null,
                'updated_at' => null,
            ];
        }

        return [
            'etapas' => $etapas,
            'etapa_atual' => $etapas[0] ?? null,
            'total_etapas' => count($etapas),
            'total_concluidas' => 0,
            'percentual_conclusao' => 0,
        ];
    }
}
