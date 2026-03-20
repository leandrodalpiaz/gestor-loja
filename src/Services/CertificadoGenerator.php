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

        $texto = "Certificamo-os com grande alegria no coração que o Ir∴ {$nomeVisitante}\n";
        $texto .= "Obr∴ da {$loja} Or∴ de {$oriente}\n\n";
        $texto .= "Honrou-nos com sua visita, dando maior brilho, força e beleza aos trabalhos da nossa oficina.\n\n";

        $paragrafoLongo = "Na ocasião, realizamos uma Sessão {$tipoSessao} de {$grauSessao}, desta forma emitimos o presente certificado digital e aproveitamos, por seu intermédio, para enviar um T∴F∴A∴ ao V∴M∴ e demais IIr∴ do quadro de sua Loja.";
        $texto .= wordwrap($paragrafoLongo, 75, "\n") . "\n\n\n";

        $texto .= "Data da Sessão: " . date('d/m/Y', strtotime($dataSessao)) . "\n\n\n";
        $texto .= "          Chan∴                                                               Ven∴ Mes∴";

        // O desenvolvedor ajustará o X=120, Y=350 e o tamanho=22 depois, se necessário
        imagettftext($imagem, 22, 0, 120, 350, $corTexto, $fontPath, $texto);

        $tempDir = __DIR__ . '/../../public/temp';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $outputPath = $tempDir . '/certificado_' . time() . '.jpeg';
        imagejpeg($imagem, $outputPath, 90);
        imagedestroy($imagem);

        return $outputPath;
    }
}
