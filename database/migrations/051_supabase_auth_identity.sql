-- Vincula identidades do Supabase Auth a registros UUID reais do ERP.
-- A conta abaixo e exclusivamente tecnica e nao representa um obreiro pessoal.

ALTER TABLE public.obreiros
    ADD COLUMN IF NOT EXISTS auth_user_id UUID REFERENCES auth.users(id) ON DELETE SET NULL,
    ADD COLUMN IF NOT EXISTS is_system_admin BOOLEAN NOT NULL DEFAULT FALSE;

CREATE UNIQUE INDEX IF NOT EXISTS ux_obreiros_auth_user_id
    ON public.obreiros (auth_user_id)
    WHERE auth_user_id IS NOT NULL;

DO $$
DECLARE
    v_auth_user_id UUID := '643257ad-f7f7-4af9-9741-aeb0b10203d5';
    v_email TEXT := 'lsdalpiaz@gmail.com';
    v_loja_id INTEGER;
BEGIN
    IF NOT EXISTS (SELECT 1 FROM auth.users WHERE id = v_auth_user_id) THEN
        RAISE EXCEPTION 'Usuario Supabase Auth % nao encontrado', v_auth_user_id;
    END IF;

    SELECT id INTO v_loja_id
    FROM public.lojas
    ORDER BY id
    LIMIT 1;

    IF v_loja_id IS NULL THEN
        RAISE EXCEPTION 'Nenhuma loja cadastrada para vincular a conta tecnica';
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM public.obreiros WHERE auth_user_id = v_auth_user_id
    ) THEN
        INSERT INTO public.obreiros (
            nome,
            nome_historico,
            grau,
            email,
            ativo,
            situacao_quadro,
            acesso_status,
            loja_id,
            excluir_em_listas,
            auth_user_id,
            is_system_admin
        ) VALUES (
            'Administrador Tecnico',
            'Administrador Tecnico',
            '3',
            v_email,
            TRUE,
            'ativo',
            'ativo',
            v_loja_id,
            TRUE,
            v_auth_user_id,
            TRUE
        );
    ELSE
        UPDATE public.obreiros
        SET is_system_admin = TRUE,
            ativo = TRUE,
            acesso_status = 'ativo',
            excluir_em_listas = TRUE,
            updated_at = NOW()
        WHERE auth_user_id = v_auth_user_id;
    END IF;
END $$;
