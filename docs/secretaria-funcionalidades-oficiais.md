# Secretaria — Funcionalidades Oficiais (Web Desktop + PWA)

## Objetivo

Documento de referência para evitar alterações indevidas na seção `Secretaria`, preservando:
- central operacional da Loja;
- padrão UX minimalista já adotado;
- separação de competências entre Secretaria e Venerável/Admin.

## Escopo oficial da Secretaria (Web)

Rotas e funções esperadas (referência; a fonte de verdade de permissão é `PermissionMap.php`):
- `/secretaria`: cockpit operacional com pendências e atalhos.
- sessões (criar/editar/publicar/cancelar/reabrir), balaústres, trabalhos/publicações, obreiros e acessos.
- convites e comunicação/conteúdo público quando sob responsabilidade da Secretaria.
- relatórios anuais/gestão quando existentes no módulo.

## Regras funcionais obrigatórias

1. Não alterar lógica de cargos.
2. Não misturar competências:
   - Secretaria organiza, prepara, publica e distribui.
   - Venerável/Admin delibera em fluxos restritos (ex.: votação de balaústre).
3. Sessões publicadas devem abastecer agenda/painel dos obreiros.
4. Relatórios devem consolidar dados sem exigir duplicação de cadastro.

## Mobile (PWA)

- O canal principal no mobile é a PWA.
- As rotas PWA devem respeitar `secretaria.manage`.
- Não depender do Telegram para paridade operacional.

## Guardrails de UX (sem reestilização)

- Manter padrão visual atual (CSS/layout).
- Não introduzir menus profundos ou ruído visual.
- Não mover funções para fora da seção Secretaria sem decisão explícita.

## Mudanças que exigem validação prévia

- Qualquer mudança de permissão por cargo.
- Qualquer remoção de rota da Secretaria.
- Qualquer alteração no fluxo de votação de balaústre.
