<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Orador Mobile</title>
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
        <h1 class="text-xl font-bold">Orador</h1>
        <p class="mt-1 text-sm text-gray-500">Pauta ritual, visitantes e lembretes no mobile.</p>
    </div>

    <div id="loading" class="text-sm text-gray-400">Carregando painel...</div>
    <div id="erro" class="hidden rounded-lg bg-red-50 p-3 text-sm text-red-700"></div>

    <div id="conteudo" class="hidden space-y-4">
        <div class="card rounded-2xl p-4 space-y-3">
            <div class="text-sm font-semibold">Sessão em foco</div>
            <select id="sessao_id" class="w-full rounded-lg border px-3 py-2 text-sm"></select>
            <div class="rounded-xl bg-white/70 p-3">
                <div id="sessao-titulo" class="font-semibold"></div>
                <div id="sessao-data" class="mt-1 text-xs text-gray-500"></div>
                <div id="sessao-badges" class="mt-3 flex flex-wrap gap-2 text-xs"></div>
            </div>
        </div>

        <div class="card rounded-2xl p-4">
            <div class="text-sm font-semibold">Resumo ritual</div>
            <div id="resumo-ritual" class="mt-3 rounded-xl bg-white/70 p-3 text-sm text-gray-700"></div>
        </div>

        <div class="card rounded-2xl p-4">
            <div class="flex items-center justify-between gap-3">
                <div class="text-sm font-semibold">Visitantes</div>
                <div id="visitantes-total" class="text-xs text-gray-500"></div>
            </div>
            <div id="lista-visitantes" class="mt-3 space-y-2 text-sm"></div>
        </div>

        <div class="card rounded-2xl p-4">
            <div class="text-sm font-semibold">Cargos da sessão</div>
            <div id="lista-cargos" class="mt-3 space-y-2 text-sm"></div>
        </div>

        <div class="card rounded-2xl p-4">
            <div class="text-sm font-semibold">Atividades registrados</div>
            <div id="lista-eventos" class="mt-3 space-y-2 text-sm"></div>
        </div>

        <div class="card rounded-2xl p-4">
            <div class="text-sm font-semibold">Lembretes do cargo</div>
            <div id="lista-lembretes" class="mt-3 space-y-2 text-sm"></div>
        </div>
    </div>
</div>

<script>
const tg = window.Telegram.WebApp;
tg.ready();
tg.expand();
let dashboard = null;

function esc(v) {
    return String(v ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

async function api(url, options = {}) {
    const finalOptions = { ...options };
    finalOptions.headers = { 'Content-Type': 'application/json', ...(options.headers || {}) };
    const joiner = url.includes('?') ? '&' : '?';
    const response = await fetch(url + joiner + 'initData=' + encodeURIComponent(tg.initData), finalOptions);
    const json = await response.json();
    if (!json.ok) throw new Error(json.erro || 'Não conseguimos carregar este painel no momento. Atualize a tela e tente novamente.');
    return json;
}

function renderLista(id, itens, vazio, mapper) {
    const root = document.getElementById(id);
    root.innerHTML = '';
    if (!itens || itens.length === 0) {
        root.innerHTML = `<div class="rounded-xl border border-dashed border-gray-300 px-3 py-3 text-gray-500">${esc(vazio)}</div>`;
        return;
    }
    itens.forEach(item => {
        const div = document.createElement('div');
        div.className = 'rounded-xl border border-gray-200 bg-white/70 px-3 py-3';
        div.innerHTML = mapper(item);
        root.appendChild(div);
    });
}

function preencherSessoes() {
    const select = document.getElementById('sessao_id');
    select.innerHTML = '';
    (dashboard.sessoes || []).forEach(sessao => {
        const option = document.createElement('option');
        option.value = sessao.id;
        option.textContent = `${sessao.titulo || `${sessao.tipo_sessao || 'Sessão'} - ${sessao.grau_sessao || ''}`} · ${sessao.data_hora_inicio || ''}`;
        if (dashboard.sessao_foco && Number(dashboard.sessao_foco.id) === Number(sessao.id)) {
            option.selected = true;
        }
        select.appendChild(option);
    });
}

function render() {
    document.getElementById('loading').classList.add('hidden');
    document.getElementById('conteudo').classList.remove('hidden');

    preencherSessoes();
    const sessao = dashboard.sessao_foco || {};
    document.getElementById('sessao-titulo').textContent = sessao.titulo || `${sessao.tipo_sessao || 'Sessão'} - ${sessao.grau_sessao || ''}`;
    document.getElementById('sessao-data').textContent = sessao.data_hora_inicio || 'Sem data';
    document.getElementById('resumo-ritual').textContent = sessao.ordem_dia || sessao.resumo_publico || 'Sem resumo ritual registrado.';
    document.getElementById('visitantes-total').textContent = `${(dashboard.visitantes || []).length} visitante(s)`;
    document.getElementById('sessao-badges').innerHTML = `
        <span class="rounded-full bg-gray-100 px-3 py-1">${esc(sessao.grau_sessao || '-')}</span>
        <span class="rounded-full bg-gray-100 px-3 py-1">${esc(sessao.tipo_sessao || '-')}</span>
        <span class="rounded-full bg-amber-100 px-3 py-1">${esc(sessao.status || '-')}</span>
    `;

    renderLista('lista-visitantes', dashboard.visitantes, 'Nenhum visitante resumido nesta sessão.', item => `
        <div class="font-medium">${esc(item.nome || 'Visitante')}</div>
        <div class="mt-1 text-xs text-gray-500">${esc(item.linha_resumida || '')}</div>
    `);

    renderLista('lista-cargos', dashboard.cargos_sessao, 'Sem composição de cargos registrada.', item => `
        <div class="font-medium">${esc(item.cargo_nome || 'Cargo')}</div>
        <div class="mt-1 text-xs text-gray-500">${esc(item.ocupante_nome || 'Sem ocupante')}</div>
        <div class="mt-2 text-[11px] uppercase tracking-wide text-gray-400">${esc(item.tipo_ocupacao || 'regular')}</div>
    `);

    renderLista('lista-eventos', dashboard.eventos_sessao, 'Nenhum evento registrado nesta sessão.', item => `
        <div class="flex items-center justify-between gap-2">
            <div class="font-medium">${esc(item.titulo || 'Atividade')}</div>
            <div class="rounded-full bg-slate-100 px-2 py-1 text-[11px] uppercase tracking-wide">${esc(item.tipo || 'evento')}</div>
        </div>
        <div class="mt-1 text-xs text-gray-500">${esc(item.linha || '')}</div>
    `);

    renderLista('lista-lembretes', dashboard.lembretes, 'Sem lembretes adicionais.', item => `
        <div>${esc(item)}</div>
    `);
}

async function carregar(sessaoId = null) {
    try {
        const sufixo = sessaoId ? `?sessao_id=${encodeURIComponent(sessaoId)}` : '';
        const json = await api('/api/miniapp/orador/dashboard' + sufixo, { method: 'GET' });
        dashboard = json.dados;
        render();
    } catch (err) {
        const erro = document.getElementById('erro');
        erro.textContent = err.message;
        erro.classList.remove('hidden');
        document.getElementById('loading').classList.add('hidden');
    }
}

document.getElementById('sessao_id').addEventListener('change', async (event) => {
    await carregar(event.target.value);
});

carregar();
</script>
</body>
</html>



