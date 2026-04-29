<?php
declare(strict_types=1);

/**
 * Repairs common UTF-8 mojibake in project text files.
 *
 * Usage:
 *   php scripts/fix_mojibake.php          # dry run
 *   php scripts/fix_mojibake.php --write  # update files
 */

$projectRoot = dirname(__DIR__);
$write = in_array('--write', $argv, true);
$extensions = ['php', 'css', 'js', 'json', 'md'];
$ignoredDirs = ['.git', 'vendor', 'node_modules'];

$from = [];
$to = [];

if (!function_exists('mb_chr')) {
    fwrite(STDERR, "mbstring is required.\n");
    exit(1);
}

for ($byte = 0x80; $byte <= 0xFF; $byte++) {
    $correct = mb_chr($byte, 'UTF-8');
    $utf8Bytes = array_values(unpack('C*', $correct));
    $mojibake = '';

    foreach ($utf8Bytes as $value) {
        $mojibake .= mb_chr($value, 'UTF-8');
    }

    if ($mojibake !== $correct) {
        $from[] = $mojibake;
        $to[] = $correct;
    }
}

$specialPairs = [
    "\xC3\xA2\xE2\x82\xAC\xC2\xA2" => "\xE2\x80\xA2",
    "\xC3\xA2\xE2\x82\xAC\xC2\x9C" => "\xE2\x80\x9C",
    "\xC3\xA2\xE2\x82\xAC\xC2\x9D" => "\xE2\x80\x9D",
    "\xC3\xA2\xE2\x82\xAC\xC2\x99" => "\xE2\x80\x99",
    "\xC3\xA2\xE2\x82\xAC\xC2\x93" => "\xE2\x80\x93",
    "\xC3\xA2\xE2\x82\xAC\xC2\x94" => "\xE2\x80\x94",
    "\xC3\xA2\xE2\x82\xAC\xC2\xA6" => "\xE2\x80\xA6",
];

foreach ($specialPairs as $bad => $good) {
    $from[] = $bad;
    $to[] = $good;
}

$iterator = new RecursiveIteratorIterator(
    new RecursiveCallbackFilterIterator(
        new RecursiveDirectoryIterator($projectRoot, FilesystemIterator::SKIP_DOTS),
        static function (SplFileInfo $current) use ($ignoredDirs): bool {
            return !$current->isDir() || !in_array($current->getFilename(), $ignoredDirs, true);
        }
    )
);

$changed = [];

foreach ($iterator as $file) {
    if (!$file instanceof SplFileInfo || !$file->isFile()) {
        continue;
    }

    $extension = strtolower($file->getExtension());
    if (!in_array($extension, $extensions, true)) {
        continue;
    }

    $path = $file->getPathname();
    $original = file_get_contents($path);
    if ($original === false) {
        continue;
    }

    $fixed = $original;
    for ($pass = 0; $pass < 4; $pass++) {
        $next = str_replace($from, $to, $fixed);
        if ($next === $fixed) {
            break;
        }
        $fixed = $next;
    }

    if ($fixed !== $original) {
        $relative = str_replace($projectRoot . DIRECTORY_SEPARATOR, '', $path);
        $changed[] = $relative;
        if ($write) {
            file_put_contents($path, $fixed);
        }
    }
}

foreach ($changed as $file) {
    echo ($write ? 'FIXED  ' : 'WOULD  ') . $file . PHP_EOL;
}

echo PHP_EOL . count($changed) . ' file(s) ' . ($write ? 'updated' : 'would be updated') . '.' . PHP_EOL;
