-- 041_assistente_telemetria.sql
-- Telemetria de comandos curtos do assistente IA (web e mobile).

CREATE TABLE IF NOT EXISTS public.assistente_telemetria (
    id BIGSERIAL PRIMARY KEY,
    canal VARCHAR(24) NOT NULL DEFAULT 'web',
    comando_original TEXT,
    comando_normalizado VARCHAR(280),
    intent VARCHAR(120) NOT NULL,
    confidence NUMERIC(5,4),
    allowed BOOLEAN NOT NULL DEFAULT FALSE,
    action_target VARCHAR(255),
    user_id UUID NULL,
    tenant_id UUID NULL,
    metadata JSONB NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_assistente_telemetria_created_at
    ON public.assistente_telemetria (created_at DESC);

CREATE INDEX IF NOT EXISTS idx_assistente_telemetria_intent
    ON public.assistente_telemetria (intent);

CREATE INDEX IF NOT EXISTS idx_assistente_telemetria_allowed
    ON public.assistente_telemetria (allowed);

CREATE INDEX IF NOT EXISTS idx_assistente_telemetria_user
    ON public.assistente_telemetria (user_id);

CREATE INDEX IF NOT EXISTS idx_assistente_telemetria_tenant
    ON public.assistente_telemetria (tenant_id);
