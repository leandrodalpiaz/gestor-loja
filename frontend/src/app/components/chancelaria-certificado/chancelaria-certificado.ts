import { Component, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { ActivatedRoute } from '@angular/router';
import { SupabaseService } from '../../services/supabase.service';
import { environment } from '../../../environments/environment';

@Component({
  selector: 'app-chancelaria-certificado',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './chancelaria-certificado.html',
  styleUrl: './chancelaria-certificado.css'
})
export class ChancelariaCertificado implements OnInit {
  private http = inject(HttpClient);
  private supabaseService = inject(SupabaseService);
  private route = inject(ActivatedRoute);

  // Estados do formulário
  protected nomeVisitante = signal('');
  protected lojaVisitante = signal('');
  protected oriente = signal('');
  protected dataSessao = signal('');
  protected tipoSessao = signal('Ordinaria');
  protected grauSessao = signal('Aprendiz Macom');
  protected chatId = signal('');
  protected initData = signal('');

  // Estados de controle
  protected loading = signal(false);
  protected successMsg = signal<string | null>(null);
  protected errorMsg = signal<string | null>(null);
  protected generatedImageUrl = signal<string | null>(null);

  ngOnInit(): void {
    // Configura a data padrão para hoje (no fuso local YYYY-MM-DD)
    const hoje = new Date();
    const offset = hoje.getTimezoneOffset();
    const dataLocal = new Date(hoje.getTime() - (offset * 60 * 1000));
    this.dataSessao.set(dataLocal.toISOString().split('T')[0]);

    // Lê os query parameters para preenchimento automático
    this.route.queryParams.subscribe(params => {
      if (params['data_sessao']) {
        this.dataSessao.set(params['data_sessao']);
      }
      if (params['tipo_sessao']) {
        this.tipoSessao.set(params['tipo_sessao']);
      }
      if (params['grau_sessao']) {
        this.grauSessao.set(params['grau_sessao']);
      }
      if (params['nome_visitante']) {
        this.nomeVisitante.set(params['nome_visitante']);
      }
      if (params['loja_visitante']) {
        this.lojaVisitante.set(params['loja_visitante']);
      }
      if (params['oriente']) {
        this.oriente.set(params['oriente']);
      }
    });

    // Detecta contexto do Telegram WebApp
    const win = window as any;
    const tg = win.Telegram && win.Telegram.WebApp ? win.Telegram.WebApp : null;
    if (tg) {
      tg.ready();
      tg.expand();
      this.initData.set(tg.initData || '');
      if (tg.initDataUnsafe && tg.initDataUnsafe.user) {
        this.chatId.set(String(tg.initDataUnsafe.user.id));
      }
    }
  }

  protected gerarCertificado(): void {
    this.loading.set(true);
    this.successMsg.set(null);
    this.errorMsg.set(null);
    this.generatedImageUrl.set(null);

    const token = this.supabaseService.getToken();
    if (!token) {
      this.errorMsg.set('Erro: Usuário não autenticado.');
      this.loading.set(false);
      return;
    }

    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    const body = {
      nome_visitante: this.nomeVisitante(),
      loja_visitante: this.lojaVisitante(),
      oriente: this.oriente(),
      tipo_sessao: this.tipoSessao(),
      grau_sessao: this.grauSessao(),
      data_sessao: this.dataSessao(),
      chat_id: this.chatId()
    };

    this.http.post<any>(`${environment.apiUrl}/api/chancelaria/certificado/gerar`, body, { headers })
      .subscribe({
        next: (res) => {
          this.loading.set(false);
          if (res.ok) {
            this.successMsg.set(res.mensagem || 'Certificado emitido com sucesso!');
            // Constrói a URL completa da imagem gerada no backend
            this.generatedImageUrl.set(`${environment.apiUrl}${res.caminho_imagem}`);
            
            // Se estiver no Telegram, notifica o usuário via WebApp Alert
            const win = window as any;
            const tg = win.Telegram && win.Telegram.WebApp ? win.Telegram.WebApp : null;
            if (tg && typeof tg.showAlert === 'function') {
              tg.showAlert(res.mensagem || 'Certificado gerado com sucesso!');
            }
          } else {
            this.errorMsg.set(res.erro || 'Falha ao gerar o certificado.');
          }
        },
        error: (err) => {
          this.loading.set(false);
          this.errorMsg.set(err.error?.erro || 'Erro de conexão ao servidor.');
        }
      });
  }

  protected abrirImagem(): void {
    const url = this.generatedImageUrl();
    if (url) {
      window.open(url, '_blank');
    }
  }

  protected baixarImagem(): void {
    const url = this.generatedImageUrl();
    if (!url) return;

    // Tenta fazer o download forçado usando tag anchor invisível
    const link = document.createElement('a');
    link.href = url;
    link.download = `certificado_${this.nomeVisitante().replace(/\s+/g, '_')}.jpeg`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  }
}
