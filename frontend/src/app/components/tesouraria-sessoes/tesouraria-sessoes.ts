import { CommonModule } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { Component, inject, OnInit, signal } from '@angular/core';
import { environment } from '../../../environments/environment';
import { SupabaseService } from '../../services/supabase.service';

@Component({
  selector: 'app-tesouraria-sessoes',
  standalone: true,
  imports: [CommonModule],
  template: `
    <section class="page">
      <header><span>Tesouraria</span><h1>Sessões Financeiras</h1><p>Reflexo financeiro dos ágapes e eventos futuros.</p></header>
      @if (erro()) { <div class="error">{{ erro() }}</div> }
      <div class="grid">
        @for (sessao of sessoes(); track sessao.id; let primeira = $first) {
          <article [class.featured]="primeira">
            <div class="top"><div><small>{{ sessao.descricao_tipo }}</small><h2>{{ sessao.titulo || sessao.descricao_tipo }}</h2><p>{{ sessao.data_hora_inicio | date:'dd/MM/yyyy HH:mm' }}</p></div>
              <b [class.off]="!sessao.reflete_financeiro_oficial">{{ sessao.reflete_financeiro_oficial ? 'Reflexo oficial' : 'Sem reflexo' }}</b>
            </div>
            <dl><div><dt>Ágape</dt><dd>{{ sessao.descricao_agape }}</dd></div><div><dt>Modelo</dt><dd>{{ sessao.descricao_modelo }}</dd></div><div><dt>Confirmados</dt><dd>{{ sessao.confirmados_agape }}</dd></div><div><dt>Estimativa</dt><dd class="money">{{ sessao.estimativa_arrecadacao | currency:'BRL':'symbol':'1.2-2':'pt-BR' }}</dd></div></dl>
            @if (primeira && sessao.participantes?.length) { <div class="people"><h3>Participantes do ágape</h3>@for (p of sessao.participantes; track p.nome) { <p><span>{{ p.nome }}</span><small>CIM {{ p.cim || '-' }}</small></p> }</div> }
          </article>
        } @empty { <article>Nenhuma sessão futura cadastrada.</article> }
      </div>
    </section>`,
  styles: [`:host{display:block;color:#e5e7eb}.page>*+*{margin-top:1.5rem}header span{color:#c9a227;font-size:.65rem;font-weight:800;text-transform:uppercase;letter-spacing:.16em}h1{font:800 1.9rem Cinzel,serif;margin:.25rem 0}header p,article p{color:#94a3b8}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(310px,1fr));gap:1rem}article{background:rgba(11,19,43,.62);border:1px solid rgba(255,255,255,.08);border-radius:1.25rem;padding:1.25rem}.featured{grid-column:span 2;border-color:rgba(201,162,39,.3)}.top{display:flex;justify-content:space-between;gap:1rem}.top small,dt{color:#64748b;text-transform:uppercase;font-size:.62rem;font-weight:800;letter-spacing:.1em}.top h2{margin:.3rem 0;font-size:1rem}.top b{height:max-content;padding:.35rem .6rem;border-radius:99px;background:#064e3b;color:#6ee7b7;font-size:.65rem}.top b.off{background:#451a1a;color:#fca5a5}dl{display:grid;grid-template-columns:1fr 1fr;gap:.8rem;margin-top:1.2rem}dl div{background:rgba(255,255,255,.03);padding:.8rem;border-radius:.8rem}dd{margin:.3rem 0 0;font-weight:700}.money{color:#6ee7b7}.people{margin-top:1rem;border-top:1px solid rgba(255,255,255,.08);padding-top:1rem}.people p{display:flex;justify-content:space-between}.error{padding:1rem;background:#450a0a;color:#fda4af;border-radius:1rem}@media(max-width:800px){.featured{grid-column:span 1}}`]
})
export class TesourariaSessoes implements OnInit {
  private http = inject(HttpClient);
  private auth = inject(SupabaseService);
  protected sessoes = signal<any[]>([]);
  protected erro = signal('');

  ngOnInit(): void {
    this.http.get<any>(`${environment.apiUrl}/api/tesouraria/sessoes`, { headers: this.auth.getAuthHeaders() }).subscribe({
      next: res => { this.sessoes.set(res.sessoes || []); this.erro.set(res.ok ? '' : res.erro); },
      error: () => this.erro.set('Não foi possível carregar as sessões financeiras.')
    });
  }
}
