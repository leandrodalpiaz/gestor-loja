<?php

require_once __DIR__ . '/../src/autoload.php';

use App\Config\Database;
use App\Config\Env;

Env::load(__DIR__ . '/../.env');

$apply = in_array('--apply', $argv, true);
$limit = 0;
$reportPath = '';
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--limit=')) {
        $limit = max(0, (int) substr($arg, 8));
    }
    if (str_starts_with($arg, '--report=')) {
        $reportPath = trim((string) substr($arg, 9));
    }
}

$db = Database::getConnection();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$resolveLojaId = static function () use ($db): int {
    $raw = trim((string) ($_ENV['APP_DEFAULT_TENANT_ID'] ?? $_ENV['APP_LOJA_ID'] ?? ''));
    if ($raw !== '' && ctype_digit($raw)) {
        return (int) $raw;
    }
    // fallback: first loja
    return (int) ($db->query("SELECT id FROM lojas ORDER BY id ASC LIMIT 1")->fetchColumn() ?: 0);
};

$lojaId = $resolveLojaId();
if ($lojaId <= 0) {
    fwrite(STDERR, "Nao foi possivel resolver loja_id.\n");
    exit(1);
}

// Efemerides_registros: regra usada aqui
// - cod_vinculo (vide EfemerideRegistro::VINCULOS_PADRAO)
//   2=esposa, 3=filha, 4=filho, 5=enteada, 6=enteado
// - parentesco: referencia ao irmao (nome) quando houver vinculo
$sql = "
    SELECT id, nome, data_evento, cod_vinculo, vinculo, parentesco
    FROM efemerides_registros
    WHERE loja_id = :loja_id
      AND ativo = true
      AND cod_vinculo IN (2, 3, 4, 5, 6)
      AND COALESCE(TRIM(parentesco), '') <> ''
";
if ($limit > 0) {
    $sql .= " ORDER BY id ASC LIMIT " . (int) $limit;
} else {
    $sql .= " ORDER BY id ASC";
}

$stmt = $db->prepare($sql);
$stmt->execute(['loja_id' => $lojaId]);
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$normalizar = static function (string $texto): string {
    $texto = trim($texto);
    $texto = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto) ?: $texto;
    $texto = strtolower($texto);
    $texto = preg_replace('/[^a-z0-9]+/', '', $texto) ?? '';
    return $texto;
};

$isGenericRef = static function (string $normRef): bool {
    // Alguns registros antigos guardam apenas "Irmão" / "Irmao" como referência.
    return in_array($normRef, ['irmao', 'irma'], true);
};

$tokenize = static function (string $texto): array {
    $texto = trim($texto);
    $texto = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto) ?: $texto;
    $texto = strtolower($texto);
    $texto = preg_replace('/[^a-z0-9 ]+/', ' ', $texto) ?? '';
    $tokens = preg_split('/\s+/', trim($texto)) ?: [];
    $tokens = array_values(array_filter($tokens, static fn ($t) => $t !== '' && $t !== 'irmao'));
    return $tokens;
};

$mapParentesco = static function (int $cod): ?string {
    return match ($cod) {
        2 => 'esposa',
        3 => 'filha',
        4 => 'filho',
        5 => 'filha', // enteada -> regra geral de filhos
        6 => 'filho', // enteado -> regra geral de filhos
        default => null,
    };
};

$resolved = 0;
$skipped = 0;
$inserted = 0;
$updatedObreiros = 0;
$unmatched = [];

try {
    if ($apply) {
        $db->beginTransaction();
    }

    foreach ($registros as $r) {
        $refIrmao = trim((string) ($r['parentesco'] ?? ''));
        $normRef = $normalizar($refIrmao);
        if ($normRef === '' || $isGenericRef($normRef)) {
            $skipped++;
            continue;
        }

        $cod = (int) ($r['cod_vinculo'] ?? 0);
        $parentesco = $mapParentesco($cod);
        if ($parentesco === null) {
            $skipped++;
            continue;
        }

        $nomeFamiliar = trim((string) ($r['nome'] ?? ''));
        if ($nomeFamiliar === '') {
            $skipped++;
            continue;
        }

        // Importante: evitar "unaccent()" pois pode nao existir e aborta transacao no Postgres.
        $obreiroStmt = $db->prepare("
            SELECT id, nome, nome_historico
            FROM public.obreiros
            WHERE loja_id = :loja_id
              AND ativo = true
              AND (
                regexp_replace(lower(coalesce(nome_historico, '')), '[^a-z0-9]+', '', 'g') = :ref
                OR regexp_replace(lower(coalesce(nome, '')), '[^a-z0-9]+', '', 'g') = :ref
              )
            LIMIT 1
        ");
        $obreiroStmt->execute(['loja_id' => $lojaId, 'ref' => $normRef]);
        $obreiro = $obreiroStmt->fetch(PDO::FETCH_ASSOC) ?: null;

        if (!$obreiro) {
            // Fallback: match "contém" com guardrails (evitar falsos positivos).
            // Regra: se tokens >= 2, usar os 2 maiores tokens; se tokens == 1, tentar por token único,
            // e aceitar apenas se houver exatamente 1 match.
            $tokens = $tokenize($refIrmao);
            if (count($tokens) > 0) {
                usort($tokens, static fn ($a, $b) => strlen($b) <=> strlen($a));
                $tokens = array_slice($tokens, 0, min(2, count($tokens)));

                $whereParts = [];
                $params = ['loja_id' => $lojaId];
                foreach ($tokens as $idx => $t) {
                    $whereParts[] = "(lower(coalesce(nome_historico, '')) LIKE :t{$idx} OR lower(coalesce(nome, '')) LIKE :t{$idx})";
                    $params["t{$idx}"] = '%' . $t . '%';
                }
                $sqlFallback = "
                    SELECT id, nome, nome_historico
                    FROM public.obreiros
                    WHERE loja_id = :loja_id
                      AND ativo = true
                      AND " . implode(' AND ', $whereParts) . "
                    ORDER BY nome ASC
                    LIMIT 2
                ";
                $fbStmt = $db->prepare($sqlFallback);
                $fbStmt->execute($params);
                $matches = $fbStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                if (count($matches) === 1) {
                    $obreiro = $matches[0];
                }
            }
        }

        if (!$obreiro) {
            $unmatched[] = ['efemeride_id' => (int) $r['id'], 'irmao_ref' => $refIrmao, 'familiar' => $nomeFamiliar];
            continue;
        }

        $resolved++;
        $obreiroId = (string) $obreiro['id'];

        // Inserir em familiares_obreiro se existir
        if ($apply) {
            $existsStmt = $db->prepare("
                SELECT 1
                FROM public.familiares_obreiro
                WHERE loja_id = :loja_id
                  AND obreiro_id = :obreiro_id
                  AND lower(nome_completo) = lower(:nome)
                  AND parentesco = :parentesco
                LIMIT 1
            ");
            $existsStmt->execute([
                'loja_id' => $lojaId,
                'obreiro_id' => $obreiroId,
                'nome' => $nomeFamiliar,
                'parentesco' => $parentesco,
            ]);
            $exists = (bool) $existsStmt->fetchColumn();

            if (!$exists) {
                $ins = $db->prepare("
                    INSERT INTO public.familiares_obreiro (
                        loja_id, obreiro_id, nome_completo, parentesco, data_nascimento, falecido, status_revisao
                    ) VALUES (
                        :loja_id, :obreiro_id, :nome, :parentesco, :data_nascimento, false, 'pendente'
                    )
                ");
                $ins->execute([
                    'loja_id' => $lojaId,
                    'obreiro_id' => $obreiroId,
                    'nome' => $nomeFamiliar,
                    'parentesco' => $parentesco,
                    'data_nascimento' => $r['data_evento'] ?? null,
                ]);
                $inserted++;
            }

            // Display de familia deve vir de familiares_obreiro (nao de colunas em obreiros).
        }
    }

    if ($apply) {
        $db->commit();
    }
} catch (Throwable $e) {
    if ($apply && $db->inTransaction()) {
        $db->rollBack();
    }
    $efId = isset($r) ? (int) ($r['id'] ?? 0) : 0;
    fwrite(STDERR, "ERRO (efemeride_id={$efId}): " . $e->getMessage() . "\n");
    exit(1);
}

echo "Loja: {$lojaId}\n";
echo "Efemerides analisadas: " . count($registros) . "\n";
echo "Resolvidas por obreiro: {$resolved}\n";
echo "Inseridas em familiares_obreiro: {$inserted}\n";
echo "Atualizacoes em obreiros: {$updatedObreiros}\n";
echo "Nao casadas: " . count($unmatched) . "\n";
if (!$apply) {
    echo "Modo: DRY-RUN (use --apply para aplicar)\n";
}
if (count($unmatched) > 0) {
    echo "Exemplos nao casados:\n";
    foreach (array_slice($unmatched, 0, 10) as $u) {
        echo "- efemeride_id={$u['efemeride_id']} irmao_ref=\"{$u['irmao_ref']}\" familiar=\"{$u['familiar']}\"\n";
    }
}

if ($reportPath !== '' && count($unmatched) > 0) {
    $dir = dirname($reportPath);
    if ($dir !== '' && $dir !== '.' && !is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    $fh = fopen($reportPath, 'wb');
    if ($fh) {
        fputcsv($fh, ['efemeride_id', 'irmao_ref', 'familiar']);
        foreach ($unmatched as $u) {
            fputcsv($fh, [$u['efemeride_id'], $u['irmao_ref'], $u['familiar']]);
        }
        fclose($fh);
        echo "Relatorio: {$reportPath}\n";
    } else {
        fwrite(STDERR, "Aviso: nao foi possivel escrever relatorio em {$reportPath}\n");
    }
}
