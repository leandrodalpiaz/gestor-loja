import { bootstrapApplication } from '@angular/platform-browser';
import { appConfig } from './app/app.config';
import { App } from './app/app';

async function clearLegacyPwaCache(): Promise<void> {
  if ('serviceWorker' in navigator) {
    const registrations = await navigator.serviceWorker.getRegistrations();
    await Promise.all(registrations.map(registration => registration.unregister()));
  }

  if ('caches' in window) {
    const cacheNames = await caches.keys();
    await Promise.all(cacheNames.map(cacheName => caches.delete(cacheName)));
  }
}

clearLegacyPwaCache()
  .catch(error => console.warn('[Bootstrap] Falha ao limpar cache legado da PWA:', error))
  .then(() => bootstrapApplication(App, appConfig))
  .catch((err) => console.error(err));
