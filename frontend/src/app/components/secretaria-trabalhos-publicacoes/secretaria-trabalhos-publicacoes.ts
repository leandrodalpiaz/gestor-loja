import { Component, OnInit, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { SupabaseService } from '../../services/supabase.service';
import { environment } from '../../../environments/environment';

type PendenteTrabalho = {
  id: string;
  titulo: string;
  obreiro_id: string;
  obreiro_nome: string;
  sessao_id?: number | null;
  tipo_trabalho?: string | null;
  arquivo_pdf_path?: string | null;
};

type TrabalhoRecente = {
  titulo: string;
  tipo_trabalho?: string | null;
  sessao_titulo?: string | null;
  autor_nome?: string | null;
  arquivo_pdf_path?: string | null;
};

@Component({
  selector: 'app-secretaria-trabalhos-publicacoes',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './secretaria-trabalhos-publicacoes.html',
  styleUrl: './secretaria-trabalhos-publicacoes.css'
})
export class SecretariaTrabalhosPublicacoes implements OnInit {
  private http = inject(HttpClient);
  private supabaseService = inject(SupabaseService);

  protected loading = signal(true);
  protected salvandoId = signal<string | null>(null);
  protected errorMsg = signal<string | null>(null);
  protected successMsg = signal<string | null>(null);

  protected pendentes = signal<PendenteTrabalho[]>([]);
  protected trabalhosRecentes = signal<TrabalhoRecente[]>([]);
  protected sessoes = signal<any[]>([]);

  protected formularios = signal<Record<string, any>>({});

  ngOnInit(): void {
    this.carregar();
  }

  protected carregar(): void {
    this.loading.set(true);
    this.errorMsg.set(null);
    const headers = this.supabaseService.getAuthHeaders();

    this.http.get<any>(`${environment.apiUrl}/api/secretaria/trabalhos-publicacoes`, { headers }).subscribe({
      next: (res) => {
        this.loading.set(false);
        if (res?.ok) {
          const pendentes = Array.isArray(res.pendentes) ? res.pendentes : [];
          this.pendentes.set(pendentes);
          this.trabalhosRecentes.set(Array.isArray(res.trabalhos_recentes) ? res.trabalhos_recentes : []);
          this.sessoes.set(Array.isArray(res.sessoes) ? res.sessoes : []);

          const forms: Record<string, any> = {};
          for (const item of pendentes) {
            forms[item.id] = {
              id: item.id,
              autor_id: item.obreiro_id,
              titulo: item.titulo || '',
              tipo_trabalho: item.tipo_trabalho || 'peca_arquitetura',
              sessao_id: item.sessao_id || '',
              arquivo_pdf_path: item.arquivo_pdf_path || '',
              status_envio_potencia: 'pendente',
              observacao: ''
            };
          }
          this.formularios.set(forms);
        } else {
          this.errorMsg.set(res?.erro || 'Falha ao carregar trabalhos da secretaria.');
        }
      },
      error: (err) => {
        this.loading.set(false);
        console.error('[SecretariaTrabalhosPublicacoes] erro ao carregar', err);
        this.errorMsg.set(err.error?.erro || 'Erro de conexao ao carregar trabalhos.');
      }
    });
  }

  protected form(itemId: string): any {
    return this.formularios()[itemId];
  }

  protected atualizarCampo(itemId: string, campo: string, valor: any): void {
    this.formularios.update((state) => ({
      ...state,
      [itemId]: {
        ...(state[itemId] || {}),
        [campo]: valor
      }
    }));
  }

  protected arquivar(itemId: string): void {
    const payload = this.form(itemId);
    if (!payload) {
      return;
    }

    this.salvandoId.set(itemId);
    this.errorMsg.set(null);
    this.successMsg.set(null);
    const headers = this.supabaseService.getAuthHeaders();

    this.http.post<any>(`${environment.apiUrl}/api/secretaria/trabalhos-publicacoes/arquivar`, payload, { headers }).subscribe({
      next: (res) => {
        this.salvandoId.set(null);
        if (res?.ok) {
          this.successMsg.set('Trabalho arquivado e publicado na Biblioteca.');
          this.carregar();
        } else {
          this.errorMsg.set(res?.erro || 'Não foi possível arquivar agora.');
        }
      },
      error: (err) => {
        this.salvandoId.set(null);
        console.error('[SecretariaTrabalhosPublicacoes] erro ao arquivar', err);
        this.errorMsg.set(err.error?.erro || 'Erro de conexao ao arquivar.');
      }
    });
  }
}
