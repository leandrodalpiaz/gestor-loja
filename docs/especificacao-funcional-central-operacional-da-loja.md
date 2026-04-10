# ESPECIFICACAO FUNCIONAL - CENTRAL OPERACIONAL DA LOJA

## 1. Objetivo

Este documento define a direcao funcional para o nucleo de sessoes e operacao da loja dentro do ERP `gestor-loja`.

O objetivo nao e criar um modulo isolado "do secretario", mas sim um nucleo unico de informacoes operacionais da loja, no qual:

- o Secretario atua como operador principal da agenda e das sessoes;
- o Chanceler consome informacoes de presenca e nominata;
- o Mestre de Banquetes consome informacoes de confirmacao e agape;
- o Administrador atua como suporte, governanca e auditoria;
- o membro interage apenas com os fluxos que lhe dizem respeito.

## 2. Premissas

- O sistema e fechado e atende uma unica loja.
- A loja ja existe no contexto do ERP e nao deve ser cadastrada dentro deste modulo.
- IA fica fora do escopo desta etapa.
- Telegram pode existir como canal complementar, mas nao como fonte principal da regra de negocio.
- A sessao da loja deve ser a entidade central deste modulo.

## 3. Problema que o modulo precisa resolver

Hoje existe uma necessidade operacional compartilhada entre cargos:

- o Secretario precisa planejar, publicar e manter a agenda oficial da loja;
- o Secretario precisa publicar o resumo da proxima sessao;
- o Chanceler precisa da nominata e da informacao de presenca;
- o Mestre de Banquetes precisa saber quantos participarao do agape;
- o Administrador precisa intervir quando houver erro, ajuste ou necessidade de governanca.

Se cada cargo tiver seu proprio fluxo isolado, a informacao se fragmenta.

Por isso, a proposta correta e concentrar tudo em um nucleo unico:

- sessao planejada;
- publicacao oficial;
- confirmacao de presenca;
- confirmacao de agape;
- presenca efetiva;
- saidas operacionais por cargo.

## 4. Conceito central

A entidade principal do modulo deve ser a `sessao`.

Tudo o que os cargos precisam passa a orbitar a sessao:

- dados principais da sessao;
- status da sessao;
- publicacao da sessao;
- confirmacoes;
- opcao de agape;
- presenca efetiva;
- comunicacoes derivadas;
- auditoria das alteracoes.

## 5. Papeis e responsabilidades

### 5.1 Secretario

Responsabilidades principais:

- criar sessoes;
- editar sessoes;
- cancelar sessoes;
- reabrir sessoes canceladas;
- manter agenda anual da loja;
- publicar agenda oficial;
- publicar resumo da proxima sessao;
- acompanhar confirmacoes;
- exportar ou copiar lista de confirmados;
- registrar ajustes operacionais da sessao.

### 5.2 Chanceler

Responsabilidades ligadas ao consumo de dados da sessao:

- consultar confirmados;
- consultar nominata prevista;
- consultar ou registrar presenca efetiva, conforme regra final do projeto;
- usar os dados da sessao para atividades de chancelaria relacionadas ao encontro.

### 5.3 Mestre de Banquetes

Responsabilidades ligadas ao agape:

- consultar quantidade de confirmados para o agape;
- consultar lista de participantes do agape;
- consultar observacoes operacionais ligadas ao agape;
- obter previsao para organizacao do abastecimento.

### 5.4 Administrador

Responsabilidades de suporte e governanca:

- corrigir dados;
- supervisionar permissoes;
- agir como contingencia operacional;
- auditar alteracoes importantes.

### 5.5 Membro

Interacoes permitidas:

- visualizar sessoes visiveis;
- confirmar presenca;
- cancelar confirmacao;
- informar participacao no agape, quando aplicavel;
- consultar sua propria resposta.

## 6. Regra central de negocio

Toda sessao pertence a loja atual do ERP.

Nao deve existir selecao manual de loja dentro deste modulo.

Os dados institucionais da loja devem ser herdados do ERP principal, quando precisarem aparecer em publicacoes, cabecalhos ou documentos.

## 7. Funcoes obrigatorias do nucleo

### 7.1 Agenda oficial da loja

- cadastrar o planejamento anual;
- listar sessoes futuras;
- permitir alteracao pontual de data, hora, tipo ou pauta;
- permitir marcar mudanca relevante;
- permitir publicar a agenda oficial.

### 7.2 Proxima sessao

- destacar a proxima sessao oficial;
- permitir gerar/publicar resumo operacional da proxima sessao;
- permitir atualizar o resumo quando houver alteracao.

### 7.3 Confirmacao de presenca

- permitir ao membro confirmar presenca;
- permitir ao membro cancelar presenca;
- garantir unicidade por usuario e sessao;
- permitir ao Secretario consultar os confirmados.

### 7.4 Agape

- permitir registrar se o membro participara do agape;
- permitir consolidar total previsto para o agape;
- permitir ao Mestre de Banquetes consultar lista e quantidade.

### 7.5 Presenca efetiva

- permitir registrar presenca real no dia da sessao, se este fluxo for adotado;
- produzir nominata dos presentes;
- permitir ao Chanceler consultar ou operar este fechamento.

### 7.6 Alteracoes relevantes

- mudar data/hora;
- mudar pauta;
- cancelar sessao;
- reabrir sessao cancelada;
- registrar autor e data da alteracao;
- opcionalmente disparar comunicacao complementar aos confirmados.

## 8. Estados da sessao

Recomendacao de estados minimos:

- `planejada`
- `publicada`
- `alterada`
- `cancelada`
- `realizada`

Observacoes:

- `planejada` significa cadastrada internamente, mas ainda nao assumida como agenda oficial;
- `publicada` significa valida para consumo institucional;
- `alterada` significa publicada, porem com modificacao relevante posterior;
- `cancelada` significa retirada da agenda ativa;
- `realizada` significa encerrada operacionalmente.

## 9. Informacoes produzidas por uma sessao

Cada sessao deve concentrar, no minimo:

- data e hora;
- tipo de sessao;
- grau;
- titulo ou referencia curta;
- pauta ou resumo;
- local, se necessario;
- status;
- observacao administrativa;
- data de publicacao;
- autor da criacao;
- autor da ultima alteracao.

## 10. Informacoes derivadas para outros cargos

### 10.1 Saidas para o Secretario

- agenda anual;
- proxima sessao;
- lista de confirmados;
- resumo da sessao;
- historico de alteracoes.

### 10.2 Saidas para o Chanceler

- nominata prevista;
- confirmados;
- presenca efetiva;
- lista final de presentes, se o fluxo de fechamento for adotado.

### 10.3 Saidas para o Mestre de Banquetes

- total de confirmados no agape;
- lista nominal dos participantes do agape;
- variacoes apos alteracoes de sessao ou de confirmacao.

### 10.4 Saidas para o Administrador

- auditoria;
- rastreio de alteracoes;
- ajustes e correcao de dados;
- visao consolidada de governanca.

## 11. Matriz funcional resumida

| Fluxo | Membro | Secretario | Chanceler | Mestre de Banquetes | Administrador |
|---|---|---|---|---|---|
| Visualizar sessoes | sim | sim | sim | sim | sim |
| Criar sessao | nao | sim | nao | nao | sim |
| Editar sessao | nao | sim | nao | nao | sim |
| Cancelar/reabrir sessao | nao | sim | nao | nao | sim |
| Publicar agenda oficial | nao | sim | nao | nao | sim |
| Publicar resumo da proxima sessao | nao | sim | nao | nao | sim |
| Confirmar presenca | sim | sim | consulta | consulta | sim |
| Informar participacao no agape | sim | sim | consulta | consulta principal | sim |
| Consultar confirmados | propria resposta | sim | sim | parcial | sim |
| Consultar dados do agape | nao | sim | nao | sim | sim |
| Consultar nominata/presentes | nao | sim | sim | nao | sim |

## 12. Modelo funcional minimo de dados

### 12.1 Entidades principais

- `sessoes`
- `confirmacoes_sessao`
- `presencas_sessao`
- `publicacoes_sessao`
- `historico_sessao`

### 12.2 Estrutura minima sugerida para `sessoes`

- `id`
- `loja_id` ou referencia implicita ao contexto institucional
- `data_hora_inicio`
- `tipo_sessao`
- `grau_sessao`
- `titulo`
- `pauta`
- `status`
- `publicada_em`
- `criada_por`
- `atualizada_por`
- `created_at`
- `updated_at`

### 12.3 Estrutura minima sugerida para `confirmacoes_sessao`

- `id`
- `sessao_id`
- `obreiro_id`
- `status_confirmacao`
- `participara_agape`
- `observacao`
- `respondido_em`
- unicidade por `sessao_id + obreiro_id`

### 12.4 Estrutura minima sugerida para `presencas_sessao`

- `id`
- `sessao_id`
- `obreiro_id`
- `presente`
- `registrado_por`
- `registrado_em`

### 12.5 Estrutura minima sugerida para `historico_sessao`

- `id`
- `sessao_id`
- `acao`
- `valor_anterior`
- `valor_novo`
- `autor_id`
- `observacao`
- `created_at`

## 13. Regras de integridade

- cada membro pode ter apenas uma confirmacao ativa por sessao;
- a confirmacao do agape pertence a confirmacao da sessao;
- alteracao importante da sessao deve gerar historico;
- cancelamento nao deve apagar sessao, apenas mudar status;
- reabertura deve preservar historico;
- dados do agape devem ser derivados de confirmacoes, nao digitados manualmente em separado;
- presenca efetiva deve ser um fechamento operacional posterior, nao substituto da confirmacao.

## 14. O que deve ser reaproveitado do conceito anterior

Do conceito do projeto anterior, vale preservar:

- fluxo de eventos/sessoes;
- fluxo de confirmacao;
- lembretes e publicacoes;
- ajuda operacional futura;
- guardrails de permissao;
- notificacoes consolidadas por funcao.

O que deve mudar neste ERP:

- remover multi-loja do fluxo principal;
- remover cadastro operacional de lojas dentro do modulo;
- usar contexto institucional nativo do ERP;
- centralizar a sessao como objeto da loja, nao como evento generico aberto.

## 15. Diagnostico do estado atual do projeto

### 15.1 O que ja existe

- existe um modelo minimo de `Sessao` em [src/Models/Sessao.php](/D:/leandro_pessoal/Renascenca/gestor-loja/src/Models/Sessao.php);
- existe um modelo minimo de `Presenca` em [src/Models/Presenca.php](/D:/leandro_pessoal/Renascenca/gestor-loja/src/Models/Presenca.php);
- existe tabela minima de `sessoes` em [database/migrations/004_create_sessoes_table.sql](/D:/leandro_pessoal/Renascenca/gestor-loja/database/migrations/004_create_sessoes_table.sql);
- `SECRETARIO` foi incluido no seed oficial de cargos em [database/migrations/002_seed_cargos.sql](/D:/leandro_pessoal/Renascenca/gestor-loja/database/migrations/002_seed_cargos.sql);
- o mapeamento central de cargos agora reconhece `SECRETARIO` em [src/Models/Cargo.php](/D:/leandro_pessoal/Renascenca/gestor-loja/src/Models/Cargo.php);
- foi criada a fundacao nova de sessoes em [database/migrations/005_central_operacional_sessoes.sql](/D:/leandro_pessoal/Renascenca/gestor-loja/database/migrations/005_central_operacional_sessoes.sql);
- os models base de `Sessao`, `Presenca`, `PresencaSessao` e `PublicacaoSessao` agora refletem esse contrato novo;
- existe menu placeholder da Secretaria no bot em [src/Bot/CommandHandler.php](/D:/leandro_pessoal/Renascenca/gestor-loja/src/Bot/CommandHandler.php);
- o login ja considera `secretario` como papel autorizado no painel em [public/index.php](/D:/leandro_pessoal/Renascenca/gestor-loja/public/index.php).

### 15.2 Gaps importantes encontrados

- ainda nao existem rotas, telas e fluxos completos para operar a nova fundacao;
- a tabela legada `presencas` continua existindo no banco, mas o contrato novo passa a ser `confirmacoes_sessao` + `presencas_sessao`;
- o menu da Secretaria no bot ainda e placeholder e ainda nao consome o novo modelo;
- nao existe ainda fluxo formal de agenda anual, publicacao oficial, resumo da proxima sessao ou consumo por Mestre de Banquetes.

### 15.3 Conclusao tecnica do estado atual

O projeto agora possui uma fundacao tecnica inicial coerente para Secretaria.

Antes de implementar telas e automacoes, ainda e necessario:

- ligar rotas e interfaces ao novo modelo;
- decidir o fluxo operacional exato de fechamento de presenca efetiva;
- implementar agenda oficial, publicacao e saidas por cargo.

## 16. Ordem recomendada de implementacao

### Fase 1 - Fundacao

- adicionar `SECRETARIO` ao catalogo oficial de cargos;
- revisar schema de `sessoes`;
- criar schema correto de confirmacoes e agape;
- alinhar `Presenca` com o schema real;
- definir estados da sessao.

### Fase 2 - Operacao principal do Secretario

- tela de agenda oficial;
- cadastro/edicao/cancelamento/reabertura de sessao;
- publicacao da agenda anual;
- tela de proxima sessao;
- publicacao do resumo da proxima sessao.

### Fase 3 - Consumo por outros cargos

- visao de nominata para Chanceler;
- visao de consolidado de agape para Mestre de Banquetes;
- exportacao/copia de listas;
- historico operacional.

### Fase 4 - Refinos

- lembretes;
- notificacoes internas;
- publicacao complementar em Telegram;
- relatorios e auditoria expandida.

## 17. Recomendacao final

O proximo modulo nao deve ser modelado como "modulo do Secretario" em sentido estreito.

O desenho mais correto e:

- um nucleo de sessao da loja;
- operado principalmente pelo Secretario;
- consumido por Chanceler e Mestre de Banquetes;
- supervisionado pelo Administrador.

Esse enquadramento evita retrabalho e reduz a chance de cada cargo criar seu proprio fluxo paralelo.

## 18. Atualizacao de alinhamento do agape (2026-04-10)

Esta secao consolida o estado atual apos os ajustes de convergencia entre Secretario, Mestre de Banquetes, Chanceler e Tesouraria.

### 18.1 Regras fechadas

- a sessao continua sendo a origem do fluxo operacional;
- o Secretario define se ha agape e qual o modelo financeiro;
- o campo de valor do agape e `valor de referencia` e nao e obrigatorio;
- o Mestre de Banquetes consome os dados da sessao e opera compras/cobrancas no fluxo operacional;
- a Tesouraria so considera reflexo automatico quando o modelo financeiro for `oficial_loja` ou `misto`;
- no modelo `particular`, nao ha reflexo automatico no financeiro oficial da Loja.

### 18.2 Contrato funcional da sessao para agape

- `agape_modalidade`: `nao_havera`, `gratuito`, `pago`
- `agape_modelo_financeiro`: `oficial_loja`, `particular`, `misto`
- `agape_valor`: opcional (valor de referencia)

### 18.3 Implementacao aplicada

- migration de suporte em [database/migrations/029_agape_modelo_financeiro_sessao.sql](/D:/leandro_pessoal/Renascenca/gestor-loja/database/migrations/029_agape_modelo_financeiro_sessao.sql);
- runner da migration em [scripts/run_migration_029.php](/D:/leandro_pessoal/Renascenca/gestor-loja/scripts/run_migration_029.php);
- normalizacao e descricoes no dominio em [src/Models/Sessao.php](/D:/leandro_pessoal/Renascenca/gestor-loja/src/Models/Sessao.php);
- captura e validacao do novo campo na Secretaria em [src/Controllers/SecretariaController.php](/D:/leandro_pessoal/Renascenca/gestor-loja/src/Controllers/SecretariaController.php);
- formulario da Secretaria com `modelo financeiro` e `valor de referencia opcional` em [src/Views/secretaria/index.php](/D:/leandro_pessoal/Renascenca/gestor-loja/src/Views/secretaria/index.php);
- leitura de reflexo oficial na Tesouraria em [src/Controllers/TesourariaSessaoController.php](/D:/leandro_pessoal/Renascenca/gestor-loja/src/Controllers/TesourariaSessaoController.php) e [src/Views/tesouraria_sessao/index.php](/D:/leandro_pessoal/Renascenca/gestor-loja/src/Views/tesouraria_sessao/index.php);
- consumo pelo Mestre de Banquetes em [src/Controllers/MestreBanquetesController.php](/D:/leandro_pessoal/Renascenca/gestor-loja/src/Controllers/MestreBanquetesController.php) e [src/Views/mestre_banquetes/index.php](/D:/leandro_pessoal/Renascenca/gestor-loja/src/Views/mestre_banquetes/index.php);
- visibilidade para Chanceler em [src/Views/chanceler_sessao/index.php](/D:/leandro_pessoal/Renascenca/gestor-loja/src/Views/chanceler_sessao/index.php).
