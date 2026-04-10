-- 002_seed_cargos.sql
-- Seed idempotente (pode rodar mais de uma vez)

INSERT INTO public.cargos (codigo, nome_exibicao, ativo)
VALUES
  ('ORADOR', 'Orador', TRUE),
  ('GUARDA_DA_LEI', 'Guarda da Lei', TRUE),
  ('MESTRE_BANQUETES', 'Mestre de Banquetes', TRUE),
  ('TESOUREIRO', 'Tesoureiro', TRUE),
  ('SECRETARIO', 'Secretario', TRUE),
  ('VENERAVEL', 'Veneravel Mestre', TRUE),
  ('PRIMEIRO_VIGILANTE', '1º Vigilante', TRUE),
  ('SEGUNDO_VIGILANTE', '2º Vigilante', TRUE),
  ('ADMINISTRADOR', 'Administrador', TRUE),
  ('BIBLIOTECARIO', 'Bibliotecário', TRUE),
  ('CHANCELER', 'Chanceler', TRUE),
  ('HOSPITALEIRO', 'Hospitaleiro', TRUE),
  ('PRIMEIRO_DIACONO', '1º Diacono', TRUE),
  ('SEGUNDO_DIACONO', '2º Diacono', TRUE),
  ('MESTRE_DE_CERIMONIAS', 'Mestre de Cerimonias', TRUE),
  ('ARQUITETO', 'Arquiteto', TRUE),
  ('PORTA_ESTANDARTE', 'Porta-Estandarte', TRUE),
  ('PORTA_ESPADA', 'Porta-Espada', TRUE),
  ('GUARDA_DO_TEMPLO', 'Guarda do Templo', TRUE),
  ('PRIMEIRO_EXPERTO', '1º Experto', TRUE),
  ('SEGUNDO_EXPERTO', '2º Experto', TRUE),
  ('COBRIDOR', 'Cobridor', TRUE),
  ('MESTRE_DE_HARMONIA', 'Mestre de Harmonia', TRUE)
ON CONFLICT (codigo) DO UPDATE
SET
  nome_exibicao = EXCLUDED.nome_exibicao,
  ativo = EXCLUDED.ativo;
