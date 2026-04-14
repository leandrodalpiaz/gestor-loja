<?php

namespace App\Services;

use App\Models\HistoriaMaconica;

class HistoriaMaconicaService
{
    private HistoriaMaconica $model;

    public function __construct()
    {
        $this->model = new HistoriaMaconica();
    }

    public function listarParaHoje(?\DateTimeImmutable $data = null): array
    {
        $data = $data ?? $this->today();
        return $this->model->buscarPorDiaMes((int) $data->format('d'), (int) $data->format('m'), true);
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
