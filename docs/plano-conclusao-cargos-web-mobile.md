# PLANO DE CONCLUSÃO DOS CARGOS - WEB E MOBILE

## 1. Objetivo

Este plano organiza a conclusão funcional de todos os cargos do ERP `gestor-loja`, garantindo:

- cobertura completa das telas e funções no ambiente web;
- espelhamento funcional no mobile/miniapp;
- consistencia de permissão por cargo;
- reaproveitamento do mesmo dominio de negócio entre web e mobile;
- evolucao por fases, sem retrabalho.

O principio central deste plano e:

- o web sera a superficie operacional completa;
- o mobile sera a superficie espelhada, com foco em consulta rapida, confirmação, aprovacao e operação enxuta;
- as regras de negócio devem ficar no backend/modelos/servicos, nunca duplicadas em views.

## 2. Estado atual resumido

### 2.1 Cargos com implementação mais avancada no web

- Secretaria
- Chanceler de Sessão
- Hospitaleiro
- Veneravel
- Tesouraria
- Biblioteca

### 2.2 Cargos com implementação parcial no web

- Primeiro Vigilante
- Segundo Vigilante
- Mestre de Banquetes
- Orador
- Mestre de Harmonia
- Tesouraria de Sessões

### 2.3 Cobertura mobile atual

Hoje o mobile/miniapp esta concentrado em:

- aprendizado do Aprendiz;
- companheirismo;
- tesouraria pessoal;
- utilitarios e consultas auxiliares.

Ainda não existe espelhamento por cargo para:

- Secretaria;
- Chancelaria operacional de sessão;
- Mestre de Banquetes;
- Hospitaleiro;
- Orador;
- Veneravel;
- Mestre de Harmonia;
- painel administrativo.

## 3. Meta de espelhamento

Cada cargo deve ter:

- painel web completo;
- versao mobile com as mesmas funções essenciais;
- mesmas permissões e mesmos estados;
- mesmos dados exibidos com adaptacao de interface, não com perda de regra.

### 3.1 Regra de paridade

Para considerar um cargo concluído:

1. o fluxo principal deve funcionar no web;
2. as ações essenciais do cargo devem existir no mobile;
3. as regras de permissão devem ser iguais;
4. o cargo deve aparecer no dashboard web e no launcher mobile;
5. o fluxo deve possuir estados, mensagens e histórico coerentes.

## 4. Matriz alvo por cargo

### 4.1 Secretaria

Web obrigatório:

- agenda anual;
- criar, editar, cancelar, reabrir e publicar sessão;
- resumo da próxima sessão;
- publicações oficiais;
- trabalhos da sessão;
- balaústre;
- relatório anual;
- acompanhamento de confirmações e agape;
- histórico da sessão.

Mobile obrigatório:

- ver agenda e próxima sessão;
- criar/editar/publicar sessão em fluxo simplificado;
- publicar resumo;
- acompanhar confirmados;
- acompanhar agape;
- operar balaústre em formato responsivo;
- abrir rapidamente a votação e acompanhar status.

### 4.2 Chanceler

Web obrigatório:

- ver próxima sessão;
- ver nominata prevista;
- ver confirmados;
- registrar presença efetiva;
- visualizar visitantes e dados auxiliares;
- emitir certificado quando aplicavel.

Mobile obrigatório:

- check-in de presença;
- lista de confirmados;
- lista de visitantes;
- consulta rapida da nominata;
- emissao simplificada de certificado.

### 4.3 Mestre de Banquetes

Web obrigatório:

- total de confirmados;
- total de participantes do agape;
- lista nominal do agape;
- observações operacionais;
- sinalizacao do modelo financeiro;
- fechamento de previsão do banquete.

Mobile obrigatório:

- resumo da sessão;
- total do agape;
- lista do agape;
- observações logisticas;
- marcacao de preparacao/abastecimento/fechamento operacional.

### 4.4 Hospitaleiro

Web obrigatório:

- registrar ocorrencias assistenciais;
- atualizar status;
- encaminhar para Tesouraria ou Veneravel;
- acompanhar prioridade, visita e apoio financeiro.

Mobile obrigatório:

- abrir ocorrencia;
- atualizar status em campo;
- registrar visita;
- registrar necessidade de apoio;
- consultar pendencias da assistencia.

### 4.5 Primeiro Vigilante

Web obrigatório:

- painel de Aprendizes;
- detalhe individual;
- trilha completa;
- passar etapa;
- registrar entrega;
- revisar trabalho;
- registrar devolutiva;
- sugerir leitura;
- solicitar certificado.

Mobile obrigatório:

- acompanhar própria trilha do Aprendiz;
- acompanhar painel do vigilante;
- atualizar etapa;
- registrar observação;
- sugerir leitura;
- solicitar certificado;
- consultar histórico formativo.

### 4.6 Segundo Vigilante

Web obrigatório:

- painel de Companheiros;
- trilha completa;
- atualizar etapas;
- recomendar docência;
- recomendar exaltacao;
- registrar orientações e devolutivas.

Mobile obrigatório:

- acompanhar própria trilha;
- acompanhar painel do vigilante;
- atualizar etapa;
- recomendar docência e exaltacao;
- consultar histórico.

### 4.7 Orador

Web obrigatório:

- resumo da próxima sessão;
- visitantes para palavra a bem;
- pauta resumida;
- pontos rituais e lembretes;
- histórico breve para suporte em Loja.

Mobile obrigatório:

- tela de leitura rapida da sessão;
- lista de visitantes;
- pauta resumida;
- lembretes operacionais.

### 4.8 Veneravel

Web obrigatório:

- supervisao das sessões;
- publicar, cancelar, reabrir e marcar realizada;
- acompanhar balaústres aptos e em votação;
- validar nominata principal;
- acompanhar cargos criticos e pendencias.

Mobile obrigatório:

- aprovacao rapida;
- abrir e encerrar votações;
- decidir sobre sessão;
- acompanhar pendencias criticas;
- receber alertas operacionais.

### 4.9 Mestre de Harmonia

Web obrigatório:

- painel do operador;
- scan da base musical;
- player ritual;
- troca de operador;
- execucao por etapa.

Mobile obrigatório:

- controle remoto do player;
- selecao de faixa por etapa;
- play/pause/avanco;
- troca rapida de operador.

### 4.10 Tesouraria

Web obrigatório:

- caixa;
- comprovantes;
- obrigacoes;
- regularidade;
- fechamento;
- relatório;
- reflexos financeiros do agape.

Mobile obrigatório:

- aprovacao de comprovante;
- consulta de caixa resumido;
- regularidade;
- obrigacoes abertas;
- ação rapida de quitacao e validação;
- consulta de sessão com reflexo financeiro.

### 4.11 Biblioteca

Web obrigatório:

- acervo;
- detalhes;
- comentarios;
- reacoes;
- solicitação;
- emprestimos;
- devolucao;
- cadastro e edicao.

Mobile obrigatório:

- buscar acervo;
- ver detalhes;
- solicitar emprestimo;
- acompanhar emprestimos;
- devolver;
- comentar e reagir.

### 4.12 Administracao

Web obrigatório:

- cargos;
- atribuicoes;
- gestões;
- parametros da loja;
- auditoria de alteracoes.

Mobile obrigatório:

- consulta de gestão e cargos;
- aprovacoes simples;
- visualização de auditoria critica;
- ajustes administrativos enxutos.

## 5. Principios técnicos

Para evitar retrabalho, a conclusão deve seguir estes principios:

- toda regra fica em model/service/controller, nunca embutida so na view;
- web e mobile devem consumir os mesmos endpoints ou o mesmo dominio;
- cada cargo deve ter um contrato funcional único;
- as telas mobile devem nascer de endpoints dedicados JSON quando houver interacao intensa;
- qualquer fluxo novo deve ser entregue com permissão, feedback ao usuário e histórico.

## 6. Fases do plano

## 6.1 Fase 1 - Fundacao de paridade

Objetivo:

- preparar a base para o espelhamento web/mobile.

Entregas:

- inventario final de cargos e funções;
- matriz oficial de permissão por cargo;
- definicao do launcher mobile por cargo;
- padronizacao de rotas web e APIs mobile;
- padronizacao de mensagens de sucesso/erro;
- checklist único de conclusão por cargo.

Resultado esperado:

- nenhum cargo evolui de forma isolada;
- toda nova entrega já nasce preparada para web e mobile.

## 6.2 Fase 2 - Fechar cargos operacionais centrais

Prioridade:

- Secretaria;
- Chanceler;
- Mestre de Banquetes;
- Veneravel.

Entregas:

- concluir histórico de sessão;
- concluir agenda oficial;
- concluir resumo da próxima sessão;
- concluir consolidado de agape;
- concluir presença efetiva;
- concluir aprovacao e decisao do Veneravel;
- criar versoes mobile dos fluxos principais.

Resultado esperado:

- núcleo de sessão da loja completamente operacional nos dois canais.

## 6.3 Fase 3 - Fechar cargos formativos

Prioridade:

- Primeiro Vigilante;
- Segundo Vigilante.

Entregas:

- concluir trilhas;
- registrar recebimento e revisão corretamente;
- integrar sugestoes de leitura com biblioteca;
- registrar histórico formativo;
- concluir solicitação de certificado e recomendacoes;
- expor miniapps completos de acompanhamento e operação.

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
- padronizar payloads de sessão, trilha, presença, ocorrencia e votação;
- garantir histórico e autoria em todas as alteracoes.

### 7.2 Web

- revisar todas as views que hoje sao somente leitura;
- incluir estados vazios, loading, erro e confirmação;
- concluir telas de detalhe onde so existe painel resumido;
- unificar navegacao por cargo.

### 7.3 Mobile

- criar launcher por cargo;
- criar telas por cargo espelhadas do web;
- privilegiar listas curtas, cards, filtros simples e ações rapidas;
- expor apenas o essencial do fluxo sem perder regra;
- padronizar autenticacao via `init_data`.

### 7.4 Permissão e seguranca

- revisar todas as rotas web;
- revisar todos os endpoints API;
- alinhar permissão do dashboard, web e miniapp;
- impedir visibilidade indevida entre cargos.

### 7.5 Qualidade

- testes manuais por cargo;
- roteiro de homologacao web;
- roteiro de homologacao mobile;
- checklist de regressao por sessão, trilha, assistencia e financeiro.

## 8. Ordem recomendada de execucao

1. Fechar matriz de cargos e permissões.
2. Concluir núcleo de sessão no web.
3. Espelhar o núcleo de sessão no mobile.
4. Concluir trilhas de formacao no web.
5. Espelhar trilhas no mobile.
6. Concluir cargos de apoio no web.
7. Espelhar cargos de apoio no mobile.
8. Concluir auditoria, admin e acabamento final.

## 9. Definicao de pronto por cargo

Um cargo so sera marcado como concluído quando atender todos os itens abaixo:

- painel web entregue;
- funções principais operacionais;
- painel mobile entregue;
- funções essenciais mobile operacionais;
- permissão consistente;
- histórico/autoria quando aplicavel;
- validação manual concluída;
- documentacao atualizada.

## 10. Recomendacao pratica imediata

A melhor sequencia para as proximas sprints e:

- Sprint 1: Secretaria + Chanceler + Mestre de Banquetes + Veneravel no mobile;
- Sprint 2: Primeiro Vigilante + Segundo Vigilante completos no web e mobile;
- Sprint 3: Hospitaleiro + Orador + Mestre de Harmonia no web e mobile;
- Sprint 4: Tesouraria + Biblioteca + Admin + consolidacao final.

## 11. Resultado esperado ao final

Ao concluir este plano, o sistema tera:

- todos os cargos principais da loja com operação completa;
- correspondencia real entre web e mobile;
- menos dependencia de telas exclusivas de escritorio;
- melhor operação durante sessão, em campo e no Telegram;
- uma arquitetura mais sustentavel para novas funções futuras.

## 12. Tabela de acompanhamento vivo

Esta seção passa a ser a referencia oficial de andamento.

Sempre que uma entrega for concluída, esta tabela deve ser atualizada antes de encerrar a tarefa.

Legenda de status:

- `nao_iniciado`
- `em_andamento`
- `parcial`
- `concluído`
- `bloqueado`

### 12.1 Visao por cargo

| Cargo | Web | Mobile | Regra/Backend | Permissões | Status geral | O que falta principal |
|---|---|---|---|---|---|---|
| Secretaria | concluído | concluído | concluído | parcial | concluído | web e mobile operacionais, com refinamento documental posterior |
| Chanceler | concluído | concluído | parcial | parcial | concluído | refinamento final de permissão/documentacao |
| Mestre de Banquetes | concluído | concluído | parcial | parcial | concluído | refinamento final de permissão/documentacao |
| Hospitaleiro | concluído | concluído | parcial | parcial | concluído | refinamento final de permissão/documentacao |
| Primeiro Vigilante | concluído | concluído | parcial | parcial | concluído | refinamento final de permissão/documentacao |
| Segundo Vigilante | concluído | concluído | parcial | parcial | concluído | refinamento final de permissão/documentacao |
| Orador | concluído | concluído | parcial | parcial | concluído | refinamento final de permissão/documentacao |
| Veneravel | concluído | concluído | parcial | parcial | concluído | refinamento final de permissão/documentacao |
| Mestre de Harmonia | concluído | concluído | parcial | parcial | concluído | refinamento final de permissão/documentacao |
| Tesouraria | concluído | concluído | parcial | parcial | concluído | refinamento final de permissão/documentacao |
| Biblioteca | concluído | concluído | parcial | parcial | concluído | refinamento final de permissão/documentacao |
| Administracao | concluído | concluído | parcial | parcial | concluído | refinamento final de permissão/documentacao |

### 12.2 Visao por fase

| Fase | Nome | Status | Observação |
|---|---|---|---|
| 1 | Fundacao de paridade | concluído | plano, matriz detalhada e checklist base consolidados |
| 2 | Cargos operacionais centrais | concluído | Secretaria, Chanceler, Mestre de Banquetes e Veneravel concluídos |
| 3 | Cargos formativos | concluído | 1o e 2o Vigilantes concluídos |
| 4 | Apoio ritual e assistencial | concluído | Hospitaleiro, Orador e Mestre de Harmonia concluídos |
| 5 | Consolidacao administrativa | concluído | Tesouraria, Biblioteca, Administracao e auditoria final consolidadas |

### 12.3 Próxima ordem recomendada

| Prioridade | Entrega | Status |
|---|---|---|
| 1 | Matriz detalhada cargo x tela x função x canal | concluído |
| 2 | Secretaria mobile | concluído |
| 3 | Mestre de Banquetes mobile | concluído |
| 4 | Fechamento funcional do Orador | concluído |
| 5 | Refinamento de permissão/documentacao dos cargos concluídos | em_andamento |

### 12.4 Regra de manutenção

Quando eu for perguntado sobre andamento do projeto, esta tabela deve ser usada como fonte principal.

Se uma entrega evoluir, atualizar:

- status do cargo;
- status da fase;
- coluna `o que falta principal`;
- ordem recomendada, se a prioridade mudar.

## 13. Matriz detalhada por cargo

Esta matriz existe para executarmos um cargo por vez.

Legenda curta:

- `W`: web
- `M`: mobile
- `B`: backend/regra
- `P`: permissão

Status por item:

- `nao_iniciado`
- `parcial`
- `concluído`
- `bloqueado`

## 13.1 Secretaria

| Item | W | M | B | P | Status | Observação |
|---|---|---|---|---|---|---|
| Painel principal da Secretaria | concluído | concluído | parcial | parcial | concluído | web e miniapp entregues |
| Criar sessão | concluído | concluído | parcial | parcial | concluído | web e miniapp entregues |
| Editar sessão | concluído | concluído | parcial | parcial | concluído | web e mobile permitem criacao e edicao no mesmo fluxo |
| Cancelar sessão | concluído | concluído | parcial | parcial | concluído | web e miniapp entregues |
| Reabrir sessão | concluído | concluído | parcial | parcial | concluído | web e miniapp entregues |
| Publicar sessão | concluído | concluído | parcial | parcial | concluído | web e miniapp entregues |
| Agenda oficial | concluído | concluído | parcial | parcial | concluído | agenda operacional entregue em ambos |
| Resumo da próxima sessão | concluído | concluído | parcial | parcial | concluído | entregue em ambos |
| Confirmados da sessão | concluído | concluído | parcial | parcial | concluído | entregue em ambos |
| Controle de agape | concluído | concluído | parcial | parcial | concluído | entregue em ambos |
| Trabalhos da sessão | concluído | concluído | parcial | parcial | concluído | miniapp registra trabalho e mostra histórico recente |
| Balaústre | concluído | concluído | parcial | parcial | concluído | miniapp salva balaústre por sessão e mostra situação atual |
| Votação de balaústre | concluído | concluído | parcial | parcial | concluído | miniapp marca apto, abre e encerra votação |
| Relatório anual | concluído | concluído | parcial | parcial | concluído | resumo anual operacional consolidado no mobile |
| Histórico operacional da sessão | concluído | concluído | parcial | parcial | concluído | entregue em ambos |

## 13.2 Chanceler

| Item | W | M | B | P | Status | Observação |
|---|---|---|---|---|---|---|
| Painel de sessão do Chanceler | concluído | concluído | parcial | parcial | concluído | web e miniapp entregues |
| Consulta de confirmados | concluído | concluído | parcial | parcial | concluído | entregue em ambos |
| Consulta de nominata prevista | concluído | concluído | parcial | parcial | concluído | entregue em ambos |
| Registro de presença efetiva | concluído | concluído | parcial | parcial | concluído | entregue em ambos |
| Lista final de presentes | concluído | concluído | parcial | parcial | concluído | painel e miniapp com leitura de presentes |
| Certificado | concluído | concluído | parcial | parcial | concluído | web com prefill e mobile acessivel pelo miniapp do cargo |
| Gestão de efemerides no web | concluído | nao_iniciado | parcial | parcial | concluído | tela web consolidada no fluxo do cargo |
| Revisão da mensagem diaria | concluído | concluído | parcial | parcial | concluído | web entregue e bot alinhado ao fluxo atual |
| Cadastro de aniversarios no mobile | nao_iniciado | concluído | parcial | parcial | concluído | miniapp dedicado acessivel pelo launcher do cargo |
| Cadastro de datas maconicas no mobile | nao_iniciado | concluído | parcial | parcial | concluído | miniapp dedicado acessivel pelo launcher do cargo |
| Cadastro de fatos históricos no mobile | nao_iniciado | concluído | parcial | parcial | concluído | miniapp dedicado acessivel pelo launcher do cargo |
| Gestão de mensagens fallback no mobile | nao_iniciado | concluído | parcial | parcial | concluído | miniapp dedicado acessivel pelo launcher do cargo |
| Integracao bot x efemerides | concluído | concluído | parcial | parcial | concluído | aniversarios e datas maconicas agora usam a mesma base de efemerides |

## 13.3 Mestre de Banquetes

| Item | W | M | B | P | Status | Observação |
|---|---|---|---|---|---|---|
| Painel do cargo | concluído | concluído | parcial | parcial | concluído | web e miniapp entregues |
| Total de confirmados | concluído | concluído | parcial | parcial | concluído | entregue em ambos |
| Total do agape | concluído | concluído | parcial | parcial | concluído | entregue em ambos |
| Lista nominal do agape | concluído | concluído | parcial | parcial | concluído | entregue em ambos |
| Observações do agape | concluído | concluído | parcial | parcial | concluído | operação própria do banquete entregue |
| Fechamento logistico do banquete | concluído | concluído | parcial | parcial | concluído | status operacional e previsão entregues em web e mobile |

## 13.4 Hospitaleiro

| Item | W | M | B | P | Status | Observação |
|---|---|---|---|---|---|---|
| Painel de assistencia | concluído | concluído | parcial | parcial | concluído | web e miniapp entregues |
| Registrar ocorrencia | concluído | concluído | parcial | parcial | concluído | web e mobile entregues |
| Atualizar status da ocorrencia | concluído | concluído | parcial | parcial | concluído | web e mobile entregues |
| Encaminhar para Tesouraria/Veneravel | concluído | concluído | parcial | parcial | concluído | fluxo presente no cadastro e no acompanhamento |
| Registrar visita e retorno | concluído | concluído | parcial | parcial | concluído | fluxo proprio entregue |

## 13.5 Primeiro Vigilante

| Item | W | M | B | P | Status | Observação |
|---|---|---|---|---|---|---|
| Painel do cargo | concluído | concluído | parcial | parcial | concluído | web e miniapp do vigilante entregues |
| Lista de Aprendizes | concluído | concluído | parcial | parcial | concluído | miniapp com seletor de Aprendizes |
| Detalhe do Aprendiz | concluído | concluído | parcial | parcial | concluído | web, miniapp do vigilante e miniapp do Aprendiz entregues |
| Trilha completa | concluído | concluído | parcial | parcial | concluído | fluxo ponta a ponta entregue |
| Passar etapa | concluído | concluído | parcial | parcial | concluído | web e mobile entregues |
| Registrar recebimento | concluído | concluído | parcial | parcial | concluído | web e mobile entregues |
| Revisar trabalho | concluído | concluído | parcial | parcial | concluído | web e mobile entregues |
| Histórico formativo | concluído | concluído | parcial | parcial | concluído | histórico consolidado entregue |
| Sugerir leitura | concluído | concluído | parcial | parcial | concluído | integrado ao acervo |
| Solicitar certificado | concluído | concluído | parcial | parcial | concluído | solicitação formal entregue |

## 13.6 Segundo Vigilante

| Item | W | M | B | P | Status | Observação |
|---|---|---|---|---|---|---|
| Painel do cargo | concluído | concluído | parcial | parcial | concluído | web e miniapp do vigilante entregues |
| Lista de Companheiros | concluído | concluído | parcial | parcial | concluído | miniapp com seletor de Companheiros |
| Detalhe do Companheiro | concluído | concluído | parcial | parcial | concluído | web, miniapp do vigilante e miniapp do Companheiro entregues |
| Trilha completa | concluído | concluído | parcial | parcial | concluído | fluxo ponta a ponta entregue |
| Passar etapa | concluído | concluído | parcial | parcial | concluído | web e mobile entregues |
| Registrar recebimento | concluído | concluído | parcial | parcial | concluído | web e mobile entregues |
| Revisar trabalho | concluído | concluído | parcial | parcial | concluído | web e mobile entregues |
| Recomendar docência | concluído | concluído | parcial | parcial | concluído | solicitação formal de certificado entregue |
| Recomendar exaltacao | concluído | concluído | parcial | parcial | concluído | fluxo formal entregue |
| Histórico formativo | concluído | concluído | parcial | parcial | concluído | histórico consolidado entregue |

## 13.7 Orador

| Item | W | M | B | P | Status | Observação |
|---|---|---|---|---|---|---|
| Painel do cargo | concluído | concluído | parcial | parcial | concluído | web e miniapp entregues |
| Resumo da próxima sessão | concluído | concluído | parcial | parcial | concluído | painel enriquecido e selecao de sessão em foco |
| Lista de visitantes | concluído | concluído | parcial | parcial | concluído | leitura ritual entregue em web e mobile |
| Cargos da sessão | concluído | concluído | parcial | parcial | concluído | composicao ritual exposta em ambos |
| Eventos registrados | concluído | concluído | parcial | parcial | concluído | congressos e palestras expostos em ambos |
| Apoio ritual/lembretes | concluído | concluído | parcial | parcial | concluído | roteiro do cargo entregue em web e mobile |

## 13.8 Veneravel

| Item | W | M | B | P | Status | Observação |
|---|---|---|---|---|---|---|
| Painel do Veneravel | concluído | concluído | parcial | parcial | concluído | web e miniapp entregues |
| Publicar sessão | concluído | concluído | parcial | parcial | concluído | web e miniapp entregues |
| Cancelar sessão | concluído | concluído | parcial | parcial | concluído | web e miniapp entregues |
| Reabrir sessão | concluído | concluído | parcial | parcial | concluído | web e miniapp entregues |
| Marcar sessão realizada | concluído | concluído | parcial | parcial | concluído | web e miniapp entregues |
| Abrir votação | concluído | concluído | parcial | parcial | concluído | fluxo agora centralizado no proprio cargo |
| Encerrar votação | concluído | concluído | parcial | parcial | concluído | fluxo agora centralizado no proprio cargo |
| Governanca consolidada | concluído | concluído | parcial | parcial | concluído | painel executivo com pendencias criticas e launcher mobile |

## 13.9 Mestre de Harmonia

| Item | W | M | B | P | Status | Observação |
|---|---|---|---|---|---|---|
| Painel do player | concluído | concluído | parcial | parcial | concluído | web e miniapp entregues |
| Scan da base musical | concluído | concluído | parcial | parcial | concluído | miniapp espelha sessões musicais disponíveis |
| Troca de operador | concluído | concluído | parcial | parcial | concluído | operador em exercicio disponível em ambos |
| Controle de execucao por etapa | concluído | concluído | parcial | parcial | concluído | etapa atual, próxima etapa e selecao manual entregues |
| Controle remoto mobile | concluído | concluído | parcial | parcial | concluído | ações remotas basicas entregues |

## 13.10 Tesouraria

| Item | W | M | B | P | Status | Observação |
|---|---|---|---|---|---|---|
| Livro-caixa | concluído | concluído | parcial | parcial | concluído | painel mobile consolidado com atalho operacional |
| Comprovantes | concluído | concluído | parcial | parcial | concluído | mobile aprova e rejeita pendencias prioritarias |
| Regularidade | concluído | concluído | parcial | parcial | concluído | mobile permite regularizar alertas principais |
| Fechamento mensal | concluído | concluído | parcial | parcial | concluído | mobile fecha a competencia atual e acompanha o resumo |
| Obrigacoes financeiras | concluído | concluído | parcial | parcial | concluído | painel mobile central agora inclui alertas e atalho operacional |
| Sessão/agape financeiro | concluído | concluído | parcial | parcial | concluído | sessões com reflexo financeiro expostas no mobile |

## 13.11 Biblioteca

| Item | W | M | B | P | Status | Observação |
|---|---|---|---|---|---|---|
| Painel do acervo | concluído | concluído | parcial | parcial | concluído | web e miniapp entregues |
| Detalhe do item | concluído | concluído | parcial | parcial | concluído | detalhe rapido no mobile entregue |
| Solicitar emprestimo | concluído | concluído | parcial | parcial | concluído | fluxo direto no detalhe mobile e no web |
| Meus emprestimos | concluído | concluído | parcial | parcial | concluído | painel mobile espelha a leitura do obreiro |
| Comentarios e reacoes | concluído | concluído | parcial | parcial | concluído | mobile publica comentario e registra reacoes |
| Operação do bibliotecario | concluído | concluído | parcial | parcial | concluído | pendencias e atalhos operacionais no miniapp |

## 13.12 Administracao

| Item | W | M | B | P | Status | Observação |
|---|---|---|---|---|---|---|
| Gestão de cargos | concluído | concluído | parcial | parcial | concluído | miniapp atribui titularidade e consulta a nominata atual |
| Gestão de gestões | concluído | concluído | parcial | parcial | concluído | miniapp abre e encerra gestão diretamente |
| Parametros da Loja | concluído | concluído | parcial | parcial | concluído | miniapp atualiza parametros principais da Loja |
| Auditoria critica | concluído | concluído | parcial | parcial | concluído | trilha administrativa consolidada em web e mobile |

## 14. Regra de execucao cargo por cargo

Seguiremos sempre esta ordem para cada cargo:

1. fechar backend e permissão;
2. fechar web;
3. fechar mobile;
4. atualizar esta matriz;
5. so entao iniciar o cargo seguinte.

## 15. Cargo atual de trabalho

| Campo | Valor |
|---|---|
| Cargo em foco | Projeto consolidado |
| Fase | 5 - Consolidacao administrativa |
| Objetivo atual | manter a matriz como fonte viva e seguir para refinamento de frontend/UX |
| Próximo cargo apos concluir | Refinamento de frontend/UX |
