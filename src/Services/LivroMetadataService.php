<?php

namespace App\Services;

class LivroMetadataService
{
    public function buscarPorIsbn(string $isbn): ?array
    {
        $isbn = preg_replace('/[^0-9Xx]/', '', trim($isbn)) ?? '';
        if ($isbn === '') {
            return null;
        }

        $url = 'https://www.googleapis.com/books/v1/volumes?q=isbn:' . urlencode($isbn);
        $response = @file_get_contents($url);
        if (!is_string($response) || trim($response) === '') {
            return null;
        }

        $payload = json_decode($response, true);
        if (!is_array($payload) || empty($payload['items'][0]['volumeInfo'])) {
            return null;
        }

        $info = $payload['items'][0]['volumeInfo'];
        $titulo = trim((string) ($info['title'] ?? ''));
        $autor = isset($info['authors']) && is_array($info['authors']) && $info['authors'] !== []
            ? implode(', ', $info['authors'])
            : '';
        $resumo = trim((string) ($info['description'] ?? ''));
        $capaUrl = trim((string) ($info['imageLinks']['thumbnail'] ?? ''));

        return [
            'titulo' => $titulo !== '' ? $titulo : null,
            'autor' => $autor !== '' ? $autor : null,
            'resumo' => $resumo !== '' ? $resumo : null,
            'capa_url' => $capaUrl !== '' ? $capaUrl : null,
            'isbn' => $isbn,
        ];
    }
}
