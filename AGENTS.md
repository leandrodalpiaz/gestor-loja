# AGENTS.md - Gestor-Loja

## Contexto do projeto
- ERP interno para Loja Maçônica.
- Stack atual: PHP 8.2 (Docker ou nativo Windows), Angular 22 SPA, Tailwind CSS, Supabase Postgres.
- Layout desktop-first com adaptação mobile inteligente.
- O painel web é Desktop-First e PWA (Progressive Web App) secundário. O bot Telegram é mantido atualizado, mas não é mais o canal principal.
- O backend pode rodar em Docker (`docker compose up -d app`) ou nativamente no Windows (`php -S localhost:8000 -t public public/router.php`) como fallback quando Docker falha.
- Integrações com Telegram e miniapps existem e não devem ser quebradas.

## Regras do Administrador Técnico (login sistema)
- O admin técnico (cim=adm) NÃO é um cargo da loja maçônica. É acesso puramente técnico ao sistema.
- O login admin deve ser INVISÍVEL como cargo: não aparece como "Ir.", não exibe badge de cargo no dashboard nem sidebar.
- O dashboard para admin mostra "Console Técnico" — nunca "Saudações, Ir.".
- O admin tem acesso total (permissão `*` / `is_system_admin`) para manutenção do sistema.
- Nunca expor o admin como "Administrador", "Ir. Administrador Técnico" ou qualquer título maçônico.

## Como subir servidores locais
Ver instruções completas em `/memories/repo/gestor-loja-notes.md` (seção "COMO SUBIR OS SERVIDORES").
Resumo rápido:
```bash
# Backend (Docker - recomendado)
docker compose up --build -d app

# Backend (PHP nativo - fallback quando Docker falha)
# Antes: matar wslrelay/php antigos na porta 8000
php -S localhost:8000 -t public public/router.php

# Frontend (Angular)
cd frontend && npm start
```
Acessar: http://localhost:4300/login
Health check: http://localhost:8000/health.php → {"status":"ok"}
Login admin: cim=adm, senha=Adm#1702
Diagnóstico rápido: health → login-cim → /api/auth/me → dashboard

## Problemas comuns e soluções
- **Docker "failed to connect at npipe"**: Docker Desktop não iniciou. Abrir Docker Desktop.
- **"could not translate host name" (DNS)**: Container Docker sem DNS. Usar PHP nativo (fallback).
- **"Loja não identificada"**: Tenant não resolvido. Verificar APP_DEFAULT_TENANT_SLUG no .env.
- **Porta 8000 ocupada por wslrelay**: Docker caiu mas WSL ficou. Matar processo e subir PHP nativo.
- **Erro 500 no login**: Backend sem conexão com Supabase. Testar /health.php primeiro.
- **Cookies PHPSESSID=0**: Proxy Angular não está forwarding. Verificar proxy.conf.json e angular.json.

## Regras de front-end
- Desktop-first: telas de gestão completa priorizam desktop com sidebar fixa e tabelas ricas.
- Mobile inteligente: telas de consulta e ação rápida funcionam bem em mobile com cards.
- Em mobile, preferir cards a tabelas.
- Evitar scroll horizontal em qualquer breakpoint.
- Formulários longos divididos em etapas (com estado via sessão ou gerenciamento de estado SPA).
- Priorizar ações principais e progressive disclosure.
- Toda mudança visual deve reforçar aparência de ERP, não de site institucional.
- Não criar reescritas amplas sem pedir antes.
- Fazer mudanças pequenas, seguras e revisáveis.

## Padrão de listas administrativas
- Em telas mobile, preferir cards empilhados para listas operacionais.
- Em md+ pode ser mantida tabela se ela já estiver estável.
- Evitar overflow horizontal como solucao principal.
- Status deve aparecer como badge visual forte.
- A ação principal deve ficar visível no card sem exigir expansao.

## Regras de arquitetura
- Permitir a modernização do front-end para um modelo SPA (ex: Angular/TypeScript), mantendo o PHP/Supabase como camada de API.
- Usar Tailwind já existente.
- Não quebrar rotas e integrações existentes.
- Não assumir lista fechada de cargos.
- Não converter IDs de obreiro para int; o banco real usa UUID.

## Fluxo esperado
- Para tarefas complexas, planeje antes de implementar.
- Antes de editar, liste arquivos impactados.
- Depois de editar, resuma o diff e proponha validação local.

## Regras de Docência e Biblioteca (Acervo)
- **Trilhas de Estudo:** A trilha de Aprendiz (1º Vigilante) possui 13 etapas e a de Companheiro (2º Vigilante) possui 10 etapas, separando passos orais de passos com entrega de trabalho.
- **Instruções Orais (Sem anexo):** Não possuem card expansível e são controladas/marcadas diretamente por um checkbox na timeline principal (primeira camada visual).
- **Publicação Manual na Biblioteca:** A publicação de trabalhos formativos na biblioteca deve ser uma ação puramente manual e intencional do Vigilante. É iniciada por meio dos atalhos `📤 Publicar` na timeline principal (com stop-propagation no clique) ou `📤 Publicar na Biblioteca` nos detalhes da etapa (quando o status é Concluído).
- **Biblioteca Dividida (Acervo):** Organizada em três sub-abas: **Livros**, **Peças de Arquitetura** e **Trabalhos de Instrução**.
- **Controle de Acesso por Grau:** Peças e Trabalhos de Instrução possuem bloqueio físico de visualização/download para graus inferiores ao grau de restrição da obra (Aprendiz=1, Companheiro=2, Mestre=3). O backend altera a propriedade `arquivo_url` para `null` por segurança se o usuário for de grau menor. Livros exibem recomendação de leitura, mas não sofrem bloqueio.
- **Visualizador Confortável de PDFs:** Utiliza um leitor fullscreen em overlay com cabeçalho limpo, botões de download e de fechar rápido, integrando iframes com sanitização (`DomSanitizer`).
