<?php

namespace App\Controllers;

use App\Models\Balaustre;
use App\Models\Obreiro;
use App\Models\Presenca;
use App\Models\PresencaSessao;
use App\Models\Sessao;

class ChancelerSessaoController
{
    public function index(): void
    {
        $sessaoModel = new Sessao();
        $presencaModel = new Presenca();
        $presencaSessaoModel = new PresencaSessao();
        $balaustreModel = new Balaustre();
        $obreiroModel = new Obreiro();

        $proximaSessao = $sessaoModel->obterProximaSessao();
        $sessoes = $sessaoModel->listarFuturas(8);
        $sessaoSelecionadaId = (int) ($_GET['sessao_id'] ?? 0);
        $sessaoSelecionada = null;
        $mapaPresencas = [];
        $confirmados = [];
        $visitantesResumo = [];
        $obreiros = $obreiroModel->getAllAtivos();

        if ($sessaoSelecionadaId > 0) {
            $sessaoSelecionada = $sessaoModel->findById($sessaoSelecionadaId);
        }
        if (!$sessaoSelecionada && $proximaSessao && !empty($proximaSessao['id'])) {
            $sessaoSelecionada = $sessaoModel->findById((int) $proximaSessao['id']);
        }

        if ($sessaoSelecionada && !empty($sessaoSelecionada['id'])) {
            $sessaoId = (int) $sessaoSelecionada['id'];
            $mapaPresencas = $presencaSessaoModel->listarMapaPorSessao($sessaoId);
            $confirmados = $presencaModel->listarConfirmadosPorSessao($sessaoId);
            $visitantesResumo = $balaustreModel->obterResumoVisitantesPorSessao($sessaoId);
        }

        require_once __DIR__ . '/../Views/chanceler_sessao/index.php';
    }

    public function registrarPresenca(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /chanceler/sessao');
            exit;
        }

        $sessaoId = (int) ($_POST['sessao_id'] ?? 0);
        $obreiroId = trim((string) ($_POST['obreiro_id'] ?? ''));
        $presente = isset($_POST['presente']) && $_POST['presente'] === '1';
        $observacao = trim((string) ($_POST['observacao'] ?? ''));
        $autorId = trim((string) ($_SESSION['usuario_id'] ?? ''));

        if ($sessaoId <= 0 || $obreiroId === '') {
            $_SESSION['mensagem_erro'] = 'Dados insuficientes para registrar a presença.';
            header('Location: /chanceler/sessao');
            exit;
        }

        $ok = (new PresencaSessao())->registrar(
            $sessaoId,
            $obreiroId,
            $presente,
            $autorId !== '' ? $autorId : null,
            $observacao !== '' ? $observacao : null
        );

        $_SESSION[$ok ? 'mensagem_sucesso' : 'mensagem_erro'] = $ok
            ? 'Presença efetiva atualizada pelo Chanceler.'
            : 'Não foi possível atualizar a presença efetiva.';

        header('Location: /chanceler/sessao?sessao_id=' . urlencode((string) $sessaoId));
        exit;
    }

    public function montarPayloadMiniapp(?int $sessaoId = null): array
    {
        $sessaoModel = new Sessao();
        $presencaModel = new Presenca();
        $presencaSessaoModel = new PresencaSessao();
        $balaustreModel = new Balaustre();

        $sessoes = $sessaoModel->listarFuturas(8);
        $proximaSessao = $sessaoModel->obterProximaSessao();
        $sessaoFoco = null;

        if ($sessaoId !== null && $sessaoId > 0) {
            $sessaoFoco = $sessaoModel->findById($sessaoId);
        }
        if (!$sessaoFoco && $proximaSessao && !empty($proximaSessao['id'])) {
            $sessaoFoco = $sessaoModel->findById((int) $proximaSessao['id']);
        }

        $mapaPresencas = [];
        $confirmados = [];
        $visitantesResumo = [];

        if ($sessaoFoco && !empty($sessaoFoco['id'])) {
            $sessaoFocoId = (int) $sessaoFoco['id'];
            $mapaPresencas = $presencaSessaoModel->listarMapaPorSessao($sessaoFocoId);
            $confirmados = $presencaModel->listarConfirmadosPorSessao($sessaoFocoId);
            $visitantesResumo = $balaustreModel->obterResumoVisitantesPorSessao($sessaoFocoId);
        }

        return [
            'proxima_sessao' => $proximaSessao ? $this->mapearSessao($proximaSessao) : null,
            'sessao_foco' => $sessaoFoco ? $this->mapearSessao($sessaoFoco) : null,
            'sessoes' => array_map(fn (array $sessao): array => $this->mapearSessao($sessao), $sessoes),
            'confirmados' => array_map(static function (array $item): array {
                return [
                    'nome' => (string) ($item['nome'] ?? 'Obreiro'),
                    'cim' => (string) ($item['cim'] ?? ''),
                    'participara_agape' => (bool) ($item['participara_agape'] ?? false),
                ];
            }, $confirmados),
            'visitantes' => array_map(static function (array $item): array {
                return [
                    'nome' => (string) ($item['nome'] ?? 'Visitante'),
                    'linha_resumida' => (string) ($item['linha_resumida'] ?? ''),
                ];
            }, $visitantesResumo),
            'presencas' => array_map(static function (array $item): array {
                return [
                    'id' => (string) ($item['id'] ?? ''),
                    'nome' => (string) ($item['nome'] ?? 'Obreiro'),
                    'cim' => (string) ($item['cim'] ?? ''),
                    'grau' => (string) ($item['grau'] ?? ''),
                    'presente' => (bool) ($item['presente'] ?? false),
                ];
            }, $mapaPresencas),
        ];
    }

    public function registrarPresencaMiniapp(int $sessaoId, string $obreiroId, bool $presente, ?string $autorId = null, ?string $observacao = null): array
    {
        if ($sessaoId <= 0 || trim($obreiroId) === '') {
            return ['ok' => false, 'erro' => 'Dados insuficientes para atualizar a presença.'];
        }

        $ok = (new PresencaSessao())->registrar(
            $sessaoId,
            trim($obreiroId),
            $presente,
            $autorId,
            $observacao
        );

        return ['ok' => $ok, 'erro' => $ok ? null : 'Não foi possível atualizar a presença.'];
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
            'agape_modalidade' => (string) ($sessao['agape_modalidade'] ?? 'nao_havera'),
            'agape_modelo_financeiro' => (string) ($sessao['agape_modelo_financeiro'] ?? 'oficial_loja'),
        ];
    }
}
