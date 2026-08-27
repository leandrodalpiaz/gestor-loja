<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Efemérides</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <link rel="stylesheet" href="/assets/css/tailwind.generated.css">
    <style>
        body { background: var(--tg-theme-bg-color, #fff); color: var(--tg-theme-text-color, #222); }
        input, textarea, select { background: var(--tg-theme-secondary-bg-color, #f3f4f6); color: var(--tg-theme-text-color, #222); border-color: var(--tg-theme-hint-color, #d1d5db); }
    </style>
</head>
<body class="min-h-screen p-4">
<div class="max-w-2xl mx-auto space-y-4">
    <div>
        <h1 class="text-xl font-bold">Efemérides</h1>
        <p class="text-sm text-gray-500">Cadastre e mantenha datas do calendário da Loja.</p>
    </div>
    <div class="rounded-2xl border border-slate-200 p-4 space-y-3">
        <h2 class="font-semibold">Fluxo Telegram (texto + imagem)</h2>
        <p id="preview-status" class="text-xs text-gray-500">Valide a prévia e envie para o grupo com os cards.</p>
        <div class="grid grid-cols-2 gap-2">
            <button type="button" id="btn-preview" class="rounded-xl bg-slate-200 px-4 py-3 text-sm font-semibold">Ver prévia do dia</button>
            <button type="button" id="btn-enviar-grupo" class="rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white">Enviar ao grupo</button>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 p-4">
        <h2 class="font-semibold mb-3">Nova efeméride</h2>
        <form id="form-efemeride" class="space-y-3">
            <input type="hidden" name="id" value="">
            <div>
                <label class="block text-sm mb-1">Nome</label>
                <input name="nome" required class="w-full rounded-lg border px-3 py-2 text-sm">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm mb-1">Tipo</label>
                    <select name="tipo" class="w-full rounded-lg border px-3 py-2 text-sm">
                        <option>Aniversário</option>
                        <option>Iniciação</option>
                        <option>Elevação</option>
                        <option>Exaltação</option>
                        <option>Instalação</option>
                        <option>Oriente Eterno</option>
                        <option>Posse Grão Mestre</option>
                        <option>Concessão de Obreiro Honorário</option>
                        <option>Filiação</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm mb-1">Data</label>
                    <input type="date" name="data_evento" required class="w-full rounded-lg border px-3 py-2 text-sm">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm mb-1">Vínculo</label>
                    <input name="vinculo" class="w-full rounded-lg border px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm mb-1">Parentesco</label>
                    <input name="parentesco" class="w-full rounded-lg border px-3 py-2 text-sm">
                </div>
            </div>
            <div>
                <label class="block text-sm mb-1">Local</label>
                <input name="local" class="w-full rounded-lg border px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm mb-1">Mensagem custom (opcional)</label>
                <textarea name="mensagem_custom" rows="3" class="w-full rounded-lg border px-3 py-2 text-sm"></textarea>
            </div>
            <div class="flex gap-2">
                <button class="flex-1 rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white">Salvar</button>
                <button type="button" id="cancelar-edicao" class="hidden rounded-xl bg-slate-200 px-4 py-3 text-sm">Cancelar</button>
            </div>
        </form>
    </div>

    <div class="rounded-2xl border border-slate-200 p-4">
        <h2 class="font-semibold mb-3">Registros</h2>
        <div id="lista" class="space-y-3 text-sm text-slate-700"></div>
    </div>
</div>

<script>
const tg = window.Telegram.WebApp;
tg.ready();
tg.expand();

const form = document.getElementById('form-efemeride');
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
    form.nome.value = item.nome || '';
    form.tipo.value = item.tipo || 'Aniversário';
    form.data_evento.value = item.data_evento || '';
    form.vinculo.value = item.vinculo || '';
    form.parentesco.value = item.parentesco || '';
    form.local.value = item.local || '';
    form.mensagem_custom.value = item.mensagem_custom || '';
    cancel.classList.remove('hidden');
    window.scrollTo({top: 0, behavior: 'smooth'});
}

async function carregar() {
    const json = await request('/api/miniapp/efemerides/listar', {method: 'GET'});
    const lista = document.getElementById('lista');
    lista.innerHTML = '';

    (json.registros || []).forEach(item => {
        const card = document.createElement('div');
        card.className = 'rounded-xl border border-slate-200 bg-slate-50 p-3';
        card.innerHTML = `
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="font-medium">${esc(item.nome)}</div>
                    <div class="text-xs text-slate-500 mt-1">${esc(item.tipo)} · ${esc(item.data_evento)} · ${item.ativo ? 'Regular' : 'Afastado'}</div>
                </div>
                <div class="flex gap-2 text-xs">
                    <button class="text-blue-600" data-act="edit">Editar</button>
                    <button class="text-amber-600" data-act="toggle">${item.ativo ? 'Desativar' : 'Ativar'}</button>
                    <button class="text-red-600" data-act="delete">Excluir</button>
                </div>
            </div>`;
        card.querySelector('[data-act="edit"]').addEventListener('click', () => preencher(item));
        card.querySelector('[data-act="toggle"]').addEventListener('click', async () => {
            await request('/api/miniapp/efemeride/desativar', {method: 'POST', body: {id: item.id}});
            await carregar();
        });
        card.querySelector('[data-act="delete"]').addEventListener('click', async () => {
            if (!confirm('Excluir esta efeméride?')) return;
            await request('/api/miniapp/efemerides/excluir', {method: 'POST', body: {id: item.id}});
            await carregar();
        });
        lista.appendChild(card);
    });
}

form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const data = Object.fromEntries(new FormData(form));
    const json = await request('/api/miniapp/efemeride/salvar', {method: 'POST', body: data});
    if (!json.ok) {
        tg.showAlert(json.erro || 'Falha ao salvar.');
        return;
    }
    resetForm();
    await carregar();
});

cancel.addEventListener('click', resetForm);

document.getElementById('btn-preview').addEventListener('click', async () => {
    const status = document.getElementById('preview-status');
    status.textContent = 'Carregando prévia...';
    const json = await request('/api/miniapp/efemerides/preview', {method: 'GET'});
    if (!json.ok) {
        status.textContent = json.erro || 'Falha ao carregar prévia.';
        return;
    }
    status.textContent = `Prévia pronta: ${json.cards_total || 0} card(s) e mensagem com ${String(json.mensagem_preview || '').length} caracteres.`;
});

document.getElementById('btn-enviar-grupo').addEventListener('click', async () => {
    const status = document.getElementById('preview-status');
    if (!confirm('Enviar efemérides no grupo com texto + imagens?')) return;
    status.textContent = 'Enviando mensagem e cards no grupo...';
    const json = await request('/api/miniapp/efemerides/enviar-grupo', {method: 'POST', body: {}});
    if (!json.ok) {
        status.textContent = json.erro || 'Falha no envio ao grupo.';
        return;
    }
    status.textContent = `Envio concluído. Cards enviados: ${json.cards_enviados || 0}.`;
});

carregar();
</script>
</body>
</html>

