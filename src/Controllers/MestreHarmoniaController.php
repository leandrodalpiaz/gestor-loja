<?php

namespace App\Controllers;

use App\Services\HarmoniaPlaylistService;

class MestreHarmoniaController
{
    private HarmoniaPlaylistService $playlistService;

    public function __construct()
    {
        $this->playlistService = new HarmoniaPlaylistService();
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
            echo json_encode(['ok' => false, 'erro' => 'Metodo nao permitido.']);
            return;
        }

        $raw = file_get_contents('php://input');
        $decoded = is_string($raw) && trim($raw) !== '' ? json_decode($raw, true) : [];
        $nome = trim((string) (($decoded['nome'] ?? $_POST['nome'] ?? '')));

        if ($nome === '') {
            http_response_code(422);
            echo json_encode(['ok' => false, 'erro' => 'Informe o nome do irmao em exercicio.']);
            return;
        }

        $_SESSION['harmonia_operador_nome'] = $nome;

        echo json_encode([
            'ok' => true,
            'operador' => $nome,
        ], JSON_UNESCAPED_UNICODE);
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

        // Fallback exibido no campo para ajuste manual caso nenhum caminho exista.
        return 'D:\leandro_pessoal\Renascença\Mestre Harmonia LD';
    }

    public function audio(): void
    {
        $trackId = trim((string) ($_GET['id'] ?? ''));
        $trackMap = $_SESSION['harmonia_track_map'] ?? [];
        $filePath = is_array($trackMap) ? (string) ($trackMap[$trackId] ?? '') : '';

        if ($trackId === '' || $filePath === '' || !is_file($filePath)) {
            http_response_code(404);
            echo 'Arquivo de audio nao encontrado para este item.';
            return;
        }

        $extension = strtolower((string) pathinfo($filePath, PATHINFO_EXTENSION));
        if ($extension !== 'mp3') {
            http_response_code(415);
            echo 'Formato de audio nao suportado.';
            return;
        }

        $size = filesize($filePath);
        if ($size === false) {
            http_response_code(500);
            echo 'Nao foi possivel ler o audio.';
            return;
        }

        header('Content-Type: audio/mpeg');
        header('Content-Length: ' . $size);
        header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
        header('Accept-Ranges: bytes');

        readfile($filePath);
    }
}
