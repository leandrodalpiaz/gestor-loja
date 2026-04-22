-- 039_unique_cim_por_loja.sql
-- CIM unico por loja (multi-tenant). Nao aplica para cim nulo/vazio.

CREATE UNIQUE INDEX IF NOT EXISTS ux_obreiros_loja_id_cim
    ON public.obreiros (loja_id, cim)
    WHERE cim IS NOT NULL AND cim <> '';

