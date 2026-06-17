import { CommonModule } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { Component, inject, OnInit, signal } from '@angular/core';
import { environment } from '../../../environments/environment';
import { SupabaseService } from '../../services/supabase.service';

@Component({
  selector: 'app-tesouraria-sessoes',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './tesouraria-sessoes.html',
  styleUrl: './tesouraria-sessoes.css'
})
export class TesourariaSessoes implements OnInit {
  private http = inject(HttpClient);
  private auth = inject(SupabaseService);
  protected sessoes = signal<any[]>([]);
  protected erro = signal('');

  ngOnInit(): void {
    this.http.get<any>(
      `${environment.apiUrl}/api/tesouraria/sessoes`,
      { headers: this.auth.getAuthHeaders() }
    ).subscribe({
      next: res => {
        this.sessoes.set(res.sessoes || []);
        this.erro.set(res.ok ? '' : res.erro);
      },
      error: () => this.erro.set('Não foi possível carregar as sessões financeiras.')
    });
  }
}
