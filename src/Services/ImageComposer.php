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
        $fontRaw = dirname(__DIR__, 2) . '/public/assets/fonte.ttf';
        $font = realpath($fontRaw) ?: $fontRaw;

        $colorArr = [40, 40, 40];
        $shadowArr = null;
        $fontSizeBase = $width * 0.046;
        $maxFontMultiplier = 1.40;
        $minFontMultiplier = 0.54;
        $paddingX = (int) round($width * 0.09);
        $textTop = (int) round($height * 0.12);
        $textBottom = (int) round($height * 0.58);
        $lineSpacingFactor = 1.36;
        $alignLeft = true;

        if (str_contains($templateFile, 'bedrock') || str_contains($templateFile, 'eterno')) {
            $colorArr = [255, 255, 255];
            $shadowArr = [0, 0, 0, 80];
            $fontSizeBase = $width * 0.050;
            $textTop = (int) round($height * 0.11);
            $textBottom = (int) round($height * 0.57);
        } elseif (str_contains($templateFile, 'kids')) {
            $colorArr = [20, 80, 160];
            $fontSizeBase = $width * 0.055;
            $textTop = (int) round($height * 0.13);
            $textBottom = (int) round($height * 0.60);
        } elseif (str_contains($templateFile, 'solar') || str_contains($templateFile, 'sobrinh')) {
            $colorArr = [60, 40, 20];
            $fontSizeBase = $width * 0.048;
            $textTop = (int) round($height * 0.12);
            $textBottom = (int) round($height * 0.58);
        } elseif (str_contains($templateFile, 'sepia')) {
            $colorArr = [90, 60, 40];
            $fontSizeBase = $width * 0.056;
            $paddingX = (int) round($width * 0.07);
            $textTop = (int) round($height * 0.08);
            $textBottom = (int) round($height * 0.70);
            $lineSpacingFactor = 1.22;
            $maxFontMultiplier = 1.85;
            $minFontMultiplier = 0.62;
            $alignLeft = true;
        } elseif ($isGold || str_contains($templateFile, 'grao_mestre') || str_contains($templateFile, 'elevacao') || str_contains($templateFile, 'exaltacao') || str_contains($templateFile, 'instalacao')) {
            $colorArr = [212, 175, 55];
            $shadowArr = [0, 0, 0, 80];
            $fontSizeBase = $width * 0.048;
            $textTop = (int) round($height * 0.11);
            $textBottom = (int) round($height * 0.57);
        } elseif (str_contains($templateFile, 'honorario') || str_contains($templateFile, 'filiacao')) {
            $colorArr = [20, 40, 80];
            $fontSizeBase = $width * 0.046;
            $textTop = (int) round($height * 0.11);
            $textBottom = (int) round($height * 0.58);
        }

        $textLen = mb_strlen($text, 'UTF-8');
        if ($textLen < 80) {
            $lineSpacingFactor = 1.50;
        } elseif ($textLen < 180) {
            $lineSpacingFactor = 1.43;
        } elseif ($textLen > 650) {
            $lineSpacingFactor = 1.18;
        }

        $maxWidth = max(60, $width - ($paddingX * 2));
        $maxHeight = max(80, $textBottom - $textTop);
        $fit = $this->fitTextToArea(
            $text,
            $font,
            (int) round($fontSizeBase * $maxFontMultiplier),
            (int) round($fontSizeBase * $minFontMultiplier),
            $lineSpacingFactor,
            $maxWidth,
            $maxHeight
        );

        $fontSize = $fit['fontSize'];
        $lineHeight = $fit['lineHeight'];
        $wrappedText = $fit['wrappedText'];
        $lines = preg_split('/\r\n|\r|\n/', $wrappedText) ?: [];
        $contentHeight = count($lines) * $lineHeight;
        $baseY = $textTop + (int) max(0, floor(($maxHeight - $contentHeight) / 2));
        $y = $baseY + $lineHeight;

        $color = imagecolorallocate($img, $colorArr[0], $colorArr[1], $colorArr[2]);
        $shadow = $shadowArr ? imagecolorallocatealpha($img, $shadowArr[0], $shadowArr[1], $shadowArr[2], $shadowArr[3]) : null;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                $y += $lineHeight;
                continue;
            }

            $fontLoaded = false;
            if (is_file($font)) {
                $box = @imagettfbbox($fontSize, 0, $font, $line);
                if (is_array($box)) {
                    $textWidth = abs($box[2] - $box[0]);
                    $x = $alignLeft ? $paddingX : (int) max($paddingX, floor(($width - $textWidth) / 2));

                    if ($shadow) {
                        @imagettftext($img, $fontSize, 0, $x + 2, $y + 2, $shadow, $font, $line);
                    }
                    @imagettftext($img, $fontSize, 0, $x, $y, $color, $font, $line);
                    $fontLoaded = true;
                }
            }

            if (!$fontLoaded) {
                $fw = imagefontwidth(5);
                $textWidth = strlen($line) * $fw;
                $x = $alignLeft ? $paddingX : (int) floor(($width - $textWidth) / 2);
                imagestring($img, 5, $x, max(0, $y - 14), $line, $color);
            }

            $y += $lineHeight;
            if ($y > ($textBottom + $lineHeight)) {
                break;
            }
        }

        imagesavealpha($img, true);
        imagepng($img, $targetPath);
        imagedestroy($img);

        return ['ok' => true, 'path' => $targetPath, 'cached' => false];
    }

    private function fitTextToArea(
        string $text,
        string $font,
        int $maxFontSize,
        int $minFontSize,
        float $lineSpacingFactor,
        int $maxWidth,
        int $maxHeight
    ): array {
        $maxFontSize = max($minFontSize, $maxFontSize);
        $wrappedMin = $this->wrapTextToPixels($text, $font, $minFontSize, $maxWidth);
        $best = [
            'fontSize' => $minFontSize,
            'lineHeight' => max(12, (int) round($minFontSize * $lineSpacingFactor)),
            'wrappedText' => $wrappedMin,
        ];

        for ($fontSize = $maxFontSize; $fontSize >= $minFontSize; $fontSize--) {
            $wrappedText = $this->wrapTextToPixels($text, $font, $fontSize, $maxWidth);
            $lines = preg_split('/\r\n|\r|\n/', $wrappedText) ?: [];
            $lineHeight = max(12, (int) round($fontSize * $lineSpacingFactor));
            $contentHeight = count($lines) * $lineHeight;

            if ($contentHeight <= $maxHeight) {
                return [
                    'fontSize' => $fontSize,
                    'lineHeight' => $lineHeight,
                    'wrappedText' => $wrappedText,
                ];
            }
        }

        return $best;
    }

    private function wrapTextToPixels(string $text, string $font, int $fontSize, int $maxWidth): string
    {
        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'ISO-8859-1');
        }

        if (!is_file($font)) {
            return wordwrap($text, 40, "\n", true);
        }

        $paragraphs = preg_split('/\R/u', $text);
        if ($paragraphs === false) {
            $paragraphs = preg_split('/\r\n|\n|\r/', $text) ?: [$text];
        }

        $finalOutput = [];
        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if ($paragraph === '') {
                $finalOutput[] = '';
                continue;
            }

            $words = explode(' ', $paragraph);
            $currentLine = '';

            foreach ($words as $word) {
                $word = trim($word);
                if ($word === '') {
                    continue;
                }

                $testLine = $currentLine === '' ? $word : $currentLine . ' ' . $word;
                $box = @imagettfbbox($fontSize, 0, $font, $testLine);
                if (is_array($box)) {
                    $lineWidth = abs($box[2] - $box[0]);
                    if ($lineWidth <= $maxWidth) {
                        $currentLine = $testLine;
                    } else {
                        if ($currentLine !== '') {
                            $finalOutput[] = $currentLine;
                            $currentLine = $word;
                        } else {
                            $finalOutput[] = $word;
                            $currentLine = '';
                        }
                    }
                } else {
                    $lenCurrent = mb_strlen($currentLine, 'UTF-8');
                    $lenWord = mb_strlen($word, 'UTF-8');
                    if ($lenCurrent + $lenWord + 1 <= 45) {
                        $currentLine = $testLine;
                    } else {
                        if ($currentLine !== '') {
                            $finalOutput[] = $currentLine;
                            $currentLine = $word;
                        } else {
                            $finalOutput[] = $word;
                            $currentLine = '';
                        }
                    }
                }
            }

            if ($currentLine !== '') {
                $finalOutput[] = $currentLine;
            }
        }

        return implode("\n", $finalOutput);
    }
}
