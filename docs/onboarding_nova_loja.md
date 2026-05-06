# Onboarding de Nova Loja — Checklist Operacional

> **Versão**: 1.0 — 2026-04-27  
> **Audiência**: Administrador do sistema (técnico ou Venerável Mestre com acesso ao banco)  
> **Pré-requisito**: migrations 000–043 aplicadas no ambiente de destino

---

## Visão geral

Cada loja no sistema é identificada por um **slug único** derivado do nome (ex: `renascenca`, `oriente-sul`). O tenant é resolvido por ordem de prioridade:

1. Header HTTP `X-Tenant-Slug`
2. Query string `?tenant=<slug>`
3. Subdomínio do host (`renascenca.erp.example.com`)
4. Sessão PHP (após primeiro login com sucesso)
5. Variável de ambiente (somente se `APP_ALLOW_ENV_TENANT_FALLBACK=true`)

Sem tenant resolvido, o painel exibe 503 (exceto `/login` e `/health`).

---

## Passo 1 — Registrar a loja no banco

Execute no Supabase SQL Editor ou via `psql`:

`````sql
-- Substituir os valores pelos dados reais da nova loja
INSERT INTO public.lojas (numero_loja, sigla, nome, oriente, ativo)
VALUES (
    '999',             -- Número oficial (ex: '270')
    'XYZ',             -- Sigla curta (ex: 'R')
    'Loja Nova XYZ',   -- Nome completo (ex: 'Loja Renascenca')
    'Capital',         -- Oriente (cidade/UF), pode ser NULL
    TRUE
)
ON CONFLICT (numero_loja) DO NOTHING
RETURNING id, numero_loja, sigla, nome;
````sql
-- Substituir os valores pelos dados reais da nova loja
INSERT INTO public.lojas (numero_loja, sigla, nome, oriente, ativo)
VALUES (
    '999',             -- Número oficial (ex: '270')
    'XYZ',             -- Sigla curta (ex: 'R')
    'Loja Nova XYZ',   -- Nome completo (ex: 'Loja Renascenca')
    'Capital',         -- Oriente (cidade/UF), pode ser NULL
    TRUE
)
ON CONFLICT (numero_loja) DO NOTHING
RETURNING id, numero_loja, sigla, nome;
```

> **Anotar** o `id` retornado — será usado nos passos seguintes.

**Verificar:**
`````sql
SELECT id, numero_loja, sigla, nome, oriente, ativo FROM public.lojas ORDER BY id;
````sql
SELECT id, numero_loja, sigla, nome, oriente, ativo FROM public.lojas ORDER BY id;
```

---

## Passo 2 — Determinar o slug do tenant

O slug é o valor que será usado em `?tenant=` e na pasta de assets. Regras:

- Derivar do `sigla` ou do `nome` da loja, em minúsculas, sem acentos, hífens no lugar de espaços
- Exemplos: `renascenca`, `oriente-sul`, `esperanca-270`
- O resolver aceita o slug **com ou sem** prefixo `loja-` (ex: `loja-renascenca` = `renascenca`)
- Deve ser único entre todas as lojas ativas

**Salvar em variável para os próximos passos:**
`````
LOJA_ID=<id retornado no Passo 1>
LOJA_SLUG=<slug escolhido>
````
LOJA_ID=<id retornado no Passo 1>
LOJA_SLUG=<slug escolhido>
```

---

## Passo 3 — Criar configurações institucionais da loja

`````sql
INSERT INTO public.configuracoes_loja (
    loja_id,
    numero_loja,
    nome_completo,
    sigla,
    oriente,
    potencia,
    potencia_sigla,
    -- Campos financeiros opcionais (podem ser atualizados via painel depois)
    pix_chave,
    pix_beneficiario,
    pix_banco,
    mensalidade_aprendiz,
    mensalidade_companheiro,
    mensalidade_mestre,
    aniversario_loja
)
VALUES (
    :LOJA_ID,
    '999',
    'Loja Nova XYZ',
    'XYZ',
    'Capital - UF',
    'Grande Loja do Estado',
    'GLOES',
    NULL,   -- PIX chave (preencher depois)
    NULL,   -- PIX beneficiário
    NULL,   -- PIX banco
    NULL,   -- mensalidade aprendiz
    NULL,   -- mensalidade companheiro
    NULL,   -- mensalidade mestre
    NULL    -- aniversário (DATE)
)
ON CONFLICT (loja_id) DO NOTHING;
````sql
INSERT INTO public.configuracoes_loja (
    loja_id,
    numero_loja,
    nome_completo,
    sigla,
    oriente,
    potencia,
    potencia_sigla,
    -- Campos financeiros opcionais (podem ser atualizados via painel depois)
    pix_chave,
    pix_beneficiario,
    pix_banco,
    mensalidade_aprendiz,
    mensalidade_companheiro,
    mensalidade_mestre,
    aniversario_loja
)
VALUES (
    :LOJA_ID,
    '999',
    'Loja Nova XYZ',
    'XYZ',
    'Capital - UF',
    'Grande Loja do Estado',
    'GLOES',
    NULL,   -- PIX chave (preencher depois)
    NULL,   -- PIX beneficiário
    NULL,   -- PIX banco
    NULL,   -- mensalidade aprendiz
    NULL,   -- mensalidade companheiro
    NULL,   -- mensalidade mestre
    NULL    -- aniversário (DATE)
)
ON CONFLICT (loja_id) DO NOTHING;
```

> Se a loja já tiver sido criada sem configurações (row ausente), este INSERT basta.
> Para atualizar campos depois: **Painel → Configurações da Loja**.

---

## Passo 4 — Backfill de cargos (nominata base)

A migration 042 adicionou `loja_id` nos cargos. Para nova loja, clonar os cargos padrão:

`````sql
-- Clona todos os cargos sem loja_id (padrão legado) para a nova loja
INSERT INTO public.cargos (codigo, nome, descricao, ordem, loja_id)
SELECT codigo, nome, descricao, ordem, :LOJA_ID
FROM public.cargos
WHERE loja_id IS NULL
ON CONFLICT DO NOTHING;
````sql
-- Clona todos os cargos sem loja_id (padrão legado) para a nova loja
INSERT INTO public.cargos (codigo, nome, descricao, ordem, loja_id)
SELECT codigo, nome, descricao, ordem, :LOJA_ID
FROM public.cargos
WHERE loja_id IS NULL
ON CONFLICT DO NOTHING;
```

> Se a loja usa cargos totalmente distintos, insira-os manualmente.

---

## Passo 5 — Cadastrar o primeiro obreiro administrador

### 5a — Via SQL (setup inicial)
`````sql
INSERT INTO public.obreiros (
    id,
    nome,
    cim,
    grau,
    email,
    ativo,
    loja_id
)
VALUES (
    gen_random_uuid(),
    'Nome Completo do Administrador',
    'CIM-ÚNICO',     -- CIM único por loja (constraint ux em migration 039)
    3,               -- grau: 1=Aprendiz, 2=Companheiro, 3=Mestre
    'admin@loja.org',
    TRUE,
    :LOJA_ID
)
RETURNING id;
````sql
INSERT INTO public.obreiros (
    id,
    nome,
    cim,
    grau,
    email,
    ativo,
    loja_id
)
VALUES (
    gen_random_uuid(),
    'Nome Completo do Administrador',
    'CIM-ÚNICO',     -- CIM único por loja (constraint ux em migration 039)
    3,               -- grau: 1=Aprendiz, 2=Companheiro, 3=Mestre
    'admin@loja.org',
    TRUE,
    :LOJA_ID
)
RETURNING id;
```

### 5b — Definir role de acesso

`````sql
-- Verificar tabela de roles do sistema (acesso_usuarios ou equivalente)
-- Exemplo genérico — ajustar ao schema real:
UPDATE public.obreiros
SET role = 'admin'   -- ou campo equivalente
WHERE id = '<uuid-retornado>'
  AND loja_id = :LOJA_ID;
````sql
-- Verificar tabela de roles do sistema (acesso_usuarios ou equivalente)
-- Exemplo genérico — ajustar ao schema real:
UPDATE public.obreiros
SET role = 'admin'   -- ou campo equivalente
WHERE id = '<uuid-retornado>'
  AND loja_id = :LOJA_ID;
```

### 5c — Gerar convite de ativação (setup de senha)

`````sql
INSERT INTO public.convites_acesso (
    id,
    obreiro_id,
    token,
    expira_em
)
VALUES (
    gen_random_uuid(),
    '<uuid-obreiro>',
    encode(gen_random_bytes(32), 'hex'),  -- token seguro
    NOW() + INTERVAL '72 hours'
)
RETURNING token;
````sql
INSERT INTO public.convites_acesso (
    id,
    obreiro_id,
    token,
    expira_em
)
VALUES (
    gen_random_uuid(),
    '<uuid-obreiro>',
    encode(gen_random_bytes(32), 'hex'),  -- token seguro
    NOW() + INTERVAL '72 hours'
)
RETURNING token;
```

Enviar o link para o administrador: `https://<host>/setup_senha?token=<token>`

---

## Passo 6 — Preparar assets de tenant

Estrutura esperada em `public/assets/tenants/{LOJA_SLUG}/`:

`````
public/assets/tenants/{LOJA_SLUG}/
├── logo.svg          ← ou logo.png (preferido)
└── portal/
    └── hero/
        └── capa-institucional.svg   ← imagem da landing/login
````
public/assets/tenants/{LOJA_SLUG}/
├── logo.svg          ← ou logo.png (preferido)
└── portal/
    └── hero/
        └── capa-institucional.svg   ← imagem da landing/login
```

**Passos:**
1. Criar a pasta: `public/assets/tenants/{LOJA_SLUG}/`
2. Copiar/exportar o logo da loja como `logo.png` (≤ 200KB, proporção 1:1 ou horizontal)
3. Criar imagem hero como `capa-institucional.svg` (ou `.png`, dimensão mínima 1200×400)
4. Se não houver assets, o sistema usa automaticamente `/assets/placeholders/logo-loja.svg` — **aceitável para testes**

---

## Passo 7 — Configurar resolução de tenant no ambiente

### Opção A — Subdomínio (recomendado para produção)
Configurar DNS/proxy: `{slug}.erp.example.com → mesma origem`

O sistema detecta `{slug}` do subdomínio automaticamente.

### Opção B — Query string (para testes e homologação)
Acessar: `https://<host>/login?tenant={slug}`

Não requer configuração adicional.

### Opção C — Header HTTP (integrações/API)
Enviar: `X-Tenant-Slug: {slug}` em cada requisição.

### Opção D — Variável de ambiente (instância dedicada, opt-in)
No `.env` da instância:
`````env
APP_ALLOW_ENV_TENANT_FALLBACK=true
APP_DEFAULT_TENANT_SLUG={slug}
APP_LOJA_NUMERO={numero_loja}
````env
APP_ALLOW_ENV_TENANT_FALLBACK=true
APP_DEFAULT_TENANT_SLUG={slug}
APP_LOJA_NUMERO={numero_loja}
```

> **Atenção**: este modo só é seguro em instâncias Docker/Render dedicadas a uma única loja.
> Em instância compartilhada, use A, B ou C.

---

## Passo 8 — Smoke test de onboarding

Execute manualmente ou com o script Python em `scripts/tmp/`:

### 8a — Verificar registro no banco
`````sql
SELECT l.id, l.sigla, l.nome, c.pix_chave
FROM public.lojas l
LEFT JOIN public.configuracoes_loja c ON c.loja_id = l.id
WHERE l.sigla = 'XYZ';
````sql
SELECT l.id, l.sigla, l.nome, c.pix_chave
FROM public.lojas l
LEFT JOIN public.configuracoes_loja c ON c.loja_id = l.id
WHERE l.sigla = 'XYZ';
```
**Esperado**: 1 row com dados completos.

### 8b — Verificar branding na tela de login
`````
GET /login?tenant={LOJA_SLUG}
````
GET /login?tenant={LOJA_SLUG}
```
Checar:
- [ ] Título da página contém o nome da nova loja (não "Renascença")
- [ ] Logo carregado (tenant-specific ou placeholder neutro)
- [ ] Formulário de login habilitado (não desativado)
- [ ] Nenhuma menção a outra loja no HTML

### 8c — Login e dashboard
1. Acessar `/login?tenant={LOJA_SLUG}`
2. Fazer login com o obreiro administrador criado no Passo 5
3. Checar:
   - [ ] Dashboard exibe nome da nova loja
   - [ ] Sessão contém `tenant_slug={LOJA_SLUG}` e `tenant_id={LOJA_ID}`
   - [ ] Nenhum dado de outra loja visível em listagens

### 8d — Isolamento de dados
`````sql
-- Confirmar que obreiros estão isolados por loja
SELECT COUNT(*) FROM public.obreiros WHERE loja_id = :LOJA_ID;
-- Deve retornar >= 1

-- Confirmar que cargos estão isolados
SELECT COUNT(*) FROM public.cargos WHERE loja_id = :LOJA_ID;
-- Deve retornar >= 1 (cargos clonados no Passo 4)
````sql
-- Confirmar que obreiros estão isolados por loja
SELECT COUNT(*) FROM public.obreiros WHERE loja_id = :LOJA_ID;
-- Deve retornar >= 1

-- Confirmar que cargos estão isolados
SELECT COUNT(*) FROM public.cargos WHERE loja_id = :LOJA_ID;
-- Deve retornar >= 1 (cargos clonados no Passo 4)
```

---

## Passo 9 — Validação completa (referência cruzada)

Executar a bateria de smoke test documentada em [docs/validacao_multi_tenant.md](validacao_multi_tenant.md), seção "Rotina de Validação", substituindo `loja-teste` pelo slug da nova loja.

**Critérios de aceite:**
- Categoria 1 (Estrutura DB): todos os checks PASS
- Categoria 2 (Isolamento de dados): todos os checks PASS
- Categoria 3 (Branding): nenhuma referência a outra loja nas telas da nova loja
- Categoria 4 (Sessão): `tenant_slug` e `tenant_id` corretos após login
- Categoria 5 (Assets): logo e hero carregados (ou placeholder neutro — sem logo de outra loja)

---

## Checklist resumido (para uso rápido)

`````
[ ] 1. INSERT em public.lojas — anotar id retornado
[ ] 2. Definir slug do tenant (minúsculas, sem acentos)
[ ] 3. INSERT em public.configuracoes_loja com loja_id
[ ] 4. Clone de cargos padrão para a nova loja_id
[ ] 5. Cadastrar obreiro admin + gerar convite de ativação
[ ] 6. Criar pasta public/assets/tenants/{slug}/ com logo
[ ] 7. Definir método de resolução de tenant (subdomínio/query/env)
[ ] 8. Smoke test: login, branding, isolamento de dados
[ ] 9. Validação cruzada com docs/validacao_multi_tenant.md
````
[ ] 1. INSERT em public.lojas — anotar id retornado
[ ] 2. Definir slug do tenant (minúsculas, sem acentos)
[ ] 3. INSERT em public.configuracoes_loja com loja_id
[ ] 4. Clone de cargos padrão para a nova loja_id
[ ] 5. Cadastrar obreiro admin + gerar convite de ativação
[ ] 6. Criar pasta public/assets/tenants/{slug}/ com logo
[ ] 7. Definir método de resolução de tenant (subdomínio/query/env)
[ ] 8. Smoke test: login, branding, isolamento de dados
[ ] 9. Validação cruzada com docs/validacao_multi_tenant.md
```

---

## Solução de problemas frequentes

| Sintoma | Causa provável | Solução |
|---|---|---|
| Tela de login desabilitada (campos cinza) | Tenant não resolvido | Confirmar `?tenant=<slug>` na URL ou subdomínio configurado |
| Login exibe nome/logo de outra loja | Fallback de tenant ativo | Checar se `APP_ALLOW_ENV_TENANT_FALLBACK=true` e `APP_DEFAULT_TENANT_SLUG` está apontando para loja errada |
| Erro 503 no painel após login | `tenant_id` não gravado na sessão | Verificar se slug bate com `sigla`, `nome` ou número em `public.lojas` |
| Cargos não aparecem na nominata | Cargos não clonados para a nova `loja_id` | Executar o INSERT do Passo 4 |
| Convite de ativação expirado | Token vencido (> 72h) | Gerar novo token via SQL e reenviar link |
| Logo não carrega | Pasta de assets ausente ou caminho errado | Criar `public/assets/tenants/{slug}/logo.svg` ou `.png` |

---

## Referências

- [docs/validacao_multi_tenant.md](validacao_multi_tenant.md) — resultado da bateria de smoke test multi-tenant
- [docs/regras-de-negócio.md](regras-de-negócio.md) — regras de negócio do sistema
- [docs/matriz-acesso-erp.md](matriz-acesso-erp.md) — perfis e permissões de acesso
- `database/migrations/008_biblioteca_paridade_multiloja.sql` — schema da tabela `lojas`
- `database/migrations/030_configuracoes_loja_multitenant.sql` — `configuracoes_loja` multi-tenant
- `database/migrations/042_nominata_multitenant_isolamento.sql` — isolamento de cargos/gestões
- `src/Core/Tenant/TenantAssetResolver.php` — resolução de assets por tenant
- `src/Core/Tenant/StoreTenantResolver.php` — resolução de loja por slug no banco
