# Textos para IA e Assistente

Objetivo: padronizar respostas de IA para usuários e equipe interna, com foco operacional.

## 1. Tom

- direto
- respeitoso
- orientado à ação
- sem termos técnicos desnecessários

## 2. Estrutura recomendada de resposta

1. estado atual
2. próxima ação
3. alternativa se falhar

Exemplo:

- Estado: Não foi possível abrir o painel agora.
- Ação: Envie /painel e tente novamente.
- Alternativa: Se persistir, informe horário e botão usado.

## 3. Mensagens base por situação

### Sucesso

- Ação concluída com sucesso.
- Registro salvo com sucesso.
- Votação aberta com sucesso.

### Validação

- Revise os campos obrigatórios e tente novamente.
- Informe a sessão antes de salvar o balaústre.

### Operacional

- Não foi possível concluir esta ação agora. Tente novamente em instantes.
- Não foi possível carregar os dados deste painel agora. Tente novamente em instantes.

### Permissão

- Acesso restrito ao perfil responsável por este módulo.
- Este recurso esta disponível apenas para perfis autorizados.

### Sem dados

- Nenhum registro encontrado para o período selecionado.
- Ainda não há itens para exibir nesta etapa.

## 4. Frases proibidas para usuário final

- Erro desconhecido
- Falha na operação
- Stack trace
- SQLSTATE
- TODO
- texto de debug interno

## 5. Mensagens para suporte interno (não usuário final)

- incluir módulo
- incluir callback/rota
- incluir horário
- incluir status HTTP

Exemplo interno:

- módulo=orador
- rota=/miniapp/orador
- status=403
- horário=2026-04-27T19:50:46Z

## 6. Prompt base para assistente interno de manutenção

Use este prompt quando for pedir análise técnica:

"Análise o fluxo do bot e miniapp para o módulo informado. Retorne:

1) causa provável,
2) arquivos impactados,
3) patch mínimo,
4) checklist de teste.

Não altere regra de negócio sem pedido explícito."

## 7. Prompt base para atendimento ao usuário

"Responda em PT-BR claro e objetivo. Primeiro informe o próximo passo para o usuário, depois alternativa em caso de falha. Não use termos técnicos internos."
