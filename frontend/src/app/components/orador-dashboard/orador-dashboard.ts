import { Component, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { SupabaseService } from '../../services/supabase.service';
import { environment } from '../../../environments/environment';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-orador-dashboard',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './orador-dashboard.html',
  styleUrl: './orador-dashboard.css'
})
export class OradorDashboard implements OnInit {
  private http = inject(HttpClient);
  protected supabaseService = inject(SupabaseService);

  protected loading = signal(true);
  protected errorMsg = signal<string | null>(null);
  protected successMsg = signal<string | null>(null);

  protected dataOrador = signal<any>(null);
  protected selectedSessaoId = signal<number | null>(null);

  // Controle de Abas
  protected activeTab = signal<string>('roteiro'); // 'roteiro' | 'jornada' | 'reconhecimento'

  // Simulador de Templo
  protected simPresenterId = signal<string>('');
  protected simStepNum = signal<number>(1);
  protected simClearanceList = signal<any[]>([]);
  protected simVeneravelPhrase = signal<string>('');

  // Reconhecimento Simbólico
  protected reconhecimentos = signal<any[]>([]);
  protected loadingReconhecimentos = signal<boolean>(false);
  protected recObreiroId = signal<string>('');
  protected recTipo = signal<string>('gratidao');
  protected recDescricao = signal<string>('');

  protected etapasAprendiz = [
    { num: 1, label: 'Etapa 1: Impressões de iniciação' },
    { num: 2, label: 'Etapa 2: Complemento à iniciação (Oral)' },
    { num: 3, label: 'Etapa 3: 1ª instrução (Oral)' },
    { num: 4, label: 'Etapa 4: Trabalho 1ª instrução (Escrito)' },
    { num: 5, label: 'Etapa 5: 2ª instrução (Oral)' },
    { num: 6, label: 'Etapa 6: Trabalho 2ª instrução (Escrito)' },
    { num: 7, label: 'Etapa 7: 3ª instrução (Oral)' },
    { num: 8, label: 'Etapa 8: Trabalho 3ª instrução (Escrito)' },
    { num: 9, label: 'Etapa 9: 4ª instrução (Oral)' },
    { num: 10, label: 'Etapa 10: Trabalho 4ª instrução (Escrito)' },
    { num: 11, label: 'Etapa 11: 5ª instrução (Oral)' },
    { num: 12, label: 'Etapa 12: Trabalho 5ª instrução (Escrito)' },
    { num: 13, label: 'Etapa 13: Certificado docência (Oral)' }
  ];

  protected etapasCompanheiro = [
    { num: 1, label: 'Etapa 1: Impressões da elevação' },
    { num: 2, label: 'Etapa 2: 1ª instrução (Oral)' },
    { num: 3, label: 'Etapa 3: Trabalho 1ª instrução (Escrito)' },
    { num: 4, label: 'Etapa 4: 2ª instrução (Oral)' },
    { num: 5, label: 'Etapa 5: Trabalho 2ª instrução (Escrito)' },
    { num: 6, label: 'Etapa 6: 3ª instrução (Oral)' },
    { num: 7, label: 'Etapa 7: Trabalho 3ª instrução (Escrito)' },
    { num: 8, label: 'Etapa 8: Registrar docência (Oral)' },
    { num: 9, label: 'Etapa 9: Certificado docência (Oral)' },
    { num: 10, label: 'Etapa 10: Indicar exaltação Mestre (Oral)' }
  ];

  ngOnInit(): void {
    this.carregarDados();
    this.carregarReconhecimentos();
  }

  protected setTab(tab: string): void {
    this.activeTab.set(tab);
    this.errorMsg.set(null);
    this.successMsg.set(null);
  }

  protected carregarDados(): void {
    this.loading.set(true);
    this.errorMsg.set(null);
    const headers = this.supabaseService.getAuthHeaders();

    const sessaoId = this.selectedSessaoId();
    const url = `${environment.apiUrl}/api/miniapp/orador/dashboard${sessaoId ? `?sessao_id=${encodeURIComponent(String(sessaoId))}` : ''}`;

    this.http.get<any>(url, { headers }).subscribe({
      next: (res) => {
        this.loading.set(false);
        if (res && res.ok) {
          const payload = res.dados ?? res;
          this.dataOrador.set(payload);
          const sessaoFocoId = Number(payload?.sessao_foco?.id || 0);
          if (sessaoFocoId > 0 && !this.selectedSessaoId()) {
            this.selectedSessaoId.set(sessaoFocoId);
          }
          this.executarSimulacao();
        } else {
          this.errorMsg.set('Erro ao carregar o painel do Orador.');
        }
      },
      error: (err) => {
        this.loading.set(false);
        this.errorMsg.set(err.error?.erro || 'Não foi possível carregar o painel do Orador.');
      }
    });
  }

  protected onSessaoChange(event: Event): void {
    const target = event.target as HTMLSelectElement | null;
    const sessaoId = target ? Number(target.value) : 0;
    this.selectedSessaoId.set(sessaoId > 0 ? sessaoId : null);
    this.carregarDados();
  }

  // Lógica do Simulador
  protected onPresenterChange(event: Event): void {
    const target = event.target as HTMLSelectElement | null;
    const presenterId = target ? target.value : '';
    this.simPresenterId.set(presenterId);

    if (presenterId === '') {
      this.simClearanceList.set([]);
      this.simVeneravelPhrase.set('');
      return;
    }

    // Tenta encontrar o obreiro nas listas para setar a etapa default
    const aprendiz = this.dataOrador()?.aprendizes?.find((a: any) => a.id === presenterId);
    if (aprendiz) {
      this.simStepNum.set(aprendiz.etapa_atual);
    } else {
      const companheiro = this.dataOrador()?.companheiros?.find((c: any) => c.id === presenterId);
      if (companheiro) {
        this.simStepNum.set(companheiro.etapa_atual);
      }
    }

    this.executarSimulacao();
  }

  protected onStepChange(event: Event): void {
    const target = event.target as HTMLSelectElement | null;
    const step = target ? Number(target.value) : 1;
    this.simStepNum.set(step);
    this.executarSimulacao();
  }

  protected executarSimulacao(): void {
    const presenterId = this.simPresenterId();
    if (!presenterId) {
      this.simClearanceList.set([]);
      this.simVeneravelPhrase.set('');
      return;
    }

    const data = this.dataOrador();
    if (!data) return;

    const stepNum = this.simStepNum();
    const clearance: any[] = [];

    // Verifica se o apresentador é Aprendiz ou Companheiro
    const isPresenterAprendiz = data.aprendizes?.some((a: any) => a.id === presenterId);
    const isPresenterCompanheiro = data.companheiros?.some((c: any) => c.id === presenterId);

    if (isPresenterAprendiz) {
      // Apresentação de Aprendiz: apenas aprendizes que NÃO concluíram esta etapa devem cobrir
      if (data.aprendizes) {
        for (const a of data.aprendizes) {
          if (a.id !== presenterId && a.etapa_atual < stepNum) {
            clearance.push(a);
          }
        }
      }
    } else if (isPresenterCompanheiro) {
      // Apresentação de Companheiro: TODOS os aprendizes devem cobrir,
      // e companheiros que NÃO concluíram esta etapa do grau 2
      if (data.aprendizes) {
        for (const a of data.aprendizes) {
          clearance.push(a);
        }
      }
      if (data.companheiros) {
        for (const c of data.companheiros) {
          if (c.id !== presenterId && c.etapa_atual < stepNum) {
            clearance.push(c);
          }
        }
      }
    }

    this.simClearanceList.set(clearance);

    if (clearance.length > 0) {
      const nomes = clearance.map(c => c.nome);
      const plural = nomes.length > 1;
      const nomesStr = this.formatarListaNomes(nomes);
      this.simVeneravelPhrase.set(
        `"Convide o${plural ? 's' : ''} Irmão${plural ? 's' : ''} ${nomesStr} a cobrir${plural ? 'em' : ''} temporariamente o Templo."`
      );
    } else {
      this.simVeneravelPhrase.set('');
    }
  }

  private formatarListaNomes(nomes: string[]): string {
    if (nomes.length === 1) return nomes[0];
    if (nomes.length === 2) return `${nomes[0]} e ${nomes[1]}`;
    return nomes.slice(0, -1).join(', ') + ' e ' + nomes[nomes.length - 1];
  }

  protected getPresenterSteps(): any[] {
    const presenterId = this.simPresenterId();
    const data = this.dataOrador();
    if (!presenterId || !data) return [];

    const isCompanheiro = data.companheiros?.some((c: any) => c.id === presenterId);
    return isCompanheiro ? this.etapasCompanheiro : this.etapasAprendiz;
  }

  // CRUD Reconhecimento Simbólico
  protected carregarReconhecimentos(): void {
    this.loadingReconhecimentos.set(true);
    const headers = this.supabaseService.getAuthHeaders();
    const url = `${environment.apiUrl}/api/miniapp/orador/reconhecimentos`;

    this.http.get<any>(url, { headers }).subscribe({
      next: (res) => {
        this.loadingReconhecimentos.set(false);
        if (res && res.ok) {
          this.reconhecimentos.set(res.dados || []);
        }
      },
      error: () => {
        this.loadingReconhecimentos.set(false);
      }
    });
  }

  protected salvarReconhecimento(): void {
    const obreiroId = this.recObreiroId();
    const tipo = this.recTipo();
    const descricao = this.recDescricao().trim();

    if (!obreiroId || !tipo || !descricao) {
      this.errorMsg.set('Por favor, preencha todos os campos do reconhecimento.');
      return;
    }

    this.errorMsg.set(null);
    this.successMsg.set(null);
    const headers = this.supabaseService.getAuthHeaders();
    const url = `${environment.apiUrl}/api/miniapp/orador/reconhecimentos`;

    this.http.post<any>(url, { obreiro_id: obreiroId, tipo, descricao }, { headers }).subscribe({
      next: (res) => {
        if (res && res.ok) {
          this.successMsg.set('Anotação de reconhecimento salva com sucesso.');
          this.recDescricao.set('');
          this.recObreiroId.set('');
          this.carregarReconhecimentos();
        } else {
          this.errorMsg.set(res.erro || 'Falha ao salvar anotação.');
        }
      },
      error: (err) => {
        this.errorMsg.set(err.error?.erro || 'Falha ao salvar anotação.');
      }
    });
  }

  protected deletarReconhecimento(id: string): void {
    if (!confirm('Deseja excluir permanentemente este registro de reconhecimento?')) {
      return;
    }

    this.errorMsg.set(null);
    this.successMsg.set(null);
    const headers = this.supabaseService.getAuthHeaders();
    const url = `${environment.apiUrl}/api/miniapp/orador/reconhecimentos?id=${encodeURIComponent(id)}`;

    this.http.delete<any>(url, { headers }).subscribe({
      next: (res) => {
        if (res && res.ok) {
          this.successMsg.set('Registro de reconhecimento excluído.');
          this.carregarReconhecimentos();
        } else {
          this.errorMsg.set(res.erro || 'Falha ao deletar registro.');
        }
      },
      error: (err) => {
        this.errorMsg.set(err.error?.erro || 'Falha ao deletar registro.');
      }
    });
  }

  protected getTipoLabel(tipo: string): string {
    const map: Record<string, string> = {
      'gratidao': 'Menção de Gratidão',
      'constancia': 'Constância Fraterna',
      'servico_silencioso': 'Serviço Silencioso',
      'marco_formativo': 'Marco Formativo'
    };
    return map[tipo] || tipo;
  }
}

