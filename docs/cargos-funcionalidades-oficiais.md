# Cargos — Funcionalidades Oficiais (Web + PWA + Miniapps)

Documento de referência para:
- comparar **planejado vs implementado** por cargo;
- evitar mudanças indevidas (ex.: função “sumir” ou “migrar de cargo”);
- garantir compatibilidade entre **Web**, **PWA** e **Miniapps** (Telegram WebApp).

Fontes:
- `docs/plano-conclusao-cargos-web-mobile.md` (planejado)
- `docs/matriz-acesso-erp.md` (RBAC + regras de organização visual)
- `src/Core/Http/*Routes.php`, `src/Core/Http/MiniappPageRoutes.php`, `src/Core/Http/MiniappApiRoutes.php` (implementado)

## Regras “não negociáveis”
1. **Não mudar lógica de cargos** (competência/decisão permanece com o cargo responsável).
2. **Não duplicar função em menus**: a rota pertence a uma seção prioritária (matriz), outros cargos acessam pela mesma rota.
3. **Permissão é por Authorizer/Guards**, não por “if cargo na view”.
4. **Miniapp = page + api coerentes**: não apontar botão WebApp para rota web “normal” quando existir `/miniapp/*` equivalente.

## Referência de guardrails (onde checar)
- Web: `src/Core/Http/ModuleGuards.php`, `src/Core/Http/WebGuards.php`
- Miniapp pages: `src/Core/Http/MiniappPageRoutes.php` (via `requireMiniappAuth(...)`)
- Miniapp apis: `src/Core/Http/MiniappApiRoutes.php` (permissões via session/initData)

---

## Checklist por cargo (planejado vs implementado)

### 1) Venerável Mestre
**Rotas Web (implementado)**
- Painel/dash: `/veneravel`, `/veneravel/dashboard`
- Ações de sessão: `/veneravel/sessoes/publicar|cancelar|reabrir|realizar`
- Ações de balaústre/votação: `/veneravel/balaustres/abrir-votacao|encerrar-votacao`

**Miniapp pages (implementado)**
- `/miniapp/veneravel`

**Miniapp APIs (implementado)**
- `GET /api/miniapp/veneravel/dashboard`
- `POST /api/miniapp/veneravel/sessao/(publicar|cancelar|reabrir|realizar)`
- `POST /api/miniapp/veneravel/balaustre/(abrir-votacao|encerrar-votacao)`

**Planejado (docs/plano-conclusão...)**
- OK: painel, publicar/cancelar/reabrir/realizar, abrir/encerrar votação.

**Riscos/guardrails**
- Abertura/encerramento de votação deve continuar restrito (`veneravel.manage`).

---

### 2) Secretaria
**Status**: documentado em `docs/secretaria-funcionalidades-oficiais.md`.

---

### 3) Chancelaria (Chanceler de Sessão)
**Rotas Web (implementado)**
- Sessão (dashboard/check-in): `/chanceler/sessao`, `/chanceler/sessao/dashboard`, `/chanceler/sessao/presenca`
- Conteúdos/efemérides: `/chancelaria/efemerides` + salvar/enviar/toggle/excluir
- Histórias: `/chancelaria/historias/*`
- Palavra do dia: `/chancelaria/palavra-dia/*`
- Permissões de conteúdo: `/chancelaria/conteudo-permissoes/salvar`

**Miniapp pages (implementado)**
- `/miniapp/chanceler`

**Miniapp APIs**
- Não há rota dedicada listada no switch para “chanceler/dashboard”; a miniapp provavelmente consome endpoints compartilhados (ver `MiniappApiRoutes.php`).

**Planejado**
- Parcial: check-in e painel existem no web; miniapp page existe; APIs dedicadas podem estar faltando.

**Gaps prováveis**
- Endpoints miniapp dedicados para check-in/presença e nominata/confirmados conforme plano.

---

### 4) Tesouraria
**Rotas Web (implementado)**
- Caixa: `/tesouraria/caixa`
- Sessões/efeitos financeiros: `/tesouraria/sessoes`
- Comprovantes: `/tesouraria/comprovantes`
- Regularidade: `/tesouraria/regularidade`
- Fechamento: `/tesouraria/fechamento`
- Relatório de gestão: `/tesouraria/relatorio-gestao`
- Obrigações: `/tesouraria/obrigacoes` + CRUD parcelas/isencões/recibo
- Pessoal: `/financeiro/minhas-obrigacoes`

**Miniapp pages (implementado)**
- `/miniapp/tesouraria` (route existe em `TesourariaRoutes.php`)

**Miniapp APIs**
- Existem em `src/Core/Http/TesourariaApiRoutes.php` (necessário conferir endpoints e paridade com o web).

**Planejado**
- OK no web; miniapp existe; APIs dedicadas existem (a validar cobertura).

**Riscos/guardrails**
- Separar “meu financeiro” (self) vs operação do tesoureiro (manage).

---

### 5) Hospitaleiro / Assistência
**Rotas Web (implementado)**
- `/assistencia`
- Ocorrências: `/assistencia/ocorrencias/salvar|status|visita`

**Miniapp pages (implementado)**
- `/miniapp/hospitaleiro`

**Miniapp APIs (implementado)**
- `GET /api/miniapp/hospitaleiro/dashboard`
- `POST /api/miniapp/hospitaleiro/ocorrencias/salvar`
- `POST /api/miniapp/hospitaleiro/ocorrencias/status`
- `POST /api/miniapp/hospitaleiro/visita`

**Planejado**
- OK: operar em campo (abrir ocorrência, status, visita).

---

### 6) Primeiro Vigilante
**Rotas Web (implementado)**
- Painel: `/primeiro-vigilante`
- Detalhe do aprendiz: `/primeiro-vigilante/aprendiz`
- Trilhas: `/primeiro-vigilante/trilha/atualizar`, `/primeiro-vigilante/trilha/acao-rapida`
- Leituras: `/primeiro-vigilante/leitura/salvar`
- Certificados: `/primeiro-vigilante/certificado/solicitar`
- Visão do obreiro: `/meu-aprendizado`

**Miniapp pages (implementado)**
- `/miniapp/aprendizado`
- `/miniapp/primeiro-vigilante`

**Miniapp APIs**
- Não há switch explícito no `MiniappApiRoutes.php` para vigilância; a miniapp pode usar endpoints compartilhados ou estar incompleta.

**Planejado**
- Parcial: web está forte; miniapp pages existem; APIs específicas podem estar faltando para operar trilhas via mobile.

---

### 7) Segundo Vigilante
**Rotas Web (implementado)**
- Painel: `/segundo-vigilante`
- Detalhe do companheiro: `/segundo-vigilante/companheiro`
- Trilhas: `/segundo-vigilante/trilha/atualizar`, `/segundo-vigilante/trilha/acao-rapida`
- Leituras: `/segundo-vigilante/leitura/salvar`
- Certificados: `/segundo-vigilante/certificado/solicitar`
- Recomendação de exaltação: `/segundo-vigilante/exaltacao/recomendar`
- Visão do obreiro: `/meu-companheirismo`

**Miniapp pages (implementado)**
- `/miniapp/companheirismo`
- `/miniapp/segundo-vigilante`

**Miniapp APIs**
- Não há switch explícito no `MiniappApiRoutes.php` para vigilância; possível gap.

---

### 8) Orador
**Rotas Web (implementado)**
- Painel/dash: `/orador`, `/orador/dashboard`

**Miniapp pages (implementado)**
- `/miniapp/orador` (aparece via `PainelRoutes.php`)

**Miniapp APIs (implementado)**
- `GET /api/miniapp/orador/dashboard`

**Planejado**
- Parcial: existe payload miniapp e dashboard; validar se cobre “visitantes para palavra a bem”, pauta e lembretes conforme plano.

---

### 9) Mestre de Banquetes
**Rotas Web (implementado)**
- Painel/dash: `/mestre-banquetes`, `/mestre-banquetes/dashboard`
- Operação: `/mestre-banquetes/operacao/salvar`

**Miniapp pages (implementado)**
- `/miniapp/mestre-banquetes`

**Miniapp APIs**
- Não há switch explícito em `MiniappApiRoutes.php` para banquetes; possível gap para operar/salvar via mobile.

---

### 10) Mestre de Harmonia
**Rotas Web (implementado)**
- Painel: `/mestre-harmonia`
- APIs dedicadas: `/api/mestre-harmonia/scan|audio|operador`

**Miniapp pages (implementado)**
- `/miniapp/mestre-harmonia` (em `MestreHarmoniaRoutes.php`)

**Miniapp APIs**
- Operação parece fora do padrão `/api/miniapp/*` e usa `/api/mestre-harmonia/*` (aceitável, mas documentar como exceção).

---

### 11) Biblioteca
**Rotas Web (implementado)**
- Catálogo e self: `/biblioteca`, `/biblioteca/detalhes`, `/biblioteca/meus-emprestimos`
- Self actions: `/biblioteca/solicitar`, `/biblioteca/comentar`, `/biblioteca/reagir`
- Gestão: `/biblioteca/adicionar|editar|excluir`, `/biblioteca/emprestimos`, `/biblioteca/devolver`
- Apoio formativo: `/biblioteca/classificar`

**PWA (implementado)**
- `/pwa/biblioteca`, `/pwa/biblioteca/meus-emprestimos`, `/pwa/biblioteca/detalhes`, `/pwa/biblioteca/adicionar`, `/pwa/biblioteca/classificar`

**Miniapp pages (implementado)**
- `/miniapp/biblioteca`
- utilitários: `/biblioteca/novo`, `/biblioteca/scanner`

**Planejado**
- OK: web + miniapp presentes, com operação administrativa e self.

---

### 12) Administração
**Rotas Web (implementado)**
- Admin: `/admin/cargos`, `/admin/loja`, `/admin/auditoria`, `/admin/acessos`, `/admin/convites`, `/admin/conteudo-publico`
- Suporte: `/admin-suporte`, `/admin-suporte/sair`

**Miniapp pages (implementado)**
- `/miniapp/admin` (observação no código: “admin aqui é recurso técnico, não cargo oficial”)

**Miniapp APIs (implementado)**
- Rotas `POST /api/miniapp/admin/*` para cargos/gestão/configurações (ver `MiniappApiRoutes.php`)

**Guardrail essencial**
- Não confundir “admin técnico do sistema” com “administração do ERP”: manter checagem por permissão (`admin.*`) e, no miniapp, pelo `is_system_admin`/auth.

---

## PWA (geral — áreas transversais)
Rotas PWA detectadas (implementado em `PainelRoutes.php`):
- Sessões: `/pwa/sessoes`, `/pwa/sessoes/atualizar`
- Comunicação: `/pwa/comunicacao`, `/pwa/comunicacao/ler`, `/pwa/comunicacao/novo`
- Admin: `/pwa/admin`

Observação: PWA é superfície transversal; cargos usam via permissão, sem “cópia de regra”.

---

## Top 10 riscos (para evitar regressão)
1. Cargo ver painel “de outro” por link hardcoded em view (quebrar regra 3).
2. Miniapp page existir sem API equivalente (ou vice-versa), gerando botão “faz metade”.
3. Função duplicada em menus (Secretaria/Admin/Chancelaria exibindo mesma função em seções diferentes).
4. Venerável/Admin assumirem operação cotidiana que é da Secretaria (polui governança).
5. “Meu financeiro” ficar preso a permissão de tesouraria (precisa regra clara, ver matriz).
6. Mestre de Harmonia fora do padrão `/api/miniapp/*` sem documentação (quebra integrações futuras).
7. Vigilâncias com miniapp page sem endpoints de operação (parcialidade permanente).
8. Chancelaria com miniapp page sem check-in/presença em API (operação mobile incompleta).
9. Rotas de “conteúdo público” e “permissões de conteúdo” sem guardrails coerentes (risco de vazamento).
10. Mudança de perm map sem atualizar documentos de cargos (perde “fonte viva”).

