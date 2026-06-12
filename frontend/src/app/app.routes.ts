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
      { path: 'secretaria/obreiros', component: SecretariaObreiros },
      { path: 'secretaria/sessoes', component: SecretariaSessoes },
      { path: 'chancelaria/efemerides', component: ChancelariaEfemerides },
      { path: 'chancelaria/certificado', component: ChancelariaCertificado },
      { path: 'harmonia/player', component: HarmoniaPlayer }
    ]
  },
  { path: '', redirectTo: 'login', pathMatch: 'full' }
];
