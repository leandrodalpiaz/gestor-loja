# Gestor de Lojas - Cargos, Funções e Regras de Negócio

## 1) Visão geral

O Gestor de Lojas é um ERP interno (PHP 8.2 server-rendered + Tailwind + Supabase Postgres), orientado à operação administrativa da Loja Maçônica.

Diretriz atual:
- **Web desktop-first** para gestão completa.
- **Mobile PWA-first**: a PWA é a experiência principal no mobile.
- **Telegram secundário**: usado como complemento/atalhos quando fizer sentido (baixo engajamento).

## 2) Cargos da Loja x Administrador do Sistema

- **Cargos da Loja**: responsabilidades rituais/administrativas/operacionais.
- **Administrador do Sistema**: perfil técnico separado, não compõe nominata e não substitui cargos oficiais.
- Regra central: não misturar semântica de gestão técnica com responsabilidades oficiais.

## 3) Perfis e foco do dashboard

Regra: o dashboard mostra a **prioridade operacional do cargo**, não toda a amplitude de acesso possível.

### veneravel
- Estratégico/analítico e de supervisão.
- Sessões e decisões: publicar/cancelar/reabrir/realizar, e ações de balaústre conforme permissão.

### secretario
- Operacional e documental.
- Sessões, balaústres, publicações/trabalhos, obreiros, acessos e convites.

### tesoureiro
- Operacional financeiro.
- Caixa, obrigações, comprovantes, regularidade, relatórios e fechamento.

### chanceler
- Operacional de sessão.
- Presença, visitantes e suporte à sessão em foco.

### primeiro_vigilante
- Formativo (Aprendizes).
- Trilhas, leituras, acompanhamento e certificados.

### segundo_vigilante
- Formativo (Companheiros).
- Trilhas, leituras, acompanhamento e certificados, incluindo recomendações quando previstas.

### hospitaleiro
- Assistencial.
- Ocorrências, visitas, status e retornos.

### orador
- Pauta e leitura ritual.
- Sessão em foco, visitantes e rotinas do cargo.

### mestre_banquetes
- Execução do ágape.
- Operação e consolidação simples de presença/itens ligados ao ágape.

### mestre_harmonia / mestre_de_harmonia
- Operação técnica do cargo.
- Painel, scan/áudio/operador e condução operacional.

### bibliotecario
- Gestão completa da biblioteca.
- Acervo, empréstimos/devoluções e classificação.

### obreiro
- Consumo pessoal.
- Financeiro pessoal, biblioteca (consulta/solicitação conforme regras) e acompanhamento.

### administrador do sistema
- Técnico exclusivo.
- Parâmetros, integrações, logs e auditoria técnica.

## 4) PWA (regra funcional)

- A PWA é o canal mobile principal e deve concentrar os fluxos de consulta e ação rápida.
- No mobile, preferir cards a tabelas e evitar scroll horizontal.

## 5) Telegram (papel secundário)

- Telegram é secundário e não deve ser tratado como fonte principal de engajamento.
- Quando existir integração, usar como atalho/deeplink e contingência, sem duplicar regra de negócio.

## 6) Fontes de verdade e reuso de dados

- Cada dashboard consome dados oficiais produzidos por módulos responsáveis.
- Evitar cálculo duplicado no frontend.
- Evitar fluxos paralelos de dados entre web e PWA.

## 7) Balaústre como fonte institucional

Para indicadores e auditoria, considerar sessões realizadas com balaústre consolidado/finalizado.

## 8) Tesouraria como fonte oficial do resumo financeiro

Leituras financeiras executivas devem consumir a origem de dados da Tesouraria; outros perfis não devem replicar regra financeira operacional.
