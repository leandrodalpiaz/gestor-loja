<?php

namespace App\Controllers;

use App\Models\BanqueteOperacao;
use App\Models\Presenca;
use App\Models\Sessao;
use App\Models\PresencaSessao;
use App\Models\VidaLojaAgapeAnfitriao;
use App\Models\Obreiro;

class MestreBanquetesController
{
    private function obterUsuarioUuid(): ?string
    {
        $userId = $_SESSION['usuario_id'] ?? null;
        if ($userId === null || trim((string)$userId) === '') {
            return null;
        }
        $userId = trim((string)$userId);
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $userId) === 1) {
            return $userId;
        }
        return null;
    }

    public function index(): void
    {
        $sessaoModel = new Sessao();
        $presencaModel = new Presenca();
        $operacaoModel = new BanqueteOperacao();

        $proximaSessao = $sessaoModel->obterProximaSessao();
        $sessoes = $sessaoModel->listarFuturas(8);
        $sessaoSelecionadaId = (int) ($_GET['sessao_id'] ?? 0);
        $sessaoEmFoco = null;
        $confirmados = [];
        $participantesAgape = [];
        $descricaoAgape = null;
        $descricaoModeloFinanceiroAgape = null;
        $operacaoBanquete = null;
        $financeiroBanquete = [
            'valor_estimado' => 0.0,
            'resultado_previsto' => 0.0,
            'resultado_real' => 0.0,
        ];

        $presencasSessao = [];
        $anfitrioesDesignados = [];
        $obreirosAtivos = [];

        if ($sessaoSelecionadaId > 0) {
            $sessaoEmFoco = $sessaoModel->findById($sessaoSelecionadaId);
        }
        if (!$sessaoEmFoco && $proximaSessao && !empty($proximaSessao['id'])) {
            $sessaoEmFoco = $sessaoModel->findById((int) $proximaSessao['id']);
        }

        if ($sessaoEmFoco && !empty($sessaoEmFoco['id'])) {
            $sessaoId = (int) $sessaoEmFoco['id'];
            $confirmados = $presencaModel->listarConfirmadosPorSessao($sessaoId);
            $participantesAgape = $presencaModel->listarParticipantesAgapePorSessao($sessaoId);
            $descricaoAgape = $sessaoModel->obterDescricaoAgape($sessaoEmFoco);
            $descricaoModeloFinanceiroAgape = $sessaoModel->obterDescricaoModeloFinanceiroAgape($sessaoEmFoco);
            $operacaoBanquete = $operacaoModel->obterPorSessao($sessaoId);
            $financeiroBanquete = $this->montarResumoFinanceiro($sessaoEmFoco, count($participantesAgape), $operacaoBanquete);

            // Fase 2: Buscar presenças físicas na sessão ritual e designações de anfitriões
            $presencasSessaoModel = new PresencaSessao();
            $presencasSessao = $presencasSessaoModel->listarPresencasAgapePorSessao($sessaoId);

            $anfitriaoModel = new VidaLojaAgapeAnfitriao();
            $anfitrioesDesignados = $anfitriaoModel->listarPorSessao($sessaoId);

            $obreiroModel = new Obreiro();
            $obreirosAtivos = $obreiroModel->getAllAtivos();
        }

        require_once __DIR__ . '/../Views/mestre_banquetes/index.php';
    }

    public function salvarOperacao(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /mestre-banquetes');
            exit;
        }

        $sessaoId = (int) ($_POST['sessao_id'] ?? 0);
        $usuarioSessaoId = trim((string) ($_SESSION['usuario_id'] ?? ''));
        $autorId = ctype_digit($usuarioSessaoId) ? (int) $usuarioSessaoId : null;
        $ok = $sessaoId > 0 && (new BanqueteOperacao())->salvar($sessaoId, $_POST, $autorId);

        $_SESSION[$ok ? 'mensagem_sucesso' : 'mensagem_erro'] = $ok
            ? 'Operação do banquete updated com sucesso.'
            : 'Não foi possível atualizar a operação do banquete.';

        header('Location: /mestre-banquetes?sessao_id=' . urlencode((string) $sessaoId));
        exit;
    }

    public function salvarPresencasAgape(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /mestre-banquetes');
            exit;
        }

        $sessaoId = (int) ($_POST['sessao_id'] ?? 0);
        $presentesAgape = $_POST['presente_agape'] ?? []; // Array de UUIDs dos obreiros que ficaram no ágape

        if ($sessaoId > 0) {
            $presencasSessaoModel = new PresencaSessao();
            $ok = $presencasSessaoModel->salvarAgape($sessaoId, $presentesAgape);
            
            $_SESSION[$ok ? 'mensagem_sucesso' : 'mensagem_erro'] = $ok
                ? 'Presenças no ágape registradas com sucesso.'
                : 'Não foi possível salvar as presenças no ágape (certifique-se de que a presença da sessão ritualística já foi registrada).';
        }

        header('Location: /mestre-banquetes?sessao_id=' . urlencode((string) $sessaoId));
        exit;
    }

    public function salvarAnfitrioes(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /mestre-banquetes');
            exit;
        }

        $sessaoId = (int) ($_POST['sessao_id'] ?? 0);
        $action = trim((string) ($_POST['action'] ?? ''));
        $usuarioUuid = $this->obterUsuarioUuid();

        if ($sessaoId <= 0 || $usuarioUuid === null) {
            $_SESSION['mensagem_erro'] = 'Sessão ou autor da operação inválido.';
            header('Location: /mestre-banquetes?sessao_id=' . urlencode((string) $sessaoId));
            exit;
        }

        $anfitriaoModel = new VidaLojaAgapeAnfitriao();

        if ($action === 'adicionar') {
            $ok = $anfitriaoModel->adicionar($sessaoId, $_POST, $usuarioUuid);
            $_SESSION[$ok ? 'mensagem_sucesso' : 'mensagem_erro'] = $ok
                ? 'Anfitrião designado com sucesso.'
                : 'Erro ao adicionar anfitrião. Certifique-se de que informou um acolhido, visitante ou que o foco seja "geral".';
        } elseif ($action === 'remover') {
            $anfitriaoId = (int) ($_POST['anfitriao_id'] ?? 0);
            $ok = $anfitriaoId > 0 && $anfitriaoModel->remover($anfitriaoId, $usuarioUuid);
            $_SESSION[$ok ? 'mensagem_sucesso' : 'mensagem_erro'] = $ok
                ? 'Designação de anfitrião removida com sucesso.'
                : 'Erro ao remover designação de anfitrião.';
        }

        header('Location: /mestre-banquetes?sessao_id=' . urlencode((string) $sessaoId));
        exit;
    }

    public function montarPayloadMiniapp(?int $sessaoId = null): array
    {
        $sessaoModel = new Sessao();
        $presencaModel = new Presenca();
        $operacaoModel = new BanqueteOperacao();

        $sessoes = $sessaoModel->listarFuturas(8);
        $proximaSessao = $sessaoModel->obterProximaSessao();
        $sessaoFoco = null;

        if ($sessaoId !== null && $sessaoId > 0) {
            $sessaoFoco = $sessaoModel->findById($sessaoId);
        }
        if (!$sessaoFoco && $proximaSessao && !empty($proximaSessao['id'])) {
            $sessaoFoco = $sessaoModel->findById((int) $proximaSessao['id']);
        }

        $confirmados = [];
        $participantesAgape = [];
        $confirmadosSemAgape = [];
        $operacao = null;

        if ($sessaoFoco && !empty($sessaoFoco['id'])) {
            $sessaoFoco['descricao_agape'] = $sessaoModel->obterDescricaoAgape($sessaoFoco);
            $sessaoFoco['descricao_modelo_financeiro_agape'] = $sessaoModel->obterDescricaoModeloFinanceiroAgape($sessaoFoco);
            $confirmados = $presencaModel->listarConfirmadosPorSessao((int) $sessaoFoco['id']);
            $participantesAgape = $presencaModel->listarParticipantesAgapePorSessao((int) $sessaoFoco['id']);
            $confirmadosSemAgape = array_values(array_filter($confirmados, static fn (array $item): bool => empty($item['participara_agape'])));
            $operacao = $operacaoModel->obterPorSessao((int) $sessaoFoco['id']);
            $sessaoFoco['financeiro_banquete'] = $this->montarResumoFinanceiro($sessaoFoco, count($participantesAgape), $operacao);
        }

        return [
            'proxima_sessao' => $proximaSessao,
            'sessao_foco' => $sessaoFoco,
            'sessoes' => array_map(function (array $sessao) use ($sessaoModel): array {
                $sessao['descricao_agape'] = $sessaoModel->obterDescricaoAgape($sessao);
                return $sessao;
            }, $sessoes),
            'confirmados' => $confirmados,
            'participantes_agape' => $participantesAgape,
            'confirmados_sem_agape' => $confirmadosSemAgape,
            'operacao' => $operacao,
        ];
    }

    public function salvarOperacaoMiniapp(array $dados, ?int $autorId = null): array
    {
        $sessaoId = (int) ($dados['sessao_id'] ?? 0);
        if ($sessaoId <= 0) {
            return ['ok' => false, 'erro' => 'Sessão inválida para operação do banquete.'];
        }

        $ok = (new BanqueteOperacao())->salvar($sessaoId, $dados, $autorId);

        return ['ok' => $ok, 'erro' => $ok ? null : 'Não foi possível salvar a operação do banquete.'];
    }

    private function montarResumoFinanceiro(array $sessao, int $participantesAgape, ?array $operacao): array
    {
        $valorSessao = (float) ($sessao['agape_valor'] ?? 0);
        $valorUnitario = (float) ($operacao['valor_unitario_previsto'] ?? 0);
        if ($valorUnitario <= 0) {
            $valorUnitario = $valorSessao;
        }

        $valorEstimado = $valorUnitario > 0 ? round($valorUnitario * $participantesAgape, 2) : 0.0;
        $custoPrevisto = (float) ($operacao['custo_previsto'] ?? 0);
        $valorArrecadado = (float) ($operacao['valor_arrecadado'] ?? 0);
        $custoReal = (float) ($operacao['custo_real'] ?? 0);

        return [
            'valor_unitario' => $valorUnitario,
            'valor_estimado' => $valorEstimado,
            'custo_previsto' => $custoPrevisto,
            'valor_arrecadado' => $valorArrecadado,
            'custo_real' => $custoReal,
            'resultado_previsto' => round($valorEstimado - $custoPrevisto, 2),
            'resultado_real' => round($valorArrecadado - $custoReal, 2),
        ];
    }
}
