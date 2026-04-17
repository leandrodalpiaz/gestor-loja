-- 035_assistencia_chancelaria_multitenant.sql
-- Fecha o tenant nos modulos de Assistencia e Chancelaria.

ALTER TABLE public.ocorrencias_assistenciais
    ADD COLUMN IF NOT EXISTS loja_id INTEGER REFERENCES public.lojas(id);

UPDATE public.ocorrencias_assistenciais oa
SET loja_id = o.loja_id
FROM public.obreiros o
WHERE o.id = oa.obreiro_id
  AND oa.loja_id IS NULL;

UPDATE public.ocorrencias_assistenciais
SET loja_id = (
    SELECT id FROM public.lojas ORDER BY id LIMIT 1
)
WHERE loja_id IS NULL;

ALTER TABLE public.ocorrencias_assistenciais
    ALTER COLUMN loja_id SET NOT NULL;

CREATE INDEX IF NOT EXISTS ix_ocorrencias_assistenciais_loja_id
    ON public.ocorrencias_assistenciais (loja_id);

ALTER TABLE public.efemerides_registros
    ADD COLUMN IF NOT EXISTS loja_id INTEGER REFERENCES public.lojas(id);

UPDATE public.efemerides_registros
SET loja_id = (
    SELECT id FROM public.lojas ORDER BY id LIMIT 1
)
WHERE loja_id IS NULL;

ALTER TABLE public.efemerides_registros
    ALTER COLUMN loja_id SET NOT NULL;

CREATE INDEX IF NOT EXISTS ix_efemerides_registros_loja_id
    ON public.efemerides_registros (loja_id);

ALTER TABLE public.historias_maconicas
    ADD COLUMN IF NOT EXISTS loja_id INTEGER REFERENCES public.lojas(id);

UPDATE public.historias_maconicas
SET loja_id = (
    SELECT id FROM public.lojas ORDER BY id LIMIT 1
)
WHERE loja_id IS NULL;

ALTER TABLE public.historias_maconicas
    ALTER COLUMN loja_id SET NOT NULL;

DROP INDEX IF EXISTS idx_historias_maconicas_unico;
CREATE UNIQUE INDEX IF NOT EXISTS idx_historias_maconicas_unico
    ON public.historias_maconicas (loja_id, mes, dia, titulo);

ALTER TABLE public.palavras_dia
    ADD COLUMN IF NOT EXISTS loja_id INTEGER REFERENCES public.lojas(id);

UPDATE public.palavras_dia
SET loja_id = (
    SELECT id FROM public.lojas ORDER BY id LIMIT 1
)
WHERE loja_id IS NULL;

ALTER TABLE public.palavras_dia
    ALTER COLUMN loja_id SET NOT NULL;

CREATE INDEX IF NOT EXISTS ix_palavras_dia_loja_id
    ON public.palavras_dia (loja_id);

ALTER TABLE public.conteudo_categoria_permissoes
    ADD COLUMN IF NOT EXISTS loja_id INTEGER REFERENCES public.lojas(id);

UPDATE public.conteudo_categoria_permissoes
SET loja_id = (
    SELECT id FROM public.lojas ORDER BY id LIMIT 1
)
WHERE loja_id IS NULL;

ALTER TABLE public.conteudo_categoria_permissoes
    ALTER COLUMN loja_id SET NOT NULL;

DROP INDEX IF EXISTS idx_conteudo_categoria_permissoes_unico;
CREATE UNIQUE INDEX IF NOT EXISTS idx_conteudo_categoria_permissoes_unico
    ON public.conteudo_categoria_permissoes (loja_id, categoria, cargo_slug);

ALTER TABLE public.mensagens_complementares
    ADD COLUMN IF NOT EXISTS loja_id INTEGER REFERENCES public.lojas(id);

UPDATE public.mensagens_complementares
SET loja_id = (
    SELECT id FROM public.lojas ORDER BY id LIMIT 1
)
WHERE loja_id IS NULL;

ALTER TABLE public.mensagens_complementares
    ALTER COLUMN loja_id SET NOT NULL;

CREATE INDEX IF NOT EXISTS ix_mensagens_complementares_loja_id
    ON public.mensagens_complementares (loja_id, tipo);

ALTER TABLE public.mensagens_rotacao_historico
    ADD COLUMN IF NOT EXISTS loja_id INTEGER REFERENCES public.lojas(id);

UPDATE public.mensagens_rotacao_historico
SET loja_id = (
    SELECT id FROM public.lojas ORDER BY id LIMIT 1
)
WHERE loja_id IS NULL;

ALTER TABLE public.mensagens_rotacao_historico
    ALTER COLUMN loja_id SET NOT NULL;

ALTER TABLE public.mensagens_rotacao_historico
    DROP CONSTRAINT IF EXISTS mensagens_rotacao_historico_tipo_key;

CREATE UNIQUE INDEX IF NOT EXISTS ux_mensagens_rotacao_historico_loja_tipo
    ON public.mensagens_rotacao_historico (loja_id, tipo);

CREATE INDEX IF NOT EXISTS ix_mensagens_rotacao_historico_loja_id
    ON public.mensagens_rotacao_historico (loja_id);
