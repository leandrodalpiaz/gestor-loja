import { Routes } from '@angular/router';
import { Login } from './components/login/login';
import { Dashboard } from './components/dashboard/dashboard';
import { DashboardHome } from './components/dashboard-home/dashboard-home';
import { TesourariaCaixa } from './components/tesouraria-caixa/tesouraria-caixa';
import { TesourariaRegularidade } from './components/tesouraria-regularidade/tesouraria-regularidade';
import { SecretariaObreiros } from './components/secretaria-obreiros/secretaria-obreiros';
import { SecretariaSessoes } from './components/secretaria-sessoes/secretaria-sessoes';
import { ChancelariaEfemerides } from './components/chancelaria-efemerides/chancelaria-efemerides';
import { HarmoniaPlayer } from './components/harmonia-player/harmonia-player';

export const routes: Routes = [
  { path: 'login', component: Login },
  {
    path: 'dashboard',
    component: Dashboard,
    children: [
      { path: '', component: DashboardHome },
      { path: 'tesouraria/caixa', component: TesourariaCaixa },
      { path: 'tesouraria/regularidade', component: TesourariaRegularidade },
      { path: 'secretaria/obreiros', component: SecretariaObreiros },
      { path: 'secretaria/sessoes', component: SecretariaSessoes },
      { path: 'chancelaria/efemerides', component: ChancelariaEfemerides },
      { path: 'harmonia/player', component: HarmoniaPlayer }
    ]
  },
  { path: '', redirectTo: 'login', pathMatch: 'full' }
];
