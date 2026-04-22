-- Conteudos publicos exibidos na landing/login (/login)
-- Uso: agenda/convites/noticias e slots discretos de publicidade (sem scripts externos)

CREATE TABLE IF NOT EXISTS public.conteudos_publicos (
    id BIGSERIAL PRIMARY KEY,
    tipo VARCHAR(32) NOT NULL,
    titulo VARCHAR(180) NOT NULL,
    resumo VARCHAR(320) NULL,
    corpo TEXT NULL,
    link_url TEXT NULL,
    imagem_url TEXT NULL,
    prioridade INT NOT NULL DEFAULT 0,
    publicado BOOLEAN NOT NULL DEFAULT TRUE,
    inicio_em DATE NULL,
    fim_em DATE NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_conteudos_publicos_publicado_tipo
    ON public.conteudos_publicos (publicado, tipo);

CREATE INDEX IF NOT EXISTS idx_conteudos_publicos_prioridade
    ON public.conteudos_publicos (prioridade DESC, created_at DESC);

