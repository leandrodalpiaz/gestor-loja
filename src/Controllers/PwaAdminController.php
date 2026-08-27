<?php

namespace App\Controllers;

use App\Core\Auth\CurrentUser;
use App\Core\Authorization\Authorizer;
use App\Core\Authorization\PermissionMap;

class PwaAdminController
{
    public function index(): void
    {
        $authorizer = new Authorizer(new CurrentUser($_SESSION), new PermissionMap());
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
