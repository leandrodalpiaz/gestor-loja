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

  // Dados
  protected efemeridesHoje = signal<any[]>([]);
  protected efemerides = signal<any[]>([]);
  protected loading = signal(false);
  protected loadingHoje = signal(false);
  protected errorMsg = signal<string | null>(null);

  // Filtros
  protected filtroTermo = signal('');
  protected filtroTipo = signal('');
  protected filtroAtivo = signal('1');

  // Tipos disponíveis
  protected tiposEfemeride = TIPOS_EFEMERIDE;

  // Modal / Formulário
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

  ngOnInit(): void {
    this.carregarHoje();
    this.carregarEfemerides();
  }

  protected carregarHoje(): void {
    this.loadingHoje.set(true);
    const headers = this.supabaseService.getAuthHeaders();

    this.http.get<any>(
      `${environment.apiUrl}/api/chancelaria/efemerides/hoje`,
      { headers }
    ).subscribe({
      next: (res) => {
        if (res && res.ok) {
          this.efemeridesHoje.set(res.registros || []);
        }
        this.loadingHoje.set(false);
      },
      error: (err) => {
        console.error('[Chancelaria] Erro ao buscar efemérides de hoje:', err);
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

    this.http.get<any>(
      `${environment.apiUrl}/api/chancelaria/efemerides?${queryParams}`,
      { headers }
    ).subscribe({
      next: (res) => {
        if (res && res.ok) {
          this.efemerides.set(res.registros || []);
        } else {
          this.errorMsg.set(res?.erro || 'Falha ao carregar efemérides.');
        }
        this.loading.set(false);
      },
      error: (err) => {
        console.error('[Chancelaria] Erro ao buscar efemérides:', err);
        this.errorMsg.set('Erro de conexão ao carregar dados do servidor.');
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
      alert('Nome, Tipo e Data do Evento são obrigatórios.');
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
          this.carregarHoje();
          this.carregarEfemerides();
        } else {
          alert(res?.erro || 'Falha ao salvar efeméride.');
        }
        this.salvando.set(false);
      },
      error: (err) => {
        console.error('[Chancelaria] Erro ao salvar:', err);
        alert('Erro de conexão ao salvar.');
        this.salvando.set(false);
      }
    });
  }

  protected desativar(id: number): void {
    if (!confirm('Deseja desativar esta efeméride? Ela não aparecerá mais nos lembretes diários.')) {
      return;
    }

    const headers = this.supabaseService.getAuthHeaders();
    this.http.post<any>(
      `${environment.apiUrl}/api/chancelaria/efemerides/desativar`,
      { id },
      { headers }
    ).subscribe({
      next: (res) => {
        if (res && res.ok) {
          this.carregarEfemerides();
        } else {
          alert('Falha ao desativar efeméride.');
        }
      },
      error: (err) => {
        console.error('[Chancelaria] Erro ao desativar:', err);
        alert('Erro de conexão.');
      }
    });
  }

  protected excluir(id: number): void {
    if (!confirm('Deseja EXCLUIR permanentemente esta efeméride? Esta ação é irreversível.')) {
      return;
    }

    const headers = this.supabaseService.getAuthHeaders();
    this.http.post<any>(
      `${environment.apiUrl}/api/chancelaria/efemerides/excluir`,
      { id },
      { headers }
    ).subscribe({
      next: (res) => {
        if (res && res.ok) {
          this.carregarHoje();
          this.carregarEfemerides();
        } else {
          alert('Falha ao excluir efeméride.');
        }
      },
      error: (err) => {
        console.error('[Chancelaria] Erro ao excluir:', err);
        alert('Erro de conexão.');
      }
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
      'Aniversario': 'Aniversário',
      'Iniciacao': 'Iniciação',
      'Elevacao': 'Elevação',
      'Exaltacao': 'Exaltação',
      'Instalacao': 'Instalação',
      'Oriente Eterno': 'Oriente Eterno',
      'Historia': 'História',
      'Posse Grao Mestre': 'Posse Grão-Mestre',
      'Concessao de Membro Honorario': 'Membro Honorário',
      'Filiacao': 'Filiação',
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
}
