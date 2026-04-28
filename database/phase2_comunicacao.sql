-- Comunicação Oficial (fase 2/3)
-- Execute no schema do ambiente (ex.: app_dev/app_homolog/app_prod) após definir `search_path`.
-- Requer: tabela `lojas` e `obreiros` já existentes no schema.

begin;

create table if not exists comunicados (
  id bigserial primary key,
  loja_id integer not null,
  tenant_id uuid null,
  titulo text not null,
  conteudo text not null,
  categoria text not null default 'geral',
  publicado boolean not null default true,
  publicado_em timestamptz not null default now(),
  criado_por uuid null,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

create index if not exists comunicados_loja_id_idx on comunicados(loja_id);
create index if not exists comunicados_publicado_em_idx on comunicados(publicado_em desc);

create table if not exists comunicados_leituras (
  id bigserial primary key,
  comunicado_id bigint not null references comunicados(id) on delete cascade,
  obreiro_id uuid not null,
  lido_em timestamptz not null default now(),
  unique (comunicado_id, obreiro_id)
);

create index if not exists comunicados_leituras_obreiro_id_idx on comunicados_leituras(obreiro_id);

commit;

