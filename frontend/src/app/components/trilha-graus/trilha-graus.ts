import { Component, inject, OnInit, signal, computed } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { SupabaseService } from '../../services/supabase.service';
import { environment } from '../../../environments/environment';

export interface TrabalhoSubmissao {
  id: string;
  titulo: string;
  tipo_trabalho: string;
  sessao_id: number | null;
  arquivo_pdf_path: string | null;
  status: string;
  mentor_decisao: string | null;
  mentor_observacao: string | null;
  created_at: string;
}

export interface SessaoFutura {
  id: number;
  data_hora_inicio: string;
  titulo: string;
}

@Component({
  selector: 'app-trilha-graus',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './trilha-graus.html',
  styleUrl: './trilha-graus.css'
})
export class TrilhaGraus implements OnInit {
  private http = inject(HttpClient);
  protected supabaseService = inject(SupabaseService);

  protected loading = signal(true);
  protected salvando = signal(false);
  protected errorMsg = signal<string | null>(null);
  protected successMsg = signal<string | null>(null);

  protected trabalhos = signal<TrabalhoSubmissao[]>([]);
  protected sessoes = signal<SessaoFutura[]>([]);

  // Formulário de Rascunho
  protected showForm = signal(false);
  protected formTrabalho = signal({
    id: '',
    titulo: '',
    tipo_trabalho: 'peca_arquitetura',
    sessao_id: '',
    arquivo_pdf_path: ''
  });

  // Checklist da Trilha com base no grau do usuário logado
  protected trilhaEtapas = computed(() => {
    const profile = this.supabaseService.profile();
    if (!profile) return [];

    const grau = (profile.grau || '').toLowerCase();
    
    if (grau.includes('aprendiz')) {
      return [
        { label: 'Instrução do Grau 1 - História e Símbolos', concluido: true },
        { label: 'Participação em pelo menos 5 Sessões Ordinárias', concluido: true },
        { label: 'Primeira Peça de Arquitetura (Rascunho e Submissão)', concluido: this.hasSubmittedWork() },
        { label: 'Avaliação pelo 1º Vigilante', concluido: this.hasApprovedWork() },
        { label: 'Exame de Elevação', concluido: false }
      ];
    } else if (grau.includes('companheiro')) {
      return [
        { label: 'Instrução do Grau 2 - As Ferramentas do Companheiro', concluido: true },
        { label: 'Participação em pelo menos 5 Sessões de Companheiro', concluido: true },
        { label: 'Segunda Peça de Arquitetura (Rascunho e Submissão)', concluido: this.hasSubmittedWork() },
        { label: 'Avaliação pelo 2º Vigilante', concluido: this.hasApprovedWork() },
        { label: 'Exame de Exaltação', concluido: false }
      ];
    } else {
      return [
        { label: 'Instrução do Grau 3 - Lenda de Hiram', concluido: true },
        { label: 'Gestão/Ofício em Loja', concluido: true },
        { label: 'Trabalho de Mestrado submetido à Secretaria', concluido: this.hasSubmittedWork() },
        { label: 'Regularidade Financeira de Mestre', concluido: true }
      ];
    }
  });

  ngOnInit(): void {
    this.carregarDados();
  }

  protected carregarDados(): void {
    this.loading.set(true);
    this.errorMsg.set(null);
    const headers = this.supabaseService.getAuthHeaders();

    // Busca rascunhos do obreiro
    this.http.get<{ ok: boolean; trabalhos?: TrabalhoSubmissao[] }>(
      `${environment.apiUrl}/api/obreiro/trabalhos/rascunhos`,
      { headers }
    ).subscribe({
      next: (res) => {
        if (res && res.ok && res.trabalhos) {
          this.trabalhos.set(res.trabalhos);
        }
        this.loading.set(false);
      },
      error: (err) => {
        this.loading.set(false);
        console.error('[Trilha] Erro ao buscar trabalhos:', err);
        this.errorMsg.set('Falha ao carregar rascunhos de trabalhos.');
      }
    });

    // Busca sessões futuras para seleção no formulário
    this.http.get<{ ok: boolean; sessoes?: SessaoFutura[] }>(
      `${environment.apiUrl}/api/obreiro/sessoes/futuras`,
      { headers }
    ).subscribe({
      next: (res) => {
        if (res && res.ok && res.sessoes) {
          this.sessoes.set(res.sessoes);
        }
      },
      error: (err) => {
        console.error('[Trilha] Erro ao carregar sessões futuras:', err);
      }
    });
  }

  private hasSubmittedWork(): boolean {
    return this.trabalhos().some(t => t.status !== 'rascunho');
  }

  private hasApprovedWork(): boolean {
    return this.trabalhos().some(t => t.status === 'arquivado' || t.mentor_decisao === 'aprovado');
  }

  protected abrirNovoForm(): void {
    this.formTrabalho.set({
      id: '',
      titulo: '',
      tipo_trabalho: 'peca_arquitetura',
      sessao_id: '',
      arquivo_pdf_path: ''
    });
    this.showForm.set(true);
    this.successMsg.set(null);
    this.errorMsg.set(null);
  }

  protected editarRascunho(trab: TrabalhoSubmissao): void {
    this.formTrabalho.set({
      id: trab.id,
      titulo: trab.titulo,
      tipo_trabalho: trab.tipo_trabalho,
      sessao_id: trab.sessao_id ? String(trab.sessao_id) : '',
      arquivo_pdf_path: trab.arquivo_pdf_path || ''
    });
    this.showForm.set(true);
    this.successMsg.set(null);
    this.errorMsg.set(null);
  }

  protected fecharForm(): void {
    this.showForm.set(false);
  }

  protected salvarRascunho(): void {
    this.salvando.set(true);
    this.successMsg.set(null);
    this.errorMsg.set(null);
    const headers = this.supabaseService.getAuthHeaders();

    const body = {
      id: this.formTrabalho().id || null,
      titulo: this.formTrabalho().titulo,
      tipo_trabalho: this.formTrabalho().tipo_trabalho,
      sessao_id: this.formTrabalho().sessao_id ? Number(this.formTrabalho().sessao_id) : null,
      arquivo_pdf_path: this.formTrabalho().arquivo_pdf_path
    };

    this.http.post<{ ok: boolean; id?: string; erro?: string }>(
      `${environment.apiUrl}/api/obreiro/trabalhos/rascunhos/salvar`,
      body,
      { headers }
    ).subscribe({
      next: (res) => {
        this.salvando.set(false);
        if (res && res.ok) {
          this.successMsg.set('Rascunho gravado com sucesso.');
          this.showForm.set(false);
          this.carregarDados();
        } else {
          this.errorMsg.set(res.erro || 'Falha ao salvar rascunho.');
        }
      },
      error: (err) => {
        this.salvando.set(false);
        console.error('[Trilha] Erro ao salvar:', err);
        this.errorMsg.set(err.error?.erro || 'Erro ao comunicar com o servidor.');
      }
    });
  }

  protected submeterTrabalho(id: string): void {
    if (!confirm('Deseja submeter oficialmente este rascunho para revisão? Após o envio, você não poderá editá-lo até a revisão do mentor.')) {
      return;
    }

    this.loading.set(true);
    this.successMsg.set(null);
    this.errorMsg.set(null);
    const headers = this.supabaseService.getAuthHeaders();

    this.http.post<{ ok: boolean; erro?: string }>(
      `${environment.apiUrl}/api/obreiro/trabalhos/submeter`,
      { id },
      { headers }
    ).subscribe({
      next: (res) => {
        if (res && res.ok) {
          this.successMsg.set('Trabalho submetido oficialmente!');
          this.carregarDados();
        } else {
          this.errorMsg.set(res.erro || 'Erro ao submeter trabalho.');
          this.loading.set(false);
        }
      },
      error: (err) => {
        console.error('[Trilha] Erro ao submeter:', err);
        this.errorMsg.set(err.error?.erro || 'Falha na conexão.');
        this.loading.set(false);
      }
    });
  }
}
