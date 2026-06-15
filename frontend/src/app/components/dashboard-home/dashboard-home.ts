import { Component, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { HttpClient } from '@angular/common/http';
import { SupabaseService } from '../../services/supabase.service';
import { environment } from '../../../environments/environment';
import { DASHBOARD_NAVIGATION } from '../../navigation/dashboard-navigation';

@Component({
  selector: 'app-dashboard-home',
  standalone: true,
  imports: [CommonModule, RouterLink],
  templateUrl: './dashboard-home.html',
  styleUrl: './dashboard-home.css'
})
export class DashboardHome implements OnInit {
  protected supabaseService = inject(SupabaseService);
  private http = inject(HttpClient);
  protected apiUrl = environment.apiUrl;

  protected currentDate = '';
  protected dashboardData = signal<any>(null);
  protected loading = signal(true);
  protected errorMsg = signal<string | null>(null);

  ngOnInit(): void {
    const today = new Date();
    this.currentDate = today.toLocaleDateString('pt-BR', {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });
    this.loadDashboard();
  }

  protected loadDashboard(): void {
    this.loading.set(true);
    this.errorMsg.set(null);

    const profile = this.supabaseService.profile();
    const isSystemAdmin = profile?.is_system_admin === true
      || profile?.cargo_principal === 'admin'
      || profile?.cargos?.includes?.('admin');
    const url = isSystemAdmin
      ? `${environment.apiUrl}/api/miniapp/admin/dashboard`
      : `${environment.apiUrl}/api/obreiro/dashboard`;

    this.http.get<any>(url, {
      headers: this.supabaseService.getAuthHeaders()
    }).subscribe({
      next: (res) => {
        this.loading.set(false);
        if (res?.ok) {
          this.dashboardData.set(this.normalizeDashboardResponse(res, isSystemAdmin));
          return;
        }

        this.errorMsg.set('Não foi possível carregar o painel inicial.');
      },
      error: (err) => {
        this.loading.set(false);
        if (isSystemAdmin) {
          this.dashboardData.set(this.buildEmptyDashboardState());
          return;
        }
        this.errorMsg.set(err.error?.erro || 'Falha de conexão.');
      }
    });
  }

  protected confirmarPresenca(sessaoId: number, acao: 'confirmar' | 'cancelar'): void {
    this.loading.set(true);

    this.http.post<any>(`${environment.apiUrl}/api/obreiro/sessoes/confirmar`, {
      sessao_id: sessaoId,
      acao
    }, {
      headers: this.supabaseService.getAuthHeaders()
    }).subscribe({
      next: (res) => {
        if (res?.ok) {
          this.loadDashboard();
          return;
        }

        this.loading.set(false);
        alert(res?.erro || 'Não foi possível registrar a confirmação.');
      },
      error: (err) => {
        this.loading.set(false);
        alert(err.error?.erro || 'Erro ao registrar confirmação.');
      }
    });
  }

  protected hasRole(roles: string[]): boolean {
    const userProfile = this.supabaseService.profile();
    if (!userProfile) return false;

    if (userProfile.cargo_principal === 'admin' || userProfile.cargos.includes('admin')) {
      return true;
    }

    return roles.some(role =>
      userProfile.cargo_principal === role || userProfile.cargos.includes(role)
    );
  }

  protected getCardsPorCategoria(): { label: string; cards: any[] }[] {
    const data = this.dashboardData();
    if (!data?.efemerides_cards) return [];

    const groups: Record<string, any[]> = {};

    for (const card of data.efemerides_cards) {
      const cat = (card.categoria || 'Geral').trim();
      const catNorm = cat.toLowerCase();
      let label = cat;

      if (catNorm.includes('história') || catNorm.includes('historia')) {
        label = 'Nossa História';
      } else if (
        catNorm.includes('aniversário')
        || catNorm.includes('aniversario')
        || ['esposa', 'cunhada', 'filho', 'filha', 'sobrinho', 'sobrinha', 'membro', 'irmao', 'irmão', 'familiar'].includes(catNorm)
      ) {
        label = 'Aniversariantes do Dia';
      } else {
        label = cat.charAt(0).toUpperCase() + cat.slice(1);
      }

      if (!groups[label]) {
        groups[label] = [];
      }

      groups[label].push(card);
    }

    return Object.keys(groups).map(key => ({
      label: key,
      cards: groups[key]
    }));
  }

  protected getAtalhosOperacionais(): any[] {
    const userProfile = this.supabaseService.profile();
    if (!userProfile) return [];

    const permissions = new Set<string>(userProfile.permissions ?? []);
    const isSystemAdmin = userProfile.is_system_admin === true || permissions.has('*');
    const shortcuts: any[] = [];
    const seenPaths = new Set<string>();

    for (const section of DASHBOARD_NAVIGATION) {
      if (section.id === 'minha-loja' || section.id === 'obreiro' || section.id === 'sistema') {
        continue;
      }

      for (const item of section.items) {
        if (item.target !== 'angular') {
          continue;
        }

        if (seenPaths.has(item.path)) {
          continue;
        }

        const hasPerm = item.permission === '*'
          ? isSystemAdmin
          : permissions.has('*') || permissions.has(item.permission);

        if (!hasPerm) {
          continue;
        }

        shortcuts.push(item);
        seenPaths.add(item.path);
      }
    }

    return shortcuts.slice(0, 6);
  }

  protected getShortcutIcon(label: string): string {
    const l = label.toLowerCase();

    if (l.includes('secretaria')) {
      return 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z';
    }
    if (l.includes('tesou') || l.includes('caixa') || l.includes('obriga')) {
      return 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z';
    }
    if (l.includes('chanc') || l.includes('sess') || l.includes('certi') || l.includes('visit')) {
      return 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z';
    }
    if (l.includes('hosp') || l.includes('assist')) {
      return 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z';
    }
    if (l.includes('vigil')) {
      return 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z';
    }
    if (l.includes('vener')) {
      return 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.907c.961 0 1.36 1.246.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.572-.387-1.81.588-1.81h4.906a1 1 0 00.95-.69l1.519-4.674z';
    }
    if (l.includes('orador') || l.includes('banquete') || l.includes('harmonia')) {
      return 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1';
    }

    return 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1';
  }

  private normalizeDashboardResponse(res: any, isSystemAdmin: boolean): any {
    if (!isSystemAdmin) {
      return res;
    }

    const dados = res?.dados ?? {};
    return {
      ok: true,
      configuracao_loja: {
        nome_loja: dados?.configuracao?.nome_loja || '',
        numero_loja: dados?.configuracao?.numero_loja || '',
        cidade: dados?.configuracao?.cidade || '',
        uf: dados?.configuracao?.uf || '',
        rito: dados?.configuracao?.rito || '',
        oriente: dados?.configuracao?.oriente || '',
        dia_semana_reuniao: 'A definir'
      },
      sessoes: [],
      recados: [],
      palavra_irmao: '',
      efemerides_cards: []
    };
  }

  private buildEmptyDashboardState(): any {
    return {
      ok: true,
      configuracao_loja: {
        nome_loja: '',
        numero_loja: '',
        dia_semana_reuniao: 'A definir'
      },
      sessoes: [],
      recados: [],
      palavra_irmao: '',
      efemerides_cards: []
    };
  }
}
