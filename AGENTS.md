# AGENTS.md - Gestor-Loja

## Contexto do projeto
- ERP interno para Loja Maçônica.
- Stack atual: PHP 8.2 server-rendered, Tailwind CSS, Supabase Postgres.
- Layout desktop-first com adaptação mobile inteligente.
- O bot Telegram é o canal principal para uso mobile frequente; o painel web não compete com ele.
- O projeto está operacional localmente (php -S) e preparado para deploy no Render via Docker.
- Integrações com Telegram e miniapps existem e não devem ser quebradas.

## Regras de front-end
- Desktop-first: telas de gestão completa priorizam desktop com sidebar fixa e tabelas ricas.
- Mobile inteligente: telas de consulta e ação rápida funcionam bem em mobile com cards.
- Em mobile, preferir cards a tabelas.
- Evitar scroll horizontal em qualquer breakpoint.
- Formulários longos divididos em etapas com estado via sessão PHP.
- Priorizar ações principais e progressive disclosure.
- Toda mudança visual deve reforçar aparência de ERP, não de site institucional.
- Não criar reescritas amplas sem pedir antes.
- Fazer mudanças pequenas, seguras e revisáveis.

## Padrão de listas administrativas
- Em telas mobile, preferir cards empilhados para listas operacionais.
- Em md+ pode ser mantida tabela se ela já estiver estável.
- Evitar overflow horizontal como solucao principal.
- Status deve aparecer como badge visual forte.
- A ação principal deve ficar visível no card sem exigir expansao.

## Regras de arquitetura
- Preservar PHP server-rendered.
- Usar Tailwind já existente.
- Não introduzir framework front-end novo.
- Não quebrar rotas existentes.
- Não assumir lista fechada de cargos.
- Não converter IDs de obreiro para int; o banco real usa UUID.

## Fluxo esperado
- Para tarefas complexas, planeje antes de implementar.
- Antes de editar, liste arquivos impactados.
- Depois de editar, resuma o diff e proponha validação local.
