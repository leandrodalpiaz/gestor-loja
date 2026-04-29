<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Assistente IA</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <link rel="stylesheet" href="/assets/css/tailwind.generated.css">
    <style>
        body { background: var(--tg-theme-bg-color, #fff); color: var(--tg-theme-text-color, #222); }
    </style>
</head>
<body class="min-h-screen p-4">
<div class="mx-auto max-w-lg space-y-4">
    <div>
        <h1 class="text-xl font-bold">Assistente IA</h1>
        <p class="mt-1 text-sm text-gray-500">Comandos naturais curtos para abrir telas e reduzir atrito.</p>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white/80 p-4">
        <input id="comando" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="Ex.: fechar mes, mensalidade do leandro">
        <button id="interpretar" class="mt-3 w-full rounded-xl bg-indigo-700 px-3 py-3 text-sm font-medium text-white">Interpretar</button>
        <div class="mt-3 grid grid-cols-3 gap-2">
            <button type="button" data-exemplo="fechar mes" class="rounded-lg border border-gray-300 px-2 py-2 text-xs">fechar mes</button>
            <button type="button" data-exemplo="mensalidade do leandro" class="rounded-lg border border-gray-300 px-2 py-2 text-xs">mensalidade</button>
            <button type="button" data-exemplo="aniversario joao" class="rounded-lg border border-gray-300 px-2 py-2 text-xs">aniversario</button>
        </div>
    </div>

    <div id="resposta" class="hidden rounded-2xl border border-gray-200 bg-white/80 p-4 text-sm"></div>
</div>

<script>
const tg = window.Telegram.WebApp;
tg.ready();
tg.expand();

function esc(v) {
    return String(v ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function abrirDestino(dest) {
    try {
        const url = new URL(dest, window.location.origin);
        url.searchParams.set('init_data', tg.initData);
        window.location.href = url.pathname + url.search;
    } catch (err) {
        tg.showAlert('NÃ£o foi possÃ­vel abrir o destino.');
    }
}

async function api(url, options = {}) {
    const finalOptions = { ...options };
    finalOptions.headers = { 'Content-Type': 'application/json', ...(options.headers || {}) };
    if (finalOptions.body && typeof finalOptions.body !== 'string') {
        finalOptions.body = JSON.stringify({
            ...finalOptions.body,
            initData: tg.initData
        });
    }
    const joiner = url.includes('?') ? '&' : '?';
    const response = await fetch(url + joiner + 'initData=' + encodeURIComponent(tg.initData), finalOptions);
    const json = await response.json();
    if (!json.ok) throw new Error(json.erro || 'NÃ£o foi possÃ­vel interpretar o comando agora.');
    return json;
}

document.getElementById('interpretar').addEventListener('click', async () => {
    const comando = String(document.getElementById('comando').value || '').trim();
    if (!comando) {
        tg.showAlert('Digite um comando para o assistente.');
        return;
    }

    const resposta = document.getElementById('resposta');
    resposta.classList.remove('hidden');
    resposta.textContent = 'Interpretando...';

    try {
        const json = await api('/api/miniapp/assistente/interpretar', {
            method: 'POST',
            body: { comando }
        });

        const resultado = json.resultado || {};
        const message = String(resultado.message || 'Sem resposta do assistente.');
        const action = resultado.action && resultado.action.target ? resultado.action : null;
        const suggestions = Array.isArray(resultado.suggestions) ? resultado.suggestions : [];

        let html = `<div class="font-medium">${esc(message)}</div>`;
        if (action) {
            html += `<button id="abrir-resultado" class="mt-3 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm">${esc(action.label || 'Abrir')}</button>`;
        }
        if (suggestions.length > 0) {
            html += '<div class="mt-3 text-xs uppercase tracking-[0.2em] text-gray-500">Sugestoes</div>';
            html += '<div class="mt-2 space-y-1">' + suggestions.map((s) => `<div class="text-sm text-gray-700">${esc(s)}</div>`).join('') + '</div>';
        }

        resposta.innerHTML = html;
        const abrir = document.getElementById('abrir-resultado');
        if (abrir && action) {
            abrir.addEventListener('click', () => abrirDestino(action.target));
        }
    } catch (err) {
        resposta.textContent = err.message;
    }
});

document.querySelectorAll('[data-exemplo]').forEach((btn) => {
    btn.addEventListener('click', () => {
        document.getElementById('comando').value = btn.getAttribute('data-exemplo') || '';
        document.getElementById('comando').focus();
    });
});
</script>
</body>
</html>
