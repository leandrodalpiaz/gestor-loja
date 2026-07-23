import { Routes } from '@angular/router';
import { Login } from './components/login/login';
import { Dashboard } from './components/dashboard/dashboard';
import { authGuard, guestGuard } from './auth.guard';

export const routes: Routes = [
  { path: 'login', component: Login, canActivate: [guestGuard] },
  {
    path: 'dashboard',
    component: Dashboard,
    canActivate: [authGuard],
    children: [
      { path: '', loadComponent: () => import('./components/dashboard-home/dashboard-home').then(m => m.DashboardHome) },
      { path: 'tesouraria/caixa', loadComponent: () => import('./components/tesouraria-caixa/tesouraria-caixa').then(m => m.TesourariaCaixa) },
      { path: 'tesouraria/regularidade', loadComponent: () => import('./components/tesouraria-regularidade/tesouraria-regularidade').then(m => m.TesourariaRegularidade) },
      { path: 'tesouraria/comprovantes', loadComponent: () => import('./components/tesouraria-comprovantes/tesouraria-comprovantes').then(m => m.TesourariaComprovantes) },
      { path: 'tesouraria/sessoes', loadComponent: () => import('./components/tesouraria-sessoes/tesouraria-sessoes').then(m => m.TesourariaSessoes) },
      { path: 'tesouraria/fechamento', loadComponent: () => import('./components/tesouraria-fechamento/tesouraria-fechamento').then(m => m.TesourariaFechamento) },
      { path: 'tesouraria/relatorio-gestao', loadComponent: () => import('./components/tesouraria-relatorio-gestao/tesouraria-relatorio-gestao').then(m => m.TesourariaRelatorioGestao) },
      { path: 'secretaria/obreiros', loadComponent: () => import('./components/secretaria-obreiros/secretaria-obreiros').then(m => m.SecretariaObreiros) },
      { path: 'secretaria', loadComponent: () => import('./components/secretaria-dashboard/secretaria-dashboard').then(m => m.SecretariaDashboard) },
      { path: 'secretaria/sessoes', loadComponent: () => import('./components/secretaria-sessoes/secretaria-sessoes').then(m => m.SecretariaSessoes) },
      { path: 'secretaria/trabalhos-publicacoes', loadComponent: () => import('./components/secretaria-trabalhos-publicacoes/secretaria-trabalhos-publicacoes').then(m => m.SecretariaTrabalhosPublicacoes) },
      { path: 'secretaria/votacao', loadComponent: () => import('./components/secretaria-votacao/secretaria-votacao').then(m => m.SecretariaVotacao) },
      { path: 'secretaria/nominata', loadComponent: () => import('./components/secretaria-nominata/secretaria-nominata').then(m => m.SecretariaNominata) },
      { path: 'secretaria/convites', loadComponent: () => import('./components/secretaria-convites/secretaria-convites').then(m => m.SecretariaConvites) },
      { path: 'secretaria/convites-externos', loadComponent: () => import('./components/secretaria-convites-externos/secretaria-convites-externos').then(m => m.SecretariaConvitesExternos) },
      { path: 'secretaria/acessos', loadComponent: () => import('./components/secretaria-acessos/secretaria-acessos').then(m => m.SecretariaAcessos) },
      { path: 'secretaria/conteudo-publico', loadComponent: () => import('./components/secretaria-conteudo-publico/secretaria-conteudo-publico').then(m => m.SecretariaConteudoPublico) },
      { path: 'secretaria/relatorio-anual', loadComponent: () => import('./components/secretaria-relatorio-anual/secretaria-relatorio-anual').then(m => m.SecretariaRelatorioAnual) },
      { path: 'secretaria/relatorio-gestao', loadComponent: () => import('./components/secretaria-relatorio-anual/secretaria-relatorio-anual').then(m => m.SecretariaRelatorioAnual) },
      { path: 'chancelaria/efemerides', loadComponent: () => import('./components/chancelaria-efemerides/chancelaria-efemerides').then(m => m.ChancelariaEfemerides) },
      { path: 'chancelaria/sessao', loadComponent: () => import('./components/chancelaria-sessao/chancelaria-sessao').then(m => m.ChancelariaSessao) },
      { path: 'chancelaria/certificado', loadComponent: () => import('./components/chancelaria-certificado/chancelaria-certificado').then(m => m.ChancelariaCertificado) },
      { path: 'veneravel', loadComponent: () => import('./components/veneravel-dashboard/veneravel-dashboard').then(m => m.VeneravelDashboard) },
      { path: 'hospitaleiro', loadComponent: () => import('./components/hospitaleiro-dashboard/hospitaleiro-dashboard').then(m => m.HospitaleiroDashboard) },
      { path: 'primeiro-vigilante', loadComponent: () => import('./components/primeiro-vigilante-dashboard/primeiro-vigilante-dashboard').then(m => m.PrimeiroVigilanteDashboard) },
      { path: 'segundo-vigilante', loadComponent: () => import('./components/segundo-vigilante-dashboard/segundo-vigilante-dashboard').then(m => m.SegundoVigilanteDashboard) },
      { path: 'mestre-banquetes', loadComponent: () => import('./components/mestre-banquetes-dashboard/mestre-banquetes-dashboard').then(m => m.MestreBanquetesDashboard) },
      { path: 'orador', loadComponent: () => import('./components/orador-dashboard/orador-dashboard').then(m => m.OradorDashboard) },
      { path: 'harmonia/player', loadComponent: () => import('./components/harmonia-player/harmonia-player').then(m => m.HarmoniaPlayer) },
      { path: 'me/carteirinha', loadComponent: () => import('./components/carteirinha-digital/carteirinha-digital').then(m => m.CarteirinhaDigital) },
      { path: 'me/cadastro', loadComponent: () => import('./components/meu-cadastro/meu-cadastro').then(m => m.MeuCadastro) },
      { path: 'calendario', loadComponent: () => import('./components/calendario-grid/calendario-grid').then(m => m.CalendarioGrid) },
      { path: 'trilha', loadComponent: () => import('./components/trilha-graus/trilha-graus').then(m => m.TrilhaGraus) },
      { path: 'loja', loadComponent: () => import('./components/minha-loja/minha-loja').then(m => m.MinhaLoja) },
      { path: 'loja/irmaos', loadComponent: () => import('./components/minha-loja/minha-loja').then(m => m.MinhaLoja), data: { lojaTab: 'irmaos' } },
      { path: 'secretaria/balaustres', loadComponent: () => import('./components/secretaria-balaustres/secretaria-balaustres').then(m => m.SecretariaBalaustres) },
      { path: 'tesouraria/obrigacoes', loadComponent: () => import('./components/tesouraria-obrigacoes/tesouraria-obrigacoes').then(m => m.TesourariaObrigacoes) },
      { path: 'cargos', loadComponent: () => import('./components/cargos-gestao/cargos-gestao').then(m => m.CargosGestao) },
      { path: 'biblioteca/acervo', loadComponent: () => import('./components/biblioteca-acervo/biblioteca-acervo').then(m => m.BibliotecaAcervo) },
      { path: 'biblioteca/emprestimos', loadComponent: () => import('./components/biblioteca-acervo/biblioteca-acervo').then(m => m.BibliotecaAcervo), data: { bibliotecaTab: 'meus' } },
      { path: 'biblioteca/gestao', loadComponent: () => import('./components/biblioteca-acervo/biblioteca-acervo').then(m => m.BibliotecaAcervo), data: { bibliotecaTab: 'gestao' } },
      { path: 'biblioteca/classificacao', loadComponent: () => import('./components/biblioteca-acervo/biblioteca-acervo').then(m => m.BibliotecaAcervo), data: { bibliotecaTab: 'classificacao' } },
      { path: 'sistema', loadComponent: () => import('./components/sistema-config/sistema-config').then(m => m.SistemaConfig) }
    ]
  },
  { path: '', redirectTo: 'login', pathMatch: 'full' }
];
