<?php

namespace App\Controllers;

use App\Core\Auth\CurrentUser;

class AdminSupportController
{
    public function ativarModoSuporte()
    {
        // Apenas para quem é Admin técnico ou Venerável/Admin (Nível 3)
        if (empty($_SESSION['is_system_admin']) && !in_array('admin', $_SESSION['usuario_cargos'] ?? [], true)) {
            http_response_code(403);
            echo "Acesso negado. Apenas administradores podem ativar o modo de suporte.";
            exit;
        }

        // Ativa o modo de suporte na sessão
        $_SESSION['admin_mode'] = true;

        // Se uma loja_id foi passada via GET para personificar, nós podemos injetar.
        // O tenant (loja) já existe no context, mas podemos sobrescrever a variável da sessão.
        if (!empty($_GET['loja_id'])) {
            $_SESSION['tenant_id'] = trim($_GET['loja_id']);
        }

        // Registra o log na sessão para evitar sujar a tabela de log normal.
        $_SESSION['system_admin_action'] = true;

        header("Location: /dashboard");
        exit;
    }

    public function desativarModoSuporte()
    {
        unset($_SESSION['admin_mode']);
        unset($_SESSION['system_admin_action']);

        header("Location: /dashboard");
        exit;
    }
}
