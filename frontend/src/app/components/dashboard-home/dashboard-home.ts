import { ChangeDetectionStrategy, Component, inject, OnInit, OnDestroy, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { HttpClient } from '@angular/common/http';
import { SupabaseService } from '../../services/supabase.service';
import { environment } from '../../../environments/environment';
import { DASHBOARD_NAVIGATION } from '../../navigation/dashboard-navigation';
import { trigger, transition, style, animate, query, stagger, keyframes, state, group } from '@angular/animations';

@Component({
  selector: 'app-dashboard-home',
  standalone: true,
  imports: [CommonModule, RouterLink],
  templateUrl: './dashboard-home.html',
  styleUrl: './dashboard-home.css',
  changeDetection: ChangeDetectionStrategy.OnPush,
  animations: [
    // Carousel slide transitions with direction awareness
    trigger('cardTransition', [
      transition(':enter', [
        style({ opacity: 0, transform: 'translateX(40px) scale(0.96) rotateY(4deg)' }),
        animate('450ms cubic-bezier(0.22, 1, 0.36, 1)',
          style({ opacity: 1, transform: 'translateX(0) scale(1) rotateY(0deg)' }))
      ]),
      transition(':leave', [
        animate('300ms cubic-bezier(0.4, 0, 0.2, 1)',
          style({ opacity: 0, transform: 'translateX(-20px) scale(0.97)' }))
      ])
    ]),
    // Staggered text reveal for content cards
    trigger('textReveal', [
      transition(':enter', [
        query('.reveal-item', [
          style({ opacity: 0, transform: 'translateY(12px)' }),
          stagger(120, [
            animate('400ms cubic-bezier(0.22, 1, 0.36, 1)',
              style({ opacity: 1, transform: 'translateY(0)' }))
          ])
        ], { optional: true })
      ])
    ]),
    // Gold line animation
    trigger('lineGrow', [
      transition(':enter', [
        style({ width: '0%' }),
        animate('600ms 200ms cubic-bezier(0.22, 1, 0.36, 1)',
          style({ width: '100%' }))
      ])
    ]),
    // Pulse glow for active dot
    trigger('dotPulse', [
      state('active', style({ transform: 'scale(1)', opacity: 1 })),
      state('inactive', style({ transform: 'scale(1)', opacity: 0.3 })),
      transition('inactive => active', [
        animate('300ms cubic-bezier(0.22, 1, 0.36, 1)')
      ])
    ])
  ]
})
export class DashboardHome implements OnInit, OnDestroy {
  protected supabaseService = inject(SupabaseService);
  private http = inject(HttpClient);
  protected apiUrl = environment.apiUrl;

  protected currentDate = '';
  protected dashboardData = signal<any>(null);
  protected loading = signal(true);
  protected errorMsg = signal<string | null>(null);

  // Calendar State
  protected calendarDays = signal<any[]>([]);
  protected currentMonthYearLabel = '';
  protected selectedDayEvents = signal<any | null>(null);

  // Highlights & Ephemerides unified carousel
  protected displayCards = signal<any[]>([]);
  protected activeCardIndex = signal(0);
  protected slideDirection = signal<'next' | 'prev'>('next');
  private cycleTimer: any;

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

  ngOnDestroy(): void {
    if (this.cycleTimer) {
      clearInterval(this.cycleTimer);
    }
  }

  protected loadDashboard(): void {
    this.loading.set(true);
    this.errorMsg.set(null);

    const url = `${environment.apiUrl}/api/obreiro/dashboard`;

    this.http.get<any>(url, {
      headers: this.supabaseService.getAuthHeaders(),
      withCredentials: true
    }).subscribe({
      next: (res) => {
        this.loading.set(false);
        if (res?.ok) {
          this.dashboardData.set(res);
          this.buildCalendar();
          this.setupDisplayCards(res);
          return;
        }

        this.errorMsg.set('Não foi possível carregar o painel inicial.');
      },
      error: (err) => {
        this.loading.set(false);
        this.errorMsg.set(err.error?.erro || 'Falha de conexão.');
      }
    });
  }

  protected buildCalendar(): void {
    const today = new Date();
    const year = today.getFullYear();
    const month = today.getMonth();

    const monthLabel = today.toLocaleDateString('pt-BR', { month: 'long' });
    this.currentMonthYearLabel = monthLabel.charAt(0).toUpperCase() + monthLabel.slice(1) + ' de ' + year;

    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);

    const startDayOfWeek = firstDay.getDay(); // 0: Sunday, 1: Monday, ...
    const totalDays = lastDay.getDate();
    const days: any[] = [];

    // Previous month padding
    for (let i = 0; i < startDayOfWeek; i++) {
      days.push({ day: null, isCurrentMonth: false });
    }

    const data = this.dashboardData();
    const sessoes = data?.sessoes || [];
    const efemerides = data?.efemerides_cards || [];

    for (let d = 1; d <= totalDays; d++) {
      const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;

      const daySessions = sessoes.filter((s: any) => {
        if (!s.data_hora_inicio) return false;
        return s.data_hora_inicio.startsWith(dateStr);
      });

      const isToday = d === today.getDate();
      const hasSession = daySessions.length > 0;
      const hasEfemeride = isToday && efemerides.length > 0;

      days.push({
        day: d,
        dateStr,
        isCurrentMonth: true,
        isToday,
        hasSession,
        hasEfemeride,
        sessions: daySessions,
        efemerides: isToday ? efemerides : []
      });
    }

    this.calendarDays.set(days);

    // Default selection
    const todayObj = days.find(day => day.isToday);
    if (todayObj && (todayObj.hasSession || todayObj.hasEfemeride)) {
      this.selectedDayEvents.set(todayObj);
    } else {
      const firstEventDay = days.find(day => day.isCurrentMonth && day.hasSession);
      if (firstEventDay) {
        this.selectedDayEvents.set(firstEventDay);
      } else if (todayObj) {
        this.selectedDayEvents.set(todayObj);
      }
    }
  }

  protected selectDay(dayObj: any): void {
    if (!dayObj || !dayObj.day) return;
    this.selectedDayEvents.set(dayObj);
  }

  protected setupDisplayCards(data: any): void {
    const cards: any[] = [];

    // Add Efemérides cards (com imagem gerada)
    if (data?.efemerides_cards && data.efemerides_cards.length > 0) {
      cards.push(...data.efemerides_cards);
    }

    // Fallback: se não há cards com imagem, criar um card textual com a Palavra do Irmão
    // para que a secção "Destaques & Efemérides" nunca fique vazia.
    if (cards.length === 0) {
      const palavra = (data?.palavra_irmao || '').trim();
      if (palavra) {
        cards.push({
          categoria: 'Mensagem do Dia',
          titulo: 'Palavra do Irmão',
          mensagem: palavra,
          image_url: null,
        });
      } else {
        // Fallback último: card genérico quando não há nada
        cards.push({
          categoria: 'Loja',
          titulo: 'Nenhuma efeméride hoje',
          mensagem: 'Não há registos de efemérides para o dia de hoje. Aceda à Chancelaria para cadastrar aniversários, iniciações e outras datas especiais.',
          image_url: null,
        });
      }
    }

    this.displayCards.set(cards);
    this.activeCardIndex.set(0);
    this.startCardCycle(cards.length);
  }

  private startCardCycle(totalCards: number): void {
    if (this.cycleTimer) {
      clearInterval(this.cycleTimer);
    }
    if (totalCards <= 1) return;

    this.cycleTimer = setInterval(() => {
      this.slideDirection.set('next');
      this.activeCardIndex.update(idx => (idx + 1) % totalCards);
    }, 7000); // Auto rotate every 7 seconds
  }

  protected nextCard(): void {
    const total = this.displayCards().length;
    if (total <= 1) return;
    this.slideDirection.set('next');
    this.activeCardIndex.update(idx => (idx + 1) % total);
    this.startCardCycle(total);
  }

  protected prevCard(): void {
    const total = this.displayCards().length;
    if (total <= 1) return;
    this.slideDirection.set('prev');
    this.activeCardIndex.update(idx => (idx === 0 ? total - 1 : idx - 1));
    this.startCardCycle(total);
  }

  protected selectCard(index: number): void {
    const total = this.displayCards().length;
    const current = this.activeCardIndex();
    this.slideDirection.set(index > current ? 'next' : 'prev');
    this.activeCardIndex.set(index);
    this.startCardCycle(total);
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
}
