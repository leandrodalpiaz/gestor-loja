<?php
function is_valid_utf8($str) {
    return mb_check_encoding($str, 'UTF-8');
}

$mojibakePatterns = [
    'Ã©', 'Ã¡', 'Ã³', 'Ã­', 'Ãº', 'Ã§', 'Ã£', 'Ãµ', 'Ãª', 'Ã¢', 'Ã´',
    'Ã‰', 'Ã ', 'Ã“', 'Ã', 'Ãš', 'Ã‡', 'Ãƒ', 'Ã•', 'ÃŠ', 'Ã‚', 'Ã”',
    'ï¿½'
];

function scan_directory($dir, &$results) {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($files as $file) {
        if ($file->isDir()) continue;
        $path = $file->getRealPath();
        if (!preg_match('/\.(php|js|css|html|md|json)$/', $path)) continue;
        if (strpos($path, 'vendor') !== false) continue;
        if (strpos($path, 'node_modules') !== false) continue;
        if (strpos($path, 'scratch') !== false) continue;

        $content = file_get_contents($path);
        
        // Check 1: Not valid UTF-8
        if (!is_valid_utf8($content)) {
            $results[] = "INVALID UTF-8: $path";
            continue;
        }

        // Check 2: Common mojibake strings (encoded as literal UTF-8 strings in the file)
        global $mojibakePatterns;
        foreach ($mojibakePatterns as $pattern) {
            if (mb_strpos($content, $pattern) !== false) {
                $results[] = "MOJIBAKE FOUND ($pattern): $path";
                break;
            }
        }
    }
}

$results = [];
scan_directory(__DIR__ . '/../src', $results);
scan_directory(__DIR__ . '/../public', $results);

if (empty($results)) {
    echo "✅ No issues found!\n";
} else {
    echo "❌ Issues found:\n";
    foreach ($results as $res) {
        echo " - $res\n";
    }
}
