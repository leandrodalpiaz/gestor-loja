-- Fase 0: Clonagem da estrutura do schema "public" para o novo schema isolado (ex: "app_homolog")
-- Este script PL/pgSQL percorre as tabelas, tipos e sequências no schema public
-- e gera comandos DDL para criar uma cópia vazia (sem dados) no schema de destino.

-- ATENÇÃO: Dependendo das permissões do Supabase, ferramentas como pg_dump/pg_restore 
-- (com a flag -s para schemas) costumam ser a forma mais segura e correta para isso.
-- Este script serve como uma automação básica se a CLI não estiver disponível.

DO $$
DECLARE
    target_schema CONSTANT TEXT := 'app_homolog'; -- Mude para app_dev, etc.
    source_schema CONSTANT TEXT := 'public';
    row RECORD;
    v_sql TEXT;
BEGIN
    -- Cria o schema caso não exista
    EXECUTE format('CREATE SCHEMA IF NOT EXISTS %I', target_schema);

    -- 1. Clonar tabelas (estrutura apenas)
    FOR row IN
        SELECT tablename 
        FROM pg_tables 
        WHERE schemaname = source_schema
    LOOP
        v_sql := format('CREATE TABLE IF NOT EXISTS %I.%I (LIKE %I.%I INCLUDING ALL)', 
                        target_schema, row.tablename, source_schema, row.tablename);
        EXECUTE v_sql;
        RAISE NOTICE 'Tabela clonada: %', row.tablename;
    END LOOP;

    -- Nota: Funções, Triggers e Views mais complexas precisam ser recriadas manualmente
    -- ou importadas via pg_dump -s -n public > schema.sql e depois aplicadas no novo schema.
    
    RAISE NOTICE 'Clonagem estrutural básica finalizada para o schema %', target_schema;
END $$;
