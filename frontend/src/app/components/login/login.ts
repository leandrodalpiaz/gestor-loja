import { Component, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { HttpClient } from '@angular/common/http';
import { SupabaseService } from '../../services/supabase.service';
import { environment } from '../../../environments/environment';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './login.html',
  styleUrl: './login.css'
})
export class Login implements OnInit {
  private supabaseService = inject(SupabaseService);
  private router = inject(Router);
  private http = inject(HttpClient);

  protected identifier = signal('');
  protected password = signal('');

  protected errorMsg = signal<string | null>(null);
  protected loading = signal(false);

  protected tenant = signal<any | null>(null);
  protected conteudos = signal<any[]>([]);
  protected ads = signal<any[]>([]);
  protected adsEnabled = signal<boolean>(false);
  protected exibirHistoriaCompleta = signal<boolean>(false);

  ngOnInit(): void {
    this.carregarDadosPublicos();
  }

  private carregarDadosPublicos(): void {
    this.http.get<any>(`${environment.apiUrl}/api/public/landing`).subscribe({
      next: (res) => {
        if (res && res.ok) {
          this.tenant.set(res.tenant);
          this.conteudos.set(res.conteudos || []);
          this.ads.set(res.ads || []);
          this.adsEnabled.set(!!res.ads_enabled);
        }
      },
      error: (err) => {
        console.error('[Landing] Erro ao carregar dados publicos:', err);
      }
    });
  }

  protected toggleHistoria(): void {
    this.exibirHistoriaCompleta.set(!this.exibirHistoriaCompleta());
  }

  protected getBadgeClass(tipo: string): string {
    switch (String(tipo).toLowerCase()) {
      case 'agenda':
        return 'bg-amber-500/10 text-amber-400 border-amber-500/20';
      case 'noticia':
        return 'bg-sky-500/10 text-sky-400 border-sky-500/20';
      case 'comunicado':
        return 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20';
      default:
        return 'bg-liturgical-gold/10 text-liturgical-gold border-liturgical-gold/20';
    }
  }

  protected onSubmit(): void {
    const identifierVal = this.identifier().trim();
    const passwordVal = this.password().trim();

    if (!identifierVal || !passwordVal) {
      this.errorMsg.set('Informe o C.I.M. ou e-mail e a senha para acessar.');
      return;
    }

    this.loading.set(true);
    this.errorMsg.set(null);

    this.supabaseService.login(identifierVal, passwordVal).subscribe({
      next: () => {
        this.router.navigate(['/dashboard']);
      },
      error: (err: any) => {
        console.error('Falha ao autenticar:', err);
        this.supabaseService.clearLocalAuth();

        if (err.status === 400 || err.message?.includes('Invalid login credentials')) {
          this.errorMsg.set('C.I.M./e-mail ou senha incorretos.');
        } else if (err.status === 403 || err.status === 404 || err.status === 409 || err.message?.includes('vinculada')) {
          this.errorMsg.set(err.error?.erro || err.message || 'Esta conta nao possui um perfil ativo vinculado ao Gestor-Loja.');
        } else {
          this.errorMsg.set(err.message || 'Ocorreu um erro ao tentar entrar. Tente novamente.');
        }

        this.loading.set(false);
      }
    });
  }
}
