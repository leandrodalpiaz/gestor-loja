import { Component, inject, OnInit, signal } from '@angular/core';
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
  protected errorMsg = signal<string | null>(null);
  protected successMsg = signal<string | null>(null);

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

  ngOnInit(): void {
    this.carregarConfig();
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
}
