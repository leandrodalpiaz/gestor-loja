import { Component, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { SupabaseService } from '../../services/supabase.service';
import { environment } from '../../../environments/environment';

export interface LojaInfo {
  nome: string;
  numero: string;
  sigla: string;
}

export interface ObreiroMembro {
  id: string;
  nome: string;
  nome_historico: string;
  grau: string;
  cargo: string;
  email: string;
  telefone: string;
  data_nascimento_civil: string | null;
}

@Component({
  selector: 'app-minha-loja',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './minha-loja.html',
  styleUrl: './minha-loja.css'
})
export class MinhaLoja implements OnInit {
  private http = inject(HttpClient);
  private route = inject(ActivatedRoute);
  private router = inject(Router);
  protected supabaseService = inject(SupabaseService);

  protected loading = signal(true);
  protected errorMsg = signal<string | null>(null);
  protected loja = signal<LojaInfo | null>(null);
  protected obreiros = signal<ObreiroMembro[]>([]);
  protected estatisticas = signal<any>({ total_ativos: 0, graus: [] });
  protected aniversariantes = signal<ObreiroMembro[]>([]);
  protected modo = signal<'visao' | 'irmaos'>('visao');
  protected busca = '';

  ngOnInit(): void {
    this.modo.set(this.route.snapshot.data['lojaTab'] === 'irmaos' ? 'irmaos' : 'visao');
    this.carregarLoja();
  }

  protected get obreirosFiltrados(): ObreiroMembro[] {
    const q = this.busca.toLowerCase().trim();
    return q === '' ? this.obreiros() : this.obreiros().filter(o =>
      `${o.nome} ${o.nome_historico} ${o.grau} ${o.cargo}`.toLowerCase().includes(q)
    );
  }

  protected abrir(modo: 'visao' | 'irmaos'): void {
    void this.router.navigate([modo === 'irmaos' ? '/dashboard/loja/irmaos' : '/dashboard/loja']);
  }

  protected carregarLoja(): void {
    this.loading.set(true);
    this.errorMsg.set(null);
    const headers = this.supabaseService.getAuthHeaders();

    this.http.get<{ ok: boolean; loja?: LojaInfo; obreiros?: ObreiroMembro[]; estatisticas?: any; aniversariantes_mes?: ObreiroMembro[]; erro?: string }>(
      `${environment.apiUrl}/api/obreiro/loja`,
      { headers }
    ).subscribe({
      next: (res) => {
        this.loading.set(false);
        if (res && res.ok && res.loja && res.obreiros) {
          this.loja.set(res.loja);
          this.obreiros.set(res.obreiros);
          this.estatisticas.set(res.estatisticas || { total_ativos: res.obreiros.length, graus: [] });
          this.aniversariantes.set(res.aniversariantes_mes || []);
        } else {
          this.errorMsg.set(res.erro || 'Falha ao carregar informações da loja.');
        }
      },
      error: (err) => {
        this.loading.set(false);
        console.error('[MinhaLoja] Erro:', err);
        this.errorMsg.set(err.error?.erro || 'Erro de conexão ao servidor.');
      }
    });
  }
}
