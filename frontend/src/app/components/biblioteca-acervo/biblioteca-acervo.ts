import { CommonModule } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { Component, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { trigger, transition, style, animate, query, stagger, state } from '@angular/animations';
import { environment } from '../../../environments/environment';
import { SupabaseService } from '../../services/supabase.service';

type BibliotecaTab = 'acervo' | 'meus' | 'gestao' | 'classificacao';

@Component({
  selector: 'app-biblioteca-acervo',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './biblioteca-acervo.html',
  styleUrl: './biblioteca-acervo.css',
  animations: [
    trigger('tabTransition', [
      transition('* => *', [
        style({ opacity: 0, transform: 'translateY(10px)' }),
        animate('250ms cubic-bezier(0.4, 0, 0.2, 1)', style({ opacity: 1, transform: 'translateY(0)' }))
      ])
    ]),
    trigger('staggeredList', [
      transition('* => *', [
        query('article, .list-item', [
          style({ opacity: 0, transform: 'translateY(15px)' }),
          stagger(35, [
            animate('250ms cubic-bezier(0.4, 0, 0.2, 1)', style({ opacity: 1, transform: 'translateY(0)' }))
          ])
        ], { optional: true })
      ])
    ]),
    trigger('fadeInOut', [
      transition(':enter', [
        style({ opacity: 0, transform: 'scale(0.97)' }),
        animate('150ms ease-out', style({ opacity: 1, transform: 'scale(1)' }))
      ]),
      transition(':leave', [
        animate('120ms ease-in', style({ opacity: 0, transform: 'scale(0.97)' }))
      ])
    ])
  ]
})
export class BibliotecaAcervo implements OnInit {
  private http = inject(HttpClient);
  private route = inject(ActivatedRoute);
  private router = inject(Router);
  protected supabaseService = inject(SupabaseService);

  protected loading = signal(true);
  protected errorMsg = signal<string | null>(null);
  protected successMsg = signal<string | null>(null);
  protected dados = signal<any>({});
  protected tab = signal<BibliotecaTab>('acervo');
  protected filtroBusca = '';
  protected selectedBook = signal<any | null>(null);
  protected loadingDetails = signal(false);
  protected novoComentario = '';
  protected classificacao = { livro_id: 0, grau_recomendado: 'Livre', nota_instrucao: '' };

  ngOnInit(): void {
    this.tab.set((this.route.snapshot.data['bibliotecaTab'] as BibliotecaTab) || 'acervo');
    this.carregar();
  }

  protected get livrosFiltrados(): any[] {
    const q = this.filtroBusca.toLowerCase().trim();
    const livros = this.dados()?.acervo || [];
    return q === '' ? livros : livros.filter((livro: any) =>
      `${livro.titulo} ${livro.autor} ${livro.codigo_acervo}`.toLowerCase().includes(q)
    );
  }

  protected pode(permissao: string): boolean {
    const profile = this.supabaseService.profile() || {};
    const permissions = new Set<string>(profile.permissions || []);
    return profile.is_system_admin === true || permissions.has('*') || permissions.has(permissao);
  }

  protected abrirTab(tab: BibliotecaTab): void {
    this.fecharDetalhes();
    const path = tab === 'acervo' ? 'acervo' : tab === 'meus' ? 'emprestimos' : tab;
    void this.router.navigate(['/dashboard/biblioteca', path]);
  }

  protected carregar(acervoId?: number): void {
    this.loading.set(true);
    this.errorMsg.set(null);

    const idFoco = acervoId || this.selectedBook()?.id;
    let url = `${environment.apiUrl}/api/miniapp/biblioteca/dashboard`;
    if (idFoco) {
      url += `?acervo_id=${idFoco}`;
    }

    this.http.get<any>(url, {
      headers: this.supabaseService.getAuthHeaders()
    }).subscribe({
      next: res => {
        this.loading.set(false);
        if (res?.ok) {
          this.dados.set(res.dados || {});
          if (res.dados?.item_foco) {
            this.selectedBook.set(res.dados.item_foco);
          }
        } else {
          this.errorMsg.set(res?.erro || 'Não foi possível carregar a Biblioteca.');
        }
      },
      error: err => {
        this.loading.set(false);
        this.errorMsg.set(err.error?.erro || 'Erro ao carregar a Biblioteca.');
      }
    });
  }

  protected selecionarLivro(livro: any): void {
    this.selectedBook.set(livro);
    this.loadingDetails.set(true);
    this.http.get<any>(`${environment.apiUrl}/api/miniapp/biblioteca/dashboard?acervo_id=${livro.id}`, {
      headers: this.supabaseService.getAuthHeaders()
    }).subscribe({
      next: res => {
        this.loadingDetails.set(false);
        if (res?.ok) {
          this.dados.set(res.dados || {});
          if (res.dados?.item_foco) {
            this.selectedBook.set(res.dados.item_foco);
          }
        }
      },
      error: () => {
        this.loadingDetails.set(false);
      }
    });
  }

  protected fecharDetalhes(): void {
    this.selectedBook.set(null);
    this.novoComentario = '';
  }

  protected reagir(acervoId: number, gostei: boolean): void {
    this.post('/api/miniapp/biblioteca/reagir', { acervo_id: acervoId, gostei: gostei ? 1 : 0 }, 'Obrigado por registrar seu voto.');
  }

  protected comentar(acervoId: number): void {
    const com = this.novoComentario.trim();
    if (com === '') return;
    this.post('/api/miniapp/biblioteca/comentar', { acervo_id: acervoId, comentario: com }, 'Comentário publicado.');
    this.novoComentario = '';
  }

  protected solicitar(livro: any): void {
    this.post('/api/miniapp/biblioteca/solicitar', {
      acervo_id: livro.id,
      loja_id: livro.loja_id,
      scope: this.dados()?.rede?.scope || 'minha'
    }, `Empréstimo de "${livro.titulo}" solicitado.`);
  }

  protected devolver(emprestimo: any): void {
    if (!confirm(`Confirmar a devolução de "${emprestimo.titulo}"?`)) return;
    this.post('/api/miniapp/biblioteca/devolver', { emprestimo_id: emprestimo.id }, 'Devolução registrada.');
  }

  protected decidir(pedido: any, decisao: 'aprovado' | 'negado'): void {
    this.post('/api/miniapp/biblioteca/interloja/decidir', {
      pedido_id: pedido.id,
      decisao
    }, `Pedido ${decisao === 'aprovado' ? 'aprovado' : 'negado'}.`);
  }

  protected prepararClassificacao(livro: any): void {
    this.classificacao = {
      livro_id: livro.id,
      grau_recomendado: livro.grau_recomendado || 'Livre',
      nota_instrucao: livro.nota_instrucao || ''
    };
  }

  protected salvarClassificacao(): void {
    if (!this.classificacao.livro_id) {
      this.errorMsg.set('Selecione uma obra para classificar.');
      return;
    }
    this.post('/api/miniapp/biblioteca/classificar', this.classificacao, 'Classificação atualizada.');
  }

  private post(path: string, body: any, sucesso: string): void {
    this.loading.set(true);
    this.errorMsg.set(null);
    this.successMsg.set(null);
    this.http.post<any>(`${environment.apiUrl}${path}`, body, {
      headers: this.supabaseService.getAuthHeaders()
    }).subscribe({
      next: res => {
        this.loading.set(false);
        if (res?.ok) {
          this.successMsg.set(sucesso);
          const activeId = this.selectedBook()?.id;
          this.carregar(activeId);
        } else {
          this.errorMsg.set(res?.erro || 'Não foi possível concluir a operação.');
        }
      },
      error: err => {
        this.loading.set(false);
        this.errorMsg.set(err.error?.erro || 'Erro ao concluir a operação.');
      }
    });
  }
}
