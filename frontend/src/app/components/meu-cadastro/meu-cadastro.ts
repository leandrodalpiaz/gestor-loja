import { CommonModule } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { Component, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { environment } from '../../../environments/environment';
import { SupabaseService } from '../../services/supabase.service';

@Component({
  selector: 'app-meu-cadastro',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './meu-cadastro.html'
})
export class MeuCadastro implements OnInit {
  private http = inject(HttpClient);
  private auth = inject(SupabaseService);

  protected perfil = signal<any>(null);
  protected loading = signal(true);
  protected saving = signal(false);
  protected editing = signal(false);
  protected message = signal<string | null>(null);
  protected error = signal<string | null>(null);

  ngOnInit(): void {
    this.load();
  }

  protected load(): void {
    this.loading.set(true);
    this.error.set(null);
    this.http.get<any>(`${environment.apiUrl}/api/obreiro/perfil`, { headers: this.auth.getAuthHeaders() }).subscribe({
      next: res => {
        this.perfil.set(res.perfil);
        this.loading.set(false);
      },
      error: err => {
        this.error.set(err.error?.erro || 'Não foi possível carregar seu cadastro.');
        this.loading.set(false);
      }
    });
  }

  protected cancel(): void {
    this.editing.set(false);
    this.load();
  }

  protected save(): void {
    const perfil = this.perfil();
    if (!perfil?.nome?.trim()) {
      this.error.set('O nome completo é obrigatório.');
      return;
    }
    this.saving.set(true);
    this.error.set(null);
    this.http.put<any>(`${environment.apiUrl}/api/obreiro/perfil`, perfil, { headers: this.auth.getAuthHeaders() }).subscribe({
      next: res => {
        this.saving.set(false);
        if (!res.ok) {
          this.error.set(res.erro || 'Não foi possível salvar.');
          return;
        }
        this.editing.set(false);
        this.message.set('Cadastro atualizado com sucesso.');
        this.load();
      },
      error: err => {
        this.saving.set(false);
        this.error.set(err.error?.erro || 'Não foi possível salvar seu cadastro.');
      }
    });
  }
}
