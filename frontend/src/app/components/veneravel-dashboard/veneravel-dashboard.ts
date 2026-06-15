import { Component, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { SupabaseService } from '../../services/supabase.service';
import { environment } from '../../../environments/environment';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-veneravel-dashboard',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './veneravel-dashboard.html',
  styleUrl: './veneravel-dashboard.css'
})
export class VeneravelDashboard implements OnInit {
  private http = inject(HttpClient);
  protected supabaseService = inject(SupabaseService);

  // General Status
  protected loading = signal(true);
  protected errorMsg = signal<string | null>(null);
  protected successMsg = signal<string | null>(null);

  // Tabs
  protected activeTab = 'resumo-executivo';

  // Navigation Month/Year
  protected mesSelecionado = new Date().getMonth() + 1;
  protected anoSelecionado = new Date().getFullYear();

  // Dashboard Data Payload
  protected dados = signal<any>(null);

  // Input bindings maps
  protected auxiliosDecisoes: Record<number, { valor_aprovado: string; justificativa: string }> = {};

  // Form Contato (Preventive Care)
  protected showContatoForm = signal(false);
  protected formContato = signal<any>({
    obreiro_id: '',
    sinal_id: null,
    data_contato: new Date().toISOString().split('T')[0],
    meio_contato: 'whatsapp',
    resultado: '',
    nivel_sigilo: 'reservado',
    observacoes_sigilosas: '',
    proximo_acompanhamento: ''
  });

  ngOnInit(): void {
    this.carregarDados();
  }

  protected carregarDados(): void {
    this.loading.set(true);
    this.errorMsg.set(null);
    this.successMsg.set(null);
    const headers = this.supabaseService.getAuthHeaders();

    const url = `${environment.apiUrl}/api/veneravel/dashboard?mes=${this.mesSelecionado}&ano=${this.anoSelecionado}`;

    this.http.get<any>(url, { headers }).subscribe({
      next: (res) => {
        this.loading.set(false);
        if (res && res.ok) {
          this.dados.set(res);
          // Pre-populate assistance maps
          if (res.auxilios_pendentes && res.auxilios_pendentes.length > 0) {
            res.auxilios_pendentes.forEach((a: any) => {
              if (!this.auxiliosDecisoes[a.id]) {
                this.auxiliosDecisoes[a.id] = {
                  valor_aprovado: String(a.valor_estimado || 0),
                  justificativa: ''
                };
              }
            });
          }
        } else {
          this.errorMsg.set('Erro ao obter dados do painel Venerável Mestre.');
        }
      },
      error: (err) => {
        this.loading.set(false);
        this.errorMsg.set(err.error?.erro || 'Erro de conexão ao carregar dados do painel.');
      }
    });
  }

  protected anteriorMes(): void {
    this.mesSelecionado--;
    if (this.mesSelecionado < 1) {
      this.mesSelecionado = 12;
      this.anoSelecionado--;
    }
    this.carregarDados();
  }

  protected proximoMes(): void {
    this.mesSelecionado++;
    if (this.mesSelecionado > 12) {
      this.mesSelecionado = 1;
      this.anoSelecionado++;
    }
    this.carregarDados();
  }

  // Hospitalaria Decisions
  protected decidirAssistencia(id: number, acao: 'aprovar' | 'recusar'): void {
    const decisao = this.auxiliosDecisoes[id] || { valor_aprovado: '0', justificativa: '' };
    if (acao === 'aprovar' && parseFloat(decisao.valor_aprovado) <= 0) {
      alert('Informe um valor aprovado maior que zero.');
      return;
    }

    this.loading.set(true);
    const headers = this.supabaseService.getAuthHeaders();
    const payload = {
      ocorrencia_id: id,
      acao,
      valor_aprovado: decisao.valor_aprovado,
      justificativa: decisao.justificativa
    };

    this.http.post<any>(`${environment.apiUrl}/api/veneravel/assistencia/decidir`, payload, { headers }).subscribe({
      next: (res) => {
        this.loading.set(false);
        if (res && res.ok) {
          this.successMsg.set(`Decisão de auxílio gravada com sucesso.`);
          this.carregarDados();
        } else {
          this.errorMsg.set(res.erro || 'Falha ao gravar decisão.');
        }
      },
      error: (err) => {
        this.loading.set(false);
        this.errorMsg.set(err.error?.erro || 'Erro ao gravar decisão.');
      }
    });
  }

  // Session Action Commands
  protected executarAcaoSessao(sessaoId: number, action: 'publicar' | 'cancelar' | 'reabrir' | 'realizar'): void {
    if (action === 'cancelar' && !confirm('Confirma o cancelamento definitivo desta sessão?')) {
      return;
    }
    this.loading.set(true);
    const headers = this.supabaseService.getAuthHeaders();
    this.http.post<any>(
      `${environment.apiUrl}/api/veneravel/sessoes/action`,
      { sessao_id: sessaoId, action },
      { headers }
    ).subscribe({
      next: (res) => {
        this.loading.set(false);
        if (res && res.ok) {
          this.successMsg.set(`Comando '${action}' executado com sucesso.`);
          this.carregarDados();
        } else {
          this.errorMsg.set(res.erro || 'Não foi possível executar o comando na sessão.');
        }
      },
      error: (err) => {
        this.loading.set(false);
        this.errorMsg.set(err.error?.erro || 'Erro ao processar comando.');
      }
    });
  }

  // Balaústre Action Commands
  protected executarAcaoBalaustre(balaustreId: number, action: 'abrir-votacao' | 'encerrar-votacao'): void {
    this.loading.set(true);
    const headers = this.supabaseService.getAuthHeaders();
    this.http.post<any>(
      `${environment.apiUrl}/api/veneravel/balaustre/action`,
      { balaustre_id: balaustreId, action },
      { headers }
    ).subscribe({
      next: (res) => {
        this.loading.set(false);
        if (res && res.ok) {
          this.successMsg.set(action === 'abrir-votacao' ? 'Votação do balaústre aberta com sucesso.' : 'Votação do balaústre encerrada com sucesso.');
          this.carregarDados();
        } else {
          this.errorMsg.set(res.erro || 'Não foi possível atualizar a votação do balaústre.');
        }
      },
      error: (err) => {
        this.loading.set(false);
        this.errorMsg.set(err.error?.erro || 'Erro ao processar votação.');
      }
    });
  }

  // Signal Actions
  protected executarAcaoSinal(sinalId: number, status: string): void {
    this.loading.set(true);
    const headers = this.supabaseService.getAuthHeaders();
    this.http.post<any>(
      `${environment.apiUrl}/api/veneravel/sinais/acao`,
      { sinal_id: sinalId, status },
      { headers }
    ).subscribe({
      next: (res) => {
        this.loading.set(false);
        if (res && res.ok) {
          this.successMsg.set('Status do sinal atualizado com sucesso.');
          this.carregarDados();
        } else {
          this.errorMsg.set(res.erro || 'Não foi possível atualizar o sinal.');
        }
      },
      error: (err) => {
        this.loading.set(false);
        this.errorMsg.set(err.error?.erro || 'Erro ao atualizar o sinal.');
      }
    });
  }

  // Contact logs
  protected abrirFormContato(sinal?: any): void {
    this.formContato.set({
      obreiro_id: sinal ? sinal.obreiro_id : '',
      sinal_id: sinal ? sinal.id : null,
      data_contato: new Date().toISOString().split('T')[0],
      meio_contato: 'whatsapp',
      resultado: '',
      nivel_sigilo: 'reservado',
      observacoes_sigilosas: '',
      proximo_acompanhamento: ''
    });
    this.showContatoForm.set(true);
  }

  protected cadastrarContato(): void {
    const form = this.formContato();
    if (!form.obreiro_id || !form.resultado) {
      alert('Selecione o Obreiro e relate o sumário do contato.');
      return;
    }
    this.loading.set(true);
    const headers = this.supabaseService.getAuthHeaders();
    this.http.post<any>(`${environment.apiUrl}/api/veneravel/contato/salvar`, form, { headers }).subscribe({
      next: (res) => {
        this.loading.set(false);
        if (res && res.ok) {
          this.showContatoForm.set(false);
          this.successMsg.set('Contato fraterno registrado com sucesso.');
          this.carregarDados();
        } else {
          this.errorMsg.set(res.erro || 'Não foi possível registrar o contato.');
        }
      },
      error: (err) => {
        this.loading.set(false);
        this.errorMsg.set(err.error?.erro || 'Erro de conexão.');
      }
    });
  }

  protected excluirContato(id: number): void {
    if (!confirm('Deseja excluir permanentemente este contato fraterno?')) {
      return;
    }
    this.loading.set(true);
    const headers = this.supabaseService.getAuthHeaders();
    this.http.post<any>(`${environment.apiUrl}/api/veneravel/contato/excluir`, { id }, { headers }).subscribe({
      next: (res) => {
        this.loading.set(false);
        if (res && res.ok) {
          this.successMsg.set('Contato fraterno excluído com sucesso.');
          this.carregarDados();
        } else {
          this.errorMsg.set(res.erro || 'Não foi possível excluir o contato.');
        }
      },
      error: (err) => {
        this.loading.set(false);
        this.errorMsg.set(err.error?.erro || 'Erro de conexão.');
      }
    });
  }

  // General UI Label Resolvers
  protected getMesNome(m: number): string {
    const meses = [
      'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
      'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'
    ];
    return meses[m - 1] || String(m);
  }

  protected getSessaoStatusLabel(status: string): string {
    switch (status) {
      case 'planejada': return 'Planejada';
      case 'publicada': return 'Publicada';
      case 'realizada': return 'Realizada';
      case 'cancelada': return 'Cancelada';
      default: return status;
    }
  }

  protected getSessaoStatusClass(status: string): string {
    switch (status) {
      case 'planejada': return 'bg-slate-500/10 text-slate-400 border border-slate-500/20';
      case 'publicada': return 'bg-blue-500/15 text-blue-400 border border-blue-500/25';
      case 'realizada': return 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/25';
      case 'cancelada': return 'bg-rose-500/15 text-rose-400 border border-rose-500/25';
      default: return 'bg-slate-500/10 text-slate-400';
    }
  }

  protected getSinalStatusClass(status: string): string {
    switch (status) {
      case 'aberto': return 'bg-rose-500/15 text-rose-400 border border-rose-500/25';
      case 'em_observacao': return 'bg-amber-500/15 text-amber-400 border border-amber-500/25';
      case 'arquivado': return 'bg-slate-500/10 text-slate-400 border border-slate-500/20';
      case 'resolvido': return 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/25';
      default: return 'bg-slate-500/10 text-slate-400';
    }
  }

  protected getSinalStatusLabel(status: string): string {
    switch (status) {
      case 'aberto': return 'Aberto / Sem Ação';
      case 'em_observacao': return 'Em Observação';
      case 'arquivado': return 'Arquivado';
      case 'resolvido': return 'Resolvido';
      default: return status;
    }
  }
}
