# Validação e Homologacao - Bot e Miniapps

Objetivo: executar um roteiro único de validação antes de deploy para evitar multiplos deploys.

## 1. Pre-check técnico

1. APP_URL correto e publico (HTTPS)
2. webhook ativo em APP_URL/webhook.php
3. SYSTEM_ADMIN_TELEGRAM_IDS configurado
4. ambiente responde /login e /health

## 2. Teste por cargo (Telegram)

Para cada cargo, validar:

- abre menu no bot
- abre miniapp
- executa ação principal
- volta ao menu sem travar

### Admin

- abrir painel admin completo

### Chanceler

- Em Loja
- Neste Dia

### Secretario

- Secretaria Mobile
- Sessão em Loja
- salvar rascunho de balaústre
- finalizar balaústre

### Tesoureiro

- Tesouraria Mobile
- comprovantes
- regularidade
- fechamento

### Bibliotecario

- Biblioteca Mobile
- acervo
- cadastro
- emprestimos

### Primeiro Vigilante

- Aprendizado
- Em Loja

### Segundo Vigilante

- Companheirismo
- Em Loja

### Orador

- Em Loja

### Mestre de Banquetes

- Em Loja

### Mestre de Harmonia

- Em Loja

### Veneravel

- Em Loja

### Hospitaleiro

- Em Loja

## 3. Checklist de permissão

Para cada teste de cargo:

- usuário autorizado: deve abrir
- usuário não autorizado: deve receber mensagem de acesso restrito

## 4. Checklist de logs

No período do teste, verificar:

- webhook processado com sucesso
- ausencia de 403 indevido
- ausencia recorrente de SQLSTATE[26000]

## 5. Criterio de aprovacao

Aprovado quando:

- 100% dos módulos por cargo abrem no Telegram
- fluxos principais executam sem erro funcional
- sem erro recorrente de permissão indevida
- sem erro recorrente de banco no webhook

## 6. Registro mínimo de evidencia

Salvar para cada cargo:

- horário
- botão usado
- resultado
- print opcional

Formato sugerido:

- cargo: Secretario
- ação: Sessão em Loja > Salvar rascunho do balaústre
- resultado: OK
- horário: 2026-04-27 20:10

## Roteiro complementar

Miniapps/Telegram são evidência secundária. Para homologacao principal Desktop + PWA, usar `docs/homologacao-paridade-desktop-mobile.md`.
