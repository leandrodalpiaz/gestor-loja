import { Component, inject, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { SupabaseService } from '../../services/supabase.service';

@Component({
  selector: 'app-dashboard-home',
  standalone: true,
  imports: [CommonModule, RouterLink],
  templateUrl: './dashboard-home.html',
  styleUrl: './dashboard-home.css'
})
export class DashboardHome implements OnInit {
  protected supabaseService = inject(SupabaseService);

  protected welcomeMessage = '';
  protected currentDate = '';

  ngOnInit(): void {
    const today = new Date();
    this.currentDate = today.toLocaleDateString('pt-BR', {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric'
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
}
