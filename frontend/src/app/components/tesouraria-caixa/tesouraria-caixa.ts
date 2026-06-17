import { Component, inject, OnInit, ViewChild, ElementRef, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';
import { SupabaseService } from '../../services/supabase.service';
import { environment } from '../../../environments/environment';
import { Chart, registerables } from 'chart.js/auto';

// Registra todos os componentes necessários do Chart.js
Chart.register(...registerables);

@Component({
  selector: 'app-tesouraria-caixa',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './tesouraria-caixa.html',
  styleUrl: './tesouraria-caixa.css'
})
export class TesourariaCaixa implements OnInit {
  private http = inject(HttpClient);
  private supabaseService = inject(SupabaseService);
  private router = inject(Router);

  // Referências aos canvas dos gráficos no template
  @ViewChild('chartMensalCanvas') private chartMensalCanvas!: ElementRef<HTMLCanvasElement>;
  @ViewChild('chartCategoriasCanvas') private chartCategoriasCanvas!: ElementRef<HTMLCanvasElement>;

  // Instâncias ativas dos gráficos
  private chartMensalInstance: Chart | null = null;
  private chartCategoriasInstance: Chart | null = null;

  // Filtros de tempo (mês e ano)
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

  // Dados da API
  protected lancamentos = signal<any[]>([]);
  protected categorias = signal<any[]>([]);
  protected totaisMes = signal<{ entrada: number; saida: number }>({ entrada: 0, saida: 0 });
  protected previsaoEntradas = signal<number>(0);
  protected previsaoSaidas = signal<number>(0);
  protected saldoPrevisto = signal<number>(0);
  protected saldoAtual = signal<number>(0);
  protected saldoInicial = signal<number>(0);
  
  // Loading e Erros
  protected loading = signal(false);
  protected loadingGraficos = signal(false);
  protected errorMsg = signal<string | null>(null);

  // Tab ativa
  protected activeTab = signal<'caixa' | 'agendamentos' | 'valores'>('caixa');
  protected obreiros = signal<any[]>([]);

  // Formulário de Agendamento
  protected schedObreiroId = signal<string>('');
  protected schedJoiaAtiva = signal<boolean>(false);
  protected schedJoiaTipo = signal<string>('nenhuma');
  protected schedJoiaValor = signal<number | null>(1502.00);
  protected schedJoiaFormato = signal<string>('a_vista');
  protected schedJoiaData = signal<string | null>(null);
  protected schedBibliotecaAtiva = signal<boolean>(false);
  protected schedBibliotecaValor = signal<number | null>(44.00);
  protected schedBibliotecaMes = signal<number | null>(null);
  protected schedBibliotecaFormato = signal<string>('mensal');
  protected schedMensalidadeAtiva = signal<boolean>(true);
  protected schedMensalidadeValor = signal<number | null>(150.00);
  protected salvandoAgendamento = signal<boolean>(false);

  // Formulário de novo lançamento
  protected showNovoForm = signal(false);
  protected novoTipo = signal<'entrada' | 'saida'>('entrada');
  protected novoValor = signal<number | null>(null);
  protected novaCategoriaId = signal<number | null>(null);
  protected novaData = signal<string>(new Date().toISOString().split('T')[0]);
  protected novaDescricao = signal('');
  protected salvandoLancamento = signal(false);

  ngOnInit(): void {
    this.carregarDados();
    this.carregarCategorias();
    this.carregarObreiros();
    this.carregarConfiguracaoValores();
  }

  protected navegar(url: string): void {
    this.router.navigate([url]);
  }

  protected carregarDados(): void {
    this.loading.set(true);
    this.errorMsg.set(null);
    const headers = this.supabaseService.getAuthHeaders();

    this.http.get<any>(
      `${environment.apiUrl}/api/tesouraria/caixa?mes=${this.mesSelecionado()}&ano=${this.anoSelecionado()}`,
      { headers }
    ).subscribe({
      next: (res) => {
        if (res && res.ok) {
          this.lancamentos.set(res.lancamentos || []);
          this.totaisMes.set({
            entrada: Number(res.totais?.entrada || 0),
            saida: Number(res.totais?.saida || 0)
          });
          this.previsaoEntradas.set(Number(res.previsao_entradas || 0));
          this.previsaoSaidas.set(Number(res.previsao_saidas || 0));
          this.saldoPrevisto.set(Number(res.saldo_previsto || 0));
          this.saldoAtual.set(Number(res.saldo_atual || 0));
          this.saldoInicial.set(Number(res.saldo_inicial || 0));
        } else {
          this.errorMsg.set(res.erro || 'Falha ao carregar lançamentos de caixa.');
        }
        this.loading.set(false);
      },
      error: (err) => {
        console.error('[Tesouraria] Erro ao carregar caixa:', err);
        this.errorMsg.set('Erro ao carregar dados do caixa do PHP.');
        this.loading.set(false);
      }
    });

    // Carrega dados específicos do gráfico do ano inteiro
    this.carregarDadosGraficos();
  }

  private carregarCategorias(): void {
    const headers = this.supabaseService.getAuthHeaders();
    this.http.get<any>(`${environment.apiUrl}/api/tesouraria/categorias`, { headers }).subscribe({
      next: (res) => {
        if (res && res.ok) {
          this.categorias.set(res.categorias || []);
        }
      }
    });
  }

  private carregarDadosGraficos(): void {
    this.loadingGraficos.set(true);
    const headers = this.supabaseService.getAuthHeaders();

    this.http.get<any>(
      `${environment.apiUrl}/api/tesouraria/graficos?ano=${this.anoSelecionado()}`,
      { headers }
    ).subscribe({
      next: (res) => {
        if (res && res.ok && res.dados) {
          this.renderizarGraficos(res.dados);
        }
        this.loadingGraficos.set(false);
      },
      error: (err) => {
        console.error('[Tesouraria] Erro ao carregar dados dos gráficos:', err);
        this.loadingGraficos.set(false);
      }
    });
  }

  private renderizarGraficos(dados: any): void {
    // 1. Renderiza Gráfico Mensal de Receitas vs Despesas
    if (this.chartMensalInstance) {
      this.chartMensalInstance.destroy();
    }

    const mesesLabels = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
    const entradas = dados.mensal.map((m: any) => m.entrada);
    const saidas = dados.mensal.map((m: any) => m.saida);

    this.chartMensalInstance = new Chart(this.chartMensalCanvas.nativeElement, {
      type: 'bar',
      data: {
        labels: mesesLabels,
        datasets: [
          {
            label: 'Entradas (Receitas)',
            data: entradas,
            backgroundColor: 'rgba(16, 185, 129, 0.65)', // Emerald-500
            borderColor: 'rgb(16, 185, 129)',
            borderWidth: 1,
            borderRadius: 6
          },
          {
            label: 'Saídas (Despesas)',
            data: saidas,
            backgroundColor: 'rgba(239, 68, 68, 0.65)', // Red-500
            borderColor: 'rgb(239, 68, 68)',
            borderWidth: 1,
            borderRadius: 6
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'top',
            labels: { color: '#94a3b8', font: { family: 'Inter', size: 10 } }
          }
        },
        scales: {
          x: { grid: { display: false }, ticks: { color: '#94a3b8' } },
          y: { grid: { color: 'rgba(255, 255, 255, 0.05)' }, ticks: { color: '#94a3b8' } }
        }
      }
    });

    // 2. Renderiza Gráfico de Distribuição de Categoria (Despesas)
    if (this.chartCategoriasInstance) {
      this.chartCategoriasInstance.destroy();
    }

    const despesasCats = dados.categorias.filter((c: any) => c.tipo === 'saida');
    const catLabels = despesasCats.map((c: any) => c.categoria);
    const catValores = despesasCats.map((c: any) => Number(c.total));

    // Cores em tons de vermelho/laranja/dourado para design premium
    const colors = [
      '#EF4444', '#F97316', '#F59E0B', '#C9A227', '#E5B82B',
      '#D97706', '#B45309', '#78350F'
    ];

    this.chartCategoriasInstance = new Chart(this.chartCategoriasCanvas.nativeElement, {
      type: 'doughnut',
      data: {
        labels: catLabels.length ? catLabels : ['Nenhuma Despesa'],
        datasets: [
          {
            data: catValores.length ? catValores : [1],
            backgroundColor: catValores.length ? colors.slice(0, catValores.length) : ['rgba(255, 255, 255, 0.05)'],
            borderWidth: 1,
            borderColor: 'rgba(11, 19, 43, 0.65)'
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'right',
            labels: { color: '#94a3b8', font: { family: 'Inter', size: 10 } }
          }
        }
      }
    });
  }

  protected salvarNovoLancamento(): void {
    if (!this.novoValor() || this.novoValor()! <= 0) {
      alert('Informe um valor de lançamento válido.');
      return;
    }

    this.salvandoLancamento.set(true);
    const headers = this.supabaseService.getAuthHeaders();
    
    // Obtém o mês/ano ref da data selecionada
    const dataParts = this.novaData().split('-');
    const mesRef = Number(dataParts[1]);
    const anoRef = Number(dataParts[0]);

    const payload = {
      tipo: this.novoTipo(),
      valor: this.novoValor(),
      categoria_id: this.novaCategoriaId(),
      data_lancamento: this.novaData(),
      descricao: this.novaDescricao().trim(),
      mes_ref: mesRef,
      ano_ref: anoRef
    };

    this.http.post<any>(
      `${environment.apiUrl}/api/tesouraria/lancamento/criar`,
      payload,
      { headers }
    ).subscribe({
      next: (res) => {
        if (res && res.ok) {
          // Limpa form e recarrega dados
          this.showNovoForm.set(false);
          this.novoValor.set(null);
          this.novaCategoriaId.set(null);
          this.novaDescricao.set('');
          this.carregarDados();
        } else {
          alert(res.erro || 'Falha ao salvar lançamento.');
        }
        this.salvandoLancamento.set(false);
      },
      error: (err) => {
        console.error('[Tesouraria] Erro ao criar lançamento:', err);
        alert('Erro de conexão ao salvar.');
        this.salvandoLancamento.set(false);
      }
    });
  }

  protected excluirLancamento(id: number): void {
    if (!confirm('Deseja realmente excluir este lançamento financeiro de caixa?')) {
      return;
    }

    const headers = this.supabaseService.getAuthHeaders();
    this.http.delete<any>(
      `${environment.apiUrl}/api/tesouraria/lancamento/${id}`,
      { headers }
    ).subscribe({
      next: (res) => {
        if (res && res.ok) {
          this.carregarDados();
        } else {
          alert('Falha ao excluir lançamento.');
        }
      },
      error: (err) => console.error('[Tesouraria] Erro ao excluir:', err)
    });
  }

  protected preencherAtalhoLancamento(tipo: 'entrada' | 'saida', codigoCategoria: string, valorSugerido: number, descricao: string): void {
    const cat = this.categorias().find(c => String(c.codigo).toUpperCase() === codigoCategoria.toUpperCase() || String(c.nome).toLowerCase() === codigoCategoria.toLowerCase());
    
    this.novoTipo.set(tipo);
    this.novoValor.set(valorSugerido);
    this.novaCategoriaId.set(cat ? Number(cat.id) : null);
    this.novaDescricao.set(descricao);
    this.novaData.set(new Date().toISOString().split('T')[0]);
    this.showNovoForm.set(true);
  }

  protected gerarLoteMensalidadesBiblioteca(): void {
    if (!confirm('Deseja realmente gerar as mensalidades (R$ 150) e contribuições à biblioteca (R$ 44) para os membros no ano atual?')) {
      return;
    }

    this.loading.set(true);
    const headers = this.supabaseService.getAuthHeaders();
    const payload = {
      mes: this.mesSelecionado(),
      ano: this.anoSelecionado()
    };

    this.http.post<any>(
      `${environment.apiUrl}/api/tesouraria/auto-gerar`,
      payload,
      { headers }
    ).subscribe({
      next: (res) => {
        this.loading.set(false);
        if (res && res.ok) {
          alert(`Lote processado! Mensalidades: ${res.mensalidades?.geradas || 0} criadas. Biblioteca: ${res.biblioteca?.geradas || 0} criadas.`);
          this.carregarDados();
        } else {
          alert(res.erro || 'Falha ao gerar lote automático.');
          this.carregarDados();
        }
      },
      error: (err) => {
        this.loading.set(false);
        console.error('[Tesouraria] Erro ao gerar lote:', err);
        alert('Erro de conexão ao gerar lote.');
        this.carregarDados();
      }
    });
  }

  private carregarObreiros(): void {
    const headers = this.supabaseService.getAuthHeaders();
    this.http.get<any>(`${environment.apiUrl}/api/tesouraria/obreiros-financeiro`, { headers }).subscribe({
      next: (res) => {
        if (res && res.ok) {
          this.obreiros.set(res.obreiros || []);
        }
      }
    });
  }

  protected onObreiroChange(obreiroId: string): void {
    this.schedObreiroId.set(obreiroId);
    if (!obreiroId) {
      this.schedJoiaAtiva.set(false);
      this.schedJoiaTipo.set('nenhuma');
      this.schedJoiaValor.set(this.genJoiaIniciacao());
      this.schedJoiaFormato.set('a_vista');
      this.schedJoiaData.set(null);
      this.schedBibliotecaAtiva.set(false);
      this.schedBibliotecaValor.set(this.genBiblioteca());
      this.schedBibliotecaMes.set(null);
      this.schedBibliotecaFormato.set('mensal');
      this.schedMensalidadeAtiva.set(true);
      this.schedMensalidadeValor.set(this.genMensalidade());
      return;
    }

    const ob = this.obreiros().find(o => o.id === obreiroId);
    if (ob) {
      this.schedJoiaAtiva.set(!!ob.financeiro_joia_ativa);
      this.schedJoiaTipo.set(ob.financeiro_joia_tipo || 'nenhuma');
      
      let defaultJoia = this.genJoiaIniciacao();
      if (ob.financeiro_joia_tipo === 'elevacao') {
        defaultJoia = this.genJoiaElevacao();
      } else if (ob.financeiro_joia_tipo === 'exaltacao') {
        defaultJoia = this.genJoiaExaltacao();
      }
      this.schedJoiaValor.set(ob.financeiro_joia_valor !== null ? Number(ob.financeiro_joia_valor) : defaultJoia);
      this.schedJoiaFormato.set(ob.financeiro_joia_formato || 'a_vista');
      
      let joiaDate = null;
      if (ob.financeiro_joia_tipo === 'elevacao') {
        joiaDate = ob.data_elevacao;
      } else if (ob.financeiro_joia_tipo === 'exaltacao') {
        joiaDate = ob.data_exaltacao;
      } else {
        joiaDate = ob.data_iniciacao;
      }
      this.schedJoiaData.set(joiaDate ? joiaDate.split('T')[0] : null);

      this.schedBibliotecaValor.set(ob.financeiro_biblioteca_valor !== null ? Number(ob.financeiro_biblioteca_valor) : this.genBiblioteca());
      this.schedBibliotecaFormato.set(ob.financeiro_biblioteca_formato || 'mensal');
      this.schedBibliotecaMes.set(ob.financeiro_biblioteca_mes !== null ? Number(ob.financeiro_biblioteca_mes) : null);
      this.schedBibliotecaAtiva.set(ob.financeiro_biblioteca_formato !== 'isento' && ob.financeiro_biblioteca_valor !== null);

      this.schedMensalidadeAtiva.set(ob.financeiro_mensalidade_formato !== 'isento');
      this.schedMensalidadeValor.set(ob.financeiro_mensalidade_valor !== null ? Number(ob.financeiro_mensalidade_valor) : this.genMensalidade());
    }
  }

  protected onJoiaTipoChange(tipo: string): void {
    this.schedJoiaTipo.set(tipo);
    this.schedJoiaAtiva.set(tipo !== 'nenhuma');
    if (tipo !== 'nenhuma' && !this.schedJoiaValor()) {
      let defaultJoia = this.genJoiaIniciacao();
      if (tipo === 'elevacao') {
        defaultJoia = this.genJoiaElevacao();
      } else if (tipo === 'exaltacao') {
        defaultJoia = this.genJoiaExaltacao();
      }
      this.schedJoiaValor.set(defaultJoia);
    }
  }

  protected salvarAgendamento(): void {
    if (!this.schedObreiroId()) {
      alert('Selecione um obreiro.');
      return;
    }

    this.salvandoAgendamento.set(true);
    const headers = this.supabaseService.getAuthHeaders();

    let dataIniciacao = null;
    let dataElevacao = null;
    let dataExaltacao = null;

    if (this.schedJoiaTipo() === 'iniciacao') {
      dataIniciacao = this.schedJoiaData();
    } else if (this.schedJoiaTipo() === 'elevacao') {
      dataElevacao = this.schedJoiaData();
    } else if (this.schedJoiaTipo() === 'exaltacao') {
      dataExaltacao = this.schedJoiaData();
    }

    const payload = {
      obreiro_id: this.schedObreiroId(),
      joia_valor: this.schedJoiaAtiva() ? this.schedJoiaValor() : null,
      joia_formato: this.schedJoiaAtiva() ? this.schedJoiaFormato() : 'a_vista',
      joia_ativa: this.schedJoiaAtiva(),
      joia_tipo: this.schedJoiaTipo(),
      biblioteca_valor: this.schedBibliotecaAtiva() ? this.schedBibliotecaValor() : null,
      biblioteca_formato: this.schedBibliotecaAtiva() ? this.schedBibliotecaFormato() : 'isento',
      biblioteca_mes: this.schedBibliotecaAtiva() ? this.schedBibliotecaMes() : null,
      data_iniciacao: dataIniciacao,
      data_elevacao: dataElevacao,
      data_exaltacao: dataExaltacao,
      mensalidade_valor: this.schedMensalidadeAtiva() ? this.schedMensalidadeValor() : null,
      mensalidade_formato: this.schedMensalidadeAtiva() ? 'mensal' : 'isento'
    };

    this.http.post<any>(
      `${environment.apiUrl}/api/tesouraria/obreiros-financeiro/salvar`,
      payload,
      { headers }
    ).subscribe({
      next: (res) => {
        this.salvandoAgendamento.set(false);
        if (res && res.ok) {
          alert('Agendamento salvo com sucesso!');
          this.carregarObreiros();
        } else {
          alert(res.erro || 'Falha ao salvar agendamento.');
        }
      },
      error: (err) => {
        this.salvandoAgendamento.set(false);
        console.error('[Tesouraria] Erro ao salvar agendamento:', err);
        alert('Erro de conexão ao salvar.');
      }
    });
  }

  // Parâmetros Gerais (Configuração da Loja)
  protected genMensalidade = signal<number>(150.00);
  protected genBiblioteca = signal<number>(44.00);
  protected genJoiaIniciacao = signal<number>(1502.00);
  protected genJoiaElevacao = signal<number>(1502.00);
  protected genJoiaExaltacao = signal<number>(1502.00);
  protected salvandoConfig = signal<boolean>(false);
  protected configSuccessMsg = signal<string | null>(null);
  protected configErrorMsg = signal<string | null>(null);

  protected mudarTab(tab: 'caixa' | 'agendamentos' | 'valores'): void {
    this.activeTab.set(tab);
    if (tab === 'valores') {
      this.carregarConfiguracaoValores();
    }
  }

  protected carregarConfiguracaoValores(): void {
    this.configErrorMsg.set(null);
    this.configSuccessMsg.set(null);
    const headers = this.supabaseService.getAuthHeaders();
    this.http.get<any>(`${environment.apiUrl}/api/tesouraria/configuracao-valores`, { headers }).subscribe({
      next: (res) => {
        if (res && res.ok) {
          this.genMensalidade.set(Number(res.mensalidade_valor_padrao || 150.00));
          this.genBiblioteca.set(Number(res.contribuicao_biblioteca_valor_padrao || 44.00));
          this.genJoiaIniciacao.set(Number(res.joia_iniciacao_valor_padrao || 1502.00));
          this.genJoiaElevacao.set(Number(res.joia_elevacao_valor_padrao || 1502.00));
          this.genJoiaExaltacao.set(Number(res.joia_exaltacao_valor_padrao || 1502.00));
        } else {
          this.configErrorMsg.set(res.erro || 'Falha ao carregar configurações gerais.');
        }
      },
      error: (err) => {
        console.error('[Tesouraria] Erro ao carregar config:', err);
        this.configErrorMsg.set('Erro de conexão ao carregar configurações.');
      }
    });
  }

  protected salvarConfiguracaoValores(): void {
    this.salvandoConfig.set(true);
    this.configErrorMsg.set(null);
    this.configSuccessMsg.set(null);
    const headers = this.supabaseService.getAuthHeaders();
    const payload = {
      mensalidade_valor_padrao: this.genMensalidade(),
      contribuicao_biblioteca_valor_padrao: this.genBiblioteca(),
      joia_iniciacao_valor_padrao: this.genJoiaIniciacao(),
      joia_elevacao_valor_padrao: this.genJoiaElevacao(),
      joia_exaltacao_valor_padrao: this.genJoiaExaltacao()
    };

    this.http.post<any>(
      `${environment.apiUrl}/api/tesouraria/configuracao-valores/salvar`,
      payload,
      { headers }
    ).subscribe({
      next: (res) => {
        this.salvandoConfig.set(false);
        if (res && res.ok) {
          this.configSuccessMsg.set('Parâmetros gerais salvos com sucesso!');
          this.carregarObreiros();
        } else {
          this.configErrorMsg.set(res.erro || 'Falha ao salvar parâmetros gerais.');
        }
      },
      error: (err) => {
        this.salvandoConfig.set(false);
        console.error('[Tesouraria] Erro ao salvar config:', err);
        this.configErrorMsg.set('Erro de conexão ao salvar.');
      }
    });
  }
}
