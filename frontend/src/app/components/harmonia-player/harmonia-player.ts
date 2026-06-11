import { Component, inject, OnInit, signal, ViewChild, ElementRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { SupabaseService } from '../../services/supabase.service';
import { environment } from '../../../environments/environment';

@Component({
  selector: 'app-harmonia-player',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './harmonia-player.html',
  styleUrl: './harmonia-player.css'
})
export class HarmoniaPlayer implements OnInit {
  private http = inject(HttpClient);
  private supabaseService = inject(SupabaseService);

  @ViewChild('audioElement') set audioRef(ref: ElementRef<HTMLAudioElement> | undefined) {
    if (ref) {
      this.audio = ref.nativeElement;
      this.syncAudioElement();
    }
  }
  private audio?: HTMLAudioElement;

  // Signals para estado reativo
  protected sessoes = signal<any[]>([]);
  protected sessaoAtual = signal<any | null>(null);
  protected faixaAtual = signal<any | null>(null);
  protected proximaFaixa = signal<any | null>(null);
  protected faixas = signal<any[]>([]);
  protected statusPlayer = signal<string>('parado'); // 'tocando' | 'pausado' | 'parado' | 'silencio' | 'pronto'
  protected operadorNome = signal<string>('');
  protected volumePercent = signal<number>(100);
  protected autoProxima = signal<boolean>(false);
  protected loading = signal<boolean>(true);
  protected errorMsg = signal<string | null>(null);
  protected editandoOperador = signal<boolean>(false);
  protected novoOperador = signal<string>('');

  ngOnInit(): void {
    this.carregarDados();
  }

  protected get token(): string {
    return this.supabaseService.getToken() || '';
  }

  protected getAudioUrl(file: string): string {
    if (!file) return '';
    return `${environment.apiUrl}/api/harmonia/audio?file=${encodeURIComponent(file)}&token=${this.token}`;
  }

  protected carregarDados(sessaoPath?: string): void {
    this.loading.set(true);
    this.errorMsg.set(null);
    const headers = this.supabaseService.getAuthHeaders();
    
    let url = `${environment.apiUrl}/api/harmonia/sessoes`;
    if (sessaoPath) {
      url += `?sessao_path=${encodeURIComponent(sessaoPath)}`;
    }

    this.http.get<any>(url, { headers }).subscribe({
      next: (res) => {
        this.loading.set(false);
        if (res && res.ok) {
          this.atualizarEstado(res);
        } else {
          this.errorMsg.set(res?.erro || 'Erro ao carregar sessões de harmonia.');
        }
      },
      error: (err) => {
        this.loading.set(false);
        console.error('[Harmonia] Erro ao carregar dados:', err);
        this.errorMsg.set('Erro de conexão com a API.');
      }
    });
  }

  protected enviarAcao(acao: string, adicionais: any = {}): void {
    const headers = this.supabaseService.getAuthHeaders();
    const sessaoPath = this.sessaoAtual()?.path || '';
    
    const body = {
      acao,
      sessao_path: sessaoPath,
      ...adicionais
    };

    this.http.post<any>(`${environment.apiUrl}/api/harmonia/acao`, body, { headers }).subscribe({
      next: (res) => {
        if (res && res.ok) {
          if (res.dados) {
            this.atualizarEstado(res.dados);
          } else if (res.operador) {
            this.operadorNome.set(res.operador);
          }
        } else {
          alert(res?.erro || 'Falha ao executar ação.');
        }
      },
      error: (err) => {
        console.error('[Harmonia] Erro ao enviar ação:', err);
        alert('Erro ao se comunicar com o servidor.');
      }
    });
  }

  private atualizarEstado(data: any): void {
    this.sessoes.set(data.sessoes || []);
    this.sessaoAtual.set(data.sessao_foco || null);
    this.faixaAtual.set(data.faixa_atual || null);
    this.proximaFaixa.set(data.proxima_faixa || null);
    this.faixas.set(data.faixas || []);
    
    if (data.estado) {
      this.statusPlayer.set(data.estado.status_player || 'parado');
      this.volumePercent.set(data.estado.volume_percent !== undefined ? data.estado.volume_percent : 100);
      this.autoProxima.set(!!data.estado.auto_proxima);
      this.operadorNome.set(data.estado.operador_nome || '');
    }

    this.syncAudioElement();
  }

  private syncAudioElement(): void {
    if (!this.audio) return;

    const currentTrack = this.faixaAtual();
    if (currentTrack && currentTrack.file) {
      const targetSrc = this.getAudioUrl(currentTrack.file);
      const cleanTarget = targetSrc.split('&token=')[0];
      const cleanCurrent = this.audio.src.split('&token=')[0];
      
      if (cleanCurrent !== cleanTarget) {
        const wasPlaying = this.statusPlayer() === 'tocando';
        this.audio.src = targetSrc;
        this.audio.load();
        if (wasPlaying) {
          this.audio.play().catch(err => console.warn('Erro ao reproduzir áudio:', err));
        }
      }
    } else {
      this.audio.src = '';
    }

    this.audio.volume = this.volumePercent() / 100;

    const status = this.statusPlayer();
    if (status === 'tocando') {
      this.audio.play().catch(err => console.warn('Erro ao reproduzir áudio:', err));
    } else if (status === 'pausado') {
      this.audio.pause();
    } else if (status === 'parado') {
      this.audio.pause();
      this.audio.currentTime = 0;
    } else if (status === 'silencio') {
      this.audio.volume = 0;
    }
  }

  protected iniciar(): void {
    this.enviarAcao('iniciar');
  }

  protected pausar(): void {
    this.enviarAcao('pausar');
  }

  protected parar(): void {
    this.enviarAcao('parar');
  }

  protected anterior(): void {
    this.enviarAcao('anterior');
  }

  protected proxima(): void {
    this.enviarAcao('proxima');
  }

  protected silencio(): void {
    this.enviarAcao('silencio');
  }

  protected volumeUp(): void {
    this.enviarAcao('volume_up');
  }

  protected volumeDown(): void {
    this.enviarAcao('volume_down');
  }

  protected toggleAutoProxima(): void {
    this.enviarAcao('toggle_auto');
  }

  protected selecionarSessao(path: string): void {
    this.enviarAcao('selecionar_sessao', { sessao_path: path });
  }

  protected selecionarFaixa(faixaId: string): void {
    this.enviarAcao('selecionar_faixa', { faixa_id: faixaId });
  }

  protected iniciarEdicaoOperador(): void {
    this.novoOperador.set(this.operadorNome());
    this.editandoOperador.set(true);
  }

  protected salvarOperador(): void {
    const nome = this.novoOperador().trim();
    if (nome === '') {
      alert('Informe o nome do operador.');
      return;
    }
    this.enviarAcao('salvar_operador', { nome });
    this.editandoOperador.set(false);
  }

  protected onAudioEnded(): void {
    if (this.autoProxima()) {
      this.proxima();
    } else {
      this.parar();
    }
  }

  protected getFaixaBadgeClass(type: string): string {
    switch (type) {
      case 'transicao':
        return 'bg-amber-500/15 text-amber-400 border border-amber-500/25';
      case 'extra':
        return 'bg-blue-500/15 text-blue-400 border border-blue-500/25';
      default:
        return 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/25';
    }
  }

  protected getFaixaBadgeLabel(type: string): string {
    switch (type) {
      case 'transicao': return 'Transição';
      case 'extra': return 'Extra';
      default: return 'Principal';
    }
  }
}
