# Bot Telegram - Operacao e Manutencao

Objetivo: documentar arquitetura funcional, cargos, menus, permissao e pontos de manutencao do bot e miniapps.

## 1. Escopo

Este documento cobre:

- fluxo de acesso no bot
- descricao dos paineis por cargo
- regra de permissao no bot e miniapps
- padrao de links WebApp para evitar erro de permissao
- checklist tecnico de manutencao

## 2. Regra principal de acesso

O bot deve abrir miniapps por rotas /miniapp/* sempre que o usuario vier do Telegram.

Nao usar rota web administrativa direta em botao WebApp, por exemplo:

- evitar /orador
- evitar /secretaria
- evitar /veneravel
- evitar /assistencia

Preferir:

- /miniapp/orador
- /miniapp/secretaria
- /miniapp/veneravel
- /miniapp/hospitaleiro

Motivo: rota web depende de sessao tradicional e pode retornar 403 no Telegram WebView.

## 3. Menus por cargo (resumo operacional)

### Admin

- painel consolidado com acesso a todos os modulos
- usa callbacks admin_* para abrir menus especificos

### Chanceler

- Em Loja: /miniapp/chanceler
- Neste Dia: previa e aprovacao de efemerides

### Secretario

- Secretaria Mobile: /miniapp/secretaria
- Sessao em Loja: /miniapp/secretaria?foco=balaustre
- fluxo de balaustre com salvar rascunho e finalizar depois

### Tesoureiro

- Tesouraria Mobile: /miniapp/tesouraria
- atalho para caixa, comprovantes, regularidade, fechamento e relatorio

### Bibliotecario

- Biblioteca Mobile: /miniapp/biblioteca
- cadastro ISBN/manual e emprestimos para perfis autorizados

### Primeiro Vigilante

- /miniapp/aprendizado
- /miniapp/primeiro-vigilante

### Segundo Vigilante

- /miniapp/companheirismo
- /miniapp/segundo-vigilante

### Orador

- Em Loja: /miniapp/orador

### Mestre de Banquetes

- Em Loja: /miniapp/mestre-banquetes

### Mestre de Harmonia

- Em Loja: /miniapp/mestre-harmonia

### Veneravel

- Em Loja: /miniapp/veneravel

### Hospitaleiro

- Em Loja: /miniapp/hospitaleiro

## 4. Fontes de permissao

### Bot

- arquivo principal: src/Bot/CommandHandler.php
- validacao por role e permission map

### Miniapp page

- roteamento e guard em src/Core/Http/MiniappPageRoutes.php
- regra via requireMiniappAuth(...)

### Miniapp API

- guard por rota em src/Core/Http/MiniappApiRoutes.php

### Matriz RBAC

- definicoes em src/Core/Authorization/PermissionMap.php

## 5. Criterios de manutencao obrigatorios

1. Qualquer novo botao WebApp deve ser validado com usuario real no Telegram.
2. Se for painel de cargo, primeiro procurar rota /miniapp/* equivalente.
3. Nao duplicar regra de permissao em front-end sem guard no backend.
4. Mensagem de acesso negado deve ser clara e sem detalhe tecnico.
5. Evitar regressao de UX: acao principal visivel e botao Voltar em todos os paineis de cargo.

## 6. Padrao de callback

Padrão recomendado:

- menu principal: exibir modulos por permissao
- callback admin_*: abrir submenu do cargo
- callback *_menu: reabrir painel do modulo

Exemplos:

- admin_chancelaria
- admin_secretaria
- admin_orador
- admin_mestre_harmonia

## 7. Erros comuns e mitigacao

### 403 ao abrir painel no Telegram

Causa comum:

- botao apontando para rota web com sessao tradicional

Acao:

- trocar para rota /miniapp/*

### SQLSTATE[26000] prepared statement does not exist

Causa comum:

- pooler em modo transacional invalidando prepared statements entre requests

Acao:

- PDO::ATTR_EMULATE_PREPARES = true na conexao

## 8. Roteiro rapido de diagnostico

1. reproduzir no Telegram com horario anotado
2. conferir log do webhook
3. validar rota do botao WebApp gerado
4. validar guard em MiniappPageRoutes e MiniappApiRoutes
5. validar role/cargo do usuario testado

## 9. Dono funcional por modulo

- Chancelaria: Chanceler
- Secretaria: Secretario
- Tesouraria: Tesoureiro
- Biblioteca: Bibliotecario
- Vigilancia: 1o e 2o Vigilantes
- Operacao ritual complementar: Orador, Mestre de Banquetes, Mestre de Harmonia
- Governanca: Veneravel e Admin

## 10. Definicao de pronto para deploy

So considerar pronto quando:

- botao abre no Telegram
- miniapp carrega dashboard
- API responde sem 401/403 indevido
- fluxo principal do cargo executa do inicio ao fim
