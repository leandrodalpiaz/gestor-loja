import { inject } from '@angular/core';
import { HttpErrorResponse, HttpInterceptorFn } from '@angular/common/http';
import { Router } from '@angular/router';
import { catchError, from, switchMap, throwError } from 'rxjs';
import { environment } from '../environments/environment';
import { supabaseClient } from './supabase-client';

export const authInterceptor: HttpInterceptorFn = (request, next) => {
  const router = inject(Router);
  const isBackendRequest = request.url.startsWith(environment.apiUrl);
  const isPublicApi = request.url.includes('/api/public/');
  const isAuthHandshake = request.url.includes('/api/auth/me') || request.url.includes('/api/ping');

  if (!isBackendRequest || isPublicApi) {
    return next(request);
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
    catchError((error: HttpErrorResponse) => {
      if (error.status === 503) {
        window.location.href = '/';
      }
      if (error.status === 401 && isAuthHandshake) {
        void supabaseClient.auth.signOut({ scope: 'local' });
        void router.navigate(['/login']);
      }
      return throwError(() => error);
    })
  );
};
