import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { map } from 'rxjs';
import { SupabaseService } from './services/supabase.service';

export const authGuard: CanActivateFn = () => {
  const auth = inject(SupabaseService);
  const router = inject(Router);

  return auth.ensureAuthenticated().pipe(
    map((authenticated) => authenticated ? true : router.createUrlTree(['/login']))
  );
};

export const guestGuard: CanActivateFn = () => {
  const auth = inject(SupabaseService);
  const router = inject(Router);

  return auth.ensureAuthenticated().pipe(
    map((authenticated) => authenticated ? router.createUrlTree(['/dashboard']) : true)
  );
};
