<?php

namespace App\Services;

use App\Models\EfemerideCardPrevia;
use App\Models\EfemerideCardCategoriaTemplate;

class EfemeridesCardService
{
    public function buildCardsForDate(string $ymd, array $registros): array
    {
        $templateDir = dirname(__DIR__, 2) . '/public/assets/images/templates/efemerides';
        $imageComposer = new ImageComposer();
        $previaModel = new EfemerideCardPrevia();
        $cards = [];

        $categoriaTemplateMap = (new EfemerideCardCategoriaTemplate())->mapa();
        foreach ($registros as $registro) {
            $card = $this->buildCardPayload($registro, $templateDir);
            $categoria = (string) ($card['categoria'] ?? '');
            if ($categoria !== '' && !empty($categoriaTemplateMap[$categoria])) {
                $card['template'] = $categoriaTemplateMap[$categoria];
                $card['template_file'] = $categoriaTemplateMap[$categoria];
                $card['template_slug'] = $categoriaTemplateMap[$categoria];
                $card['gold_theme'] = $this->isGoldTheme($categoriaTemplateMap[$categoria]);
                $card['cache_key'] = sha1(json_encode([$card['template'], $card['mensagem'], $card['ocultar_idade']], JSON_UNESCAPED_UNICODE) ?: uniqid('', true));
                $card['card_hash'] = $card['cache_key'];
            }
            $compose = $imageComposer->compose($card);
            if (!empty($compose['ok'])) {
                $card['card_path'] = (string) $compose['path'];
                $card['image_url'] = '/assets/images/efemerides_geradas/' . basename((string) $compose['path']);
            }
            $previaModel->upsert($ymd, (int) ($registro['id'] ?? 0), $card);
            $cards[] = $card;
        }

        return $cards;
    }

    private function buildCardPayload(array $registro, string $templateDir): array
    {
        $categoria = strtolower(trim((string) ($registro['vinculo'] ?? $registro['tipo'] ?? '')));
        $idade = $this->calcularIdade((string) ($registro['data_evento'] ?? ''));
        $ocultar = $this->defaultOcultarIdade($categoria, $idade);
        $template = $this->resolverTemplate($categoria, $idade, (string) ($registro['tipo'] ?? ''));
        $mensagem = $this->resolverMensagem($registro, $idade, $ocultar);
        $goldTheme = $this->isGoldTheme($template);
        $hash = sha1(json_encode([$template, $mensagem, $ocultar], JSON_UNESCAPED_UNICODE) ?: uniqid('', true));

        return [
            'registro_id' => (int) ($registro['id'] ?? 0),
            'categoria' => $categoria,
            'template' => $template,
            'template_file' => $template,
            'template_slug' => $template,
            'template_dir' => $templateDir,
            'titulo' => trim((string) ($registro['nome'] ?? '')),
            'mensagem' => $mensagem,
            'idade_exibida' => !$ocultar && $idade !== null,
            'ocultar_idade' => $ocultar || $idade === null,
            'cache_key' => $hash,
            'card_hash' => $hash,
            'gold_theme' => $goldTheme,
            'texto_custom_card' => trim((string) ($registro['mensagem_custom'] ?? '')) ?: null,
            'aprovado' => false,
        ];
    }

    public function buildCardForRegistro(string $ymd, array $registro, ?bool $ocultarIdadeOverride = null, ?string $textoCustomOverride = null, ?string $templateOverride = null): array
    {
        $templateDir = dirname(__DIR__, 2) . '/public/assets/images/templates/efemerides';
        $card = $this->buildCardPayload($registro, $templateDir);
        if ($ocultarIdadeOverride !== null) {
            $card['ocultar_idade'] = $ocultarIdadeOverride;
        }
        if ($textoCustomOverride !== null && trim($textoCustomOverride) !== '') {
            $card['mensagem'] = trim($textoCustomOverride);
            $card['texto_custom_card'] = trim($textoCustomOverride);
        }
        if ($templateOverride !== null && trim($templateOverride) !== '') {
            $card['template'] = trim($templateOverride);
            $card['template_file'] = trim($templateOverride);
            $card['template_slug'] = trim($templateOverride);
            $card['gold_theme'] = $this->isGoldTheme(trim($templateOverride));
        }
        $card['idade_exibida'] = !$card['ocultar_idade'] && !empty($card['idade_exibida']);
        $card['cache_key'] = sha1(json_encode([$card['template'], $card['mensagem'], $card['ocultar_idade']], JSON_UNESCAPED_UNICODE) ?: uniqid('', true));
        $card['card_hash'] = $card['cache_key'];
        $compose = (new ImageComposer())->compose($card);
        if (!empty($compose['ok'])) {
            $card['card_path'] = (string) $compose['path'];
            $card['image_url'] = '/assets/images/efemerides_geradas/' . basename((string) $compose['path']);
        }
        (new EfemerideCardPrevia())->upsert($ymd, (int) ($registro['id'] ?? 0), $card);
        return $card;
    }

    private function calcularIdade(string $data): ?int
    {
        if ($data === '' || strlen($data) < 4) {
            return null;
        }
        try {
            $date = new \DateTimeImmutable($data);
            return (int) $date->diff(new \DateTimeImmutable('today'))->y;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function defaultOcultarIdade(string $categoria, ?int $idade): bool
    {
        if ($idade === null) {
            return true;
        }
        if (str_contains($categoria, 'esposa') || str_contains($categoria, 'cunhada')) {
            return true;
        }
        if (str_contains($categoria, 'filha') || str_contains($categoria, 'sobrinha')) {
            return $idade >= 16;
        }
        return false;
    }

    private function resolverMensagem(array $registro, ?int $idade, bool $ocultar): string
    {
        $nome = trim((string) ($registro['nome'] ?? ''));
        if ($ocultar || $idade === null) {
            return $nome . "\nParabéns pelo seu dia";
        }
        return $nome . "\nFeliz {$idade} anos";
    }

    private function resolverTemplate(string $categoria, ?int $idade, string $tipo): string
    {
        $tipoNorm = strtolower($tipo);
        if (str_contains($tipoNorm, 'inicia')) return 'card_grau_iniciacao.png';
        if (str_contains($tipoNorm, 'eleva')) return 'card_grau_elevacao.png';
        if (str_contains($tipoNorm, 'exalta')) return 'card_grau_exaltacao.png';
        if (str_contains($tipoNorm, 'instala')) return 'card_grau_instalacao.png';
        if (str_contains($categoria, 'irma')) return 'card_irmao_bedrock.png';
        if (str_contains($categoria, 'esposa') || str_contains($categoria, 'cunhada')) return 'card_cunhada_solar.png';
        if (str_contains($categoria, 'filh') || str_contains($categoria, 'sobrinh')) {
            if (($idade ?? 0) < 10) return 'card_familia_kids.png';
            if (($idade ?? 0) <= 25) return 'card_sobrinho_jovem.png';
            return str_contains($categoria, 'filha') || str_contains($categoria, 'sobrinha') ? 'card_sobrinha_adulta.png' : 'card_sobrinho_adulto.png';
        }
        return 'card_oficial_convite.png';
    }

    private function isGoldTheme(string $template): bool
    {
        foreach (['pop', 'elevacao', 'exaltacao', 'instalacao', 'grao_mestre'] as $token) {
            if (str_contains($template, $token)) return true;
        }
        return false;
    }
}
