-- Fase 0: Isolamento de Ambientes no Supabase
-- Script Base: Criação dos Schemas de Produção, Homologação e Desenvolvimento
-- ATENÇÃO: Execute este script com o usuário 'postgres' (admin).

-- 1. Criação dos schemas
CREATE SCHEMA IF NOT EXISTS app_prod;
CREATE SCHEMA IF NOT EXISTS app_homolog;
CREATE SCHEMA IF NOT EXISTS app_dev;

-- 2. Revogar acesso público aos novos schemas
REVOKE ALL ON SCHEMA app_prod FROM public;
REVOKE ALL ON SCHEMA app_homolog FROM public;
REVOKE ALL ON SCHEMA app_dev FROM public;

-- 3. (Opcional) Criação de Roles Dedicadas por Ambiente
-- Para isolamento perfeito, recomenda-se criar roles que só acessam um schema específico.
-- Exemplo:
-- CREATE ROLE role_prod NOLOGIN;
-- GRANT USAGE ON SCHEMA app_prod TO role_prod;
-- GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA app_prod TO role_prod;

-- CREATE ROLE role_homolog NOLOGIN;
-- GRANT USAGE ON SCHEMA app_homolog TO role_homolog;
-- GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA app_homolog TO role_homolog;

-- Observação: A migração dos dados e tabelas do schema "public" atual para o 
-- schema "app_prod" não é feita automaticamente aqui para evitar perda de dados.
-- O schema "public" atual pode ser renomeado para "app_prod" (se suportado sem quebrar FKs)
-- ou os dados exportados via pg_dump e restaurados no novo schema.
