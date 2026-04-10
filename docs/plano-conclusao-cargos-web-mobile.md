# PLANO DE CONCLUSAO DOS CARGOS - WEB E MOBILE

## 1. Objetivo

Este plano organiza a conclusao funcional de todos os cargos do ERP `gestor-loja`, garantindo:

- cobertura completa das telas e funcoes no ambiente web;
- espelhamento funcional no mobile/miniapp;
- consistencia de permissao por cargo;
- reaproveitamento do mesmo dominio de negocio entre web e mobile;
- evolucao por fases, sem retrabalho.

O principio central deste plano e:

- o web sera a superficie operacional completa;
- o mobile sera a superficie espelhada, com foco em consulta rapida, confirmacao, aprovacao e operacao enxuta;
- as regras de negocio devem ficar no backend/modelos/servicos, nunca duplicadas em views.

## 2. Estado atual resumido

### 2.1 Cargos com implementacao mais avancada no web

- Secretaria
- Chanceler de Sessao
- Hospitaleiro
- Veneravel
- Tesouraria
- Biblioteca

### 2.2 Cargos com implementacao parcial no web

- Primeiro Vigilante
- Segundo Vigilante
- Mestre de Banquetes
- Orador
- Mestre de Harmonia
- Tesouraria de Sessoes

### 2.3 Cobertura mobile atual

Hoje o mobile/miniapp esta concentrado em:

- aprendizado do Aprendiz;
- companheirismo;
- tesouraria pessoal;
- utilitarios e consultas auxiliares.

Ainda nao existe espelhamento por cargo para:

- Secretaria;
- Chancelaria operacional de sessao;
- Mestre de Banquetes;
- Hospitaleiro;
- Orador;
- Veneravel;
- Mestre de Harmonia;
- painel administrativo.

## 3. Meta de espelhamento

Cada cargo deve ter:

- painel web completo;
- versao mobile com as mesmas funcoes essenciais;
- mesmas permissoes e mesmos estados;
- mesmos dados exibidos com adaptacao de interface, nao com perda de regra.

### 3.1 Regra de paridade

Para considerar um cargo concluido:

1. o fluxo principal deve funcionar no web;
2. as acoes essenciais do cargo devem existir no mobile;
3. as regras de permissao devem ser iguais;
4. o cargo deve aparecer no dashboard web e no launcher mobile;
5. o fluxo deve possuir estados, mensagens e historico coerentes.

## 4. Matriz alvo por cargo

### 4.1 Secretaria

Web obrigatorio:

- agenda anual;
- criar, editar, cancelar, reabrir e publicar sessao;
- resumo da proxima sessao;
- publicacoes oficiais;
- trabalhos da sessao;
- balaustre;
- relatorio anual;
- acompanhamento de confirmacoes e agape;
- historico da sessao.

Mobile obrigatorio:

- ver agenda e proxima sessao;
- criar/editar/publicar sessao em fluxo simplificado;
- publicar resumo;
- acompanhar confirmados;
- acompanhar agape;
- operar balaustre em formato responsivo;
- abrir rapidamente a votacao e acompanhar status.

### 4.2 Chanceler

Web obrigatorio:

- ver proxima sessao;
- ver nominata prevista;
- ver confirmados;
- registrar presenca efetiva;
- visualizar visitantes e dados auxiliares;
- emitir certificado quando aplicavel.

Mobile obrigatorio:

- check-in de presenca;
- lista de confirmados;
- lista de visitantes;
- consulta rapida da nominata;
- emissao simplificada de certificado.

### 4.3 Mestre de Banquetes

Web obrigatorio:

- total de confirmados;
- total de participantes do agape;
- lista nominal do agape;
- observacoes operacionais;
- sinalizacao do modelo financeiro;
- fechamento de previsao do banquete.

Mobile obrigatorio:

- resumo da sessao;
- total do agape;
- lista do agape;
- observacoes logisticas;
- marcacao de preparacao/abastecimento/fechamento operacional.

### 4.4 Hospitaleiro

Web obrigatorio:

- registrar ocorrencias assistenciais;
- atualizar status;
- encaminhar para Tesouraria ou Veneravel;
- acompanhar prioridade, visita e apoio financeiro.

Mobile obrigatorio:

- abrir ocorrencia;
- atualizar status em campo;
- registrar visita;
- registrar necessidade de apoio;
- consultar pendencias da assistencia.

### 4.5 Primeiro Vigilante

Web obrigatorio:

- painel de Aprendizes;
- detalhe individual;
- trilha completa;
- passar etapa;
- registrar entrega;
- revisar trabalho;
- registrar devolutiva;
- sugerir leitura;
- solicitar certificado.

Mobile obrigatorio:

- acompanhar propria trilha do Aprendiz;
- acompanhar painel do vigilante;
- atualizar etapa;
- registrar observacao;
- sugerir leitura;
- solicitar certificado;
- consultar historico formativo.

### 4.6 Segundo Vigilante

Web obrigatorio:

- painel de Companheiros;
- trilha completa;
- atualizar etapas;
- recomendar docencia;
- recomendar exaltacao;
- registrar orientacoes e devolutivas.

Mobile obrigatorio:

- acompanhar propria trilha;
- acompanhar painel do vigilante;
- atualizar etapa;
- recomendar docencia e exaltacao;
- consultar historico.

### 4.7 Orador

Web obrigatorio:

- resumo da proxima sessao;
- visitantes para palavra a bem;
- pauta resumida;
- pontos rituais e lembretes;
- historico breve para suporte em Loja.

Mobile obrigatorio:

- tela de leitura rapida da sessao;
- lista de visitantes;
- pauta resumida;
- lembretes operacionais.

### 4.8 Veneravel

Web obrigatorio:

- supervisao das sessoes;
- publicar, cancelar, reabrir e marcar realizada;
- acompanhar balaustres aptos e em votacao;
- validar nominata principal;
- acompanhar cargos criticos e pendencias.

Mobile obrigatorio:

- aprovacao rapida;
- abrir e encerrar votacoes;
- decidir sobre sessao;
- acompanhar pendencias criticas;
- receber alertas operacionais.

### 4.9 Mestre de Harmonia

Web obrigatorio:

- painel do operador;
- scan da base musical;
- player ritual;
- troca de operador;
- execucao por etapa.

Mobile obrigatorio:

- controle remoto do player;
- selecao de faixa por etapa;
- play/pause/avanco;
- troca rapida de operador.

### 4.10 Tesouraria

Web obrigatorio:

- caixa;
- comprovantes;
- obrigacoes;
- regularidade;
- fechamento;
- relatorio;
- reflexos financeiros do agape.

Mobile obrigatorio:

- aprovacao de comprovante;
- consulta de caixa resumido;
- regularidade;
- obrigacoes abertas;
- acao rapida de quitacao e validacao;
- consulta de sessao com reflexo financeiro.

### 4.11 Biblioteca

Web obrigatorio:

- acervo;
- detalhes;
- comentarios;
- reacoes;
- solicitacao;
- emprestimos;
- devolucao;
- cadastro e edicao.

Mobile obrigatorio:

- buscar acervo;
- ver detalhes;
- solicitar emprestimo;
- acompanhar emprestimos;
- devolver;
- comentar e reagir.

### 4.12 Administracao

Web obrigatorio:

- cargos;
- atribuicoes;
- gestoes;
- parametros da loja;
- auditoria de alteracoes.

Mobile obrigatorio:

- consulta de gestao e cargos;
- aprovacoes simples;
- visualizacao de auditoria critica;
- ajustes administrativos enxutos.

## 5. Principios tecnicos

Para evitar retrabalho, a conclusao deve seguir estes principios:

- toda regra fica em model/service/controller, nunca embutida so na view;
- web e mobile devem consumir os mesmos endpoints ou o mesmo dominio;
- cada cargo deve ter um contrato funcional unico;
- as telas mobile devem nascer de endpoints dedicados JSON quando houver interacao intensa;
- qualquer fluxo novo deve ser entregue com permissao, feedback ao usuario e historico.

## 6. Fases do plano

## 6.1 Fase 1 - Fundacao de paridade

Objetivo:

- preparar a base para o espelhamento web/mobile.

Entregas:

- inventario final de cargos e funcoes;
- matriz oficial de permissao por cargo;
- definicao do launcher mobile por cargo;
- padronizacao de rotas web e APIs mobile;
- padronizacao de mensagens de sucesso/erro;
- checklist unico de conclusao por cargo.

Resultado esperado:

- nenhum cargo evolui de forma isolada;
- toda nova entrega ja nasce preparada para web e mobile.

## 6.2 Fase 2 - Fechar cargos operacionais centrais

Prioridade:

- Secretaria;
- Chanceler;
- Mestre de Banquetes;
- Veneravel.

Entregas:

- concluir historico de sessao;
- concluir agenda oficial;
- concluir resumo da proxima sessao;
- concluir consolidado de agape;
- concluir presenca efetiva;
- concluir aprovacao e decisao do Veneravel;
- criar versoes mobile dos fluxos principais.

Resultado esperado:

- nucleo de sessao da loja completamente operacional nos dois canais.

## 6.3 Fase 3 - Fechar cargos formativos

Prioridade:

- Primeiro Vigilante;
- Segundo Vigilante.

Entregas:

- concluir trilhas;
- registrar recebimento e revisao corretamente;
- integrar sugestoes de leitura com biblioteca;
- registrar historico formativo;
- concluir solicitacao de certificado e recomendacoes;
- expor miniapps completos de acompanhamento e operacao.

Resultado esperado:

- formacao de Aprendizes e Companheiros passa a ser de ponta a ponta.

## 6.4 Fase 4 - Fechar cargos de apoio ritual e assistencial

Prioridade:

- Hospitaleiro;
- Orador;
- Mestre de Harmonia.

Entregas:

- consolidar ocorrencias e visitas no Hospitaleiro;
- concluir painel ritual do Orador;
- criar controle mobile do Mestre de Harmonia;
- concluir a espelhagem mobile desses cargos.

Resultado esperado:

- os cargos de apoio deixam de ser somente consulta parcial e passam a operar em campo.

## 6.5 Fase 5 - Fechar cargos administrativos e consolidacao final

Prioridade:

- Tesouraria;
- Biblioteca;
- Administracao.

Entregas:

- finalizar tudo que ainda depender de mobile;
- concluir auditoria critica;
- criar indicadores de cobertura por cargo;
- validar consistencia entre dashboard web e launcher mobile.

Resultado esperado:

- todos os cargos ativos da loja com cobertura completa.

## 7. Backlog objetivo por frente

### 7.1 Backend e contratos

- criar ou consolidar endpoints JSON por cargo;
- separar melhor leitura e escrita;
- padronizar payloads de sessao, trilha, presenca, ocorrencia e votacao;
- garantir historico e autoria em todas as alteracoes.

### 7.2 Web

- revisar todas as views que hoje sao somente leitura;
- incluir estados vazios, loading, erro e confirmacao;
- concluir telas de detalhe onde so existe painel resumido;
- unificar navegacao por cargo.

### 7.3 Mobile

- criar launcher por cargo;
- criar telas por cargo espelhadas do web;
- privilegiar listas curtas, cards, filtros simples e acoes rapidas;
- expor apenas o essencial do fluxo sem perder regra;
- padronizar autenticacao via `init_data`.

### 7.4 Permissao e seguranca

- revisar todas as rotas web;
- revisar todos os endpoints API;
- alinhar permissao do dashboard, web e miniapp;
- impedir visibilidade indevida entre cargos.

### 7.5 Qualidade

- testes manuais por cargo;
- roteiro de homologacao web;
- roteiro de homologacao mobile;
- checklist de regressao por sessao, trilha, assistencia e financeiro.

## 8. Ordem recomendada de execucao

1. Fechar matriz de cargos e permissoes.
2. Concluir nucleo de sessao no web.
3. Espelhar o nucleo de sessao no mobile.
4. Concluir trilhas de formacao no web.
5. Espelhar trilhas no mobile.
6. Concluir cargos de apoio no web.
7. Espelhar cargos de apoio no mobile.
8. Concluir auditoria, admin e acabamento final.

## 9. Definicao de pronto por cargo

Um cargo so sera marcado como concluido quando atender todos os itens abaixo:

- painel web entregue;
- funcoes principais operacionais;
- painel mobile entregue;
- funcoes essenciais mobile operacionais;
- permissao consistente;
- historico/autoria quando aplicavel;
- validacao manual concluida;
- documentacao atualizada.

## 10. Recomendacao pratica imediata

A melhor sequencia para as proximas sprints e:

- Sprint 1: Secretaria + Chanceler + Mestre de Banquetes + Veneravel no mobile;
- Sprint 2: Primeiro Vigilante + Segundo Vigilante completos no web e mobile;
- Sprint 3: Hospitaleiro + Orador + Mestre de Harmonia no web e mobile;
- Sprint 4: Tesouraria + Biblioteca + Admin + consolidacao final.

## 11. Resultado esperado ao final

Ao concluir este plano, o sistema tera:

- todos os cargos principais da loja com operacao completa;
- correspondencia real entre web e mobile;
- menos dependencia de telas exclusivas de escritorio;
- melhor operacao durante sessao, em campo e no Telegram;
- uma arquitetura mais sustentavel para novas funcoes futuras.

## 12. Tabela de acompanhamento vivo

Esta secao passa a ser a referencia oficial de andamento.

Sempre que uma entrega for concluida, esta tabela deve ser atualizada antes de encerrar a tarefa.

Legenda de status:

- `nao_iniciado`
- `em_andamento`
- `parcial`
- `concluido`
- `bloqueado`

### 12.1 Visao por cargo

| Cargo | Web | Mobile | Regra/Backend | Permissoes | Status geral | O que falta principal |
|---|---|---|---|---|---|---|
| Secretaria | parcial | nao_iniciado | parcial | parcial | parcial | agenda oficial completa, historico consolidado e espelhamento mobile |
| Chanceler | parcial | nao_iniciado | parcial | parcial | parcial | presenca efetiva mobile, nominata mobile e fechamento operacional completo |
| Mestre de Banquetes | parcial | nao_iniciado | parcial | parcial | parcial | operacao do agape alem de consulta e versao mobile |
| Hospitaleiro | parcial | nao_iniciado | parcial | parcial | parcial | fluxo mobile de ocorrencias, visitas e encaminhamentos |
| Primeiro Vigilante | parcial | parcial | parcial | parcial | parcial | leituras, certificado, historico formativo e conclusao do fluxo |
| Segundo Vigilante | parcial | parcial | parcial | parcial | parcial | docencia, exaltacao, historico e conclusao do fluxo |
| Orador | parcial | nao_iniciado | parcial | parcial | parcial | painel ritual mais completo e versao mobile |
| Veneravel | parcial | nao_iniciado | parcial | parcial | parcial | aprovacoes e decisoes rapidas no mobile, consolidacao de governanca |
| Mestre de Harmonia | parcial | nao_iniciado | parcial | parcial | parcial | controle mobile/remoto e fechamento do fluxo do operador |
| Tesouraria | parcial | parcial | parcial | parcial | parcial | concluir espelhamento mobile operacional e integrações finais |
| Biblioteca | parcial | nao_iniciado | parcial | parcial | parcial | espelhamento mobile e refinamento de operacao por cargo |
| Administracao | parcial | nao_iniciado | parcial | parcial | parcial | auditoria critica, ajustes mobile e consolidacao de gestoes |

### 12.2 Visao por fase

| Fase | Nome | Status | Observacao |
|---|---|---|---|
| 1 | Fundacao de paridade | em_andamento | plano criado, falta matriz detalhada de permissao e checklist final por cargo |
| 2 | Cargos operacionais centrais | nao_iniciado | iniciar por Secretaria, Chanceler, Mestre de Banquetes e Veneravel |
| 3 | Cargos formativos | nao_iniciado | concluir 1o e 2o Vigilantes no web e mobile |
| 4 | Apoio ritual e assistencial | nao_iniciado | concluir Hospitaleiro, Orador e Mestre de Harmonia |
| 5 | Consolidacao administrativa | nao_iniciado | fechar Tesouraria, Biblioteca, Admin e auditoria final |

### 12.3 Proxima ordem recomendada

| Prioridade | Entrega | Status |
|---|---|---|
| 1 | Matriz detalhada cargo x tela x funcao x canal | pendente |
| 2 | Secretaria mobile | pendente |
| 3 | Chanceler mobile | pendente |
| 4 | Mestre de Banquetes mobile | pendente |
| 5 | Veneravel mobile | pendente |
| 6 | Fechamento funcional do 1o Vigilante | pendente |
| 7 | Fechamento funcional do 2o Vigilante | pendente |

### 12.4 Regra de manutencao

Quando eu for perguntado sobre andamento do projeto, esta tabela deve ser usada como fonte principal.

Se uma entrega evoluir, atualizar:

- status do cargo;
- status da fase;
- coluna `o que falta principal`;
- ordem recomendada, se a prioridade mudar.
