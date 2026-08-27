import { HttpErrorResponse, HttpInterceptorFn } from '@angular/common/http';
import { catchError, from, switchMap, throwError } from 'rxjs';
import { environment } from '../environments/environment';
import { supabaseClient } from './supabase-client';

let redirectingToLogin = false;

export const authInterceptor: HttpInterceptorFn = (request, next) => {
  const isBackendRequest = request.url.startsWith(environment.apiUrl);
  const isPublicApi = request.url.includes('/api/public/');
  const isLoginRequest = /\/api\/auth\/(?:login-cim|login)(?:$|\?)/.test(request.url);

  if (!isBackendRequest || isPublicApi) {
    return next(request);
  }

  const handleError = (error: HttpErrorResponse) => {
    if (error.status === 503) {
      window.location.href = '/';
    }

    // Um 401 em qualquer API protegida significa que o estado local ficou
    // inválido (por exemplo, após o container do Render ser redeployado).
    // Limpa o marcador legado e força uma nova inicialização da SPA, evitando
    // que o shell continue exibindo um perfil antigo enquanto os módulos
    // mostram "Nao autenticado.".
    if (error.status === 401 && !isLoginRequest) {
      localStorage.removeItem('gestor_auth_mode');
      void supabaseClient.auth.signOut({ scope: 'local' });

      if (!redirectingToLogin && window.location.pathname !== '/login') {
        redirectingToLogin = true;
        window.location.replace('/login');
      }
    }

    return throwError(() => error);
  };

  // O login CIM usa a sessão PHP. Se sobrou um JWT Supabase de uma sessão
  // anterior, ele não pode ter precedência sobre o cookie legado.
  if (localStorage.getItem('gestor_auth_mode') === 'legacy') {
    const legacyRequest = request.clone({
      withCredentials: true,
      headers: request.headers.delete('Authorization')
    });
    return next(legacyRequest).pipe(catchError(handleError));
  }

  return from(supabaseClient.auth.getSession()).pipe(
    switchMap(({ data }) => {
      const cloned = request.clone(data.session?.access_token ? {
        withCredentials: true,
        setHeaders: { Authorization: `Bearer ${data.session.access_token}` }
      } : {
        withCredentials: true
      });
      return next(cloned);
    }),
    catchError(handleError)
  );
};
