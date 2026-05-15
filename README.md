# gestor-loja

ERP interno (PHP 8.2 server-rendered + Tailwind + Supabase Postgres) para operação administrativa da Loja Maçônica.

Diretriz atual:
- Web: **desktop-first** para gestão completa.
- Mobile: **PWA é a experiência principal**.
- Telegram: **secundário** (baixo engajamento), usado apenas como complemento/atalhos quando fizer sentido.

## Início com 1 clique (Windows)

Dê duplo clique em `iniciar_local.bat` na raiz do projeto.

## Requisitos

- Docker Desktop instalado e rodando (recomendado)
- Arquivo `.env` configurado (use `.env.example` como base)

## Subir o sistema web local (Docker)

```powershell
docker compose up --build -d app
```

Aplicação web: `http://localhost:8000`  
Healthcheck: `http://localhost:8000/health`

### Opcional: rodar sem Docker

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\run_local.ps1 -Port 8000
```

Para abrir painel sem login na sessão local:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\run_local.ps1 -Port 8000 -OpenAccess
```

## Checklist automatizado

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\checklist_local.ps1 -Port 8090
```

Opções úteis:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\checklist_local.ps1 -Port 8090 -OpenAccess
powershell -ExecutionPolicy Bypass -File .\scripts\checklist_local.ps1 -Port 8090 -SkipTelegram
powershell -ExecutionPolicy Bypass -File .\scripts\checklist_local.ps1 -Port 8090 -TelegramMode polling
```

## Encerrar ambiente

```powershell
docker compose down
```

## Referências (documentação)

- Regras e responsabilidades por cargo: `docs/regras-de-negocio.md`
- Matriz de acesso (RBAC + organização visual): `docs/matriz-acesso-erp.md`
- Cargos e funcionalidades oficiais (web/PWA/miniapps): `docs/cargos-funcionalidades-oficiais.md`
- Homologação (Desktop + PWA): `docs/homologacao-paridade-desktop-mobile.md`
