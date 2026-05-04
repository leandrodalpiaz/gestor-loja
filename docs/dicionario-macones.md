# Dicionario Mestre de Linguagem - Gestor Loja

Este dicionario define a padronizacao de linguagem visivel ao usuario no sistema.

## Regras gerais

- Aplicar apenas em textos visiveis (labels, titulos, mensagens e botoes).
- Nao alterar rotas, nomes internos, tabelas, colunas, variaveis, enums tecnicos ou chaves de status.
- Manter termos tecnicos quando forem de infraestrutura: `webhook`, `long polling`, `API`, `healthcheck`, `miniapp`, `URL`, `login tecnico`, `token`, `Chat ID`.

## Substituicoes contextuais

| Termo base | Termo em macones | Regra de contexto | Exemplo |
|---|---|---|---|
| Usuario / Usuarios | Obreiro / Obreiros | Administrativo e sistema | "Adicionar obreiro" |
| Membro / Membros | Irmao / Irmaos | Fraterno e convivio | "Mensagem aos irmaos" |
| Membro / Membros | Obreiro / Obreiros | Administrativo e gestao | "Registro de obreiros" |
| Dashboard | Painel | Navegacao e titulos | "Voltar ao Painel" |
| Dashboard geral | Painel da Loja | Visao principal | "Painel da Loja" |
| Financeiro | Tesouraria | Modulo e textos de gestao | "Relatorio da Tesouraria" |
| Pagamento / Pagamentos | Contribuicao / Contribuicoes | Cobranca e baixa | "Contribuicao confirmada" |
| Mensalidade / Mensalidades | Contribuicao mensal / Contribuicoes mensais | Obrigações recorrentes | "Gerar contribuicoes mensais" |
| Caixa | Caixa da Loja | Fluxo de caixa visivel | "Movimentacao do Caixa da Loja" |
| Receita | Entrada | Relatorios e lancamentos | "Entradas do periodo" |
| Despesa | Saida | Relatorios e lancamentos | "Saidas do periodo" |
| Evento / Eventos | Atividade / Atividades | Acoes gerais | "Atividades da semana" |
| Reuniao | Sessao | Reuniao formal da Loja | "Proxima Sessao" |
| Presenca | Frequencia | Confirmacao e controle | "Confirmar frequencia" |
| Configuracoes | Ajustes da Loja | Tela administrativa | "Ajustes da Loja" |
| Dados da empresa | Dados da Loja | Identificacao institucional | "Dados da Loja" |
| Cidade / Localidade | Oriente | Somente quando localidade masonica | "Oriente: Porto Alegre" |
| Relatorio financeiro | Relatorio da Tesouraria | Titulos de relatorio | "Relatorio da Tesouraria" |
| Relatorio de usuarios | Relatorio de obreiros | Titulos de relatorio | "Relatorio de obreiros" |
| Cadastro | Registro | Fluxo cadastral administrativo | "Registro atualizado" |
| Criar usuario | Registrar obreiro | CTA | "Registrar obreiro" |
| Adicionar usuario | Adicionar obreiro | CTA | "Adicionar obreiro" |
| Editar usuario | Atualizar obreiro | CTA | "Atualizar obreiro" |
| Excluir usuario | Remover do quadro | CTA | "Remover do quadro" |
| Ativo | Regular | Estado visual | "Situacao: Regular" |
| Inativo | Afastado | Estado visual | "Situacao: Afastado" |
| Bloqueado | Irregular | Estado visual | "Situacao: Irregular" |
| Pendente | Em aberto / Em analise | Contexto de cobranca ou validacao | "Contribuicao em aberto" |
| Membros, Usuários, Clientes | Quadro de Obreiros | Telas de listagem e gestão de pessoas | "Quadro de Obreiros" |
| Financeiro, Pagamentos | Tesouraria | Módulo do cargo e navegação | "Tesouraria" |
| Usuário logado / Sucesso | Seja bem-vindo, Irmão | Apenas após o login | "Seja bem-vindo, Irmão" |
| Oriente (como saudação) | Oriente (como cidade) | Restrito a campos de endereço geográfico | "Oriente: São Paulo" |
## Termos vetados fora de contexto ritual

Evitar, salvo contexto ritualistico real:

- iniciacao
- palavra de passe
- escrutinio
- grau (como substituicao automatica)
- ritual
- Camara do Meio
- sinais
- toques
- trabalhos abertos/fechados
