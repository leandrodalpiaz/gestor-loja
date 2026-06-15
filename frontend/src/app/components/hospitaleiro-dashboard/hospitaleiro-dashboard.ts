import { Component, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { SupabaseService } from '../../services/supabase.service';
import { environment } from '../../../environments/environment';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-hospitaleiro-dashboard',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './hospitaleiro-dashboard.html',
  styleUrl: './hospitaleiro-dashboard.css'
})
export class HospitaleiroDashboard implements OnInit {
  private http = inject(HttpClient);
  protected supabaseService = inject(SupabaseService);

  protected loading = signal(true);
  protected errorMsg = signal<string | null>(null);
  protected successMsg = signal<string | null>(null);

  protected dataHospitaleiro = signal<any>(null);
  protected ocorrenciaForm: any = {
    tipo_ocorrencia: 'assistencia_geral',
    prioridade: 'media',
    obreiro_id: '',
    nome_familiar: '',
    parentesco: '',
    descricao: '',
    necessita_visita: false,
    necessita_apoio_financeiro: false,
    encaminhar_para: 'nenhum',
    data_ocorrencia: new Date().toISOString().slice(0, 10),
    data_proxima_acao: ''
  };

  ngOnInit(): void {
    this.carregarDados();
  }

  protected carregarDados(): void {
    this.loading.set(true);
    this.errorMsg.set(null);
    const headers = this.supabaseService.getAuthHeaders();

    const url = `${environment.apiUrl}/api/miniapp/hospitaleiro/dashboard`;

    this.http.get<any>(url, { headers }).subscribe({
      next: (res) => {
        this.loading.set(false);
        if (res && res.ok) {
          const payload = res.dados ?? res;
          this.dataHospitaleiro.set(payload);
        } else {
          this.errorMsg.set('Erro ao carregar o painel do Hospitaleiro.');
        }
      },
      error: (err) => {
        this.loading.set(false);
        this.errorMsg.set(err.error?.erro || 'Não foi possível carregar o painel do Hospitaleiro.');
      }
    });
  }

  protected salvarOcorrencia(): void {
    this.executarPost('/api/miniapp/hospitaleiro/ocorrencias/salvar', this.ocorrenciaForm, 'Ocorrência registrada com sucesso.');
  }

  protected atualizarOcorrencia(id: number, status: string): void {
    const observacao = prompt('Observação da atualização:', '') || '';
    this.executarPost('/api/miniapp/hospitaleiro/ocorrencias/status', {
      ocorrencia_id: id,
      status,
      observacao_status: observacao
    }, 'Status atualizado com sucesso.');
  }

  protected registrarVisita(id: number): void {
    const observacao = prompt('Observação da visita ou retorno:', '') || '';
    const data = prompt('Próxima ação (AAAA-MM-DD), se houver:', '') || '';
    this.executarPost('/api/miniapp/hospitaleiro/visita', {
      ocorrencia_id: id,
      observacao_visita: observacao,
      data_proxima_acao: data
    }, 'Visita registrada com sucesso.');
  }

  private executarPost(path: string, body: any, sucesso: string): void {
    this.loading.set(true);
    this.http.post<any>(`${environment.apiUrl}${path}`, body, {
      headers: this.supabaseService.getAuthHeaders()
    }).subscribe({
      next: (res) => {
        this.loading.set(false);
        if (res?.ok) {
          this.successMsg.set(sucesso);
          // Limpa form ao salvar com sucesso
          if (path.includes('salvar')) {
            this.ocorrenciaForm = {
              tipo_ocorrencia: 'assistencia_geral',
              prioridade: 'media',
              obreiro_id: '',
              nome_familiar: '',
              parentesco: '',
              descricao: '',
              necessita_visita: false,
              necessita_apoio_financeiro: false,
              encaminhar_para: 'nenhum',
              data_ocorrencia: new Date().toISOString().slice(0, 10),
              data_proxima_acao: ''
            };
          }
          this.carregarDados();
        } else {
          this.errorMsg.set(res?.erro || 'Não foi possível concluir a operação.');
        }
      },
      error: (err) => {
        this.loading.set(false);
        this.errorMsg.set(err.error?.erro || 'Erro ao concluir a operação.');
      }
    });
  }
}
