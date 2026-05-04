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
- Clonar estrutura (sem dados) do `public` para o schema do ambiente: `database/phase0_clone_public_to_schema.sql`

3) Guardrails no código
- `APP_ENV` governa integrações
- `TELEGRAM_DRY_RUN` (default seguro): em não-produção não envia Telegram real
- Feature flags por domínio: `FEATURE_PWA_*`
- Guardrail de segurança: `APP_ENV!=production` não pode usar `DB_SCHEMA=app_prod`

4) Rollback testado
- Software: desligar feature flags e validar fluxo antigo/bot
- Dados: ter estratégia de restore (PITR/backup lógico) testada em `app_homolog`

## Migração funcional (após Fase 0)
1) Sessões: presença, ágape, justificativa
2) Biblioteca
3) Comunicação oficial
4) CRUDs administrativos

---

### ⚠️ CLÁUSULA DE RISCO ZERO (RESTRIÇÕES ABSOLUTAS PARA O AGENTE)

Para garantir a estabilidade do sistema que já se encontra operacional e hospedado no Render, o agente responsável pela execução do código deve seguir obrigatoriamente as seguintes restrições estruturais:

* **Infraestrutura Intocável:** É estritamente proibido alterar o arquivo `.env`, as chaves de API, tokens do Telegram, ou credenciais do Supabase. O ambiente Docker e os scripts de *long polling/webhook* originais não devem sofrer modificações arquiteturais.
* **Banco de Dados Preservado:** Nenhuma tabela existente deve ser apagada (`DROP`) ou recriada do zero. Toda transição para o modelo Multi-tenant deve ocorrer de forma aditiva (incremental), preservando os dados e a estrutura de logins vigentes.
* **Lógica de Negócio Blindada (Tesouraria):** As regras do *back-end* do Tesoureiro já foram homologadas. O agente não tem permissão para alterar a lógica de transações atômicas, cálculos, ou rotas de aprovação/cancelamento financeiro (`TesourariaApiRoutes.php`).
* **Escopo Fechado de Atuação:** A refatoração deve focar **exclusivamente na camada de apresentação** (Views em Blade/PHP, classes do Tailwind CSS, comportamento responsivo, `manifest.json` do PWA) e na blindagem de sessões no PHP para o correto controle de acesso (RBAC).

A quebra de qualquer uma destas restrições será considerada uma falha crítica na execução do prompt.

---
