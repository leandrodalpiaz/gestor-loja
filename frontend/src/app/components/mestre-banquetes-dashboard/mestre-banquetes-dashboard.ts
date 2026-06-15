import { Component, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { SupabaseService } from '../../services/supabase.service';
import { environment } from '../../../environments/environment';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-mestre-banquetes-dashboard',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './mestre-banquetes-dashboard.html',
  styleUrl: './mestre-banquetes-dashboard.css'
})
export class MestreBanquetesDashboard implements OnInit {
  private http = inject(HttpClient);
  protected supabaseService = inject(SupabaseService);

  protected loading = signal(true);
  protected errorMsg = signal<string | null>(null);
  protected successMsg = signal<string | null>(null);

  protected dataBanquetes = signal<any>(null);
  protected selectedBanqueteSessaoId = signal<number | null>(null);
  protected banqueteForm: any = {};

  ngOnInit(): void {
    this.carregarDados();
  }

  protected carregarDados(): void {
    this.loading.set(true);
    this.errorMsg.set(null);
    const headers = this.supabaseService.getAuthHeaders();

    const sessaoId = this.selectedBanqueteSessaoId();
    const url = `${environment.apiUrl}/api/miniapp/mestre-banquetes/dashboard${sessaoId ? `?sessao_id=${encodeURIComponent(String(sessaoId))}` : ''}`;

    this.http.get<any>(url, { headers }).subscribe({
      next: (res) => {
        this.loading.set(false);
        if (res && res.ok) {
          const payload = res.dados ?? res;
          this.dataBanquetes.set(payload);
          const sessaoFocoId = Number(payload?.sessao_foco?.id || 0);
          this.selectedBanqueteSessaoId.set(sessaoFocoId > 0 ? sessaoFocoId : null);
          this.banqueteForm = {
            sessao_id: sessaoFocoId,
            status_operacional: payload?.operacao?.status_operacional || 'planejamento',
            fornecedor: payload?.operacao?.fornecedor || '',
            previsao_participantes: Number(payload?.operacao?.previsao_participantes || payload?.participantes_agape?.length || 0),
            valor_unitario_previsto: Number(payload?.operacao?.valor_unitario_previsto || payload?.sessao_foco?.agape_valor || 0),
            custo_previsto: Number(payload?.operacao?.custo_previsto || 0),
            valor_arrecadado: Number(payload?.operacao?.valor_arrecadado || 0),
            custo_real: Number(payload?.operacao?.custo_real || 0),
            forma_pagamento: payload?.operacao?.forma_pagamento || '',
            financeiro_status: payload?.operacao?.financeiro_status || 'planejado',
            financeiro_observacoes: payload?.operacao?.financeiro_observacoes || '',
            fluxo_financeiro: payload?.operacao?.fluxo_financeiro || 'rateio_particular',
            responsavel_pagamento: payload?.operacao?.responsavel_pagamento || '',
            observacoes: payload?.operacao?.observacoes || ''
          };
        } else {
          this.errorMsg.set('Erro ao carregar o painel do Mestre de Banquetes.');
        }
      },
      error: (err) => {
        this.loading.set(false);
        this.errorMsg.set(err.error?.erro || 'Não foi possível carregar o painel do Mestre de Banquetes.');
      }
    });
  }

  protected selecionarBanqueteSessao(event: Event): void {
    const id = Number((event.target as HTMLSelectElement).value);
    this.selectedBanqueteSessaoId.set(id > 0 ? id : null);
    this.carregarDados();
  }

  protected salvarOperacaoBanquete(): void {
    this.banqueteForm.sessao_id = this.selectedBanqueteSessaoId();
    this.loading.set(true);
    this.http.post<any>(`${environment.apiUrl}/api/miniapp/mestre-banquetes/operacao/salvar`, this.banqueteForm, {
      headers: this.supabaseService.getAuthHeaders()
    }).subscribe({
      next: (res) => {
        this.loading.set(false);
        if (res?.ok) {
          this.successMsg.set('Operação do ágape salva com sucesso.');
          this.carregarDados();
        } else {
          this.errorMsg.set(res?.erro || 'Não foi possível concluir a operação.');
        }
      },
      error: (err) => {
        this.loading.set(false);
        this.errorMsg.set(err.error?.erro || 'Erro ao concluir a operação.');
      }
    });
  }
}
