<?php

require_once __DIR__ . '/../src/autoload.php';

use App\Config\Env;
use App\Models\EfemerideRegistro;
use App\Models\EfemeridePreviaDiaria;
use App\Services\EfemeridesComposer;

Env::load(__DIR__ . '/../.env');

$registroModel = new EfemerideRegistro();
$composer = new EfemeridesComposer();
$previaModel = new EfemeridePreviaDiaria();

$registrosHoje = $registroModel->getRegistrosDoDia();
$mensagemBase = $composer->composeDailyPreview($registrosHoje);

$ok = $previaModel->prepararAutomaticaDoDia($mensagemBase);

if (!$ok) {
    fwrite(STDERR, "Falha ao preparar prévia diária.\n");
    exit(1);
}

echo "Prévia diária preparada com sucesso.\n";
