import { Component, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { SupabaseService } from '../../services/supabase.service';
import { environment } from '../../../environments/environment';

@Component({
  selector: 'app-tesouraria-regularidade',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './tesouraria-regularidade.html',
  styleUrl: './tesouraria-regularidade.css'
})
export class TesourariaRegularidade implements OnInit {
  private http = inject(HttpClient);
  private supabaseService = inject(SupabaseService);

  // Filtros de data
  protected mesSelecionado = signal<number>(new Date().getMonth() + 1);
  protected anoSelecionado = signal<number>(new Date().getFullYear());
  protected anosDisponiveis = [new Date().getFullYear() - 1, new Date().getFullYear(), new Date().getFullYear() + 1];
  protected meses = [
    { value: 1, label: 'Janeiro' },
    { value: 2, label: 'Fevereiro' },
    { value: 3, label: 'Março' },
    { value: 4, label: 'Abril' },
    { value: 5, label: 'Maio' },
    { value: 6, label: 'Junho' },
    { value: 7, label: 'Julho' },
    { value: 8, label: 'Agosto' },
    { value: 9, label: 'Setembro' },
    { value: 10, label: 'Outubro' },
    { value: 11, label: 'Novembro' },
    { value: 12, label: 'Dezembro' }
  ];

  // Listagens e loadings
  protected obreirosRegularidade = signal<any[]>([]);
  protected loading = signal(false);
  protected errorMsg = signal<string | null>(null);
  protected definindoStatus = signal(false);

  // Modal e edições
  protected showModalDefinir = signal(false);
  protected obreiroSelecionado = signal<any | null>(null);
  protected novoStatus = signal<'regular' | 'irregular'>('regular');
  protected novaObservacao = signal('');

  ngOnInit(): void {
    this.carregarRegularidades();
  }

  protected carregarRegularidades(): void {
    this.loading.set(true);
    this.errorMsg.set(null);
    const headers = this.supabaseService.getAuthHeaders();

    this.http.get<any>(
      `${environment.apiUrl}/api/tesouraria/regularidade?mes=${this.mesSelecionado()}&ano=${this.anoSelecionado()}`,
      { headers }
    ).subscribe({
      next: (res) => {
        if (res && res.ok) {
          this.obreirosRegularidade.set(res.regularidade || []);
        } else {
          this.errorMsg.set(res.erro || 'Falha ao carregar listagem de regularidade.');
        }
        this.loading.set(false);
      },
      error: (err) => {
        console.error('[Tesouraria] Erro ao buscar regularidades:', err);
        this.errorMsg.set('Erro de conexão ao carregar dados do PHP.');
        this.loading.set(false);
      }
    });
  }

  protected abrirModalDefinir(item: any): void {
    this.obreiroSelecionado.set(item);
    this.novoStatus.set(item.status === 'regular' ? 'regular' : 'irregular');
    this.novaObservacao.set(item.observacao || '');
    this.showModalDefinir.set(true);
  }

  protected salvarDefinicao(): void {
    const obreiro = this.obreiroSelecionado();
    if (!obreiro) return;

    this.definindoStatus.set(true);
    const headers = this.supabaseService.getAuthHeaders();

    const payload = {
      obreiro_id: obreiro.obreiro_id,
      mes: this.mesSelecionado(),
      ano: this.anoSelecionado(),
      status: this.novoStatus(),
      observacao: this.novaObservacao().trim() || null
    };

    this.http.post<any>(
      `${environment.apiUrl}/api/tesouraria/regularidade/definir`,
      payload,
      { headers }
    ).subscribe({
      next: (res) => {
        if (res && res.ok) {
          this.showModalDefinir.set(false);
          this.carregarRegularidades();
        } else {
          alert(res.erro || 'Falha ao salvar regularidade.');
        }
        this.definindoStatus.set(false);
      },
      error: (err) => {
        console.error('[Tesouraria] Erro ao salvar regularidade:', err);
        alert('Erro de conexão.');
        this.definindoStatus.set(false);
      }
    });
  }

  protected definirTodosRegulares(): void {
    if (!confirm('Deseja realmente marcar TODOS os obreiros como REGULARES neste mês?')) {
      return;
    }

    this.loading.set(true);
    const headers = this.supabaseService.getAuthHeaders();

    const payload = {
      mes: this.mesSelecionado(),
      ano: this.anoSelecionado(),
      status: 'regular'
    };

    this.http.post<any>(
      `${environment.apiUrl}/api/tesouraria/regularidade/definir-todos`,
      payload,
      { headers }
    ).subscribe({
      next: (res) => {
        if (res && res.ok) {
          this.carregarRegularidades();
        } else {
          alert('Falha ao redefinir a regularidade de todos.');
          this.loading.set(false);
        }
      },
      error: (err) => {
        console.error('[Tesouraria] Erro ao redefinir todos:', err);
        this.loading.set(false);
      }
    });
  }
}
