<?php

namespace App\Controllers;

use App\Models\Balaustre;
use App\Models\Sessao;

class OradorController
{
    public function index(): void
    {
        $sessaoModel = new Sessao();
        $balaustreModel = new Balaustre();

        $proximaSessao = $sessaoModel->obterProximaSessao();
        $sessoes = $sessaoModel->listarFuturas(8);
        $sessaoSelecionadaId = (int) ($_GET['sessao_id'] ?? 0);
        $sessaoEmFoco = null;

        if ($sessaoSelecionadaId > 0) {
            $sessaoEmFoco = $sessaoModel->findById($sessaoSelecionadaId);
        }
        if (!$sessaoEmFoco && $proximaSessao && !empty($proximaSessao['id'])) {
            $sessaoEmFoco = $sessaoModel->findById((int) $proximaSessao['id']);
        }

        $visitantesResumo = [];
        $cargosSessao = [];
        $eventosSessao = [];
        $lembretes = [];

        if ($sessaoEmFoco && !empty($sessaoEmFoco['id'])) {
            $visitantesResumo = $balaustreModel->obterResumoVisitantesPorSessao((int) $sessaoEmFoco['id']);
            $dadosBalaustre = $this->obterDadosBalaustre($balaustreModel, (int) $sessaoEmFoco['id']);
            $cargosSessao = $dadosBalaustre['cargos_sessao'] ?? [];
            $eventosSessao = $this->montarEventosSessao($dadosBalaustre);
            $lembretes = $this->montarLembretes($sessaoEmFoco, $visitantesResumo, $cargosSessao, $eventosSessao);
        }

        require_once __DIR__ . '/../Views/orador/index.php';
    }

    public function montarPayloadMiniapp(?int $sessaoId = null): array
    {
        $sessaoModel = new Sessao();
        $balaustreModel = new Balaustre();

        $sessoes = $sessaoModel->listarFuturas(8);
        $proximaSessao = $sessaoModel->obterProximaSessao();
        $sessaoFoco = null;

        if ($sessaoId !== null && $sessaoId > 0) {
            $sessaoFoco = $sessaoModel->findById($sessaoId);
        }
        if (!$sessaoFoco && $proximaSessao && !empty($proximaSessao['id'])) {
            $sessaoFoco = $sessaoModel->findById((int) $proximaSessao['id']);
        }

        $visitantes = [];
        $cargosSessao = [];
        $eventosSessao = [];
        $lembretes = [];

        if ($sessaoFoco && !empty($sessaoFoco['id'])) {
            $visitantes = $balaustreModel->obterResumoVisitantesPorSessao((int) $sessaoFoco['id']);
            $dadosBalaustre = $this->obterDadosBalaustre($balaustreModel, (int) $sessaoFoco['id']);
            $cargosSessao = $dadosBalaustre['cargos_sessao'] ?? [];
            $eventosSessao = $this->montarEventosSessao($dadosBalaustre);
            $lembretes = $this->montarLembretes($sessaoFoco, $visitantes, $cargosSessao, $eventosSessao);
        }

        return [
            'proxima_sessao' => $proximaSessao ? $this->mapearSessao($proximaSessao) : null,
            'sessao_foco' => $sessaoFoco ? $this->mapearSessao($sessaoFoco) : null,
            'sessoes' => array_map(fn (array $sessao): array => $this->mapearSessao($sessao), $sessoes),
            'visitantes' => $visitantes,
            'cargos_sessao' => array_map(static function (array $item): array {
                return [
                    'cargo_nome' => (string) ($item['cargo_nome'] ?? $item['codigo'] ?? 'Cargo'),
                    'ocupante_nome' => (string) ($item['ocupante_nome'] ?? ''),
                    'tipo_ocupacao' => (string) ($item['tipo_ocupacao'] ?? 'regular'),
                ];
            }, $cargosSessao),
            'eventos_sessao' => $eventosSessao,
            'lembretes' => $lembretes,
        ];
    }

    private function mapearSessao(array $sessao): array
    {
        return [
            'id' => (int) ($sessao['id'] ?? 0),
            'titulo' => (string) ($sessao['titulo'] ?? ''),
            'data_hora_inicio' => (string) ($sessao['data_hora_inicio'] ?? ''),
            'status' => (string) ($sessao['status'] ?? ''),
            'tipo_sessao' => (string) ($sessao['tipo_sessao'] ?? ''),
            'grau_sessao' => (string) ($sessao['grau_sessao'] ?? ''),
            'ordem_dia' => (string) ($sessao['ordem_dia'] ?? ''),
            'resumo_publico' => (string) ($sessao['resumo_publico'] ?? ''),
        ];
    }

    private function obterDadosBalaustre(Balaustre $balaustreModel, int $sessaoId): array
    {
        $balaustre = $balaustreModel->buscarPorSessao($sessaoId);
        if (!$balaustre) {
            return [];
        }

        $dados = $balaustre['dados_capturados'] ?? null;
        if (is_string($dados)) {
            $dados = json_decode($dados, true);
        }

        return is_array($dados) ? $dados : [];
    }

    private function montarEventosSessao(array $dadosBalaustre): array
    {
        $eventos = [];
        foreach ((array) ($dadosBalaustre['eventos_realizados']['congressos'] ?? []) as $item) {
            $eventos[] = [
                'tipo' => 'congresso',
                'titulo' => (string) ($item['titulo'] ?? 'Congresso'),
                'linha' => trim((string) (($item['promotor'] ?? '') . ' ' . ($item['data'] ?? ''))),
            ];
        }
        foreach ((array) ($dadosBalaustre['eventos_realizados']['palestras'] ?? []) as $item) {
            $eventos[] = [
                'tipo' => 'palestra',
                'titulo' => (string) ($item['titulo'] ?? 'Palestra'),
                'linha' => trim((string) (($item['palestrante'] ?? '') . ' ' . ($item['data'] ?? ''))),
            ];
        }
        return $eventos;
    }

    private function montarLembretes(array $sessao, array $visitantes, array $cargosSessao, array $eventosSessao): array
    {
        $lembretes = [];
        $resumo = trim((string) ($sessao['ordem_dia'] ?? $sessao['resumo_publico'] ?? ''));
        if ($resumo !== '') {
            $lembretes[] = 'Revisar a pauta resumida antes da abertura da palavra a bem.';
        }
        if ($visitantes !== []) {
            $lembretes[] = 'Conferir a nominata dos visitantes para agradecer nominalmente.';
        }
        if (array_filter($cargosSessao, static fn (array $item): bool => (string) ($item['tipo_ocupacao'] ?? '') === 'ad_hoc')) {
            $lembretes[] = 'Validar cargos exercidos ad hoc para leitura coerente em Loja.';
        }
        if ($eventosSessao !== []) {
            $lembretes[] = 'Separar mencoes a congressos, palestras e atividades registradas no balaustre.';
        }
        if ($lembretes === []) {
            $lembretes[] = 'Manter a leitura ritual alinhada com a sessao em foco e os registros oficiais.';
        }
        return $lembretes;
    }
}
