# Cargos — Funcionalidades Oficiais (Web + Mini Apps)

Documento de referência para:
- comparar **planejado vs implementado** por cargo;
- evitar mudanças indevidas (ex.: função “sumir” ou “migrar de cargo”);
- garantir compatibilidade entre **Web** e **Mini Apps** (Telegram WebApp).

Fontes (implementado):
- `src/Core/Authorization/PermissionMap.php` (RBAC: roles → permissions e rotas → permission)
- `src/Core/Http/PainelRoutes.php` (rotas web + rotas PWA quando existirem)
- `src/Core/Http/MiniappPageRoutes.php` (páginas miniapp)
- `src/Core/Http/MiniappApiRoutes.php` (APIs miniapp)
- `src/Core/Http/*Routes.php` (rotas por módulo, quando aplicável)

## Regras não negociáveis

1. **Não mudar lógica de cargos**: competência/decisão permanece com o cargo responsável.
2. **Não duplicar função em menus**: a rota pertence a uma seção prioritária; outros cargos acessam pela mesma rota.
3. **Permissão é por Authorizer/Guards**, não por “if cargo na view”.
4. **Mini App = page + api coerentes**: não apontar botão WebApp para rota web “normal” quando existir `/miniapp/*` equivalente.

## Referência de guardrails (onde checar)

- Web: `src/Core/Http/WebGuards.php`, `src/Core/Http/ModuleGuards.php`
- Miniapp pages: `src/Core/Http/MiniappPageRoutes.php` (via `requireMiniappAuth(...)`)
- Miniapp APIs: `src/Core/Http/MiniappApiRoutes.php` (permissões via sessão/init_data)

---

## Checklist por cargo (rotas principais)

Observação: esta lista é um “índice operacional”. A fonte de verdade de permissão/rotas é `PermissionMap.php`.

### Venerável Mestre
- Web: `/veneravel`, `/veneravel/dashboard`
- Ações: `/veneravel/sessoes/publicar|cancelar|reabrir|realizar`
- Balaústre: `/veneravel/balaustres/abrir-votacao|encerrar-votacao`
- Mini App: `/miniapp/veneravel`

### Secretaria
- Web: `/secretaria`
- Mini App: `/miniapp/secretaria`

### Chancelaria (Chanceler)
- Web: `/chanceler/sessao`, `/chanceler/sessao/dashboard`, `/chanceler/sessao/presenca`, `/chanceler/sessao/visitante`
- Mini App: `/miniapp/chanceler`

### Tesouraria
- Web: `/tesouraria/caixa`, `/tesouraria/comprovantes`, `/tesouraria/regularidade`, `/tesouraria/fechamento`, `/tesouraria/obrigacoes`, `/tesouraria/relatorio-gestao`, `/financeiro/minhas-obrigacoes`
- Mini App: `/miniapp/tesouraria`

### Hospitaleiro (Assistência)
- Web: `/assistencia` + ações em `/assistencia/ocorrencias/*`
- Mini App: `/miniapp/hospitaleiro`

### Primeiro Vigilante
- Web: `/primeiro-vigilante` + ações em `/primeiro-vigilante/*`
- Mini App: `/miniapp/primeiro-vigilante` e `/miniapp/aprendizado`

### Segundo Vigilante
- Web: `/segundo-vigilante` + ações em `/segundo-vigilante/*`
- Mini App: `/miniapp/segundo-vigilante` e `/miniapp/companheirismo`

### Orador
- Web: `/orador`, `/orador/dashboard`
- Mini App: `/miniapp/orador`

### Mestre de Banquetes
- Web: `/mestre-banquetes`, `/mestre-banquetes/dashboard`, `/mestre-banquetes/operacao/salvar`
- Mini App: `/miniapp/mestre-banquetes`

### Mestre de Harmonia
- Web: `/mestre-harmonia` + APIs `/api/mestre-harmonia/*`
- Mini App: `/miniapp/mestre-harmonia`

### Biblioteca
- Web (self): `/biblioteca`, `/biblioteca/detalhes`, `/biblioteca/meus-emprestimos`, `/biblioteca/solicitar`, `/biblioteca/comentar`, `/biblioteca/reagir`
- Web (gestão): `/biblioteca/adicionar|editar|excluir|emprestimos|devolver|importar|isbn|classificar|interloja/decidir`
- Mini App: `/miniapp/biblioteca`

### Administração / Sistema
- Web: `/admin/*` (cargos, loja, auditoria, acessos, convites, conteúdo público)
- Mini App técnico (quando aplicável): `/miniapp/admin`
