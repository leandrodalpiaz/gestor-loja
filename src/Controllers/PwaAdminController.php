<?php

namespace App\Controllers;

use App\Core\Authorization\Authorizer;

class PwaAdminController
{
    public function index(): void
    {
        $authorizer = new Authorizer($_SESSION);
        $links = [];

        foreach (PwaCargosController::modules() as $slug => $module) {
            if ($authorizer->hasPermission((string) $module['permission'])) {
                $links[] = [
                    'label' => (string) $module['title'],
                    'href' => '/pwa/' . $slug,
                    'desc' => (string) $module['summary'],
                ];
            }
        }

        require __DIR__ . '/../Views/pwa/admin_home.php';
    }
}
