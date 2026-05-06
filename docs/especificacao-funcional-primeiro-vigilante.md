# ESPECIFICAÇÃO FUNCIONAL - PRIMEIRO VIGILANTE

## 1. Objetivo

Este documento define os requisitos funcionais para criar o cargo `PRIMEIRO_VIGILANTE` no ERP `gestor-loja`, com foco na formacao dos Aprendizes.

O 1o Vigilante, dentro do sistema, deve ser tratado como:

- orientador da Coluna do Norte;
- referencia de conhecimento para os Aprendizes;
- responsável por conduzir a trilha de estudo;
- responsável por passar, receber e revisar os trabalhos de instrucao;
- incentivador da leitura e do aprofundamento simbolico;
- agente de recomendacao positiva para o progresso do Aprendiz.

O objetivo não e criar um painel de vigilancia corretiva, mas um painel de instrucao e desenvolvimento.

## 2. Contexto atual do projeto

O projeto já possui base técnica para sustentar esse cargo:

- cargos oficiais em `cargos` e `atribuicoes_cargo`;
- dashboard geral por cargos;
- tela administrativa de atribuicao de cargos;
- módulo de obreiros com campo `grau`;
- módulo de biblioteca;
- módulo de sessões;
- estrutura de autenticacao por cargo.

Conclusão:

o 1o Vigilante deve nascer como cargo oficial com painel proprio, aproveitando a arquitetura existente e adicionando uma camada especifica de acompanhamento formativo dos Aprendizes.

## 3. Papel do 1o Vigilante no sistema

O papel do 1o Vigilante no sistema e acompanhar o desenvolvimento do Aprendiz por meio de uma trilha fixa de estudo.

Esse acompanhamento deve ser positivo, orientativo e formativo.

O 1o Vigilante deve poder:

- acompanhar em que etapa cada Aprendiz esta;
- passar conteudos e instrucoes;
- receber trabalhos;
- revisar trabalhos;
- registrar devolutivas formativas;
- sugerir leituras e livros da biblioteca;
- sugerir temas de aprofundamento;
- indicar que o Aprendiz concluiu a docência maconica.

## 4. Problema que o módulo precisa resolver

Hoje o sistema possui obreiros, cargos, biblioteca e sessões, mas não possui um fluxo estruturado para a jornada de formacao do Aprendiz.

O módulo precisa responder perguntas como:

- em que etapa da trilha cada Aprendiz se encontra;
- quais trabalhos já foram passados;
- quais trabalhos já foram recebidos;
- quais trabalhos aguardam revisão;
- quais leituras e temas foram sugeridos;
- quais Aprendizes concluiram a trilha;
- quais Aprendizes estao aptos para solicitação do certificado de conclusão da docência maconica.

## 5. Premissas

- O sistema atende uma única loja.
- O foco do módulo sera obreiros com `grau = 'Aprendiz'`.
- O cargo `PRIMEIRO_VIGILANTE` tera um único titular ativo por vez, seguindo a regra atual do sistema.
- A trilha de estudo do Aprendiz sera fixa nesta primeira etapa.
- O centro do módulo e a formacao, não disciplina corretiva.
- Informações de presença podem existir como apoio futuro, mas não sao o eixo principal do cargo.

## 6. Eixo central do módulo

O centro funcional do 1o Vigilante deve ser a `trilha de estudo por Aprendiz`.

Cada Aprendiz devera ter sua própria trilha, com etapas claras, progresso visível e histórico de orientações.

## 7. Trilha fixa de estudo do Aprendiz

Cada Aprendiz deve seguir as seguintes etapas:

1. `Entrega das impressoes de iniciacao`
2. `Passar o complemento a iniciacao`
3. `Passar e receber o trabalho da 1a instrucao`
4. `Passar e receber o trabalho da 2a instrucao`
5. `Passar e receber o trabalho da 3a instrucao`
6. `Passar e receber o trabalho da 4a instrucao`
7. `Passar e receber o trabalho da 5a instrucao`
8. `Solicitar o certificado de conclusão da docência maconica`

Essa trilha sera o fluxo principal do painel do 1o Vigilante.

## 8. Requisitos funcionais

### 8.1 Cadastro oficial do cargo

O sistema deve:

- incluir o cargo `PRIMEIRO_VIGILANTE` no catalogo oficial de cargos;
- permitir a atribuicao do cargo pela tela administrativa atual;
- reconhecer o cargo no sistema de autenticacao e autorizacao;
- exibir o cargo na nominata principal do dashboard.

### 8.2 Painel do 1o Vigilante

O sistema deve disponibilizar uma tela própria para o 1o Vigilante.

Esse painel deve ser centrado em instrucao e acompanhamento formativo.

O painel deve mostrar, no mínimo:

- total de Aprendizes ativos;
- Aprendizes em acompanhamento;
- etapa atual de cada Aprendiz;
- trabalhos aguardando entrega;
- trabalhos recebidos aguardando revisão;
- Aprendizes com trilha concluída;
- Aprendizes aptos para solicitação de certificado.

O painel deve oferecer ações como:

- `Ver Aprendizes`
- `Abrir trilha do Aprendiz`
- `Passar conteudo`
- `Registrar recebimento`
- `Revisar trabalho`
- `Sugerir leitura`
- `Solicitar certificado`

### 8.3 Lista de Aprendizes

O sistema deve listar obreiros ativos com `grau = 'Aprendiz'`, exibindo ao menos:

- nome;
- CIM;
- data de iniciacao;
- etapa atual da trilha;
- status da etapa atual;
- ultima orientação registrada;
- situação geral da formacao.

### 8.4 Acompanhamento por trilha

Para cada Aprendiz, o sistema deve manter a trilha completa com as 8 etapas fixas.

Cada etapa deve permitir registro de:

- status da etapa;
- data em que a etapa foi passada ao Aprendiz;
- data em que o trabalho foi recebido;
- data de revisão;
- comentario orientativo do 1o Vigilante;
- material enviado ou entregue, quando houver.

### 8.5 Passar conteudo ao Aprendiz

O 1o Vigilante deve conseguir registrar que uma etapa foi iniciada e passada ao Aprendiz.

Exemplos:

- complemento a iniciacao entregue;
- 1a instrucao passada;
- 2a instrucao passada.

O sistema deve registrar:

- qual etapa foi passada;
- quando foi passada;
- quem passou;
- orientação complementar, se houver.

### 8.6 Receber trabalhos

O sistema deve permitir registrar que o trabalho da etapa foi recebido.

O recebimento deve registrar:

- etapa;
- data de recebimento;
- responsável pelo recebimento;
- observação breve;
- anexo, texto ou referencia ao material entregue, se houver suporte nessa etapa do projeto.

### 8.7 Revisar trabalhos

O sistema deve permitir ao 1o Vigilante revisar os trabalhos recebidos e devolver uma avaliacao formativa.

Essa revisão deve contemplar:

- comentario orientativo;
- status da revisão;
- data da revisão;
- responsável pela revisão.

O foco da revisão deve ser:

- amadurecimento do Aprendiz;
- compreensao simbolica;
- coerencia do trabalho;
- incentivo ao aprofundamento.

### 8.8 Sugestao de leituras e temas

O 1o Vigilante deve poder sugerir leituras e temas para cada Aprendiz.

Essas sugestoes devem incluir:

- livro do acervo;
- texto recomendado;
- tema de estudo;
- observação do por que a leitura foi indicada.

O módulo deve se integrar com a biblioteca para:

- consultar livros do acervo;
- associar livros ao Aprendiz;
- registrar recomendacoes de leitura;
- futuramente permitir controle de leitura sugerida, iniciada e concluída.

### 8.9 Histórico de orientações

O sistema deve manter histórico das orientações dadas ao Aprendiz.

Esse histórico deve ser positivo e formativo, servindo para registrar:

- orientações de estudo;
- devolutivas de revisão;
- encaminhamentos da trilha;
- sugestoes de leitura;
- recomendacoes de aprofundamento.

### 8.10 Conclusão da trilha

Quando o Aprendiz concluir todas as etapas anteriores, o sistema deve permitir ao 1o Vigilante registrar que ele esta apto para a etapa final da trilha.

Essa conclusão deve representar:

- trilha formativa concluída;
- docência maconica concluída;
- prontidao para solicitação do certificado.

### 8.11 Solicitação do certificado de conclusão

O sistema deve permitir ao 1o Vigilante solicitar o certificado de conclusão da docência maconica.

Essa ação deve registrar:

- Aprendiz;
- data da solicitação;
- vigilante responsável;
- observação opcional.

Nesta primeira etapa, a funcionalidade pode comecar apenas como registro interno da solicitação.

Se depois houver fluxo com outro cargo ou geração automatica, esse passo podera ser expandido.

## 9. Status sugeridos

### 9.1 Status por etapa

Cada etapa da trilha pode usar status como:

- `nao_iniciado`
- `disponibilizado`
- `aguardando_entrega`
- `recebido`
- `revisado`
- `concluído`

### 9.2 Status geral do Aprendiz

O Aprendiz pode ter um status geral de acompanhamento, como:

- `iniciando_jornada`
- `em_formacao`
- `em_execucao_de_trilha`
- `trabalho_em_revisao`
- `trilha_concluida`
- `apto_para_certificado`
- `certificado_solicitado`

## 10. Requisitos de permissão

O cargo `PRIMEIRO_VIGILANTE` deve ter permissão para:

- acessar painel proprio;
- consultar Aprendizes;
- visualizar trilha de estudo;
- passar etapas da trilha;
- registrar recebimento de trabalhos;
- revisar trabalhos;
- registrar orientações;
- sugerir temas;
- sugerir livros da biblioteca;
- solicitar certificado de conclusão.

O cargo não deve, nesta etapa inicial:

- editar cadastro geral de obreiros;
- alterar sessões;
- atribuir cargos;
- executar a administracao global do sistema.

O Veneravel Mestre e o Administrador podem ter acesso de contingencia ou consulta.

## 11. Modelo funcional mínimo de dados

### 11.1 Reaproveitamento do que já existe

Devem ser reaproveitados:

- `obreiros`
- `cargos`
- `atribuicoes_cargo`
- `acervo` e demais estruturas da biblioteca

### 11.2 Estrutura sugerida para trilha do Aprendiz

Tabela sugerida: `trilha_aprendiz`

Campos minimos:

- `id`
- `aprendiz_id`
- `etapa_ordem`
- `titulo_etapa`
- `status`
- `data_disponibilizacao`
- `data_entrega`
- `data_revisao`
- `observacao_vigilante`
- `arquivo_entrega`
- `revisado_por`
- `created_at`
- `updated_at`

Observação:

- essa tabela guarda o progresso concreto da trilha por Aprendiz.

### 11.3 Estrutura sugerida para sugestoes de leitura

Tabela sugerida: `leituras_aprendiz`

Campos minimos:

- `id`
- `aprendiz_id`
- `livro_id`
- `tema`
- `observação`
- `sugerido_por`
- `status`
- `data_sugestao`
- `created_at`

Status sugeridos:

- `sugerido`
- `em_leitura`
- `concluído`

### 11.4 Estrutura sugerida para orientações e devolutivas

Se necessario, pode existir uma tabela própria como `orientacoes_aprendiz`.

Mas, para a primeira entrega, tambem e aceitavel manter a observação e devolutiva dentro da própria `trilha_aprendiz`, desde que o histórico fique preservado.

## 12. Regras de negócio

- Apenas obreiros ativos com `grau = 'Aprendiz'` entram nesse módulo.
- Cada Aprendiz deve possuir a trilha completa com as 8 etapas padronizadas.
- A etapa seguinte so deve ser liberada quando a anterior estiver adequadamente concluída, se essa regra for adotada no fluxo.
- O 1o Vigilante e o responsável principal por passar, receber e revisar as etapas.
- Nenhum registro de trilha deve apagar o histórico anterior sem auditoria.
- A solicitação do certificado depende da conclusão da trilha.
- Sugestoes de leitura devem funcionar como apoio formativo complementar.

## 13. Requisitos de interface

### 13.1 Dashboard geral

O dashboard principal deve:

- exibir `1 Vigilante` na nominata;
- mostrar a área do 1o Vigilante quando o usuário possuir esse cargo;
- incluir link para o painel do cargo.

### 13.2 Rotas sugeridas

Rotas sugeridas:

- `/primeiro-vigilante`
- `/primeiro-vigilante/aprendizes`
- `/primeiro-vigilante/aprendiz?id=...`
- `/primeiro-vigilante/trilha/salvar`
- `/primeiro-vigilante/leituras/sugerir`
- `/primeiro-vigilante/certificado/solicitar`

### 13.3 Experiencia inicial minima

Primeira versao recomendada:

- cards de resumo;
- lista de Aprendizes;
- tela de detalhe do Aprendiz;
- visualização da trilha em etapas;
- formulario simples para passar, receber e revisar trabalhos;
- formulario para sugerir leitura.

## 14. Integracoes com o projeto atual

### 14.1 Arquivos que provavelmente precisarao de ajuste

- `database/migrations/002_seed_cargos.sql`
- `src/Models/Cargo.php`
- `src/Models/Obreiro.php`
- `public/index.php`
- `src/Views/dashboard.php`

### 14.2 Novos componentes provaveis

- `src/Controllers/PrimeiroVigilanteController.php`
- `src/Models/TrilhaAprendiz.php`
- `src/Models/LeituraAprendiz.php`
- `src/Views/primeiro_vigilante/index.php`
- `src/Views/primeiro_vigilante/aprendiz.php`

## 15. Ordem recomendada de implementação

### Fase 1 - Fundacao do cargo

- adicionar `PRIMEIRO_VIGILANTE` ao seed;
- mapear o cargo no sistema;
- liberar acesso por rota;
- incluir o cargo no dashboard e na nominata.

### Fase 2 - Painel inicial

- criar controller e view do 1o Vigilante;
- listar Aprendizes;
- mostrar etapa atual de cada Aprendiz;
- exibir resumo do acompanhamento formativo.

### Fase 3 - Trilha de estudo

- criar tabela da trilha;
- popular as 8 etapas por Aprendiz;
- permitir passar conteudo;
- permitir registrar recebimento;
- permitir revisar trabalhos.

### Fase 4 - Biblioteca e certificado

- integrar sugestao de leituras com a biblioteca;
- registrar temas indicados;
- implementar solicitação do certificado de conclusão da docência maconica.

## 16. Recomendacao final

O 1o Vigilante deve ser implementado como um cargo de instrucao e acompanhamento formativo.

O núcleo correto do módulo e:

- trilha de estudo por Aprendiz;
- passagem e recebimento de instrucoes;
- revisão de trabalhos;
- sugestao de leituras e temas;
- conclusão da jornada formativa;
- solicitação do certificado final.

Essa abordagem representa melhor a natureza do cargo, aproveita o que o sistema já possui e cria uma base sólida para a implementação.
