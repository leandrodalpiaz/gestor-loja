-- Phase 0: Isolamento por schema + roles no mesmo projeto Supabase
-- Execute no SQL Editor do Supabase (como owner/admin do projeto).
--
-- Objetivo:
-- - Criar schemas por ambiente: app_prod, app_homolog, app_dev
-- - Criar roles de aplicação por ambiente com acesso APENAS ao seu schema
-- - Definir search_path default para cada role (opcional, mas recomendado)
--
-- Ajuste os nomes/segredos antes de rodar.

begin;

create schema if not exists app_prod;
create schema if not exists app_homolog;
create schema if not exists app_dev;

-- Roles (não-login) para agrupar permissões
do $$
begin
  if not exists (select 1 from pg_roles where rolname = 'role_prod_app') then
    create role role_prod_app;
  end if;
  if not exists (select 1 from pg_roles where rolname = 'role_homolog_app') then
    create role role_homolog_app;
  end if;
  if not exists (select 1 from pg_roles where rolname = 'role_dev_app') then
    create role role_dev_app;
  end if;
end $$;

-- Usuários de aplicação (login). Troque as senhas.
do $$
begin
  if not exists (select 1 from pg_roles where rolname = 'user_prod_app') then
    create role user_prod_app login password 'TROQUE_ESTA_SENHA';
  end if;
  if not exists (select 1 from pg_roles where rolname = 'user_homolog_app') then
    create role user_homolog_app login password 'TROQUE_ESTA_SENHA';
  end if;
  if not exists (select 1 from pg_roles where rolname = 'user_dev_app') then
    create role user_dev_app login password 'TROQUE_ESTA_SENHA';
  end if;
end $$;

grant role_prod_app to user_prod_app;
grant role_homolog_app to user_homolog_app;
grant role_dev_app to user_dev_app;

-- Permissões por schema: uso + objetos futuros
grant usage on schema app_prod to role_prod_app;
grant usage on schema app_homolog to role_homolog_app;
grant usage on schema app_dev to role_dev_app;

alter default privileges in schema app_prod grant select, insert, update, delete on tables to role_prod_app;
alter default privileges in schema app_homolog grant select, insert, update, delete on tables to role_homolog_app;
alter default privileges in schema app_dev grant select, insert, update, delete on tables to role_dev_app;

alter default privileges in schema app_prod grant usage, select, update on sequences to role_prod_app;
alter default privileges in schema app_homolog grant usage, select, update on sequences to role_homolog_app;
alter default privileges in schema app_dev grant usage, select, update on sequences to role_dev_app;

-- Opcional: search_path padrão por role (reduz risco de cross-schema)
alter role role_prod_app set search_path = app_prod, public;
alter role role_homolog_app set search_path = app_homolog, public;
alter role role_dev_app set search_path = app_dev, public;

commit;

