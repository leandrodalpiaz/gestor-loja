# SDD Oficial — Migração Telegram Bot → PWA (Fase 0 obrigatória)

## Summary
Migrar, por fases e com rollback, todas as interações hoje feitas no Telegram Bot para Web/PWA, preservando 100% das regras de negócio e multi-tenant. Telegram passa a ser um adapter opcional (notificação/deeplink/captura), e a fonte única de verdade é o sistema web (PHP + Tailwind + Supabase).

## UI Principle
Operacional mobile-first; administrativo desktop-first responsivo.

## Fase 0 (obrigatória antes de qualquer PWA)
1) Congelar ponto atual
- Commit limpo + tag `pre-pwa-freeze-YYYYMMDD`
- Branch de backup para rollback rápido
- Backup fora do git: `.env`, `render.yaml`, `docker-compose.yml`

2) Isolamento no mesmo Supabase
- Schemas: `app_prod`, `app_homolog`, `app_dev`
- Roles/usuários separados por ambiente, com acesso apenas ao schema do ambiente
- Script base: `database/phase0_isolation.sql`

3) Guardrails no código
- `APP_ENV` governa integrações
- `TELEGRAM_DRY_RUN` (default seguro): em não-produção não envia Telegram real
- Feature flags por domínio: `FEATURE_PWA_*`

4) Rollback testado
- Software: desligar feature flags e validar fluxo antigo/bot
- Dados: ter estratégia de restore (PITR/backup lógico) testada em `app_homolog`

## Migração funcional (após Fase 0)
1) Sessões: presença, ágape, justificativa
2) Biblioteca
3) Comunicação oficial
4) CRUDs administrativos

