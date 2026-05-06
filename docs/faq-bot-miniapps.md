# FAQ - Bot e Miniapps

## 1) Cliquei no botão e apareceu acesso restrito. O que verificar primeiro?

- confirme se o botão abre rota /miniapp/*
- confirme o cargo/permissão do usuário
- confirme se o usuário esta ativo
- reenviar /painel e testar de novo

## 2) Por que funciona no navegador e falha no Telegram?

Rotas web tradicionais usam sessão do browser. No Telegram, o fluxo correto e miniapp com initData.

## 3) Como corrigir erro 403 em painel de cargo?

1. trocar link WebApp para /miniapp/módulo
2. revisar requireMiniappAuth na rota da página miniapp
3. revisar permissão da API miniapp

## 4) O menu do admin apareceu incompleto. Qual causa comum?

- cargo com alias não normalizado (ex.: administrador em vez de admin)
- SYSTEM_ADMIN_TELEGRAM_IDS ausente
- usuário sem role esperada no cadastro

## 5) Quando usar web e quando usar miniapp?

- bot Telegram: sempre preferir miniapp
- uso interno desktop fora do Telegram: pode usar rota web

## 6) Secretaria: como trabalhar balaústre durante a sessão?

- usar Sessão em Loja
- salvar rascunho do balaústre varias vezes
- finalizar apenas quando estiver pronto para votação

## 7) Qual mensagem mostrar para erro temporario?

Não foi possível concluir esta ação agora. Tente novamente em instantes.

## 8) O que enviar ao suporte para facilitar análise?

- usuário e cargo
- botão/comando usado
- horário aproximado
- mensagem vista na tela
- se possível, print

## 9) Favicon 404 no log quebra o sistema?

Não. E ruido de navegador. Não impacta a regra de permissão.

## 10) Qual criterio mínimo de homologacao antes de deploy?

- abrir cada módulo por cargo via bot
- validar fluxo principal do cargo
- validar que não ha 403 indevido no Telegram
- validar webhook sem erro recorrente
