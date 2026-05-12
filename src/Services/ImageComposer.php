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

        // AUTOSCALING INTELIGENTE: Aumenta textos curtos para preencher e reduz textos longos
        $textLen = mb_strlen($text, 'UTF-8');
        $scaleFactor = 1.0;
        $lineSpacingFactor = 1.5; // Espaçamento padrão

        if ($textLen < 80) {
            $scaleFactor = 1.40;       // Textos bem curtos ganham 40% extra
            $lineSpacingFactor = 1.8; // E ficam mais espaçados verticalmente
        } elseif ($textLen < 180) {
            $scaleFactor = 1.15;       // Textos médios crescem um pouco
            $lineSpacingFactor = 1.6;
        } elseif ($textLen > 400 && $textLen <= 650) {
            $scaleFactor = 0.90;       // Textos longos encolhem levemente
            $lineSpacingFactor = 1.4;
        } elseif ($textLen > 650) {
            $scaleFactor = 0.80;       // Textos enormes encolhem mais
            $lineSpacingFactor = 1.3;
        }

        $fontSize = (int) round($fontSizeBase * $scaleFactor); 
        $lineHeight = (int) round($fontSize * $lineSpacingFactor);
        $y = (int) round($yBase);
        
        // Ajuste visual: se o texto é muito curto, descer levemente a posição inicial Y para centrar melhor verticalmente
        if ($textLen < 120) {
            $y += (int) ($height * 0.08); // Desce ~8% para evitar "colar" no topo com texto curto
        }
        
        // 1. Definir largura máxima útil (Margem de 10% de cada lado)
        $maxWidth = (int) ($width * 0.80);
        $paddingX = (int) ($width * 0.10);
        
        // 2. Função de quebra baseada em pixels TrueType (não em contagem de caracteres)
        $wrappedText = $this->wrapTextToPixels($text, $font, $fontSize, $maxWidth);
        $lines = preg_split('/\r\n|\r|\n/', $wrappedText) ?: [];
        $color = imagecolorallocate($img, $colorArr[0], $colorArr[1], $colorArr[2]);
        $shadow = $shadowArr ? imagecolorallocatealpha($img, $shadowArr[0], $shadowArr[1], $shadowArr[2], $shadowArr[3]) : null;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                $y += $lineHeight;
                continue;
            }
            $fontLoaded = false;
            $isAlignLeft = str_contains($templateFile, 'sepia'); // Histórias ficam melhores alinhadas à esquerda
            
            if (is_file($font)) {
                $box = @imagettfbbox($fontSize, 0, $font, $line);
                if (is_array($box)) {
                    $textWidth = abs($box[2] - $box[0]);
                    if ($isAlignLeft) {
                        $x = $paddingX; // Alinhamento fixo à esquerda com margem
                    } else {
                        $x = (int) max($paddingX, floor(($width - $textWidth) / 2)); // Centralizado, mas seguro
                    }
                    
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

    private function wrapTextToPixels(string $text, string $font, int $fontSize, int $maxWidth): string
    {
        // 1. Sanitização crítica de encoding: impede crash de regex e garante suporte a acentuação brasileira
        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'ISO-8859-1');
        }

        if (!is_file($font)) {
            return wordwrap($text, 40, "\n", true);
        }

        // 2. Quebra segura de parágrafos manuais
        $paragraphs = preg_split('/\R/u', $text);
        if ($paragraphs === false) {
            // Fallback se até a quebra de parágrafo falhar (sem o /u)
            $paragraphs = preg_split('/\r\n|\n|\r/', $text) ?: [$text];
        }

        $finalOutput = [];
        $fontAvailable = true;

        foreach ($paragraphs as $para) {
            $para = trim($para);
            if ($para === '') {
                $finalOutput[] = '';
                continue;
            }

            // 3. Split seguro de palavras usando espaço comum em vez de regex instável
            $words = explode(' ', $para);
            $currentLine = '';
            
            foreach ($words as $word) {
                $word = trim($word);
                if ($word === '') continue;
                
                $testLine = $currentLine === '' ? $word : $currentLine . ' ' . $word;
                $box = null;
                if ($fontAvailable) {
                    $box = @imagettfbbox($fontSize, 0, $font, $testLine);
                }
                
                if (is_array($box)) {
                    $lineWidth = abs($box[2] - $box[0]);
                    if ($lineWidth <= $maxWidth) {
                        $currentLine = $testLine;
                    } else {
                        if ($currentLine !== '') {
                            $finalOutput[] = $currentLine;
                            $currentLine = $word;
                        } else {
                            $finalOutput[] = $word; // Palavra isolada gigante
                            $currentLine = '';
                        }
                    }
                } else {
                    // EMERGÊNCIA: Falha catastrófica na leitura da fonte ou medição.
                    // Usamos contagem de caracteres como último recurso de segurança.
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
