# Secretaria — Funcionalidades Oficiais (Web Desktop + PWA)

## Objetivo
Documento de referência para evitar alterações indevidas na seção `Secretaria`, preservando:
- central operacional da Loja;
- padrão UX minimalista já adotado;
- separação de competências entre Secretaria e Venerável/Admin.

## Escopo oficial da Secretaria
Rotas e funções obrigatórias:
- `/secretaria`: cockpit operacional com pendências e atalhos.
- `/secretaria/sessoes`: criação, edição, publicação, cancelamento e reabertura de sessões (sem duplicar sessão interna).
- `/secretaria/balaustres`: redação completa em blocos oficiais, inclusive sem sessão vinculada.
- `/secretaria/votacao`: acompanhamento de votação; abertura/encerramento continuam com Venerável/Admin.
- `/secretaria/trabalhos-publicacoes`: registro operacional de trabalhos, peças e publicações.
- `/secretaria/convites-externos`: gestão de convites de terceiros, anexos e presença.
- `/secretaria/nominata`: apoio à composição oficial de cargos.
- `/secretaria/acessos`: apoio operacional de acessos.
- `/secretaria/conteudo-publico`: publicações institucionais.
- `/secretaria/relatorio-anual`: relatório anual.
- `/secretaria/relatorio-gestao`: relatório consolidado de gestão.

## Regras funcionais obrigatórias
1. Não alterar lógica de cargos.
2. Não misturar competências:
   - Secretaria organiza, prepara, publica e distribui.
   - Venerável/Admin delibera em fluxos restritos (ex.: votação de balaústre).
3. Balaústre deve permitir:
   - redação com sessão vinculada (consumindo dados existentes);
   - redação independente (sem sessão), com possibilidade de ajustes manuais.
4. Sessões publicadas devem abastecer agenda/painel dos obreiros.
5. Convites externos:
   - cadastro manual com ou sem anexo;
   - anexo em cache no banco com remoção pela UI;
   - presença confirmada/cancelada e lista de confirmados consultável.
6. Relatórios devem consolidar dados sem exigir duplicação de cadastro.

## Guardrails de UX (sem reestilização)
- Manter padrão visual atual (CSS/layout).
- Não introduzir menus profundos ou ruído visual.
- Não mover funções para fora da seção Secretaria sem decisão explícita.
- Priorizar páginas funcionais por domínio (sessões, balaústres, convites, relatórios).

## Alterações que exigem validação prévia
- Qualquer mudança de permissão por cargo.
- Qualquer remoção de rota da Secretaria.
- Qualquer alteração do fluxo de votação de balaústre.
- Qualquer mudança que quebre o consumo de dados por outros cargos.
