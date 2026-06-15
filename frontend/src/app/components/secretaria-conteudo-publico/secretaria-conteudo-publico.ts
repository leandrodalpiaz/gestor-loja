import { Component, OnInit, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../../environments/environment';
import { SupabaseService } from '../../services/supabase.service';

interface ConteudoForm {
  id: number;
  tipo: string;
  titulo: string;
  resumo: string;
  corpo: string;
  link_url: string;
  imagem_url: string;
  prioridade: number;
  inicio_em: string;
  fim_em: string;
  publicado: boolean;
}

@Component({
  selector: 'app-secretaria-conteudo-publico',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './secretaria-conteudo-publico.html',
  styleUrl: './secretaria-conteudo-publico.css'
})
export class SecretariaConteudoPublico implements OnInit {
  private http = inject(HttpClient);
  protected supabaseService = inject(SupabaseService);

  protected loading = signal(true);
  protected salvando = signal(false);
  protected errorMsg = signal<string | null>(null);
  protected successMsg = signal<string | null>(null);
  protected itens = signal<any[]>([]);
  protected tipos = signal<string[]>([]);
  protected form = signal<ConteudoForm>(this.novoForm());

  ngOnInit(): void {
    this.carregar();
  }

  private novoForm(): ConteudoForm {
    return {
      id: 0,
      tipo: 'agenda',
      titulo: '',
      resumo: '',
      corpo: '',
      link_url: '',
      imagem_url: '',
      prioridade: 0,
      inicio_em: '',
      fim_em: '',
      publicado: true
    };
  }

  protected carregar(): void {
    this.loading.set(true);
    this.errorMsg.set(null);

    this.http.get<any>(`${environment.apiUrl}/api/admin/conteudo-publico`, {
      headers: this.supabaseService.getAuthHeaders()
    }).subscribe({
      next: (res) => {
        this.loading.set(false);
        if (!res?.ok) {
          this.errorMsg.set(res?.erro || 'Falha ao carregar conteudos.');
          return;
        }
        this.itens.set(res.itens || []);
        this.tipos.set(res.tipos || []);
      },
      error: (err) => {
        this.loading.set(false);
        this.errorMsg.set(err.error?.erro || 'Erro ao consultar conteudos.');
      }
    });
  }

  protected setField(field: keyof ConteudoForm, value: string | number | boolean): void {
    this.form.update((state) => ({ ...state, [field]: value }));
  }

  protected editar(item: any): void {
    this.form.set({
      id: Number(item.id || 0),
      tipo: item.tipo || 'agenda',
      titulo: item.titulo || '',
      resumo: item.resumo || '',
      corpo: item.corpo || '',
      link_url: item.link_url || '',
      imagem_url: item.imagem_url || '',
      prioridade: Number(item.prioridade || 0),
      inicio_em: item.inicio_em || '',
      fim_em: item.fim_em || '',
      publicado: !!item.publicado
    });
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  protected limpar(): void {
    this.form.set(this.novoForm());
  }

  protected salvar(): void {
    this.salvando.set(true);
    this.errorMsg.set(null);
    this.successMsg.set(null);

    this.http.post<any>(`${environment.apiUrl}/api/admin/conteudo-publico/salvar`, this.form(), {
      headers: this.supabaseService.getAuthHeaders()
    }).subscribe({
      next: (res) => {
        this.salvando.set(false);
        if (!res?.ok) {
          this.errorMsg.set(res?.erro || 'Não foi possível salvar o conteúdo.');
          return;
        }
        this.successMsg.set('Conteúdo salvo com sucesso.');
        this.limpar();
        this.carregar();
      },
      error: (err) => {
        this.salvando.set(false);
        this.errorMsg.set(err.error?.erro || 'Erro ao salvar conteúdo.');
      }
    });
  }

  protected excluir(id: number): void {
    this.errorMsg.set(null);
    this.successMsg.set(null);

    this.http.post<any>(`${environment.apiUrl}/api/admin/conteudo-publico/excluir`, { id }, {
      headers: this.supabaseService.getAuthHeaders()
    }).subscribe({
      next: (res) => {
        if (!res?.ok) {
          this.errorMsg.set(res?.erro || 'Não foi possível remover o conteúdo.');
          return;
        }
        this.successMsg.set('Conteúdo removido com sucesso.');
        this.carregar();
      },
      error: (err) => {
        this.errorMsg.set(err.error?.erro || 'Erro ao remover conteúdo.');
      }
    });
  }
}
