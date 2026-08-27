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
    private SecretariaController $secretaria;

    public function __construct()
    {
        $this->secretaria = new SecretariaController();
    }

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
        $this->salvar(fn(): bool => (new TrabalhoSessao())->criar($_POST, $this->autorId()), 'Trabalho registrado.', 'Não foi possível registrar o trabalho.');
    }

    public function salvarPublicacao(): void
    {
        $this->salvar(fn(): bool => (new PublicacaoSecretaria())->criar($_POST, $this->autorId()), 'Publicação registrada.', 'Não foi possível registrar a publicação.');
    }

    public function salvarBalaustre(): void
    {
        $sessaoId = (int) ($_POST['sessao_id'] ?? 0);
        $this->salvar(fn(): bool => $sessaoId > 0 && (new Balaustre())->salvarPorSessao($sessaoId, $_POST, $this->autorId()), 'Balaustre salvo.', 'Selecione uma sessão válida para salvar o balaustre.');
    }

    public function sessoes(): void { $this->secretaria->sessoes(); }
    public function balaustres(): void { $this->secretaria->balaustres(); }
    public function trabalhosPublicacoes(): void { $this->secretaria->trabalhosPublicacoes(); }
    public function convitesExternos(): void { $this->secretaria->convitesExternos(); }
    public function votacao(): void { $this->secretaria->votacao(); }
    public function relatorioAnual(): void { $this->secretaria->relatorioAnual(); }
    public function relatorioGestao(): void { $this->secretaria->relatorioGestao(); }
    public function nominata(): void { $this->secretaria->nominata(); }
    public function acessos(): void { $this->secretaria->acessos(); }
    public function convites(): void { $this->secretaria->convites(); }
    public function conteudoPublico(): void { $this->secretaria->conteudoPublico(); }
    public function visualizarBalaustre(): void { $this->secretaria->visualizarBalaustre(); }

    public function salvarSessao(): void { $this->secretaria->salvarSessao(); }
    public function publicarSessaoRascunho(): void { $this->secretaria->publicarSessaoRascunho(); }
    public function cancelarRascunhoSessao(): void { $this->secretaria->cancelarRascunhoSessao(); }
    public function marcarBalaustreApto(): void { $this->secretaria->marcarBalaustreApto(); }
    public function abrirVotacaoBalaustre(): void { $this->secretaria->abrirVotacaoBalaustre(); }
    public function votarBalaustre(): void { $this->secretaria->votarBalaustre(); }
    public function encerrarVotacaoBalaustre(): void { $this->secretaria->encerrarVotacaoBalaustre(); }
    public function salvarNominata(): void { $this->secretaria->salvarNominata(); }
    public function atualizarAcesso(): void { $this->secretaria->atualizarAcesso(); }
    public function gerarConvite(): void { $this->secretaria->gerarConvite(); }
    public function salvarConviteExterno(): void { $this->secretaria->salvarConviteExterno(); }
    public function removerAnexoConviteExterno(): void { $this->secretaria->removerAnexoConviteExterno(); }
    public function confirmarConviteExterno(): void { $this->secretaria->confirmarConviteExterno(); }
    public function salvarConteudoPublico(): void { $this->secretaria->salvarConteudoPublico(); }
    public function excluirConteudoPublico(): void { $this->secretaria->excluirConteudoPublico(); }

    private function acaoSessao(string $acao): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect();
        }

        $sessaoId = (int) ($_POST['sessao_id'] ?? 0);
        if ($sessaoId <= 0) {
            $_SESSION['mensagem_erro'] = 'Sessão inválida.';
            $this->redirect();
        }

        $model = new Sessao();
        $autorId = $this->autorId();
        $ok = match ($acao) {
            'publicar' => $model->marcarPublicada($sessaoId, $autorId, 'Publicação operacional realizada pelo PWA Secretaria.'),
            'cancelar' => $model->cancelar($sessaoId, $autorId, 'Cancelamento operacional realizado pelo PWA Secretaria.'),
            'reabrir' => $model->reabrir($sessaoId, $autorId, 'Reabertura operacional realizada pelo PWA Secretaria.'),
            default => false,
        };

        $_SESSION[$ok ? 'mensagem_sucesso' : 'mensagem_erro'] = $ok ? 'Sessão atualizada.' : 'Não foi possível atualizar a sessão.';
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
