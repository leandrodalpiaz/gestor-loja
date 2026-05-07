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
        $dataSessaoInformada = trim((string) ($_GET['data_sessao'] ?? ''));
        if ($dataSessaoInformada === '' && $sessaoSelecionadaId <= 0) {
            $dataSessaoInformada = (new \DateTimeImmutable('today', new \DateTimeZone('America/Sao_Paulo')))->format('Y-m-d');
        }
        $sessaoSelecionada = null;
        $mapaPresencas = [];
        $confirmados = [];
        $visitantesResumo = [];
        $obreiros = $obreiroModel->getAllAtivos();

        if ($sessaoSelecionadaId > 0) {
            $sessaoSelecionada = $sessaoModel->findById($sessaoSelecionadaId);
        }
        if (!$sessaoSelecionada && $dataSessaoInformada !== '') {
            $autorId = $this->currentUserUuidOrNull();
            $sessaoSelecionada = $sessaoModel->obterOuCriarControleChancelerPorData(
                $dataSessaoInformada,
                $autorId
            );
            if ($sessaoSelecionada && !empty($sessaoSelecionada['id'])) {
                $vistos = [];
                $sessoesUnicas = [];
                foreach (array_merge([$sessaoSelecionada], $sessoes) as $sessaoOpcao) {
                    $idOpcao = (int) ($sessaoOpcao['id'] ?? 0);
                    if ($idOpcao <= 0 || isset($vistos[$idOpcao])) {
                        continue;
                    }
                    $vistos[$idOpcao] = true;
                    $sessoesUnicas[] = $sessaoOpcao;
                }
                $sessoes = $sessoesUnicas;
            }
        }
        if (!$sessaoSelecionada && $proximaSessao && !empty($proximaSessao['id']) && empty($_GET['data_sessao'])) {
            $sessaoSelecionada = $sessaoModel->findById((int) $proximaSessao['id']);
        }

        if ($sessaoSelecionada && !empty($sessaoSelecionada['id'])) {
            $sessaoId = (int) $sessaoSelecionada['id'];
            $mapaPresencas = $presencaSessaoModel->listarMapaPorSessao($sessaoId);
            $confirmados = $presencaModel->listarConfirmadosPorSessao($sessaoId);
            $visitantesResumo = $balaustreModel->obterResumoVisitantesPorSessao($sessaoId);
        }
        if ($mapaPresencas === [] && $obreiros !== []) {
            $mapaPresencas = array_map(static function (array $obreiro): array {
                return [
                    'id' => (string) ($obreiro['id'] ?? ''),
                    'nome' => (string) ($obreiro['nome_historico'] ?? $obreiro['nome'] ?? 'Obreiro'),
                    'cim' => (string) ($obreiro['cim'] ?? ''),
                    'grau' => (string) ($obreiro['grau'] ?? ''),
                    'presente' => false,
                    'observacao' => null,
                ];
            }, $obreiros);
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
        $autorId = $this->currentUserUuidOrNull();

        if ($sessaoId <= 0 || $obreiroId === '') {
            $_SESSION['mensagem_erro'] = 'Dados insuficientes para registrar a presença.';
            header('Location: /chanceler/sessao');
            exit;
        }

        $ok = (new PresencaSessao())->registrar(
            $sessaoId,
            $obreiroId,
            $presente,
            $autorId,
            $observacao !== '' ? $observacao : null
        );

        $_SESSION[$ok ? 'mensagem_sucesso' : 'mensagem_erro'] = $ok
            ? 'Presença efetiva atualizada pelo Chanceler.'
            : 'Não foi possível atualizar a presença efetiva.';

        header('Location: /chanceler/sessao?sessao_id=' . urlencode((string) $sessaoId));
        exit;
    }

    public function registrarVisitante(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /chanceler/sessao');
            exit;
        }

        $sessaoId = (int) ($_POST['sessao_id'] ?? 0);
        $nome = trim((string) ($_POST['nome'] ?? ''));
        $autorId = $this->currentUserUuidOrNull();
        if ($sessaoId <= 0) {
            $sessaoControle = (new Sessao())->obterOuCriarControleChancelerPorData(
                (new \DateTimeImmutable('today', new \DateTimeZone('America/Sao_Paulo')))->format('Y-m-d'),
                $autorId
            );
            $sessaoId = (int) ($sessaoControle['id'] ?? 0);
        }

        if ($sessaoId <= 0 || $nome === '') {
            $_SESSION['mensagem_erro'] = 'Informe a sessão e o nome do visitante.';
            header('Location: /chanceler/sessao' . ($sessaoId > 0 ? '?sessao_id=' . urlencode((string) $sessaoId) : ''));
            exit;
        }

        $ok = (new Balaustre())->adicionarVisitanteSessao($sessaoId, $_POST, $autorId);
        $_SESSION[$ok ? 'mensagem_sucesso' : 'mensagem_erro'] = $ok
            ? 'Visitante registrado para abastecer Balaústre e Orador.'
            : 'Não foi possível registrar o visitante.';

        header('Location: /chanceler/sessao?sessao_id=' . urlencode((string) $sessaoId));
        exit;
    }

    public function cancelarSessao(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /chanceler/sessao');
            exit;
        }

        $sessaoId = (int) ($_POST['sessao_id'] ?? 0);
        if ($sessaoId <= 0) {
            $_SESSION['mensagem_erro'] = 'Sessão inválida para cancelamento.';
            header('Location: /chanceler/sessao');
            exit;
        }

        $autorId = $this->currentUserUuidOrNull();
        $ok = (new Sessao())->cancelar($sessaoId, $autorId, 'Cancelamento solicitado no painel do Chanceler.');

        $_SESSION[$ok ? 'mensagem_sucesso' : 'mensagem_erro'] = $ok
            ? 'Sessão cancelada com sucesso.'
            : 'Não foi possível cancelar a sessão.';

        header('Location: /chanceler/sessao?sessao_id=' . urlencode((string) $sessaoId));
        exit;
    }

    public function excluirSessao(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /chanceler/sessao');
            exit;
        }

        $sessaoId = (int) ($_POST['sessao_id'] ?? 0);
        if ($sessaoId <= 0) {
            $_SESSION['mensagem_erro'] = 'Sessão inválida para exclusão.';
            header('Location: /chanceler/sessao');
            exit;
        }

        $ok = (new Sessao())->excluir($sessaoId);

        $_SESSION[$ok ? 'mensagem_sucesso' : 'mensagem_erro'] = $ok
            ? 'Sessão excluída com sucesso.'
            : 'Não foi possível excluir a sessão.';

        header('Location: /chanceler/sessao');
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

    private function currentUserUuidOrNull(): ?string
    {
        $id = trim((string) ($_SESSION['usuario_id'] ?? ''));
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $id) === 1 ? $id : null;
    }
}
