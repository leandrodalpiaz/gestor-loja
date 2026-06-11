<?php

namespace App\Core\Http;

use App\Controllers\MestreHarmoniaController;
use App\Core\Http\JsonResponse;
use App\Core\Http\RequestBody;

class HarmoniaApiRoutes
{
    public static function dispatch(
        string $requestUri,
        string $method,
        array $session,
        callable $requireHarmoniaApiAccess
    ): bool {
        if (!str_starts_with($requestUri, '/api/harmonia/')) {
            return false;
        }

        // Intercept token from query parameter for audio tag requests
        if (empty($_SERVER['HTTP_AUTHORIZATION']) && isset($_GET['token'])) {
            $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $_GET['token'];
        }

        // For audio endpoint, we serve binary, otherwise we serve json
        if (str_starts_with($requestUri, '/api/harmonia/audio')) {
            // Headers are sent inside the audio action block below
        } else {
            header('Content-Type: application/json; charset=utf-8');
        }

        // Run authentication
        $requireHarmoniaApiAccess();

        $controller = new MestreHarmoniaController();

        // 1. GET /api/harmonia/sessoes
        if (($requestUri === '/api/harmonia/sessoes' || str_starts_with($requestUri, '/api/harmonia/sessoes?')) && $method === 'GET') {
            $sessaoPath = trim((string) ($_GET['sessao_path'] ?? ''));
            $payload = $controller->montarPayloadMiniapp($sessaoPath !== '' ? $sessaoPath : null);
            JsonResponse::send($payload);
            return true;
        }

        // 2. POST /api/harmonia/acao
        if ($requestUri === '/api/harmonia/acao' && $method === 'POST') {
            $body = RequestBody::json();
            $acao = trim((string) ($body['acao'] ?? ''));
            
            if ($acao === '') {
                JsonResponse::send(['ok' => false, 'erro' => 'Ação não informada.']);
                return true;
            }

            if ($acao === 'salvar_operador') {
                $payload = $controller->salvarOperadorMiniapp($body);
                JsonResponse::send($payload);
                return true;
            }

            $payload = $controller->executarAcaoMiniapp($acao, $body);
            JsonResponse::send($payload);
            return true;
        }

        // 3. GET /api/harmonia/audio
        if (str_starts_with($requestUri, '/api/harmonia/audio') && $method === 'GET') {
            $file = trim((string) ($_GET['file'] ?? ''));
            if ($file === '') {
                http_response_code(400);
                echo 'Arquivo não especificado.';
                return true;
            }

            // Sanitização contra path traversal
            if (str_contains($file, '..') || str_contains($file, './') || str_contains($file, '.\\')) {
                http_response_code(400);
                echo 'Caminho de arquivo inválido.';
                return true;
            }

            $basePath = $controller->resolveDefaultBasePath();
            // Substitui barras invertidas por normais para facilitar manipulação
            $cleanFile = str_replace('\\', '/', $file);
            $absolutePath = rtrim($basePath, '/\\') . '/' . ltrim($cleanFile, '/');

            // Verifica se o arquivo existe e é um arquivo válido
            if (!is_file($absolutePath)) {
                http_response_code(404);
                echo 'Arquivo de áudio não encontrado.';
                return true;
            }

            // Verifica se o arquivo está dentro do diretório base (prevenção contra path traversal)
            $realBasePath = realpath($basePath);
            $realFilePath = realpath($absolutePath);
            if ($realBasePath === false || $realFilePath === false || !str_starts_with(strtolower($realFilePath), strtolower($realBasePath))) {
                http_response_code(403);
                echo 'Acesso negado.';
                return true;
            }

            $extension = strtolower((string) pathinfo($realFilePath, PATHINFO_EXTENSION));
            if ($extension !== 'mp3') {
                http_response_code(415);
                echo 'Formato de áudio não suportado.';
                return true;
            }

            $size = filesize($realFilePath);
            if ($size === false) {
                http_response_code(500);
                echo 'Não foi possível ler o arquivo.';
                return true;
            }

            header('Content-Type: audio/mpeg');
            header('Content-Length: ' . $size);
            header('Content-Disposition: inline; filename="' . basename($realFilePath) . '"');
            header('Accept-Ranges: bytes');

            readfile($realFilePath);
            return true;
        }

        return false;
    }
}
