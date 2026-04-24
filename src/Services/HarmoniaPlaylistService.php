<?php

namespace App\Services;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class HarmoniaPlaylistService
{
    private const TRANSITION_KEYWORDS = [
        'apos',
        'conferencia',
        'espera',
        'aguardando',
        'retorno',
        'preparacao',
        'transicao',
    ];

    public function scanBase(string $basePath, ?string $preferredSessionPath = null): array
    {
        $resolvedBase = $this->resolveDirectory($basePath);
        if ($resolvedBase === null) {
            return [
                'ok' => false,
                'erro' => 'Diretório base inválido ou inacessível.',
                'base_path' => $basePath,
                'sessions' => [],
                'selected_session' => null,
                'track_map' => [],
            ];
        }

        $sessionDirs = $this->discoverSessionDirectories($resolvedBase);
        if ($sessionDirs === []) {
            return [
                'ok' => false,
                'erro' => 'Nenhum arquivo MP3 encontrado no diretório informado.',
                'base_path' => $resolvedBase,
                'sessions' => [],
                'selected_session' => null,
                'track_map' => [],
            ];
        }

        $sessions = [];
        $trackMap = [];
        foreach ($sessionDirs as $sessionDir) {
            $tracks = $this->buildTracks($sessionDir);
            if ($tracks === []) {
                continue;
            }

            foreach ($tracks as $track) {
                $trackMap[$track['id']] = $track['path'];
            }

            $sessions[] = [
                'name' => $this->guessSessionName($sessionDir),
                'path' => $sessionDir,
                'total_tracks' => count($tracks),
                'summary' => $this->buildSessionSummary($tracks),
                'tracks' => $tracks,
            ];
        }

        if ($sessions === []) {
            return [
                'ok' => false,
                'erro' => 'As pastas localizadas não possuem MP3 válidos.',
                'base_path' => $resolvedBase,
                'sessions' => [],
                'selected_session' => null,
                'track_map' => [],
            ];
        }

        $selected = $sessions[0];
        if (is_string($preferredSessionPath) && trim($preferredSessionPath) !== '') {
            $preferred = $this->resolveDirectory($preferredSessionPath);
            if ($preferred !== null) {
                foreach ($sessions as $session) {
                    if ($this->pathsEqual($session['path'], $preferred)) {
                        $selected = $session;
                        break;
                    }
                }
            }
        }

        return [
            'ok' => true,
            'erro' => null,
            'base_path' => $resolvedBase,
            'sessions' => $sessions,
            'selected_session' => [
                'name' => $selected['name'],
                'path' => $selected['path'],
                'total_tracks' => $selected['total_tracks'],
                'summary' => $selected['summary'],
            ],
            'selected_tracks' => $selected['tracks'],
            'track_map' => $trackMap,
        ];
    }

    private function buildSessionSummary(array $tracks): array
    {
        $summary = [
            'principal' => 0,
            'transicao' => 0,
            'extra' => 0,
        ];

        foreach ($tracks as $track) {
            $type = (string) ($track['type'] ?? 'principal');
            if (!array_key_exists($type, $summary)) {
                continue;
            }
            $summary[$type]++;
        }

        return $summary;
    }

    private function discoverSessionDirectories(string $basePath): array
    {
        $directories = [];
        $baseHasMp3 = $this->directoryHasMp3InCurrentLevel($basePath);

        // Se a pasta-base já tiver MP3 (ex.: sessão Magna), ela deve ser tratada
        // como sessão principal, sem exigir subpasta adicional.
        if ($baseHasMp3) {
            $directories[] = $basePath;
        }

        $iterator = new FilesystemIterator($basePath, FilesystemIterator::SKIP_DOTS);
        foreach ($iterator as $item) {
            if (!$item->isDir()) {
                continue;
            }

            $dirPath = $item->getPathname();
            if ($this->directoryHasMp3($dirPath)) {
                $directories[] = $dirPath;
            }
        }

        $directories = array_values(array_unique($directories));
        usort($directories, static function (string $left, string $right) use ($basePath): int {
            if (strcasecmp($left, $basePath) === 0) {
                return -1;
            }
            if (strcasecmp($right, $basePath) === 0) {
                return 1;
            }
            return strnatcasecmp($left, $right);
        });
        return $directories;
    }

    private function directoryHasMp3InCurrentLevel(string $directory): bool
    {
        $iterator = new FilesystemIterator($directory, FilesystemIterator::SKIP_DOTS);
        foreach ($iterator as $item) {
            if (!$item->isFile()) {
                continue;
            }
            if (strtolower((string) $item->getExtension()) === 'mp3') {
                return true;
            }
        }
        return false;
    }

    private function directoryHasMp3(string $directory): bool
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'mp3') {
                return true;
            }
        }

        return false;
    }

    private function buildTracks(string $sessionDir): array
    {
        $tracks = [];
        $sequence = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sessionDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || strtolower($file->getExtension()) !== 'mp3') {
                continue;
            }

            $fullPath = $file->getPathname();
            $filename = pathinfo($fullPath, PATHINFO_FILENAME);
            $parsed = $this->parseTrackName($filename);
            $trackId = sha1(strtolower($fullPath));

            $tracks[] = [
                'sequence' => $sequence++,
                'id' => $trackId,
                'path' => $fullPath,
                'filename' => $file->getBasename(),
                'title' => $parsed['title'],
                'order' => $parsed['order'],
                'code' => $parsed['code'],
                'type' => $parsed['type'],
                'phase' => $parsed['phase'],
                'stage_key' => $parsed['stage_key'],
                'is_transition' => $parsed['type'] === 'transicao',
                'is_extra' => $parsed['type'] === 'extra',
            ];
        }

        usort($tracks, static function (array $a, array $b): int {
            $orderA = (int) ($a['order'] ?? 999999);
            $orderB = (int) ($b['order'] ?? 999999);
            if ($orderA !== $orderB) {
                return $orderA <=> $orderB;
            }

            return ((int) ($a['sequence'] ?? 0)) <=> ((int) ($b['sequence'] ?? 0));
        });

        return array_values($tracks);
    }

    private function parseTrackName(string $filename): array
    {
        $cleanName = trim($filename);
        $order = 999999;
        $code = '';
        $body = $cleanName;
        $sessionPrefix = '';

        if (preg_match('/^\s*(\d{1,4})\s*[-_]\s*(.+)$/u', $cleanName, $matches) === 1) {
            $order = (int) $matches[1];
            $code = $matches[1];
            $body = trim((string) $matches[2]);
        } elseif (preg_match('/^\s*([^-]+?)\s*-\s*(\d{1,4})\s*-\s*(.+)$/u', $cleanName, $matches) === 1) {
            // Ex.: "INICIACAO -10 - ENTRADA - Musica..." (prefixo de sessão + ordem + faixa)
            $sessionPrefix = trim((string) $matches[1]);
            $order = (int) $matches[2];
            $code = $matches[2];
            $body = trim((string) $matches[3]);
        } elseif (preg_match('/(?:^|[^0-9])(\d{1,4})\s*[-_]\s*(.+)$/u', $cleanName, $matches) === 1) {
            // Fallback para nomes menos padronizados com número no meio.
            $order = (int) $matches[1];
            $code = $matches[1];
            $body = trim((string) $matches[2]);
        }

        $normalized = $this->normalizeForMatch($body);
        $isExtra = str_contains($normalized, 'extra');
        $isTransition = !$isExtra && $this->containsTransitionKeyword($normalized);

        $phase = $this->extractPhaseLabel($body, $sessionPrefix);
        $stageKey = $this->normalizeForMatch($phase !== '' ? $phase : $body);
        if ($stageKey === '') {
            $stageKey = $this->normalizeForMatch($cleanName);
        }

        return [
            'title' => $body !== '' ? $body : $cleanName,
            'order' => $order,
            'code' => $code,
            'type' => $isExtra ? 'extra' : ($isTransition ? 'transicao' : 'principal'),
            'phase' => $phase !== '' ? $phase : 'Etapa livre',
            'stage_key' => $stageKey,
        ];
    }

    private function extractPhaseLabel(string $body, string $sessionPrefix = ''): string
    {
        $chunks = preg_split('/\s*-\s*/u', $body) ?: [];
        $candidate = trim((string) ($chunks[0] ?? ''));
        if ($candidate === '') {
            return '';
        }

        if (preg_match('/^[A-Z]{2}_/u', $candidate) === 1) {
            $candidate = substr($candidate, 3);
        }

        if ($sessionPrefix !== '') {
            $prefixNorm = $this->normalizeForMatch($sessionPrefix);
            $candidateNorm = $this->normalizeForMatch($candidate);
            if ($prefixNorm !== '' && $candidateNorm === $prefixNorm && isset($chunks[1])) {
                $candidate = trim((string) $chunks[1]);
            }
        }

        $candidate = str_replace('_', ' ', $candidate);
        $candidate = preg_replace('/\s+/', ' ', $candidate) ?? $candidate;
        return trim($candidate);
    }

    private function containsTransitionKeyword(string $normalizedText): bool
    {
        foreach (self::TRANSITION_KEYWORDS as $keyword) {
            if (str_contains($normalizedText, $keyword)) {
                return true;
            }
        }
        return false;
    }

    private function normalizeForMatch(string $value): string
    {
        $value = strtolower(trim($value));
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? '';
        return trim($value);
    }

    private function guessSessionName(string $path): string
    {
        return trim((string) basename($path));
    }

    private function resolveDirectory(string $path): ?string
    {
        $path = trim($path);
        if ($path === '') {
            return null;
        }

        $path = trim($path, "\"' ");
        $resolved = realpath($path);
        if ($resolved === false || !is_dir($resolved)) {
            return null;
        }

        return $resolved;
    }

    private function pathsEqual(string $left, string $right): bool
    {
        return strtolower(str_replace('/', '\\', $left)) === strtolower(str_replace('/', '\\', $right));
    }
}
