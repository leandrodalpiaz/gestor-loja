<?php
declare(strict_types=1);

namespace App\Services;

use Exception;

class CertificadoGenerator
{
    public function gerar(
        string $nomeVisitante,
        string $loja,
        string $oriente,
        string $tipoSessao,
        string $grauSessao,
        string $dataSessao
    ): string {
        $baseImagePath = __DIR__ . '/../../public/assets/certificado_base.jpeg';
        if (!file_exists($baseImagePath)) {
            throw new Exception('Imagem base do certificado não encontrada: ' . $baseImagePath);
        }

        $fontPath = __DIR__ . '/../../public/assets/fonte.ttf';
            if (!file_exists($fontPath)) {
                // URL direta para uma fonte clássica e elegante (Playfair Display) no repositório oficial do Google
                $fontUrl = 'https://raw.githubusercontent.com/google/fonts/main/ofl/playfairdisplay/PlayfairDisplay-Regular.ttf';

                // Tenta baixar a fonte
                $fontData = @file_get_contents($fontUrl);

                if ($fontData !== false) {
                    // Salva a fonte na pasta assets
                    file_put_contents($fontPath, $fontData);
                } else {
                    throw new Exception("Erro: O arquivo fonte.ttf não existe e o download automático falhou. Por favor, adicione o arquivo manualmente na pasta public/assets/");
                }
            }

        $imagemConteudo = file_get_contents($baseImagePath);
        $imagem = imagecreatefromstring($imagemConteudo);
        if (!$imagem) {
            throw new Exception("Erro: O arquivo de imagem base é inválido ou está corrompido.");
        }
        $corTexto = imagecolorallocate($imagem, 0, 0, 0);

        // Símbolo maçônico correto
        $pontos = "∴";
        $texto = "Certificamo-os com grande alegria no coração que o Ir{$pontos} {$nomeVisitante}\n";
        $texto .= "Obr{$pontos} da {$loja} Or{$pontos} de {$oriente}\n\n";
        $texto .= "Honrou-nos com sua visita, dando maior brilho, força e beleza aos trabalhos da nossa oficina.\n\n";

        $paragrafoLongo = "Na ocasião, realizamos uma Sessão {$tipoSessao} de {$grauSessao}, desta forma emitimos o presente certificado digital e aproveitamos, por seu intermédio, para enviar um T{$pontos}F{$pontos}A{$pontos} ao V{$pontos}M{$pontos} e demais IIr{$pontos} do quadro de sua Loja.";
        // Limitar texto para não ultrapassar as colunas (aprox. 50 caracteres por linha)
        $texto .= wordwrap($paragrafoLongo, 50, "\n") . "\n\n\n";

        $texto .= "Data da Sessão: " . date('d/m/Y', strtotime($dataSessao)) . "\n\n\n";
        $texto .= str_pad("Chan{$pontos}", 30, " ", STR_PAD_RIGHT) . str_pad("Ven{$pontos} Mes{$pontos}", 30, " ", STR_PAD_LEFT);

        // Centralizar texto entre as colunas
        $x = 120; // Ajuste para centralização
        $y = 350; // Ajuste para altura
        $tamanho = 22;
        $linhas = explode("\n", $texto);
        foreach ($linhas as $i => $linha) {
            // Calcula largura do texto
            $box = imagettfbbox($tamanho, 0, $fontPath, $linha);
            $largura = $box[2] - $box[0];
            $imgWidth = imagesx($imagem);
            // Centraliza cada linha
            $xCentral = ($imgWidth - $largura) / 2;
            imagettftext($imagem, $tamanho, 0, (int)$xCentral, $y + ($i * 32), $corTexto, $fontPath, $linha);
        }

        $tempDir = __DIR__ . '/../../public/temp';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $outputPath = $tempDir . '/certificado_' . time() . '.jpeg';
        imagejpeg($imagem, $outputPath, 90);
        $imagem = null;

        return $outputPath;
    }
}
