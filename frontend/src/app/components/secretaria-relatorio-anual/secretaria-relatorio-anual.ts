import { Component, OnInit, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute } from '@angular/router';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../../environments/environment';
import { SupabaseService } from '../../services/supabase.service';

@Component({
  selector: 'app-secretaria-relatorio-anual',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './secretaria-relatorio-anual.html',
  styleUrl: './secretaria-relatorio-anual.css'
})
export class SecretariaRelatorioAnual implements OnInit {
  private http = inject(HttpClient);
  private route = inject(ActivatedRoute);
  protected supabaseService = inject(SupabaseService);

  protected loading = signal(true);
  protected errorMsg = signal<string | null>(null);
  protected anosDisponiveis = signal<number[]>([]);
  protected anoSelecionado = signal(new Date().getFullYear());
  protected relatorio = signal<any | null>(null);
  protected modoGestao = signal(false);

  ngOnInit(): void {
    const url = this.route.snapshot.routeConfig?.path || '';
    this.modoGestao.set(url.includes('relatorio-gestao'));
    this.carregar();
  }

  protected carregar(): void {
    this.loading.set(true);
    this.errorMsg.set(null);

    this.http.get<any>(`${environment.apiUrl}/api/admin/secretaria/relatorio-anual?ano=${this.anoSelecionado()}`, {
      headers: this.supabaseService.getAuthHeaders()
    }).subscribe({
      next: (res) => {
        this.loading.set(false);
        if (!res?.ok) {
          this.errorMsg.set(res?.erro || 'Falha ao carregar relatório.');
          return;
        }
        this.relatorioset(res.relatorio || null);
        this.anosDisponiveis.set(res.anos_disponiveis || []);
      },
      error: (err) => {
        this.loading.set(false);
        this.errorMsg.set(err.error?.erro || 'Erro ao consultar relatório.');
      }
    });
  }

  private relatorioset(valor: any): void {
    this.relatorio.set(valor);
  }

  protected atualizarAno(valor: string | number): void {
    this.anoSelecionado.set(Number(valor));
    this.carregar();
  }

  protected formatCategoria(valor: string): string {
    return (valor || 'N/A').replaceAll('_', ' ');
  }
}
