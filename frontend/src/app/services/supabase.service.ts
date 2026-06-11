import { Injectable, inject, signal } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { createClient, SupabaseClient, Session, User } from '@supabase/supabase-js';
import { environment } from '../../environments/environment';
import { Observable, from, map, catchError, of } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class SupabaseService {
  private http = inject(HttpClient);
  private supabase: SupabaseClient;
  
  // Angular Signals para gerenciamento de estado reativo moderno
  public session = signal<Session | null>(null);
  public user = signal<User | null>(null);
  public profile = signal<any | null>(null);
  public loading = signal<boolean>(true);

  constructor() {
    this.supabase = createClient(environment.supabaseUrl, environment.supabaseKey, {
      auth: {
        persistSession: true,
        autoRefreshToken: true
      }
    });

    // Escuta mudanças de estado da autenticação (login, logout, refresh de token)
    this.supabase.auth.onAuthStateChange((event, session) => {
      this.session.set(session);
      this.user.set(session?.user ?? null);
      
      if (session) {
        this.fetchProfile(session.access_token).subscribe({
          next: () => this.loading.set(false),
          error: () => this.loading.set(false)
        });
      } else {
        this.profile.set(null);
        this.loading.set(false);
      }
    });

    // Recupera a sessão inicial persistida
    this.supabase.auth.getSession().then(({ data: { session } }) => {
      this.session.set(session);
      this.user.set(session?.user ?? null);
      
      if (session) {
        this.fetchProfile(session.access_token).subscribe({
          next: () => this.loading.set(false),
          error: () => this.loading.set(false)
        });
      } else {
        this.loading.set(false);
      }
    });
  }

  /**
   * Realiza o login utilizando E-mail e Senha no Supabase.
   */
  login(email: string, password: string): Observable<any> {
    return from(this.supabase.auth.signInWithPassword({ email, password })).pipe(
      map(response => {
        if (response.error) {
          throw response.error;
        }
        return response.data;
      })
    );
  }

  /**
   * Efetua o logout no Supabase.
   */
  logout(): Observable<any> {
    this.profile.set(null);
    return from(this.supabase.auth.signOut());
  }

  /**
   * Retorna o token JWT ativo.
   */
  getToken(): string | null {
    return this.session()?.access_token ?? null;
  }

  /**
   * Cria cabeçalhos HTTP com o Bearer token do Supabase.
   */
  getAuthHeaders(): HttpHeaders {
    const token = this.getToken();
    return new HttpHeaders({
      'Content-Type': 'application/json',
      'Authorization': token ? `Bearer ${token}` : ''
    });
  }

  /**
   * Busca as informações do obreiro local correspondente ao e-mail do JWT no PHP.
   */
  private fetchProfile(token: string): Observable<any> {
    const headers = new HttpHeaders({
      'Authorization': `Bearer ${token}`
    });

    return this.http.get<any>(`${environment.apiUrl}/api/auth/me`, { headers }).pipe(
      map(res => {
        if (res && res.ok && res.user) {
          this.profile.set(res.user);
          return res.user;
        }
        throw new Error(res.erro || 'Resposta inválida do servidor.');
      }),
      catchError(error => {
        console.error('[SupabaseService] Erro ao carregar perfil no backend:', error);
        this.profile.set(null);
        return of(null);
      })
    );
  }
}
