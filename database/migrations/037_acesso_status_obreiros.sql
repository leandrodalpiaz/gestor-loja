DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'acesso_status_enum') THEN
        CREATE TYPE public.acesso_status_enum AS ENUM ('pendente', 'ativo', 'inativo');
    END IF;
END $$;

ALTER TABLE public.obreiros
    ADD COLUMN IF NOT EXISTS acesso_status public.acesso_status_enum;

UPDATE public.obreiros
SET acesso_status = CASE
    WHEN ativo = TRUE THEN 'ativo'::public.acesso_status_enum
    ELSE 'inativo'::public.acesso_status_enum
END
WHERE acesso_status IS NULL;

