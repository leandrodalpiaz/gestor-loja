import { Component, OnInit, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../../environments/environment';
import { SupabaseService } from '../../services/supabase.service';

@Component({
  selector: 'app-secretaria-nominata',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './secretaria-nominata.html',
  styleUrl: './secretaria-nominata.css'
})
export class SecretariaNominata implements OnInit {
  private http = inject(HttpClient);
  protected supabaseService = inject(SupabaseService);

  protected loading = signal(true);
  protected salvando = signal(false);
  protected errorMsg = signal<string | null>(null);
  protected successMsg = signal<string | null>(null);

  protected gestaoAtual = signal<any | null>(null);
  protected gestoes = signal<any[]>([]);
  protected cargos = signal<any[]>([]);
  protected historico = signal<any[]>([]);
  protected obreiros = signal<any[]>([]);

  protected novaGestao = signal({ titulo: '', inicio_em: '', observacao: '' });
  protected encerramento = signal({ gestao_id: 0, encerrada_em: '' });
  protected formulariosCargo = signal<Record<string, any>>({});

  ngOnInit(): void {
    this.carregar();
  }

  protected carregar(): void {
    this.loading.set(true);
    this.errorMsg.set(null);

    this.http.get<any>(`${environment.apiUrl}/api/admin/cargos`, {
      headers: this.supabaseService.getAuthHeaders()
    }).subscribe({
      next: (res) => {
        this.loading.set(false);
        if (!res?.ok) {
          this.errorMsg.set(res?.erro || 'Falha ao carregar nominata.');
          return;
        }

        const dados = res.dados || {};
        this.gestaoAtual.set(dados.gestao_atual || null);
        this.gestoes.set(dados.gestoes || []);
        this.cargos.set((dados.cargos || []).filter((item: any) => item.codigo !== 'ADMINISTRADOR'));
        this.obreiros.set(dados.obreiros || []);
        this.historico.set(dados.auditoria || []);
        this.encerramento.set({
          gestao_id: Number(dados.gestao_atual?.id || 0),
          encerrada_em: ''
        });

        const forms: Record<string, any> = {};
        for (const cargo of (dados.cargos || [])) {
          forms[cargo.codigo] = { obreiro_id: '', inicio_em: '', observacao: '' };
        }
        this.formulariosCargo.set(forms);
      },
      error: (err) => {
        this.loading.set(false);
        this.errorMsg.set(err.error?.erro || 'Erro ao consultar nominata.');
      }
    });
  }

  protected setNovaGestao(field: string, value: string): void {
    this.novaGestao.update((state) => ({ ...state, [field]: value }));
  }

  protected setEncerramento(value: string): void {
    this.encerramento.update((state) => ({ ...state, encerrada_em: value }));
  }

  protected setCargo(codigo: string, field: string, value: string): void {
    this.formulariosCargo.update((state) => ({
      ...state,
      [codigo]: { ...(state[codigo] || {}), [field]: value }
    }));
  }

  protected abrirGestao(): void {
    this.post('/api/admin/cargos/gestao/abrir', this.novaGestao(), 'Gestão aberta com sucesso.', () => {
      this.novaGestao.set({ titulo: '', inicio_em: '', observacao: '' });
    });
  }

  protected encerrarGestao(): void {
    this.post('/api/admin/cargos/gestao/encerrar', this.encerramento(), 'Gestão encerrada com sucesso.');
  }

  protected atribuirCargo(codigo: string): void {
    const form = this.formulariosCargo()[codigo] || {};
    this.post('/api/admin/cargos/atribuir', {
      cargo_codigo: codigo,
      obreiro_id: form.obreiro_id || '',
      gestao_id: this.gestaoAtual()?.id || null,
      inicio_em: form.inicio_em || '',
      observacao: form.observacao || ''
    }, 'Titularidade atualizada com sucesso.');
  }

  private post(path: string, payload: any, successMsg: string, onSuccess?: () => void): void {
    this.salvando.set(true);
    this.errorMsg.set(null);
    this.successMsg.set(null);

    this.http.post<any>(`${environment.apiUrl}${path}`, payload, {
      headers: this.supabaseService.getAuthHeaders()
    }).subscribe({
      next: (res) => {
        this.salvando.set(false);
        if (!res?.ok) {
          this.errorMsg.set(res?.erro || 'Não foi possível concluir a operação.');
          return;
        }
        onSuccess?.();
        this.successMsg.set(successMsg);
        this.carregar();
      },
      error: (err) => {
        this.salvando.set(false);
        this.errorMsg.set(err.error?.erro || 'Erro ao salvar dados.');
      }
    });
  }
}
