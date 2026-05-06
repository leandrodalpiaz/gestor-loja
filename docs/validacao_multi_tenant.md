# Validação multi-tenant

## Pre-condicoes
- Definir `APP_ENV=local`.
- Definir tenant padrão valido para migrações e bootstrap (`APP_DEFAULT_TENANT_SLUG` ou `APP_LOJA_NUMERO`).
- Rodar migration `042_nominata_multitenant_isolamento.sql`.

## Dados de teste
1. Criar duas lojas:
- `renascenca`
- `loja_teste`
2. Criar dados separados por loja:
- obreiros
- sessões
- cargos/nominata/gestões
- conteudo publico
- assets em `/public/assets/tenants/{tenant_slug}/...`

## Cenarios de validação
1. Acessar tenant `renascenca` e confirmar que:
- obreiros/sessões/nominata exibem apenas dados de `renascenca`
- portal/login exibem branding de `renascenca`
- miniapps exibem apenas dados de `renascenca`
2. Acessar tenant `loja_teste` e confirmar que:
- obreiros/sessões/nominata exibem apenas dados de `loja_teste`
- portal/login exibem branding de `loja_teste`
- miniapps exibem apenas dados de `loja_teste`
3. Confirmar isolamento cruzado:
- Loja A não enxerga dados da Loja B
- Loja B não enxerga dados da Loja A
- login (CIM/telegram) não autentica cruzado entre tenants

## Falhas esperadas (seguranca)
1. Em `homolog/staging/production`, sem tenant resolvido:
- resposta `503` com mensagem `Loja não identificada. Verifique a configuração do ambiente.`
2. Em `local/development`, sem tenant resolvido:
- `/login` abre em modo neutro (formulario desabilitado)
- endpoints fora de `/login` e `/health` retornam bloqueio por tenant ausente
3. Fallback proibido:
- não usar fallback para Renascenca
- não usar fallback para primeira loja do banco

## Comandos uteis
`````powershell
php -l public\index.php
php -l src\Models\Cargo.php
php -l src\Models\Gestao.php
php -l src\Views\login.php
php -l src\Views\dashboard.php
````powershell
php -l public\index.php
php -l src\Models\Cargo.php
php -l src\Models\Gestao.php
php -l src\Views\login.php
php -l src\Views\dashboard.php
```

`````powershell
.\scripts\checklist_local.ps1
````powershell
.\scripts\checklist_local.ps1
```

## Resultado final - 2026-04-27
- Status: APROVADO para onboarding de nova loja.
- Ambiente de teste: `APP_ENV=local` com `APP_ALLOW_ENV_TENANT_FALLBACK=false`.

### Evidencias objetivas
1. Isolamento de dados
- `loja_id=1` não retornou dados de `loja_id=2`.
- `loja_id=2` não retornou dados de `loja_id=1`.

2. Branding por tenant
- `/login?tenant=renascenca` exibiu identidade da Renascenca e não exibiu Loja Teste.
- `/login?tenant=loja-teste` exibiu identidade da Loja Teste e não exibiu Renascenca.
- `/dashboard` manteve identidade coerente com o tenant logado.

3. Comportamento sem tenant
- `/login` sem tenant abriu em modo neutro com formulario desabilitado.
- `/dashboard` sem tenant retornou bloqueio `503`.
- Sem fallback silencioso para tenant padrão.

4. Login isolado
- Fluxo de autenticacao de obreiro permanece escopado por `loja_id` no tenant atual.
- Validação de dashboard por tenant apos login técnico permaneceu isolada por sessão.

### Correcoes aplicadas
- `public/index.php`
	- Resolucao explicita de tenant por `X-Tenant-Slug`, query `tenant` e host.
	- Fallback por variavel de ambiente passou a ser opt-in via `APP_ALLOW_ENV_TENANT_FALLBACK=true`.
	- Comportamento padrão agora bloqueia acesso fora de `/login` e `/health` quando tenant não for resolvido.
- `src/Core/Tenant/StoreTenantResolver.php`
	- Matching de slug robusto para nomes com/sem prefixo `loja-`.
