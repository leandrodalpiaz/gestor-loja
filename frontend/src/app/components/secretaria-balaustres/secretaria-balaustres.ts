import { Component, OnInit, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { FormsModule } from '@angular/forms';
import { environment } from '../../../environments/environment';
import { SupabaseService } from '../../services/supabase.service';

export interface BalaustreItem {
  id: number;
  numero_balaustre: string;
  status: string;
  updated_at: string;
  sessao_titulo: string;
  data_hora_inicio: string;
  grau_sessao: string;
}

type BalaustreTab = 'blocos' | 'nominata' | 'palavra';

@Component({
  selector: 'app-secretaria-balaustres',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './secretaria-balaustres.html',
  styleUrl: './secretaria-balaustres.css'
})
export class SecretariaBalaustres implements OnInit {
  private http = inject(HttpClient);
  protected supabaseService = inject(SupabaseService);

  protected loading = signal(true);
  protected errorMsg = signal<string | null>(null);
  protected successMsg = signal<string | null>(null);

  protected balaustres = signal<BalaustreItem[]>([]);
  protected sessoesRecentes = signal<any[]>([]);
  protected obreirosList = signal<any[]>([]);

  protected selectedSessaoId = signal<number>(-1);
  protected balaustreId = signal<number>(0);
  protected numeroBalaustre = signal<string>('');
  protected templateVersao = signal<string>('oficial-v1');
  protected previewTexto = signal<string>('');
  protected currentBalaustreStatus = signal<string>('');
  protected activeTab: BalaustreTab = 'blocos';
  protected activeBlocoIndex = signal<number>(0);
  protected blocoTransitionDir = signal<'next' | 'prev'>('next');

  protected blocos: Record<string, string> = this.buildEmptyBlocos();

  protected cargosSessao = signal<any[]>([]);
  protected palavraObreiros = signal<any[]>([]);
  protected palavraVisitantes = signal<any[]>([]);

  ngOnInit(): void {
    this.carregarInicial();
  }

  protected carregarInicial(): void {
    this.loading.set(true);
    this.errorMsg.set(null);

    const headers = this.supabaseService.getAuthHeaders();
    this.http.get<any>(`${environment.apiUrl}/api/secretaria/dashboard`, { headers }).subscribe({
      next: (res) => {
        if (res?.ok) {
          this.balaustres.set(res.balaustres || []);
          this.sessoesRecentes.set(res.sessoes_recentes || []);
        }
        this.carregarObreiros();
      },
      error: (err) => {
        this.loading.set(false);
        this.errorMsg.set(err.error?.erro || 'Erro ao carregar o livro de balaústres.');
      }
    });
  }

  private carregarObreiros(): void {
    const headers = this.supabaseService.getAuthHeaders();
    this.http.get<any>(`${environment.apiUrl}/api/secretaria/obreiros`, { headers }).subscribe({
      next: (res) => {
        this.loading.set(false);
        if (res?.ok) {
          this.obreirosList.set(res.obreiros || []);
        }
      },
      error: (err) => {
        this.loading.set(false);
        this.errorMsg.set(err.error?.erro || 'Erro ao carregar a lista de obreiros.');
      }
    });
  }

  protected onSessaoSelect(value: string | number): void {
    const sessaoId = Number(value);
    this.selectedSessaoId.set(Number.isNaN(sessaoId) ? -1 : sessaoId);
    this.onSessaoChange();
  }

  protected onSessaoChange(): void {
    const sessaoId = this.selectedSessaoId();
    this.successMsg.set(null);
    this.errorMsg.set(null);

    if (sessaoId < 0) {
      this.resetEditor();
      return;
    }

    this.carregarBalaustre(sessaoId);
  }

  protected onTemplateVersaoChange(value: string): void {
    this.templateVersao.set(value);
  }

  protected setActiveTab(tab: BalaustreTab): void {
    this.activeTab = tab;
  }

  protected updateBloco(campo: string, valor: string): void {
    this.blocos = {
      ...this.blocos,
      [campo]: valor
    };
  }

  protected get blocoAtual() {
    return this.blocosConfig[this.activeBlocoIndex()];
  }

  protected get blocoProgresso(): number {
    return Math.round(((this.activeBlocoIndex() + 1) / this.blocosConfig.length) * 100);
  }

  protected blocoEstaPreenchido(campo: string): boolean {
    return (this.blocos[campo] || '').trim().length > 0;
  }

  protected blocoDotClass(idx: number, campo: string): string {
    if (idx === this.activeBlocoIndex()) {
      return 'bg-liturgical-gold';
    }
    return this.blocoEstaPreenchido(campo) ? 'bg-emerald-600/60' : 'bg-white/10';
  }

  protected irParaBloco(index: number): void {
    if (index < 0 || index >= this.blocosConfig.length || index === this.activeBlocoIndex()) {
      return;
    }
    this.blocoTransitionDir.set(index > this.activeBlocoIndex() ? 'next' : 'prev');
    this.activeBlocoIndex.set(index);
  }

  protected proximoBloco(): void {
    this.irParaBloco(this.activeBlocoIndex() + 1);
  }

  protected blocoAnterior(): void {
    this.irParaBloco(this.activeBlocoIndex() - 1);
  }

  protected carregarBalaustre(sessaoId: number): void {
    this.loading.set(true);
    this.errorMsg.set(null);
    this.successMsg.set(null);

    const headers = this.supabaseService.getAuthHeaders();
    this.http.get<any>(`${environment.apiUrl}/api/secretaria/sessoes/${sessaoId}/balaustre`, { headers }).subscribe({
      next: (res) => {
        this.loading.set(false);

        if (!res?.ok) {
          this.errorMsg.set('Não foi possível carregar o contexto do balaústre.');
          return;
        }

        const balaustre = res.balaustre || {};
        const dados = res.dados_capturados || {};
        const blocos = dados.blocos || {};

        this.balaustreId.set(balaustre.id || 0);
        this.numeroBalaustre.set(balaustre.numero_balaustre || '');
        this.templateVersao.set(balaustre.template_versao || 'oficial-v1');
        this.previewTexto.set(res.preview || '');
        this.currentBalaustreStatus.set(balaustre.status || 'rascunho');

        this.blocos = {
          abertura: blocos.abertura || '',
          balaustre: blocos.balaustre || '',
          expediente: blocos.expediente || '',
          saco_propostas: blocos.saco_propostas || '',
          ordem_dia: blocos.ordem_dia || '',
          tronco_solidariedade: blocos.tronco_solidariedade || '',
          conclusoes_orador: blocos.conclusoes_orador || '',
          encerramento: blocos.encerramento || '',
          assinaturas: blocos.assinaturas || this.defaultAssinaturas()
        };

        this.cargosSessao.set(
          Array.isArray(dados.cargos_sessao) && dados.cargos_sessao.length > 0
            ? dados.cargos_sessao
            : this.getBaseCargos()
        );

        const obreiros = Array.isArray(dados.palavra_bem_ordem?.obreiros)
          ? [...dados.palavra_bem_ordem.obreiros]
          : [];
        while (obreiros.length < 3) {
          obreiros.push({ obreiro_id: '', nome: '', cargo_no_momento: '', fala_resumida: '' });
        }
        this.palavraObreiros.set(obreiros);

        const visitantes = Array.isArray(dados.palavra_bem_ordem?.visitantes)
          ? [...dados.palavra_bem_ordem.visitantes]
          : [];
        while (visitantes.length < 3) {
          visitantes.push({
            nome: '',
            grau: '',
            loja: '',
            oriente: '',
            potencia: '',
            dia_reuniao: '',
            fala_resumida: ''
          });
        }
        this.palavraVisitantes.set(visitantes);
        this.activeBlocoIndex.set(0);
      },
      error: (err) => {
        this.loading.set(false);
        this.errorMsg.set(err.error?.erro || 'Erro ao carregar o balaústre da sessão.');
      }
    });
  }

  protected adicionarLinhaObreiro(): void {
    this.palavraObreiros.set([
      ...this.palavraObreiros(),
      { obreiro_id: '', nome: '', cargo_no_momento: '', fala_resumida: '' }
    ]);
  }

  protected removerLinhaObreiro(index: number): void {
    this.palavraObreiros.set(this.palavraObreiros().filter((_, i) => i !== index));
  }

  protected adicionarLinhaVisitante(): void {
    this.palavraVisitantes.set([
      ...this.palavraVisitantes(),
      {
        nome: '',
        grau: '',
        loja: '',
        oriente: '',
        potencia: '',
        dia_reuniao: '',
        fala_resumida: ''
      }
    ]);
  }

  protected removerLinhaVisitante(index: number): void {
    this.palavraVisitantes.set(this.palavraVisitantes().filter((_, i) => i !== index));
  }

  protected salvarBalaustre(): void {
    if (this.selectedSessaoId() < 0) {
      this.errorMsg.set('Selecione uma sessão válida antes de salvar.');
      return;
    }

    this.loading.set(true);
    this.errorMsg.set(null);
    this.successMsg.set(null);

    const headers = this.supabaseService.getAuthHeaders();
    const payload = {
      balaustre_id: this.balaustreId(),
      numero_balaustre: this.numeroBalaustre(),
      template_versao: this.templateVersao(),
      bloco_abertura: this.blocos['abertura'],
      bloco_balaustre: this.blocos['balaustre'],
      bloco_expediente: this.blocos['expediente'],
      bloco_saco_propostas: this.blocos['saco_propostas'],
      bloco_ordem_dia: this.blocos['ordem_dia'],
      bloco_tronco_solidariedade: this.blocos['tronco_solidariedade'],
      bloco_conclusoes_orador: this.blocos['conclusoes_orador'],
      bloco_encerramento: this.blocos['encerramento'],
      bloco_assinaturas: this.blocos['assinaturas'],
      cargo_sessao_codigo: this.cargosSessao().map((cargo) => cargo.codigo || ''),
      cargo_sessao_nome: this.cargosSessao().map((cargo) => cargo.cargo_nome || ''),
      cargo_sessao_titular_oficial: this.cargosSessao().map((cargo) => cargo.titular_oficial || ''),
      cargo_sessao_ocupante_nome: this.cargosSessao().map((cargo) => cargo.ocupante_nome || ''),
      cargo_sessao_observacao: this.cargosSessao().map((cargo) => cargo.observacao || ''),
      palavra_obreiro_id: this.palavraObreiros().map((obreiro) => obreiro.obreiro_id || ''),
      palavra_obreiro_nome: this.palavraObreiros().map((obreiro) => obreiro.nome || ''),
      palavra_obreiro_cargo: this.palavraObreiros().map((obreiro) => obreiro.cargo_no_momento || ''),
      palavra_obreiro_fala: this.palavraObreiros().map((obreiro) => obreiro.fala_resumida || ''),
      palavra_visitante_nome: this.palavraVisitantes().map((visitante) => visitante.nome || ''),
      palavra_visitante_grau: this.palavraVisitantes().map((visitante) => visitante.grau || ''),
      palavra_visitante_loja: this.palavraVisitantes().map((visitante) => visitante.loja || ''),
      palavra_visitante_oriente: this.palavraVisitantes().map((visitante) => visitante.oriente || ''),
      palavra_visitante_potencia: this.palavraVisitantes().map((visitante) => visitante.potencia || ''),
      palavra_visitante_dia_reuniao: this.palavraVisitantes().map((visitante) => visitante.dia_reuniao || ''),
      palavra_visitante_fala: this.palavraVisitantes().map((visitante) => visitante.fala_resumida || '')
    };

    this.http.post<any>(
      `${environment.apiUrl}/api/secretaria/sessoes/${this.selectedSessaoId()}/balaustre/salvar`,
      payload,
      { headers }
    ).subscribe({
      next: (res) => {
        this.loading.set(false);
        if (!res?.ok) {
          this.errorMsg.set(res?.erro || 'Falha ao salvar o balaústre.');
          return;
        }

        this.successMsg.set('Rascunho oficial do balaústre salvo com sucesso.');
        this.carregarBalaustre(this.selectedSessaoId());
        this.carregarInicial();
      },
      error: (err) => {
        this.loading.set(false);
        this.errorMsg.set(err.error?.erro || 'Erro de rede ao salvar o balaústre.');
      }
    });
  }

  protected executarAcao(action: 'marcar-apto' | 'abrir-votacao' | 'encerrar-votacao'): void {
    if (this.selectedSessaoId() < 0) {
      this.errorMsg.set('Selecione uma sessão válida antes de executar a ação.');
      return;
    }

    this.loading.set(true);
    this.errorMsg.set(null);
    this.successMsg.set(null);

    const headers = this.supabaseService.getAuthHeaders();
    this.http.post<any>(
      `${environment.apiUrl}/api/secretaria/sessoes/${this.selectedSessaoId()}/balaustre/action`,
      { action },
      { headers }
    ).subscribe({
      next: (res) => {
        this.loading.set(false);
        if (!res?.ok) {
          this.errorMsg.set(res?.erro || 'Não foi possível concluir a ação.');
          return;
        }

        this.successMsg.set(`Ação "${action}" executada com sucesso.`);
        this.carregarBalaustre(this.selectedSessaoId());
      },
      error: (err) => {
        this.loading.set(false);
        this.errorMsg.set(err.error?.erro || 'Erro ao processar a ação.');
      }
    });
  }

  protected readonly blocosConfig = [
    {
      campo: 'abertura',
      titulo: 'Abertura',
      ajuda: 'Registre a abertura ritualística, data, horário, grau e presidência da sessão.',
      placeholder: 'Ex: Às 20h00, no Templo da A.R.L.S. Renascença, sob a presidência do Venerável Mestre, foram abertos os trabalhos no grau correspondente...'
    },
    {
      campo: 'balaustre',
      titulo: 'Leitura do Balaústre Anterior',
      ajuda: 'Descreva a leitura, discussão e aprovação da ata anterior.',
      placeholder: 'Ex: Foi lido o balaústre da sessão anterior, colocado em discussão e aprovado sem restrições...'
    },
    {
      campo: 'expediente',
      titulo: 'Expediente',
      ajuda: 'Inclua correspondências, pranchas, decretos, convites e comunicações oficiais.',
      placeholder: 'Ex: Foram lidas as circulares da Potência, prancha da Loja coirmã e convite para sessão magna...'
    },
    {
      campo: 'saco_propostas',
      titulo: 'Saco de Propostas e Informações',
      ajuda: 'Registre propostas, notícias e peças recolhidas no saco.',
      placeholder: 'Ex: O saco produziu pedido de auxílio, proposta administrativa e comunicação fraterna...'
    },
    {
      campo: 'ordem_dia',
      titulo: 'Ordem do Dia',
      ajuda: 'Descreva trabalhos, peças, debates, votações e deliberações centrais da sessão.',
      placeholder: 'Ex: O Irmão apresentou peça de arquitetura, seguida de debates e votação das propostas da noite...'
    },
    {
      campo: 'tronco_solidariedade',
      titulo: 'Tronco de Solidariedade',
      ajuda: 'Informe a circulação do tronco, valores e destinação beneficente.',
      placeholder: 'Ex: O tronco circulou e arrecadou o valor destinado à Hospitália...'
    },
    {
      campo: 'conclusoes_orador',
      titulo: 'Conclusões do Orador',
      ajuda: 'Registre o parecer final do Orador sobre a justiça e perfeição dos trabalhos.',
      placeholder: 'Ex: O Orador concluiu que os trabalhos transcorreram justos e perfeitos...'
    },
    {
      campo: 'encerramento',
      titulo: 'Encerramento',
      ajuda: 'Feche com o encerramento ritualístico, horário e observações finais.',
      placeholder: 'Ex: Nada mais havendo, o Venerável Mestre encerrou os trabalhos às 22h00 na forma da lei...'
    },
    {
      campo: 'assinaturas',
      titulo: 'Assinaturas Oficiais',
      ajuda: 'Defina a linha de assinatura do documento final.',
      placeholder: this.defaultAssinaturas()
    }
  ];

  private resetEditor(): void {
    this.balaustreId.set(0);
    this.numeroBalaustre.set('');
    this.templateVersao.set('oficial-v1');
    this.previewTexto.set('');
    this.currentBalaustreStatus.set('');
    this.blocos = this.buildEmptyBlocos();
    this.cargosSessao.set(this.getBaseCargos());
    this.palavraObreiros.set([]);
    this.palavraVisitantes.set([]);
    this.activeBlocoIndex.set(0);
  }

  private buildEmptyBlocos(): Record<string, string> {
    return {
      abertura: '',
      balaustre: '',
      expediente: '',
      saco_propostas: '',
      ordem_dia: '',
      tronco_solidariedade: '',
      conclusoes_orador: '',
      encerramento: '',
      assinaturas: this.defaultAssinaturas()
    };
  }

  private defaultAssinaturas(): string {
    return 'Secretário              Guarda da Lei              Venerável Mestre';
  }

  private getBaseCargos(): any[] {
    return [
      { codigo: 'VENERAVEL', cargo_nome: 'Venerável Mestre', titular_oficial: '', ocupante_nome: '', observacao: '' },
      { codigo: 'PRIMEIRO_VIGILANTE', cargo_nome: '1º Vigilante', titular_oficial: '', ocupante_nome: '', observacao: '' },
      { codigo: 'SEGUNDO_VIGILANTE', cargo_nome: '2º Vigilante', titular_oficial: '', ocupante_nome: '', observacao: '' },
      { codigo: 'ORADOR', cargo_nome: 'Orador', titular_oficial: '', ocupante_nome: '', observacao: '' },
      { codigo: 'GUARDA_DA_LEI', cargo_nome: 'Guarda da Lei', titular_oficial: '', ocupante_nome: '', observacao: '' },
      { codigo: 'SECRETARIO', cargo_nome: 'Secretário', titular_oficial: '', ocupante_nome: '', observacao: '' },
      { codigo: 'TESOUREIRO', cargo_nome: 'Tesoureiro', titular_oficial: '', ocupante_nome: '', observacao: '' },
      { codigo: 'CHANCELER', cargo_nome: 'Chanceler', titular_oficial: '', ocupante_nome: '', observacao: '' },
      { codigo: 'MESTRE_BANQUETES', cargo_nome: 'Mestre de Banquetes', titular_oficial: '', ocupante_nome: '', observacao: '' },
      { codigo: 'MESTRE_DE_CERIMONIAS', cargo_nome: 'Mestre de Cerimônias', titular_oficial: '', ocupante_nome: '', observacao: '' },
      { codigo: 'GUARDA_DO_TEMPLO', cargo_nome: 'Guarda do Templo', titular_oficial: '', ocupante_nome: '', observacao: '' },
      { codigo: 'HOSPITALEIRO', cargo_nome: 'Hospitaleiro', titular_oficial: '', ocupante_nome: '', observacao: '' },
      { codigo: 'PRIMEIRO_DIACONO', cargo_nome: '1º Diácono', titular_oficial: '', ocupante_nome: '', observacao: '' },
      { codigo: 'SEGUNDO_DIACONO', cargo_nome: '2º Diácono', titular_oficial: '', ocupante_nome: '', observacao: '' },
      { codigo: 'ARQUITETO', cargo_nome: 'Arquiteto', titular_oficial: '', ocupante_nome: '', observacao: '' },
      { codigo: 'MESTRE_DE_HARMONIA', cargo_nome: 'Mestre de Harmonia', titular_oficial: '', ocupante_nome: '', observacao: '' },
      { codigo: 'PORTA_BANDEIRA', cargo_nome: 'Porta-Bandeira', titular_oficial: '', ocupante_nome: '', observacao: '' },
      { codigo: 'PORTA_ESPADA', cargo_nome: 'Porta-Espada', titular_oficial: '', ocupante_nome: '', observacao: '' },
      { codigo: 'COBRIDOR_INTERNO', cargo_nome: 'Cobridor Interno', titular_oficial: '', ocupante_nome: '', observacao: '' },
      { codigo: 'COBRIDOR_EXTERNO', cargo_nome: 'Cobridor Externo', titular_oficial: '', ocupante_nome: '', observacao: '' }
    ];
  }
}
