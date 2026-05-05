<?php

namespace App\Controllers;

use App\Config\FeatureFlags;
use App\Core\Authorization\Authorizer;

class PwaAdminController
{
    public function index(): void
    {
        if (!FeatureFlags::pwaAdminCrud()) {
            http_response_code(404);
            echo 'Recurso indisponível.';
            return;
        }

        $authorizer = new Authorizer($_SESSION);
        $links = [];

        if ($authorizer->hasPermission('secretaria.manage')) {
            $links[] = ['label' => 'Secretaria', 'href' => '/secretaria', 'desc' => 'Publicações, agendas e rotinas.'];
        }

        if ($authorizer->hasPermission('tesouraria.manage')) {
            $links[] = ['label' => 'Tesouraria', 'href' => '/tesouraria/caixa', 'desc' => 'Caixa, comprovantes, fechamento.'];
        }

        if ($authorizer->hasPermission('chancelaria.manage')) {
            $links[] = ['label' => 'Chancelaria', 'href' => '/chanceler/sessao', 'desc' => 'Sessões e conteúdos da chancelaria.'];
        }

        if ($authorizer->hasPermission('biblioteca.manage')) {
            $links[] = ['label' => 'Biblioteca', 'href' => '/biblioteca', 'desc' => 'Gestão do acervo e empréstimos.'];
        }

        if (!empty($_SESSION['is_system_admin'])) {
            $links[] = ['label' => 'Sistema', 'href' => '/sistema', 'desc' => 'Ajustes técnicos e auditoria.'];
        }

        require __DIR__ . '/../Views/pwa/admin_home.php';
    }
}
