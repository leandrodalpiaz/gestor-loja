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

        if (!mb_check_encoding($text, 'UTF-8')) {
            return ['ok' => false, 'error' => 'invalid_text_encoding'];
        }

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
        $fontNormal = realpath($fontRaw) ?: $fontRaw;

        $fontCursiveRaw = dirname(__DIR__, 2) . '/public/assets/fonts/AlexBrush-Regular.ttf';
        $fontCursive = realpath($fontCursiveRaw) ?: $fontCursiveRaw;
        if (!is_file($fontCursive)) {
            $fontCursive = $fontNormal;
        }

        $fontBoldRaw = dirname(__DIR__, 2) . '/public/assets/fonts/Inter-Bold.ttf';
        $fontBoldInter = realpath($fontBoldRaw) ?: $fontBoldRaw;
        if (!is_file($fontBoldInter)) {
            $fontBoldInter = $fontNormal;
        }

        $fontPlayfairRaw = dirname(__DIR__, 2) . '/public/assets/fonts/PlayfairDisplay-Italic.ttf';
        $fontPlayfair = realpath($fontPlayfairRaw) ?: $fontPlayfairRaw;
        if (!is_file($fontPlayfair)) {
            $fontPlayfair = $fontNormal;
        }

        // Determina se é um evento festivo/aniversário
        $profile = $this->resolveVisualProfile($templateFile, (string) ($cardPayload['categoria'] ?? ''), $isGold, $width, $height);
        $isCelebration = (bool) $profile['celebration'];
        $isAffectionateBirthday = $profile['kind'] === 'birthday_affectionate';

        $fontHeader = match ($profile['headerFont']) {
            'cursive' => $fontCursive,
            'inter-bold' => $fontBoldInter,
            'playfair-italic' => $fontPlayfair,
            default => $fontNormal,
        };
        $fontBody = match ($profile['bodyFont']) {
            'cursive' => $fontCursive,
            'inter-bold' => $fontBoldInter,
            'playfair-italic' => $fontPlayfair,
            default => $fontNormal,
        };
        $fontBold = match ($profile['boldFont']) {
            'cursive' => $fontCursive,
            'inter-bold' => $fontBoldInter,
            'playfair-italic' => $fontPlayfair,
            default => $fontNormal,
        };

        $colorArr = $profile['color'];
        $shadowArr = $profile['shadow'];
        $fontSizeBase = $profile['fontSizeBase'];
        $maxFontMultiplier = $profile['maxFontMultiplier'];
        $minFontMultiplier = $profile['minFontMultiplier'];
        $paddingX = $profile['paddingX'];
        $textTop = $profile['textTop'];
        $textBottom = $profile['textBottom'];
        $lineSpacingFactor = $profile['lineSpacingFactor'];
        $alignLeft = $profile['align'] === 'left';
        $adaptiveLineSpacing = (bool) $profile['adaptiveLineSpacing'];

        $color = imagecolorallocate($img, $colorArr[0], $colorArr[1], $colorArr[2]);
        $shadow = $shadowArr ? imagecolorallocatealpha($img, $shadowArr[0], $shadowArr[1], $shadowArr[2], $shadowArr[3]) : null;

        if ($profile['panelStyle'] === 'birthday') {
            $this->drawBirthdayPanel($img, $width, $height);
        } elseif ($profile['panelStyle'] === 'history') {
            $this->drawHistoryPanel($img, $width, $height);
        } elseif ($profile['panelStyle'] === 'masonic') {
            $this->drawMasonicPanel($img, $width, $height);
        } elseif ($profile['panelStyle'] === 'memorial') {
            $this->drawMemorialPanel($img, $width, $height);
        }

        // Gerar e desenhar o cabeçalho decorativo
        $cabecalho = $this->obterCabecalhoCard($templateFile, (string) ($cardPayload['categoria'] ?? ''));
        if ($cabecalho !== '') {
            $fontSizeHeader = (int) round($fontSizeBase * $profile['headerMultiplier']);
            $fontSizeHeader = $this->fitFontSizeForText($cabecalho, $fontHeader, $fontSizeHeader, (int) round($fontSizeBase * 0.72), $width - ($paddingX * 2));
            $letterSpacing = (int) round($fontSizeHeader * (float) $profile['headerLetterSpacing']);
            $widthHeader = is_file($fontHeader) ? $this->measureTextWidth($cabecalho, $fontHeader, $fontSizeHeader, $letterSpacing) : 0;
            $xHeader = (int) max($paddingX, floor(($width - $widthHeader) / 2));

            if (is_file($fontHeader)) {
                if ($shadow) {
                    $this->drawTextLine($img, $cabecalho, $fontHeader, $fontSizeHeader, $xHeader + 1, $textTop + 1, $shadow, $letterSpacing);
                }
                if ($profile['headerBold']) {
                    $this->drawTextLine($img, $cabecalho, $fontHeader, $fontSizeHeader, $xHeader + 1, $textTop, $color, $letterSpacing);
                }
                $this->drawTextLine($img, $cabecalho, $fontHeader, $fontSizeHeader, $xHeader, $textTop, $color, $letterSpacing);
            }

            // Desloca o topo do corpo do cartão para baixo para deixar espaço elegante
            $textTop += (int) round($fontSizeHeader * $profile['headerGapMultiplier']);
        }

        $textLen = mb_strlen($text, 'UTF-8');
        if ($adaptiveLineSpacing && $textLen < 80) {
            $lineSpacingFactor = 1.50;
        } elseif ($adaptiveLineSpacing && $textLen < 180) {
            $lineSpacingFactor = 1.43;
        } elseif ($adaptiveLineSpacing && $textLen > 650) {
            $lineSpacingFactor = 1.18;
        }

        $maxWidth = max(60, $width - ($paddingX * 2));
        $maxHeight = max(80, $textBottom - $textTop);
        $fit = $this->fitTextToArea(
            $text,
            $fontBody,
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

        $isBoldGlobal = false;
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                $y += $lineHeight;
                continue;
            }

            $fontLoaded = false;
            if (is_file($fontBody)) {
                $totalWidth = 0;
                $segments = [];
                $parts = explode('**', $line);

                // First pass to calculate total width
                $tempIsBold = $isBoldGlobal;
                foreach ($parts as $idx => $part) {
                    if ($idx > 0) {
                        $tempIsBold = !$tempIsBold;
                    }
                    $segmentFont = $tempIsBold ? $fontBold : $fontBody;
                    $box = @imagettfbbox($fontSize, 0, $segmentFont, $part);
                    $w = is_array($box) ? abs($box[2] - $box[0]) : 0;

                    if ($tempIsBold && !$isCelebration) {
                        $w += 1;
                    }

                    $segments[] = [
                        'text' => $part,
                        'isBold' => $tempIsBold,
                        'width' => $w
                    ];
                    $totalWidth += $w;
                }

                $x = $alignLeft ? $paddingX : (int) max($paddingX, floor(($width - $totalWidth) / 2));

                foreach ($segments as $seg) {
                    if ($seg['text'] === '') {
                        continue;
                    }
                    $segmentFont = $seg['isBold'] ? $fontBold : $fontBody;
                    
                    if ($seg['isBold']) {
                        if ($isCelebration) {
                            if ($shadow) {
                                @imagettftext($img, $fontSize, 0, $x + 1, $y + 1, $shadow, $segmentFont, $seg['text']);
                            }
                            if ($isAffectionateBirthday) {
                                @imagettftext($img, $fontSize, 0, $x + 1, $y, $color, $segmentFont, $seg['text']);
                            }
                            @imagettftext($img, $fontSize, 0, $x, $y, $color, $segmentFont, $seg['text']);
                        } else {
                            for ($offset = 0; $offset <= 1; $offset++) {
                                if ($shadow) {
                                    @imagettftext($img, $fontSize, 0, $x + $offset + 1, $y + 1, $shadow, $segmentFont, $seg['text']);
                                }
                                @imagettftext($img, $fontSize, 0, $x + $offset, $y, $color, $segmentFont, $seg['text']);
                            }
                        }
                    } else {
                        if ($shadow) {
                            @imagettftext($img, $fontSize, 0, $x + 1, $y + 1, $shadow, $segmentFont, $seg['text']);
                        }
                        @imagettftext($img, $fontSize, 0, $x, $y, $color, $segmentFont, $seg['text']);
                    }
                    $x += $seg['width'];
                }

                // Update global state for next line
                $isBoldGlobal = $tempIsBold;
                $fontLoaded = true;
            }

            if (!$fontLoaded) {
                $lineClean = str_replace('**', '', $line);
                $fw = imagefontwidth(5);
                $textWidth = strlen($lineClean) * $fw;
                $x = $alignLeft ? $paddingX : (int) floor(($width - $textWidth) / 2);
                imagestring($img, 5, $x, max(0, $y - 14), $lineClean, $color);
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
                $testLineClean = str_replace('**', '', $testLine);
                $box = @imagettfbbox($fontSize, 0, $font, $testLineClean);
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

    private function fitFontSizeForText(string $text, string $font, int $maxFontSize, int $minFontSize, int $maxWidth): int
    {
        if (!is_file($font)) {
            return max($minFontSize, $maxFontSize);
        }

        for ($fontSize = $maxFontSize; $fontSize >= $minFontSize; $fontSize--) {
            $width = $this->measureTextWidth($text, $font, $fontSize, 0);
            if ($width <= $maxWidth) {
                return $fontSize;
            }
        }

        return max(8, $minFontSize);
    }

    private function measureTextWidth(string $text, string $font, int $fontSize, int $letterSpacing = 0): int
    {
        $box = @imagettfbbox($fontSize, 0, $font, $text);
        $width = is_array($box) ? abs($box[2] - $box[0]) : 0;
        if ($letterSpacing <= 0) {
            return $width;
        }

        $characters = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        return $width + max(0, count($characters) - 1) * $letterSpacing;
    }

    private function drawTextLine($img, string $text, string $font, int $fontSize, int $x, int $y, int $color, int $letterSpacing = 0): void
    {
        if ($letterSpacing <= 0) {
            @imagettftext($img, $fontSize, 0, $x, $y, $color, $font, $text);
            return;
        }

        $characters = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        foreach ($characters as $character) {
            @imagettftext($img, $fontSize, 0, $x, $y, $color, $font, $character);
            $x += $this->measureTextWidth($character, $font, $fontSize) + $letterSpacing;
        }
    }

    private function resolveVisualProfile(string $templateFile, string $categoria, bool $isGold, int $width, int $height): array
    {
        $template = strtolower($templateFile);
        $categoria = $this->lowerAscii($categoria);

        $profile = [
            'kind' => 'default',
            'celebration' => false,
            'headerFont' => 'normal',
            'bodyFont' => 'normal',
            'boldFont' => 'normal',
            'color' => [40, 40, 40],
            'shadow' => null,
            'fontSizeBase' => $width * 0.046,
            'maxFontMultiplier' => 1.40,
            'minFontMultiplier' => 0.54,
            'paddingX' => (int) round($width * 0.09),
            'textTop' => (int) round($height * 0.12),
            'textBottom' => (int) round($height * 0.58),
            'lineSpacingFactor' => 1.36,
            'align' => 'center',
            'adaptiveLineSpacing' => true,
            'panelStyle' => 'none',
            'headerMultiplier' => 1.12,
            'headerGapMultiplier' => 2.1,
            'headerLetterSpacing' => 0.0,
            'headerBold' => false,
        ];

        if (str_contains($template, 'solar') || str_contains($template, 'sobrinh')) {
            return array_replace($profile, [
                'kind' => 'birthday_affectionate',
                'celebration' => true,
                'headerFont' => 'normal',
                'bodyFont' => 'cursive',
                'boldFont' => 'cursive',
                'color' => [60, 40, 20],
                'fontSizeBase' => $width * 0.043,
                'maxFontMultiplier' => 1.08,
                'minFontMultiplier' => 0.48,
                'paddingX' => (int) round($width * 0.105),
                'textTop' => (int) round($height * 0.105),
                'textBottom' => (int) round($height * 0.490),
                'lineSpacingFactor' => 1.28,
                'adaptiveLineSpacing' => false,
                'panelStyle' => 'birthday',
                'headerMultiplier' => 1.02,
                'headerGapMultiplier' => 1.7,
                'headerLetterSpacing' => 0.04,
                'headerBold' => true,
            ]);
        }

        if (str_contains($template, 'kids') || str_contains($categoria, 'filh')) {
            return array_replace($profile, [
                'kind' => 'birthday_child',
                'celebration' => true,
                'headerFont' => 'normal',
                'bodyFont' => 'normal',
                'boldFont' => 'cursive',
                'color' => [20, 80, 160],
                'fontSizeBase' => $width * 0.052,
                'textTop' => (int) round($height * 0.13),
                'textBottom' => (int) round($height * 0.60),
                'panelStyle' => 'birthday',
                'headerMultiplier' => 1.38,
                'headerGapMultiplier' => 1.3,
                'headerBold' => true,
            ]);
        }

        if (str_contains($template, 'bedrock') || str_contains($template, 'simpsons')) {
            return array_replace($profile, [
                'kind' => 'birthday_brother',
                'celebration' => true,
                'headerFont' => 'normal',
                'bodyFont' => 'normal',
                'boldFont' => 'cursive',
                'color' => [255, 255, 255],
                'shadow' => [0, 0, 0, 80],
                'fontSizeBase' => $width * 0.050,
                'textTop' => (int) round($height * 0.11),
                'textBottom' => (int) round($height * 0.57),
                'headerMultiplier' => 1.55,
                'headerGapMultiplier' => 1.3,
                'headerBold' => true,
            ]);
        }

        if (str_contains($template, 'historia') || str_contains($template, 'sepia') || str_contains($categoria, 'historia')) {
            return array_replace($profile, [
                'kind' => 'history',
                'headerFont' => 'normal',
                'bodyFont' => 'normal',
                'boldFont' => 'normal',
                'color' => [60, 42, 28],
                'fontSizeBase' => $width * 0.048,
                'maxFontMultiplier' => 1.10,
                'minFontMultiplier' => 0.58,
                'paddingX' => (int) round($width * 0.078),
                'textTop' => (int) round($height * 0.065),
                'textBottom' => (int) round($height * 0.725),
                'lineSpacingFactor' => 1.32,
                'align' => 'left',
                'panelStyle' => 'history',
                'headerMultiplier' => 1.0,
                'headerGapMultiplier' => 1.0,
            ]);
        }

        if (str_contains($template, 'eterno') || str_contains($template, 'memorial')) {
            return array_replace($profile, [
                'kind' => 'memorial',
                'color' => [245, 242, 235],
                'shadow' => [0, 0, 0, 88],
                'fontSizeBase' => $width * 0.036,
                'maxFontMultiplier' => 1.00,
                'minFontMultiplier' => 0.50,
                'paddingX' => (int) round($width * 0.12),
                'textTop' => (int) round($height * 0.16),
                'textBottom' => (int) round($height * 0.62),
                'lineSpacingFactor' => 1.34,
                'adaptiveLineSpacing' => false,
                'panelStyle' => 'memorial',
                'headerMultiplier' => 1.08,
                'headerGapMultiplier' => 1.85,
            ]);
        }

        if ($isGold || str_contains($template, 'grao_mestre') || str_contains($template, 'iniciacao') || str_contains($template, 'elevacao') || str_contains($template, 'exaltacao') || str_contains($template, 'instalacao')) {
            return array_replace($profile, [
                'kind' => 'masonic_degree',
                'color' => [222, 184, 78],
                'shadow' => [0, 0, 0, 80],
                'fontSizeBase' => $width * 0.038,
                'maxFontMultiplier' => 1.00,
                'minFontMultiplier' => 0.50,
                'paddingX' => (int) round($width * 0.105),
                'textTop' => (int) round($height * 0.12),
                'textBottom' => (int) round($height * 0.585),
                'lineSpacingFactor' => 1.30,
                'adaptiveLineSpacing' => false,
                'panelStyle' => 'masonic',
                'headerMultiplier' => 1.18,
                'headerGapMultiplier' => 1.95,
                'headerBold' => true,
            ]);
        }

        if (str_contains($template, 'honorario') || str_contains($template, 'filiacao')) {
            return array_replace($profile, [
                'kind' => 'formal_special',
                'color' => [20, 40, 80],
                'fontSizeBase' => $width * 0.046,
                'textTop' => (int) round($height * 0.11),
                'textBottom' => (int) round($height * 0.58),
                'panelStyle' => 'masonic',
                'headerMultiplier' => 1.12,
                'headerGapMultiplier' => 2.0,
            ]);
        }

        return $profile;
    }

    private function lowerAscii(string $value): string
    {
        $value = function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
        return iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
    }

    private function drawBirthdayPanel($img, int $width, int $height): void
    {
        imagealphablending($img, true);

        $panelLeft = (int) round($width * 0.075);
        $panelTop = (int) round($height * 0.055);
        $panelRight = (int) round($width * 0.925);
        $panelBottom = (int) round($height * 0.525);

        $warmWash = imagecolorallocatealpha($img, 255, 247, 232, 72);
        imagefilledrectangle($img, $panelLeft, $panelTop, $panelRight, $panelBottom, $warmWash);

        $border = imagecolorallocatealpha($img, 146, 102, 49, 76);
        imagerectangle($img, $panelLeft, $panelTop, $panelRight, $panelBottom, $border);

        $innerBorder = imagecolorallocatealpha($img, 255, 255, 255, 72);
        imagerectangle($img, $panelLeft + 8, $panelTop + 8, $panelRight - 8, $panelBottom - 8, $innerBorder);
    }

    private function drawHistoryPanel($img, int $width, int $height): void
    {
        imagealphablending($img, true);

        // Painel sutil sem fundo sólido — apenas moldura fina e linha decorativa
        $panelLeft = (int) round($width * 0.055);
        $panelTop = (int) round($height * 0.055);
        $panelRight = (int) round($width * 0.945);
        $panelBottom = (int) round($height * 0.735);

        // Moldura externa muito sutil (apenas borda, sem preenchimento)
        imagerectangle($img, $panelLeft, $panelTop, $panelRight, $panelBottom, imagecolorallocatealpha($img, 105, 72, 38, 90));
        // Linha decorativa horizontal fina
        imageline($img, $panelLeft + 22, $panelTop + 50, $panelRight - 22, $panelTop + 50, imagecolorallocatealpha($img, 105, 72, 38, 100));
    }

    private function drawMasonicPanel($img, int $width, int $height): void
    {
        imagealphablending($img, true);

        $panelLeft = (int) round($width * 0.075);
        $panelTop = (int) round($height * 0.075);
        $panelRight = (int) round($width * 0.925);
        $panelBottom = (int) round($height * 0.62);

        imagefilledrectangle($img, $panelLeft, $panelTop, $panelRight, $panelBottom, imagecolorallocatealpha($img, 3, 10, 30, 72));
        imagerectangle($img, $panelLeft, $panelTop, $panelRight, $panelBottom, imagecolorallocatealpha($img, 222, 184, 78, 72));
        imagerectangle($img, $panelLeft + 10, $panelTop + 10, $panelRight - 10, $panelBottom - 10, imagecolorallocatealpha($img, 255, 255, 255, 96));
    }

    private function drawMemorialPanel($img, int $width, int $height): void
    {
        imagealphablending($img, true);

        $panelLeft = (int) round($width * 0.10);
        $panelTop = (int) round($height * 0.10);
        $panelRight = (int) round($width * 0.90);
        $panelBottom = (int) round($height * 0.66);

        imagefilledrectangle($img, $panelLeft, $panelTop, $panelRight, $panelBottom, imagecolorallocatealpha($img, 0, 0, 0, 84));
        imagerectangle($img, $panelLeft, $panelTop, $panelRight, $panelBottom, imagecolorallocatealpha($img, 230, 220, 198, 78));
    }

    private function obterCabecalhoCard(string $templateFile, string $categoria): string
    {
        $templateFile = strtolower($templateFile);
        $categoria = strtolower($categoria);

        if (str_contains($templateFile, 'iniciacao')) {
            return 'INICIAÇÃO';
        }
        if (str_contains($templateFile, 'elevacao')) {
            return 'ELEVAÇÃO';
        }
        if (str_contains($templateFile, 'exalta')) {
            return 'EXALTAÇÃO';
        }
        if (str_contains($templateFile, 'instala')) {
            return 'INSTALAÇÃO';
        }
        if (str_contains($templateFile, 'memorial') || str_contains($templateFile, 'eterno')) {
            return 'EM MEMÓRIA';
        }
        if (str_contains($templateFile, 'historia') || str_contains($templateFile, 'sepia') || $categoria === 'nossa história' || $categoria === 'nossa historia') {
            return ''; // Cartões de história não precisam de cabeçalho — o título já está no corpo
        }
        if (str_contains($templateFile, 'solar') || str_contains($templateFile, 'kids') || str_contains($templateFile, 'sobrinh') || str_contains($templateFile, 'bedrock') || str_contains($templateFile, 'simpsons') || $categoria === 'aniversario' || $categoria === 'aniversário' || $categoria === 'cunhada') {
            return 'FELIZ ANIVERSÁRIO!';
        }

        return 'EFEMÉRIDE';
    }
}
