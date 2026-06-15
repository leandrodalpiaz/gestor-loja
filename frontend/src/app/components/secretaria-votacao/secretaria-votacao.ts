import { Component, OnInit, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../../environments/environment';
import { SupabaseService } from '../../services/supabase.service';

interface VotacaoItem {
  id: number;
  sessao_id: number;
  numero_balaustre: string;
  sessao_titulo: string;
  tipo_sessao: string;
  grau_sessao: string;
  data_hora_inicio: string;
  elegivel?: boolean;
}

@Component({
  selector: 'app-secretaria-votacao',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './secretaria-votacao.html',
  styleUrl: './secretaria-votacao.css'
})
export class SecretariaVotacao implements OnInit {
  private http = inject(HttpClient);
  protected supabaseService = inject(SupabaseService);
  protected loading = signal(true);
  protected errorMsg = signal<string | null>(null);
  protected successMsg = signal<string | null>(null);
  protected votacoes = signal<VotacaoItem[]>([]);

  protected votos = signal<Record<number, string>>({});
  protected justificativas = signal<Record<number, string>>({});
  protected detalhe = signal<any | null>(null);
  protected detalheLoading = signal(false);

  ngOnInit(): void {
    this.carregar();
  }

  protected carregar(): void {
    this.loading.set(true);
    this.errorMsg.set(null);

    this.http.get<any>(`${environment.apiUrl}/api/secretaria/votacao`, {
      headers: this.supabaseService.getAuthHeaders()
    }).subscribe({
      next: (res) => {
        this.loading.set(false);
        if (!res?.ok) {
          this.errorMsg.set(res?.erro || 'Falha ao carregar votacoes abertas.');
          return;
        }

        const elegibilidade = res.elegibilidade_voto || {};
        const items = (res.votacoes_abertas || []).map((item: any) => ({
          ...item,
          elegivel: !!elegibilidade[item.id]
        }));

        this.votacoes.set(items);
        const votosIniciais: Record<number, string> = {};
        const justificativasIniciais: Record<number, string> = {};
        for (const item of items) {
          votosIniciais[item.id] = 'aprovar';
          justificativasIniciais[item.id] = '';
        }
        this.votos.set(votosIniciais);
        this.justificativas.set(justificativasIniciais);
      },
      error: (err) => {
        this.loading.set(false);
        this.errorMsg.set(err.error?.erro || 'Erro ao consultar votacoes abertas.');
      }
    });
  }

  protected atualizarVoto(id: number, valor: string): void {
    this.votos.update((state) => ({ ...state, [id]: valor }));
  }

  protected atualizarJustificativa(id: number, valor: string): void {
    this.justificativas.update((state) => ({ ...state, [id]: valor }));
  }

  protected registrarVoto(item: VotacaoItem): void {
    const voto = this.votos()[item.id] || 'aprovar';
    const justificativa = (this.justificativas()[item.id] || '').trim();

    this.loading.set(true);
    this.errorMsg.set(null);
    this.successMsg.set(null);

    this.http.post<any>(
      `${environment.apiUrl}/api/secretaria/sessoes/${item.sessao_id}/balaustre/votar`,
      { voto, justificativa },
      { headers: this.supabaseService.getAuthHeaders() }
    ).subscribe({
      next: (res) => {
        this.loading.set(false);
        if (res?.ok) {
          this.successMsg.set('Voto registrado com sucesso.');
          this.carregar();
          return;
        }
        this.errorMsg.set(res?.erro || 'Não foi possível registrar o voto.');
      },
      error: (err) => {
        this.loading.set(false);
        this.errorMsg.set(err.error?.erro || 'Erro ao registrar voto.');
      }
    });
  }

  protected abrirDetalhe(item: VotacaoItem): void {
    this.detalheLoading.set(true);
    this.http.get<any>(`${environment.apiUrl}/api/secretaria/sessoes/${item.sessao_id}/balaustre`, {
      headers: this.supabaseService.getAuthHeaders()
    }).subscribe({
      next: res => {
        this.detalheLoading.set(false);
        this.detalhe.set({ item, preview: res.preview });
      },
      error: err => {
        this.detalheLoading.set(false);
        this.errorMsg.set(err.error?.erro || 'Não foi possível carregar o balaústre.');
      }
    });
  }
}
