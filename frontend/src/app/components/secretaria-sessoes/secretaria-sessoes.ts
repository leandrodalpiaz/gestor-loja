import { Component, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { SupabaseService } from '../../services/supabase.service';
import { environment } from '../../../environments/environment';

@Component({
  selector: 'app-secretaria-sessoes',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './secretaria-sessoes.html',
  styleUrl: './secretaria-sessoes.css'
})
export class SecretariaSessoes implements OnInit {
  private http = inject(HttpClient);
  private supabaseService = inject(SupabaseService);

  // Listagem de sessões
  protected sessoesFuturas = signal<any[]>([]);
  protected sessoesRecentes = signal<any[]>([]);
  protected loading = signal(false);
  protected errorMsg = signal<string | null>(null);

  // Modais de controle
  protected showModalSessao = signal(false);
  protected showModalPresenca = signal(false);
  protected showModalBalaustre = signal(false);

  protected isNovaSessao = signal(false);
  protected salvandoSessao = signal(false);

  // Objeto de Sessão em Edição
  protected formSessao = signal<any>({
    id: 0,
    titulo: '',
    data_inicio: '',
    hora_inicio: '',
    hora_fim: '',
    grau_sessao: '3',
    grau_personalizado: '',
    tipo_sessao_principal: 'ordinaria',
    tipo_sessao_subtipo: '',
    traje_tipo: 'terno_escuro',
    agape_modalidade: 'nao_havera',
    agape_modelo_financeiro: 'oficial_loja',
    agape_valor: '',
    natureza_sessao: 'trabalho',
    formato_sessao: 'ritualistica',
    templo_local: 'Templo Principal',
    sessao_branca: false,
    sessao_a_campo: false,
    conta_relatorio_potencia: true,
    observacao_relatorio: '',
    ordem_dia: '',
    observacao_interna: ''
  });

  // Controle de Presença
  protected sessaoFoco = signal<any | null>(null);
  protected mapaPresenca = signal<any[]>([]); // Obreiros do quadro
  protected salvandoPresencas = signal(false);
  
  // Controle de Ágape
  protected presencasAgape = signal<string[]>([]); // Array de obreiro_id que ficaram no ágape
  protected salvandoAgape = signal(false);

  // Controle de Balaustre
  protected balaustre = signal<any | null>(null);
  protected previewTexto = signal<string>('');
  protected salvandoBalaustre = signal(false);
  protected executandoBalaustreAction = signal(false);
  protected balaustreTabActive = signal<'editor' | 'visitantes' | 'preview'>('editor');
  
  // Dados Estruturados do Balaustre
  protected formBalaustre = signal<any>({
    numero_balaustre: '',
    template_versao: 'oficial-v1',
    bloco_abertura: '',
    bloco_balaustre: '',
    bloco_expediente: '',
    bloco_saco_propostas: '',
    bloco_ordem_dia: '',
    bloco_tronco_solidariedade: '',
    bloco_conclusoes_orador: '',
    bloco_encerramento: '',
    bloco_assinaturas: 'Secretário              Guarda da Lei              Venerável Mestre',
    observacoes_secretaria: '',
    // Listas adicionais
    lojas_visitantes_frequentes: '',
    palavra_visitante_nome: [],
    palavra_visitante_loja: [],
    palavra_visitante_fala: [],
    palavra_obreiro_nome: [],
    palavra_obreiro_cargo: [],
    palavra_obreiro_fala: []
  });

  // Novos visitantes temporários para o formulário
  protected novoVisitante = signal<any>({ nome: '', loja: '', fala: '' });
  protected novoObreiroFala = signal<any>({ nome: '', cargo: '', fala: '' });

  ngOnInit(): void {
    this.carregarSessoes();
  }

  protected carregarSessoes(): void {
    this.loading.set(true);
    this.errorMsg.set(null);
    const headers = this.supabaseService.getAuthHeaders();

    this.http.get<any>(
      `${environment.apiUrl}/api/secretaria/sessoes`,
      { headers }
    ).subscribe({
      next: (res) => {
        if (res && res.ok) {
          this.sessoesFuturas.set(res.futuras || []);
          this.sessoesRecentes.set(res.recentes || []);
        } else {
          this.errorMsg.set(res.erro || 'Falha ao carregar sessões.');
        }
        this.loading.set(false);
      },
      error: (err) => {
        console.error('[Secretaria] Erro ao carregar sessões:', err);
        this.errorMsg.set('Erro de conexão ao carregar dados do PHP.');
        this.loading.set(false);
      }
    });
  }

  // AÇÕES SESSÃO
  protected abrirModalSessaoNova(): void {
    this.isNovaSessao.set(true);
    this.formSessao.set({
      id: 0,
      titulo: '',
      data_inicio: new Date().toISOString().split('T')[0],
      hora_inicio: '20:00',
      hora_fim: '22:00',
      grau_sessao: '3',
      grau_personalizado: '',
      tipo_sessao_principal: 'ordinaria',
      tipo_sessao_subtipo: '',
      traje_tipo: 'terno_escuro',
      agape_modalidade: 'nao_havera',
      agape_modelo_financeiro: 'oficial_loja',
      agape_valor: '',
      natureza_sessao: 'trabalho',
      formato_sessao: 'ritualistica',
      templo_local: 'Templo Principal',
      sessao_branca: false,
      sessao_a_campo: false,
      conta_relatorio_potencia: true,
      observacao_relatorio: '',
      ordem_dia: '',
      observacao_interna: ''
    });
    this.showModalSessao.set(true);
  }

  protected abrirModalSessaoEditar(sessao: any): void {
    this.isNovaSessao.set(false);
    
    // Desmembra data_hora_inicio e data_hora_fim
    let dataInicio = '';
    let horaInicio = '';
    let horaFim = '';

    if (sessao.data_hora_inicio) {
      const parts = sessao.data_hora_inicio.split(' ');
      dataInicio = parts[0];
      if (parts[1]) {
        horaInicio = parts[1].substring(0, 5);
      }
    }
    if (sessao.data_hora_fim) {
      const parts = sessao.data_hora_fim.split(' ');
      if (parts[1]) {
        horaFim = parts[1].substring(0, 5);
      }
    }

    this.formSessao.set({
      id: sessao.id,
      titulo: sessao.titulo || '',
      data_inicio: dataInicio,
      hora_inicio: horaInicio,
      hora_fim: horaFim,
      grau_sessao: String(sessao.grau_sessao || '3'),
      grau_personalizado: sessao.grau_personalizado || '',
      tipo_sessao_principal: sessao.tipo_sessao_principal || 'ordinaria',
      tipo_sessao_subtipo: sessao.tipo_sessao_subtipo || '',
      traje_tipo: sessao.traje_tipo || 'terno_escuro',
      agape_modalidade: sessao.agape_modalidade || 'nao_havera',
      agape_modelo_financeiro: sessao.agape_modelo_financeiro || 'oficial_loja',
      agape_valor: sessao.agape_valor || '',
      natureza_sessao: sessao.natureza_sessao || 'trabalho',
      formato_sessao: sessao.formato_sessao || 'ritualistica',
      templo_local: sessao.templo_local || 'Templo Principal',
      sessao_branca: !!sessao.sessao_branca,
      sessao_a_campo: !!sessao.sessao_a_campo,
      conta_relatorio_potencia: !!sessao.conta_relatorio_potencia,
      observacao_relatorio: sessao.observacao_relatorio || '',
      ordem_dia: sessao.ordem_dia || '',
      observacao_interna: sessao.observacao_interna || ''
    });

    this.showModalSessao.set(true);
  }

  protected salvarDadosSessao(): void {
    const payload = this.formSessao();
    if (!payload.data_inicio || !payload.hora_inicio) {
      alert('Data e Hora de Início são obrigatórias.');
      return;
    }

    this.salvandoSessao.set(true);
    const headers = this.supabaseService.getAuthHeaders();

    this.http.post<any>(
      `${environment.apiUrl}/api/secretaria/sessoes/salvar`,
      payload,
      { headers }
    ).subscribe({
      next: (res) => {
        if (res && res.ok) {
          this.showModalSessao.set(false);
          this.carregarSessoes();
        } else {
          alert(res.erro || 'Falha ao salvar sessão.');
        }
        this.salvandoSessao.set(false);
      },
      error: (err) => {
        console.error('[Secretaria] Erro ao salvar sessão:', err);
        alert('Erro de conexão ao salvar.');
        this.salvandoSessao.set(false);
      }
    });
  }

  protected executarSessaoAction(id: number, action: 'cancelar' | 'reabrir' | 'publicar' | 'realizar'): void {
    let msg = 'Deseja realmente executar esta ação na sessão?';
    if (action === 'cancelar') msg = 'Deseja realmente CANCELAR esta sessão?';
    if (action === 'publicar') msg = 'Deseja publicar e notificar esta sessão?';

    if (!confirm(msg)) return;

    const headers = this.supabaseService.getAuthHeaders();
    this.http.post<any>(
      `${environment.apiUrl}/api/secretaria/sessoes/${id}/action`,
      { action },
      { headers }
    ).subscribe({
      next: (res) => {
        if (res && res.ok) {
          this.carregarSessoes();
        } else {
          alert(res.erro || 'Falha ao realizar ação na sessão.');
        }
      },
      error: (err) => {
        console.error('[Secretaria] Erro ao disparar ação na sessão:', err);
        alert('Erro de conexão.');
      }
    });
  }

  // GERENCIAR PRESENÇA
  protected abrirModalPresenca(sessao: any): void {
    this.sessaoFoco.set(sessao);
    this.loading.set(true);
    const headers = this.supabaseService.getAuthHeaders();

    this.http.get<any>(
      `${environment.apiUrl}/api/secretaria/sessoes/${sessao.id}`,
      { headers }
    ).subscribe({
      next: (res) => {
        if (res && res.ok) {
          this.mapaPresenca.set(res.mapa_presenca || []);
          
          // Hidrata lista de presença de ágape com os obreiros ativos retornados
          const noAgape = (res.presencas_agape || [])
            .filter((p: any) => p.presente_agape === true || String(p.presente_agape) === 'true')
            .map((p: any) => p.obreiro_id);
          this.presencasAgape.set(noAgape);
          
          this.showModalPresenca.set(true);
        } else {
          alert(res.erro || 'Falha ao carregar detalhes da presença.');
        }
        this.loading.set(false);
      },
      error: (err) => {
        console.error('[Secretaria] Erro ao carregar mapa de presença:', err);
        alert('Erro ao conectar.');
        this.loading.set(false);
      }
    });
  }

  protected salvarPresencasFisicas(): void {
    const sessao = this.sessaoFoco();
    if (!sessao) return;

    this.salvandoPresencas.set(true);
    const headers = this.supabaseService.getAuthHeaders();
    
    // Filtra apenas o mapa formatado
    const payload = {
      presencas: this.mapaPresenca().map(p => ({
        obreiro_id: p.id,
        presente: p.presente === true || String(p.presente) === 'true',
        observacao: p.observacao || null
      }))
    };

    this.http.post<any>(
      `${environment.apiUrl}/api/secretaria/sessoes/${sessao.id}/presenca`,
      payload,
      { headers }
    ).subscribe({
      next: (res) => {
        if (res && res.ok) {
          alert('Ficha de presenças ritualísticas salva com sucesso.');
          this.showModalPresenca.set(false);
          this.carregarSessoes();
        } else {
          alert(res.erro || 'Falha ao salvar presença.');
        }
        this.salvandoPresencas.set(false);
      },
      error: (err) => {
        console.error('[Secretaria] Erro ao salvar presenças:', err);
        alert('Erro de conexão.');
        this.salvandoPresencas.set(false);
      }
    });
  }

  protected toggleAgapePresenca(obreiroId: string): void {
    const list = [...this.presencasAgape()];
    const index = list.indexOf(obreiroId);
    if (index > -1) {
      list.splice(index, 1);
    } else {
      list.push(obreiroId);
    }
    this.presencasAgape.set(list);
  }

  protected salvarPresencaAgape(): void {
    const sessao = this.sessaoFoco();
    if (!sessao) return;

    this.salvandoAgape.set(true);
    const headers = this.supabaseService.getAuthHeaders();
    
    const payload = {
      presentes_agape_obreiro_ids: this.presencasAgape()
    };

    this.http.post<any>(
      `${environment.apiUrl}/api/secretaria/sessoes/${sessao.id}/agape`,
      payload,
      { headers }
    ).subscribe({
      next: (res) => {
        if (res && res.ok) {
          alert('Lista de presença do banquete ágape atualizada.');
          this.showModalPresenca.set(false);
        } else {
          alert(res.erro || 'Falha ao salvar presença no ágape.');
        }
        this.salvandoAgape.set(false);
      },
      error: (err) => {
        console.error('[Secretaria] Erro ao salvar ágape:', err);
        alert('Erro de conexão.');
        this.salvandoAgape.set(false);
      }
    });
  }

  // GERENCIAR BALAUSTRE (ATA)
  protected abrirModalBalaustre(sessao: any): void {
    this.sessaoFoco.set(sessao);
    this.loading.set(true);
    this.balaustreTabActive.set('editor');
    const headers = this.supabaseService.getAuthHeaders();

    this.http.get<any>(
      `${environment.apiUrl}/api/secretaria/sessoes/${sessao.id}/balaustre`,
      { headers }
    ).subscribe({
      next: (res) => {
        if (res && res.ok) {
          this.balaustre.set(res.balaustre || null);
          this.previewTexto.set(res.preview || '');
          
          const d = res.dados_capturados || {};
          const b = d.blocos || {};

          // Carrega ou inicializa dados do formulário
          this.formBalaustre.set({
            numero_balaustre: res.balaustre?.numero_balaustre || '',
            template_versao: res.balaustre?.template_versao || 'oficial-v1',
            bloco_abertura: b.abertura || '',
            bloco_balaustre: b.balaustre || '',
            bloco_expediente: b.expediente || '',
            bloco_saco_propostas: b.saco_propostas || '',
            bloco_ordem_dia: b.ordem_dia || sessao.ordem_dia || '',
            bloco_tronco_solidariedade: b.tronco_solidariedade || '',
            bloco_conclusoes_orador: b.conclusoes_orador || '',
            bloco_encerramento: b.encerramento || '',
            bloco_assinaturas: b.assinaturas || 'Secretário              Guarda da Lei              Venerável Mestre',
            observacoes_secretaria: d.observacoes_secretaria || '',
            
            // Listas internas
            lojas_visitantes_frequentes: d.palavra_bem_ordem?.lojas_frequentes?.join('\n') || '',
            
            palavra_visitante_nome: d.palavra_bem_ordem?.visitantes?.map((v: any) => v.nome) || [],
            palavra_visitante_loja: d.palavra_bem_ordem?.visitantes?.map((v: any) => v.loja) || [],
            palavra_visitante_fala: d.palavra_bem_ordem?.visitantes?.map((v: any) => v.fala_resumida) || [],
            
            palavra_obreiro_nome: d.palavra_bem_ordem?.obreiros?.map((o: any) => o.nome) || [],
            palavra_obreiro_cargo: d.palavra_bem_ordem?.obreiros?.map((o: any) => o.cargo_no_momento) || [],
            palavra_obreiro_fala: d.palavra_bem_ordem?.obreiros?.map((o: any) => o.fala_resumida) || []
          });

          this.showModalBalaustre.set(true);
        } else {
          alert(res.erro || 'Falha ao buscar dados do balaustre.');
        }
        this.loading.set(false);
      },
      error: (err) => {
        console.error('[Secretaria] Erro ao carregar balaustre:', err);
        alert('Erro ao conectar.');
        this.loading.set(false);
      }
    });
  }

  protected adicionarVisitanteForm(): void {
    const v = this.novoVisitante();
    if (!v.nome) return;

    const f = this.formBalaustre();
    f.palavra_visitante_nome.push(v.nome);
    f.palavra_visitante_loja.push(v.loja || '');
    f.palavra_visitante_fala.push(v.fala || '');
    
    this.formBalaustre.set({ ...f });
    this.novoVisitante.set({ nome: '', loja: '', fala: '' });
  }

  protected removerVisitanteForm(index: number): void {
    const f = this.formBalaustre();
    f.palavra_visitante_nome.splice(index, 1);
    f.palavra_visitante_loja.splice(index, 1);
    f.palavra_visitante_fala.splice(index, 1);
    this.formBalaustre.set({ ...f });
  }

  protected adicionarObreiroFalaForm(): void {
    const o = this.novoObreiroFala();
    if (!o.nome) return;

    const f = this.formBalaustre();
    f.palavra_obreiro_nome.push(o.nome);
    f.palavra_obreiro_cargo.push(o.cargo || '');
    f.palavra_obreiro_fala.push(o.fala || '');
    
    this.formBalaustre.set({ ...f });
    this.novoObreiroFala.set({ nome: '', cargo: '', fala: '' });
  }

  protected removerObreiroFalaForm(index: number): void {
    const f = this.formBalaustre();
    f.palavra_obreiro_nome.splice(index, 1);
    f.palavra_obreiro_cargo.splice(index, 1);
    f.palavra_obreiro_fala.splice(index, 1);
    this.formBalaustre.set({ ...f });
  }

  protected salvarDraftBalaustre(): void {
    const sessao = this.sessaoFoco();
    if (!sessao) return;

    this.salvandoBalaustre.set(true);
    const headers = this.supabaseService.getAuthHeaders();

    this.http.post<any>(
      `${environment.apiUrl}/api/secretaria/sessoes/${sessao.id}/balaustre/salvar`,
      this.formBalaustre(),
      { headers }
    ).subscribe({
      next: (res) => {
        if (res && res.ok) {
          alert('Rascunho do Balaustre salvo no banco com sucesso.');
          
          // Re-busca o balaustre para atualizar o preview
          this.http.get<any>(
            `${environment.apiUrl}/api/secretaria/sessoes/${sessao.id}/balaustre`,
            { headers }
          ).subscribe({
            next: r => {
              if (r.ok) {
                this.previewTexto.set(r.preview || '');
                this.balaustre.set(r.balaustre);
              }
            },
            error: () => console.error('Falha ao atualizar a prévia do balaústre após salvar.')
          });
        } else {
          alert(res.erro || 'Falha ao salvar rascunho.');
        }
        this.salvandoBalaustre.set(false);
      },
      error: (err) => {
        console.error('[Secretaria] Erro ao salvar balaustre:', err);
        alert('Erro de conexão.');
        this.salvandoBalaustre.set(false);
      }
    });
  }

  protected executarBalaustreAction(action: 'marcar-apto' | 'abrir-votacao' | 'encerrar-votacao'): void {
    const sessao = this.sessaoFoco();
    if (!sessao) return;

    this.executandoBalaustreAction.set(true);
    const headers = this.supabaseService.getAuthHeaders();

    this.http.post<any>(
      `${environment.apiUrl}/api/secretaria/sessoes/${sessao.id}/balaustre/action`,
      { action },
      { headers }
    ).subscribe({
      next: (res) => {
        if (res && res.ok) {
          alert('Operação no Balaustre concluída: ' + action);
          this.showModalBalaustre.set(false);
          this.carregarSessoes();
        } else {
          alert(res.erro || 'Falha ao executar ação no balaustre.');
        }
        this.executandoBalaustreAction.set(false);
      },
      error: (err) => {
        console.error('[Secretaria] Erro ao executar ação no balaustre:', err);
        alert('Erro de conexão.');
        this.executandoBalaustreAction.set(false);
      }
    });
  }

  protected getGrauLabel(grau: any): string {
    switch (String(grau)) {
      case '1': return 'A.';
      case '2': return 'C.';
      case '3': return 'M.';
      default: return 'G.' + grau;
    }
  }

  protected getSessaoStatusBadge(status: string): string {
    switch (String(status).toLowerCase()) {
      case 'realizada': return 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20';
      case 'publicada': return 'bg-blue-500/10 text-blue-400 border border-blue-500/20';
      case 'cancelada': return 'bg-rose-500/10 text-rose-400 border border-rose-500/20';
      default: return 'bg-amber-500/10 text-amber-400 border border-amber-500/20';
    }
  }

  protected getBalaustreStatusBadge(status: string): string {
    switch (String(status).toLowerCase()) {
      case 'aprovado': return 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20';
      case 'em_votacao': return 'bg-purple-500/10 text-purple-400 border border-purple-500/20';
      case 'apto_votacao': return 'bg-blue-500/10 text-blue-400 border border-blue-500/20';
      default: return 'bg-amber-500/10 text-amber-400 border border-amber-500/20';
    }
  }
}
