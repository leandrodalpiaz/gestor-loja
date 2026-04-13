# Matriz de acesso do ERP

Objetivo: consolidar quem pode ver, agir ou administrar cada area do sistema, sem espalhar regra apenas pelo front-end.

Principios:
- preservar a regra real implementada hoje
- nao assumir lista fechada de cargos
- separar permissao de acesso da prioridade visual na interface
- diferenciar o que e comum a todos do que e exclusivo por responsabilidade
- manter compatibilidade com web, Telegram e miniapps

## Niveis de acesso

### 1. Comum a todos
Rotas e funcoes que podem ser mostradas para qualquer usuario autenticado, sem depender de um cargo administrativo especifico.

Uso esperado:
- consulta pessoal
- acompanhamento proprio
- acoes sem impacto administrativo amplo

### 2. Por cargo
Rotas e funcoes ligadas a uma responsabilidade especifica da Loja.

Uso esperado:
- operacao de secretaria
- tesouraria
- biblioteca administrativa
- vigilancia
- chancelaria

### 3. Especial ou restrito
Rotas e funcoes que envolvem aprovacao, fechamento, configuracao ou supervisao ampla.

Uso esperado:
- administracao
- configuracoes da Loja
- auditoria
- votacoes e fechamentos

## Tipos de acao

- Ver: consultar tela, lista, dashboard ou detalhe
- Criar: abrir novo registro
- Editar: alterar registro existente
- Aprovar: validar, fechar, autorizar ou decidir
- Administrar: configurar, manter parametros ou regras centrais

## Direcao de visualizacao mobile

- mostrar primeiro a acao principal do cargo
- reduzir ruide de opcoes que o usuario nao executa com frequencia
- manter acoes criticas visiveis sem depender de tabela larga
- em listas operacionais mobile, preferir cards empilhados
- status importante deve aparecer como badge forte

## Tabela pratica de acesso

| Area | Ver | Agir (criar/editar/aprovar) | Administrar | Observacoes |
| --- | --- | --- | --- | --- |
| Dashboard | autenticado com dashboard liberado | varia por links mostrados no menu do cargo | nao se aplica diretamente | precisa consolidar melhor a regra de entrada e prioridade por cargo |
| Obreiros | secretario, primeiro_vigilante, segundo_vigilante, chanceler, veneravel, admin | editar ficha: secretario, admin; criar novo: secretario, admin | nao | manter busca como fluxo principal no mobile |
| Biblioteca catalogo | autenticado no fluxo atual | solicitar, comentar e reagir conforme sessao autenticada; classificar para perfis formativos e administrativos | nao | separar consulta geral de gestao administrativa |
| Biblioteca gestao | bibliotecario, admin, veneravel | adicionar, editar, emprestimos, devolucao | nao | manter visivel so para quem opera a area |
| Secretaria | secretario, veneravel, admin | sessoes, trabalhos, publicacoes, balaustres | admin e parte de veneravel em acoes especiais | separar operacao rotineira de aprovacao |
| Vigilancia 1 | primeiro_vigilante, veneravel, admin | trilhas, acoes rapidas, certificados | nao | foco em acompanhamento formativo |
| Vigilancia 2 | segundo_vigilante, veneravel, admin | trilhas, acoes rapidas, recomendacoes, certificados | nao | foco em acompanhamento formativo |
| Chancelaria | chanceler, veneravel, admin | previas, dados, envio e certificado | nao | destacar pendencias e mensagem do dia |
| Assistencia | hospitaleiro, secretario, tesoureiro, veneravel, admin | ocorrencias, visitas, status | nao | mostrar ocorrencias abertas primeiro |
| Mestre de Harmonia | mestre_harmonia, veneravel, admin | operacao do painel ritual | nao | fluxo altamente operacional e de baixa densidade |
| Mestre de Banquetes | mestre_banquetes, veneravel, admin | salvar operacao e leitura de confirmados | nao | priorizar leitura rapida e confirmacoes |
| Orador | orador, veneravel, admin | uso de painel e apoio de sessao | nao | foco em leitura resumida |
| Tesouraria | tesoureiro, veneravel, admin | caixa, comprovantes, regularidade, fechamento, obrigacoes, sessoes | nao | separar ver, lancar, validar e fechar |
| Administracao de cargos | admin, secretario, veneravel | salvar e encerrar gestoes: admin, secretario, veneravel | admin lidera governanca | importante separar governanca de operacao comum |
| Parametros da Loja | admin | salvar parametros: admin | admin | acesso especial/restrito |
| Auditoria admin | admin, veneravel | consulta e revisao | admin | nao tratar como menu comum |
| Meu financeiro | usuario autenticado no fluxo atual e/ou miniapp autorizado | consulta pessoal, possivel leitura de obrigacoes proprias | nao | precisa confirmar regra funcional final |
| Telegram e miniapps | depende do perfil resolvido na sessao ou init_data | APIs miniapp por area e links web_app | nao | nunca presumir equivalencia automatica com a web |

## Mapa inicial por area

### Dashboard

Acesso atual:
- usuarios autenticados com dashboard liberado

Visualizacao recomendada:
- mostrar primeiro atalhos e pendencias do cargo principal
- reduzir densidade de secoes na primeira dobra mobile
- manter menus completos para desktop

### Obreiros

Acesso atual:
- secretario
- primeiro_vigilante
- segundo_vigilante
- chanceler
- veneravel
- admin

Tipos de acao atuais:
- Ver: lista de obreiros
- Editar: ficha do obreiro
- Criar: novo obreiro (secretario, admin)

Visualizacao recomendada:
- busca e alerta primeiro no mobile
- acoes principais visiveis sem competir com filtros secundarios

### Biblioteca

Acesso atual:
- painel principal com acesso autenticado no fluxo atual
- gestao administrativa:
  - bibliotecario
  - admin
  - veneravel
- classificacao e apoio formativo:
  - primeiro_vigilante
  - segundo_vigilante
  - bibliotecario
  - veneravel
  - admin

Tipos de acao atuais:
- Ver: catalogo e detalhes
- Criar/Editar: titulos e gestao de emprestimos
- Agir: solicitar emprestimo, comentar, reagir, devolver

Visualizacao recomendada:
- catalogo limpo para consulta
- emprestimos e gestao destacados apenas para quem atua na area

### Tesouraria

Acesso atual:
- tesoureiro
- veneravel
- admin

Tipos de acao atuais:
- Ver: caixa, sessoes, comprovantes, regularidade, fechamento, relatorio, obrigacoes
- Criar/Editar: lancamentos e obrigacoes
- Aprovar: comprovantes, fechamentos, quitacoes

Visualizacao recomendada:
- destacar pendencias financeiras e validacoes
- evitar expor operacoes criticas a perfis sem responsabilidade direta

### Secretaria

Acesso atual:
- secretario
- veneravel
- admin

Tipos de acao atuais:
- Ver: painel e relatorios
- Criar/Editar: sessoes, trabalhos, publicacoes, balaustres
- Aprovar: algumas acoes ficam com veneravel/admin

Visualizacao recomendada:
- foco em saneamento cadastral, sessoes e votacoes

### Vigilancias

Primeiro Vigilante:
- primeiro_vigilante
- veneravel
- admin

Segundo Vigilante:
- segundo_vigilante
- veneravel
- admin

Tipos de acao atuais:
- Ver: paines e trilhas
- Editar: acompanhamento e acoes rapidas
- Agir: recomendacoes, certificados, trilhas

Visualizacao recomendada:
- separar acompanhamento formativo de operacoes administrativas

### Chancelaria

Acesso atual:
- chanceler
- veneravel
- admin

Tipos de acao atuais:
- Ver: efemerides, certificados, sessao
- Editar: previas, dados e mensagens
- Agir: envio de previas, emissao de certificado

Visualizacao recomendada:
- destacar mensagem do dia e pendencias de dados

### Assistencia

Acesso atual:
- hospitaleiro
- secretario
- tesoureiro
- veneravel
- admin

Tipos de acao atuais:
- Ver: painel de assistencia
- Criar/Editar: ocorrencias
- Agir: visitas, status e encaminhamentos

Visualizacao recomendada:
- mostrar ocorrencias abertas primeiro

### Administracao

Acesso atual:
- admin
- alguns pontos tambem para secretario e veneravel

Tipos de acao atuais:
- Administrar: cargos, gestoes, parametros da Loja, auditoria

Visualizacao recomendada:
- nunca tratar como menu comum
- mostrar somente para perfis com responsabilidade real de governanca

### Meu financeiro

Observacao:
- hoje a permissao real em `/financeiro/minhas-obrigacoes` parece mais restrita do que o nome sugere e precisa de revisao funcional antes de virar regra de experiencia

Acao pendente:
- confirmar se esta rota deve ser realmente comum a todo usuario autenticado ou continuar restrita aos cargos atuais

### Telegram e miniapps

Regra geral:
- qualquer alteracao em acesso e visualizacao deve considerar:
  - dashboard web
  - comandos do bot
  - miniapps por perfil

Cuidados:
- nao pressupor que liberar uma rota web significa liberar o miniapp equivalente
- validar sempre o impacto em links `web_app`, APIs miniapp e resolucao por init_data

## Pendencias de consolidacao

- revisar acesso real da rota `/dashboard`
- revisar se `/financeiro/minhas-obrigacoes` deve ser comum a todos
- mapear melhor o que e "ver", "agir" e "administrar" dentro da Biblioteca
- documentar visualizacao inicial recomendada por cargo no dashboard
