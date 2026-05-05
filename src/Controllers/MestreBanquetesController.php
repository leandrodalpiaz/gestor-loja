<?php

namespace App\Controllers;

use App\Models\BanqueteOperacao;
use App\Models\Presenca;
use App\Models\Sessao;

class MestreBanquetesController
{
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
        $autorId = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : null;
        $ok = $sessaoId > 0 && (new BanqueteOperacao())->salvar($sessaoId, $_POST, $autorId);

        $_SESSION[$ok ? 'mensagem_sucesso' : 'mensagem_erro'] = $ok
            ? 'Operação do banquete atualizada com sucesso.'
            : 'Não foi possível atualizar a operação do banquete.';

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
