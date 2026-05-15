# Homologação - Paridade Desktop + Mobile (PWA)

## Objetivo

Homologar a paridade funcional entre **Desktop (gestão completa)** e **Mobile (PWA)**.

Diretriz atual do projeto:
- Desktop-first no web para gestão completa.
- **PWA é a experiência principal no mobile**.
- Telegram é **secundário** e não conta como paridade principal.

## Fontes de verdade

- `docs/regras-de-negocio.md`
- `docs/matriz-acesso-erp.md`
- `docs/cargos-funcionalidades-oficiais.md`
- `src/Core/Authorization/PermissionMap.php`
- `src/Core/Http/PainelRoutes.php`

## Critério de aprovação

Uma funcionalidade está alinhada quando:

- existe no Desktop e no PWA, ou está formalmente marcada como fora do escopo PWA;
- usa a mesma regra de negócio e permissão efetiva (Authorizer/Guards);
- executa a ação principal sem erro e persiste o estado esperado;
- bloqueia usuário sem permissão com mensagem adequada;
- usa UI mobile apropriada: cards em listas operacionais, status como badge forte e sem scroll horizontal.

## Preparação do ambiente

1. Usar ambiente isolado de homologação.
2. Confirmar configuração esperada:
   - `APP_ENV=homolog`
   - `DB_SCHEMA=app_homolog`
   - `TELEGRAM_DRY_RUN=true`
3. Confirmar que nenhum teste aponta para schema de produção.
4. Subir a aplicação e validar:
   - `/health`
   - `/login`
   - `/pwa`

## Registro de evidência

Copiar uma linha para cada ação testada:

| Data/hora | Cargo | Usuário | Canal | Rota | Ação | Esperado | Obtido | Status | Severidade | Evidência |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
|  |  |  | Desktop/PWA |  |  |  |  | OK/FALHA | BLOQUEANTE/ALTA/MEDIA/BAIXA | print/log/id |
