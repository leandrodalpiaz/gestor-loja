<?php

namespace App\Controllers;

use App\Models\Balaustre;
use App\Models\Obreiro;
use App\Models\Presenca;
use App\Models\PresencaSessao;
use App\Models\Sessao;

class PwaChancelariaController
{
    public function index(): void
    {
        $sessaoModel = new Sessao();
        $presencaModel = new Presenca();
        $presencaSessaoModel = new PresencaSessao();
        $balaustreModel = new Balaustre();

        $sessoes = $sessaoModel->listarFuturas(8);
        $sessaoId = (int) ($_GET['sessao_id'] ?? 0);
        $sessao = $sessaoId > 0 ? $sessaoModel->findById($sessaoId) : null;
        if (!$sessao) {
            $proxima = $sessaoModel->obterProximaSessao();
            $sessao = $proxima && !empty($proxima['id']) ? $sessaoModel->findById((int) $proxima['id']) : null;
        }

        $confirmados = [];
        $presencas = [];
        $visitantes = [];
        if ($sessao && !empty($sessao['id'])) {
            $sessaoId = (int) $sessao['id'];
            $confirmados = $presencaModel->listarConfirmadosPorSessao($sessaoId);
            $presencas = $presencaSessaoModel->listarMapaPorSessao($sessaoId);
            $visitantes = $balaustreModel->obterResumoVisitantesPorSessao($sessaoId);
        }

        if ($presencas === []) {
            $presencas = array_map(static function (array $obreiro): array {
                return [
                    'id' => (string) ($obreiro['id'] ?? ''),
                    'nome' => (string) ($obreiro['nome_historico'] ?? $obreiro['nome'] ?? 'Obreiro'),
                    'cim' => (string) ($obreiro['cim'] ?? ''),
                    'grau' => (string) ($obreiro['grau'] ?? ''),
                    'presente' => false,
                    'observacao' => null,
                ];
            }, (new Obreiro())->getAllAtivos());
        }

        $mensagemSucesso = $_SESSION['mensagem_sucesso'] ?? null;
        $mensagemErro = $_SESSION['mensagem_erro'] ?? null;
        unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']);

        require __DIR__ . '/../Views/pwa/chancelaria.php';
    }

    public function presenca(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /pwa/chancelaria');
            exit;
        }

        $sessaoId = (int) ($_POST['sessao_id'] ?? 0);
        $obreiroId = trim((string) ($_POST['obreiro_id'] ?? ''));
        $presente = (string) ($_POST['presente'] ?? '') === '1';
        $observacao = trim((string) ($_POST['observacao'] ?? ''));

        $ok = $sessaoId > 0 && $obreiroId !== ''
            ? (new PresencaSessao())->registrar(
                $sessaoId,
                $obreiroId,
                $presente,
                $this->currentUserUuidOrNull(),
                $observacao !== '' ? $observacao : null
            )
            : false;

        $_SESSION[$ok ? 'mensagem_sucesso' : 'mensagem_erro'] = $ok
            ? 'Presença efetiva atualizada.'
            : 'Não foi possível atualizar a presença.';

        header('Location: /pwa/chancelaria?sessao_id=' . urlencode((string) $sessaoId));
        exit;
    }

    public function visitante(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /pwa/chancelaria');
            exit;
        }

        $sessaoId = (int) ($_POST['sessao_id'] ?? 0);
        $nome = trim((string) ($_POST['nome'] ?? ''));
        $ok = $sessaoId > 0 && $nome !== ''
            ? (new Balaustre())->adicionarVisitanteSessao($sessaoId, $_POST, $this->currentUserUuidOrNull())
            : false;

        $_SESSION[$ok ? 'mensagem_sucesso' : 'mensagem_erro'] = $ok
            ? 'Visitante registrado.'
            : 'Não foi possível registrar o visitante.';

        header('Location: /pwa/chancelaria?sessao_id=' . urlencode((string) $sessaoId));
        exit;
    }

    private function currentUserUuidOrNull(): ?string
    {
        $id = trim((string) ($_SESSION['usuario_id'] ?? ''));
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $id) === 1 ? $id : null;
    }
}
