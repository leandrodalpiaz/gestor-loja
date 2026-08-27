import { Injectable, inject, signal } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Session, User } from '@supabase/supabase-js';
import { environment } from '../../environments/environment';
import { Observable, from, map, catchError, of, switchMap, tap, finalize, shareReplay } from 'rxjs';
import { supabaseClient } from '../supabase-client';

@Injectable({
  providedIn: 'root'
})
export class SupabaseService {
  private http = inject(HttpClient);
  private supabase = supabaseClient;

  public session = signal<Session | Record<string, unknown> | null>(null);
  public user = signal<User | null>(null);
  public profile = signal<any | null>(null);
  public loading = signal<boolean>(true);
  private profileRequest$: Observable<any> | null = null;

  constructor() {
    this.supabase.auth.onAuthStateChange((event, session) => {
      // A sessão CIM é mantida pelo cookie PHP. Enquanto ela estiver ativa,
      // eventos de um JWT Supabase antigo não podem substituir o estado
      // legado em memória.
      if (this.isLegacySessionActive()) {
        return;
      }

      this.session.set(session);
      this.user.set(session?.user ?? null);

      if (session) {
        this.fetchProfile(session.access_token).subscribe({
          next: () => this.loading.set(false),
          error: () => this.loading.set(false)
        });
      } else if (!this.isLegacySessionActive()) {
        this.profile.set(null);
        this.loading.set(false);
      }
    });

    this.supabase.auth.getSession().then(({ data: { session } }) => {
      if (this.isLegacySessionActive()) {
        this.ensureLegacySession().subscribe({
          next: (authenticated) => {
            if (!authenticated) {
              this.loading.set(false);
            }
          },
          error: () => this.loading.set(false)
        });
        return;
      }

      if (session) {
        this.session.set(session);
        this.user.set(session.user);
        this.fetchProfile(session.access_token).subscribe({
          next: () => this.loading.set(false),
          error: () => this.loading.set(false)
        });
        return;
      }

      this.ensureLegacySession().subscribe({
        next: (authenticated) => {
          if (!authenticated) {
            this.loading.set(false);
          }
        },
        error: () => this.loading.set(false)
      });
    });
  }

  login(identifier: string, password: string): Observable<any> {
    const normalized = identifier.trim();
    if (normalized.includes('@')) {
      return this.loginWithEmail(normalized, password);
    }
    return this.loginWithCim(normalized, password);
  }

  private loginWithEmail(email: string, password: string): Observable<any> {
    // Permite trocar de uma sessão CIM/PHP para uma sessão Supabase sem que
    // o interceptor reutilize o cookie legado no carregamento do perfil.
    this.clearLegacySessionMarker();

    return from(this.supabase.auth.signInWithPassword({ email, password })).pipe(
      switchMap(response => {
        if (response.error) {
          throw response.error;
        }
        const token = response.data.session?.access_token;
        if (!token) {
          throw new Error('A autenticação não retornou uma sessão válida.');
        }
        return this.fetchProfile(token).pipe(
          map(profile => {
            if (!profile) {
              throw new Error('Esta conta não está vinculada a um perfil ativo do Gestor-Loja.');
            }
            return response.data;
          })
        );
      })
    );
  }

  private loginWithCim(cim: string, password: string): Observable<any> {
    return this.http.post<any>(`${environment.apiUrl}/api/auth/login-cim`, {
      cim,
      password
    }, {
      withCredentials: true
    }).pipe(
      switchMap(res => {
        if (!res?.ok) {
          throw new Error(res?.erro || 'Falha ao autenticar o obreiro.');
        }
        this.markLegacySessionActive();
        this.session.set({ mode: 'legacy' });
        this.user.set(null);
        void this.supabase.auth.signOut({ scope: 'local' });
        return this.fetchProfile(null);
      })
    );
  }

  logout(): Observable<any> {
    this.profile.set(null);
    return this.http.post<any>(`${environment.apiUrl}/api/auth/logout`, {}, {
      withCredentials: true
    }).pipe(
      switchMap(() => from(this.supabase.auth.signOut({ scope: 'local' }))),
      tap(() => {
        this.session.set(null);
        this.user.set(null);
        this.clearLegacySessionMarker();
      }),
      catchError(() => {
        this.session.set(null);
        this.user.set(null);
        this.profile.set(null);
        this.clearLegacySessionMarker();
        return of({ ok: true });
      })
    );
  }

  getToken(): string | null {
    const current = this.session();
    return current && 'access_token' in current ? String((current as any).access_token ?? '') || null : null;
  }

  async getValidToken(): Promise<string | null> {
    const { data, error } = await this.supabase.auth.getSession();
    if (error || !data.session) {
      return null;
    }
    this.session.set(data.session);
    this.user.set(data.session.user);
    return data.session.access_token;
  }

  ensureAuthenticated(): Observable<boolean> {
    this.loading.set(true);
    return from(this.supabase.auth.getSession()).pipe(
      switchMap(({ data, error }) => {
        if (this.isLegacySessionActive()) {
          return this.ensureLegacySession();
        }

        const session = error ? null : data.session;
        if (session) {
          this.session.set(session);
          this.user.set(session.user);
          return this.fetchProfile(session.access_token).pipe(map(profile => !!profile));
        }
        return this.ensureLegacySession();
      }),
      catchError(() => of(false)),
      tap(() => this.loading.set(false))
    );
  }

  clearLocalAuth(): void {
    this.session.set(null);
    this.user.set(null);
    this.profile.set(null);
    this.clearLegacySessionMarker();
    void this.supabase.auth.signOut({ scope: 'local' });
  }

  getAuthHeaders(): HttpHeaders {
    const token = this.getToken();
    return new HttpHeaders({
      'Content-Type': 'application/json',
      'Authorization': token ? `Bearer ${token}` : ''
    });
  }

  private ensureLegacySession(): Observable<boolean> {
    if (!this.isLegacySessionActive()) {
      return of(false);
    }

    this.session.set({ mode: 'legacy' });
    this.user.set(null);
    return this.fetchProfile(null).pipe(map(profile => !!profile));
  }

  private fetchProfile(token: string | null): Observable<any> {
    if (this.profileRequest$) {
      return this.profileRequest$;
    }

    const headers = token ? new HttpHeaders({ 'Authorization': `Bearer ${token}` }) : undefined;

    this.profileRequest$ = this.http.get(`${environment.apiUrl}/api/auth/me`, {
      headers,
      responseType: 'text',
      withCredentials: true
    }).pipe(
      map(body => {
        const normalizedBody = body.replace(/^\uFEFF/, '').trim();
        const jsonStart = normalizedBody.indexOf('{');
        const jsonEnd = normalizedBody.lastIndexOf('}');

        if (jsonStart < 0 || jsonEnd < jsonStart) {
          throw new Error(`Resposta não JSON do backend: ${normalizedBody.slice(0, 200)}`);
        }

        let res: any;
        try {
          res = JSON.parse(normalizedBody.slice(jsonStart, jsonEnd + 1));
        } catch {
          throw new Error(`JSON invalido retornado pelo backend: ${normalizedBody.slice(0, 200)}`);
        }

        if (res && res.ok && res.user) {
          this.profile.set(res.user);
          if (!token) {
            this.markLegacySessionActive();
            this.session.set({ mode: 'legacy' });
          }
          return res.user;
        }
        throw new Error(res.erro || 'Resposta invalida do servidor.');
      }),
      catchError(error => {
        console.error('[SupabaseService] Erro ao carregar perfil no backend:', error);
        this.profile.set(null);
        if (!token) {
          this.session.set(null);
          this.clearLegacySessionMarker();
        }
        throw error;
      }),
      finalize(() => this.profileRequest$ = null),
      shareReplay({ bufferSize: 1, refCount: false })
    );

    return this.profileRequest$;
  }

  private isLegacySessionActive(): boolean {
    return localStorage.getItem('gestor_auth_mode') === 'legacy';
  }

  private markLegacySessionActive(): void {
    localStorage.setItem('gestor_auth_mode', 'legacy');
  }

  private clearLegacySessionMarker(): void {
    localStorage.removeItem('gestor_auth_mode');
  }
}
