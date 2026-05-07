# Homologacao - Paridade Desktop + PWA

## Objetivo

Homologar a paridade funcional entre Desktop e PWA. O Desktop continua sendo a superficie completa de gestao e o PWA passa a ser a superficie mobile oficial. Miniapps Telegram ficam como canal secundario/complementar e nao contam como paridade principal.

## Fontes de verdade

- `docs/cargos-funcionalidades-oficiais.md`
- `docs/matriz-acesso-erp.md`
- `docs/plano-conclusao-cargos-web-mobile.md`
- `src/Core/Authorization/PermissionMap.php`
- `src/Core/Http/PainelRoutes.php`
- `src/Controllers/Pwa*Controller.php`
- `src/Views/pwa/*`

Miniapps podem ser consultados como referencia de regra ou fallback operacional, mas uma funcionalidade planejada para o mobile so e considerada homologada quando existir no PWA ou quando houver aceite explicito de que ficara fora do PWA.

## Criterio de aprovacao

Uma funcionalidade esta alinhada quando:

- existe no Desktop e no PWA, ou esta formalmente marcada como fora do escopo PWA;
- usa a mesma regra de negocio e permissao efetiva do Desktop;
- executa a acao principal sem erro e persiste o estado esperado;
- bloqueia usuario sem permissao com mensagem adequada;
- usa UI mobile apropriada: cards em listas operacionais, status como badge forte e sem scroll horizontal.

A homologacao geral so pode ser aprovada quando todos os cargos estiverem com decisao `APROVADO` ou `APROVADO_COM_GAP_ACEITO`.

## Preparacao do ambiente

1. Usar ambiente isolado de homologacao.
2. Confirmar configuracao esperada:
   - `APP_ENV=homolog`
   - `DB_SCHEMA=app_homolog`
   - `TELEGRAM_DRY_RUN=true`
3. Confirmar que nenhum teste aponta para `app_prod`.
4. Confirmar feature flags:
   - `FEATURE_PWA_SESSOES=true`
   - `FEATURE_PWA_BIBLIOTECA=true`
   - `FEATURE_PWA_COMUNICACAO=true`
   - `FEATURE_PWA_ADMIN_CRUD=true`
5. Subir a aplicacao e validar:
   - `/health`
   - `/login`
   - `/pwa`
   - rotas PWA por perfil
6. Separar usuarios de teste:
   - um usuario autorizado por cargo;
   - um usuario sem permissao para o mesmo cargo;
   - um usuario comum `obreiro`;
   - um usuario admin tecnico, quando o teste envolver Sistema.

## Regras de execucao

- Nao alterar schema, migracoes, `.env`, tokens, credenciais ou configuracoes produtivas durante a homologacao.
- Validar persistencia apenas por consultas de conferencia.
- Testar sempre usuario autorizado e usuario nao autorizado.
- Registrar evidencia antes de corrigir qualquer falha.
- Tratar miniapp como evidencia secundaria, nunca como substituto automatico do PWA.
- Classificar toda falha por severidade:
  - `BLOQUEANTE`: funcao essencial ausente no PWA, permissao errada, erro de banco, acao nao persiste ou fluxo critico quebra.
  - `ALTA`: funcao existe no PWA, mas a regra diverge do Desktop.
  - `MEDIA`: UX mobile prejudica a operacao, mas o fluxo conclui.
  - `BAIXA`: texto, visual ou ajuste cosmetico sem impacto funcional.

## Matriz de homologacao por cargo

| Cargo | Desktop | PWA | Permissao | Persistencia | Miniapp secundario | Decisao | Gaps |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Obreiro | PENDENTE | PENDENTE | PENDENTE | PENDENTE | INFO | PENDENTE |  |
| Tesoureiro | PENDENTE | PENDENTE | PENDENTE | PENDENTE | INFO | PENDENTE |  |
| Bibliotecario | PENDENTE | PENDENTE | PENDENTE | PENDENTE | INFO | PENDENTE |  |
| Secretario | PENDENTE | PENDENTE | PENDENTE | PENDENTE | INFO | PENDENTE |  |
| Veneravel | PENDENTE | PENDENTE | PENDENTE | PENDENTE | INFO | PENDENTE |  |
| Chanceler | PENDENTE | PENDENTE | PENDENTE | PENDENTE | INFO | PENDENTE |  |
| Hospitaleiro | PENDENTE | PENDENTE | PENDENTE | PENDENTE | INFO | PENDENTE |  |
| Primeiro Vigilante | PENDENTE | PENDENTE | PENDENTE | PENDENTE | INFO | PENDENTE |  |
| Segundo Vigilante | PENDENTE | PENDENTE | PENDENTE | PENDENTE | INFO | PENDENTE |  |
| Orador | PENDENTE | PENDENTE | PENDENTE | PENDENTE | INFO | PENDENTE |  |
| Mestre de Banquetes | PENDENTE | PENDENTE | PENDENTE | PENDENTE | INFO | PENDENTE |  |
| Mestre de Harmonia | PENDENTE | PENDENTE | PENDENTE | PENDENTE | INFO | PENDENTE |  |
| Administracao/Sistema | PENDENTE | PENDENTE | PENDENTE | PENDENTE | INFO | PENDENTE |  |

Valores aceitos nas colunas de status: `OK`, `FALHA`, `NAO_APLICA`, `GAP_ACEITO`, `PENDENTE`, `INFO`.

Valores aceitos em decisao: `APROVADO`, `APROVADO_COM_GAP_ACEITO`, `REPROVADO`, `PENDENTE`.

## Roteiro por cargo

### Obreiro

Validar no PWA:

- `/pwa` abre e mostra atalhos pessoais corretos.
- `/pwa/sessoes` lista sessoes futuras.
- Confirmar presenca com agape, confirmar sem agape, marcar ausencia e cancelar resposta.
- `/pwa/perfil` mostra dados do usuario autenticado.
- `/pwa/obrigacoes` lista obrigacoes proprias.
- `/pwa/obrigacoes/enviar-comprovante` registra comprovante pendente.
- `/pwa/biblioteca`, detalhes e meus emprestimos funcionam.
- Solicitar emprestimo, comentar e reagir existem no PWA ou estao registrados como gap.

### Tesoureiro

Validar no PWA:

- Painel operacional de tesouraria PWA abre para `tesouraria.manage`.
- Comprovantes pendentes aparecem e podem ser aprovados/rejeitados.
- Regularidade, fechamento, obrigacoes, caixa/resumo e sessao/agape financeiro existem no PWA ou estao registrados como gap.
- `financeiro.self` nao concede operacao de tesouraria.
- `tesouraria.manage` nao bloqueia acesso ao financeiro pessoal.

### Bibliotecario

Validar no PWA:

- Catalogo, busca e detalhe abrem.
- Adicionar item funciona para `biblioteca.manage`.
- Classificar item funciona para `biblioteca.classificar`.
- Solicitar emprestimo, comentarios, reacoes, emprestimos, devolucao e gestao operacional existem no PWA ou estao registrados como gap.
- Usuario sem `biblioteca.manage` nao ve nem executa operacoes administrativas.

### Secretario

Validar no PWA:

- Painel de Secretaria PWA abre para `secretaria.manage`.
- Agenda, criar/editar/publicar/cancelar/reabrir sessao, confirmados, agape, trabalhos, balaustre, comunicacao e relatorio anual existem no PWA ou estao registrados como gap.
- Comunicacao oficial permite criar e ler comunicado para perfil autorizado.

### Veneravel

Validar no PWA:

- Painel executivo PWA abre para `veneravel.manage`.
- Publicar, cancelar, reabrir e marcar sessao como realizada funcionam.
- Abrir e encerrar votacao de balaustre funcionam.
- Pendencias criticas aparecem de forma priorizada.

### Chanceler

Validar no PWA:

- Painel de Chancelaria PWA abre para `chancelaria.manage`.
- Check-in de presenca efetiva funciona.
- Nominata prevista, confirmados, visitantes, lista final de presentes, certificado e conteudos/efemerides existem no PWA ou estao registrados como gap.

### Hospitaleiro

Validar no PWA:

- Painel de Assistencia PWA abre para `hospitaleiro.manage`.
- Abrir ocorrencia, atualizar status, registrar visita, registrar apoio e encaminhar para Tesouraria/Veneravel funcionam.
- Pendencias da assistencia aparecem primeiro.

### Primeiro Vigilante

Validar no PWA:

- Painel PWA do Primeiro Vigilante abre para `vigilancia.primeiro.manage`.
- Lista de aprendizes, detalhe, trilha, avancar etapa, observacao/devolutiva, leitura sugerida, historico e certificado existem no PWA ou estao registrados como gap.

### Segundo Vigilante

Validar no PWA:

- Painel PWA do Segundo Vigilante abre para `vigilancia.segundo.manage`.
- Lista de companheiros, detalhe, trilha, avancar etapa, orientacao/devolutiva, docencia, exaltacao, historico e certificado existem no PWA ou estao registrados como gap.

### Orador

Validar no PWA:

- Painel PWA do Orador abre para `orador.view`.
- Resumo da proxima sessao, visitantes, pauta, cargos da sessao, eventos e lembretes existem no PWA ou estao registrados como gap.

### Mestre de Banquetes

Validar no PWA:

- Painel PWA abre para `mestre_banquetes.manage`.
- Resumo da sessao, total de confirmados, total de agape, lista nominal, observacoes logisticas e fechamento operacional existem no PWA ou estao registrados como gap.

### Mestre de Harmonia

Validar no PWA:

- Painel PWA abre para `mestre_harmonia.manage`.
- Base musical, operador, etapa atual, selecao de faixa, play/pause/avanco e controle remoto existem no PWA ou estao registrados como gap.

### Administracao/Sistema

Validar no PWA:

- Administracao PWA abre somente para perfil autorizado.
- Gestao de cargos, gestoes, parametros, auditoria, convites, acessos e conteudo publico existem no PWA ou estao registrados como gap.
- Admin tecnico fica separado de Administracao do ERP.

## Registro de evidencia

Copiar uma linha para cada acao testada:

| Data/hora | Cargo | Usuario | Canal | Rota/botao | Acao | Esperado | Obtido | Status | Severidade | Evidencia |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
|  |  |  | Desktop/PWA |  |  |  |  | OK/FALHA | BLOQUEANTE/ALTA/MEDIA/BAIXA | print/log/id |

## Checklist de fechamento

- Rodar checklist local conforme `README.md`.
- Validar PWA instalado em mobile real ou viewport mobile.
- Validar login real com usuario por cargo.
- Validar bloqueio de usuario sem permissao por cargo.
- Validar ausencia de scroll horizontal nas telas PWA.
- Verificar logs durante a janela de teste:
  - sem 403 indevido;
  - sem erro recorrente de webhook;
  - sem `SQLSTATE` recorrente;
  - sem falha de rota PWA.
- Registrar miniapps apenas como observacao secundaria, se forem testados.

## Relatorio final

Usar este formato no encerramento:

| Cargo | Decisao | Evidencias principais PWA | Miniapp secundario | Gaps aceitos | Bloqueantes |
| --- | --- | --- | --- | --- | --- |
| Obreiro |  |  |  |  |  |
| Tesoureiro |  |  |  |  |  |
| Bibliotecario |  |  |  |  |  |
| Secretario |  |  |  |  |  |
| Veneravel |  |  |  |  |  |
| Chanceler |  |  |  |  |  |
| Hospitaleiro |  |  |  |  |  |
| Primeiro Vigilante |  |  |  |  |  |
| Segundo Vigilante |  |  |  |  |  |
| Orador |  |  |  |  |  |
| Mestre de Banquetes |  |  |  |  |  |
| Mestre de Harmonia |  |  |  |  |  |
| Administracao/Sistema |  |  |  |  |  |

Conclusao geral permitida:

- `APROVADO`: sem bloqueantes e sem gaps nao aceitos.
- `APROVADO_COM_GAP_ACEITO`: sem bloqueantes, com gaps documentados e aceitos.
- `REPROVADO`: existe ao menos um bloqueante.
