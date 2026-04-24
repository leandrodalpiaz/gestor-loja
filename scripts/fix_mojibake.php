<?php
/**
 * Fix mojibake in PHP view/bot files.
 *
 * Cause: UTF-8 bytes were treated as individual Latin-1 chars and re-encoded as UTF-8,
 * turning 2-byte UTF-8 sequences (e.g., ã = C3 A3) into 4-byte sequences (Ã£ = C3 83 C2 A3).
 */

$projectRoot = dirname(__DIR__);

$files = [
    $projectRoot . '/src/Bot/CommandHandler.php',
    $projectRoot . '/src/Views/hospitaleiro/index.php',
    $projectRoot . '/src/Views/tesouraria_sessao/index.php',
    $projectRoot . '/src/Views/efemerides_chanceler.php',
    $projectRoot . '/src/Views/miniapp/data-maconica.php',
    $projectRoot . '/src/Views/tesouraria_relatorio_gestao.php',
    $projectRoot . '/src/Views/tesouraria_recibo.php',
    $projectRoot . '/src/Views/miniapp/admin.php',
    $projectRoot . '/src/Views/biblioteca/adicionar.php',
];

// Build replacement pairs for 2-byte UTF-8 mojibake (Latin-1 supplement U+0080-U+00FF).
// Each byte N was treated as Unicode code point N (= Latin-1 value) and encoded as UTF-8.
// So the mojibake of char U+00XY (UTF-8 bytes [B1, B2]) is:
//   mb_chr(B1) . mb_chr(B2)  (each byte interpreted as a Unicode code point)
$from = [];
$to   = [];

for ($byte = 0x80; $byte <= 0xFF; $byte++) {
    $correct    = mb_chr($byte, 'UTF-8');         // e.g., 'ã' for 0xE3
    $utf8Bytes  = array_values(unpack('C*', $correct)); // raw UTF-8 bytes
    $moji       = '';
    foreach ($utf8Bytes as $b) {
        $moji .= mb_chr($b, 'UTF-8');             // byte value → Unicode code point → UTF-8 char
    }
    if ($moji !== $correct) {
        $from[] = $moji;
        $to[]   = $correct;
    }
}

// Handle 3-byte char mojibake (Windows-1252 0x80 = € U+20AC used for UTF-8 0x80 byte).
// • U+2022 (E2 80 A2) → â (U+00E2) + € (U+20AC) + ¢ (U+00A2) = "â€¢"
$from[] = "\xC3\xA2\xE2\x82\xAC\xC2\xA2"; $to[] = "\xE2\x80\xA2"; // â€¢ → •
$from[] = "\xC3\xA2\xE2\x82\xAC\xC2\x9C"; $to[] = "\xE2\x80\x9C"; // â€œ → "
$from[] = "\xC3\xA2\xE2\x82\xAC\xC2\x9D"; $to[] = "\xE2\x80\x9D"; // â€  → "
$from[] = "\xC3\xA2\xE2\x82\xAC\xC2\x99"; $to[] = "\xE2\x80\x99"; // â€™ → '
$from[] = "\xC3\xA2\xE2\x82\xAC\xC2\x93"; $to[] = "\xE2\x80\x93"; // â€" → –
$from[] = "\xC3\xA2\xE2\x82\xAC\xC2\x94"; $to[] = "\xE2\x80\x94"; // â€" → —

$totalFixed = 0;

foreach ($files as $file) {
    if (!file_exists($file)) {
        echo "NOT FOUND: $file\n";
        continue;
    }

    $original = file_get_contents($file);
    $fixed    = str_replace($from, $to, $original);

    if ($fixed !== $original) {
        file_put_contents($file, $fixed);
        echo "FIXED  : " . str_replace($projectRoot . '/', '', $file) . "\n";
        $totalFixed++;
    } else {
        echo "ok     : " . str_replace($projectRoot . '/', '', $file) . "\n";
    }
}

echo "\n$totalFixed file(s) updated.\n";
