<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Tesouraria Mobile</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background: var(--tg-theme-bg-color, #fff); color: var(--tg-theme-text-color, #222); }
        input, textarea, select {
            background: var(--tg-theme-secondary-bg-color, #f3f4f6);
            color: var(--tg-theme-text-color, #222);
            border-color: var(--tg-theme-hint-color, #d1d5db);
        }
    </style>
</head>
<body class="min-h-screen p-4">
<div class="max-w-lg mx-auto">
    <h1 class="mb-1 text-lg font-bold">Tesouraria Mobile</h1>
    <p class="mb-4 text-sm text-gray-500">Painel rápido para Tesouraria: validar comprovantes, consultar caixa, regularidade e fechamento mensal.</p>

    <div class="space-y-4">
        <a href="/tesouraria/comprovantes" class="js-miniapp-link block w-full rounded-xl bg-blue-700 py-3 text-center text-lg font-semibold text-white transition hover:bg-blue-800">Validação de Comprovantes</a>
        <a href="/tesouraria/caixa" class="js-miniapp-link block w-full rounded-xl bg-green-700 py-3 text-center text-lg font-semibold text-white transition hover:bg-green-800">Livro-Caixa</a>
        <a href="/tesouraria/regularidade" class="js-miniapp-link block w-full rounded-xl bg-yellow-600 py-3 text-center text-lg font-semibold text-white transition hover:bg-yellow-700">Regularidade</a>
        <a href="/tesouraria/fechamento" class="js-miniapp-link block w-full rounded-xl bg-purple-700 py-3 text-center text-lg font-semibold text-white transition hover:bg-purple-800">Fechamento Mensal</a>
    </div>

    <div class="mt-8 text-center text-xs text-gray-400">Acesso restrito ao Tesoureiro, Venerável Mestre ou Administrador.</div>
</div>
<script>
    (function () {
        const tg = window.Telegram && window.Telegram.WebApp ? window.Telegram.WebApp : null;
        if (!tg || !tg.initData) {
            return;
        }

        tg.ready();

        document.querySelectorAll('.js-miniapp-link').forEach((anchor) => {
            try {
                const url = new URL(anchor.getAttribute('href'), window.location.origin);
                url.searchParams.set('init_data', tg.initData);
                anchor.setAttribute('href', url.pathname + url.search);
            } catch (error) {
                console.error('Falha ao montar link do miniapp:', error);
            }
        });

        const params = new URLSearchParams(window.location.search);
        const destino = params.get('dest');
        if (destino && destino.startsWith('/tesouraria/')) {
            try {
                const url = new URL(destino, window.location.origin);
                url.searchParams.set('init_data', tg.initData);
                window.location.replace(url.toString());
            } catch (error) {
                console.error('Falha ao redirecionar no miniapp:', error);
            }
        }
    })();
</script>
</body>
</html>
