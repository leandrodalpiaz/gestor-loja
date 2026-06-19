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
            $email = strtolower(trim((string) ($payload['email'] ?? '')));
            $adminEmail = strtolower(trim((string) ($_ENV['SYSTEM_ADMIN_EMAIL'] ?? getenv('SYSTEM_ADMIN_EMAIL') ?: 'lsdalpiaz@gmail.com')));
            if ($email !== '' && ($email === $adminEmail || $email === 'lsdalpiaz@gmail.com')) {
                $obreiro = [
                    'id' => '00000000-0000-0000-0000-000000000000',
                    'nome' => 'Administrador Técnico',
                    'nome_historico' => 'Administrador Técnico',
                    'cim' => null,
                    'grau' => 3,
                    'cargo' => 'admin',
                    'cargo_principal' => 'admin',
                    'cargos' => ['admin'],
                    'ativo' => true,
                    'is_system_admin' => true,
                    'excluir_em_listas' => true,
                    'email' => $email,
                    'auth_user_id' => $authUserId,
                ];
            } else {
                return null;
            }
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
