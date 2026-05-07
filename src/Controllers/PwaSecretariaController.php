<?php

namespace App\Controllers;

use App\Models\Balaustre;
use App\Models\Presenca;
use App\Models\PublicacaoSecretaria;
use App\Models\RelatorioSecretariaAnual;
use App\Models\Sessao;
use App\Models\TrabalhoSessao;

class PwaSecretariaController
{
    public function index(): void
    {
        $sessaoModel = new Sessao();
        $presencaModel = new Presenca();

        $sessoes = $sessaoModel->listarFuturas(12);
        $sessaoId = (int) ($_GET['sessao_id'] ?? ($sessoes[0]['id'] ?? 0));
        $sessao = $sessaoId > 0 ? $sessaoModel->findById($sessaoId) : null;
        $confirmados = $sessaoId > 0 ? $presencaModel->listarConfirmadosPorSessao($sessaoId) : [];
        $agape = $sessaoId > 0 ? $presencaModel->listarParticipantesAgapePorSessao($sessaoId) : [];
        $trabalhos = (new TrabalhoSessao())->listarRecentes(8);
        $publicacoes = (new PublicacaoSecretaria())->listarRecentes(8);
        $balaustres = (new Balaustre())->listarRecentes(8);
        $relatorio = (new RelatorioSecretariaAnual())->montar((int) date('Y'));
        $mensagemSucesso = $_SESSION['mensagem_sucesso'] ?? null;
        $mensagemErro = $_SESSION['mensagem_erro'] ?? null;
        unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']);

        require __DIR__ . '/../Views/pwa/secretaria.php';
    }

    public function publicarSessao(): void
    {
        $this->acaoSessao('publicar');
    }

    public function cancelarSessao(): void
    {
        $this->acaoSessao('cancelar');
    }

    public function reabrirSessao(): void
    {
        $this->acaoSessao('reabrir');
    }

    public function salvarTrabalho(): void
    {
        $this->salvar(fn(): bool => (new TrabalhoSessao())->criar($_POST, $this->autorId()), 'Trabalho registrado.', 'Nao foi possivel registrar o trabalho.');
    }

    public function salvarPublicacao(): void
    {
        $this->salvar(fn(): bool => (new PublicacaoSecretaria())->criar($_POST, $this->autorId()), 'Publicacao registrada.', 'Nao foi possivel registrar a publicacao.');
    }

    public function salvarBalaustre(): void
    {
        $sessaoId = (int) ($_POST['sessao_id'] ?? 0);
        $this->salvar(fn(): bool => $sessaoId > 0 && (new Balaustre())->salvarPorSessao($sessaoId, $_POST, $this->autorId()), 'Balaustre salvo.', 'Selecione uma sessao valida para salvar o balaustre.');
    }

    private function acaoSessao(string $acao): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect();
        }

        $sessaoId = (int) ($_POST['sessao_id'] ?? 0);
        if ($sessaoId <= 0) {
            $_SESSION['mensagem_erro'] = 'Sessao invalida.';
            $this->redirect();
        }

        $model = new Sessao();
        $autorId = $this->autorId();
        $ok = match ($acao) {
            'publicar' => $model->marcarPublicada($sessaoId, $autorId, 'Publicacao operacional realizada pelo PWA Secretaria.'),
            'cancelar' => $model->cancelar($sessaoId, $autorId, 'Cancelamento operacional realizado pelo PWA Secretaria.'),
            'reabrir' => $model->reabrir($sessaoId, $autorId, 'Reabertura operacional realizada pelo PWA Secretaria.'),
            default => false,
        };

        $_SESSION[$ok ? 'mensagem_sucesso' : 'mensagem_erro'] = $ok ? 'Sessao atualizada.' : 'Nao foi possivel atualizar a sessao.';
        $this->redirect($sessaoId);
    }

    private function salvar(callable $callback, string $sucesso, string $erro): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect();
        }

        $ok = (bool) $callback();
        $_SESSION[$ok ? 'mensagem_sucesso' : 'mensagem_erro'] = $ok ? $sucesso : $erro;
        $this->redirect((int) ($_POST['sessao_id'] ?? 0));
    }

    private function autorId(): ?string
    {
        $autorId = trim((string) ($_SESSION['usuario_id'] ?? ''));
        return $autorId !== '' ? $autorId : null;
    }

    private function redirect(int $sessaoId = 0): void
    {
        $url = '/pwa/secretaria';
        if ($sessaoId > 0) {
            $url .= '?sessao_id=' . $sessaoId;
        }
        header('Location: ' . $url);
        exit;
    }
}
