# AGENTS.md - Gestor-Loja

## Contexto do projeto
- ERP interno para Loja Maconica.
- Stack atual: PHP 8.2 server-rendered, Apache/PHP local, Tailwind CSS, Supabase Postgres.
- Uso predominante em celular; o front-end deve ser mobile-first.
- O projeto ja esta operacional localmente e preparado para futura hospedagem.
- Integracoes com Telegram e miniapps existem e nao devem ser quebradas.

## Regras de front-end
- Priorizar telas pequenas primeiro.
- Nao assumir desktop como layout principal.
- Em mobile, preferir cards a tabelas.
- Evitar scroll horizontal.
- Formularios longos devem ser divididos em secoes claras.
- Priorizar acoes principais e progressive disclosure.
- Nao criar reescritas amplas sem pedir antes.
- Fazer mudancas pequenas, seguras e revisaveis.

## Padrao de listas administrativas
- Em telas mobile, preferir cards empilhados para listas operacionais.
- Em md+ pode ser mantida tabela se ela ja estiver estavel.
- Evitar overflow horizontal como solucao principal.
- Status deve aparecer como badge visual forte.
- A acao principal deve ficar visivel no card sem exigir expansao.

## Regras de arquitetura
- Preservar PHP server-rendered.
- Usar Tailwind ja existente.
- Nao introduzir framework front-end novo.
- Nao quebrar rotas existentes.
- Nao assumir lista fechada de cargos.
- Nao converter IDs de obreiro para int; o banco real usa UUID.

## Fluxo esperado
- Para tarefas complexas, planeje antes de implementar.
- Antes de editar, liste arquivos impactados.
- Depois de editar, resuma o diff e proponha validacao local.
