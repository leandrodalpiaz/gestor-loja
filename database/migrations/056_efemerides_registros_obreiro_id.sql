-- Liga efemerides_registros ao obreiro real (obreiro_id), em vez de depender
-- só de correspondência por nome em texto livre. Isso permite que a
-- inativação/exclusão de um obreiro desative de forma confiável tanto os
-- eventos próprios (aniversário, iniciação, elevação, exaltação, instalação)
-- quanto os eventos de familiares vinculados a ele.

ALTER TABLE public.efemerides_registros
    ADD COLUMN IF NOT EXISTS obreiro_id UUID NULL REFERENCES public.obreiros(id) ON DELETE SET NULL;

CREATE INDEX IF NOT EXISTS idx_efemerides_registros_obreiro_id
    ON public.efemerides_registros(obreiro_id);

-- Backfill: eventos do próprio obreiro (casamento por nome normalizado).
UPDATE public.efemerides_registros e
SET obreiro_id = o.id
FROM public.obreiros o
WHERE e.obreiro_id IS NULL
  AND e.loja_id = o.loja_id
  AND (
    e.tipo IN ('Iniciação', 'Elevação', 'Exaltação', 'Instalação')
    OR (e.tipo = 'Aniversário' AND (e.cod_vinculo = 1 OR LOWER(COALESCE(e.vinculo, '')) LIKE 'irm%'))
  )
  AND lower(regexp_replace(translate(e.nome,
        'áàâãäéèêëíìîïóòôõöúùûüçÁÀÂÃÄÉÈÊËÍÌÎÏÓÒÔÕÖÚÙÛÜÇ',
        'aaaaaeeeeiiiiooooouuuucAAAAAEEEEIIIIOOOOOUUUUC'), '[^a-z0-9]+', '', 'g'))
      = lower(regexp_replace(translate(COALESCE(o.nome_historico, o.nome),
        'áàâãäéèêëíìîïóòôõöúùûüçÁÀÂÃÄÉÈÊËÍÌÎÏÓÒÔÕÖÚÙÛÜÇ',
        'aaaaaeeeeiiiiooooouuuucAAAAAEEEEIIIIOOOOOUUUUC'), '[^a-z0-9]+', '', 'g'));

-- Backfill (reforço): eventos próprios de registros legados sem
-- vinculo/cod_vinculo preenchido (parentesco = 'Irmão' ou vazio).
UPDATE public.efemerides_registros e
SET obreiro_id = o.id
FROM public.obreiros o
WHERE e.obreiro_id IS NULL
  AND e.loja_id = o.loja_id
  AND e.tipo IN ('Iniciação', 'Elevação', 'Exaltação', 'Instalação', 'Aniversário',
                 'Concessão de Membro Honorário', 'Filiação', 'Posse Grão Mestre')
  AND (COALESCE(TRIM(e.parentesco), '') = '' OR lower(TRIM(e.parentesco)) IN ('irmao', 'irmão'))
  AND lower(regexp_replace(translate(e.nome,
        'áàâãäéèêëíìîïóòôõöúùûüçÁÀÂÃÄÉÈÊËÍÌÎÏÓÒÔÕÖÚÙÛÜÇ',
        'aaaaaeeeeiiiiooooouuuucAAAAAEEEEIIIIOOOOOUUUUC'), '[^a-z0-9]+', '', 'g'))
      = lower(regexp_replace(translate(COALESCE(o.nome_historico, o.nome),
        'áàâãäéèêëíìîïóòôõöúùûüçÁÀÂÃÄÉÈÊËÍÌÎÏÓÒÔÕÖÚÙÛÜÇ',
        'aaaaaeeeeiiiiooooouuuucAAAAAEEEEIIIIOOOOOUUUUC'), '[^a-z0-9]+', '', 'g'));

-- Backfill: eventos de familiares (o campo parentesco guarda o nome do
-- obreiro ao qual o familiar está vinculado).
UPDATE public.efemerides_registros e
SET obreiro_id = o.id
FROM public.obreiros o
WHERE e.obreiro_id IS NULL
  AND e.loja_id = o.loja_id
  AND COALESCE(TRIM(e.parentesco), '') <> ''
  AND lower(regexp_replace(translate(e.parentesco,
        'áàâãäéèêëíìîïóòôõöúùûüçÁÀÂÃÄÉÈÊËÍÌÎÏÓÒÔÕÖÚÙÛÜÇ',
        'aaaaaeeeeiiiiooooouuuucAAAAAEEEEIIIIOOOOOUUUUC'), '[^a-z0-9]+', '', 'g'))
      = lower(regexp_replace(translate(COALESCE(o.nome_historico, o.nome),
        'áàâãäéèêëíìîïóòôõöúùûüçÁÀÂÃÄÉÈÊËÍÌÎÏÓÒÔÕÖÚÙÛÜÇ',
        'aaaaaeeeeiiiiooooouuuucAAAAAEEEEIIIIOOOOOUUUUC'), '[^a-z0-9]+', '', 'g'));
