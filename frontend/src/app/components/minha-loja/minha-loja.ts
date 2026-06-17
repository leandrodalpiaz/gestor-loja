import { Component, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { trigger, transition, style, animate, query, stagger } from '@angular/animations';
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
  styleUrl: './minha-loja.css',
  animations: [
    trigger('staggeredList', [
      transition('* => *', [
        query('article, .list-item', [
          style({ opacity: 0, transform: 'translateY(12px)' }),
          stagger(30, [
            animate('250ms cubic-bezier(0.4, 0, 0.2, 1)', style({ opacity: 1, transform: 'translateY(0)' }))
          ])
        ], { optional: true })
      ])
    ]),
    trigger('fadeInOut', [
      transition(':enter', [
        style({ opacity: 0, transform: 'scale(0.98)' }),
        animate('180ms ease-out', style({ opacity: 1, transform: 'scale(1)' }))
      ]),
      transition(':leave', [
        animate('120ms ease-in', style({ opacity: 0, transform: 'scale(0.98)' }))
      ])
    ])
  ]
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
  protected busca = '';

  ngOnInit(): void {
    this.carregarLoja();
  }

  protected get obreirosFiltrados(): ObreiroMembro[] {
    const q = this.busca.toLowerCase().trim();
    return q === '' ? this.obreiros() : this.obreiros().filter(o =>
      `${o.nome} ${o.nome_historico} ${o.grau} ${o.cargo}`.toLowerCase().includes(q)
    );
  }

  protected get obreirosPorGrau(): { grau: string; membros: ObreiroMembro[] }[] {
    const filtrados = this.obreirosFiltrados;
    const grupos: { [key: string]: ObreiroMembro[] } = {};

    filtrados.forEach(o => {
      const g = o.grau ? o.grau.trim() : 'Outros';
      if (!grupos[g]) {
        grupos[g] = [];
      }
      grupos[g].push(o);
    });

    const ordemGraus = ['mestre', 'companheiro', 'aprendiz'];
    const chavesOrdenadas = Object.keys(grupos).sort((a, b) => {
      const idxA = ordemGraus.indexOf(a.toLowerCase());
      const idxB = ordemGraus.indexOf(b.toLowerCase());
      if (idxA !== -1 && idxB !== -1) return idxA - idxB;
      if (idxA !== -1) return -1;
      if (idxB !== -1) return 1;
      return a.localeCompare(b);
    });

    return chavesOrdenadas.map(g => ({
      grau: g,
      membros: grupos[g]
    }));
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
