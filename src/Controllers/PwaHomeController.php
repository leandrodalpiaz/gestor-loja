<?php

namespace App\Controllers;

use App\Config\FeatureFlags;

class PwaHomeController
{
    public function index(): void
    {
        $links = [
            'sessoes' => FeatureFlags::pwaSessoes(),
            'biblioteca' => FeatureFlags::pwaBiblioteca(),
            'comunicacao' => FeatureFlags::pwaComunicacao(),
        ];

        require __DIR__ . '/../Views/pwa/home.php';
    }
}

