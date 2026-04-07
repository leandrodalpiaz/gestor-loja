<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/autoload.php';
\App\Config\Env::load(__DIR__ . '/../.env');

$db = \App\Config\Database::getConnection();

$normalizeExpr = "lower(regexp_replace(translate(%s, 'áàâãäéèêëíìîïóòôõöúùûüçÁÀÂÃÄÉÈÊËÍÌÎÏÓÒÔÕÖÚÙÛÜÇ', 'aaaaaeeeeiiiiooooouuuucAAAAAEEEEIIIIOOOOOUUUUC'), '[^a-z0-9]+', '', 'g'))";
$efNomeKey = sprintf($normalizeExpr, "e.nome");
$obNomeKey = sprintf($normalizeExpr, "COALESCE(o.nome_historico, o.nome)");

// 1) Snapshot previo.
$before = (int) $db->query("SELECT COUNT(*) FROM public.obreiros")->fetchColumn();

// 2) Consolida os irmaos a partir das efemerides.
$sqlInsert = "
WITH base AS (
    SELECT
        e.nome,
        {$efNomeKey} AS nome_key,
        e.tipo,
        e.data_evento,
        e.cod_vinculo,
        LOWER(COALESCE(e.vinculo, '')) AS vinculo_norm
    FROM public.efemerides_registros e
    WHERE e.ativo = TRUE
      AND COALESCE(TRIM(e.nome), '') <> ''
      AND (
        e.tipo IN ('Iniciação', 'Elevação', 'Exaltação', 'Instalação')
        OR (e.tipo = 'Aniversário' AND (e.cod_vinculo = 1 OR LOWER(COALESCE(e.vinculo, '')) LIKE 'irm%'))
      )
),
irmaos AS (
    SELECT
        nome_key,
        MIN(nome) AS nome_ref,
        MIN(CASE WHEN tipo = 'Aniversário' THEN data_evento END) AS dt_nascimento,
        MIN(CASE WHEN tipo = 'Iniciação' THEN data_evento END) AS dt_iniciacao,
        MIN(CASE WHEN tipo = 'Elevação' THEN data_evento END) AS dt_elevacao,
        MIN(CASE WHEN tipo = 'Exaltação' THEN data_evento END) AS dt_exaltacao,
        MIN(CASE WHEN tipo = 'Instalação' THEN data_evento END) AS dt_instalacao
    FROM base
    GROUP BY nome_key
),
faltantes AS (
    SELECT i.*
    FROM irmaos i
    LEFT JOIN public.obreiros o
      ON {$obNomeKey} = i.nome_key
    WHERE o.id IS NULL
)
INSERT INTO public.obreiros (
    nome,
    nome_historico,
    grau,
    data_nascimento_civil,
    data_iniciacao,
    data_elevacao,
    data_exaltacao,
    ativo,
    observacao_secretaria
)
SELECT
    f.nome_ref,
    f.nome_ref,
    CASE
        WHEN f.dt_instalacao IS NOT NULL THEN 'Mestre Instalado'
        WHEN f.dt_exaltacao IS NOT NULL THEN 'Mestre'
        WHEN f.dt_elevacao IS NOT NULL THEN 'Companheiro'
        WHEN f.dt_iniciacao IS NOT NULL THEN 'Aprendiz'
        ELSE 'Aprendiz'
    END AS grau_calc,
    f.dt_nascimento,
    f.dt_iniciacao,
    f.dt_elevacao,
    f.dt_exaltacao,
    TRUE,
    'Cadastro gerado automaticamente a partir de efemerides.'
FROM faltantes f
RETURNING id";

$insertedRows = $db->query($sqlInsert)->fetchAll(PDO::FETCH_ASSOC);
$inserted = count($insertedRows);

// 3) Atualiza obreiros existentes com dados faltantes + grau inferido.
$sqlUpdate = "
WITH base AS (
    SELECT
        e.nome,
        {$efNomeKey} AS nome_key,
        e.tipo,
        e.data_evento,
        e.cod_vinculo,
        LOWER(COALESCE(e.vinculo, '')) AS vinculo_norm
    FROM public.efemerides_registros e
    WHERE e.ativo = TRUE
      AND COALESCE(TRIM(e.nome), '') <> ''
      AND (
        e.tipo IN ('Iniciação', 'Elevação', 'Exaltação', 'Instalação')
        OR (e.tipo = 'Aniversário' AND (e.cod_vinculo = 1 OR LOWER(COALESCE(e.vinculo, '')) LIKE 'irm%'))
      )
),
irmaos AS (
    SELECT
        nome_key,
        MIN(CASE WHEN tipo = 'Aniversário' THEN data_evento END) AS dt_nascimento,
        MIN(CASE WHEN tipo = 'Iniciação' THEN data_evento END) AS dt_iniciacao,
        MIN(CASE WHEN tipo = 'Elevação' THEN data_evento END) AS dt_elevacao,
        MIN(CASE WHEN tipo = 'Exaltação' THEN data_evento END) AS dt_exaltacao,
        MIN(CASE WHEN tipo = 'Instalação' THEN data_evento END) AS dt_instalacao
    FROM base
    GROUP BY nome_key
),
upd AS (
    UPDATE public.obreiros o
    SET
        data_nascimento_civil = COALESCE(o.data_nascimento_civil, i.dt_nascimento),
        data_iniciacao = COALESCE(o.data_iniciacao, i.dt_iniciacao),
        data_elevacao = COALESCE(o.data_elevacao, i.dt_elevacao),
        data_exaltacao = COALESCE(o.data_exaltacao, i.dt_exaltacao),
        grau = CASE
            WHEN i.dt_instalacao IS NOT NULL THEN 'Mestre Instalado'
            WHEN i.dt_exaltacao IS NOT NULL THEN 'Mestre'
            WHEN i.dt_elevacao IS NOT NULL THEN 'Companheiro'
            WHEN i.dt_iniciacao IS NOT NULL THEN 'Aprendiz'
            ELSE o.grau
        END
    FROM irmaos i
    WHERE {$obNomeKey} = i.nome_key
    RETURNING o.id
)
SELECT COUNT(*) FROM upd";

$updated = (int) $db->query($sqlUpdate)->fetchColumn();

$after = (int) $db->query("SELECT COUNT(*) FROM public.obreiros")->fetchColumn();

echo "OBREIROS_ANTES: {$before}" . PHP_EOL;
echo "INSERIDOS: {$inserted}" . PHP_EOL;
echo "ATUALIZADOS: {$updated}" . PHP_EOL;
echo "OBREIROS_DEPOIS: {$after}" . PHP_EOL;
