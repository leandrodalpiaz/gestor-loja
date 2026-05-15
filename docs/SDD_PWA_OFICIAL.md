# SDD Oficial — Mobile PWA-first e Web Desktop-first

Este documento consolida a diretriz vigente do projeto:
- Web: **desktop-first** para gestão completa.
- Mobile: **PWA é a experiência principal**.
- Telegram: **secundário** (baixo engajamento), usado como complemento/atalhos quando fizer sentido.

## Objetivo

Manter um ERP web consistente e seguro, preservando regras de negócio e multi-tenant, com operação mobile efetiva via PWA.

## Princípios de UI/UX

- **Desktop-first (gestão):** sidebar fixa, tabelas ricas, densidade operacional.
- **Mobile PWA (operação):** listas operacionais em cards, status como badge forte, sem scroll horizontal.
- Não depender de Telegram para paridade: quando existir integração, deve ser um atalho/deeplink para a PWA.

## Guardrails (não quebrar produção)

- Não alterar `.env`, tokens/credenciais e integrações produtivas sem decisão explícita.
- Não remover tabelas ou recriar estrutura do zero; mudanças de banco devem ser aditivas.
- Lógica de Tesouraria já homologada: evitar alterações em transações/cálculos sem validação dirigida.

## Arquitetura e pontos de verdade

- RBAC/rotas: `src/Core/Authorization/PermissionMap.php`
- Web dispatch/guards: `src/Core/Http/PainelRoutes.php`, `src/Core/Http/WebGuards.php`, `src/Core/Http/ModuleGuards.php`
- PWA: rotas `/pwa/*` e controllers `src/Controllers/Pwa*Controller.php` quando existirem

## Roadmap recomendado (alto nível)

1. Consolidar por cargo o que é “somente Desktop” vs “obrigatório no PWA”.
2. Para cada fluxo PWA:
   - garantir permissão correta,
   - garantir persistência e mensagens de bloqueio,
   - garantir UI mobile sem tabela larga.
3. Manter Telegram apenas como complemento (atalhos/notificações), sem duplicar regra de negócio.
