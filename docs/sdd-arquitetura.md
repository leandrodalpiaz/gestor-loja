# Documento de Especificação Arquitetural (SDD) - ERP Gestor Lojas

**Objetivo:** Orientar a refatoração visual, arquitetural e de permissões do ERP para Lojas Maçônicas, consolidando o PWA como front-end principal e preparando o sistema para o modelo Multi-tenant (SaaS).

## 1. Stack Tecnológica e Princípios Base
- **Back-end:** PHP 8.2 (Server-rendered) com gestão de estado via sessão.
- **Front-end:** Tailwind CSS nativo (sem introdução de novos frameworks JavaScript como React ou Vue).
- **Banco de Dados:** Supabase (PostgreSQL) utilizando UUIDs nativos.
- **Infraestrutura:** Manter a compatibilidade com o Docker atual e scripts de long polling/webhook.
- **Canal Principal:** O PWA assume o protagonismo; o bot do Telegram passa a atuar como notificador secundário (envio de links de acesso rápido).

## 2. Arquitetura Multi-Tenant e Identidade Dinâmica
O sistema deve isolar completamente os dados e a identidade visual de diferentes Lojas.
- **Isolamento de Dados:** Toda consulta ao banco de dados deve obrigatoriamente aplicar o filtro `loja_id` (UUID) amarrado à sessão do usuário logado.
- **Tabela de Configurações Dinâmicas:** A tabela de configurações da loja (ex: `lojas_configuracoes`) deve ditar o comportamento do PWA para suportar Dark/Light mode.
- **Colunas Obrigatórias na Configuração:** `loja_id`, `nome_oficial`, `cnpj`, `logo_path`, `cor_primaria_light` (ex: #1E3A8A), `cor_primaria_dark` (ex: #0F172A).
- **Manifesto PWA Dinâmico:** O arquivo `manifest.json` (ou `manifest.php`) deve ser gerado pelo servidor, lendo o banco de dados para injetar o nome da Loja, o logotipo e forçar os parâmetros `display: standalone` e `orientation: portrait`.

## 3. Controle de Acesso (RBAC) e Isolamento de Sessões
A regra principal é a não concomitância de telas. Não existe um "Painel Admin" global que misture cargos.
- **Nível 1 (Obreiro):** Acesso restrito aos seus próprios dados (Perfil, Obrigações financeiras, Biblioteca). Sem acesso a telas de gestão.
- **Nível 2 (Cargo):** Permissões somadas ao Nível 1. A navegação exibe o módulo específico do cargo (ex: Tesouraria). Telas e lógicas de cargos não se misturam no front-end.
- **Nível 3 (Admin do Sistema / Ghost Login):** Acesso de infraestrutura utilizando a mesma credencial de Obreiro do desenvolvedor, mas isolado em sessão.
- **Troca de Contexto (Modo Suporte):** Ao acessar a rota oculta `/admin-suporte`, o PHP injeta uma flag na sessão (ex: `$_SESSION['admin_mode'] = true`). As ações realizadas neste modo devem ser registradas no banco de dados como `system_admin_action` para não poluir os logs da Loja ou o histórico civil do Obreiro.
- **Personificação (Impersonation):** O Admin em modo suporte pode visualizar o sistema sob a ótica de qualquer Loja para fins de diagnóstico.

## 4. Diretrizes de Interface e UI/UX
O sistema deve transmitir sobriedade e possuir "cara de sistema", não de site institucional.
- **Desktop-First (Gestão):** Telas grandes mantêm tabelas ricas, cabeçalhos fixos e sidebar à esquerda.
- **Mobile-First (Operação):** Tabelas devem ser ocultadas e substituídas por Cards empilhados (largura 100%). Evitar barra de rolagem horizontal em qualquer breakpoint.
- **Navegação Mobile:** Substituir o menu lateral (hambúrguer) por uma Bottom Navigation Bar fixa no rodapé e uma Sticky Top Bar contendo o título da página e botão de voltar.
- **Componentização PHP/Blade:** Criar arquivos de inclusão padronizados (`card-obreiro.php`, `badge-status.php`) utilizando Tailwind, garantindo consistência na apresentação de status.

## 5. Padrão Vernacular ("Maçonês" Discreto)
O texto impresso na tela deve ser de um português-BR impecável, formal e minimalista. Mensagens de erro de sistema ou servidor não devem ser expostas ao usuário.

| Termo Proibido / Genérico | Termo Oficial do Sistema | Regra de Uso |
| :--- | :--- | :--- |
| Membros, Usuários, Clientes | Quadro de Obreiros | Telas de listagem e gestão de pessoas. |
| Financeiro, Pagamentos | Tesouraria | Módulo do cargo e navegação. |
| Usuário logado / Sucesso | Seja bem-vindo, Irmão | Apenas após o login. |
| Oriente (como saudação) | Oriente (como cidade) | Restrito a campos de endereço geográfico. |

---

### ⚠️ CLÁUSULA DE RISCO ZERO (RESTRIÇÕES ABSOLUTAS PARA O AGENTE)

Para garantir a estabilidade do sistema que já se encontra operacional e hospedado no Render, o agente responsável pela execução do código deve seguir obrigatoriamente as seguintes restrições estruturais:

* **Infraestrutura Intocável:** É estritamente proibido alterar o arquivo `.env`, as chaves de API, tokens do Telegram, ou credenciais do Supabase. O ambiente Docker e os scripts de *long polling/webhook* originais não devem sofrer modificações arquiteturais.
* **Banco de Dados Preservado:** Nenhuma tabela existente deve ser apagada (`DROP`) ou recriada do zero. Toda transição para o modelo Multi-tenant deve ocorrer de forma aditiva (incremental), preservando os dados e a estrutura de logins vigentes.
* **Lógica de Negócio Blindada (Tesouraria):** As regras do *back-end* do Tesoureiro já foram homologadas. O agente não tem permissão para alterar a lógica de transações atômicas, cálculos, ou rotas de aprovação/cancelamento financeiro (`TesourariaApiRoutes.php`).
* **Escopo Fechado de Atuação:** A refatoração deve focar **exclusivamente na camada de apresentação** (Views em Blade/PHP, classes do Tailwind CSS, comportamento responsivo, `manifest.json` do PWA) e na blindagem de sessões no PHP para o correto controle de acesso (RBAC).

A quebra de qualquer uma destas restrições será considerada uma falha crítica na execução do prompt.
