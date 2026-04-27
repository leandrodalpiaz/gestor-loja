# Padrao de Mensagens ao Usuario Final

## Objetivo

Garantir comunicacao clara, natural e operacional em Web ERP, Miniapps e Bot Telegram.

## Principios

- Use linguagem direta e objetiva.
- Comece por acao quando fizer sentido: `Confirme`, `Envie`, `Revise`, `Tente novamente`.
- Evite termos tecnicos internos, stack trace e siglas sem contexto.
- Mantenha o mesmo tom entre modulos: claro, respeitoso e orientado a tarefa.

## Padroes por tipo

- Sucesso: confirme resultado e, se preciso, o proximo passo.
  - Ex.: `Livro cadastrado com sucesso.`
- Erro de validacao: diga o campo e como corrigir.
  - Ex.: `Informe o titulo da obra para continuar.`
- Erro operacional: descreva impacto e oriente acao imediata.
  - Ex.: `Nao foi possivel concluir agora. Tente novamente em instantes.`
- Sem dados: informe estado atual sem tom de falha.
  - Ex.: `Nenhum registro encontrado para hoje.`
- Acesso negado: explique permissao necessaria sem expor regra interna.
  - Ex.: `Este recurso esta disponivel apenas para perfis autorizados.`

## Frases a evitar

- `Falha na operacao`
- `Erro ao salvar`
- `Erro desconhecido`
- `Pagto teste`
- qualquer mensagem de debug, TODO ou texto provisiorio visivel ao usuario

## Regra de fallback

Quando nao houver mensagem especifica do backend:

- Use `Nao foi possivel concluir esta acao agora. Tente novamente em instantes.`
- Se houver contexto de modulo, prefira:
  - `Nao foi possivel carregar os dados deste painel agora. Tente novamente em instantes.`

## Padrao especifico para Bot Telegram

- Mensagem de abertura de painel deve ter:
  - nome do painel
  - instrucao curta de proximo passo
- Mensagem de acesso negado deve citar perfil funcional, sem detalhe tecnico.
- Sempre manter um caminho de retorno (ex.: `Voltar` ou `/painel`).

Exemplos:

- `Painel da Secretaria. Selecione uma opcao para continuar.`
- `Acesso restrito ao Orador, Veneravel Mestre ou Administrador.`

## Padrao especifico para Miniapps

- Em erros de API: contexto + acao.
- Evitar mensagens vagas como `Falha na operacao`.

Exemplos:

- `Nao foi possivel salvar o balaustre agora. Revise os dados e tente novamente.`
- `Nao foi possivel carregar o dashboard agora. Tente novamente em instantes.`

## Textos para IA de atendimento

- Responder em 3 passos:
  1) estado atual
  2) acao recomendada
  3) alternativa se falhar
- Proibido expor SQLSTATE, stack trace, nome de tabela ou termo de infraestrutura.
