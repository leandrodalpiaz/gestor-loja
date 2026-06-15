import { Component, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { SupabaseService } from '../../services/supabase.service';
import { environment } from '../../../environments/environment';

export interface ObrigacaoAberta {
  id: number;
  titulo: string;
  valor_base: string;
  status: string;
  dia_vencimento: number;
  mes_ref: number;
  ano_ref: number;
}

@Component({
  selector: 'app-tesouraria-obrigacoes',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './tesouraria-obrigacoes.html',
  styleUrl: './tesouraria-obrigacoes.css'
})
export class TesourariaObrigacoes implements OnInit {
  private http = inject(HttpClient);
  protected supabaseService = inject(SupabaseService);

  protected loading = signal(true);
  protected salvando = signal(false);
  protected errorMsg = signal<string | null>(null);
  protected successMsg = signal<string | null>(null);

  protected obrigacoes = signal<ObrigacaoAberta[]>([]);
  protected showModal = signal(false);

  protected formComprovante = signal({
    obrigacao_id: '',
    valor_pago: '',
    observacao: '',
    arquivo_comprovante: ''
  });

  ngOnInit(): void {
    this.carregarObrigacoes();
  }

  protected carregarObrigacoes(): void {
    this.loading.set(true);
    this.errorMsg.set(null);
    const headers = this.supabaseService.getAuthHeaders();
    const obreiroId = this.supabaseService.profile()?.id;

    if (!obreiroId) {
      this.loading.set(false);
      this.errorMsg.set('O perfil local do obreiro ainda não foi resolvido.');
      this.obrigacoes.set([]);
      return;
    }

    this.http.get<any>(
      `${environment.apiUrl}/api/tesouraria/obrigacoes-abertas?obreiro_id=${encodeURIComponent(obreiroId)}`,
      { headers }
    ).subscribe({
      next: (res) => {
        this.loading.set(false);
        if (res && res.ok) {
          const parcelas = Array.isArray(res.parcelas) ? res.parcelas : [];
          this.obrigacoes.set(parcelas.map((parcela: any) => this.mapParcela(parcela)));
        } else {
          this.errorMsg.set('Falha ao carregar obrigações financeiras.');
        }
      },
      error: (err) => {
        this.loading.set(false);
        console.error('[Obrigacoes] Erro:', err);
        this.errorMsg.set(err.error?.erro || 'Erro de conexao.');
      }
    });
  }

  protected abrirSubmissao(ob: ObrigacaoAberta): void {
    this.formComprovante.set({
      obrigacao_id: String(ob.id),
      valor_pago: ob.valor_base,
      observacao: '',
      arquivo_comprovante: ''
    });
    this.showModal.set(true);
    this.successMsg.set(null);
    this.errorMsg.set(null);
  }

  protected fecharModal(): void {
    this.showModal.set(false);
  }

  protected enviarComprovante(): void {
    const parcela = this.obrigacoes().find(item => item.id === Number(this.formComprovante().obrigacao_id));
    const obreiroId = this.supabaseService.profile()?.id;

    if (!parcela || !obreiroId) {
      this.errorMsg.set('Não foi possível identificar a parcela ou o perfil do obreiro.');
      return;
    }

    this.salvando.set(true);
    this.successMsg.set(null);
    this.errorMsg.set(null);
    const headers = this.supabaseService.getAuthHeaders();

    const body = {
      obrigacao_parcela_id: parcela.id,
      valor: Number(this.formComprovante().valor_pago),
      descricao: this.formComprovante().observacao,
      comprovante_url: this.formComprovante().arquivo_comprovante,
      obreiro_id: obreiroId,
      mes_ref: parcela.mes_ref,
      ano_ref: parcela.ano_ref
    };

    this.http.post<any>(
      `${environment.apiUrl}/api/tesouraria/comprovantes/enviar`,
      body,
      { headers }
    ).subscribe({
      next: (res) => {
        this.salvando.set(false);
        if (res && res.ok) {
          this.successMsg.set('Comprovante enviado com sucesso para validação da Tesouraria.');
          this.showModal.set(false);
          this.carregarObrigacoes();
        } else {
          this.errorMsg.set(res.erro || 'Falha ao enviar comprovante.');
        }
      },
      error: (err) => {
        this.salvando.set(false);
        console.error('[Obrigacoes] Erro envio comprovante:', err);
        this.errorMsg.set(err.error?.erro || 'Erro de comunicacao com o servidor.');
      }
    });
  }

  private mapParcela(parcela: any): ObrigacaoAberta {
    const vencimento = typeof parcela.vencimento === 'string'
      ? new Date(`${parcela.vencimento}T00:00:00`)
      : null;
    const fallbackDate = new Date();

    return {
      id: Number(parcela.id),
      titulo: parcela.titulo || parcela.competencia_label || 'Obrigação financeira',
      valor_base: Number(parcela.valor_previsto || 0).toFixed(2),
      status: parcela.status || 'pendente',
      dia_vencimento: vencimento && !Number.isNaN(vencimento.getTime()) ? vencimento.getDate() : 0,
      mes_ref: Number(parcela.competencia_mes || (vencimento ? vencimento.getMonth() + 1 : fallbackDate.getMonth() + 1)),
      ano_ref: Number(parcela.competencia_ano || (vencimento ? vencimento.getFullYear() : fallbackDate.getFullYear()))
    };
  }
}
