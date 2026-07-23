import { CommonModule } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { Component, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { environment } from '../../../environments/environment';
import { SupabaseService } from '../../services/supabase.service';

@Component({
  selector: 'app-tesouraria-comprovantes',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './tesouraria-comprovantes.html',
  styleUrl: './tesouraria-comprovantes.css'
})
export class TesourariaComprovantes implements OnInit {
  private http = inject(HttpClient);
  private auth = inject(SupabaseService);

  protected status = signal('pendente');
  protected itens = signal<any[]>([]);
  protected categorias = signal<any[]>([]);
  protected parcelas = signal<any[]>([]);
  protected selecionado = signal<any>(null);
  protected erro = signal('');
  protected statusList = ['pendente', 'aprovado', 'rejeitado'];

  protected valor = 0;
  protected mes = 1;
  protected ano = new Date().getFullYear();
  protected rotulo = '';
  protected categoriaId: number | null = null;
  protected parcelaId: number | null = null;

  ngOnInit() {
    this.carregar();
    this.http.get<any>(
      `${environment.apiUrl}/api/tesouraria/categorias?tipo=entrada`,
      { headers: this.auth.getAuthHeaders() }
    ).subscribe(r => this.categorias.set(r.categorias || []));
  }

  protected carregar() {
    this.http.get<any>(
      `${environment.apiUrl}/api/tesouraria/comprovantes?status=${this.status()}`,
      { headers: this.auth.getAuthHeaders() }
    ).subscribe({
      next: r => {
        this.itens.set(r.comprovantes || []);
        this.erro.set(r.ok ? '' : r.erro);
      },
      error: () => this.erro.set('Falha ao carregar comprovantes.')
    });
  }

  protected abrir(c: any) {
    this.selecionado.set(c);
    this.valor = Number(c.valor_validado || c.valor_informado || 0);
    this.mes = Number(c.mes_ref_validado || c.mes_ref_informado || 1);
    this.ano = Number(c.ano_ref_validado || c.ano_ref_informado || new Date().getFullYear());
    this.rotulo = c.rotulo_pagamento || c.descricao_usuario || '';
    this.categoriaId = c.categoria_id ? Number(c.categoria_id) : null;
    this.parcelaId = c.obrigacao_parcela_id ? Number(c.obrigacao_parcela_id) : null;
    this.parcelas.set([]);

    if (c.obreiro_id) {
      this.http.get<any>(
        `${environment.apiUrl}/api/tesouraria/obrigacoes-abertas?obreiro_id=${encodeURIComponent(c.obreiro_id)}`,
        { headers: this.auth.getAuthHeaders() }
      ).subscribe(r => {
        const rawParcelas = Array.isArray(r.parcelas) ? r.parcelas : [];
        this.parcelas.set(rawParcelas.map((p: any) => ({
          id: p.id,
          titulo: p.titulo || p.competencia_label || 'Parcela',
          valor_base: Number(p.valor_previsto || 0),
          status: p.status || 'pendente'
        })));
      });
    }
  }

  protected aprovar() {
    const c = this.selecionado();
    if (!c) return;

    this.http.post<any>(
      `${environment.apiUrl}/api/tesouraria/comprovantes/aprovar`,
      {
        id: c.id,
        valor: this.valor,
        mes: this.mes,
        ano: this.ano,
        rotulo_pagamento: this.rotulo,
        categoria_id: this.categoriaId,
        obrigacao_parcela_id: this.parcelaId
      },
      { headers: this.auth.getAuthHeaders() }
    ).subscribe({
      next: r => {
        if (r.ok) {
          this.selecionado.set(null);
          this.carregar();
        } else {
          this.erro.set(r.erro || 'Falha ao aprovar comprovante.');
        }
      },
      error: e => this.erro.set(e.error?.erro || 'Falha de conexão ao aprovar comprovante.')
    });
  }

  protected rejeitar() {
    const c = this.selecionado();
    if (!c) return;

    const motivo = prompt('Motivo da rejeição:');
    if (!motivo) return;

    this.http.post<any>(
      `${environment.apiUrl}/api/tesouraria/comprovantes/rejeitar`,
      { id: c.id, motivo },
      { headers: this.auth.getAuthHeaders() }
    ).subscribe({
      next: r => {
        if (r.ok) {
          this.selecionado.set(null);
          this.carregar();
        } else {
          this.erro.set(r.erro || 'Falha ao rejeitar comprovante.');
        }
      },
      error: e => this.erro.set(e.error?.erro || 'Falha de conexão ao rejeitar comprovante.')
    });
  }
}
