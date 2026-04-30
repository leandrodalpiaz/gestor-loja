<?php

namespace App\Controllers;

use App\Config\FeatureFlags;

class PwaAdminController
{
    public function index(): void
    {
        if (!FeatureFlags::pwaAdminCrud()) {
            http_response_code(404);
            echo 'Recurso indisponível.';
            return;
        }

        $links = [
            ['label' => 'Secretaria', 'href' => '/secretaria', 'desc' => 'Publicações, agendas e rotinas.'],
            ['label' => 'Tesouraria', 'href' => '/tesouraria', 'desc' => 'Caixa, comprovantes, fechamento.'],
            ['label' => 'Chancelaria', 'href' => '/chanceler/sessao', 'desc' => 'Sessões e conteúdos da chancelaria.'],
            ['label' => 'Biblioteca', 'href' => '/biblioteca', 'desc' => 'Gestão do acervo e empréstimos.'],
            // Links tecnicos ficam visiveis apenas para admin do sistema.
        ];

        if (!empty($_SESSION['is_system_admin'])) {
            $links[] = ['label' => 'Sistema', 'href' => '/sistema', 'desc' => 'Ajustes técnicos e auditoria.'];
        }

        require __DIR__ . '/../Views/pwa/admin_home.php';
    }
}

