-- 002_seed_cargos.sql
-- Seed idempotente (pode rodar mais de uma vez)

INSERT INTO public.cargos (codigo, nome_exibicao, ativo)
VALUES
  ('MESTRE_BANQUETES', 'Mestre de Banquetes', TRUE),
  ('TESOUREIRO', 'Tesoureiro', TRUE),
  ('VENERAVEL', 'Venerável', TRUE),
  ('ADMINISTRADOR', 'Administrador', TRUE),
  ('BIBLIOTECARIO', 'Bibliotecário', TRUE),
  ('CHANCELER', 'Chanceler', TRUE)
ON CONFLICT (codigo) DO UPDATE
SET
  nome_exibicao = EXCLUDED.nome_exibicao,
  ativo = EXCLUDED.ativo;
