# ESPECIFICAÇÃO FUNCIONAL - CENTRAL OPERACIONAL DA LOJA

## 1. Objetivo

Este documento define a direcao funcional para o núcleo de sessões e operação da loja dentro do ERP `gestor-loja`.

O objetivo não e criar um módulo isolado "do secretario", mas sim um núcleo único de informações operacionais da loja, no qual:

- o Secretario atua como operador principal da agenda e das sessões;
- o Chanceler consome informações de presença e nominata;
- o Mestre de Banquetes consome informações de confirmação e agape;
- o Administrador atua como suporte, governanca e auditoria;
- o membro interage apenas com os fluxos que lhe dizem respeito.

## 2. Premissas

- O sistema e fechado e atende uma única loja.
- A loja já existe no contexto do ERP e não deve ser cadastrada dentro deste módulo.
- IA fica fora do escopo desta etapa.
- Telegram pode existir como canal complementar, mas não como fonte principal da regra de negócio.
- A sessão da loja deve ser a entidade central deste módulo.

## 3. Problema que o módulo precisa resolver

Hoje existe uma necessidade operacional compartilhada entre cargos:

- o Secretario precisa planejar, publicar e manter a agenda oficial da loja;
- o Secretario precisa publicar o resumo da próxima sessão;
- o Chanceler precisa da nominata e da informação de presença;
- o Mestre de Banquetes precisa saber quantos participarao do agape;
- o Administrador precisa intervir quando houver erro, ajuste ou necessidade de governanca.

Se cada cargo tiver seu proprio fluxo isolado, a informação se fragmenta.

Por isso, a proposta correta e concentrar tudo em um núcleo único:

- sessão planejada;
- publicação oficial;
- confirmação de presença;
- confirmação de agape;
- presença efetiva;
- saidas operacionais por cargo.

## 4. Conceito central

A entidade principal do módulo deve ser a `sessão`.

Tudo o que os cargos precisam passa a orbitar a sessão:

- dados principais da sessão;
- status da sessão;
- publicação da sessão;
- confirmações;
- opcao de agape;
- presença efetiva;
- comunicacoes derivadas;
- auditoria das alteracoes.

## 5. Papeis e responsabilidades

### 5.1 Secretario

Responsabilidades principais:

- criar sessões;
- editar sessões;
- cancelar sessões;
- reabrir sessões canceladas;
- manter agenda anual da loja;
- publicar agenda oficial;
- publicar resumo da próxima sessão;
- acompanhar confirmações;
- exportar ou copiar lista de confirmados;
- registrar ajustes operacionais da sessão.

### 5.2 Chanceler

Responsabilidades ligadas ao consumo de dados da sessão:

- consultar confirmados;
- consultar nominata prevista;
- consultar ou registrar presença efetiva, conforme regra final do projeto;
- usar os dados da sessão para atividades de chancelaria relacionadas ao encontro.

### 5.3 Mestre de Banquetes

Responsabilidades ligadas ao agape:

- consultar quantidade de confirmados para o agape;
- consultar lista de participantes do agape;
- consultar observações operacionais ligadas ao agape;
- obter previsão para organizacao do abastecimento.

### 5.4 Administrador

Responsabilidades de suporte e governanca:

- corrigir dados;
- supervisionar permissões;
- agir como contingencia operacional;
- auditar alteracoes importantes.

### 5.5 Membro

Interacoes permitidas:

- visualizar sessões visíveis;
- confirmar presença;
- cancelar confirmação;
- informar participacao no agape, quando aplicavel;
- consultar sua própria resposta.

## 6. Regra central de negócio

Toda sessão pertence a loja atual do ERP.

Não deve existir selecao manual de loja dentro deste módulo.

Os dados institucionais da loja devem ser herdados do ERP principal, quando precisarem aparecer em publicações, cabecalhos ou documentos.

## 7. Funções obrigatorias do núcleo

### 7.1 Agenda oficial da loja

- cadastrar o planejamento anual;
- listar sessões futuras;
- permitir alteracao pontual de data, hora, tipo ou pauta;
- permitir marcar mudanca relevante;
- permitir publicar a agenda oficial.

### 7.2 Próxima sessão

- destacar a próxima sessão oficial;
- permitir gerar/publicar resumo operacional da próxima sessão;
- permitir atualizar o resumo quando houver alteracao.

### 7.3 Confirmação de presença

- permitir ao membro confirmar presença;
- permitir ao membro cancelar presença;
- garantir unicidade por usuário e sessão;
- permitir ao Secretario consultar os confirmados.

### 7.4 Agape

- permitir registrar se o membro participara do agape;
- permitir consolidar total previsto para o agape;
- permitir ao Mestre de Banquetes consultar lista e quantidade.

### 7.5 Presença efetiva

- permitir registrar presença real no dia da sessão, se este fluxo for adotado;
- produzir nominata dos presentes;
- permitir ao Chanceler consultar ou operar este fechamento.

### 7.6 Alteracoes relevantes

- mudar data/hora;
- mudar pauta;
- cancelar sessão;
- reabrir sessão cancelada;
- registrar autor e data da alteracao;
- opcionalmente disparar comunicacao complementar aos confirmados.

## 8. Estados da sessão

Recomendacao de estados minimos:

- `planejada`
- `publicada`
- `alterada`
- `cancelada`
- `realizada`

Observações:

- `planejada` significa cadastrada internamente, mas ainda não assumida como agenda oficial;
- `publicada` significa valida para consumo institucional;
- `alterada` significa publicada, porem com modificacao relevante posterior;
- `cancelada` significa retirada da agenda ativa;
- `realizada` significa encerrada operacionalmente.

## 9. Informações produzidas por uma sessão

Cada sessão deve concentrar, no mínimo:

- data e hora;
- tipo de sessão;
- grau;
- título ou referencia curta;
- pauta ou resumo;
- local, se necessario;
- status;
- observação administrativa;
- data de publicação;
- autor da criacao;
- autor da ultima alteracao.

## 10. Informações derivadas para outros cargos

### 10.1 Saidas para o Secretario

- agenda anual;
- próxima sessão;
- lista de confirmados;
- resumo da sessão;
- histórico de alteracoes.

### 10.2 Saidas para o Chanceler

- nominata prevista;
- confirmados;
- presença efetiva;
- lista final de presentes, se o fluxo de fechamento for adotado.

### 10.3 Saidas para o Mestre de Banquetes

- total de confirmados no agape;
- lista nominal dos participantes do agape;
- variacoes apos alteracoes de sessão ou de confirmação.

### 10.4 Saidas para o Administrador

- auditoria;
- rastreio de alteracoes;
- ajustes e correcao de dados;
- visao consolidada de governanca.

## 11. Matriz funcional resumida

| Fluxo | Membro | Secretario | Chanceler | Mestre de Banquetes | Administrador |
|---|---|---|---|---|---|
| Visualizar sessões | sim | sim | sim | sim | sim |
| Criar sessão | não | sim | não | não | sim |
| Editar sessão | não | sim | não | não | sim |
| Cancelar/reabrir sessão | não | sim | não | não | sim |
| Publicar agenda oficial | não | sim | não | não | sim |
| Publicar resumo da próxima sessão | não | sim | não | não | sim |
| Confirmar presença | sim | sim | consulta | consulta | sim |
| Informar participacao no agape | sim | sim | consulta | consulta principal | sim |
| Consultar confirmados | própria resposta | sim | sim | parcial | sim |
| Consultar dados do agape | não | sim | não | sim | sim |
| Consultar nominata/presentes | não | sim | sim | não | sim |

## 12. Modelo funcional mínimo de dados

### 12.1 Entidades principais

- `sessões`
- `confirmacoes_sessao`
- `presencas_sessao`
- `publicacoes_sessao`
- `historico_sessao`

### 12.2 Estrutura minima sugerida para `sessões`

- `id`
- `loja_id` ou referencia implicita ao contexto institucional
- `data_hora_inicio`
- `tipo_sessao`
- `grau_sessao`
- `título`
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
- `observação`
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
- `ação`
- `valor_anterior`
- `valor_novo`
- `autor_id`
- `observação`
- `created_at`

## 13. Regras de integridade

- cada membro pode ter apenas uma confirmação ativa por sessão;
- a confirmação do agape pertence a confirmação da sessão;
- alteracao importante da sessão deve gerar histórico;
- cancelamento não deve apagar sessão, apenas mudar status;
- reabertura deve preservar histórico;
- dados do agape devem ser derivados de confirmações, não digitados manualmente em separado;
- presença efetiva deve ser um fechamento operacional posterior, não substituto da confirmação.

## 14. O que deve ser reaproveitado do conceito anterior

Do conceito do projeto anterior, vale preservar:

- fluxo de eventos/sessões;
- fluxo de confirmação;
- lembretes e publicações;
- ajuda operacional futura;
- guardrails de permissão;
- notificacoes consolidadas por função.

O que deve mudar neste ERP:

- remover multi-loja do fluxo principal;
- remover cadastro operacional de lojas dentro do módulo;
- usar contexto institucional nativo do ERP;
- centralizar a sessão como objeto da loja, não como evento generico aberto.

## 15. Diagnostico do estado atual do projeto

### 15.1 O que já existe

- existe um modelo mínimo de `Sessão` em [src/Models/Sessão.php](/D:/leandro_pessoal/Renascenca/gestor-loja/src/Models/Sessão.php);
- existe um modelo mínimo de `Presença` em [src/Models/Presença.php](/D:/leandro_pessoal/Renascenca/gestor-loja/src/Models/Presença.php);
- existe tabela minima de `sessões` em [database/migrations/004_create_sessoes_table.sql](/D:/leandro_pessoal/Renascenca/gestor-loja/database/migrations/004_create_sessoes_table.sql);
- `SECRETARIO` foi incluido no seed oficial de cargos em [database/migrations/002_seed_cargos.sql](/D:/leandro_pessoal/Renascenca/gestor-loja/database/migrations/002_seed_cargos.sql);
- o mapeamento central de cargos agora reconhece `SECRETARIO` em [src/Models/Cargo.php](/D:/leandro_pessoal/Renascenca/gestor-loja/src/Models/Cargo.php);
- foi criada a fundacao nova de sessões em [database/migrations/005_central_operacional_sessoes.sql](/D:/leandro_pessoal/Renascenca/gestor-loja/database/migrations/005_central_operacional_sessoes.sql);
- os models base de `Sessão`, `Presença`, `PresencaSessao` e `PublicacaoSessao` agora refletem esse contrato novo;
- existe menu placeholder da Secretaria no bot em [src/Bot/CommandHandler.php](/D:/leandro_pessoal/Renascenca/gestor-loja/src/Bot/CommandHandler.php);
- o login já considera `secretario` como papel autorizado no painel em [public/index.php](/D:/leandro_pessoal/Renascenca/gestor-loja/public/index.php).

### 15.2 Gaps importantes encontrados

- ainda não existem rotas, telas e fluxos completos para operar a nova fundacao;
- a tabela legada `presenças` continua existindo no banco, mas o contrato novo passa a ser `confirmacoes_sessao` + `presencas_sessao`;
- o menu da Secretaria no bot ainda e placeholder e ainda não consome o novo modelo;
- não existe ainda fluxo formal de agenda anual, publicação oficial, resumo da próxima sessão ou consumo por Mestre de Banquetes.

### 15.3 Conclusão técnica do estado atual

O projeto agora possui uma fundacao técnica inicial coerente para Secretaria.

Antes de implementar telas e automacoes, ainda e necessario:

- ligar rotas e interfaces ao novo modelo;
- decidir o fluxo operacional exato de fechamento de presença efetiva;
- implementar agenda oficial, publicação e saidas por cargo.

## 16. Ordem recomendada de implementação

### Fase 1 - Fundacao

- adicionar `SECRETARIO` ao catalogo oficial de cargos;
- revisar schema de `sessões`;
- criar schema correto de confirmações e agape;
- alinhar `Presença` com o schema real;
- definir estados da sessão.

### Fase 2 - Operação principal do Secretario

- tela de agenda oficial;
- cadastro/edicao/cancelamento/reabertura de sessão;
- publicação da agenda anual;
- tela de próxima sessão;
- publicação do resumo da próxima sessão.

### Fase 3 - Consumo por outros cargos

- visao de nominata para Chanceler;
- visao de consolidado de agape para Mestre de Banquetes;
- exportacao/copia de listas;
- histórico operacional.

### Fase 4 - Refinos

- lembretes;
- notificacoes internas;
- publicação complementar em Telegram;
- relatórios e auditoria expandida.

## 17. Recomendacao final

O próximo módulo não deve ser modelado como "módulo do Secretario" em sentido estreito.

O desenho mais correto e:

- um núcleo de sessão da loja;
- operado principalmente pelo Secretario;
- consumido por Chanceler e Mestre de Banquetes;
- supervisionado pelo Administrador.

Esse enquadramento evita retrabalho e reduz a chance de cada cargo criar seu proprio fluxo paralelo.

## 18. Atualizacao de alinhamento do agape (2026-04-10)

Esta seção consolida o estado atual apos os ajustes de convergencia entre Secretario, Mestre de Banquetes, Chanceler e Tesouraria.

### 18.1 Regras fechadas

- a sessão continua sendo a origem do fluxo operacional;
- o Secretario define se ha agape e qual o modelo financeiro;
- o campo de valor do agape e `valor de referencia` e não e obrigatório;
- o Mestre de Banquetes consome os dados da sessão e opera compras/cobranças no fluxo operacional;
- a Tesouraria so considera reflexo automatico quando o modelo financeiro for `oficial_loja` ou `misto`;
- no modelo `particular`, não ha reflexo automatico no financeiro oficial da Loja.

### 18.2 Contrato funcional da sessão para agape

- `agape_modalidade`: `nao_havera`, `gratuito`, `pago`
- `agape_modelo_financeiro`: `oficial_loja`, `particular`, `misto`
- `agape_valor`: opcional (valor de referencia)

### 18.3 Implementação aplicada

- migration de suporte em [database/migrations/029_agape_modelo_financeiro_sessao.sql](/D:/leandro_pessoal/Renascenca/gestor-loja/database/migrations/029_agape_modelo_financeiro_sessao.sql);
- runner da migration em [scripts/run_migration_029.php](/D:/leandro_pessoal/Renascenca/gestor-loja/scripts/run_migration_029.php);
- normalizacao e descricoes no dominio em [src/Models/Sessão.php](/D:/leandro_pessoal/Renascenca/gestor-loja/src/Models/Sessão.php);
- captura e validação do novo campo na Secretaria em [src/Controllers/SecretariaController.php](/D:/leandro_pessoal/Renascenca/gestor-loja/src/Controllers/SecretariaController.php);
- formulario da Secretaria com `modelo financeiro` e `valor de referencia opcional` em [src/Views/secretaria/index.php](/D:/leandro_pessoal/Renascenca/gestor-loja/src/Views/secretaria/index.php);
- leitura de reflexo oficial na Tesouraria em [src/Controllers/TesourariaSessaoController.php](/D:/leandro_pessoal/Renascenca/gestor-loja/src/Controllers/TesourariaSessaoController.php) e [src/Views/tesouraria_sessao/index.php](/D:/leandro_pessoal/Renascenca/gestor-loja/src/Views/tesouraria_sessao/index.php);
- consumo pelo Mestre de Banquetes em [src/Controllers/MestreBanquetesController.php](/D:/leandro_pessoal/Renascenca/gestor-loja/src/Controllers/MestreBanquetesController.php) e [src/Views/mestre_banquetes/index.php](/D:/leandro_pessoal/Renascenca/gestor-loja/src/Views/mestre_banquetes/index.php);
- visibilidade para Chanceler em [src/Views/chanceler_sessao/index.php](/D:/leandro_pessoal/Renascenca/gestor-loja/src/Views/chanceler_sessao/index.php).
