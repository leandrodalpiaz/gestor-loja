# Relatório de Homologação (HISTÓRICO) - Paridade Desktop + PWA

Data: 2026-05-07

⚠️ **Status atual (2026-05-14): diretriz atualizada.**  
Este relatório registra uma rodada histórica em que o alvo foi “Desktop + PWA”. A diretriz vigente do projeto é:
- Web: gestão completa (desktop-first);
- Mobile principal: **PWA**;
- Telegram: **secundário** (baixo engajamento), usado como complemento.

Mudança de rumo aplicada na época: o alvo oficial do projeto passou a ser Desktop + PWA, com Mini Apps tratadas como secundárias.

Escopo executado: validacao tecnica local, smoke test HTTP de PWA, consulta secundaria de miniapps, simulacao de convites WebApp como evidencia complementar e cruzamento estatico entre Desktop, PWA e permissoes.

Conclusao geral: `APROVADO_COM_GAP_ACEITO`

Motivo: todos os cargos passaram a ter rota PWA oficial e protegida por permissao. Algumas funcionalidades ainda abrem o fluxo Desktop completo a partir do PWA; estes pontos ficam aceitos como gap de paridade nativa, preservando a regra de negocio existente enquanto as telas PWA dedicadas sao aprofundadas.

## Ambiente observado

| Item | Resultado |
| --- | --- |
| PHP CLI | OK - PHP 8.2.30 |
| Composer | FALHA - comando `composer` nao encontrado no PATH |
| Isolamento basico | OK - script retornou `APP_ENV=local`, `DB_SCHEMA=` e `TELEGRAM_DRY_RUN=` com isolamento basico validado |
| Servidor local | OK - PHP embutido em `http://127.0.0.1:8099` durante a execucao |
| Open access local | Ativado para smoke test local |

## Checks executados

| Check | Resultado | Evidencia |
| --- | --- | --- |
| `php scripts/check_dashboard_sections.php` | OK | Dashboard sections unicas para perfis testados |
| `scripts/checklist_local.ps1 -Port 8099 -OpenAccess -SkipTelegram` | OK parcial | 8 PASS, 3 SKIP, 0 FAIL |
| `/health` | OK | HTTP 200 |
| `/login` | OK | Checklist local marcou PASS |
| `/pwa` | OK | HTTP 200 |
| Webhook local | OK | Checklist local marcou PASS |
| Biblioteca ISBN API | OK | Checklist local marcou PASS |
| Tesouraria API | OK | `/api/tesouraria/comprovantes` respondeu `ok=true` |
| Login real | SKIP | `CHECK_LOGIN_CIM` e `CHECK_LOGIN_PASSWORD` ausentes |
| Telegram externo | SKIP | Fora do alvo principal nesta rodada |
| Multi-tenant completo | SKIP | Checklist orienta executar `docs/validacao_multi_tenant.md` |

## Smoke test PWA

| Rota | Status | Decisao |
| --- | --- | --- |
| `/pwa` | 200 | OK |
| `/pwa/sessoes` | 403 | PENDENTE - exige usuario real/permissao para validar fluxo |
| `/pwa/biblioteca` | 403 | PENDENTE - exige usuario real/permissao para validar fluxo |
| `/pwa/comunicacao` | 403 | PENDENTE - exige usuario real/permissao para validar fluxo |
| `/pwa/admin` | 403 | OK como bloqueio de area restrita no smoke sem usuario autorizado |
| `/pwa/perfil` | 200 | OK tecnico |
| `/pwa/obrigacoes` | 200 | OK tecnico |
| `/pwa/comprovantes` | 200 | OK tecnico |
| `/pwa/secretaria` | 200 | OK - modulo PWA oficial por cargo |
| `/pwa/tesouraria` | 200 | OK - modulo PWA oficial por cargo |
| `/pwa/chancelaria` | 200 | OK - tela PWA nativa de check-in, visitantes e presencas |
| `/pwa/biblioteca/emprestimos` | 403 | OK como bloqueio sem perfil bibliotecario no smoke; rota nativa criada |
| `/pwa/biblioteca-gestao` | 200 | OK - modulo PWA oficial por cargo |
| `/pwa/veneravel` | 200 | OK - modulo PWA oficial por cargo |
| `/pwa/chancelaria` | 200 | OK - modulo PWA oficial por cargo |
| `/pwa/hospitaleiro` | 200 | OK - modulo PWA oficial por cargo |
| `/pwa/primeiro-vigilante` | 200 | OK - modulo PWA oficial por cargo |
| `/pwa/segundo-vigilante` | 200 | OK - modulo PWA oficial por cargo |
| `/pwa/orador` | 200 | OK - modulo PWA oficial por cargo |
| `/pwa/mestre-banquetes` | 200 | OK - modulo PWA oficial por cargo |
| `/pwa/mestre-harmonia` | 200 | OK - modulo PWA oficial por cargo |
| `/pwa/administracao` | 200 | OK - modulo PWA oficial por cargo |

## Rotas PWA encontradas

| Area PWA | Rotas |
| --- | --- |
| Home | `/pwa` |
| Sessoes | `/pwa/sessoes`, `/pwa/sessoes/atualizar` |
| Biblioteca | `/pwa/biblioteca`, `/pwa/biblioteca/meus-emprestimos`, `/pwa/biblioteca/detalhes`, `/pwa/biblioteca/adicionar`, `/pwa/biblioteca/classificar` |
| Comunicacao | `/pwa/comunicacao`, `/pwa/comunicacao/ler`, `/pwa/comunicacao/novo` |
| Admin/atalhos | `/pwa/admin` |
| Perfil | `/pwa/perfil` |
| Financeiro pessoal | `/pwa/obrigacoes`, `/pwa/obrigacoes/enviar-comprovante` |
| Comprovantes Tesouraria | `/pwa/comprovantes` |
| Modulos por cargo | `/pwa/secretaria`, `/pwa/tesouraria`, `/pwa/biblioteca-gestao`, `/pwa/veneravel`, `/pwa/chancelaria`, `/pwa/hospitaleiro`, `/pwa/primeiro-vigilante`, `/pwa/segundo-vigilante`, `/pwa/orador`, `/pwa/mestre-banquetes`, `/pwa/mestre-harmonia`, `/pwa/administracao` |

## Cobertura Desktop + PWA por cargo

| Cargo | Desktop | PWA atual | Miniapp secundario | Decisao |
| --- | --- | --- | --- | --- |
| Obreiro | OK | OK - home, sessoes, perfil, financeiro pessoal, biblioteca, solicitar, comentar e reagir | Existe apoio secundario | APROVADO_COM_GAP_ACEITO |
| Tesoureiro | OK | OK - dashboard PWA nativo com caixa, comprovantes, regularidade, fechamento, obrigacoes e sessoes financeiras | Existe apoio secundario | APROVADO_COM_GAP_ACEITO |
| Bibliotecario | OK | OK - catalogo, adicionar, classificar, emprestimos e devolucao no PWA | Existe apoio secundario | APROVADO_COM_GAP_ACEITO |
| Secretario | OK | OK parcial - comunicacao PWA e modulo `/pwa/secretaria` | Existe apoio secundario | APROVADO_COM_GAP_ACEITO |
| Veneravel | OK | OK parcial - modulo `/pwa/veneravel` com acoes Desktop-backed | Existe apoio secundario | APROVADO_COM_GAP_ACEITO |
| Chanceler | OK | OK - tela PWA nativa com sessao, confirmados, visitantes e check-in efetivo | Existe apoio secundario | APROVADO_COM_GAP_ACEITO |
| Hospitaleiro | OK | OK parcial - modulo `/pwa/hospitaleiro` com acoes Desktop-backed | Existe apoio secundario | APROVADO_COM_GAP_ACEITO |
| Primeiro Vigilante | OK | OK parcial - modulo `/pwa/primeiro-vigilante` com acoes Desktop-backed | Existe apoio secundario | APROVADO_COM_GAP_ACEITO |
| Segundo Vigilante | OK | OK parcial - modulo `/pwa/segundo-vigilante` com acoes Desktop-backed | Existe apoio secundario | APROVADO_COM_GAP_ACEITO |
| Orador | OK | OK parcial - modulo `/pwa/orador` com acoes Desktop-backed | Existe apoio secundario | APROVADO_COM_GAP_ACEITO |
| Mestre de Banquetes | OK | OK parcial - modulo `/pwa/mestre-banquetes` com acoes Desktop-backed | Existe apoio secundario | APROVADO_COM_GAP_ACEITO |
| Mestre de Harmonia | OK | OK parcial - modulo `/pwa/mestre-harmonia` com acoes Desktop-backed | Existe apoio secundario | APROVADO_COM_GAP_ACEITO |
| Administracao/Sistema | OK | OK parcial - `/pwa/admin` e `/pwa/administracao` centralizam acoes administrativas | Existe apoio secundario | APROVADO_COM_GAP_ACEITO |

## Evidencia secundaria de miniapps

Miniapps foram testados como informacao complementar, nao como criterio de aprovacao Desktop + PWA.

| Evidencia | Resultado |
| --- | --- |
| Principais rotas `/miniapp/*` | HTTP 200 |
| Simulacao WebApp Bibliotecario | OK |
| Simulacao WebApp Secretario | OK |
| Simulacao WebApp Tesoureiro | OK |
| Simulacao WebApp Chanceler | FALHA - obreiro ja possui Telegram vinculado |

## Gaps aceitos para aprovar Desktop + PWA nesta etapa

| Severidade | Item | Impacto |
| --- | --- | --- |
| GAP_ACEITO | Varias acoes ainda abrem fluxos Desktop a partir do PWA | Mantem regra de negocio e permite uso mobile, mas nao e tela PWA nativa final |
| GAP_ACEITO | Falta teste com usuario real autorizado e negativo por cargo | Smoke local validou rotas; homologacao de producao ainda precisa usuarios reais |
| ALTA | Composer ausente no PATH | Script Composer nao roda como documentado |

## Proxima ordem recomendada

1. Definir quais funcoes de cada cargo devem entrar no PWA e quais serao formalmente aceitas como Desktop-only.
2. Priorizar PWA por cargo nesta ordem: Obreiro, Tesouraria, Secretaria, Biblioteca, Chancelaria, Hospitaleiro, Vigilantes, Veneravel, Orador, Banquetes, Harmonia, Administracao.
3. Criar rotas/controllers/views PWA dedicados por cargo, reaproveitando models e services ja existentes.
4. Manter miniapps como fallback secundario durante a transicao, sem contar como homologacao principal.
5. Reexecutar este relatorio com usuarios reais e negativos por cargo.
