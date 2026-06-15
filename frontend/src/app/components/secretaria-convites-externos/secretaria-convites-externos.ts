import { CommonModule } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { Component, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { environment } from '../../../environments/environment';
import { SupabaseService } from '../../services/supabase.service';

@Component({
  selector: 'app-secretaria-convites-externos',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './secretaria-convites-externos.html'
})
export class SecretariaConvitesExternos implements OnInit {
  private http = inject(HttpClient);
  private auth = inject(SupabaseService);
  protected convites = signal<any[]>([]);
  protected loading = signal(true);
  protected saving = signal(false);
  protected error = signal<string | null>(null);
  protected filtro = signal('todos');
  protected anexo = signal<any>(null);
  protected form = signal<any>({
    tipo: 'sessao_magna', titulo: '', loja_origem: '', potencia: '', grau: '',
    data_hora: '', cidade: '', local: '', prazo_confirmacao: '', contatos: '',
    valor: '', traje: '', descricao: '', texto_original: '', status: 'rascunho', fixado: false
  });

  ngOnInit(): void { this.load(); }

  protected load(): void {
    this.loading.set(true);
    this.http.get<any>(`${environment.apiUrl}/api/secretaria/convites-externos`, { headers: this.auth.getAuthHeaders() }).subscribe({
      next: res => { this.convites.set(res.convites || []); this.loading.set(false); },
      error: err => { this.error.set(err.error?.erro || 'Falha ao carregar convites.'); this.loading.set(false); }
    });
  }

  protected filtrados(): any[] {
    return this.filtro() === 'todos' ? this.convites() : this.convites().filter(c => c.tipo === this.filtro());
  }

  protected async selecionarArquivo(event: Event): Promise<void> {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (!file) { this.anexo.set(null); return; }
    if (file.size > 5 * 1024 * 1024) { this.error.set('O anexo deve ter no máximo 5 MB.'); return; }
    const base64 = await new Promise<string>((resolve, reject) => {
      const reader = new FileReader();
      reader.onload = () => resolve(String(reader.result).split(',')[1] || '');
      reader.onerror = reject;
      reader.readAsDataURL(file);
    });
    this.anexo.set({ nome: file.name, mime: file.type, conteudo_base64: base64 });
  }

  protected save(): void {
    if (!this.form().titulo.trim()) { this.error.set('O título é obrigatório.'); return; }
    this.saving.set(true);
    this.http.post<any>(`${environment.apiUrl}/api/secretaria/convites-externos`, { ...this.form(), anexo: this.anexo() }, { headers: this.auth.getAuthHeaders() }).subscribe({
      next: res => {
        this.saving.set(false);
        if (!res.ok) { this.error.set(res.erro); return; }
        this.form.update(f => ({ ...f, titulo: '', descricao: '', texto_original: '', fixado: false }));
        this.anexo.set(null);
        this.load();
      },
      error: err => { this.saving.set(false); this.error.set(err.error?.erro || 'Falha ao salvar convite.'); }
    });
  }

  protected presenca(id: number, status: string): void {
    this.http.post<any>(`${environment.apiUrl}/api/secretaria/convites-externos/${id}/presenca`, { status }, { headers: this.auth.getAuthHeaders() }).subscribe(() => this.load());
  }

  protected removerAnexo(id: number): void {
    this.http.delete<any>(`${environment.apiUrl}/api/secretaria/convites-externos/${id}/anexo`, { headers: this.auth.getAuthHeaders() }).subscribe(() => this.load());
  }
}
