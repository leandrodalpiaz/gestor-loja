import { Component, computed, inject, OnInit, effect } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router, RouterLink, RouterOutlet, RouterLinkActive } from '@angular/router';
import { SupabaseService } from '../../services/supabase.service';
import { environment } from '../../../environments/environment';
import { DASHBOARD_NAVIGATION, DashboardNavigationItem } from '../../navigation/dashboard-navigation';

@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [CommonModule, RouterLink, RouterOutlet, RouterLinkActive],
  templateUrl: './dashboard.html',
  styleUrl: './dashboard.css'
})
export class Dashboard implements OnInit {
  protected supabaseService = inject(SupabaseService);
  protected router = inject(Router);

  // Estado da barra lateral móvel
  protected mobileSidebarOpen = false;
  protected expandedSections = new Set<string>(['minha-loja']);
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
