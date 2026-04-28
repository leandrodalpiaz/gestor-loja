-- Clona estrutura do schema `public` para `app_homolog`.
-- Execute no Supabase SQL Editor após phase0_isolation.sql.

do $$
declare
  target_schema text := 'app_homolog';
  src_schema text := 'public';
  rec record;
begin
  execute format('create schema if not exists %I', target_schema);

  for rec in
    select sequence_name
    from information_schema.sequences
    where sequence_schema = src_schema
  loop
    execute format('create sequence if not exists %I.%I', target_schema, rec.sequence_name);
  end loop;

  for rec in
    select tablename
    from pg_tables
    where schemaname = src_schema
      and tablename not like 'pg_%'
  loop
    execute format(
      'create table if not exists %I.%I (like %I.%I including all)',
      target_schema, rec.tablename, src_schema, rec.tablename
    );
  end loop;

  for rec in
    select
      n.nspname as seq_schema,
      s.relname as seq_name,
      tn.nspname as tbl_schema,
      t.relname as tbl_name,
      a.attname as col_name
    from pg_class s
    join pg_namespace n on n.oid = s.relnamespace
    join pg_depend d on d.objid = s.oid and d.deptype = 'a'
    join pg_class t on t.oid = d.refobjid
    join pg_namespace tn on tn.oid = t.relnamespace
    join pg_attribute a on a.attrelid = t.oid and a.attnum = d.refobjsubid
    where n.nspname = src_schema
      and s.relkind = 'S'
      and t.relkind = 'r'
  loop
    execute format(
      'alter sequence if exists %I.%I owned by %I.%I.%I',
      target_schema, rec.seq_name, target_schema, rec.tbl_name, rec.col_name
    );
  end loop;
end $$;
