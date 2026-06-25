import { CommonModule } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { Component, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { trigger, transition, style, animate, query, stagger, state } from '@angular/animations';
import { environment } from '../../../environments/environment';
import { SupabaseService } from '../../services/supabase.service';
import { DomSanitizer, SafeResourceUrl } from '@angular/platform-browser';
import { Html5Qrcode } from 'html5-qrcode';

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
  private sanitizer = inject(DomSanitizer);

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

  protected subTab = signal<'livros' | 'pecas' | 'trabalhos'>('livros');
  protected pdfLeituraUrl = signal<SafeResourceUrl | null>(null);
  protected pdfLeituraTitulo = signal<string>('');
  protected pdfLeituraAutor = signal<string>('');
  protected pdfRawUrl = signal<string | null>(null);

  // ─── CRUD de obras ────────────────────────────────────────────────────
  protected showFormModal = signal(false);
  protected formMode = signal<'add' | 'edit'>('add');
  protected formSaving = signal(false);
  protected form = signal({
    id: 0,
    titulo: '',
    autor: '',
    tipo: 'Livro Físico',
    isbn: '',
    capa_url: '',
    resumo: '',
    quantidade_disponivel: 1,
    grau_restricao: 1,
    grau_recomendado: 'Livre',
    nota_instrucao: '',
    arquivo_url: '',
  });

  protected readonly TIPOS_OBRA = ['Livro Físico', 'Peça de Arquitetura', 'Trabalho de Instrução'];
  protected readonly GRAUS_RESTRICAO = [
    { value: 1, label: 'Aprendiz' },
    { value: 2, label: 'Companheiro' },
    { value: 3, label: 'Mestre' },
  ];
  protected readonly GRAUS_RECOMENDADOS = ['Livre', 'Aprendiz', 'Companheiro', 'Mestre'];

  // ISBN lookup
  protected isbnBuscando = signal(false);
  protected isbnStatus = signal('');
  protected isbnStatusTipo = signal<'info' | 'success' | 'error'>('info');

  // QR Code Scanner
  protected scannerAtivo = signal(false);
  protected scannerErro = signal('');
  private html5QrCode: Html5Qrcode | null = null;

  protected buscarIsbn(): void {
    const isbn = this.form().isbn.trim();
    if (!isbn) {
      this.isbnStatus.set('Informe um ISBN para buscar.');
      this.isbnStatusTipo.set('error');
      return;
    }
    this.isbnBuscando.set(true);
    this.isbnStatus.set('Consultando servidores literários...');
    this.isbnStatusTipo.set('info');

    this.http.get<any>(`${environment.apiUrl}/api/miniapp/biblioteca/isbn?isbn=${encodeURIComponent(isbn)}`, {
      headers: this.supabaseService.getAuthHeaders()
    }).subscribe({
      next: res => {
        this.isbnBuscando.set(false);
        if (res?.ok && res.dados) {
          const d = res.dados;
          this.form.update(f => ({
            ...f,
            titulo: d.titulo || f.titulo,
            autor: d.autor || f.autor,
            isbn: d.isbn || isbn,
            capa_url: d.capa_url || f.capa_url,
            resumo: d.resumo || f.resumo,
          }));
          this.isbnStatus.set('Dados carregados com sucesso! Revise as informações.');
          this.isbnStatusTipo.set('success');
        } else {
          this.isbnStatus.set(res?.erro || 'ISBN não encontrado.');
          this.isbnStatusTipo.set('error');
        }
      },
      error: () => {
        this.isbnBuscando.set(false);
        this.isbnStatus.set('Não foi possível consultar os servidores do ISBN agora.');
        this.isbnStatusTipo.set('error');
      }
    });
  }

  // ─── QR Code Scanner ──────────────────────────────────────────────────

  protected abrirScanner(): void {
    this.scannerAtivo.set(true);
    this.scannerErro.set('');

    setTimeout(() => {
      const el = document.getElementById('qr-reader');
      if (!el) {
        this.scannerErro.set('Não foi possível inicializar o scanner.');
        return;
      }
      try {
        this.html5QrCode = new Html5Qrcode('qr-reader');
        this.html5QrCode.start(
          { facingMode: 'environment' },
          { fps: 10, qrbox: { width: 250, height: 150 }, aspectRatio: 1 },
          (decodedText: string) => this.onQrCodeLido(decodedText),
          () => {}
        ).catch(() => {
          this.scannerErro.set('Erro ao acessar a câmera. Verifique as permissões.');
        });
      } catch {
        this.scannerErro.set('Scanner não suportado neste dispositivo.');
      }
    }, 100);
  }

  protected fecharScanner(): void {
    if (this.html5QrCode) {
      this.html5QrCode.stop().catch(() => {});
      this.html5QrCode = null;
    }
    this.scannerAtivo.set(false);
  }

  private onQrCodeLido(decodedText: string): void {
    if (this.html5QrCode) {
      this.html5QrCode.stop().catch(() => {});
      this.html5QrCode = null;
    }
    this.scannerAtivo.set(false);

    const isbnMatch = decodedText.match(/(\d{10,13})/);
    const isbn = isbnMatch ? isbnMatch[1] : decodedText;

    this.form.update(f => ({ ...f, isbn: isbn }));
    this.buscarIsbn();
  }

  ngOnInit(): void {
    this.tab.set((this.route.snapshot.data['bibliotecaTab'] as BibliotecaTab) || 'acervo');
    this.carregar();
  }

  protected get livrosFiltrados(): any[] {
    const q = this.filtroBusca.toLowerCase().trim();
    let livros = this.dados()?.acervo || [];

    const st = this.subTab();
    livros = livros.filter((livro: any) => {
      const t = (livro.tipo || '').toLowerCase();
      const isBook = t !== 'peca de arquitetura' && t !== 'trabalho de instrucao';
      if (st === 'livros') return isBook;
      if (st === 'pecas') return t === 'peca de arquitetura';
      if (st === 'trabalhos') return t === 'trabalho de instrucao';
      return true;
    });

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

  protected abrirLeitorPdf(url: string, titulo: string, autor: string): void {
    if (!url) return;
    const fullUrl = url.startsWith('http') ? url : `${environment.apiUrl}${url}`;
    this.pdfRawUrl.set(fullUrl);
    this.pdfLeituraUrl.set(this.sanitizer.bypassSecurityTrustResourceUrl(fullUrl));
    this.pdfLeituraTitulo.set(titulo);
    this.pdfLeituraAutor.set(autor);
  }

  protected fecharLeitorPdf(): void {
    this.pdfLeituraUrl.set(null);
    this.pdfRawUrl.set(null);
    this.pdfLeituraTitulo.set('');
    this.pdfLeituraAutor.set('');
  }

  protected baixarPdf(url: string): void {
    if (!url) return;
    const fullUrl = url.startsWith('http') ? url : `${environment.apiUrl}${url}`;
    window.open(fullUrl, '_blank');
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

  // ─── CRUD de obras ────────────────────────────────────────────────────

  protected abrirFormNovo(): void {
    this.formMode.set('add');
    this.form.set({
      id: 0, titulo: '', autor: '', tipo: 'Livro Físico', isbn: '', capa_url: '',
      resumo: '', quantidade_disponivel: 1, grau_restricao: 1,
      grau_recomendado: 'Livre', nota_instrucao: '', arquivo_url: '',
    });
    this.isbnStatus.set(''); this.isbnStatusTipo.set('info');
    this.isbnStatus.set(''); this.isbnStatusTipo.set('info');
    this.showFormModal.set(true);
  }

  protected abrirFormEditar(livro: any): void {
    this.formMode.set('edit');
    this.form.set({
      id: livro.id || 0,
      titulo: livro.titulo || '',
      autor: livro.autor || '',
      tipo: livro.tipo || 'Livro Físico',
      isbn: livro.isbn || '',
      capa_url: livro.capa_url || '',
      resumo: livro.resumo || '',
      quantidade_disponivel: livro.quantidade_disponivel || 1,
      grau_restricao: livro.grau_restricao || 1,
      grau_recomendado: livro.grau_recomendado || 'Livre',
      nota_instrucao: livro.nota_instrucao || '',
      arquivo_url: livro.arquivo_url || '',
    });
    this.showFormModal.set(true);
  }

  protected fecharForm(): void {
    this.showFormModal.set(false);
  }

  protected salvarForm(): void {
    const f = this.form();
    if (!f.titulo.trim()) {
      this.errorMsg.set('O título da obra é obrigatório.');
      return;
    }

    this.formSaving.set(true);
    const endpoint = this.formMode() === 'add'
      ? '/api/miniapp/biblioteca/adicionar'
      : '/api/miniapp/biblioteca/editar';

    const body: any = { ...f };
    if (this.formMode() === 'edit') body.id = f.id;

    this.http.post<any>(`${environment.apiUrl}${endpoint}`, body, {
      headers: this.supabaseService.getAuthHeaders()
    }).subscribe({
      next: res => {
        this.formSaving.set(false);
        if (res?.ok) {
          this.showFormModal.set(false);
          this.successMsg.set(this.formMode() === 'add' ? 'Obra adicionada com sucesso!' : 'Obra atualizada com sucesso!');
          this.carregar();
        } else {
          this.errorMsg.set(res?.erro || 'Erro ao salvar a obra.');
        }
      },
      error: err => {
        this.formSaving.set(false);
        this.errorMsg.set(err.error?.erro || 'Erro de conexão ao salvar.');
      }
    });
  }

  protected excluirLivro(livro: any): void {
    if (!confirm(`Excluir permanentemente "${livro.titulo}"?\n\nEsta ação é irreversível.`)) return;

    this.http.post<any>(`${environment.apiUrl}/api/miniapp/biblioteca/excluir`, { id: livro.id }, {
      headers: this.supabaseService.getAuthHeaders()
    }).subscribe({
      next: res => {
        if (res?.ok) {
          this.successMsg.set('Obra excluída com sucesso.');
          this.selectedBook.set(null);
          this.carregar();
        } else {
          this.errorMsg.set(res?.erro || 'Erro ao excluir a obra.');
        }
      },
      error: err => {
        this.errorMsg.set(err.error?.erro || 'Erro de conexão ao excluir.');
      }
    });
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

  protected formatarTipo(tipo: string): string {
    if (!tipo) return 'Livro';
    if (tipo === 'Peca de Arquitetura') return 'Peça de Arquitetura';
    if (tipo === 'Trabalho de Instrucao') return 'Trabalho de Instrução';
    return tipo;
  }
}
