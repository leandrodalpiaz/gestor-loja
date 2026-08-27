# Frontend — Gestor de Loja

SPA Angular 22 (standalone components) do Gestor de Loja. Publicado no Cloudflare Pages; o backend PHP (`../public`) serve API/webhook/health e mantém as pontes legadas do PWA/Telegram.

## Development server

Este projeto roda na porta 4300 (não a 4200 padrão do Angular CLI), configurada em `package.json`:

```bash
npm start
```

Isso executa `ng serve --host 0.0.0.0 --port 4300 --configuration development`, que usa `proxy.conf.json` para encaminhar `/api` e `/assets` para o backend local em `http://localhost:8000`. Abra `http://localhost:4300/`.

## Code scaffolding

Angular CLI includes powerful code scaffolding tools. To generate a new component, run:

```bash
ng generate component component-name
```

For a complete list of available schematics (such as `components`, `directives`, or `pipes`), run:

```bash
ng generate --help
```

## Building

To build the project run:

```bash
ng build
```

This will compile your project and store the build artifacts in the `dist/` directory. By default, the production build optimizes your application for performance and speed.

## Running unit tests

To execute unit tests with the [Vitest](https://vitest.dev/) test runner, use the following command:

```bash
ng test
```

## Running end-to-end tests

For end-to-end (e2e) testing, run:

```bash
ng e2e
```

Angular CLI does not come with an end-to-end testing framework by default. You can choose one that suits your needs.

## Additional Resources

For more information on using the Angular CLI, including detailed command references, visit the [Angular CLI Overview and Command Reference](https://angular.dev/tools/cli) page.
