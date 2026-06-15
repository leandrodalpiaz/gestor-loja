import { Component, OnInit, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../../environments/environment';
import { SupabaseService } from '../../services/supabase.service';

@Component({
  selector: 'app-secretaria-acessos',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './secretaria-acessos.html',
  styleUrl: './secretaria-acessos.css'
})
export class SecretariaAcessos implements OnInit {
  private http = inject(HttpClient);
  protected supabaseService = inject(SupabaseService);

  protected loading = signal(true);
  protected processando = signal<string | null>(null);
  protected errorMsg = signal<string | null>(null);
  protected successMsg = signal<string | null>(null);
  protected itens = signal<any[]>([]);

  ngOnInit(): void {
    this.carregar();
  }

  protected carregar(): void {
    this.loading.set(true);
    this.errorMsg.set(null);

    this.http.get<any>(`${environment.apiUrl}/api/admin/acessos`, {
      headers: this.supabaseService.getAuthHeaders()
    }).subscribe({
      next: (res) => {
        this.loading.set(false);
        if (!res?.ok) {
          this.errorMsg.set(res?.erro || 'Falha ao carregar acessos.');
          return;
        }
        this.itens.set(res.itens || []);
      },
      error: (err) => {
        this.loading.set(false);
        this.errorMsg.set(err.error?.erro || 'Erro ao consultar acessos.');
      }
    });
  }

  protected atualizar(itemId: string, status: 'ativo' | 'inativo'): void {
    this.processando.set(itemId);
    this.errorMsg.set(null);
    this.successMsg.set(null);

    this.http.post<any>(`${environment.apiUrl}/api/admin/acessos/atualizar`, {
      id: itemId,
      status
    }, {
      headers: this.supabaseService.getAuthHeaders()
    }).subscribe({
      next: (res) => {
        this.processando.set(null);
        if (!res?.ok) {
          this.errorMsg.set(res?.erro || 'Não foi possível atualizar o acesso.');
          return;
        }
        this.successMsg.set('Status de acesso atualizado com sucesso.');
        this.carregar();
      },
      error: (err) => {
        this.processando.set(null);
        this.errorMsg.set(err.error?.erro || 'Erro ao atualizar acesso.');
      }
    });
  }
}
