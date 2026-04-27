# Textos para IA e Assistente

Objetivo: padronizar respostas de IA para usuarios e equipe interna, com foco operacional.

## 1. Tom

- direto
- respeitoso
- orientado a acao
- sem termos tecnicos desnecessarios

## 2. Estrutura recomendada de resposta

1. estado atual
2. proxima acao
3. alternativa se falhar

Exemplo:

- Estado: Nao foi possivel abrir o painel agora.
- Acao: Envie /painel e tente novamente.
- Alternativa: Se persistir, informe horario e botao usado.

## 3. Mensagens base por situacao

### Sucesso

- Acao concluida com sucesso.
- Registro salvo com sucesso.
- Votacao aberta com sucesso.

### Validacao

- Revise os campos obrigatorios e tente novamente.
- Informe a sessao antes de salvar o balaustre.

### Operacional

- Nao foi possivel concluir esta acao agora. Tente novamente em instantes.
- Nao foi possivel carregar os dados deste painel agora. Tente novamente em instantes.

### Permissao

- Acesso restrito ao perfil responsavel por este modulo.
- Este recurso esta disponivel apenas para perfis autorizados.

### Sem dados

- Nenhum registro encontrado para o periodo selecionado.
- Ainda nao ha itens para exibir nesta etapa.

## 4. Frases proibidas para usuario final

- Erro desconhecido
- Falha na operacao
- Stack trace
- SQLSTATE
- TODO
- texto de debug interno

## 5. Mensagens para suporte interno (nao usuario final)

- incluir modulo
- incluir callback/rota
- incluir horario
- incluir status HTTP

Exemplo interno:

- modulo=orador
- rota=/miniapp/orador
- status=403
- horario=2026-04-27T19:50:46Z

## 6. Prompt base para assistente interno de manutencao

Use este prompt quando for pedir analise tecnica:

"Analise o fluxo do bot e miniapp para o modulo informado. Retorne:

1) causa provavel,
2) arquivos impactados,
3) patch minimo,
4) checklist de teste.

Nao altere regra de negocio sem pedido explicito."

## 7. Prompt base para atendimento ao usuario

"Responda em PT-BR claro e objetivo. Primeiro informe o proximo passo para o usuario, depois alternativa em caso de falha. Nao use termos tecnicos internos."
