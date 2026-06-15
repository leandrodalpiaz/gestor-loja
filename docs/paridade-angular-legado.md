# Paridade Angular x PHP legado

Este documento é a referência operacional da migração. Uma tela só pode ser considerada migrada quando preserva regras de negócio, permissões, persistência, auditoria e comportamento após recarga.

## Estados

- **Angular**: rota SPA existente e exposta no menu.
- **Integração**: abre um canal complementar preservado, como miniapps.
- **Parcial**: existe tela Angular, mas ainda depende de fluxo ou detalhe legado.

## Matriz atual

| Módulo | Funcionalidade | Estado | Rota atual |
| --- | --- | --- | --- |
| Minha Loja | Área do Irmão | Angular | `/dashboard/loja` |
| Minha Loja | Meu Cadastro | Angular | `/dashboard/me/cadastro` |
| Minha Loja | Meus Trabalhos | Angular | `/dashboard/trilha` |
| Minha Loja | Irmãos da Loja | Angular | `/dashboard/loja/irmaos` |
| Minha Loja | Carteirinha | Angular | `/dashboard/me/carteirinha` |
| Minha Loja | Calendário | Angular | `/dashboard/calendario` |
| Secretaria | Painel da Secretaria | Angular | `/dashboard/secretaria` |
| Secretaria | Sessões | Angular | `/dashboard/secretaria/sessoes` |
| Secretaria | Balaústres | Angular | `/dashboard/secretaria/balaustres` |
| Secretaria | Trabalhos e Publicações | Angular | `/dashboard/secretaria/trabalhos-publicacoes` |
| Secretaria | Convites Externos | Angular | `/dashboard/secretaria/convites-externos` |
| Secretaria | Relatórios | Angular | `/dashboard/secretaria/relatorio-anual` |
| Secretaria | Votação | Angular | `/dashboard/secretaria/votacao` |
| Secretaria | Obreiros | Angular | `/dashboard/secretaria/obreiros` |
| Secretaria | Cadastrar Obreiro | Angular | `/dashboard/secretaria/obreiros` |
| Secretaria | Nominata e Cargos | Angular | `/dashboard/secretaria/nominata` |
| Secretaria | Convites e Acessos | Angular | `/dashboard/secretaria/convites` |
| Secretaria | Conteúdo Público | Angular | `/dashboard/secretaria/conteudo-publico` |
| Chancelaria | Sessão da Chancelaria | Angular | `/dashboard/chancelaria/sessao` |
| Chancelaria | Efemérides | Angular | `/dashboard/chancelaria/efemerides` |
| Chancelaria | Visitantes | Angular | `/dashboard/chancelaria/sessao` |
| Chancelaria | Certificado | Angular | `/dashboard/chancelaria/certificado` |
| Tesouraria | Caixa | Angular | `/dashboard/tesouraria/caixa` |
| Tesouraria | Obrigações | Angular | `/dashboard/tesouraria/obrigacoes` |
| Tesouraria | Regularidade | Angular | `/dashboard/tesouraria/regularidade` |
| Tesouraria | Comprovantes | Angular | `/dashboard/tesouraria/comprovantes` |
| Tesouraria | Sessões Financeiras | Angular | `/dashboard/tesouraria/sessoes` |
| Tesouraria | Fechamento de Mês | Angular | `/dashboard/tesouraria/fechamento` |
| Tesouraria | Relatório de Gestão | Angular | `/dashboard/tesouraria/relatorio-gestao` |
| Venerável Mestre | Painel | Angular | `/dashboard/cargos` |
| Hospitaleiro | Assistência | Angular | `/dashboard/cargos` |
| Primeiro Vigilante | Painel | Angular | `/dashboard/cargos` |
| Segundo Vigilante | Painel | Angular | `/dashboard/cargos` |
| Orador | Painel | Angular | `/dashboard/cargos` |
| Mestre de Banquetes | Painel | Angular | `/dashboard/cargos` |
| Mestre de Harmonia | Painel | Angular | `/dashboard/harmonia/player` |
| Biblioteca | Acervo | Angular | `/dashboard/biblioteca/acervo` |
| Biblioteca | Meus Empréstimos | Angular | `/dashboard/biblioteca/emprestimos` |
| Biblioteca | Gerenciar Empréstimos | Angular | `/dashboard/biblioteca/gestao` |
| Biblioteca | Classificação | Angular | `/dashboard/biblioteca/classificacao` |
| Sistema | Painel Técnico | Angular | `/dashboard/sistema` |

## Critério obrigatório para remover um bridge

1. API PHP equivalente protegida pela mesma permissão RBAC.
2. Listagem, criação, edição, exclusão e ações especiais funcionando no Angular.
3. IDs UUID preservados sem conversão numérica.
4. Tenant, autor e auditoria persistidos corretamente.
5. Validação de recarga, erro, `401`, `403` e sessão expirada.
6. Fluxos PHP, Telegram e miniapps não afetados.

## Ordem de conclusão

1. Cadastros pessoais e obreiros.
2. Secretaria completa.
3. Tesouraria completa.
4. Chancelaria completa.
5. Painéis individuais dos oficiais.
6. Biblioteca completa.
7. Remoção final do bridge e das rotas visuais PHP substituídas.
