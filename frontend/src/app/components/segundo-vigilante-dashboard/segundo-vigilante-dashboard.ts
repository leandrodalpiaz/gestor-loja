import { Component, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { SupabaseService } from '../../services/supabase.service';
import { environment } from '../../../environments/environment';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-segundo-vigilante-dashboard',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './segundo-vigilante-dashboard.html',
  styleUrl: './segundo-vigilante-dashboard.css'
})
export class SegundoVigilanteDashboard implements OnInit {
  private http = inject(HttpClient);
  protected supabaseService = inject(SupabaseService);

  protected loading = signal(true);
  protected errorMsg = signal<string | null>(null);
  protected successMsg = signal<string | null>(null);

  protected dataVigilante = signal<any>(null);
  protected selectedCompanheiroId = signal<string | null>(null);

  ngOnInit(): void {
    this.carregarDados();
  }

  protected carregarDados(): void {
    this.loading.set(true);
    this.errorMsg.set(null);
    const headers = this.supabaseService.getAuthHeaders();

    const url = `${environment.apiUrl}/api/miniapp/segundo-vigilante/dashboard${this.selectedCompanheiroId() ? `?companheiro_id=${encodeURIComponent(this.selectedCompanheiroId()!)}` : ''}`;

    this.http.get<any>(url, { headers }).subscribe({
      next: (res) => {
        this.loading.set(false);
        if (res && res.ok) {
          const payload = res.dados ?? res;
          this.dataVigilante.set(payload);
          if (!this.selectedCompanheiroId() && payload?.companheiro_foco?.companheiro?.id) {
            this.selectedCompanheiroId.set(payload.companheiro_foco.companheiro.id);
          }
        } else {
          this.errorMsg.set('Erro ao carregar o painel do 2º Vigilante.');
        }
      },
      error: (err) => {
        this.loading.set(false);
        this.errorMsg.set(err.error?.erro || 'Não foi possível carregar o painel do 2º Vigilante.');
      }
    });
  }

  protected selecionarFormando(event: Event): void {
    const id = (event.target as HTMLSelectElement).value || null;
    this.selectedCompanheiroId.set(id);
    this.carregarDados();
  }

  protected atualizarEtapa(ordem: number, statusAtual: string): void {
    const novoStatus = prompt('Novo status da etapa:', statusAtual);
    if (!novoStatus) return;
    const id = this.selectedCompanheiroId();
    this.executarPost('/api/miniapp/segundo-vigilante/trilha/atualizar', {
      companheiro_id: id,
      etapa_ordem: ordem,
      status: novoStatus
    }, 'Etapa de progresso do Companheiro atualizada.');
  }

  protected salvarLeitura(): void {
    const painel = this.dataVigilante();
    const id = this.selectedCompanheiroId();
    const acervoId = Number(prompt('ID da obra do acervo:', String(painel?.companheiro_foco?.leitura_sugerida?.acervo_id || ''))) || null;
    const observacao = prompt('Orientação de leitura:', '') || '';
    this.executarPost('/api/miniapp/segundo-vigilante/leitura/salvar', {
      companheiro_id: id,
      acervo_id: acervoId,
      observacao_leitura: observacao
    }, 'Orientação de leitura sugerida com sucesso.');
  }

  protected solicitarCertificado(): void {
    const id = this.selectedCompanheiroId();
    this.executarPost('/api/miniapp/segundo-vigilante/certificado/solicitar', {
      companheiro_id: id,
      observacao_certificado: prompt('Observação do certificado:', '') || ''
    }, 'Solicitação de certificado enviada.');
  }

  protected recomendarExaltacao(): void {
    this.executarPost('/api/miniapp/segundo-vigilante/exaltacao/recomendar', {
      companheiro_id: this.selectedCompanheiroId(),
      observacao_exaltacao: prompt('Observação da recomendação:', '') || ''
    }, 'Recomendação de exaltação enviada.');
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
