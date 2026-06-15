import { Component, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { SupabaseService } from '../../services/supabase.service';
import { environment } from '../../../environments/environment';

export interface VisitanteItem {
  nome: string;
  linha_resumida: string;
}

@Component({
  selector: 'app-chancelaria-visitantes',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './chancelaria-visitantes.html',
  styleUrl: './chancelaria-visitantes.css'
})
export class ChancelariaVisitantes implements OnInit {
  private http = inject(HttpClient);
  protected supabaseService = inject(SupabaseService);

  protected loading = signal(true);
  protected salvando = signal(false);
  protected errorMsg = signal<string | null>(null);
  protected successMsg = signal<string | null>(null);

  protected visitantes = signal<VisitanteItem[]>([]);
  protected showForm = signal(false);

  protected formVisitante = signal({
    nome_visitante: '',
    loja_visitante: '',
    oriente: '',
    tipo_sessao: 'Ordinaria',
    grau_sessao: 'Aprendiz Macom',
    data_sessao: '',
    chat_id: ''
  });

  ngOnInit(): void {
    const hoje = new Date().toISOString().split('T')[0];
    this.formVisitante.update(f => ({ ...f, data_sessao: hoje }));
    this.carregarVisitantes();
  }

  protected carregarVisitantes(): void {
    this.loading.set(true);
    this.errorMsg.set(null);
    const headers = this.supabaseService.getAuthHeaders();

    this.http.get<any>(
      `${environment.apiUrl}/api/miniapp/chanceler/dashboard`,
      { headers }
    ).subscribe({
      next: (res) => {
        this.loading.set(false);
        if (res && res.ok) {
          this.visitantes.set((res.dados?.visitantes || []) as VisitanteItem[]);
        } else {
          this.errorMsg.set('Falha ao carregar livro de visitantes.');
        }
      },
      error: (err) => {
        this.loading.set(false);
        console.error('[ChancelariaVisitantes] Erro:', err);
        this.errorMsg.set(err.error?.erro || 'Erro ao carregar visitantes.');
        this.visitantes.set([]);
      }
    });
  }

  protected abrirCadastro(): void {
    const hoje = new Date().toISOString().split('T')[0];
    this.formVisitante.set({
      nome_visitante: '',
      loja_visitante: '',
      oriente: '',
      tipo_sessao: 'Ordinaria',
      grau_sessao: 'Aprendiz Macom',
      data_sessao: hoje,
      chat_id: ''
    });
    this.showForm.set(true);
    this.successMsg.set(null);
    this.errorMsg.set(null);
  }

  protected fecharForm(): void {
    this.showForm.set(false);
  }

  protected cadastrarVisitante(): void {
    this.salvando.set(true);
    this.successMsg.set(null);
    this.errorMsg.set(null);
    const headers = this.supabaseService.getAuthHeaders();

    this.http.post<any>(
      `${environment.apiUrl}/api/chancelaria/certificado/gerar`,
      this.formVisitante(),
      { headers }
    ).subscribe({
      next: (res) => {
        this.salvando.set(false);
        if (res && res.ok) {
          this.successMsg.set('Visitante cadastrado e certificado digital emitido com sucesso!');
          this.showForm.set(false);
          this.carregarVisitantes();
        } else {
          this.errorMsg.set(res.erro || 'Falha ao cadastrar visitante.');
        }
      },
      error: (err) => {
        this.salvando.set(false);
        console.error('[ChancelariaVisitantes] Erro no cadastro:', err);
        this.errorMsg.set(err.error?.erro || 'Erro ao registrar visitante.');
      }
    });
  }
}
