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

  protected activeTab = signal<'logistica' | 'rateio'>('logistica');
  protected pixChave = signal<string>('');
  protected pixBeneficiario = signal<string>('');
  protected pixCidade = signal<string>('Porto Alegre');
  protected pixValor = signal<number | null>(null);
  protected pixQrCodeUrl = signal<string>('');
  protected pixCopiaCola = signal<string>('');

  ngOnInit(): void {
    this.carregarDados();
  }

  protected setTab(tab: 'logistica' | 'rateio'): void {
    this.activeTab.set(tab);
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

          // Preenchimento das configurações do Pix
          const config = payload?.configuracao;
          if (config) {
            this.pixChave.set(config.pix_chave_valor || '');
            this.pixBeneficiario.set(config.nome_loja || '');
            this.pixCidade.set(config.cidade || 'Porto Alegre');
          }
          const valorUnitario = Number(payload?.operacao?.valor_unitario_previsto || payload?.sessao_foco?.agape_valor || 0);
          this.pixValor.set(valorUnitario > 0 ? valorUnitario : null);
          this.gerarPix();
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

  // Lógica do Gerador Pix
  protected onPixChange(): void {
    this.gerarPix();
  }

  protected setPixValor(event: Event): void {
    const val = (event.target as HTMLInputElement).value;
    this.pixValor.set(val === '' ? null : Number(val));
    this.gerarPix();
  }

  private sanitizarTexto(str: string): string {
    return str
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .replace(/[^a-zA-Z0-9 ]/g, '')
      .trim();
  }

  protected gerarPix(): void {
    const chave = this.pixChave().trim();
    let beneficiario = this.pixBeneficiario().trim();
    let cidade = this.pixCidade().trim();
    const valor = this.pixValor();

    if (!chave) {
      this.pixCopiaCola.set('');
      this.pixQrCodeUrl.set('');
      return;
    }

    beneficiario = this.sanitizarTexto(beneficiario).substring(0, 25);
    cidade = this.sanitizarTexto(cidade).substring(0, 15);
    if (cidade === '') {
      cidade = 'Porto Alegre';
    }

    const gui = '0014br.gov.bcb.pix';
    const keyTag = '01' + String(chave.length).padStart(2, '0') + chave;
    const merchantInfo = gui + keyTag;
    
    let payload = '';
    payload += '000201'; // Payload Format Indicator
    payload += '010211'; // Point of Initiation Method: 11 (estático)
    payload += '26' + String(merchantInfo.length).padStart(2, '0') + merchantInfo;
    payload += '52040000'; // Category Code
    payload += '5303986'; // Currency: 986 (BRL)
    
    if (valor !== null && valor > 0) {
      const valorStr = valor.toFixed(2);
      payload += '54' + String(valorStr.length).padStart(2, '0') + valorStr;
    }
    
    payload += '5802BR'; // Country Code
    payload += '59' + String(beneficiario.length).padStart(2, '0') + beneficiario;
    payload += '60' + String(cidade.length).padStart(2, '0') + cidade;
    
    const additionalData = '0503***';
    payload += '62' + String(additionalData.length).padStart(2, '0') + additionalData;
    
    payload += '6304';
    const crc = this.calcularCRC16(payload);
    const brcode = payload + crc;
    
    this.pixCopiaCola.set(brcode);
    this.pixQrCodeUrl.set(`https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${encodeURIComponent(brcode)}`);
  }

  private calcularCRC16(str: string): string {
    let crc = 0xFFFF;
    const polynomial = 0x1021;
    
    for (let i = 0; i < str.length; i++) {
      const b = str.charCodeAt(i);
      for (let j = 0; j < 8; j++) {
        const bit = ((b >> (7 - j)) & 1) === 1;
        const c15 = ((crc >> 15) & 1) === 1;
        crc <<= 1;
        if (bit !== c15) {
          crc ^= polynomial;
        }
      }
    }
    
    crc &= 0xFFFF;
    return crc.toString(16).toUpperCase().padStart(4, '0');
  }

  protected copiarPix(): void {
    const brcode = this.pixCopiaCola();
    if (!brcode) return;
    
    navigator.clipboard.writeText(brcode).then(() => {
      this.successMsg.set('Código Pix Copia e Cola copiado com sucesso.');
      setTimeout(() => this.successMsg.set(null), 3000);
    }).catch(() => {
      this.errorMsg.set('Não foi possível copiar o código Pix.');
      setTimeout(() => this.errorMsg.set(null), 3000);
    });
  }
}
