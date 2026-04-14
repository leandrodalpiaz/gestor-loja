<?php

namespace App\Services;

use App\Models\PalavraDia;

class PalavraDiaService
{
    private PalavraDia $model;

    public function __construct()
    {
        $this->model = new PalavraDia();
    }

    public function selecionarParaUsuario(string $usuarioRef, ?\DateTimeImmutable $data = null): array
    {
        $data = $data ?? $this->today();
        $ativas = $this->model->listarAtivas();
        if ($ativas === []) {
            return [
                'titulo' => 'Palavra do Dia',
                'mensagem' => 'Que a Luz do Grande Arquiteto do Universo inspire nossos pensamentos, palavras e obras neste dia.',
                'observacao' => null,
                'origem_padrao' => true,
            ];
        }

        $seed = $data->format('Y-m-d') . '|' . trim($usuarioRef);
        $hash = abs(crc32($seed));
        $indice = $hash % count($ativas);

        return $ativas[$indice];
    }

    private function today(): \DateTimeImmutable
    {
        $timezone = trim((string) ($_ENV['APP_TIMEZONE'] ?? 'America/Sao_Paulo'));
        try {
            return new \DateTimeImmutable('today', new \DateTimeZone($timezone));
        } catch (\Throwable $e) {
            return new \DateTimeImmutable('today', new \DateTimeZone('America/Sao_Paulo'));
        }
    }
}
