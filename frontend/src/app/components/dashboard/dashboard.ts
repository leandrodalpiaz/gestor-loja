import { ChangeDetectionStrategy, ChangeDetectorRef, Component, computed, inject, OnInit, effect, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router, RouterLink, RouterOutlet, RouterLinkActive, NavigationEnd } from '@angular/router';
import { trigger, state, style, transition, animate } from '@angular/animations';
import { filter } from 'rxjs/operators';
import { SupabaseService } from '../../services/supabase.service';
import { environment } from '../../../environments/environment';
import { DASHBOARD_NAVIGATION, DashboardNavigationItem } from '../../navigation/dashboard-navigation';
import { BottomNav, BottomNavTab } from '../bottom-nav/bottom-nav';

@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [CommonModule, RouterLink, RouterOutlet, RouterLinkActive, BottomNav],
  templateUrl: './dashboard.html',
  styleUrl: './dashboard.css',
  changeDetection: ChangeDetectionStrategy.OnPush,
  animations: [
    trigger('expandCollapse', [
      state('collapsed', style({ height: '0px', opacity: 0, overflow: 'hidden', padding: '0px', visibility: 'hidden' })),
      state('expanded', style({ height: '*', opacity: 1, overflow: 'visible', visibility: 'visible' })),
      transition('collapsed <=> expanded', [
        animate('250ms cubic-bezier(0.4, 0, 0.2, 1)')
      ])
    ])
  ]
})
export class Dashboard implements OnInit {
  protected supabaseService = inject(SupabaseService);
  protected router = inject(Router);
  private cdr = inject(ChangeDetectorRef);

  // Estado da barra lateral móvel
  protected mobileSidebarOpen = false;
  protected expandedSections = new Set<string>(['minha-loja']);

  // Detecta se está rodando como PWA instalado (standalone), não como site responsivo
  protected readonly isPwaStandalone = signal(
    typeof window !== 'undefined' && window.matchMedia('(display-mode: standalone)').matches
  );

  // Bottom Navigation Bar (PWA nativo)
  protected bottomNavTabs: BottomNavTab[] = [
    { id: 'inicio',  label: 'Início',      path: '/dashboard',                    icon: 'M3 12l9-9 9 9M5 10v10h14V10M9 20v-6h6v6', exact: true },
    { id: 'loja',    label: 'Minha Loja',  path: '/dashboard/loja',              icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z' },
    { id: 'tesouraria', label: 'Tesouraria', path: '/dashboard/tesouraria/caixa', icon: 'M3 6v12a2 2 0 002 2h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2zm2 0h14v2H5V6zm0 4h14v2H5v-2zm0 4h10v2H5v-2z' },
    { id: 'secretaria', label: 'Secretaria', path: '/dashboard/secretaria',      icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
    { id: 'more',     label: 'Mais',        path: '',                             icon: 'M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z' },
  ];
  protected navigationSections = computed(() => {
    const profile = this.supabaseService.profile();
    if (!profile) return [];

    const permissions = new Set<string>(profile.permissions ?? []);
    const isSystemAdmin = profile.is_system_admin === true || permissions.has('*');

    return DASHBOARD_NAVIGATION
      .map(section => ({
        ...section,
        items: section.items.filter(item =>
          item.permission === '*'
            ? isSystemAdmin
            : permissions.has('*') || permissions.has(item.permission)
        ),
      }))
      .filter(section => section.items.length > 0);
  });

  constructor() {
    // Observa mudanças no estado da sessão de forma reativa e redireciona se deslogar
    effect(() => {
      if (!this.supabaseService.loading() && !this.supabaseService.session()) {
        this.router.navigate(['/login']);
      }
    });

    // Fecha a sidebar mobile ao navegar (bottom nav tabs)
    this.router.events
      .pipe(filter(e => e instanceof NavigationEnd))
      .subscribe(() => {
        // Mutação fora de um evento do próprio template: com OnPush isso não
        // marcaria a view como suja sozinho, então força o recheck aqui.
        this.mobileSidebarOpen = false;
        this.cdr.markForCheck();
      });
  }

  ngOnInit(): void {
    if (!this.supabaseService.loading() && !this.supabaseService.session()) {
      this.router.navigate(['/login']);
    }
  }

  protected toggleMobileSidebar(): void {
    this.mobileSidebarOpen = !this.mobileSidebarOpen;
  }

  protected toggleSection(sectionId: string): void {
    const updated = new Set(this.expandedSections);
    updated.has(sectionId) ? updated.delete(sectionId) : updated.add(sectionId);
    this.expandedSections = updated;
  }

  protected isSectionExpanded(sectionId: string): boolean {
    return this.expandedSections.has(sectionId);
  }

  protected navigate(item: DashboardNavigationItem): void {
    this.mobileSidebarOpen = false;
    if (item.target === 'integration') {
      void this.openIntegration(item.path);
      return;
    }
    void this.router.navigateByUrl(item.path);
  }

  protected logout(): void {
    this.supabaseService.logout().subscribe({
      next: () => this.router.navigate(['/login']),
      error: (err) => console.error('Erro ao efetuar logout:', err)
    });
  }

  protected async openIntegration(path: string): Promise<void> {
    const token = await this.supabaseService.getValidToken();
    if (!token) {
      window.location.href = `${environment.apiUrl}${path.startsWith('/') ? path : `/${path}`}`;
      return;
    }

    const normalizedPath = path.startsWith('/') ? path : `/${path}`;
    const bridgeUrl = `${environment.apiUrl}/auth/bridge?token=${encodeURIComponent(token)}&redirect=${encodeURIComponent(normalizedPath)}`;
    window.location.href = bridgeUrl;
  }

}
