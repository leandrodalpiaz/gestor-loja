<?php

function cleanup_mojibake($content) {
    // 1. Fix Unicode Escapes (\u00e1 -> á)
    $content = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
        return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
    }, $content);

    // 2. Fix Triple Encoding (UTF-8 -> ISO-8859-1 -> ISO-8859-1 -> UTF-8)
    $triple = [
        'Ãƒ§' => 'ç',
        'Ãƒ£' => 'ã',
        'Ãƒµ' => 'õ',
        'Ãƒ©' => 'é',
        'Ãƒª' => 'ê',
        'Ãƒ­' => 'í',
        'Ãƒ³' => 'ó',
        'Ãƒ´' => 'ô',
        'Ãƒº' => 'ú',
        'Ãƒ¡' => 'á',
        'Ãƒ ' => 'à',
        'Ãƒ¢' => 'â',
        'ÃƒÂ‰' => 'É',
        'ÃƒÂ€' => 'À',
        'Ãƒâ€¡' => 'Ç',
        'Ãƒâ€œ' => 'Ó',
        'Ãƒâ€°' => 'É',
        'Ãƒâ€˜' => 'Ñ',
        'Ãƒâ€š' => 'Â',
        'Ãƒâ€¹' => 'Ê',
        'ÃƒÆ’' => 'Ã',
    ];
    $content = str_replace(array_keys($triple), array_values($triple), $content);

    // 3. Fix Double Encoding (UTF-8 -> ISO-8859-1 -> UTF-8)
    $double = [
        'ç' => 'ç',
        'ã' => 'ã',
        'é' => 'é',
        'ó' => 'ó',
        'ú' => 'ú',
        'í' => 'í',
        'ê' => 'ê',
        'õ' => 'õ',
        'á' => 'á',
        'ò' => 'ò',
        'Ã ' => 'à',
        'â' => 'â',
        'ô' => 'ô',
        'Ã‡' => 'Ç',
        'Ã‰' => 'É',
        'Ã“' => 'Ó',
        'Ã‚º' => 'º',
        'Ã‚ª' => 'ª',
        'Ã‚Â' => '', 
    ];
    $content = str_replace(array_keys($double), array_values($double), $content);

    // 4. Fix broken UTF-8 replacement chars
    $replacements = [
        'Sessï¿½es' => 'Sessões',
        'Sessï¿½es' => 'Sessões',
        'atualizaï¿½ï¿½o' => 'atualização',
        'atualizaï¿½ï¿½o' => 'atualização',
        'publicaï¿½ï¿½o' => 'publicação',
        'publicaï¿½ï¿½o' => 'publicação',
        'revisï¿½o' => 'revisão',
        'revisï¿½o' => 'revisão',
        'Revisï¿½o' => 'Revisão',
        'Revisï¿½o' => 'Revisão',
        'existï¿½ntï¿½' => 'existente',
        'Sessï¿½o' => 'Sessão',
        'Sessï¿½o' => 'Sessão',
        'sessï¿½o' => 'sessão',
    ];
    $content = str_replace(array_keys($replacements), array_values($replacements), $content);

    $content = str_replace(['nï¿½', 'nï¿½'], 'nº', $content);
    $content = str_replace(['jï¿½', 'jï¿½'], 'já', $content);
    $content = str_replace('j  ', 'já ', $content);

    return $content;
}

function process_directory($dir) {
    if (!is_dir($dir)) return;
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($files as $file) {
        if ($file->isDir()) continue;
        $path = $file->getRealPath();
        if (preg_match('/\.(php|js|html|css|md|json)$/', $path)) {
            $content = file_get_contents($path);
            $clean = cleanup_mojibake($content);
            if ($content !== $clean) {
                echo "Cleaning $path...\n";
                file_put_contents($path, $clean);
            }
        }
    }
}

process_directory(__DIR__ . '/../src');
process_directory(__DIR__ . '/../public');
process_directory(__DIR__ . '/../docs');
