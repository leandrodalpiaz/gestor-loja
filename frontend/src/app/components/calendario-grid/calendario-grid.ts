import { Component, inject, OnInit, signal, computed } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { SupabaseService } from '../../services/supabase.service';
import { environment } from '../../../environments/environment';

export interface CalendarioEvento {
  id: number;
  data_hora_inicio: string;
  data_hora_fim: string;
  tipo_sessao: string;
  grau_sessao: string;
  titulo: string;
  status: string;
  agape_modalidade: string;
  agape_valor: string;
  total_confirmados: number;
}

export interface DiaCalendario {
  dataStr: string;
  diaNum: number;
  isMesAtual: boolean;
  eventos: CalendarioEvento[];
}

@Component({
  selector: 'app-calendario-grid',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './calendario-grid.html',
  styleUrl: './calendario-grid.css'
})
export class CalendarioGrid implements OnInit {
  private http = inject(HttpClient);
  private supabaseService = inject(SupabaseService);

  protected loading = signal(false);
  protected errorMsg = signal<string | null>(null);
  protected eventos = signal<CalendarioEvento[]>([]);
  
  // Data selecionada
  protected dataAtual = signal(new Date());
  protected anoAtual = computed(() => this.dataAtual().getFullYear());
  protected mesAtual = computed(() => this.dataAtual().getMonth());

  protected nomesMeses = [
    'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
    'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'
  ];

  protected diasSemana = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];

  // Evento selecionado para exibição de detalhes
  protected eventoSelecionado = signal<CalendarioEvento | null>(null);

  // Computado que monta o grid com os dias do mês
  protected gridDias = computed(() => {
    const year = this.anoAtual();
    const month = this.mesAtual();
    const evs = this.eventos();

    const primeiroDiaSemana = new Date(year, month, 1).getDay();
    const totalDiasMes = new Date(year, month + 1, 0).getDate();
    const totalDiasMesAnterior = new Date(year, month, 0).getDate();

    const dias: DiaCalendario[] = [];

    // Preenche os dias do mês anterior
    for (let i = primeiroDiaSemana - 1; i >= 0; i--) {
      const dia = totalDiasMesAnterior - i;
      const dataStr = `${year}-${String(month).padStart(2, '0')}-${String(dia).padStart(2, '0')}`;
      dias.push({
        dataStr,
        diaNum: dia,
        isMesAtual: false,
        eventos: this.filtrarEventosPorData(evs, dataStr)
      });
    }

    // Preenche os dias do mês atual
    for (let dia = 1; dia <= totalDiasMes; dia++) {
      const dataStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(dia).padStart(2, '0')}`;
      dias.push({
        dataStr,
        diaNum: dia,
        isMesAtual: true,
        eventos: this.filtrarEventosPorData(evs, dataStr)
      });
    }

    // Preenche com os dias do próximo mês para completar o grid (múltiplo de 7, geralmente 35 ou 42 slots)
    const restante = 42 - dias.length;
    for (let dia = 1; dia <= restante; dia++) {
      const dataStr = `${year}-${String(month + 2).padStart(2, '0')}-${String(dia).padStart(2, '0')}`;
      dias.push({
        dataStr,
        diaNum: dia,
        isMesAtual: false,
        eventos: this.filtrarEventosPorData(evs, dataStr)
      });
    }

    return dias;
  });

  ngOnInit(): void {
    this.carregarEventos();
  }

  protected carregarEventos(): void {
    this.loading.set(true);
    this.errorMsg.set(null);
    const headers = this.supabaseService.getAuthHeaders();

    const ano = this.anoAtual();
    const mes = this.mesAtual();
    
    // Define a data de início e fim do mês correspondente para consulta da API
    const dataInicio = `${ano}-${String(mes + 1).padStart(2, '0')}-01`;
    const dataFim = `${ano}-${String(mes + 1).padStart(2, '0')}-${new Date(ano, mes + 1, 0).getDate()}`;

    this.http.get<{ ok: boolean; eventos?: CalendarioEvento[]; erro?: string }>(
      `${environment.apiUrl}/api/calendario/eventos?inicio=${dataInicio}&fim=${dataFim}`,
      { headers }
    ).subscribe({
      next: (res) => {
        this.loading.set(false);
        if (res && res.ok && res.eventos) {
          this.eventos.set(res.eventos);
        } else {
          this.errorMsg.set(res.erro || 'Falha ao buscar sessões do calendário.');
        }
      },
      error: (err) => {
        this.loading.set(false);
        console.error('[Calendário] Erro de rede:', err);
        this.errorMsg.set(err.error?.erro || 'Erro de conexão ao servidor.');
      }
    });
  }

  private filtrarEventosPorData(evs: CalendarioEvento[], dataStr: string): CalendarioEvento[] {
    return evs.filter(e => {
      if (!e.data_hora_inicio) return false;
      const dataEvento = e.data_hora_inicio.split('T')[0];
      return dataEvento === dataStr;
    });
  }

  protected anterior(): void {
    const d = this.dataAtual();
    this.dataAtual.set(new Date(d.getFullYear(), d.getMonth() - 1, 1));
    this.carregarEventos();
  }

  protected proximo(): void {
    const d = this.dataAtual();
    this.dataAtual.set(new Date(d.getFullYear(), d.getMonth() + 1, 1));
    this.carregarEventos();
  }

  protected selecionarEvento(ev: CalendarioEvento): void {
    this.eventoSelecionado.set(ev);
  }

  protected fecharModal(): void {
    this.eventoSelecionado.set(null);
  }
}
