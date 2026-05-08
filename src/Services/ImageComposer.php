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
        $font = dirname(__DIR__, 2) . '/public/assets/fonts/Inter-Bold.ttf';
        $fontSize = 24;
        $y = 150;
        $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];
        $color = $isGold ? imagecolorallocate($img, 255, 215, 0) : imagecolorallocate($img, 26, 26, 26);
        $shadow = imagecolorallocatealpha($img, 0, 0, 0, 60);

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                $y += 32;
                continue;
            }
            if (is_file($font)) {
                $box = imagettfbbox($fontSize, 0, $font, $line);
                $textWidth = abs(($box[2] ?? 0) - ($box[0] ?? 0));
                $x = (int) floor(($width - $textWidth) / 2);
                if ($isGold) {
                    imagettftext($img, $fontSize, 0, $x + 2, $y + 2, $shadow, $font, $line);
                }
                imagettftext($img, $fontSize, 0, $x, $y, $color, $font, $line);
            } else {
                $fw = imagefontwidth(5);
                $textWidth = strlen($line) * $fw;
                $x = (int) floor(($width - $textWidth) / 2);
                imagestring($img, 5, $x, max(0, $y - 14), $line, $color);
            }
            $y += 34;
        }

        imagesavealpha($img, true);
        imagepng($img, $targetPath);
        imagedestroy($img);

        return ['ok' => true, 'path' => $targetPath, 'cached' => false];
    }
}
