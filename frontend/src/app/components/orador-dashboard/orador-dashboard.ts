import { Component, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { SupabaseService } from '../../services/supabase.service';
import { environment } from '../../../environments/environment';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-orador-dashboard',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './orador-dashboard.html',
  styleUrl: './orador-dashboard.css'
})
export class OradorDashboard implements OnInit {
  private http = inject(HttpClient);
  protected supabaseService = inject(SupabaseService);

  protected loading = signal(true);
  protected errorMsg = signal<string | null>(null);
  protected successMsg = signal<string | null>(null);

  protected dataOrador = signal<any>(null);
  protected selectedSessaoId = signal<number | null>(null);

  ngOnInit(): void {
    this.carregarDados();
  }

  protected carregarDados(): void {
    this.loading.set(true);
    this.errorMsg.set(null);
    const headers = this.supabaseService.getAuthHeaders();

    const sessaoId = this.selectedSessaoId();
    const url = `${environment.apiUrl}/api/miniapp/orador/dashboard${sessaoId ? `?sessao_id=${encodeURIComponent(String(sessaoId))}` : ''}`;

    this.http.get<any>(url, { headers }).subscribe({
      next: (res) => {
        this.loading.set(false);
        if (res && res.ok) {
          const payload = res.dados ?? res;
          this.dataOrador.set(payload);
          const sessaoFocoId = Number(payload?.sessao_foco?.id || 0);
          if (sessaoFocoId > 0 && !this.selectedSessaoId()) {
            this.selectedSessaoId.set(sessaoFocoId);
          }
        } else {
          this.errorMsg.set('Erro ao carregar o painel do Orador.');
        }
      },
      error: (err) => {
        this.loading.set(false);
        this.errorMsg.set(err.error?.erro || 'Não foi possível carregar o painel do Orador.');
      }
    });
  }

  protected onSessaoChange(event: Event): void {
    const target = event.target as HTMLSelectElement | null;
    const sessaoId = target ? Number(target.value) : 0;
    this.selectedSessaoId.set(sessaoId > 0 ? sessaoId : null);
    this.carregarDados();
  }
}
