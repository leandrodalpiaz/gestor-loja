# Gestor de Lojas - Cargos, Funções e Regras de Negócio

## 1) Visão geral do sistema
O Gestor de Lojas é um ERP interno, server-rendered em PHP 8.2 com Tailwind, orientado a operação administrativa da Loja Maçônica.  
Os dashboards priorizam a função prática de cada perfil (o que fazer agora), mantendo identidade institucional, densidade operacional e coerência entre módulos.

## 2) Cargos da Loja x Administrador do Sistema
- **Cargos da Loja**: representam funções rituais, administrativas e operacionais da oficina.
- **Administrador do Sistema**: perfil técnico separado, não compõe nominata da Loja e não substitui cargos oficiais.
- Regra central: não misturar semântica de gestão técnica com responsabilidades dos cargos da Loja.

## 3) Perfis e função principal

### veneravel
- **Tipo de dashboard**: estratégico, analítico e de supervisão.
- **Foco**: ritmo de sessões, frequência, ágape e leitura executiva financeira.
- **Operação**: decisões de abertura/encerramento de votação conforme regras vigentes.

### secretario
- **Tipo de dashboard**: operacional e documental.
- **Foco**: sessões, balaústres, publicações, trabalhos, obreiros, acessos e convites.
- **Operação**: execução diária e controle de pendências.

### tesoureiro
- **Tipo de dashboard**: operacional financeiro.
- **Foco**: caixa, obrigações, inadimplência, comprovantes, regularidade, relatórios.
- **Operação**: cobrança, quitação, recibos, fechamento e contexto de miniapp.

### chanceler
- **Tipo de dashboard**: operacional específico de sessão.
- **Foco**: sessão atual, presença, visitantes, consulta de obreiros em leitura.
- **Operação**: abertura de sessão em foco e registro de presença.

### primeiro_vigilante
- **Tipo de dashboard**: operacional formativo (Aprendizes).
- **Foco**: trilha, leituras, certificados, acompanhamento individual.
- **Operação**: atualizar trilha, ação rápida, leitura e solicitação de certificado.

### segundo_vigilante
- **Tipo de dashboard**: operacional formativo (Companheiros).
- **Foco**: trilha, leituras, certificados e recomendação de exaltação.
- **Operação**: atualização de trilha, leituras, certificados e exaltação.

### hospitaleiro
- **Tipo de dashboard**: operacional assistencial.
- **Foco**: ocorrências abertas, visitas pendentes, status e retornos.
- **Operação**: registrar ocorrência, atualizar status e registrar visita.

### orador
- **Tipo de dashboard**: operacional de pauta e leitura ritual.
- **Foco**: sessão em foco, visitantes, cargos de sessão e eventos registrados.
- **Operação**: painel web e miniapp do cargo como extensão do mesmo fluxo.

### mestre_banquetes
- **Tipo de dashboard**: operacional de execução.
- **Foco**: operação do ágape, sessões relacionadas e histórico simples.
- **Operação**: registro de operação e leitura de participantes.

### mestre_harmonia / mestre_de_harmonia
- **Tipo de dashboard**: operacional técnico do cargo.
- **Foco**: estado operacional, execuções, acesso ao miniapp e APIs do módulo.
- **Operação**: scan, áudio, operador e condução da sessão musical.

### bibliotecario
- **Tipo de dashboard**: operacional de gestão completa da biblioteca.
- **Foco**: acervo, empréstimos, devoluções, itens recentes e classificação.
- **Operação**: adicionar/editar/excluir item, registrar empréstimo/devolução e classificar.

### obreiro
- **Tipo de dashboard**: consumo pessoal.
- **Foco**: obrigações pessoais, biblioteca, empréstimos e interações.
- **Operação**: consultar obrigações, solicitar item e acompanhar empréstimos.

### administrador do sistema
- **Tipo de dashboard**: técnico exclusivo.
- **Foco**: parâmetros do sistema, integrações, logs e auditoria técnica.
- **Operação**: sustentação da aplicação, sem papel ritual/oficial de cargo da Loja.

## 4) Miniapps (regra funcional)
- Miniapps são **extensões de perfil**, não um sistema paralelo.
- Aparição obrigatória como ação contextual de cargo:
  - tesoureiro -> `/miniapp/tesouraria`
  - orador -> `/miniapp/orador`
  - mestre_harmonia / mestre_de_harmonia -> `/miniapp/mestre-harmonia`
- Não deslocar miniapps para uma navegação global independente.

## 5) Reutilização de dados entre módulos
- Cada dashboard **consome** dados oficiais produzidos por módulos responsáveis.
- Evitar cálculo duplicado no frontend.
- Evitar fluxo paralelo de dados entre web, dashboard e miniapp.
- Toda informação compartilhada deve manter coerência intermodular.

## 6) Balaústre como fonte de verdade (analytics do Venerável)
- Para indicadores analíticos do Venerável, considerar sessões realizadas com balaústre consolidado/finalizado.
- Balaústre é a referência institucional para frequência, ágape, participantes e visitantes.

## 7) Tesouraria como fonte oficial do resumo financeiro
- Leituras financeiras executivas devem consumir a origem de dados da Tesouraria.
- O dashboard de outros perfis não deve replicar regras de cálculo financeiro operacional.

## 8) Prioridade funcional do dashboard
- Dashboard mostra a **prioridade operacional do cargo**, não toda a amplitude de acesso possível.
- Acesso amplo pode existir por permissão, mas a tela principal deve manter foco do papel institucional.
