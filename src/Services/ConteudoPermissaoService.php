<?php

namespace App\Services;

use App\Models\ConteudoCategoriaPermissao;

class ConteudoPermissaoService
{
    private ConteudoCategoriaPermissao $model;

    public function __construct()
    {
        $this->model = new ConteudoCategoriaPermissao();
    }

    public function getAllowedRoles(string $categoria): array
    {
        $base = ['chanceler', 'admin', 'veneravel'];
        if (!in_array($categoria, ['historia', 'palavra_dia'], true)) {
            return $base;
        }

        return array_values(array_unique(array_merge($base, $this->model->listarPorCategoria($categoria))));
    }

    public function canManage(string $categoria, array $roles): bool
    {
        $roles = array_values(array_unique(array_filter(array_map(
            static fn($role): string => strtolower(trim((string) $role)),
            $roles
        ))));

        foreach ($this->getAllowedRoles($categoria) as $allowed) {
            if (in_array($allowed, $roles, true)) {
                return true;
            }
        }

        return false;
    }

    public function salvarDelegacoes(string $categoria, array $roles): bool
    {
        return $this->model->salvarPorCategoria($categoria, $roles);
    }
}
