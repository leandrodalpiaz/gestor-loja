<?php

namespace App\Controllers;

use App\Models\Obreiro;
use App\Models\VidaLojaSinal;
use App\Models\VidaLojaAcompanhamento;
use App\Services\VidaLojaService;
use App\Core\Authorization\Authorizer;
use App\Core\Authorization\PermissionMap;
use App\Core\Auth\CurrentUser;

class VidaLojaController
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

    public function dashboard(): void
    {
        header('Location: /assistencia');
        exit;
    }

    public function sinais(): void
    {
        header('Location: /assistencia?tab=preventivo');
        exit;
    }

    private function obterDestinoRedirect(): string
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if (str_contains($referer, '/veneravel')) {
            return '/veneravel?tab=preventivo';
        }
        return '/assistencia?tab=preventivo';
    }

    public function atualizarSinalStatus(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $this->obterDestinoRedirect());
            exit;
        }

        $id = (int) ($_POST['sinal_id'] ?? 0);
        $status = trim((string) ($_POST['status'] ?? ''));
        $motivo = trim((string) ($_POST['motivo_resolucao'] ?? ''));
        $usuarioUuid = $this->obterUsuarioUuid();

        if ($id <= 0 || $status === '') {
            $_SESSION['mensagem_erro'] = 'Dados insuficientes para atualizar o status do sinal de cuidado.';
            header('Location: ' . $this->obterDestinoRedirect());
            exit;
        }

        $sinalModel = new VidaLojaSinal();
        $ok = $sinalModel->atualizarStatus($id, $status, $usuarioUuid, $motivo);

        $_SESSION[$ok ? 'mensagem_sucesso' : 'mensagem_erro'] = $ok
            ? 'Status do sinal de cuidado atualizado com sucesso.'
            : 'Não foi possível atualizar o status do sinal de cuidado.';

        header('Location: ' . $this->obterDestinoRedirect());
        exit;
    }

    public function salvarContato(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $this->obterDestinoRedirect());
            exit;
        }

        $obreiroId = trim((string) ($_POST['obreiro_id'] ?? ''));
        $resultado = trim((string) ($_POST['resultado'] ?? ''));

        if ($obreiroId === '' || $resultado === '') {
            $_SESSION['mensagem_erro'] = 'Por favor, preencha o irmão contatado e o sumário do contato.';
            header('Location: ' . $this->obterDestinoRedirect());
            exit;
        }

        $usuarioUuid = $this->obterUsuarioUuid();
        if ($usuarioUuid === null) {
            // Caso especial: se logado como admin sem obreiro vinculado, não permite criar contato
            $_SESSION['mensagem_erro'] = 'Seu usuário de suporte/administração não possui um cadastro de Obreiro ativo vinculado para assinar o contato.';
            header('Location: ' . $this->obterDestinoRedirect());
            exit;
        }

        $payload = [
            'sinal_id' => !empty($_POST['sinal_id']) ? (int) $_POST['sinal_id'] : null,
            'obreiro_id' => $obreiroId,
            'data_contato' => trim((string) ($_POST['data_contato'] ?? date('Y-m-d'))),
            'meio_contato' => trim((string) ($_POST['meio_contato'] ?? 'whatsapp')),
            'resultado' => $resultado,
            'proximo_acompanhamento' => !empty($_POST['proximo_acompanhamento']) ? trim((string) $_POST['proximo_acompanhamento']) : null,
            'nivel_sigilo' => trim((string) ($_POST['nivel_sigilo'] ?? 'reservado')),
            'observacoes_sigilosas' => !empty($_POST['observacoes_sigilosas']) ? trim((string) $_POST['observacoes_sigilosas']) : null,
            'responsavel_id' => $usuarioUuid,
        ];

        $acompanhamentoModel = new VidaLojaAcompanhamento();
        $ok = $acompanhamentoModel->criar($payload);

        if ($ok) {
            $_SESSION['mensagem_sucesso'] = 'Contato fraterno registrado com sucesso.';
            
            // Se o contato foi associado a um sinal e o sinal estava "aberto", move automaticamente para "em_observacao"
            if (!empty($payload['sinal_id'])) {
                $sinalModel = new VidaLojaSinal();
                $sinal = $sinalModel->buscarPorId($payload['sinal_id']);
                if ($sinal && $sinal['status'] === 'aberto') {
                    $sinalModel->atualizarStatus($sinal['id'], 'em_observacao');
                }
            }
        } else {
            $_SESSION['mensagem_erro'] = 'Não foi possível registrar o contato fraterno.';
        }

        header('Location: ' . $this->obterDestinoRedirect());
        exit;
    }

    public function excluirContato(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $this->obterDestinoRedirect());
            exit;
        }

        $id = (int) ($_POST['acompanhamento_id'] ?? 0);
        $motivo = trim((string) ($_POST['motivo_exclusao'] ?? ''));
        $usuarioUuid = $this->obterUsuarioUuid();

        if ($id <= 0) {
            $_SESSION['mensagem_erro'] = 'ID do contato inválido.';
            header('Location: ' . $this->obterDestinoRedirect());
            exit;
        }

        $acompanhamentoModel = new VidaLojaAcompanhamento();
        $ok = $acompanhamentoModel->softDelete($id, $usuarioUuid, $motivo);

        $_SESSION[$ok ? 'mensagem_sucesso' : 'mensagem_erro'] = $ok
            ? 'Contato fraterno removido (exclusão lógica) com sucesso.'
            : 'Não foi possível remover o contato fraterno.';

        header('Location: ' . $this->obterDestinoRedirect());
        exit;
    }
}
