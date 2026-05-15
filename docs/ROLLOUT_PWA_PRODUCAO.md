# Rollout PWA em Produção (checklist seguro)

Estratégia atual:
- Web é a base (gestão desktop-first).
- **PWA é a experiência principal no mobile**.
- Telegram é secundário (atalhos/notificações), não é o canal principal.

## 0) Freeze / rollback

- Garantir um ponto estável com tag/branch de backup antes de mudanças relevantes.
- Backup fora do git: `.env`, `render.yaml`, `docker-compose.yml`.

## 1) Banco (Supabase)

- Validar RLS/tenant por `loja_id` e ausência de cross-tenant.
- Se houver schemas por ambiente (`app_prod`, `app_homolog`, `app_dev`), confirmar que:
  - produção aponta para `app_prod`;
  - homologação aponta para `app_homolog`;
  - nenhum job/teste escreve em schema de produção.

## 2) Config (produção)

- `APP_ENV=production`
- `DB_SCHEMA=app_prod` (se aplicável ao setup atual)
- `TELEGRAM_DRY_RUN=false` somente quando for habilitar envio real.

## 3) Smoke test (produção)

- Web:
  - `GET /health` → 200
  - `GET /login` → 200
- PWA:
  - abrir `/pwa` em mobile e instalar (quando aplicável);
  - fluxos mínimos por domínio (sessões, biblioteca, comunicação, etc.).
- Telegram (secundário, se usado):
  - botões/deeplinks abrem rotas PWA correspondentes;
  - logs sem 403 indevido e sem `SQLSTATE` recorrente.

## 4) Rollback rápido

- Software: desligar/voltar feature flags (quando existirem) e validar fluxo anterior.
- Código: voltar para a tag/branch de backup e redeploy.
- Dados: restaurar via PITR/backup lógico conforme runbook do projeto.
