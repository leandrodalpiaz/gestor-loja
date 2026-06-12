import { inject } from '@angular/core';
import { HttpErrorResponse, HttpInterceptorFn } from '@angular/common/http';
import { Router } from '@angular/router';
import { catchError, from, switchMap, throwError } from 'rxjs';
import { environment } from '../environments/environment';
import { supabaseClient } from './supabase-client';

export const authInterceptor: HttpInterceptorFn = (request, next) => {
  const router = inject(Router);

  if (!request.url.startsWith(environment.apiUrl) || request.url.includes('/api/public/')) {
    return next(request);
  }

  return from(supabaseClient.auth.getSession()).pipe(
    switchMap(({ data }) => next(data.session?.access_token ? request.clone({
      setHeaders: { Authorization: `Bearer ${data.session.access_token}` }
    }) : request)),
    catchError((error: HttpErrorResponse) => {
      if (error.status === 401) {
        void supabaseClient.auth.signOut({ scope: 'local' });
        void router.navigate(['/login']);
      }
      return throwError(() => error);
    })
  );
};
