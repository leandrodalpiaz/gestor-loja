# Documento de Especificação Arquitetural (SDD) - ERP Gestor Lojas

**Objetivo:** orientar arquitetura, permissões e UI do ERP para Lojas Maçônicas, preservando PHP server-rendered e o fluxo mobile via PWA.

## 1. Stack tecnológica e princípios base

- **Back-end:** PHP 8.2 (server-rendered) com gestão de estado via sessão.
- **Front-end:** Tailwind CSS (sem introdução de frameworks JS).
- **Banco:** Supabase (PostgreSQL) com UUIDs nativos.
- **Infraestrutura:** manter compatibilidade com Docker e scripts de long polling/webhook.
- **Canal principal (mobile):** PWA.
- **Telegram:** canal secundário (atalhos/notificações), sem regra principal.

## 2. Multi-tenant e identidade dinâmica

- **Isolamento de dados:** toda consulta deve aplicar filtro por `loja_id` (UUID) amarrado à sessão do usuário.
- **Configuração da Loja:** tabela/configuração dita comportamento visual (nome, logo, cores) por tenant.
- **Manifesto/ícones:** suporte a manifesto dinâmico quando existir (ex.: `manifest.php`) e ícones por tenant.

## 3. Controle de acesso (RBAC)

Princípios:
- permissão é por Authorizer/Guards (não por “if cargo” em view);
- “admin técnico” é separado de cargos oficiais.

Referências:
- `src/Core/Authorization/PermissionMap.php`
- `src/Core/Authorization/Authorizer.php`
- `src/Core/Http/WebGuards.php` e `src/Core/Http/ModuleGuards.php`

## 4. Diretrizes de interface e UI/UX

- **Desktop-first:** manter tabelas ricas e navegação lateral estável.
- **Mobile (Mini App):** cards empilhados, ações principais visíveis, sem overflow horizontal como solução padrão.
- **Progressive disclosure:** priorizar ações principais e esconder o restante atrás de expansão/etapas quando necessário.
- Evitar reescritas amplas; mudanças devem ser pequenas, seguras e revisáveis.

## 5. Vernacular (“Maçonês” discreto)

Texto em português-BR formal e minimalista. Mensagens de erro técnicas não devem vazar para o usuário.
