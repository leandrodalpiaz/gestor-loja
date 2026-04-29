<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Palavra do Dia</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <link rel="stylesheet" href="/assets/css/tailwind.generated.css">
    <style>
        body { background: var(--tg-theme-bg-color, #fff); color: var(--tg-theme-text-color, #222); }
        input, textarea { background: var(--tg-theme-secondary-bg-color, #f3f4f6); color: var(--tg-theme-text-color, #222); border-color: var(--tg-theme-hint-color, #d1d5db); }
    </style>
</head>
<body class="min-h-screen p-4">
<div class="max-w-2xl mx-auto space-y-4">
    <div>
        <h1 class="text-xl font-bold">Palavra do Dia</h1>
        <p class="text-sm text-gray-500">Mensagens editoriais diárias com gestão própria.</p>
    </div>

    <div class="rounded-2xl border border-slate-200 p-4">
        <h2 class="font-semibold mb-3">Nova mensagem</h2>
        <form id="form-palavra" class="space-y-3">
            <input type="hidden" name="id" value="">
            <div>
                <label class="block text-sm mb-1">Título opcional</label>
                <input name="titulo" class="w-full rounded-lg border px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm mb-1">Mensagem</label>
                <textarea name="mensagem" required rows="5" class="w-full rounded-lg border px-3 py-2 text-sm"></textarea>
            </div>
            <div>
                <label class="block text-sm mb-1">Observação editorial</label>
                <input name="observacao" class="w-full rounded-lg border px-3 py-2 text-sm">
            </div>
            <div class="flex gap-2">
                <button class="flex-1 rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white">Salvar</button>
                <button type="button" id="cancelar-edicao" class="hidden rounded-xl bg-slate-200 px-4 py-3 text-sm">Cancelar</button>
            </div>
        </form>
    </div>

    <div class="rounded-2xl border border-slate-200 p-4">
        <h2 class="font-semibold mb-3">Mensagens cadastradas</h2>
        <div id="lista" class="space-y-3"></div>
    </div>
</div>

<script>
const tg = window.Telegram.WebApp;
tg.ready();
tg.expand();

const form = document.getElementById('form-palavra');
const cancel = document.getElementById('cancelar-edicao');

function esc(v) {
    return String(v ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

async function request(url, options = {}) {
    const initData = tg.initData;
    const headers = {'Content-Type': 'application/json', ...(options.headers || {})};
    const body = options.body ? JSON.stringify({...options.body, initData}) : undefined;
    const response = await fetch(url + (url.includes('?') ? '&' : '?') + 'initData=' + encodeURIComponent(initData), {...options, headers, body});
    return response.json();
}

function resetForm() {
    form.reset();
    form.id.value = '';
    cancel.classList.add('hidden');
}

function preencher(item) {
    form.id.value = item.id || '';
    form.titulo.value = item.titulo || '';
    form.mensagem.value = item.mensagem || '';
    form.observacao.value = item.observacao || '';
    cancel.classList.remove('hidden');
    window.scrollTo({top: 0, behavior: 'smooth'});
}

async function carregar() {
    const json = await request('/api/miniapp/palavra-dia/listar', {method: 'GET'});
    const lista = document.getElementById('lista');
    lista.innerHTML = '';

    (json.mensagens || []).forEach(item => {
        const card = document.createElement('div');
        card.className = 'rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm';
        card.innerHTML = `
            <div class="flex items-start justify-between gap-3">
                <div class="flex-1">
                    <div class="font-medium">${esc(item.titulo || 'Palavra do Dia')}</div>
                    <div class="mt-1 text-slate-700">${esc(item.mensagem)}</div>
                    <div class="mt-2 text-xs text-slate-500">${item.ativo ? 'Ativa' : 'Inativa'}</div>
                </div>
                <div class="flex gap-2 text-xs">
                    <button class="text-blue-600" data-act="edit">Editar</button>
                    <button class="text-amber-600" data-act="toggle">${item.ativo ? 'Desativar' : 'Ativar'}</button>
                    <button class="text-red-600" data-act="delete">Excluir</button>
                </div>
            </div>`;
        card.querySelector('[data-act="edit"]').addEventListener('click', () => preencher(item));
        card.querySelector('[data-act="toggle"]').addEventListener('click', async () => {
            await request('/api/miniapp/palavra-dia/toggle', {method: 'POST', body: {id: item.id}});
            await carregar();
        });
        card.querySelector('[data-act="delete"]').addEventListener('click', async () => {
            if (!confirm('Excluir esta mensagem?')) return;
            await request('/api/miniapp/palavra-dia/excluir', {method: 'POST', body: {id: item.id}});
            await carregar();
        });
        lista.appendChild(card);
    });
}

form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const data = Object.fromEntries(new FormData(form));
    const json = await request('/api/miniapp/palavra-dia/salvar', {method: 'POST', body: data});
    if (!json.ok) {
        tg.showAlert(json.erro || 'Falha ao salvar.');
        return;
    }
    resetForm();
    await carregar();
});

cancel.addEventListener('click', resetForm);
carregar();
</script>
</body>
</html>
