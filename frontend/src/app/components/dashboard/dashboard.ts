import { Component, inject, OnInit, effect } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router, RouterLink, RouterOutlet, RouterLinkActive } from '@angular/router';
import { SupabaseService } from '../../services/supabase.service';

@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [CommonModule, RouterLink, RouterOutlet, RouterLinkActive],
  templateUrl: './dashboard.html',
  styleUrl: './dashboard.css'
})
export class Dashboard implements OnInit {
  protected supabaseService = inject(SupabaseService);
  private router = inject(Router);

  // Estado da barra lateral móvel
  protected mobileSidebarOpen = false;

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

  protected logout(): void {
    this.supabaseService.logout().subscribe({
      next: () => this.router.navigate(['/login']),
      error: (err) => console.error('Erro ao efetuar logout:', err)
    });
  }

  /**
   * Verifica se o perfil do obreiro possui algum dos cargos permitidos.
   */
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
}
