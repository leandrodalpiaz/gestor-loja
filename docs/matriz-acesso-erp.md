# Matriz de acesso do ERP

Objetivo: consolidar quem pode ver, agir ou administrar cada área do sistema, sem espalhar regra apenas pelo front-end.

Principios:

- preservar a regra real implementada hoje

- não assumir lista fechada de cargos

- separar permissão de acesso da prioridade visual na interface

- diferenciar o que e comum a todos do que e exclusivo por responsabilidade

- manter compatibilidade com web, Telegram e miniapps

## Regra oficial de organizacao visual do dashboard

Esta regra define somente organizacao visual. Não altera RBAC.

1. Cada função/rota do menu pertence a uma seção prioritaria (cargo responsável pela área).
2. A função deve aparecer uma única vez no menu (sem duplicacao em seções diferentes).
3. Se outro cargo tiver permissão para a mesma função, ele acessa essa função na mesma seção prioritaria.
4. Exibicao de item e sempre por permissão efetiva da sessão, nunca por hardcode de cargo na view.
5. O cargo-base "obreiro" concentra apenas funções comuns (pessoais), sem absorver funções técnicas de outros módulos.

### Ordem oficial de prioridade das seções

1. Obreiro
2. Veneravel Mestre
3. Secretaria
4. Chancelaria
5. Tesouraria
6. Hospitaleiro
7. Primeiro Vigilante
8. Segundo Vigilante
9. Orador
10. Mestre de Banquetes
11. Mestre de Harmonia
12. Biblioteca
13. Administracao
14. Sistema (somente admin técnico)

## Niveis de acesso

### 1. Comum a todos

Rotas e funções que podem ser mostradas para qualquer usuário autenticado, sem depender de um cargo administrativo especifico.

Uso esperado:

- consulta pessoal

- acompanhamento proprio

- ações sem impacto administrativo amplo

### 2. Por cargo

Rotas e funções ligadas a uma responsabilidade especifica da Loja.

Uso esperado:

- operação de secretaria

- tesouraria

- biblioteca administrativa

- vigilancia

- chancelaria

### 3. Especial ou restrito

Rotas e funções que envolvem aprovacao, fechamento, configuração ou supervisao ampla.

Uso esperado:

- administracao

- configurações da Loja

- auditoria

- votações e fechamentos

## Tipos de ação

- Ver: consultar tela, lista, dashboard ou detalhe

- Criar: abrir novo registro

- Editar: alterar registro existente

- Aprovar: validar, fechar, autorizar ou decidir

- Administrar: configurar, manter parametros ou regras centrais

## Direcao de visualização mobile

- mostrar primeiro a ação principal do cargo

- reduzir ruide de opcoes que o usuário não executa com frequência

- manter ações criticas visíveis sem depender de tabela larga

- em listas operacionais mobile, preferir cards empilhados

- status importante deve aparecer como badge forte

## Tabela pratica de acesso

| Área | Ver | Agir (criar/editar/aprovar) | Administrar | Observações |
| --- | --- | --- | --- | --- |
| Dashboard | autenticado com dashboard liberado | varia por links mostrados no menu do cargo | não se aplica diretamente | precisa consolidar melhor a regra de entrada e prioridade por cargo |
| Obreiros | secretario, primeiro_vigilante, segundo_vigilante, chanceler, veneravel, admin | editar ficha: secretario, admin; criar novo: secretario, admin | não | manter busca como fluxo principal no mobile |
| Biblioteca catalogo | autenticado no fluxo atual | solicitar, comentar e reagir conforme sessão autenticada; classificar para perfis formativos e administrativos | não | separar consulta geral de gestão administrativa |
| Biblioteca gestão | bibliotecario, admin, veneravel | adicionar, editar, emprestimos, devolucao | não | manter visível so para quem opera a área |
| Secretaria | secretario, veneravel, admin | sessões, trabalhos, publicações, balaústres | admin e parte de veneravel em ações especiais | separar operação rotineira de aprovacao |
| Vigilancia 1 | primeiro_vigilante, veneravel, admin | trilhas, ações rapidas, certificados | não | foco em acompanhamento formativo |
| Vigilancia 2 | segundo_vigilante, veneravel, admin | trilhas, ações rapidas, recomendacoes, certificados | não | foco em acompanhamento formativo |
| Chancelaria | chanceler, veneravel, admin | previas, dados, envio e certificado | não | destacar pendencias e mensagem do dia |
| Assistencia | hospitaleiro, secretario, tesoureiro, veneravel, admin | ocorrencias, visitas, status | não | mostrar ocorrencias abertas primeiro |
| Mestre de Harmonia | mestre_harmonia, veneravel, admin | operação do painel ritual | não | fluxo altamente operacional e de baixa densidade |
| Mestre de Banquetes | mestre_banquetes, veneravel, admin | salvar operação e leitura de confirmados | não | priorizar leitura rapida e confirmações |
| Orador | orador, veneravel, admin | uso de painel e apoio de sessão | não | foco em leitura resumida |
| Tesouraria | tesoureiro, veneravel, admin | caixa, comprovantes, regularidade, fechamento, obrigacoes, sessões | não | separar ver, lancar, validar e fechar |
| Administracao de cargos | admin, secretario, veneravel | salvar e encerrar gestões: admin, secretario, veneravel | admin lidera governanca | importante separar governanca de operação comum |
| Parametros da Loja | admin | salvar parametros: admin | admin | acesso especial/restrito |
| Auditoria admin | admin, veneravel | consulta e revisão | admin | não tratar como menu comum |
| Meu financeiro | usuário autenticado no fluxo atual e/ou miniapp autorizado | consulta pessoal, possível leitura de obrigacoes próprias | não | precisa confirmar regra funcional final |
| Telegram e miniapps | depende do perfil resolvido na sessão ou init_data | APIs miniapp por área e links web_app | não | nunca presumir equivalencia automatica com a web |

## Mapa inicial por área

### Dashboard

Acesso atual:

- usuários autenticados com dashboard liberado

Visualização recomendada:

- mostrar primeiro atalhos e pendencias do cargo principal

- reduzir densidade de seções na primeira dobra mobile

- manter menus completos para desktop

### Obreiros

Acesso atual:

- secretario

- primeiro_vigilante

- segundo_vigilante

- chanceler

- veneravel

- admin

Tipos de ação atuais:

- Ver: lista de obreiros

- Editar: ficha do obreiro

- Criar: novo obreiro (secretario, admin)

Visualização recomendada:

- busca e alerta primeiro no mobile

- ações principais visíveis sem competir com filtros secundarios

### Biblioteca

Acesso atual:

- painel principal com acesso autenticado no fluxo atual

- gestão administrativa:

  - bibliotecario

  - admin

  - veneravel

- classificacao e apoio formativo:

  - primeiro_vigilante

  - segundo_vigilante

  - bibliotecario

  - veneravel

  - admin

Tipos de ação atuais:

- Ver: catalogo e detalhes

- Criar/Editar: títulos e gestão de emprestimos

- Agir: solicitar emprestimo, comentar, reagir, devolver

Visualização recomendada:

- catalogo limpo para consulta

- emprestimos e gestão destacados apenas para quem atua na área

### Tesouraria

Acesso atual:

- tesoureiro

- veneravel

- admin

Tipos de ação atuais:

- Ver: caixa, sessões, comprovantes, regularidade, fechamento, relatório, obrigacoes

- Criar/Editar: lancamentos e obrigacoes

- Aprovar: comprovantes, fechamentos, quitacoes

Visualização recomendada:

- destacar pendencias financeiras e validações

- evitar expor operações criticas a perfis sem responsabilidade direta

### Secretaria

Acesso atual:

- secretario

- veneravel

- admin

Tipos de ação atuais:

- Ver: painel e relatórios

- Criar/Editar: sessões, trabalhos, publicações, balaústres

- Aprovar: algumas ações ficam com veneravel/admin

Visualização recomendada:

- foco em saneamento cadastral, sessões e votações

### Vigilancias

Primeiro Vigilante:

- primeiro_vigilante

- veneravel

- admin

Segundo Vigilante:

- segundo_vigilante

- veneravel

- admin

Tipos de ação atuais:

- Ver: paines e trilhas

- Editar: acompanhamento e ações rapidas

- Agir: recomendacoes, certificados, trilhas

Visualização recomendada:

- separar acompanhamento formativo de operações administrativas

### Chancelaria

Acesso atual:

- chanceler

- veneravel

- admin

Tipos de ação atuais:

- Ver: efemerides, certificados, sessão

- Editar: previas, dados e mensagens

- Agir: envio de previas, emissao de certificado

Visualização recomendada:

- destacar mensagem do dia e pendencias de dados

### Assistencia

Acesso atual:

- hospitaleiro

- secretario

- tesoureiro

- veneravel

- admin

Tipos de ação atuais:

- Ver: painel de assistencia

- Criar/Editar: ocorrencias

- Agir: visitas, status e encaminhamentos

Visualização recomendada:

- mostrar ocorrencias abertas primeiro

### Administracao

Acesso atual:

- admin

- alguns pontos tambem para secretario e veneravel

Tipos de ação atuais:

- Administrar: cargos, gestões, parametros da Loja, auditoria

Visualização recomendada:

- nunca tratar como menu comum

- mostrar somente para perfis com responsabilidade real de governanca

### Meu financeiro

Observação:

- hoje a permissão real em `/financeiro/minhas-obrigacoes` parece mais restrita do que o nome sugere e precisa de revisão funcional antes de virar regra de experiencia

Ação pendente:

- confirmar se esta rota deve ser realmente comum a todo usuário autenticado ou continuar restrita aos cargos atuais

### Telegram e miniapps

Regra geral:

- qualquer alteracao em acesso e visualização deve considerar:

  - dashboard web

  - comandos do bot

  - miniapps por perfil

Cuidados:

- não pressupor que liberar uma rota web significa liberar o miniapp equivalente

- validar sempre o impacto em links `web_app`, APIs miniapp e resolucao por init_data

## Pendencias de consolidacao

- revisar acesso real da rota `/dashboard`

- revisar se `/financeiro/minhas-obrigacoes` deve ser comum a todos

- mapear melhor o que e "ver", "agir" e "administrar" dentro da Biblioteca

- documentar visualização inicial recomendada por cargo no dashboard

## Validação de manutenção (Bot + Miniapp)

Toda alteracao de acesso deve validar os 3 pontos abaixo:

$11. Bot Telegram

- menu por cargo aparece conforme permissão

- callback abre painel correto

$11. Miniapp page

- rota `/miniapp/*` com `requireMiniappAuth` coerente com o cargo

$11. Miniapp API

- endpoint `/api/miniapp/*` com permissão equivalente a página miniapp

Regra de seguranca operacional:

- evitar usar rota web tradicional em botão WebApp do Telegram quando existir rota `/miniapp/*` equivalente.
