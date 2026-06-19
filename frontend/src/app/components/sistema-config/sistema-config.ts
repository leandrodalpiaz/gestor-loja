import { Component, inject, OnInit, signal, computed } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { SupabaseService } from '../../services/supabase.service';
import { environment } from '../../../environments/environment';

export interface SistemaConfigData {
  nome_loja: string;
  numero_loja: string;
  cidade: string;
  uf: string;
  oriente: string;
  potencia_nome: string;
  potencia_sigla: string;
  rito: string;
  email_oficial: string;
  telefone_oficial: string;
  endereco: string;
  cep: string;
}

export interface TecnicoConfigData {
  sistema_status: 'online' | 'manutencao' | 'suspenso';
  manutencao_mensagem: string;
  suspenso_mensagem: string;
}

export interface HealthCheckData {
  db: { ok: boolean; latency: number };
  telegram: { ok: boolean; msg: string; webhook?: any };
  supabase: { ok: boolean; msg: string };
}

@Component({
  selector: 'app-sistema-config',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './sistema-config.html',
  styleUrl: './sistema-config.css'
})
export class SistemaConfig implements OnInit {
  private http = inject(HttpClient);
  protected supabaseService = inject(SupabaseService);

  protected loading = signal(true);
  protected salvando = signal(false);
  protected salvandoTecnico = signal(false);
  protected executandoAcao = signal<string | null>(null);

  protected errorMsg = signal<string | null>(null);
  protected successMsg = signal<string | null>(null);

  protected activeTab = signal<'institucional' | 'tecnico'>('institucional');

  protected isSystemAdmin = computed(() => {
    const profile = this.supabaseService.profile();
    return !!(profile?.is_system_admin || profile?.cargos?.includes('admin'));
  });

  protected config = signal<SistemaConfigData>({
    nome_loja: '',
    numero_loja: '',
    cidade: '',
    uf: '',
    oriente: '',
    potencia_nome: '',
    potencia_sigla: '',
    rito: '',
    email_oficial: '',
    telefone_oficial: '',
    endereco: '',
    cep: ''
  });

  protected tecnicoConfig = signal<TecnicoConfigData>({
    sistema_status: 'online',
    manutencao_mensagem: 'O sistema está em manutenção técnica programada. Retornaremos em breve.',
    suspenso_mensagem: 'O acesso a esta Loja está suspenso ou desativado.'
  });

  protected health = signal<HealthCheckData>({
    db: { ok: false, latency: 0 },
    telegram: { ok: false, msg: 'Carregando...' },
    supabase: { ok: false, msg: 'Carregando...' }
  });

  protected env = signal<any>({});

  ngOnInit(): void {
    this.carregarConfig();
    if (this.isSystemAdmin()) {
      this.carregarTecnicoConfig();
    }
  }

  protected setTab(tab: 'institucional' | 'tecnico'): void {
    if (tab === 'tecnico' && !this.isSystemAdmin()) {
      return;
    }
    this.activeTab.set(tab);
    this.errorMsg.set(null);
    this.successMsg.set(null);
  }

  protected carregarConfig(): void {
    this.loading.set(true);
    this.errorMsg.set(null);
    const headers = this.supabaseService.getAuthHeaders();

    this.http.get<any>(
      `${environment.apiUrl}/api/obreiro/sistema/config`,
      { headers }
    ).subscribe({
      next: (res) => {
        this.loading.set(false);
        if (res && res.ok && res.config) {
          this.config.set(res.config);
        }
      },
      error: (err) => {
        this.loading.set(false);
        console.error('[SistemaConfig] Erro:', err);
        this.errorMsg.set('Falha ao carregar configurações do sistema.');
      }
    });
  }

  protected carregarTecnicoConfig(): void {
    const headers = this.supabaseService.getAuthHeaders();
    this.http.get<any>(
      `${environment.apiUrl}/api/obreiro/sistema/tecnico/config`,
      { headers }
    ).subscribe({
      next: (res) => {
        if (res && res.ok) {
          if (res.config) {
            this.tecnicoConfig.set(res.config);
          }
          if (res.health) {
            this.health.set(res.health);
          }
          if (res.env) {
            this.env.set(res.env);
          }
        }
      },
      error: (err) => {
        console.error('[SistemaConfig] Erro técnico:', err);
      }
    });
  }

  protected salvarConfig(): void {
    this.salvando.set(true);
    this.successMsg.set(null);
    this.errorMsg.set(null);
    const headers = this.supabaseService.getAuthHeaders();

    this.http.post<any>(
      `${environment.apiUrl}/api/obreiro/sistema/config/salvar`,
      this.config(),
      { headers }
    ).subscribe({
      next: (res) => {
        this.salvando.set(false);
        if (res && res.ok) {
          this.successMsg.set(res.mensagem || 'Configurações institucionais gravadas com sucesso!');
        } else {
          this.errorMsg.set(res.erro || 'Falha ao salvar configurações.');
        }
      },
      error: (err) => {
        this.salvando.set(false);
        console.error('[SistemaConfig] Erro ao salvar:', err);
        this.errorMsg.set(err.error?.erro || 'Erro ao salvar configurações institucionais.');
      }
    });
  }

  protected salvarTecnicoConfig(): void {
    this.salvandoTecnico.set(true);
    this.successMsg.set(null);
    this.errorMsg.set(null);
    const headers = this.supabaseService.getAuthHeaders();

    this.http.post<any>(
      `${environment.apiUrl}/api/obreiro/sistema/tecnico/salvar`,
      this.tecnicoConfig(),
      { headers }
    ).subscribe({
      next: (res) => {
        this.salvandoTecnico.set(false);
        if (res && res.ok) {
          this.successMsg.set(res.mensagem || 'Status de implantação técnica atualizado!');
          this.carregarTecnicoConfig();
        } else {
          this.errorMsg.set(res.erro || 'Falha ao salvar status técnico.');
        }
      },
      error: (err) => {
        this.salvandoTecnico.set(false);
        this.errorMsg.set(err.error?.erro || 'Erro ao salvar status técnico.');
      }
    });
  }

  protected executarAcao(acao: string): void {
    this.executandoAcao.set(acao);
    this.successMsg.set(null);
    this.errorMsg.set(null);
    const headers = this.supabaseService.getAuthHeaders();

    this.http.post<any>(
      `${environment.apiUrl}/api/obreiro/sistema/tecnico/acao`,
      { acao },
      { headers }
    ).subscribe({
      next: (res) => {
        this.executandoAcao.set(null);
        if (res && res.ok) {
          this.successMsg.set(res.mensagem || 'Ação técnica executada com sucesso.');
          this.carregarTecnicoConfig();
        } else {
          this.errorMsg.set(res.erro || 'Falha ao executar ação.');
        }
      },
      error: (err) => {
        this.executandoAcao.set(null);
        this.errorMsg.set(err.error?.erro || 'Erro ao executar ação.');
      }
    });
  }
}
