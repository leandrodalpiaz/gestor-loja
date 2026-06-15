import { CommonModule } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { Component, inject, OnInit, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { environment } from '../../../environments/environment';
import { SupabaseService } from '../../services/supabase.service';

@Component({
  selector: 'app-secretaria-dashboard',
  standalone: true,
  imports: [CommonModule, RouterLink],
  templateUrl: './secretaria-dashboard.html'
})
export class SecretariaDashboard implements OnInit {
  private http = inject(HttpClient);
  private auth = inject(SupabaseService);
  protected data = signal<any>(null);
  protected loading = signal(true);
  protected error = signal<string | null>(null);

  ngOnInit(): void {
    this.http.get<any>(`${environment.apiUrl}/api/secretaria/dashboard`, { headers: this.auth.getAuthHeaders() }).subscribe({
      next: res => { this.data.set(res); this.loading.set(false); },
      error: err => { this.error.set(err.error?.erro || 'Falha ao carregar a Secretaria.'); this.loading.set(false); }
    });
  }
}
