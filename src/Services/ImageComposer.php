<?php

namespace App\Services;

class ImageComposer
{
    private const FALLBACK_TEMPLATE = 'card_oficial_convite.png';

    public function compose(array $cardPayload): array
    {
        $templateDir = rtrim((string) ($cardPayload['template_dir'] ?? ''), '/\\');
        $templateFile = (string) ($cardPayload['template_file'] ?? '');
        $text = trim((string) ($cardPayload['mensagem'] ?? ''));
        $cacheKey = (string) ($cardPayload['cache_key'] ?? '');
        $isGold = !empty($cardPayload['gold_theme']);

        $templatePath = $templateDir . DIRECTORY_SEPARATOR . $templateFile;
        if (!is_file($templatePath)) {
            $templatePath = $templateDir . DIRECTORY_SEPARATOR . self::FALLBACK_TEMPLATE;
        }
        if (!is_file($templatePath)) {
            return ['ok' => false, 'error' => 'template_not_found'];
        }

        $targetDir = dirname(__DIR__, 2) . '/public/assets/images/efemerides_geradas';
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0775, true);
        }
        $targetPath = $targetDir . DIRECTORY_SEPARATOR . $cacheKey . '.png';
        if (is_file($targetPath)) {
            return ['ok' => true, 'path' => $targetPath, 'cached' => true];
        }

        $img = imagecreatefrompng($templatePath);
        if ($img === false) {
            return ['ok' => false, 'error' => 'template_open_failed'];
        }

        $width = imagesx($img);
        $height = imagesy($img);
        $font = dirname(__DIR__, 2) . '/public/assets/fonts/Inter-Bold.ttf';
        
        $fontSizeBase = $width * 0.045;
        $colorArr = [40, 40, 40];
        $shadowArr = null;
        $yBase = $height * 0.15;

        // Configurações específicas por template para garantir legibilidade
        if (str_contains($templateFile, 'bedrock') || str_contains($templateFile, 'eterno')) {
            $colorArr = [255, 255, 255]; // Texto branco para fundos escuros
            $shadowArr = [0, 0, 0, 80]; // Sombra para destacar
            $fontSizeBase = $width * 0.050; // Pouco maior
            $yBase = $height * 0.15;
        } elseif (str_contains($templateFile, 'kids')) {
            $colorArr = [20, 80, 160]; // Azul mais lúdico
            $fontSizeBase = $width * 0.055;
            $yBase = $height * 0.18;
        } elseif (str_contains($templateFile, 'solar') || str_contains($templateFile, 'sobrinh')) {
            $colorArr = [60, 40, 20]; // Marrom escuro / quente
            $fontSizeBase = $width * 0.048;
            $yBase = $height * 0.16;
        } elseif (str_contains($templateFile, 'sepia')) {
            $colorArr = [90, 60, 40]; // Marrom vintage
            $fontSizeBase = $width * 0.040; // Menor para caber mais texto (história)
            $yBase = $height * 0.18;
        } elseif ($isGold || str_contains($templateFile, 'grao_mestre') || str_contains($templateFile, 'elevacao') || str_contains($templateFile, 'exaltacao') || str_contains($templateFile, 'instalacao')) {
            $colorArr = [212, 175, 55]; // Dourado
            $shadowArr = [0, 0, 0, 80];
            $fontSizeBase = $width * 0.048;
            $yBase = $height * 0.15;
        } elseif (str_contains($templateFile, 'honorario') || str_contains($templateFile, 'filiacao')) {
            $colorArr = [20, 40, 80]; // Azul marinho
            $fontSizeBase = $width * 0.046;
        }

        $fontSize = (int) round($fontSizeBase); 
        $lineHeight = (int) round($fontSize * 1.5);
        $y = (int) round($yBase);
        
        $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];
        $color = imagecolorallocate($img, $colorArr[0], $colorArr[1], $colorArr[2]);
        $shadow = $shadowArr ? imagecolorallocatealpha($img, $shadowArr[0], $shadowArr[1], $shadowArr[2], $shadowArr[3]) : null;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                $y += $lineHeight;
                continue;
            }
            if (is_file($font)) {
                $box = imagettfbbox($fontSize, 0, $font, $line);
                $textWidth = abs(($box[2] ?? 0) - ($box[0] ?? 0));
                $x = (int) floor(($width - $textWidth) / 2);
                if ($shadow) {
                    imagettftext($img, $fontSize, 0, $x + 2, $y + 2, $shadow, $font, $line);
                }
                imagettftext($img, $fontSize, 0, $x, $y, $color, $font, $line);
            } else {
                $fw = imagefontwidth(5);
                $textWidth = strlen($line) * $fw;
                $x = (int) floor(($width - $textWidth) / 2);
                imagestring($img, 5, $x, max(0, $y - 14), $line, $color);
            }
            $y += $lineHeight;
        }

        imagesavealpha($img, true);
        imagepng($img, $targetPath);
        imagedestroy($img);

        return ['ok' => true, 'path' => $targetPath, 'cached' => false];
    }
}
