<?php

namespace App\Controllers;

use App\Models\HarmoniaOperacao;
use App\Services\HarmoniaPlaylistService;

class MestreHarmoniaController
{
    private HarmoniaPlaylistService $playlistService;
    private HarmoniaOperacao $operacaoModel;

    public function __construct()
    {
        $this->playlistService = new HarmoniaPlaylistService();
        $this->operacaoModel = new HarmoniaOperacao();
    }

    public function index(): void
    {
        $defaultPath = $this->resolveDefaultBasePath();
        $basePathInput = trim((string) ($_GET['base_path'] ?? $defaultPath));
        $sessionPathInput = trim((string) ($_GET['sessao_path'] ?? ''));
        $operadorEmExercicio = trim((string) ($_SESSION['harmonia_operador_nome'] ?? ''));

        $payload = $this->playlistService->scanBase($basePathInput, $sessionPathInput !== '' ? $sessionPathInput : null);
        $_SESSION['harmonia_track_map'] = $payload['track_map'] ?? [];

        require_once __DIR__ . '/../Views/mestre_harmonia/index.php';
    }

    public function scan(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $basePathInput = trim((string) ($_GET['base_path'] ?? ''));
        $sessionPathInput = trim((string) ($_GET['sessao_path'] ?? ''));
        if ($basePathInput === '') {
            http_response_code(422);
            echo json_encode([
                'ok' => false,
                'erro' => 'Informe o caminho base da playlist.',
            ]);
            return;
        }

        $payload = $this->playlistService->scanBase($basePathInput, $sessionPathInput !== '' ? $sessionPathInput : null);
        $_SESSION['harmonia_track_map'] = $payload['track_map'] ?? [];

        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }

    public function salvarOperador(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'erro' => 'Método não permitido.']);
            return;
        }

        $raw = file_get_contents('php://input');
        $decoded = is_string($raw) && trim($raw) !== '' ? json_decode($raw, true) : [];
        $nome = trim((string) (($decoded['nome'] ?? $_POST['nome'] ?? '')));

        if ($nome === '') {
            http_response_code(422);
            echo json_encode(['ok' => false, 'erro' => 'Informe o nome do irmão em exercício.']);
            return;
        }

        $_SESSION['harmonia_operador_nome'] = $nome;

        echo json_encode([
            'ok' => true,
            'operador' => $nome,
        ], JSON_UNESCAPED_UNICODE);
    }

    public function montarPayloadMiniapp(?string $sessaoPath = null): array
    {
        $payload = $this->playlistService->scanBase($this->resolveDefaultBasePath(), $sessaoPath);
        $sessaoSelecionada = $payload['selected_session'] ?? null;
        $tracks = (array) ($payload['selected_tracks'] ?? []);

        if (!$sessaoSelecionada || $tracks === []) {
            return [
                'ok' => false,
                'erro' => $payload['erro'] ?? 'Nenhuma playlist disponível para o cargo.',
                'sessoes' => [],
            ];
        }

        $estado = $this->resolverEstadoOperacional($sessaoSelecionada, $tracks);
        $faixaAtual = $this->encontrarFaixa($tracks, (string) ($estado['faixa_atual_id'] ?? '')) ?? $tracks[0];
        $indiceAtual = $this->encontrarIndiceFaixa($tracks, (string) ($faixaAtual['id'] ?? ''));
        $proximaFaixa = $indiceAtual >= 0 ? ($tracks[$indiceAtual + 1] ?? null) : null;

        return [
            'ok' => true,
            'base_path' => $payload['base_path'] ?? null,
            'sessao_foco' => [
                'nome' => (string) ($sessaoSelecionada['name'] ?? ''),
                'path' => (string) ($sessaoSelecionada['path'] ?? ''),
                'total_tracks' => (int) ($sessaoSelecionada['total_tracks'] ?? 0),
                'summary' => $sessaoSelecionada['summary'] ?? [],
            ],
            'sessoes' => array_map(static function (array $sessao): array {
                return [
                    'nome' => (string) ($sessao['name'] ?? ''),
                    'path' => (string) ($sessao['path'] ?? ''),
                    'total_tracks' => (int) ($sessao['total_tracks'] ?? 0),
                ];
            }, (array) ($payload['sessions'] ?? [])),
            'estado' => [
                'operador_nome' => (string) ($estado['operador_nome'] ?? ''),
                'status_player' => (string) ($estado['status_player'] ?? 'parado'),
                'volume_percent' => (int) ($estado['volume_percent'] ?? 100),
                'auto_proxima' => !empty($estado['auto_proxima']),
                'updated_at' => (string) ($estado['updated_at'] ?? ''),
            ],
            'faixa_atual' => $this->mapearFaixa($faixaAtual),
            'proxima_faixa' => $proximaFaixa ? $this->mapearFaixa($proximaFaixa) : null,
            'alternativas' => array_map(fn (array $faixa): array => $this->mapearFaixa($faixa), $this->listarAlternativas($tracks, $faixaAtual)),
            'faixas' => array_map(fn (array $faixa): array => $this->mapearFaixa($faixa), $tracks),
        ];
    }

    public function salvarOperadorMiniapp(array $dados): array
    {
        $sessaoPath = trim((string) ($dados['sessao_path'] ?? ''));
        $nome = trim((string) ($dados['nome'] ?? ''));

        if ($sessaoPath === '' || $nome === '') {
            return ['ok' => false, 'erro' => 'Informe sessão e operador para continuar.'];
        }

        $payload = $this->playlistService->scanBase($this->resolveDefaultBasePath(), $sessaoPath);
        $sessaoSelecionada = $payload['selected_session'] ?? null;
        $tracks = (array) ($payload['selected_tracks'] ?? []);
        if (!$sessaoSelecionada || $tracks === []) {
            return ['ok' => false, 'erro' => $payload['erro'] ?? 'Sessão musical não encontrada.'];
        }

        $estado = $this->resolverEstadoOperacional($sessaoSelecionada, $tracks);
        $estado['operador_nome'] = $nome;
        $this->persistirEstado($sessaoSelecionada, $tracks, $estado);
        $_SESSION['harmonia_operador_nome'] = $nome;

        return ['ok' => true, 'operador' => $nome];
    }

    public function executarAcaoMiniapp(string $acao, array $dados): array
    {
        $sessaoPath = trim((string) ($dados['sessao_path'] ?? ''));
        $payload = $this->playlistService->scanBase($this->resolveDefaultBasePath(), $sessaoPath !== '' ? $sessaoPath : null);
        $sessaoSelecionada = $payload['selected_session'] ?? null;
        $tracks = (array) ($payload['selected_tracks'] ?? []);

        if (!$sessaoSelecionada || $tracks === []) {
            return ['ok' => false, 'erro' => $payload['erro'] ?? 'Sessão musical não encontrada.'];
        }

        $estado = $this->resolverEstadoOperacional($sessaoSelecionada, $tracks);
        $faixaAtual = $this->encontrarFaixa($tracks, (string) ($estado['faixa_atual_id'] ?? '')) ?? $tracks[0];
        $indiceAtual = max(0, $this->encontrarIndiceFaixa($tracks, (string) ($faixaAtual['id'] ?? '')));

        switch ($acao) {
            case 'selecionar_sessao':
                $estado['status_player'] = 'pronto';
                break;
            case 'selecionar_faixa':
                $faixaId = trim((string) ($dados['faixa_id'] ?? ''));
                $novaFaixa = $this->encontrarFaixa($tracks, $faixaId);
                if (!$novaFaixa) {
                    return ['ok' => false, 'erro' => 'Faixa não encontrada na sessão selecionada.'];
                }
                $faixaAtual = $novaFaixa;
                $estado['status_player'] = 'pronto';
                break;
            case 'anterior':
                $faixaAtual = $tracks[max(0, $indiceAtual - 1)];
                $estado['status_player'] = 'pronto';
                break;
            case 'proxima':
                $faixaAtual = $tracks[min(count($tracks) - 1, $indiceAtual + 1)];
                $estado['status_player'] = 'pronto';
                break;
            case 'iniciar':
                $estado['status_player'] = 'tocando';
                break;
            case 'pausar':
                $estado['status_player'] = 'pausado';
                break;
            case 'parar':
                $estado['status_player'] = 'parado';
                break;
            case 'silencio':
                $estado['status_player'] = 'silencio';
                $estado['volume_percent'] = 0;
                break;
            case 'toggle_auto':
                $estado['auto_proxima'] = empty($estado['auto_proxima']);
                break;
            case 'volume_up':
                $estado['volume_percent'] = min(100, ((int) ($estado['volume_percent'] ?? 100)) + 10);
                break;
            case 'volume_down':
                $estado['volume_percent'] = max(0, ((int) ($estado['volume_percent'] ?? 100)) - 10);
                break;
            default:
                return ['ok' => false, 'erro' => 'Ação do player não reconhecida.'];
        }

        $estado['faixa_atual_id'] = (string) ($faixaAtual['id'] ?? '');
        $estado['faixa_atual_codigo'] = (string) ($faixaAtual['code'] ?? '');
        $estado['faixa_atual_titulo'] = (string) ($faixaAtual['title'] ?? $faixaAtual['filename'] ?? '');
        $this->persistirEstado($sessaoSelecionada, $tracks, $estado);

        return [
            'ok' => true,
            'dados' => $this->montarPayloadMiniapp((string) ($sessaoSelecionada['path'] ?? '')),
        ];
    }

    private function resolveDefaultBasePath(): string
    {
        $candidates = [
            trim((string) ($_ENV['MESTRE_HARMONIA_BASE_PATH'] ?? '')),
            'D:\leandro_pessoal\Renascença\Mestre Harmonia LD',
            'D:\leandro_pessoal\Renascenca\Mestre Harmonia LD',
        ];

        foreach ($candidates as $candidate) {
            if ($candidate === '') {
                continue;
            }
            $candidate = trim($candidate, "\"' ");
            if (is_dir($candidate)) {
                return $candidate;
            }
        }

        // Valor padrão exibido no formulário quando nenhum diretório válido é detectado.
        // O operador ainda pode ajustar manualmente antes de iniciar a sessão.
        return 'D:\leandro_pessoal\Renascença\Mestre Harmonia LD';
    }

    public function audio(): void
    {
        $trackId = trim((string) ($_GET['id'] ?? ''));
        $trackMap = $_SESSION['harmonia_track_map'] ?? [];
        $filePath = is_array($trackMap) ? (string) ($trackMap[$trackId] ?? '') : '';

        if ($trackId === '' || $filePath === '' || !is_file($filePath)) {
            http_response_code(404);
            echo 'Arquivo de áudio não encontrado para este item.';
            return;
        }

        $extension = strtolower((string) pathinfo($filePath, PATHINFO_EXTENSION));
        if ($extension !== 'mp3') {
            http_response_code(415);
            echo 'Formato de áudio não suportado.';
            return;
        }

        $size = filesize($filePath);
        if ($size === false) {
            http_response_code(500);
            echo 'Não foi possível ler o áudio.';
            return;
        }

        header('Content-Type: audio/mpeg');
        header('Content-Length: ' . $size);
        header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
        header('Accept-Ranges: bytes');

        readfile($filePath);
    }

    private function resolverEstadoOperacional(array $sessaoSelecionada, array $tracks): array
    {
        $sessaoPath = (string) ($sessaoSelecionada['path'] ?? '');
        $estado = $sessaoPath !== '' ? $this->operacaoModel->obterPorSessao($sessaoPath) : null;
        if ($estado) {
            return $estado;
        }

        $primeiraFaixa = $tracks[0] ?? [];

        return [
            'sessao_path' => $sessaoPath,
            'sessao_nome' => (string) ($sessaoSelecionada['name'] ?? ''),
            'operador_nome' => (string) ($_SESSION['harmonia_operador_nome'] ?? ''),
            'faixa_atual_id' => (string) ($primeiraFaixa['id'] ?? ''),
            'faixa_atual_codigo' => (string) ($primeiraFaixa['code'] ?? ''),
            'faixa_atual_titulo' => (string) ($primeiraFaixa['title'] ?? ''),
            'status_player' => 'pronto',
            'volume_percent' => 100,
            'auto_proxima' => false,
            'updated_at' => '',
        ];
    }

    private function persistirEstado(array $sessaoSelecionada, array $tracks, array $estado): void
    {
        $sessaoPath = (string) ($sessaoSelecionada['path'] ?? '');
        if ($sessaoPath === '') {
            return;
        }

        if (empty($estado['faixa_atual_id']) && $tracks !== []) {
            $estado['faixa_atual_id'] = (string) ($tracks[0]['id'] ?? '');
            $estado['faixa_atual_codigo'] = (string) ($tracks[0]['code'] ?? '');
            $estado['faixa_atual_titulo'] = (string) ($tracks[0]['title'] ?? '');
        }

        $this->operacaoModel->salvarEstado(
            $sessaoPath,
            (string) ($sessaoSelecionada['name'] ?? 'Sessao musical'),
            $estado
        );
    }

    private function encontrarFaixa(array $tracks, string $faixaId): ?array
    {
        foreach ($tracks as $track) {
            if ((string) ($track['id'] ?? '') === $faixaId) {
                return $track;
            }
        }

        return null;
    }

    private function encontrarIndiceFaixa(array $tracks, string $faixaId): int
    {
        foreach ($tracks as $index => $track) {
            if ((string) ($track['id'] ?? '') === $faixaId) {
                return $index;
            }
        }

        return -1;
    }

    private function listarAlternativas(array $tracks, array $faixaAtual): array
    {
        $stageKey = (string) ($faixaAtual['stage_key'] ?? '');
        if ($stageKey === '') {
            return [];
        }

        return array_values(array_filter($tracks, static function (array $track) use ($stageKey, $faixaAtual): bool {
            return (string) ($track['stage_key'] ?? '') === $stageKey
                && (string) ($track['id'] ?? '') !== (string) ($faixaAtual['id'] ?? '');
        }));
    }

    private function mapearFaixa(array $faixa): array
    {
        return [
            'id' => (string) ($faixa['id'] ?? ''),
            'code' => (string) ($faixa['code'] ?? ''),
            'title' => (string) ($faixa['title'] ?? $faixa['filename'] ?? ''),
            'type' => (string) ($faixa['type'] ?? 'principal'),
            'phase' => (string) ($faixa['phase'] ?? ''),
            'stage_key' => (string) ($faixa['stage_key'] ?? ''),
        ];
    }
}
