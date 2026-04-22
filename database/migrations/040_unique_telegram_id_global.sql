-- 040_unique_telegram_id_global.sql
-- telegram_id unico global (uma conta Telegram = uma identidade).

CREATE UNIQUE INDEX IF NOT EXISTS ux_obreiros_telegram_id
    ON public.obreiros (telegram_id)
    WHERE telegram_id IS NOT NULL;

