# Padrão de Mensagens ao Usuário Final

## Objetivo

Garantir comunicacao clara, natural e operacional em Web ERP, Miniapps e Bot Telegram.

## Principios

- Use linguagem direta e objetiva.
- Comece por ação quando fizer sentido: `Confirme`, `Envie`, `Revise`, `Tente novamente`.
- Evite termos técnicos internos, stack trace e siglas sem contexto.
- Mantenha o mesmo tom entre módulos: claro, respeitoso e orientado a tarefa.

## Padroes por tipo

- Sucesso: confirme resultado e, se preciso, o próximo passo.
  - Ex.: `Livro cadastrado com sucesso.`
- Erro de validação: diga o campo e como corrigir.
  - Ex.: `Informe o título da obra para continuar.`
- Erro operacional: descreva impacto e oriente ação imediata.
  - Ex.: `Não foi possivel concluir agora. Tente novamente em instantes.`
- Sem dados: informe estado atual sem tom de falha.
  - Ex.: `Nenhum registro encontrado para hoje.`
- Acesso negado: explique permissão necessaria sem expor regra interna.
  - Ex.: `Este recurso esta disponivel apenas para perfis autorizados.`

## Frases a evitar

- `Falha na operação`
- `Erro ao salvar`
- `Erro desconhecido`
- `Pagto teste`
- qualquer mensagem de debug, TODO ou texto provisiorio visível ao usuário

## Regra de fallback

Quando não houver mensagem especifica do backend:

- Use `Não foi possivel concluir esta ação agora. Tente novamente em instantes.`
- Se houver contexto de módulo, prefira:
  - `Não foi possivel carregar os dados deste painel agora. Tente novamente em instantes.`

## Padrão especifico para Bot Telegram

- Mensagem de abertura de painel deve ter:
  - nome do painel
  - instrucao curta de próximo passo
- Mensagem de acesso negado deve citar perfil funcional, sem detalhe técnico.
- Sempre manter um caminho de retorno (ex.: `Voltar` ou `/painel`).

Exemplos:

- `Painel da Secretaria. Selecione uma opcao para continuar.`
- `Acesso restrito ao Orador, Veneravel Mestre ou Administrador.`

## Padrão especifico para Miniapps

- Em erros de API: contexto + ação.
- Evitar mensagens vagas como `Falha na operação`.

Exemplos:

- `Não foi possivel salvar o balaústre agora. Revise os dados e tente novamente.`
- `Não foi possivel carregar o dashboard agora. Tente novamente em instantes.`

## Textos para IA de atendimento

- Responder em 3 passos:
  1) estado atual
  2) ação recomendada
  3) alternativa se falhar
- Proibido expor SQLSTATE, stack trace, nome de tabela ou termo de infraestrutura.
