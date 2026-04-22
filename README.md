# gestor-loja

Fluxo recomendado para continuar desenvolvimento local (web + Telegram) sem perder caminho de deploy futuro.

## 0) Inicio com 1 clique (Windows)

De um duplo clique no arquivo `iniciar_local.bat` na raiz do projeto.

Importante: a URL publica muda a cada execucao. Depois de iniciar, envie `/painel` novamente no bot para receber botoes com a URL atual.

Ele executa automaticamente:

- servidor web local
- bot Telegram em long polling (sem Render, sem webhook publico)
- tunnel HTTPS para abrir botoes Mini App
- healthcheck + checklist local

## 1) Requisitos

- Docker Desktop instalado e rodando
- Arquivo `.env` configurado (use `.env.example` como base)

## 2) Subir o sistema web local

```powershell
docker compose up --build -d app
```

Aplicacao web:

```text
http://localhost:8000
```

Healthcheck:

```text
http://localhost:8000/health
```

### Opcional: rodar sem Docker

Se voce ainda nao instalou Docker, rode com PHP local:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\run_local.ps1 -Port 8000
```

Para abrir painel sem login na sessao local:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\run_local.ps1 -Port 8000 -OpenAccess
```

## 3) Expor URL publica para Telegram (tunnel)

O Telegram exige URL publica HTTPS para webhook e Mini App.

Suba o tunnel:

```powershell
docker compose --profile tunnel up -d tunnel
docker compose logs -f tunnel
```

Copie a URL publica exibida no log (exemplo: `https://abc123.trycloudflare.com`).

Atualize `APP_URL` no `.env` com essa URL e reinicie o app:

```powershell
docker compose up -d app
```

## 4) Escolher modo do bot Telegram

### Modo A: Webhook (com tunnel)

Use quando quiser testar Mini App e webhook HTTP.

```powershell
php scripts/telegram_webhook.php set
php scripts/telegram_webhook.php status
```

Se quiser definir manualmente:

```powershell
php scripts/telegram_webhook.php set https://SUA-URL/webhook.php
```

### Modo B: Long Polling (sem tunnel e sem Render)

Use quando quiser continuar o desenvolvimento do bot local sem URL publica.

```powershell
php scripts/telegram_webhook.php delete
php scripts/telegram_polling.php
```

Observacao: nesse modo os comandos do bot funcionam, mas os botoes `web_app` (Mini App) continuam exigindo `APP_URL` publica HTTPS.

## 5) Ciclo de desenvolvimento diario

1. `docker compose up -d app`
2. Escolha um modo Telegram:
3. Webhook: `docker compose --profile tunnel up -d tunnel`
4. Polling: `php scripts/telegram_webhook.php delete` + `php scripts/telegram_polling.php`
5. Se a URL do tunnel mudou, atualizar `APP_URL` e rodar `php scripts/telegram_webhook.php set`

## 5.1) Checklist automatizado (pronto para uso diario)

Roda verificacoes de:

- web (`/health`, `/login`)
- miniapps biblioteca
- webhook local
- APIs de biblioteca e tesouraria
- webhook externo do Telegram
- login real (opcional, se `CHECK_LOGIN_CIM` e `CHECK_LOGIN_PASSWORD` estiverem no `.env`)

Comando:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\checklist_local.ps1 -Port 8090
```

Se quiser forcar modo aberto para validar views sem login durante homologacao:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\checklist_local.ps1 -Port 8090 -OpenAccess
```

Se quiser rodar sem validar Telegram externo:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\checklist_local.ps1 -Port 8090 -SkipTelegram
```

Se estiver usando modo polling:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\checklist_local.ps1 -Port 8090 -TelegramMode polling
```

## 5.2) Comando unico de uso diario

Sobe o servidor local, faz healthcheck, consulta webhook e roda checklist:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\dev_start.ps1 -Port 8000 -ChecklistPort 8090 -OpenAccess
```

Se quiser apenas executar a sequencia e encerrar (sem manter servidor aberto):

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\dev_start.ps1 -Port 8000 -ChecklistPort 8090 -OpenAccess -NoHold
```

Para tirar o Render do caminho e apontar Telegram para o ambiente local:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\dev_start.ps1 -Port 8000 -ChecklistPort 8090 -OpenAccess -WithTunnel
```

Esse modo:

- sobe tunnel publico (padrao: `localhost.run` via `ssh`)
- atualiza `APP_URL` no `.env`
- atualiza webhook para `APP_URL/webhook.php`

Se quiser apenas atualizar tunnel/webhook e encerrar em seguida:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\dev_start.ps1 -Port 8000 -ChecklistPort 8090 -OpenAccess -WithTunnel -NoHold
```

Observacao: com `-NoHold`, o tunnel e encerrado no final; use sem `-NoHold` para manter Telegram ativo.

Se quiser forcar provider alternativo:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\dev_start.ps1 -Port 8000 -ChecklistPort 8090 -OpenAccess -WithTunnel -TunnelProvider cloudflared
```

Para deixar o Render totalmente de lado e rodar Telegram sem webhook:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\dev_start.ps1 -Port 8000 -ChecklistPort 8090 -OpenAccess -WithPolling
```

Esse modo:

- remove webhook no Telegram
- inicia `telegram_polling.php` em background
- roda checklist em `-TelegramMode polling`

Para usar menus do bot em polling e manter Mini Apps abrindo (certificado, scanner, cadastro manual):

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\dev_start.ps1 -Port 8000 -ChecklistPort 8090 -OpenAccess -WithPolling -WithTunnel -TunnelProvider cloudflared
```

Nesse caso:

- tunnel fornece `APP_URL` publica HTTPS para os botoes `web_app`
- bot continua em polling (sem depender de webhook do Render)

## 6) Encerrar ambiente

```powershell
docker compose down
```

## 7) Caminho para deploy futuro

O projeto ja fica alinhado para migracao:

- `Dockerfile` continua sendo a base do ambiente
- `APP_URL` e vars de Telegram/Banco controlam comportamento por ambiente
- deploy em Render/Fly.io/Railway/VM fica principalmente troca de infraestrutura, sem reescrever aplicacao
