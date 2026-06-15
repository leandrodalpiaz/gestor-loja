import { Component, inject, OnInit, OnDestroy, signal, ViewChild, ElementRef } from '@angular/core';
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
export class HarmoniaPlayer implements OnInit, OnDestroy {
  private http = inject(HttpClient);
  private supabaseService = inject(SupabaseService);

  @ViewChild('audioElement') set audioRef(ref: ElementRef<HTMLAudioElement> | undefined) {
    if (ref) {
      this.audio = ref.nativeElement;
      this.syncAudioElement();
      setTimeout(() => this.initVisualizer(), 100);
    }
  }
  private audio?: HTMLAudioElement;

  @ViewChild('visualizerCanvas') canvasRef?: ElementRef<HTMLCanvasElement>;

  // Web Audio Visualizer states
  private audioCtx?: AudioContext;
  private analyser?: AnalyserNode;
  private source?: MediaElementAudioSourceNode;
  private animationFrameId?: number;
  private visualizerInitialized = false;
  private fadeInterval?: any;

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
  protected repetirFaixa = signal<boolean>(false);
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
    if (this.repetirFaixa()) {
      if (this.audio) {
        this.audio.currentTime = 0;
        this.audio.play().catch(err => console.warn('Erro ao repetir áudio:', err));
      }
    } else if (this.autoProxima()) {
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

  protected definirVolume(event: Event): void {
    const input = event.target as HTMLInputElement;
    const value = parseInt(input.value, 10);
    this.volumePercent.set(value);
    this.enviarAcao('definir_volume', { volume: value });
  }

  protected toggleRepetirFaixa(): void {
    this.repetirFaixa.set(!this.repetirFaixa());
  }

  protected fadeIn(): void {
    if (!this.audio || !this.faixaAtual()) return;
    this.initVisualizer();
    
    clearInterval(this.fadeInterval);

    const wasPlaying = this.statusPlayer() === 'tocando';
    if (!wasPlaying) {
      this.statusPlayer.set('tocando');
      this.audio.play().catch(err => console.warn(err));
    }

    const targetVolume = this.volumePercent() / 100;
    this.audio.volume = 0;
    
    const duration = 2500; // 2.5s
    const intervalTime = 50; 
    const step = targetVolume / (duration / intervalTime);

    this.fadeInterval = setInterval(() => {
      if (!this.audio) {
        clearInterval(this.fadeInterval);
        return;
      }
      let newVol = this.audio.volume + step;
      if (newVol >= targetVolume) {
        newVol = targetVolume;
        clearInterval(this.fadeInterval);
      }
      this.audio.volume = newVol;
    }, intervalTime);
  }

  protected fadeOut(): void {
    if (!this.audio || this.statusPlayer() !== 'tocando') return;
    
    clearInterval(this.fadeInterval);

    const startVolume = this.audio.volume;
    const duration = 2500; // 2.5s
    const intervalTime = 50; 
    const step = startVolume / (duration / intervalTime);

    this.fadeInterval = setInterval(() => {
      if (!this.audio) {
        clearInterval(this.fadeInterval);
        return;
      }
      let newVol = this.audio.volume - step;
      if (newVol <= 0) {
        newVol = 0;
        clearInterval(this.fadeInterval);
        this.audio.pause();
        this.statusPlayer.set('pausado');
        this.audio.volume = this.volumePercent() / 100; // restaura
      } else {
        this.audio.volume = newVol;
      }
    }, intervalTime);
  }

  private initVisualizer(): void {
    if (this.visualizerInitialized || !this.audio || !this.canvasRef) return;

    const canvas = this.canvasRef.nativeElement;
    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    try {
      this.audioCtx = new (window.AudioContext || (window as any).webkitAudioContext)();
      this.analyser = this.audioCtx.createAnalyser();
      this.analyser.fftSize = 256;
      this.source = this.audioCtx.createMediaElementSource(this.audio);
      this.source.connect(this.analyser);
      this.analyser.connect(this.audioCtx.destination);
      this.visualizerInitialized = true;
      this.startAnimationLoop(canvas, ctx);
    } catch (err) {
      console.warn('Web Audio API not supported or user interaction blocked:', err);
    }
  }

  private startAnimationLoop(canvas: HTMLCanvasElement, ctx: CanvasRenderingContext2D): void {
    const bufferLength = this.analyser?.frequencyBinCount || 0;
    const dataArray = new Uint8Array(bufferLength);

    const draw = () => {
      this.animationFrameId = requestAnimationFrame(draw);
      
      const width = canvas.width;
      const height = canvas.height;
      
      ctx.clearRect(0, 0, width, height);

      if (this.statusPlayer() === 'tocando' && this.analyser) {
        if (this.audioCtx && this.audioCtx.state === 'suspended') {
          this.audioCtx.resume();
        }
        this.analyser.getByteFrequencyData(dataArray);
      } else {
        const time = Date.now() * 0.003;
        for (let i = 0; i < bufferLength; i++) {
          dataArray[i] = (Math.sin(i * 0.1 + time) + 1) * 12 + 10;
        }
      }

      const centerX = width / 2;
      const centerY = height / 2;
      const baseRadius = Math.min(width, height) * 0.32;

      ctx.beginPath();
      ctx.arc(centerX, centerY, baseRadius - 8, 0, 2 * Math.PI);
      ctx.fillStyle = 'rgba(5, 11, 20, 0.6)';
      ctx.fill();
      ctx.strokeStyle = 'rgba(201, 162, 39, 0.1)';
      ctx.lineWidth = 1;
      ctx.stroke();

      ctx.font = '28px serif';
      ctx.fillStyle = 'rgba(201, 162, 39, 0.7)';
      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';
      ctx.fillText('⚜', centerX, centerY);

      ctx.beginPath();
      for (let i = 0; i < bufferLength; i++) {
        const angle = (i / bufferLength) * Math.PI * 2;
        const amplitude = (dataArray[i] / 255) * 35;
        const r = baseRadius + amplitude;
        const x = centerX + Math.cos(angle) * r;
        const y = centerY + Math.sin(angle) * r;

        if (i === 0) {
          ctx.moveTo(x, y);
        } else {
          ctx.lineTo(x, y);
        }
      }
      ctx.closePath();
      
      const gradient = ctx.createRadialGradient(centerX, centerY, baseRadius - 5, centerX, centerY, baseRadius + 35);
      gradient.addColorStop(0, '#C9A227');
      gradient.addColorStop(0.4, 'rgba(201, 162, 39, 0.8)');
      gradient.addColorStop(1, 'rgba(201, 162, 39, 0)');
      
      ctx.strokeStyle = gradient;
      ctx.lineWidth = 3.5;
      ctx.shadowBlur = 8;
      ctx.shadowColor = '#C9A227';
      ctx.stroke();
      ctx.shadowBlur = 0;
    };

    draw();
  }

  ngOnDestroy(): void {
    if (this.animationFrameId) {
      cancelAnimationFrame(this.animationFrameId);
    }
    clearInterval(this.fadeInterval);
    if (this.audioCtx) {
      this.audioCtx.close().catch(err => console.warn(err));
    }
  }

  protected getStatusLabel(): string {
    switch (this.statusPlayer()) {
      case 'tocando': return 'Tocando';
      case 'pausado': return 'Pausado';
      case 'silencio': return 'Silêncio';
      case 'parado': return 'Parado';
      case 'pronto': return 'Pronto';
      default: return 'Parado';
    }
  }

  protected getStatusColorClass(): string {
    switch (this.statusPlayer()) {
      case 'tocando':
        return 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 shadow-md shadow-emerald-500/10';
      case 'pausado':
        return 'bg-amber-500/15 text-amber-400 border border-amber-500/30 shadow-md shadow-amber-500/10';
      case 'silencio':
        return 'bg-rose-500/15 text-rose-400 border border-rose-500/30 shadow-md shadow-rose-500/10';
      case 'pronto':
        return 'bg-blue-500/15 text-blue-400 border border-blue-500/30 shadow-md shadow-blue-500/10';
      default:
        return 'bg-slate-500/15 text-slate-400 border border-slate-500/30';
    }
  }
}
