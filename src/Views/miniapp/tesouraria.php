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
    <h1 class="text-lg font-bold mb-1">💰 Tesouraria Mobile</h1>
    <p class="text-sm text-gray-500 mb-4">Painel rápido para tesoureiro: validar comprovantes, consultar caixa, regularidade e fechamento mensal.</p>

    <div class="space-y-4">
        <a href="/tesouraria/comprovantes" class="block w-full py-3 rounded-xl bg-blue-700 text-white text-center font-semibold text-lg hover:bg-blue-800 transition">Validação de Comprovantes</a>
        <a href="/tesouraria/caixa" class="block w-full py-3 rounded-xl bg-green-700 text-white text-center font-semibold text-lg hover:bg-green-800 transition">Livro-Caixa</a>
        <a href="/tesouraria/regularidade" class="block w-full py-3 rounded-xl bg-yellow-600 text-white text-center font-semibold text-lg hover:bg-yellow-700 transition">Regularidade</a>
        <a href="/tesouraria/fechamento" class="block w-full py-3 rounded-xl bg-purple-700 text-white text-center font-semibold text-lg hover:bg-purple-800 transition">Fechamento Mensal</a>
    </div>

    <div class="mt-8 text-xs text-gray-400 text-center">Acesso restrito ao Tesoureiro. Use o menu do bot para validar comprovantes e consultar saldo.</div>
</div>
</body>
</html>
