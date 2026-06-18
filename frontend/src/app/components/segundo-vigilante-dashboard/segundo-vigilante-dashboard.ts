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

  // Controle de Edição de Etapa Inline
  protected expandedEtapa = signal<number | null>(null);
  protected editStatus = signal<string>('');
  protected editObservacao = signal<string>('');
  protected selectedFile: File | null = null;
  protected publicarBiblioteca = signal<boolean>(false);
  protected apiUrl = environment.apiUrl;
  protected msgTexto: string = '';
  protected enviandoMensagem = signal<boolean>(false);

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
    this.expandedEtapa.set(null);
    this.selectedFile = null;
    this.carregarDados();
  }

  protected toggleExpandirEtapa(etapa: any): void {
    if (this.expandedEtapa() === etapa.ordem) {
      this.expandedEtapa.set(null);
      this.selectedFile = null;
      this.publicarBiblioteca.set(false);
    } else {
      this.expandedEtapa.set(etapa.ordem);
      this.editStatus.set(etapa.status);
      this.editObservacao.set(etapa.observacao_vigilante || '');
      this.selectedFile = null;
      this.publicarBiblioteca.set(false);
    }
  }

  protected onFileSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    if (input.files && input.files.length > 0) {
      this.selectedFile = input.files[0];
    }
  }

  protected salvarEtapa(ordem: number): void {
    const id = this.selectedCompanheiroId();
    if (!id) return;

    this.loading.set(true);
    this.errorMsg.set(null);
    this.successMsg.set(null);

    const formData = new FormData();
    formData.append('companheiro_id', id);
    formData.append('etapa_ordem', String(ordem));
    formData.append('status', this.editStatus());
    formData.append('observacao_vigilante', this.editObservacao());
    formData.append('publicar_biblioteca', this.publicarBiblioteca() ? '1' : '0');
    
    if (this.selectedFile) {
      formData.append('trabalho', this.selectedFile);
    }

    const headers = this.supabaseService.getAuthHeaders();

    this.http.post<any>(`${environment.apiUrl}/api/miniapp/segundo-vigilante/trilha/atualizar`, formData, {
      headers
    }).subscribe({
      next: (res) => {
        this.loading.set(false);
        if (res?.ok) {
          this.successMsg.set('Etapa do Companheiro atualizada com sucesso.');
          this.expandedEtapa.set(null);
          this.selectedFile = null;
          this.carregarDados();
        } else {
          this.errorMsg.set(res?.erro || 'Não foi possível atualizar a etapa da trilha.');
        }
      },
      error: (err) => {
        this.loading.set(false);
        this.errorMsg.set(err.error?.erro || 'Erro ao atualizar a etapa.');
      }
    });
  }

  protected alternarEtapaOral(ordem: number, concluida: boolean): void {
    const id = this.selectedCompanheiroId();
    if (!id) return;

    this.loading.set(true);
    this.errorMsg.set(null);
    this.successMsg.set(null);

    const formData = new FormData();
    formData.append('companheiro_id', id);
    formData.append('etapa_ordem', String(ordem));
    formData.append('status', concluida ? 'concluido' : 'nao_iniciado');
    formData.append('observacao_vigilante', '');

    const headers = this.supabaseService.getAuthHeaders();

    this.http.post<any>(`${environment.apiUrl}/api/miniapp/segundo-vigilante/trilha/atualizar`, formData, {
      headers
    }).subscribe({
      next: (res) => {
        this.loading.set(false);
        if (res?.ok) {
          this.successMsg.set('Instrução oral atualizada com sucesso.');
          this.carregarDados();
        } else {
          this.errorMsg.set(res?.erro || 'Não foi possível atualizar a etapa oral.');
        }
      },
      error: (err) => {
        this.loading.set(false);
        this.errorMsg.set(err.error?.erro || 'Erro ao atualizar a etapa oral.');
      }
    });
  }

  protected publicarTrabalhoDireto(etapa: any, event: Event): void {
    event.stopPropagation();
    const id = this.selectedCompanheiroId();
    if (!id) return;

    this.loading.set(true);
    this.errorMsg.set(null);
    this.successMsg.set(null);

    const formData = new FormData();
    formData.append('companheiro_id', id);
    formData.append('etapa_ordem', String(etapa.ordem));
    formData.append('status', 'concluido');
    formData.append('observacao_vigilante', etapa.observacao_vigilante || '');
    formData.append('publicar_biblioteca', '1');

    const headers = this.supabaseService.getAuthHeaders();

    this.http.post<any>(`${environment.apiUrl}/api/miniapp/segundo-vigilante/trilha/atualizar`, formData, {
      headers
    }).subscribe({
      next: (res) => {
        this.loading.set(false);
        if (res?.ok) {
          this.successMsg.set('Trabalho publicado na biblioteca com sucesso.');
          this.carregarDados();
        } else {
          this.errorMsg.set(res?.erro || 'Não foi possível publicar o trabalho.');
        }
      },
      error: (err) => {
        this.loading.set(false);
        this.errorMsg.set(err.error?.erro || 'Erro ao publicar o trabalho.');
      }
    });
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
      observacao_exaltacao: prompt('Observação da recomendação de Exaltação:', '') || ''
    }, 'Recomendação de exaltação enviada.');
  }

  protected enviarMensagem(etapaOrdem: number): void {
    const msg = this.msgTexto.trim();
    if (!msg) return;

    const id = this.selectedCompanheiroId();
    if (!id) return;

    this.enviandoMensagem.set(true);
    this.http.post<any>(`${environment.apiUrl}/api/miniapp/segundo-vigilante/trilha/mensagem`, {
      companheiro_id: id,
      etapa_ordem: etapaOrdem,
      mensagem: msg
    }, {
      headers: this.supabaseService.getAuthHeaders()
    }).subscribe({
      next: (res) => {
        this.enviandoMensagem.set(false);
        if (res?.ok) {
          this.msgTexto = '';
          this.carregarDados();
        } else {
          this.errorMsg.set(res?.erro || 'Erro ao enviar mensagem.');
        }
      },
      error: (err) => {
        this.enviandoMensagem.set(false);
        this.errorMsg.set(err.error?.erro || 'Erro na conexão com o servidor.');
      }
    });
  }

  protected calcularProgressoIntersticio(dataInicioStr: string, mesesNecessarios: number): { percentual: number, concluido: boolean, mesesRestantes: number, textoProgresso: string } {
    if (!dataInicioStr) {
      return { percentual: 0, concluido: false, mesesRestantes: mesesNecessarios, textoProgresso: 'Elevação não registrada' };
    }
    const inicio = new Date(dataInicioStr);
    if (isNaN(inicio.getTime())) {
      return { percentual: 0, concluido: false, mesesRestantes: mesesNecessarios, textoProgresso: 'Data inválida' };
    }
    const hoje = new Date();
    
    let diffMeses = (hoje.getFullYear() - inicio.getFullYear()) * 12 + (hoje.getMonth() - inicio.getMonth());
    if (hoje.getDate() < inicio.getDate()) {
      diffMeses--;
    }
    if (diffMeses < 0) diffMeses = 0;
    
    const percentual = Math.min(100, Math.floor((diffMeses / mesesNecessarios) * 100));
    const concluido = percentual >= 100;
    const mesesRestantes = Math.max(0, mesesNecessarios - diffMeses);
    
    const textoProgresso = concluido 
      ? 'Interstício cumprido!' 
      : `${diffMeses} de ${mesesNecessarios} meses completos (${percentual}%)`;

    return { percentual, concluido, mesesRestantes, textoProgresso };
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
