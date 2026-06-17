import { CommonModule } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { Component, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { environment } from '../../../environments/environment';
import { SupabaseService } from '../../services/supabase.service';

@Component({
  selector: 'app-tesouraria-relatorio-gestao',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './tesouraria-relatorio-gestao.html',
  styleUrl: './tesouraria-relatorio-gestao.css'
})
export class TesourariaRelatorioGestao implements OnInit {
  private http = inject(HttpClient);
  private auth = inject(SupabaseService);

  protected readonly referenciasRelatorio = [
    {
      titulo: 'Receitas ordinarias',
      bloco: 'receitas_ordinarias',
      exemplos: ['Mensalidade']
    },
    {
      titulo: 'Receitas eventuais',
      bloco: 'receitas_eventuais',
      exemplos: ['Contribuicao a Biblioteca', 'Doacoes']
    },
    {
      titulo: 'Capitacoes',
      bloco: 'capitacoes',
      exemplos: ['Joias', 'Iniciacao', 'Elevacao', 'Exaltacao', 'Regularizacao', 'Filiacao']
    },
    {
      titulo: 'Agapes e eventos',
      bloco: 'agapes_eventos',
      exemplos: ['Agape', 'Despesas Agape', 'Aluguel Salao de Agapes']
    },
    {
      titulo: 'Despesas administrativas',
      bloco: 'despesas_administrativas',
      exemplos: ['Aluguel Templo', 'Aluguel', 'Grafica', 'Despesas Cartorio', 'Despesas Diversas da Loja']
    },
    {
      titulo: 'Despesas de potencia e ritualistica',
      bloco: 'despesas_potencia',
      exemplos: ['Despesas Grande Loja', 'A Trolha']
    },
    {
      titulo: 'Financeiro e banco',
      bloco: 'receitas_financeiras',
      exemplos: ['Juros Aplicacao Bancaria', 'Despesas Bancarias']
    },
    {
      titulo: 'Tronco e entidades',
      bloco: 'tronco',
      exemplos: ['Tronco de Solidariedade', 'Despesas Tronco de Solidariedade']
    }
  ];

  private readonly rotulosBloco: Record<string, string> = {
    receitas_ordinarias: 'Entradas ordinarias',
    receitas_eventuais: 'Entradas eventuais',
    receitas_financeiras: 'Entradas financeiras',
    capitacoes: 'Capitacoes',
    agapes_eventos: 'Agapes e eventos',
    despesas_potencia: 'Saidas com a Potencia',
    despesas_administrativas: 'Saidas administrativas',
    despesas_bancarias: 'Saidas bancarias',
    despesas_ritualisticas: 'Saidas ritualisticas',
    tronco: 'Tronco de solidariedade',
    entidades_auxiliadas: 'Entidades auxiliadas',
    outros: 'Outros'
  };

  protected gestoes = signal<any[]>([]);
  protected relatorio = signal<any>(null);
  protected gestaoId = 0;
  protected fim = '';
  protected erro = signal('');

  ngOnInit() {
    this.carregar();
  }

  protected carregar() {
    const q = new URLSearchParams();
    if (this.gestaoId) {
      q.set('gestao_id', String(this.gestaoId));
    }
    if (this.fim) {
      q.set('encerramento_em', this.fim);
    }

    this.http.get<any>(
      `${environment.apiUrl}/api/tesouraria/relatorio-gestao?${q}`,
      { headers: this.auth.getAuthHeaders() }
    ).subscribe({
      next: r => {
        this.gestoes.set(r.gestoes || []);
        this.relatorio.set(r.relatorio);
        if (!this.gestaoId && r.relatorio) {
          this.gestaoId = Number(r.relatorio.gestao.id);
        }
        if (!this.fim && r.relatorio) {
          this.fim = r.relatorio.periodo.fim_data;
        }
        this.erro.set(r.ok ? '' : r.erro);
      },
      error: () => this.erro.set('Falha ao carregar o relatório financeiro.')
    });
  }

  protected blocos(v: any): Array<{ nome: string; valor: any }> {
    return Object.entries(v || {}).map(([nome, valor]) => ({ nome, valor }));
  }

  protected rotulo(v: string) {
    return this.rotulosBloco[v] || v.replaceAll('_', ' ').replace(/^\w/, c => c.toUpperCase());
  }

  protected imprimir() {
    window.print();
  }
}
