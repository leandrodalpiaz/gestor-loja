<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>VenerÃ¡vel Mobile</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <link rel="stylesheet" href="/assets/css/tailwind.generated.css">
    <style>
        body { background: var(--tg-theme-bg-color, #fff); color: var(--tg-theme-text-color, #222); }
        .card { background: var(--tg-theme-secondary-bg-color, #f8fafc); }
        select {
            background: var(--tg-theme-secondary-bg-color, #f3f4f6);
            color: var(--tg-theme-text-color, #222);
            border-color: var(--tg-theme-hint-color, #d1d5db);
        }
    </style>
</head>
<body class="min-h-screen p-4">
<div class="mx-auto max-w-lg space-y-4">
    <div>
        <h1 class="text-xl font-bold">VenerÃ¡vel Mestre</h1>
        <p class="mt-1 text-sm text-gray-500">DecisÃµes de sessÃ£o, votaÃ§Ãµes e governanÃ§a crÃ­tica no mobile.</p>
    </div>

    <div id="loading" class="text-sm text-gray-400">Carregando painel...</div>
    <div id="erro" class="hidden rounded-lg bg-red-50 p-3 text-sm text-red-700"></div>

    <div id="conteudo" class="hidden space-y-4">
        <div class="card rounded-2xl p-4">
            <div class="text-xs uppercase tracking-wide text-gray-400">SessÃ£o em foco</div>
            <div id="sessao-titulo" class="mt-1 text-base font-semibold"></div>
            <div id="sessao-meta" class="mt-1 text-sm text-gray-600"></div>

            <div class="mt-4">
                <label class="mb-1 block text-sm font-medium text-gray-700">Trocar sessÃ£o</label>
                <select id="sessao-select" class="w-full rounded-lg border px-3 py-2 text-sm"></select>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                <div class="rounded-xl bg-white/70 p-3">
                    <div class="text-gray-500">Confirmados</div>
                    <div id="meta-confirmados" class="mt-1 text-lg font-semibold"></div>
                </div>
                <div class="rounded-xl bg-white/70 p-3">
                    <div class="text-gray-500">Ãgape</div>
                    <div id="meta-agape" class="mt-1 text-lg font-semibold"></div>
                </div>
                <div class="rounded-xl bg-white/70 p-3">
                    <div class="text-gray-500">Balaustres aptos</div>
                    <div id="meta-aptos" class="mt-1 text-lg font-semibold"></div>
                </div>
                <div class="rounded-xl bg-white/70 p-3">
                    <div class="text-gray-500">Em votaÃ§Ã£o</div>
                    <div id="meta-votaÃ§Ã£o" class="mt-1 text-lg font-semibold"></div>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-3">
                <button data-acao-sessao="publicar" class="rounded-xl bg-slate-900 px-4 py-3 text-sm font-medium text-white">Publicar</button>
                <button data-acao-sessao="realizar" class="rounded-xl bg-amber-400 px-4 py-3 text-sm font-medium text-slate-900">Realizar</button>
                <button data-acao-sessao="cancelar" class="rounded-xl border border-rose-300 bg-white px-4 py-3 text-sm font-medium text-rose-700">Cancelar</button>
                <button data-acao-sessao="reabrir" class="rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-medium text-slate-700">Reabrir</button>
            </div>
        </div>

        <div class="card rounded-2xl p-4">
            <div class="text-sm font-semibold">VotaÃ§Ãµes de balaustre</div>
            <div id="lista-balaustres-aptos" class="mt-3 space-y-2 text-sm"></div>
            <div id="lista-balaustres-votaÃ§Ã£o" class="mt-3 space-y-2 text-sm"></div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div class="card rounded-2xl p-4">
                <div class="text-sm font-semibold">Cargos criticos pendentes</div>
                <div id="lista-cargos-pendentes" class="mt-3 space-y-2 text-sm"></div>
            </div>
            <div class="card rounded-2xl p-4">
                <div class="text-sm font-semibold">PendÃªncias cadastrais</div>
                <div id="lista-obreiros-pendentes" class="mt-3 space-y-2 text-sm"></div>
            </div>
        </div>
    </div>
</div>

<script>
const tg = window.Telegram.WebApp;
tg.ready();
tg.expand();

let dashboard = null;
let sessaoAtualId = null;

function esc(v) {
    return String(v ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

async function api(url, options = {}) {
    const finalOptions = { ...options };
    finalOptions.headers = { 'Content-Type': 'application/json', ...(options.headers || {}) };
    if (finalOptions.body && typeof finalOptions.body !== 'string') {
        finalOptions.body = JSON.stringify({ ...finalOptions.body, initData: tg.initData });
    }
    const joiner = url.includes('?') ? '&' : '?';
    const response = await fetch(url + joiner + 'initData=' + encodeURIComponent(tg.initData), finalOptions);
    const json = await response.json();
    if (!json.ok) throw new Error(json.erro || 'NÃ£o conseguimos concluir sua solicitaÃ§Ã£o agora. Tente novamente em alguns minutos.');
    return json;
}

function renderListaSimples(id, itens, vazio, mapper) {
    const root = document.getElementById(id);
    root.innerHTML = '';
    if (!itens || itens.length === 0) {
        root.innerHTML = `<div class="rounded-xl border border-dashed border-gray-300 px-3 py-3 text-gray-500">${esc(vazio)}</div>`;
        return;
    }
    itens.forEach(item => {
        const data = mapper(item);
        const div = document.createElement('div');
        div.className = 'rounded-xl border border-gray-200 bg-white/70 px-3 py-2';
        div.innerHTML = `<div class="font-medium">${esc(data.nome)}</div>${data.linha ? `<div class="text-xs text-gray-500 mt-1">${esc(data.linha)}</div>` : ''}`;
        root.appendChild(div);
    });
}

function renderBalaustres(id, itens, vazio, acao, label) {
    const root = document.getElementById(id);
    root.innerHTML = '';
    if (!itens || itens.length === 0) {
        root.innerHTML = `<div class="rounded-xl border border-dashed border-gray-300 px-3 py-3 text-gray-500">${esc(vazio)}</div>`;
        return;
    }
    itens.forEach(item => {
        const div = document.createElement('div');
        div.className = 'rounded-xl border border-gray-200 bg-white/70 px-3 py-3';
        div.innerHTML = `<div class="font-medium">${esc(item.numero_balaustre || 'Sem nÃºmero')}</div>
            <div class="mt-1 text-xs text-gray-500">${esc(item.sessao_titulo || item.data_hora_inicio || '')}</div>
            <button type="button" class="mt-3 w-full rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white">${esc(label)}</button>`;
        div.querySelector('button').addEventListener('click', async () => {
            try {
                await api('/api/miniapp/veneravel/balaustre/' + acao, {
                    method: 'POST',
                    body: { balaustre_id: item.id }
                });
                tg.showAlert('AÃ§Ã£o registrada com sucesso.');
                await carregar(sessaoAtualId);
            } catch (err) {
                tg.showAlert(err.message);
            }
        });
        root.appendChild(div);
    });
}

function render() {
    document.getElementById('loading').classList.add('hidden');
    document.getElementById('conteudo').classList.remove('hidden');

    const sessao = dashboard.sessao_foco;
    document.getElementById('sessao-titulo').textContent = sessao ? (sessao.titulo || 'SessÃ£o') : 'Sem sessÃ£o em foco';
    document.getElementById('sessao-meta').textContent = sessao ? `${sessao.data_hora_inicio || ''} Â· ${sessao.status || ''}` : 'Sem dados';
    document.getElementById('meta-confirmados').textContent = sessao ? (sessao.total_confirmados || 0) : 0;
    document.getElementById('meta-agape').textContent = sessao ? (sessao.total_agape || 0) : 0;
    document.getElementById('meta-aptos').textContent = dashboard.balaustres_aptos?.length || 0;
    document.getElementById('meta-votaÃ§Ã£o').textContent = dashboard.balaustres_em_votaÃ§Ã£o?.length || 0;

    const select = document.getElementById('sessao-select');
    select.innerHTML = '';
    (dashboard.sessoes || []).forEach(item => {
        const option = document.createElement('option');
        option.value = item.id;
        option.textContent = `${item.titulo || 'SessÃ£o'} Â· ${item.status || ''}`;
        if (sessao && item.id === sessao.id) option.selected = true;
        select.appendChild(option);
    });

    renderBalaustres('lista-balaustres-aptos', dashboard.balaustres_aptos, 'Nenhum balaustre apto para abrir votaÃ§Ã£o.', 'abrir-votaÃ§Ã£o', 'Abrir votaÃ§Ã£o');
    renderBalaustres('lista-balaustres-votaÃ§Ã£o', dashboard.balaustres_em_votaÃ§Ã£o, 'Nenhum balaustre em votaÃ§Ã£o.', 'encerrar-votaÃ§Ã£o', 'Encerrar votaÃ§Ã£o');
    renderListaSimples('lista-cargos-pendentes', dashboard.cargos_criticos_pendentes, 'Nenhum cargo crÃ­tico pendente.', item => ({
        nome: item.nome_exibicao || item.codigo,
        linha: item.codigo || ''
    }));
    renderListaSimples('lista-obreiros-pendentes', dashboard.obreiros_pendentes_criticos, 'Sem pendÃªncias cadastrais crÃ­ticas.', item => ({
        nome: item.nome,
        linha: `CIM ${item.cim || '-'} Â· ${Array.isArray(item.alertas) ? item.alertas.join(', ') : ''}`
    }));
}

async function carregar(sessaoId = null) {
    try {
        const query = sessaoId ? ('?sessao_id=' + encodeURIComponent(sessaoId)) : '';
        const json = await api('/api/miniapp/veneravel/dashboard' + query, { method: 'GET' });
        dashboard = json.dados;
        sessaoAtualId = dashboard.sessao_foco?.id || null;
        render();
    } catch (err) {
        const erro = document.getElementById('erro');
        erro.textContent = err.message;
        erro.classList.remove('hidden');
        document.getElementById('loading').classList.add('hidden');
    }
}

document.getElementById('sessao-select').addEventListener('change', (event) => carregar(event.target.value));
document.querySelectorAll('[data-acao-sessao]').forEach(button => {
    button.addEventListener('click', async () => {
        try {
            await api('/api/miniapp/veneravel/sessao/' + button.dataset.acaoSessao, {
                method: 'POST',
                body: { sessao_id: sessaoAtualId }
            });
            tg.showAlert('AÃ§Ã£o registrada com sucesso.');
            await carregar(sessaoAtualId);
        } catch (err) {
            tg.showAlert(err.message);
        }
    });
});

carregar();
</script>
</body>
</html>


