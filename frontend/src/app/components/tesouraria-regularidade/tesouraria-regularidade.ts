import { Component, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { SupabaseService } from '../../services/supabase.service';
import { environment } from '../../../environments/environment';

@Component({
  selector: 'app-tesouraria-regularidade',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './tesouraria-regularidade.html',
  styleUrl: './tesouraria-regularidade.css'
})
export class TesourariaRegularidade implements OnInit {
  private http = inject(HttpClient);
  private supabaseService = inject(SupabaseService);

  // Tab ativa
  protected activeTab = signal<'regularidade' | 'configuracoes'>('regularidade');

  // Filtros de data
  protected mesSelecionado = signal<number>(new Date().getMonth() + 1);
  protected anoSelecionado = signal<number>(new Date().getFullYear());
  protected anosDisponiveis = [new Date().getFullYear() - 1, new Date().getFullYear(), new Date().getFullYear() + 1];
  protected meses = [
    { value: 1, label: 'Janeiro' },
    { value: 2, label: 'Fevereiro' },
    { value: 3, label: 'Março' },
    { value: 4, label: 'Abril' },
    { value: 5, label: 'Maio' },
    { value: 6, label: 'Junho' },
    { value: 7, label: 'Julho' },
    { value: 8, label: 'Agosto' },
    { value: 9, label: 'Setembro' },
    { value: 10, label: 'Outubro' },
    { value: 11, label: 'Novembro' },
    { value: 12, label: 'Dezembro' }
  ];

  // Listagens e loadings
  protected obreirosRegularidade = signal<any[]>([]);
  protected obreirosFinanceiro = signal<any[]>([]);
  protected loading = signal(false);
  protected errorMsg = signal<string | null>(null);
  protected definindoStatus = signal(false);

  // Modal de Ajustar Regularidade
  protected showModalDefinir = signal(false);
  protected obreiroSelecionado = signal<any | null>(null);
  protected novoStatus = signal<'regular' | 'irregular'>('regular');
  protected novaObservacao = signal('');

  // Modal de Configuração Individual (Joias / Biblioteca)
  protected showModalConfig = signal(false);
  protected editJoiaValor = signal<number | null>(null);
  protected editJoiaFormato = signal<string>('a_vista');
  protected editJoiaAtiva = signal<boolean>(false);
  protected editJoiaTipo = signal<string>('nenhuma');
  protected editBibliotecaValor = signal<number | null>(null);
  protected editBibliotecaFormato = signal<string>('mensal');
  protected editBibliotecaMes = signal<number | null>(null);
  protected editMensalidadeValor = signal<number | null>(null);
  protected editMensalidadeFormato = signal<string>('mensal');
  protected editMensalidadeAtiva = signal<boolean>(true);
  protected editDataIniciacao = signal<string | null>(null);
  protected editDataElevacao = signal<string | null>(null);
  protected editDataExaltacao = signal<string | null>(null);
  protected salvandoConfig = signal(false);

  // Modal/Drawer de Visualização Individual (Acompanhamento)
  protected showModalDetalhe = signal(false);
  protected detalheFinanceiroObreiro = signal<any | null>(null);
  protected loadingDetalhe = signal(false);

  ngOnInit(): void {
    this.carregarRegularidades();
    this.carregarObreiros();
  }

  protected abrirModalDetalhe(ob: any): void {
    const obreiroId = ob.id || ob.obreiro_id;
    if (!obreiroId) return;

    this.obreiroSelecionado.set(ob);
    this.loadingDetalhe.set(true);
    this.detalheFinanceiroObreiro.set(null);
    this.showModalDetalhe.set(true);

    const headers = this.supabaseService.getAuthHeaders();
    this.http.get<any>(
      `${environment.apiUrl}/api/tesouraria/obreiro/detalhe-financeiro?obreiro_id=${encodeURIComponent(obreiroId)}`,
      { headers }
    ).subscribe({
      next: (res) => {
        this.loadingDetalhe.set(false);
        if (res && res.ok) {
          this.detalheFinanceiroObreiro.set(res.detalhe);
        }
      },
      error: (err) => {
        this.loadingDetalhe.set(false);
        console.error('[Tesouraria] Erro detalhe financeiro obreiro:', err);
      }
    });
  }

  protected fecharModalDetalhe(): void {
    this.showModalDetalhe.set(false);
  }

  protected setTab(tab: 'regularidade' | 'configuracoes'): void {
    this.activeTab.set(tab);
    if (tab === 'regularidade') {
      this.carregarRegularidades();
    } else {
      this.carregarObreiros();
    }
  }

  protected carregarRegularidades(): void {
    this.loading.set(true);
    this.errorMsg.set(null);
    const headers = this.supabaseService.getAuthHeaders();

    this.http.get<any>(
      `${environment.apiUrl}/api/tesouraria/regularidade?mes=${this.mesSelecionado()}&ano=${this.anoSelecionado()}`,
      { headers }
    ).subscribe({
      next: (res) => {
        if (res && res.ok) {
          this.obreirosRegularidade.set(res.regularidade || []);
        } else {
          this.errorMsg.set(res.erro || 'Falha ao carregar listagem de regularidade.');
        }
        this.loading.set(false);
      },
      error: (err) => {
        console.error('[Tesouraria] Erro ao buscar regularidades:', err);
        this.errorMsg.set('Erro de conexão ao carregar dados do PHP.');
        this.loading.set(false);
      }
    });
  }

  protected carregarObreiros(): void {
    this.loading.set(true);
    this.errorMsg.set(null);
    const headers = this.supabaseService.getAuthHeaders();

    this.http.get<any>(
      `${environment.apiUrl}/api/tesouraria/obreiros-financeiro`,
      { headers }
    ).subscribe({
      next: (res) => {
        if (res && res.ok) {
          this.obreirosFinanceiro.set(res.obreiros || []);
        } else {
          this.errorMsg.set(res.erro || 'Falha ao carregar configurações financeiras de membros.');
        }
        this.loading.set(false);
      },
      error: (err) => {
        console.error('[Tesouraria] Erro ao buscar obreiros financeiro:', err);
        this.errorMsg.set('Erro de conexão ao carregar dados do PHP.');
        this.loading.set(false);
      }
    });
  }

  protected abrirModalDefinir(item: any): void {
    this.obreiroSelecionado.set(item);
    this.novoStatus.set(item.status === 'regular' ? 'regular' : 'irregular');
    this.novaObservacao.set(item.observacao || '');
    this.showModalDefinir.set(true);
  }

  protected salvarDefinicao(): void {
    const obreiro = this.obreiroSelecionado();
    if (!obreiro) return;

    this.definindoStatus.set(true);
    const headers = this.supabaseService.getAuthHeaders();

    const payload = {
      obreiro_id: obreiro.obreiro_id,
      mes: this.mesSelecionado(),
      ano: this.anoSelecionado(),
      status: this.novoStatus(),
      observacao: this.novaObservacao().trim()
    };

    this.http.post<any>(
      `${environment.apiUrl}/api/tesouraria/regularidade/definir`,
      payload,
      { headers }
    ).subscribe({
      next: (res) => {
        this.definindoStatus.set(false);
        if (res && res.ok) {
          this.showModalDefinir.set(false);
          this.carregarRegularidades();
        } else {
          alert(res.erro || 'Falha ao definir regularidade.');
        }
      },
      error: (err) => {
        this.definindoStatus.set(false);
        console.error('[Tesouraria] Erro ao salvar regularidade:', err);
        alert('Erro de conexão ao salvar.');
      }
    });
  }

  protected definirTodosRegulares(): void {
    if (!confirm('Deseja realmente marcar TODOS os obreiros como REGULARES neste mês?')) {
      return;
    }

    this.loading.set(true);
    const headers = this.supabaseService.getAuthHeaders();

    const payload = {
      mes: this.mesSelecionado(),
      ano: this.anoSelecionado(),
      status: 'regular'
    };

    this.http.post<any>(
      `${environment.apiUrl}/api/tesouraria/regularidade/definir-todos`,
      payload,
      { headers }
    ).subscribe({
      next: (res) => {
        if (res && res.ok) {
          this.carregarRegularidades();
        } else {
          alert('Falha ao redefinir a regularidade de todos.');
          this.loading.set(false);
        }
      },
      error: (err) => {
        console.error('[Tesouraria] Erro ao redefinir todos:', err);
        this.loading.set(false);
      }
    });
  }

  // Ações de Configuração Individual
  protected abrirModalConfig(ob: any): void {
    this.obreiroSelecionado.set(ob);
    this.editJoiaValor.set(ob.financeiro_joia_valor !== null ? Number(ob.financeiro_joia_valor) : null);
    this.editJoiaFormato.set(ob.financeiro_joia_formato || 'a_vista');
    this.editJoiaAtiva.set(!!ob.financeiro_joia_ativa);
    this.editJoiaTipo.set(ob.financeiro_joia_tipo || 'nenhuma');
    this.editBibliotecaValor.set(ob.financeiro_biblioteca_valor !== null ? Number(ob.financeiro_biblioteca_valor) : null);
    this.editBibliotecaFormato.set(ob.financeiro_biblioteca_formato || 'mensal');
    this.editBibliotecaMes.set(ob.financeiro_biblioteca_mes !== null ? Number(ob.financeiro_biblioteca_mes) : null);
    
    this.editMensalidadeValor.set(ob.financeiro_mensalidade_valor !== null ? Number(ob.financeiro_mensalidade_valor) : null);
    this.editMensalidadeFormato.set(ob.financeiro_mensalidade_formato || 'mensal');
    this.editMensalidadeAtiva.set((ob.financeiro_mensalidade_formato || 'mensal') !== 'isento');

    this.editDataIniciacao.set(ob.data_iniciacao ? ob.data_iniciacao.split('T')[0] : null);
    this.editDataElevacao.set(ob.data_elevacao ? ob.data_elevacao.split('T')[0] : null);
    this.editDataExaltacao.set(ob.data_exaltacao ? ob.data_exaltacao.split('T')[0] : null);
    this.showModalConfig.set(true);
  }

  protected salvarConfiguracao(): void {
    const ob = this.obreiroSelecionado();
    if (!ob) return;

    this.salvandoConfig.set(true);
    const headers = this.supabaseService.getAuthHeaders();

    const payload = {
      obreiro_id: ob.id || ob.obreiro_id,
      joia_valor: this.editJoiaValor(),
      joia_formato: this.editJoiaFormato(),
      joia_ativa: this.editJoiaAtiva(),
      joia_tipo: this.editJoiaTipo(),
      biblioteca_valor: this.editBibliotecaValor(),
      biblioteca_formato: this.editBibliotecaFormato(),
      biblioteca_mes: this.editBibliotecaMes(),
      mensalidade_valor: this.editMensalidadeAtiva() ? this.editMensalidadeValor() : null,
      mensalidade_formato: this.editMensalidadeAtiva() ? 'mensal' : 'isento',
      data_iniciacao: this.editDataIniciacao(),
      data_elevacao: this.editDataElevacao(),
      data_exaltacao: this.editDataExaltacao()
    };

    this.http.post<any>(
      `${environment.apiUrl}/api/tesouraria/obreiros-financeiro/salvar`,
      payload,
      { headers }
    ).subscribe({
      next: (res) => {
        this.salvandoConfig.set(false);
        if (res && res.ok) {
          this.showModalConfig.set(false);
          this.carregarObreiros();
        } else {
          alert(res.erro || 'Falha ao salvar configurações financeiras.');
        }
      },
      error: (err) => {
        console.error('[Tesouraria] Erro ao salvar configurações:', err);
        alert('Erro de conexão ao salvar.');
        this.salvandoConfig.set(false);
      }
    });
  }
}
