-- 043_configuracoes_loja_pk_multitenant_fix.sql
-- Remove legado id=1 fixo para permitir uma configuracao por loja.

ALTER TABLE public.configuracoes_loja
    DROP CONSTRAINT IF EXISTS configuracoes_loja_id_check;

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.sequences
        WHERE sequence_schema = 'public'
          AND sequence_name = 'configuracoes_loja_id_seq'
    ) THEN
        CREATE SEQUENCE public.configuracoes_loja_id_seq;
    END IF;
END $$;

SELECT setval(
    'public.configuracoes_loja_id_seq',
    COALESCE((SELECT MAX(id) FROM public.configuracoes_loja), 1),
    true
);

ALTER TABLE public.configuracoes_loja
    ALTER COLUMN id SET DEFAULT nextval('public.configuracoes_loja_id_seq');
