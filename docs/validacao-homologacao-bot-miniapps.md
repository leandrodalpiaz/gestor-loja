# Validacao e Homologacao - Bot e Miniapps

Objetivo: executar um roteiro unico de validacao antes de deploy para evitar multiplos deploys.

## 1. Pre-check tecnico

1. APP_URL correto e publico (HTTPS)
2. webhook ativo em APP_URL/webhook.php
3. SYSTEM_ADMIN_TELEGRAM_IDS configurado
4. ambiente responde /login e /health

## 2. Teste por cargo (Telegram)

Para cada cargo, validar:

- abre menu no bot
- abre miniapp
- executa acao principal
- volta ao menu sem travar

### Admin

- abrir painel admin completo

### Chanceler

- Em Loja
- Neste Dia

### Secretario

- Secretaria Mobile
- Sessao em Loja
- salvar rascunho de balaustre
- finalizar balaustre

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

## 3. Checklist de permissao

Para cada teste de cargo:

- usuario autorizado: deve abrir
- usuario nao autorizado: deve receber mensagem de acesso restrito

## 4. Checklist de logs

No periodo do teste, verificar:

- webhook processado com sucesso
- ausencia de 403 indevido
- ausencia recorrente de SQLSTATE[26000]

## 5. Criterio de aprovacao

Aprovado quando:

- 100% dos modulos por cargo abrem no Telegram
- fluxos principais executam sem erro funcional
- sem erro recorrente de permissao indevida
- sem erro recorrente de banco no webhook

## 6. Registro minimo de evidencia

Salvar para cada cargo:

- horario
- botao usado
- resultado
- print opcional

Formato sugerido:

- cargo: Secretario
- acao: Sessao em Loja > Salvar rascunho do balaustre
- resultado: OK
- horario: 2026-04-27 20:10
