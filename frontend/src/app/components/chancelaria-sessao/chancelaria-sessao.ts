import { CommonModule } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { Component, inject, OnInit, signal, computed } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { environment } from '../../../environments/environment';
import { SupabaseService } from '../../services/supabase.service';

@Component({
  selector: 'app-chancelaria-sessao',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink],
  templateUrl: './chancelaria-sessao.html',
  styleUrl: './chancelaria-sessao.css'
})
export class ChancelariaSessao implements OnInit {
  private http=inject(HttpClient);private auth=inject(SupabaseService);
  protected sessoes=signal<any[]>([]);protected sessoesAtiva=signal<any>(null);protected presencas=signal<any[]>([]);protected visitantes=signal<any[]>([]);protected confirmados=signal<any[]>([]);protected erro=signal('');protected filtro=signal('todos');protected filtros=['todos','presentes','pendentes'];protected sessaoId=0;protected dataSessao=new Date().toISOString().slice(0,10);
  protected visitante = {
    nome: '',
    grau: '',
    loja: '',
    numero_loja: '',
    oriente: '',
    potencia: '',
    numero_certificado: '',
    certificado_emitido_em: '',
    fala_resumida: ''
  };
  protected visitanteTextoLivre = '';

  protected paramsCertificado = computed(() => {
    const s = this.sessoesAtiva();
    if (!s || !s.data_hora_inicio) return {};
    return {
      data_sessao: s.data_hora_inicio.substring(0, 10),
      tipo_sessao: s.tipo_sessao || 'Ordinaria',
      grau_sessao: s.grau_sessao || 'Mestre Macom'
    };
  });

  ngOnInit(){this.carregar()} 
  
  protected presentes(){return this.presencas().filter(p=>p.presente).length} 
  
  protected presencasFiltradas(){return this.presencas().filter(p=>this.filtro()==='todos'||(this.filtro()==='presentes'&&p.presente)||(this.filtro()==='pendentes'&&!p.presente))}
  
  protected carregar(id=0,data=''){const q=new URLSearchParams();if(id)q.set('sessao_id',String(id));if(data)q.set('data_sessao',data);this.http.get<any>(`${environment.apiUrl}/api/chancelaria/sessao?${q}`,{headers:this.auth.getAuthHeaders()}).subscribe({next:r=>{this.sessoes.set(r.sessoes||[]);this.sessoesAtiva.set(r.sessao);this.sessaoId=Number(r.sessao?.id||0);this.presencas.set(r.presencas||[]);this.visitantes.set(r.visitantes||[]);this.confirmados.set(r.confirmados||[]);this.erro.set(r.ok?'':r.erro)},error:e=>this.erro.set(e.error?.erro||'Falha ao carregar a sessão da Chancelaria.')})}
  
  protected abrirData(){this.carregar(0,this.dataSessao)} 
  
  protected marcar(p:any,presente:boolean){this.http.post<any>(`${environment.apiUrl}/api/chancelaria/sessao/presenca`,{sessao_id:this.sessaoId,obreiro_id:p.id,presente},{headers:this.auth.getAuthHeaders()}).subscribe({next:r=>r.ok?this.carregar(this.sessaoId):this.erro.set(r.erro),error:e=>this.erro.set(e.error?.erro||'Falha de conexão ao marcar presença.')})}
  
  protected preencherVisitante(): void {
    const partes = this.visitanteTextoLivre.split(',').map(p => p.trim()).filter(Boolean);
    if (partes[0]) this.visitante.nome = partes[0];
    if (partes[1]) this.visitante.grau = partes[1];
    if (partes[2]) this.visitante.loja = partes[2];
    if (partes[3]) this.visitante.numero_loja = partes[3];
    if (partes[4]) this.visitante.oriente = partes[4];
    if (partes[5]) this.visitante.potencia = partes[5];
  }

  protected salvarVisitante(){if(!this.visitante.nome.trim()){this.erro.set('Informe o nome do visitante.');return}this.http.post<any>(`${environment.apiUrl}/api/chancelaria/sessao/visitante`,{sessao_id:this.sessaoId,...this.visitante},{headers:this.auth.getAuthHeaders()}).subscribe({next:r=>{if(r.ok){this.visitante={nome:'',grau:'',loja:'',numero_loja:'',oriente:'',potencia:'',numero_certificado:'',certificado_emitido_em:'',fala_resumida:''};this.visitanteTextoLivre='';this.carregar(this.sessaoId)}else this.erro.set(r.erro)},error:e=>this.erro.set(e.error?.erro||'Falha de conexão ao salvar visitante.')})}
  
  protected cancelar(id:number){if(confirm('Cancelar esta sessão?'))this.acao(id,'cancelar')} 
  
  protected excluir(id:number){if(confirm('Excluir definitivamente esta sessão e seus registros?'))this.acao(id,'excluir')} 
  
  private acao(id:number,acao:string){this.http.post<any>(`${environment.apiUrl}/api/chancelaria/sessao/${id}/${acao}`,{},{headers:this.auth.getAuthHeaders()}).subscribe({next:r=>r.ok?this.carregar():this.erro.set(r.erro),error:e=>this.erro.set(e.error?.erro||'Falha de conexão ao processar a ação.')})}
}

