import { Component, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { SupabaseService } from '../../services/supabase.service';
import { environment } from '../../../environments/environment';
import { Router } from '@angular/router';

@Component({
  selector: 'app-secretaria-obreiros',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './secretaria-obreiros.html',
  styleUrl: './secretaria-obreiros.css'
})
export class SecretariaObreiros implements OnInit {
  private http = inject(HttpClient);
  private supabaseService = inject(SupabaseService);
  private router = inject(Router);

  // Filtros e listagem
  protected obreiros = signal<any[]>([]);
  protected resumo = signal<any>({});
  protected cargos = signal<any[]>([]);
  protected loading = signal(false);
  protected errorMsg = signal<string | null>(null);

  protected filtroBusca = signal('');
  protected filtroSituacao = signal('');
  protected filtroGrau = signal('');
  protected filtroAlerta = signal('');
  protected filtroCargo = signal('');
  protected filtroOrdenacao = signal('nome');

  // Controle de Abas e Modais
  protected showModalEditar = signal(false);
  protected isNovo = signal(false);
  protected editTabActive = signal<'pessoais' | 'maconicos' | 'outros'>('pessoais');
  protected salvandoObreiro = signal(false);

  // Objeto em edição
  protected formObreiro = signal<any>({
    id: '',
    cim: '',
    nome: '',
    nome_historico: '',
    cpf: '',
    data_nascimento_civil: '',
    estado_civil: 'casado',
    profissao: '',
    escolaridade: '',
    faixa_renda: '',
    grau: 'Aprendiz',
    cargo: '',
    loja_origem: '',
    situacao_quadro: 'ativo',
    acesso_status: 'ativo',
    ativo: true,
    data_iniciacao: '',
    data_elevacao: '',
    data_exaltacao: '',
    data_filiacao: '',
    data_regularizacao: '',
    telefone: '',
    email: '',
    telegram_id: '',
    observacao_secretaria: '',
    potencia_nome: '',
    potencia_sigla: '',
    numero_quadro: '',
    potencia_login: '',
    acesso_potencia_liberado: false
  });

  ngOnInit(): void {
    this.carregarObreiros();
  }

  protected pode(permissao: string): boolean {
    const profile = this.supabaseService.profile() || {};
    const permissions = new Set<string>(profile.permissions || []);
    return profile.is_system_admin === true || permissions.has('*') || permissions.has(permissao);
  }

  protected abrirConvites(): void {
    void this.router.navigate(['/dashboard/secretaria/convites']);
  }

  protected carregarObreiros(): void {
    this.loading.set(true);
    this.errorMsg.set(null);
    const headers = this.supabaseService.getAuthHeaders();

    const queryParams = new URLSearchParams({
      busca: this.filtroBusca(),
      situacao: this.filtroSituacao(),
      grau: this.filtroGrau(),
      alerta: this.filtroAlerta(),
      cargo_codigo: this.filtroCargo(),
      ordenacao: this.filtroOrdenacao()
    }).toString();

    this.http.get<any>(
      `${environment.apiUrl}/api/secretaria/obreiros?${queryParams}`,
      { headers }
    ).subscribe({
      next: (res) => {
        if (res && res.ok) {
          this.obreiros.set(res.obreiros || []);
          this.resumo.set(res.resumo || {});
          this.cargos.set(res.cargos || []);
        } else {
          this.errorMsg.set(res.erro || 'Falha ao carregar listagem de obreiros.');
        }
        this.loading.set(false);
      },
      error: (err) => {
        console.error('[Secretaria] Erro ao buscar obreiros:', err);
        this.errorMsg.set('Erro de conexão ao carregar dados do PHP.');
        this.loading.set(false);
      }
    });
  }

  protected limparFiltros(): void {
    this.filtroBusca.set('');
    this.filtroSituacao.set('');
    this.filtroGrau.set('');
    this.filtroAlerta.set('');
    this.filtroCargo.set('');
    this.filtroOrdenacao.set('nome');
    this.carregarObreiros();
  }

  protected abrirModalNovo(): void {
    this.isNovo.set(true);
    this.editTabActive.set('pessoais');
    this.formObreiro.set({
      id: '',
      cim: '',
      nome: '',
      nome_historico: '',
      cpf: '',
      data_nascimento_civil: '',
      estado_civil: 'casado',
      profissao: '',
      escolaridade: '',
      faixa_renda: '',
      grau: 'Aprendiz',
      cargo: '',
      loja_origem: '',
      situacao_quadro: 'ativo',
      acesso_status: 'ativo',
      ativo: true,
      data_iniciacao: '',
      data_elevacao: '',
      data_exaltacao: '',
      data_filiacao: '',
      data_regularizacao: '',
      telefone: '',
      email: '',
      telegram_id: '',
      observacao_secretaria: '',
      potencia_nome: '',
      potencia_sigla: '',
      numero_quadro: '',
      potencia_login: '',
      acesso_potencia_liberado: false
    });
    this.showModalEditar.set(true);
  }

  protected abrirModalEditar(id: string): void {
    this.loading.set(true);
    const headers = this.supabaseService.getAuthHeaders();

    this.http.get<any>(
      `${environment.apiUrl}/api/secretaria/obreiros/${id}`,
      { headers }
    ).subscribe({
      next: (res) => {
        if (res && res.ok && res.obreiro) {
          this.isNovo.set(false);
          this.editTabActive.set('pessoais');
          this.formObreiro.set({
            ...res.obreiro,
            ativo: res.obreiro.ativo !== false
          });
          this.showModalEditar.set(true);
        } else {
          alert(res.erro || 'Falha ao buscar dados do obreiro.');
        }
        this.loading.set(false);
      },
      error: (err) => {
        console.error('[Secretaria] Erro ao buscar obreiro:', err);
        alert('Erro ao conectar com a API.');
        this.loading.set(false);
      }
    });
  }

  protected salvarObreiro(): void {
    const obreiro = this.formObreiro();
    if (!obreiro.nome || !obreiro.grau || !obreiro.situacao_quadro) {
      alert('Nome, Grau e Situação são obrigatórios.');
      return;
    }

    this.salvandoObreiro.set(true);
    const headers = this.supabaseService.getAuthHeaders();

    this.http.post<any>(
      `${environment.apiUrl}/api/secretaria/obreiros/salvar`,
      {
        ...obreiro,
        nome_completo: obreiro.nome,
        ativo: obreiro.ativo ? '1' : '0'
      },
      { headers }
    ).subscribe({
      next: (res) => {
        if (res && res.ok) {
          this.showModalEditar.set(false);
          this.carregarObreiros();
        } else {
          alert(res.erro || 'Falha ao salvar obreiro.');
        }
        this.salvandoObreiro.set(false);
      },
      error: (err) => {
        console.error('[Secretaria] Erro ao salvar obreiro:', err);
        alert('Erro de conexão ao salvar.');
        this.salvandoObreiro.set(false);
      }
    });
  }

  protected inativarObreiro(id: string): void {
    if (!confirm('Deseja realmente inativar este obreiro do quadro?')) {
      return;
    }

    const headers = this.supabaseService.getAuthHeaders();
    this.http.post<any>(
      `${environment.apiUrl}/api/secretaria/obreiros/inativar`,
      { id },
      { headers }
    ).subscribe({
      next: (res) => {
        if (res && res.ok) {
          this.carregarObreiros();
        } else {
          alert('Falha ao inativar obreiro.');
        }
      },
      error: (err) => {
        console.error('[Secretaria] Erro ao inativar obreiro:', err);
        alert('Erro de conexão.');
      }
    });
  }

  protected excluirObreiro(id: string): void {
    if (!confirm('Deseja realmente EXCLUIR permanentemente este obreiro? Esta ação é irreversível.')) {
      return;
    }

    const headers = this.supabaseService.getAuthHeaders();
    this.http.delete<any>(
      `${environment.apiUrl}/api/secretaria/obreiros/${id}`,
      { headers }
    ).subscribe({
      next: (res) => {
        if (res && res.ok) {
          this.carregarObreiros();
        } else {
          alert('Falha ao excluir obreiro.');
        }
      },
      error: (err) => {
        console.error('[Secretaria] Erro ao excluir obreiro:', err);
        alert('Erro de conexão.');
      }
    });
  }

  protected getGrauLabel(grau: any): string {
    switch (String(grau)) {
      case '1':
      case 'Aprendiz': return 'Aprendiz';
      case '2':
      case 'Companheiro': return 'Companheiro';
      case '3':
      case 'Mestre': return 'Mestre';
      case 'Mestre Instalado': return 'Mestre'; // normalizado: instalado é status, não grau
      default: return String(grau || '-');
    }
  }

  protected getCargoLabel(codigo: string): string {
    const map: Record<string, string> = {
      veneravel: 'Venerável Mestre',
      primeiro_vigilante: '1º Vigilante',
      segundo_vigilante: '2º Vigilante',
      secretario: 'Secretário',
      tesoureiro: 'Tesoureiro',
      chanceler: 'Chanceler',
      hospitaleiro: 'Hospitaleiro',
      mestre_de_harmonia: 'Mestre de Harmonia',
      mestre_banquetes: 'Mestre de Banquetes',
      bibliotecario: 'Bibliotecário',
      orador: 'Orador',
      admin: 'Admin Técnico',
    };
    return map[codigo] || codigo;
  }

  protected getSituacaoBadgeClass(situacao: string): string {
    switch (String(situacao).toLowerCase()) {
      case 'ativo': return 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20';
      case 'inativo': return 'bg-rose-500/10 text-rose-400 border border-rose-500/20';
      case 'isento': return 'bg-blue-500/10 text-blue-400 border border-blue-500/20';
      case 'oriente_eterno': return 'bg-slate-500/20 text-slate-400 border border-slate-500/30';
      default: return 'bg-amber-500/10 text-amber-400 border border-amber-500/20';
    }
  }

  protected formatCpf(cpf: string): string {
    if (!cpf) return '';
    cpf = cpf.replace(/\D/g, '');
    if (cpf.length === 11) {
      return cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, "$1.$2.$3-$4");
    }
    return cpf;
  }
}
