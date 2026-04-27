# FAQ - Bot e Miniapps

## 1) Cliquei no botao e apareceu acesso restrito. O que verificar primeiro?

- confirme se o botao abre rota /miniapp/*
- confirme o cargo/permissao do usuario
- confirme se o usuario esta ativo
- reenviar /painel e testar de novo

## 2) Por que funciona no navegador e falha no Telegram?

Rotas web tradicionais usam sessao do browser. No Telegram, o fluxo correto e miniapp com initData.

## 3) Como corrigir erro 403 em painel de cargo?

1. trocar link WebApp para /miniapp/modulo
2. revisar requireMiniappAuth na rota da pagina miniapp
3. revisar permissao da API miniapp

## 4) O menu do admin apareceu incompleto. Qual causa comum?

- cargo com alias nao normalizado (ex.: administrador em vez de admin)
- SYSTEM_ADMIN_TELEGRAM_IDS ausente
- usuario sem role esperada no cadastro

## 5) Quando usar web e quando usar miniapp?

- bot Telegram: sempre preferir miniapp
- uso interno desktop fora do Telegram: pode usar rota web

## 6) Secretaria: como trabalhar balaustre durante a sessao?

- usar Sessao em Loja
- salvar rascunho do balaustre varias vezes
- finalizar apenas quando estiver pronto para votacao

## 7) Qual mensagem mostrar para erro temporario?

Nao foi possivel concluir esta acao agora. Tente novamente em instantes.

## 8) O que enviar ao suporte para facilitar analise?

- usuario e cargo
- botao/comando usado
- horario aproximado
- mensagem vista na tela
- se possivel, print

## 9) Favicon 404 no log quebra o sistema?

Nao. E ruido de navegador. Nao impacta a regra de permissao.

## 10) Qual criterio minimo de homologacao antes de deploy?

- abrir cada modulo por cargo via bot
- validar fluxo principal do cargo
- validar que nao ha 403 indevido no Telegram
- validar webhook sem erro recorrente
