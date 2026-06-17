export type NavigationTarget = 'angular' | 'integration';

export interface DashboardNavigationItem {
  label: string;
  target: NavigationTarget;
  path: string;
  permission: string;
}

export interface DashboardNavigationSection {
  id: string;
  label: string;
  items: DashboardNavigationItem[];
}

export const DASHBOARD_NAVIGATION: DashboardNavigationSection[] = [
  {
    id: 'minha-loja',
    label: 'Minha Loja',
    items: [
      { label: 'Irmãos da Loja', target: 'angular', path: '/dashboard/loja', permission: 'dashboard.view' },
      { label: 'Meu Cadastro', target: 'angular', path: '/dashboard/me/cadastro', permission: 'dashboard.view' },
      { label: 'Meus Trabalhos', target: 'angular', path: '/dashboard/trilha', permission: 'dashboard.view' },
      { label: 'Carteirinha', target: 'angular', path: '/dashboard/me/carteirinha', permission: 'dashboard.view' },
      { label: 'Calendário', target: 'angular', path: '/dashboard/calendario', permission: 'dashboard.view' },
      { label: 'Minhas Obrigações', target: 'angular', path: '/dashboard/tesouraria/obrigacoes', permission: 'financeiro.self' },
    ],
  },
  {
    id: 'veneravel',
    label: 'Venerável Mestre',
    items: [
      { label: 'Painel do Venerável', target: 'angular', path: '/dashboard/veneravel', permission: 'veneravel.manage' },
    ],
  },
  {
    id: 'secretaria',
    label: 'Secretaria',
    items: [
      { label: 'Painel da Secretaria', target: 'angular', path: '/dashboard/secretaria', permission: 'secretaria.manage' },
      { label: 'Sessões', target: 'angular', path: '/dashboard/secretaria/sessoes', permission: 'secretaria.manage' },
      { label: 'Balaústres', target: 'angular', path: '/dashboard/secretaria/balaustres', permission: 'secretaria.manage' },
      { label: 'Trabalhos/Publicações', target: 'angular', path: '/dashboard/secretaria/trabalhos-publicacoes', permission: 'secretaria.manage' },
      { label: 'Convites Externos', target: 'angular', path: '/dashboard/secretaria/convites-externos', permission: 'secretaria.manage' },
      { label: 'Relatório Anual', target: 'angular', path: '/dashboard/secretaria/relatorio-anual', permission: 'secretaria.manage' },
      { label: 'Relatório de Gestão', target: 'angular', path: '/dashboard/secretaria/relatorio-gestao', permission: 'secretaria.manage' },
      { label: 'Balaústres em Votação', target: 'angular', path: '/dashboard/secretaria/votacao', permission: 'secretaria.manage' },
      { label: 'Obreiros', target: 'angular', path: '/dashboard/secretaria/obreiros', permission: 'obreiros.view' },
      { label: 'Cadastrar Obreiro', target: 'angular', path: '/dashboard/secretaria/obreiros', permission: 'obreiros.manage' },
      { label: 'Nominata e Cargos', target: 'angular', path: '/dashboard/secretaria/nominata', permission: 'admin.cargos.view' },
      { label: 'Convites de Acesso', target: 'angular', path: '/dashboard/secretaria/convites', permission: 'access.manage' },
      { label: 'Acessos', target: 'angular', path: '/dashboard/secretaria/acessos', permission: 'access.manage' },
      { label: 'Conteúdo Público', target: 'angular', path: '/dashboard/secretaria/conteudo-publico', permission: 'public_content.manage' },
    ],
  },
  {
    id: 'chancelaria',
    label: 'Chancelaria',
    items: [
      { label: 'Sessão da Chancelaria', target: 'angular', path: '/dashboard/chancelaria/sessao', permission: 'chancelaria.manage' },
      { label: 'Efemérides', target: 'angular', path: '/dashboard/chancelaria/efemerides', permission: 'chancelaria.manage' },
      { label: 'Emitir Certificado', target: 'angular', path: '/dashboard/chancelaria/certificado', permission: 'chancelaria.manage' },
    ],
  },
  {
    id: 'tesouraria',
    label: 'Tesouraria',
    items: [
      { label: 'Caixa', target: 'angular', path: '/dashboard/tesouraria/caixa', permission: 'tesouraria.manage' },
      { label: 'Obrigações', target: 'angular', path: '/dashboard/tesouraria/obrigacoes', permission: 'tesouraria.manage' },
      { label: 'Comprovantes', target: 'angular', path: '/dashboard/tesouraria/comprovantes', permission: 'tesouraria.manage' },
      { label: 'Regularidade', target: 'angular', path: '/dashboard/tesouraria/regularidade', permission: 'tesouraria.manage' },
      { label: 'Sessões Financeiras', target: 'angular', path: '/dashboard/tesouraria/sessoes', permission: 'tesouraria.manage' },
      { label: 'Fechamento de Mês', target: 'angular', path: '/dashboard/tesouraria/fechamento', permission: 'tesouraria.manage' },
      { label: 'Relatório de Gestão', target: 'angular', path: '/dashboard/tesouraria/relatorio-gestao', permission: 'tesouraria.manage' },
    ],
  },
  {
    id: 'hospitaleiro',
    label: 'Hospitaleiro',
    items: [
      { label: 'Assistência', target: 'angular', path: '/dashboard/hospitaleiro', permission: 'hospitaleiro.manage' },
    ],
  },
  {
    id: 'primeiro-vigilante',
    label: 'Primeiro Vigilante',
    items: [
      { label: 'Painel do 1º Vigilante', target: 'angular', path: '/dashboard/primeiro-vigilante', permission: 'vigilancia.primeiro.manage' },
    ],
  },
  {
    id: 'segundo-vigilante',
    label: 'Segundo Vigilante',
    items: [
      { label: 'Painel do 2º Vigilante', target: 'angular', path: '/dashboard/segundo-vigilante', permission: 'vigilancia.segundo.manage' },
    ],
  },
  {
    id: 'orador',
    label: 'Orador',
    items: [
      { label: 'Painel do Orador', target: 'angular', path: '/dashboard/orador', permission: 'orador.view' },
    ],
  },
  {
    id: 'banquetes',
    label: 'Mestre de Banquetes',
    items: [
      { label: 'Painel de Banquetes', target: 'angular', path: '/dashboard/mestre-banquetes', permission: 'mestre_banquetes.manage' },
    ],
  },
  {
    id: 'harmonia',
    label: 'Mestre de Harmonia',
    items: [
      { label: 'Painel de Harmonia', target: 'angular', path: '/dashboard/harmonia/player', permission: 'mestre_harmonia.manage' },
      { label: 'Miniapp Harmonia', target: 'integration', path: '/miniapp/mestre-harmonia', permission: 'mestre_harmonia.manage' },
    ],
  },
  {
    id: 'biblioteca',
    label: 'Biblioteca',
    items: [
      { label: 'Acervo', target: 'angular', path: '/dashboard/biblioteca/acervo', permission: 'biblioteca.self' },
      { label: 'Meus Empréstimos', target: 'angular', path: '/dashboard/biblioteca/emprestimos', permission: 'biblioteca.self' },
      { label: 'Gerenciar Empréstimos', target: 'angular', path: '/dashboard/biblioteca/gestao', permission: 'biblioteca.manage' },
      { label: 'Classificação', target: 'angular', path: '/dashboard/biblioteca/classificacao', permission: 'biblioteca.classificar' },
    ],
  },
  {
    id: 'sistema',
    label: 'Sistema',
    items: [
      { label: 'Painel Técnico', target: 'angular', path: '/dashboard/sistema', permission: '*' },
    ],
  },
];
