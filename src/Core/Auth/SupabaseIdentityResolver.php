<?php

namespace App\Core\Auth;

use App\Models\Obreiro;

class SupabaseIdentityResolver
{
    public static function resolve(string $token): ?array
    {
        $payload = SupabaseJwtValidator::validate($token);
        if (!$payload) {
            return null;
        }

        $authUserId = strtolower(trim((string) ($payload['sub'] ?? '')));
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $authUserId)) {
            return null;
        }

        $obreiro = (new Obreiro())->findByAuthUserId($authUserId);
        if (!$obreiro) {
            return null;
        }

        $ativo = filter_var($obreiro['ativo'] ?? false, FILTER_VALIDATE_BOOL);
        $acessoStatus = strtolower(trim((string) ($obreiro['acesso_status'] ?? 'ativo')));
        if (!$ativo || $acessoStatus === 'inativo') {
            return null;
        }

        return [
            'payload' => $payload,
            'obreiro' => $obreiro,
        ];
    }
}
