<?php

namespace App\Core\Auth;

use App\Models\Obreiro;

class AccountGate
{
    public function __construct(
        private readonly Obreiro $obreiroModel,
    ) {
    }

    public function byTelegramId(int $telegramId): array
    {
        if ($telegramId <= 0) {
            return ['state' => 'inexistente', 'user' => null];
        }

        $user = $this->obreiroModel->findByTelegramIdAnyStatus($telegramId);
        if (!$user) {
            return ['state' => 'inexistente', 'user' => null];
        }

        return ['state' => $this->obreiroModel->resolverAcessoStatus($user), 'user' => $user];
    }

    public function byCim(string $cim): array
    {
        $cim = trim($cim);
        if ($cim === '') {
            return ['state' => 'inexistente', 'user' => null];
        }

        $user = $this->obreiroModel->findByCimAnyStatus($cim);
        if (!$user) {
            return ['state' => 'inexistente', 'user' => null];
        }

        return ['state' => $this->obreiroModel->resolverAcessoStatus($user), 'user' => $user];
    }
}

