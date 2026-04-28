# Rollout PWA em Produção (checklist seguro)

Este checklist assume a estratégia oficial do projeto:
- Mesmo projeto Supabase
- Schemas por ambiente (`app_prod`, `app_homolog`, `app_dev`)
- App aponta para o schema via `DB_SCHEMA`
- Telegram vira adapter (deeplink/notificação), sem lógica principal
- Feature flags por domínio (`FEATURE_PWA_*`)

## 0) Freeze / rollback
- Confirmar tag `pre-pwa-freeze-20260428` existe e referencia o último ponto estável.
- Confirmar branch `backup/pre-pwa-freeze-20260428` existe.
- Backup fora do git: `.env`, `render.yaml`, `docker-compose.yml`
  - Script: `powershell -File scripts/phase0_backup_files.ps1 -Label pre-pwa-rollout`

## 1) Banco (Supabase)
1. Rodar `database/phase0_isolation.sql` (cria schemas/roles/usuários).
2. Rodar `database/phase0_clone_public_to_schema.sql` 3x:
   - `target_schema = app_dev`
   - `target_schema = app_homolog`
   - `target_schema = app_prod`
3. Rodar `database/phase2_comunicacao.sql` no schema alvo (no mínimo `app_prod` e `app_homolog`).
4. Validar RLS/tenant/rbac:
   - sem cross-tenant por `loja_id/tenant_id`
   - roles de app sem acesso a schemas fora do ambiente

## 2) Config (produção)
- `APP_ENV=production`
- `DB_SCHEMA=app_prod`
- `TELEGRAM_DRY_RUN=false` (somente quando for habilitar notificações reais)
- Feature flags (habilitar de forma incremental):
  - `FEATURE_PWA_SESSOES=true`
  - `FEATURE_PWA_BIBLIOTECA=true`
  - `FEATURE_PWA_COMUNICACAO=true`
  - `FEATURE_PWA_ADMIN_CRUD=true`

## 3) Smoke test (produção)
- Abrir `/pwa` em mobile e instalar PWA.
- Fluxos mínimos:
  - Sessões: confirmar presença / marcar ausência.
  - Biblioteca: solicitar empréstimo / ver meus empréstimos.
  - Comunicação: criar comunicado (se perfil tiver permissão) e ler.
- Telegram:
  - Botão “Abrir PWA” abre `/pwa`
  - Botões por feature abrem rotas PWA correspondentes

## 4) Rollback rápido
- Software: desligar `FEATURE_PWA_*` e validar fluxo antigo.
- Código: `git checkout backup/pre-pwa-freeze-20260428` (se necessário) e redeploy.
- Dados: restaurar via PITR/backup lógico conforme runbook.

