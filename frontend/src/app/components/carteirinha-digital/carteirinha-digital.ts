import { Component, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { SupabaseService } from '../../services/supabase.service';
import { environment } from '../../../environments/environment';

export interface CarteirinhaInfo {
  nome: string;
  nome_historico: string;
  cim: string;
  grau: string;
  cargo: string;
  data_iniciacao: string | null;
  data_elevacao: string | null;
  data_exaltacao: string | null;
  loja_nome: string;
  loja_numero: string;
  loja_sigla: string;
  situacao: string;
}

@Component({
  selector: 'app-carteirinha-digital',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './carteirinha-digital.html',
  styleUrl: './carteirinha-digital.css'
})
export class CarteirinhaDigital implements OnInit {
  private http = inject(HttpClient);
  protected supabaseService = inject(SupabaseService);

  protected loading = signal(true);
  protected errorMsg = signal<string | null>(null);
  protected carteirinha = signal<CarteirinhaInfo | null>(null);
  protected virada = signal(false); // Alterna entre frente e verso da carteirinha

  ngOnInit(): void {
    this.carregarCarteirinha();
  }

  protected carregarCarteirinha(): void {
    this.loading.set(true);
    this.errorMsg.set(null);
    const headers = this.supabaseService.getAuthHeaders();

    this.http.get<{ ok: boolean; carteirinha?: CarteirinhaInfo; erro?: string }>(
      `${environment.apiUrl}/api/obreiro/carteirinha`,
      { headers }
    ).subscribe({
      next: (res) => {
        this.loading.set(false);
        if (res && res.ok && res.carteirinha) {
          this.carteirinha.set(res.carteirinha);
        } else {
          this.errorMsg.set(res.erro || 'Erro ao carregar dados da carteirinha.');
        }
      },
      error: (err) => {
        this.loading.set(false);
        console.error('[Carteirinha] Erro na requisição:', err);
        this.errorMsg.set(err.error?.erro || 'Erro de conexão ao servidor.');
      }
    });
  }

  protected toggleVerso(): void {
    this.virada.set(!this.virada());
  }

  protected imprimir(): void {
    window.print();
  }

  protected getQrCodeUrl(): string {
    const info = this.carteirinha();
    if (!info) return '';
    const text = `Nome: ${info.nome_historico || info.nome} | CIM: ${info.cim} | Grau: ${info.grau} | Loja: ${info.loja_sigla} ${info.loja_nome} nº ${info.loja_numero}`;
    return `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(text)}`;
  }
}
