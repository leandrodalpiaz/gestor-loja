import { CommonModule } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { Component, inject, OnInit, signal, ViewChild, ElementRef } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { environment } from '../../../environments/environment';
import { SupabaseService } from '../../services/supabase.service';
import { Chart, registerables } from 'chart.js/auto';

Chart.register(...registerables);

@Component({
  selector: 'app-tesouraria-fechamento',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './tesouraria-fechamento.html',
  styleUrl: './tesouraria-fechamento.css'
})
export class TesourariaFechamento implements OnInit {
  private http = inject(HttpClient);
  private auth = inject(SupabaseService);

  @ViewChild('chartPizzaCanvas') private chartPizzaCanvas!: ElementRef<HTMLCanvasElement>;
  private chartPizzaInstance: Chart | null = null;

  protected mes = new Date().getMonth() + 1;
  protected ano = new Date().getFullYear();
  protected fechamento = signal<any>(null);
  protected fechamentoAnterior = signal<any>(null);
  protected tronco = signal<any>(null);
  protected inadimplencia = signal<any[]>([]);
  protected creditosAReceber = signal<any>(null);
  protected erro = signal('');
  protected salvando = signal(false);
  protected meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'].map((l, i) => ({ v: i + 1, l }));

  ngOnInit() {
    this.carregar();
  }

  protected carregar() {
    this.erro.set('');
    this.http.get<any>(
      `${environment.apiUrl}/api/tesouraria/fechamento?mes=${this.mes}&ano=${this.ano}`,
      { headers: this.auth.getAuthHeaders() }
    ).subscribe({
      next: r => {
        if (r && r.ok) {
          this.fechamento.set(r.fechamento);
          this.fechamentoAnterior.set(r.anterior);
          this.tronco.set(r.tronco);
          this.inadimplencia.set(r.inadimplencia || []);
          this.creditosAReceber.set(r.creditos_a_receber);

          // Renderizar o gráfico de pizza após atualização dos dados
          setTimeout(() => {
            const entradas = Number(r.fechamento?.total_entradas || 0);
            const saidas = Number(r.fechamento?.total_saidas || 0);
            this.renderizarPizza(entradas, saidas);
          }, 100);
        } else {
          this.erro.set(r.erro || 'Falha ao carregar o fechamento.');
        }
      },
      error: () => this.erro.set('Falha ao carregar o fechamento.')
    });
  }

  private renderizarPizza(entradas: number, saidas: number) {
    if (!this.chartPizzaCanvas) return;
    
    if (this.chartPizzaInstance) {
      this.chartPizzaInstance.destroy();
    }

    if (entradas === 0 && saidas === 0) {
      return; // Sem dados para renderizar pizza
    }

    this.chartPizzaInstance = new Chart(this.chartPizzaCanvas.nativeElement, {
      type: 'pie',
      data: {
        labels: ['Receitas (Entradas)', 'Despesas (Saídas)'],
        datasets: [{
          data: [entradas, saidas],
          backgroundColor: ['rgba(16, 185, 129, 0.75)', 'rgba(239, 68, 68, 0.75)'],
          borderWidth: 1,
          borderColor: 'rgba(11, 19, 43, 0.65)'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'bottom',
            labels: { color: '#94a3b8', font: { family: 'Inter', size: 10 } }
          }
        }
      }
    });
  }

  protected editarSaldo() {
    const f = this.fechamento();
    if (!f) return;

    const valor = prompt('Novo saldo inicial:', String(f.saldo_inicial));
    if (valor === null) return;

    const justificativa = prompt('Justificativa obrigatória:', 'Ajuste conferido pela Tesouraria.');
    if (!justificativa) return;

    this.http.post<any>(
      `${environment.apiUrl}/api/tesouraria/fechamento/saldo-inicial`,
      { fechamento_id: f.id, novo_saldo: Number(valor), justificativa },
      { headers: this.auth.getAuthHeaders() }
    ).subscribe(r => {
      if (r && r.ok) {
        this.carregar();
      } else {
        this.erro.set(r.erro || 'Falha ao atualizar saldo.');
      }
    });
  }

  protected fechar() {
    const f = this.fechamento();
    if (!f) return;

    if (!confirm(`Confirma o fechamento de ${this.mes}/${this.ano}?`)) return;
    this.salvando.set(true);

    this.http.post<any>(
      `${environment.apiUrl}/api/tesouraria/fechamento/fechar`,
      { mes: this.mes, ano: this.ano, fechamento_id: f.id },
      { headers: this.auth.getAuthHeaders() }
    ).subscribe({
      next: r => {
        this.salvando.set(false);
        if (r && r.ok) {
          this.carregar();
        } else {
          this.erro.set(r.erro || 'Falha ao fechar competência.');
        }
      },
      error: () => {
        this.salvando.set(false);
        this.erro.set('Falha ao fechar competência.');
      }
    });
  }

  protected imprimir() {
    window.print();
  }
}
