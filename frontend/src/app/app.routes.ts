import { Routes } from '@angular/router';
import { Login } from './components/login/login';
import { Dashboard } from './components/dashboard/dashboard';
import { DashboardHome } from './components/dashboard-home/dashboard-home';
import { TesourariaCaixa } from './components/tesouraria-caixa/tesouraria-caixa';
import { TesourariaRegularidade } from './components/tesouraria-regularidade/tesouraria-regularidade';
import { SecretariaObreiros } from './components/secretaria-obreiros/secretaria-obreiros';
import { SecretariaSessoes } from './components/secretaria-sessoes/secretaria-sessoes';
import { ChancelariaEfemerides } from './components/chancelaria-efemerides/chancelaria-efemerides';
import { ChancelariaCertificado } from './components/chancelaria-certificado/chancelaria-certificado';
import { HarmoniaPlayer } from './components/harmonia-player/harmonia-player';
import { CarteirinhaDigital } from './components/carteirinha-digital/carteirinha-digital';
import { CalendarioGrid } from './components/calendario-grid/calendario-grid';
import { TrilhaGraus } from './components/trilha-graus/trilha-graus';
import { MinhaLoja } from './components/minha-loja/minha-loja';
import { SecretariaBalaustres } from './components/secretaria-balaustres/secretaria-balaustres';
import { TesourariaObrigacoes } from './components/tesouraria-obrigacoes/tesouraria-obrigacoes';
import { CargosGestao } from './components/cargos-gestao/cargos-gestao';
import { BibliotecaAcervo } from './components/biblioteca-acervo/biblioteca-acervo';
import { SistemaConfig } from './components/sistema-config/sistema-config';
import { SecretariaTrabalhosPublicacoes } from './components/secretaria-trabalhos-publicacoes/secretaria-trabalhos-publicacoes';
import { SecretariaVotacao } from './components/secretaria-votacao/secretaria-votacao';
import { SecretariaNominata } from './components/secretaria-nominata/secretaria-nominata';
import { SecretariaConvites } from './components/secretaria-convites/secretaria-convites';
import { SecretariaAcessos } from './components/secretaria-acessos/secretaria-acessos';
import { SecretariaConteudoPublico } from './components/secretaria-conteudo-publico/secretaria-conteudo-publico';
import { SecretariaRelatorioAnual } from './components/secretaria-relatorio-anual/secretaria-relatorio-anual';
import { MeuCadastro } from './components/meu-cadastro/meu-cadastro';
import { SecretariaConvitesExternos } from './components/secretaria-convites-externos/secretaria-convites-externos';
import { SecretariaDashboard } from './components/secretaria-dashboard/secretaria-dashboard';
import { TesourariaComprovantes } from './components/tesouraria-comprovantes/tesouraria-comprovantes';
import { TesourariaSessoes } from './components/tesouraria-sessoes/tesouraria-sessoes';
import { TesourariaFechamento } from './components/tesouraria-fechamento/tesouraria-fechamento';
import { TesourariaRelatorioGestao } from './components/tesouraria-relatorio-gestao/tesouraria-relatorio-gestao';
import { ChancelariaSessao } from './components/chancelaria-sessao/chancelaria-sessao';
import { VeneravelDashboard } from './components/veneravel-dashboard/veneravel-dashboard';
import { HospitaleiroDashboard } from './components/hospitaleiro-dashboard/hospitaleiro-dashboard';
import { PrimeiroVigilanteDashboard } from './components/primeiro-vigilante-dashboard/primeiro-vigilante-dashboard';
import { SegundoVigilanteDashboard } from './components/segundo-vigilante-dashboard/segundo-vigilante-dashboard';
import { MestreBanquetesDashboard } from './components/mestre-banquetes-dashboard/mestre-banquetes-dashboard';
import { OradorDashboard } from './components/orador-dashboard/orador-dashboard';
import { authGuard, guestGuard } from './auth.guard';

export const routes: Routes = [
  { path: 'login', component: Login, canActivate: [guestGuard] },
  {
    path: 'dashboard',
    component: Dashboard,
    canActivate: [authGuard],
    children: [
      { path: '', component: DashboardHome },
      { path: 'tesouraria/caixa', component: TesourariaCaixa },
      { path: 'tesouraria/regularidade', component: TesourariaRegularidade },
      { path: 'tesouraria/comprovantes', component: TesourariaComprovantes },
      { path: 'tesouraria/sessoes', component: TesourariaSessoes },
      { path: 'tesouraria/fechamento', component: TesourariaFechamento },
      { path: 'tesouraria/relatorio-gestao', component: TesourariaRelatorioGestao },
      { path: 'secretaria/obreiros', component: SecretariaObreiros },
      { path: 'secretaria', component: SecretariaDashboard },
      { path: 'secretaria/sessoes', component: SecretariaSessoes },
      { path: 'secretaria/trabalhos-publicacoes', component: SecretariaTrabalhosPublicacoes },
      { path: 'secretaria/votacao', component: SecretariaVotacao },
      { path: 'secretaria/nominata', component: SecretariaNominata },
      { path: 'secretaria/convites', component: SecretariaConvites },
      { path: 'secretaria/convites-externos', component: SecretariaConvitesExternos },
      { path: 'secretaria/acessos', component: SecretariaAcessos },
      { path: 'secretaria/conteudo-publico', component: SecretariaConteudoPublico },
      { path: 'secretaria/relatorio-anual', component: SecretariaRelatorioAnual },
      { path: 'secretaria/relatorio-gestao', component: SecretariaRelatorioAnual },
      { path: 'chancelaria/efemerides', component: ChancelariaEfemerides },
      { path: 'chancelaria/sessao', component: ChancelariaSessao },
      { path: 'chancelaria/certificado', component: ChancelariaCertificado },
      { path: 'veneravel', component: VeneravelDashboard },
      { path: 'hospitaleiro', component: HospitaleiroDashboard },
      { path: 'primeiro-vigilante', component: PrimeiroVigilanteDashboard },
      { path: 'segundo-vigilante', component: SegundoVigilanteDashboard },
      { path: 'mestre-banquetes', component: MestreBanquetesDashboard },
      { path: 'orador', component: OradorDashboard },
      { path: 'harmonia/player', component: HarmoniaPlayer },
      { path: 'me/carteirinha', component: CarteirinhaDigital },
      { path: 'me/cadastro', component: MeuCadastro },
      { path: 'calendario', component: CalendarioGrid },
      { path: 'trilha', component: TrilhaGraus },
      { path: 'loja', component: MinhaLoja },
      { path: 'loja/irmaos', component: MinhaLoja, data: { lojaTab: 'irmaos' } },
      { path: 'secretaria/balaustres', component: SecretariaBalaustres },
      { path: 'tesouraria/obrigacoes', component: TesourariaObrigacoes },
      { path: 'cargos', component: CargosGestao },
      { path: 'biblioteca/acervo', component: BibliotecaAcervo },
      { path: 'biblioteca/emprestimos', component: BibliotecaAcervo, data: { bibliotecaTab: 'meus' } },
      { path: 'biblioteca/gestao', component: BibliotecaAcervo, data: { bibliotecaTab: 'gestao' } },
      { path: 'biblioteca/classificacao', component: BibliotecaAcervo, data: { bibliotecaTab: 'classificacao' } },
      { path: 'sistema', component: SistemaConfig }
    ]
  },
  { path: '', redirectTo: 'login', pathMatch: 'full' }
];
