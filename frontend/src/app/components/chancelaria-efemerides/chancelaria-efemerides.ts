import { Component, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { SupabaseService } from '../../services/supabase.service';
import { environment } from '../../../environments/environment';

const TIPOS_EFEMERIDE = [
  'Aniversario',
  'Iniciacao',
  'Elevacao',
  'Exaltacao',
  'Instalacao',
  'Oriente Eterno',
  'Historia',
  'Posse Grao Mestre',
  'Concessao de Membro Honorario',
  'Filiacao',
];

@Component({
  selector: 'app-chancelaria-efemerides',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './chancelaria-efemerides.html',
  styleUrl: './chancelaria-efemerides.css'
})
export class ChancelariaEfemerides implements OnInit {
  private http = inject(HttpClient);
  private supabaseService = inject(SupabaseService);

  protected activeTab = signal<string>('diaria');

  protected efemeridesHoje = signal<any[]>([]);
  protected efemerides = signal<any[]>([]);
  protected stories = signal<any[]>([]);
  protected cards = signal<any[]>([]);
  protected categoriasCards = signal<any[]>([]);
  protected loading = signal(false);
  protected loadingHoje = signal(false);
  protected errorMsg = signal<string | null>(null);
  protected feedbackMsg = signal<string | null>(null);
  protected feedbackTipo = signal<'success' | 'error' | 'info'>('info');

  protected mensagemBase = signal<string>('');
  protected mensagemPreview = signal<string>('');
  protected salvandoCompilada = signal(false);
  protected enviandoPrevia = signal(false);
  protected enviandoGrupo = signal(false);
  protected publicandoTudo = signal(false);
  protected gerandoCards = signal(false);
  protected edicaoAvancadaAberta = signal(false);

  protected templateConfig = signal<string>('');
  protected categoriasSelecionadas = signal<string[]>([]);
  protected salvandoTemplateCategoria = signal(false);

  protected zoomCardUrl = signal<string | null>(null);
  protected salvandoCardId = signal<number | null>(null);

  protected filtroTermo = signal('');
  protected filtroTipo = signal('');
  protected filtroAtivo = signal('1');

  protected tiposEfemeride = TIPOS_EFEMERIDE;

  protected templateOpcoes = [
    { value: 'card_irmao_bedrock.png', label: 'Irmao Bedrock' },
    { value: 'card_cunhada_solar.png', label: 'Cunhada Solar' },
    { value: 'card_grau_iniciacao.png', label: 'Grau Iniciacao' },
    { value: 'card_grau_elevacao.png', label: 'Grau Elevacao' },
    { value: 'card_grau_exaltacao.png', label: 'Grau Exaltacao' },
    { value: 'card_grau_instalacao.png', label: 'Grau Instalacao' },
    { value: 'card_historia_sepia.png', label: 'Historia Sepia' },
    { value: 'card_memorial_eterno.png', label: 'Memorial Eterno' },
    { value: 'card_familia_kids.png', label: 'Familia Kids' },
    { value: 'card_sobrinho_jovem.png', label: 'Sobrinho Jovem' },
    { value: 'card_sobrinha_adulta.png', label: 'Sobrinha Adulta' },
    { value: 'card_sobrinho_adulto.png', label: 'Sobrinho Adulto' },
    { value: 'card_oficial_sessao.png', label: 'Oficial Sessao' },
    { value: 'card_oficial_convite.png', label: 'Oficial Convite' },
    { value: 'card_especial_filiacao.png', label: 'Especial Filiacao' },
    { value: 'card_especial_honorario.png', label: 'Especial Honorario' },
    { value: 'card_especial_grao_mestre.png', label: 'Especial Grao-Mestre' },
  ];

  protected showModal = signal(false);
  protected isNovo = signal(true);
  protected salvando = signal(false);

  protected formEfemeride = signal<any>({
    id: null,
    nome: '',
    tipo: 'Aniversario',
    data_evento: '',
    vinculo: '',
    parentesco: '',
    local: '',
    mensagem_custom: '',
  });

  protected showHistoriaForm = signal(false);
  protected isNovaHistoria = signal(true);
  protected formHistoria = signal<any>({
    id: null,
    titulo: '',
    texto: '',
    dia: 1,
    mes: 1,
    ano_ref: null,
    fonte: ''
  });

  ngOnInit(): void {
    this.carregarDashboard();
  }

  protected carregarDashboard(): void {
    this.loadingHoje.set(true);
    const headers = this.supabaseService.getAuthHeaders();
    this.http.get<any>(`${environment.apiUrl}/api/chancelaria/efemerides/dashboard`, { headers }).subscribe({
      next: (res) => {
        if (res && res.ok) {
          this.efemeridesHoje.set(res.registrosHoje || []);
          this.mensagemBase.set(res.mensagemBase || '');
          this.mensagemPreview.set(res.mensagemPreview || '');
          this.cards.set(res.cards || []);
          this.categoriasCards.set(res.categoriasCards || []);
          this.categoriasSelecionadas.set([]);
          this.stories.set(res.historias || []);
          this.efemerides.set(res.registrosRecentes || []);
        } else {
          this.setFeedback(res?.erro || 'Falha ao carregar efemerides.', 'error');
        }
        this.loadingHoje.set(false);
      },
      error: (err) => {
        console.error('[Chancelaria] Erro ao carregar dashboard de efemerides:', err);
        this.setFeedback('Erro de conexao ao carregar o painel de efemerides.', 'error');
        this.loadingHoje.set(false);
      }
    });
  }

  protected carregarEfemerides(): void {
    this.loading.set(true);
    this.errorMsg.set(null);
    const headers = this.supabaseService.getAuthHeaders();
    const queryParams = new URLSearchParams({
      termo: this.filtroTermo(),
      tipo: this.filtroTipo(),
      ativo: this.filtroAtivo(),
    }).toString();

    this.http.get<any>(`${environment.apiUrl}/api/chancelaria/efemerides?${queryParams}`, { headers }).subscribe({
      next: (res) => {
        if (res && res.ok) {
          this.efemerides.set(res.registros || []);
        } else {
          this.errorMsg.set(res?.erro || 'Falha ao carregar efemerides.');
        }
        this.loading.set(false);
      },
      error: (err) => {
        console.error('[Chancelaria] Erro ao buscar efemerides:', err);
        this.errorMsg.set('Erro de conexao ao carregar dados do servidor.');
        this.loading.set(false);
      }
    });
  }

  protected limparFiltros(): void {
    this.filtroTermo.set('');
    this.filtroTipo.set('');
    this.filtroAtivo.set('1');
    this.carregarEfemerides();
  }

  protected abrirModalNovo(): void {
    this.isNovo.set(true);
    this.formEfemeride.set({
      id: null,
      nome: '',
      tipo: 'Aniversario',
      data_evento: '',
      vinculo: '',
      parentesco: '',
      local: '',
      mensagem_custom: '',
    });
    this.showModal.set(true);
  }

  protected abrirModalEditar(efemeride: any): void {
    this.isNovo.set(false);
    this.formEfemeride.set({
      id: efemeride.id,
      nome: efemeride.nome || '',
      tipo: efemeride.tipo || 'Aniversario',
      data_evento: efemeride.data_evento || '',
      vinculo: efemeride.vinculo || '',
      parentesco: efemeride.parentesco || '',
      local: efemeride.local || '',
      mensagem_custom: efemeride.mensagem_custom || '',
    });
    this.showModal.set(true);
  }

  protected salvar(): void {
    const form = this.formEfemeride();
    if (!form.nome || !form.tipo || !form.data_evento) {
      this.setFeedback('Nome, tipo e data do evento sao obrigatorios.', 'error');
      return;
    }

    this.salvando.set(true);
    const headers = this.supabaseService.getAuthHeaders();
    const endpoint = this.isNovo()
      ? `${environment.apiUrl}/api/chancelaria/efemerides/salvar`
      : `${environment.apiUrl}/api/chancelaria/efemerides/atualizar`;

    this.http.post<any>(endpoint, form, { headers }).subscribe({
      next: (res) => {
        if (res && res.ok) {
          this.showModal.set(false);
          this.setFeedback('Registro de efemeride salvo com sucesso.', 'success');
          this.carregarDashboard();
        } else {
          this.setFeedback(res?.erro || 'Falha ao salvar efemeride.', 'error');
        }
        this.salvando.set(false);
      },
      error: (err) => {
        console.error('[Chancelaria] Erro ao salvar:', err);
        this.setFeedback('Erro de conexao ao salvar.', 'error');
        this.salvando.set(false);
      }
    });
  }

  protected desativar(id: number): void {
    if (!confirm('Deseja desativar esta efemeride? Ela nao aparecera mais nos lembretes diarios.')) return;

    const headers = this.supabaseService.getAuthHeaders();
    this.http.post<any>(`${environment.apiUrl}/api/chancelaria/efemerides/desativar`, { id }, { headers }).subscribe({
      next: (res) => {
        if (res && res.ok) {
          this.setFeedback('Efemeride desativada.', 'success');
          this.carregarDashboard();
        } else {
          this.setFeedback('Falha ao desativar efemeride.', 'error');
        }
      },
      error: (err) => {
        console.error('[Chancelaria] Erro ao desativar:', err);
        this.setFeedback('Erro de conexao ao desativar.', 'error');
      }
    });
  }

  protected excluir(id: number): void {
    if (!confirm('Deseja excluir permanentemente esta efemeride? Esta acao e irreversivel.')) return;

    const headers = this.supabaseService.getAuthHeaders();
    this.http.post<any>(`${environment.apiUrl}/api/chancelaria/efemerides/excluir`, { id }, { headers }).subscribe({
      next: (res) => {
        if (res && res.ok) {
          this.setFeedback('Efemeride excluida.', 'success');
          this.carregarDashboard();
        } else {
          this.setFeedback('Falha ao excluir efemeride.', 'error');
        }
      },
      error: (err) => {
        console.error('[Chancelaria] Erro ao excluir:', err);
        this.setFeedback('Erro de conexao ao excluir.', 'error');
      }
    });
  }

  protected salvarPrevia(): void {
    this.salvandoCompilada.set(true);
    const headers = this.supabaseService.getAuthHeaders();
    this.http.post<any>(
      `${environment.apiUrl}/api/chancelaria/efemerides/salvar-previa`,
      { mensagem_preview: this.mensagemPreview() },
      { headers }
    ).subscribe({
      next: (res) => {
        if (res && res.ok) {
          this.setFeedback('Mensagem do dia salva com sucesso.', 'success');
          this.carregarDashboard();
        } else {
          this.setFeedback(res?.erro || 'Erro ao salvar previa.', 'error');
        }
        this.salvandoCompilada.set(false);
      },
      error: () => {
        this.setFeedback('Erro de rede ao salvar previa.', 'error');
        this.salvandoCompilada.set(false);
      }
    });
  }

  protected enviarPrevia(): void {
    this.enviandoPrevia.set(true);
    const headers = this.supabaseService.getAuthHeaders();
    this.http.post<any>(`${environment.apiUrl}/api/chancelaria/efemerides/enviar-previa`, {}, { headers }).subscribe({
      next: (res) => {
        if (res && res.ok) {
          this.setFeedback('Previa enviada para o Telegram privado.', 'success');
        } else {
          this.setFeedback(res?.erro || 'Erro ao enviar previa privada.', 'error');
        }
        this.enviandoPrevia.set(false);
      },
      error: () => {
        this.setFeedback('Erro de rede ao enviar previa privada.', 'error');
        this.enviandoPrevia.set(false);
      }
    });
  }

  protected enviarGrupo(): void {
    if (!confirm('Deseja enviar apenas o texto compilado no canal oficial do Telegram?')) return;
    this.enviandoGrupo.set(true);
    const headers = this.supabaseService.getAuthHeaders();
    this.http.post<any>(`${environment.apiUrl}/api/chancelaria/efemerides/enviar-grupo`, {}, { headers }).subscribe({
      next: (res) => {
        if (res && res.ok) {
          this.setFeedback('Texto enviado com sucesso no canal do Telegram.', 'success');
        } else {
          this.setFeedback(res?.erro || 'Erro ao enviar apenas texto no grupo.', 'error');
        }
        this.enviandoGrupo.set(false);
      },
      error: () => {
        this.setFeedback('Erro de rede ao enviar texto no grupo.', 'error');
        this.enviandoGrupo.set(false);
      }
    });
  }

  protected aprovarEEnviarTudo(): void {
    if (!confirm('Deseja homologar a mensagem e enviar o texto oficial com todos os cards gerados?')) return;
    this.publicandoTudo.set(true);
    const headers = this.supabaseService.getAuthHeaders();
    this.http.post<any>(`${environment.apiUrl}/api/chancelaria/efemerides/aprovar-e-enviar-tudo`, {}, { headers }).subscribe({
      next: (res) => {
        if (res && res.ok) {
          this.setFeedback(`Mensagem e imagens enviadas com sucesso. ${res.cards_enviados} de ${res.total_cards} cards publicados.`, 'success');
          this.carregarDashboard();
        } else {
          this.setFeedback(res?.erro || 'Erro ao aprovar e enviar.', 'error');
        }
        this.publicandoTudo.set(false);
      },
      error: () => {
        this.setFeedback('Erro de rede ao aprovar e enviar.', 'error');
        this.publicandoTudo.set(false);
      }
    });
  }

  protected cardsAprovarTodos(): void {
    this.gerandoCards.set(true);
    const headers = this.supabaseService.getAuthHeaders();
    this.http.post<any>(`${environment.apiUrl}/api/chancelaria/efemerides/cards-aprovar-todos`, {}, { headers }).subscribe({
      next: (res) => {
        if (res && res.ok) {
          this.setFeedback(`${res.total} cards gerados no cache com sucesso.`, 'success');
          this.carregarDashboard();
        } else {
          this.setFeedback(res?.erro || 'Erro ao re-gerar cards.', 'error');
        }
        this.gerandoCards.set(false);
      },
      error: () => {
        this.setFeedback('Erro de rede ao gerar cards.', 'error');
        this.gerandoCards.set(false);
      }
    });
  }

  protected configurarCard(card: any): void {
    const registroId = Number(card?.registro_id || 0);
    if (registroId <= 0) {
      this.setFeedback('Registro do card invalido.', 'error');
      return;
    }

    this.salvandoCardId.set(registroId);
    const headers = this.supabaseService.getAuthHeaders();
    const payload = {
      registro_id: registroId,
      ocultar_idade: !!card.ocultar_idade,
      texto_custom_card: card.texto_custom_card || card.mensagem,
      template_card: card.template_slug || card.template
    };

    this.http.post<any>(`${environment.apiUrl}/api/chancelaria/efemerides/cards-configurar`, payload, { headers }).subscribe({
      next: (res) => {
        if (res && res.ok && res.card) {
          this.cards.update((cards) => cards.map((item) => {
            if (Number(item.registro_id || 0) !== registroId) return item;
            return { ...item, ...res.card, _cacheBust: Date.now(), _statusMsg: 'Previa atualizada.' };
          }));
          this.setFeedback('Configuracao do card salva e imagem atualizada.', 'success');
        } else {
          this.setFeedback(res?.erro || 'Erro ao customizar card.', 'error');
        }
        this.salvandoCardId.set(null);
      },
      error: () => {
        this.setFeedback('Erro de rede ao salvar customizacao.', 'error');
        this.salvandoCardId.set(null);
      }
    });
  }

  protected aplicarTemplateConfig(): void {
    const templateSlug = this.templateConfig();
    const categorias = this.categoriasSelecionadas();
    if (!templateSlug || categorias.length === 0) {
      this.setFeedback('Selecione ao menos uma categoria e um template para aplicar.', 'error');
      return;
    }

    this.salvandoTemplateCategoria.set(true);
    const headers = this.supabaseService.getAuthHeaders();
    this.http.post<any>(
      `${environment.apiUrl}/api/chancelaria/efemerides/cards-template-categorias`,
      { categorias, template_slug: templateSlug },
      { headers }
    ).subscribe({
      next: (res) => {
        if (res && res.ok) {
          this.setFeedback('Template padrao das categorias selecionadas atualizado.', 'success');
          this.carregarDashboard();
        } else {
          this.setFeedback(res?.erro || 'Erro ao configurar categoria.', 'error');
        }
        this.salvandoTemplateCategoria.set(false);
      },
      error: () => {
        this.setFeedback('Erro de rede ao configurar categorias.', 'error');
        this.salvandoTemplateCategoria.set(false);
      }
    });
  }

  protected toggleCategoria(categoria: string, checked: boolean): void {
    this.categoriasSelecionadas.update((selecionadas) => {
      const set = new Set(selecionadas);
      if (checked) {
        set.add(categoria);
      } else {
        set.delete(categoria);
      }
      return Array.from(set);
    });
  }

  protected zoomCard(imageUrl: string): void {
    this.zoomCardUrl.set(this.absoluteAssetUrl(imageUrl));
  }

  protected fecharZoom(): void {
    this.zoomCardUrl.set(null);
  }

  protected baixarCard(card: any): void {
    if (!card.image_url) return;
    const link = document.createElement('a');
    link.href = this.cardImageUrl(card);
    link.download = `card_${card.registro_id || 'efemeride'}.png`;
    link.target = '_blank';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  }

  protected abrirFormHistoria(h?: any): void {
    if (h) {
      this.isNovaHistoria.set(false);
      this.formHistoria.set({
        id: h.id,
        titulo: h.titulo || '',
        texto: h.texto || '',
        dia: h.dia || 1,
        mes: h.mes || 1,
        ano_ref: h.ano_ref || null,
        fonte: h.fonte || ''
      });
    } else {
      this.isNovaHistoria.set(true);
      this.formHistoria.set({
        id: null,
        titulo: '',
        texto: '',
        dia: new Date().getDate(),
        mes: new Date().getMonth() + 1,
        ano_ref: null,
        fonte: ''
      });
    }
    this.showHistoriaForm.set(true);
  }

  protected salvarHistoria(): void {
    const form = this.formHistoria();
    if (!form.titulo || !form.texto || !form.dia || !form.mes) {
      this.setFeedback('Campos obrigatorios de historia ausentes.', 'error');
      return;
    }
    const headers = this.supabaseService.getAuthHeaders();
    const endpoint = this.isNovaHistoria()
      ? `${environment.apiUrl}/api/chancelaria/historias/salvar`
      : `${environment.apiUrl}/api/chancelaria/historias/atualizar`;

    this.http.post<any>(endpoint, form, { headers }).subscribe({
      next: (res) => {
        if (res && res.ok) {
          this.showHistoriaForm.set(false);
          this.carregarDashboard();
          this.setFeedback('Fato historico salvo com sucesso.', 'success');
        } else {
          this.setFeedback(res?.erro || 'Erro ao salvar fato historico.', 'error');
        }
      },
      error: () => this.setFeedback('Erro de rede ao salvar fato historico.', 'error')
    });
  }

  protected excluirHistoria(id: number): void {
    if (!confirm('Deseja excluir permanentemente este fato historico?')) return;
    const headers = this.supabaseService.getAuthHeaders();
    this.http.post<any>(`${environment.apiUrl}/api/chancelaria/historias/excluir`, { id }, { headers }).subscribe({
      next: (res) => {
        if (res && res.ok) {
          this.carregarDashboard();
          this.setFeedback('Fato historico excluido com sucesso.', 'success');
        } else {
          this.setFeedback(res?.erro || 'Erro ao excluir fato historico.', 'error');
        }
      },
      error: () => this.setFeedback('Erro de rede ao excluir fato historico.', 'error')
    });
  }

  protected getTipoBadgeClass(tipo: string): string {
    switch (tipo) {
      case 'Aniversario': return 'bg-amber-500/15 text-amber-400 border border-amber-500/25';
      case 'Iniciacao': return 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/25';
      case 'Elevacao': return 'bg-blue-500/15 text-blue-400 border border-blue-500/25';
      case 'Exaltacao': return 'bg-purple-500/15 text-purple-400 border border-purple-500/25';
      case 'Oriente Eterno': return 'bg-slate-500/20 text-slate-300 border border-slate-500/30';
      case 'Historia': return 'bg-teal-500/15 text-teal-400 border border-teal-500/25';
      case 'Instalacao': return 'bg-orange-500/15 text-orange-400 border border-orange-500/25';
      default: return 'bg-liturgical-gold/10 text-liturgical-gold border border-liturgical-gold/20';
    }
  }

  protected getTipoLabel(tipo: string): string {
    const labels: Record<string, string> = {
      'Aniversario': 'Aniversario',
      'Iniciacao': 'Iniciacao',
      'Elevacao': 'Elevacao',
      'Exaltacao': 'Exaltacao',
      'Instalacao': 'Instalacao',
      'Oriente Eterno': 'Oriente Eterno',
      'Historia': 'Historia',
      'Posse Grao Mestre': 'Posse Grao-Mestre',
      'Concessao de Membro Honorario': 'Membro Honorario',
      'Filiacao': 'Filiacao',
    };
    return labels[tipo] || tipo;
  }

  protected formatarData(data: string): string {
    if (!data) return '';
    const parts = data.split('-');
    if (parts.length === 3) {
      return `${parts[2]}/${parts[1]}/${parts[0]}`;
    }
    return data;
  }

  protected getAnosDesde(dataEvento: string): string {
    if (!dataEvento) return '';
    try {
      const year = parseInt(dataEvento.split('-')[0], 10);
      const current = new Date().getFullYear();
      const diff = current - year;
      if (diff <= 0) return '';
      return `${diff} anos`;
    } catch {
      return '';
    }
  }

  protected previewHtml(): string {
    const escaped = this.escapeHtml(this.mensagemPreview());
    return escaped
      .replace(/&lt;(\/?)(b|strong|i|em|u)&gt;/gi, '<$1$2>')
      .replace(/\n/g, '<br>');
  }

  protected cardImageUrl(card: any): string {
    const imageUrl = String(card?.image_url || '');
    if (!imageUrl) return '';
    const cache = card?._cacheBust || card?.card_hash || card?.cache_key || '';
    const separator = imageUrl.includes('?') ? '&' : '?';
    return `${this.absoluteAssetUrl(imageUrl)}${cache ? `${separator}v=${encodeURIComponent(String(cache))}` : ''}`;
  }

  protected cardStatus(card: any): string {
    if (card?._statusMsg) return String(card._statusMsg);
    if (card?.image_url) return 'Card gerado';
    return 'Previa indisponivel';
  }

  protected feedbackClass(): string {
    switch (this.feedbackTipo()) {
      case 'success': return 'border-emerald-500/25 bg-emerald-500/10 text-emerald-200';
      case 'error': return 'border-rose-500/25 bg-rose-500/10 text-rose-200';
      default: return 'border-blue-500/25 bg-blue-500/10 text-blue-200';
    }
  }

  protected isCategoriaSelecionada(categoria: string): boolean {
    return this.categoriasSelecionadas().includes(categoria);
  }

  private setFeedback(message: string, type: 'success' | 'error' | 'info' = 'info'): void {
    this.feedbackMsg.set(message);
    this.feedbackTipo.set(type);
  }

  private absoluteAssetUrl(path: string): string {
    if (/^https?:\/\//i.test(path)) {
      return path;
    }
    return `${environment.apiUrl}${path}`;
  }

  private escapeHtml(value: string): string {
    return value
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }
}
