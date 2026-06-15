import { Component, OnInit, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../../environments/environment';
import { SupabaseService } from '../../services/supabase.service';

@Component({
  selector: 'app-secretaria-convites',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './secretaria-convites.html',
  styleUrl: './secretaria-convites.css'
})
export class SecretariaConvites implements OnInit {
  private http = inject(HttpClient);
  protected supabaseService = inject(SupabaseService);

  protected loading = signal(true);
  protected gerando = signal<string | null>(null);
  protected errorMsg = signal<string | null>(null);
  protected successMsg = signal<string | null>(null);
  protected pendentes = signal<any[]>([]);
  protected convites = signal<any[]>([]);
  protected conviteGerado = signal<any | null>(null);

  ngOnInit(): void {
    this.carregar();
  }

  protected carregar(): void {
    this.loading.set(true);
    this.errorMsg.set(null);

    this.http.get<any>(`${environment.apiUrl}/api/admin/convites`, {
      headers: this.supabaseService.getAuthHeaders()
    }).subscribe({
      next: (res) => {
        this.loading.set(false);
        if (!res?.ok) {
          this.errorMsg.set(res?.erro || 'Falha ao carregar convites.');
          return;
        }
        this.pendentes.set(res.pendentes || []);
        this.convites.set(res.convites || []);
      },
      error: (err) => {
        this.loading.set(false);
        this.errorMsg.set(err.error?.erro || 'Erro ao consultar convites.');
      }
    });
  }

  protected gerarConvite(obreiroId: string): void {
    this.gerando.set(obreiroId);
    this.errorMsg.set(null);
    this.successMsg.set(null);

    this.http.post<any>(`${environment.apiUrl}/api/admin/convites/gerar`, { obreiro_id: obreiroId }, {
      headers: this.supabaseService.getAuthHeaders()
    }).subscribe({
      next: (res) => {
        this.gerando.set(null);
        if (!res?.ok) {
          this.errorMsg.set(res?.erro || 'Não foi possível gerar o convite.');
          return;
        }
        this.conviteGerado.set(res);
        this.successMsg.set('Convite gerado com sucesso.');
        this.carregar();
      },
      error: (err) => {
        this.gerando.set(null);
        this.errorMsg.set(err.error?.erro || 'Erro ao gerar convite.');
      }
    });
  }

  protected async copiar(valor: string): Promise<void> {
    if (!valor) return;
    await navigator.clipboard.writeText(valor);
  }
}
