<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Chanceler Mobile</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background: var(--tg-theme-bg-color, #fff); color: var(--tg-theme-text-color, #222); }
        .card { background: var(--tg-theme-secondary-bg-color, #f8fafc); }
        input, textarea, select {
            background: var(--tg-theme-secondary-bg-color, #f3f4f6);
            color: var(--tg-theme-text-color, #222);
            border-color: var(--tg-theme-hint-color, #d1d5db);
        }
    </style>
</head>
<body class="min-h-screen p-4">
<div class="mx-auto max-w-lg space-y-4">
    <div>
        <h1 class="text-xl font-bold">Chanceler Mobile</h1>
        <p class="mt-1 text-sm text-gray-500">Check-in da sessão, controle da nominata e leitura objetiva de visitantes.</p>
    </div>

    <div id="loading" class="text-sm text-gray-400">Carregando painel...</div>
    <div id="erro" class="hidden rounded-lg bg-red-50 p-3 text-sm text-red-700"></div>

    <div id="conteudo" class="hidden space-y-4">
        <div class="card rounded-2xl p-4">
            <div class="text-xs uppercase tracking-wide text-gray-400">Sessão em foco</div>
            <div id="sessao-titulo" class="mt-1 text-base font-semibold"></div>
            <div id="sessao-meta" class="mt-1 text-sm text-gray-600"></div>

            <div class="mt-4">
                <label class="mb-1 block text-sm font-medium text-gray-700">Trocar sessão</label>
                <select id="sessao-select" class="w-full rounded-lg border px-3 py-2 text-sm"></select>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                <div class="rounded-xl bg-white/70 p-3">
                    <div class="text-gray-500">Confirmados</div>
                    <div id="meta-confirmados" class="mt-1 text-lg font-semibold"></div>
                </div>
                <div class="rounded-xl bg-white/70 p-3">
                    <div class="text-gray-500">Presentes</div>
                    <div id="meta-presentes" class="mt-1 text-lg font-semibold"></div>
                </div>
                <div class="rounded-xl bg-white/70 p-3">
                    <div class="text-gray-500">Visitantes</div>
                    <div id="meta-visitantes" class="mt-1 text-lg font-semibold"></div>
                </div>
                <div class="rounded-xl bg-white/70 p-3">
                    <div class="text-gray-500">Ágape</div>
                    <div id="meta-agape" class="mt-1 text-lg font-semibold"></div>
                </div>
            </div>

            <a id="link-certificado" href="/chancelaria/certificado" class="mt-4 block w-full rounded-xl bg-slate-900 px-4 py-3 text-center text-sm font-medium text-white">Emitir certificado</a>
        </div>

        <div class="card rounded-2xl p-4">
            <div class="text-sm font-semibold">Atalhos da Chancelaria</div>
            <div class="mt-3 grid grid-cols-2 gap-3 text-sm">
                <a class="rounded-xl bg-white/70 px-3 py-3 text-center font-medium text-slate-700" href="/miniapp/aniversario">Aniversarios</a>
                <a class="rounded-xl bg-white/70 px-3 py-3 text-center font-medium text-slate-700" href="/miniapp/data-maconica">Datas maçônicas</a>
                <a class="rounded-xl bg-white/70 px-3 py-3 text-center font-medium text-slate-700" href="/miniapp/historico">Histórico</a>
                <a class="rounded-xl bg-white/70 px-3 py-3 text-center font-medium text-slate-700" href="/miniapp/fallback">Conteúdo complementar</a>
            </div>
        </div>

        <div class="card rounded-2xl p-4">
            <div class="flex items-center justify-between gap-3">
                <div class="text-sm font-semibold">Check-in da nominata</div>
                <div class="text-xs text-gray-500">Toque para alternar o status</div>
            </div>
            <div id="lista-presencas" class="mt-3 space-y-2 text-sm"></div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div class="card rounded-2xl p-4">
                <div class="text-sm font-semibold">Confirmados</div>
                <div id="lista-confirmados" class="mt-3 space-y-2 text-sm"></div>
            </div>
            <div class="card rounded-2xl p-4">
                <div class="text-sm font-semibold">Visitantes</div>
                <div id="lista-visitantes" class="mt-3 space-y-2 text-sm"></div>
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
    finalOptions.headers = {
        'Content-Type': 'application/json',
        ...(options.headers || {})
    };
    if (finalOptions.body && typeof finalOptions.body !== 'string') {
        finalOptions.body = JSON.stringify({
            ...finalOptions.body,
            initData: tg.initData
        });
    }
    const joiner = url.includes('?') ? '&' : '?';
    const response = await fetch(url + joiner + 'initData=' + encodeURIComponent(tg.initData), finalOptions);
    const json = await response.json();
    if (!json.ok) {
        throw new Error(json.erro || 'Não foi possível concluir esta ação agora. Tente novamente em instantes.');
    }
    return json;
}

function renderLista(id, itens, vazio, mapper = null) {
    const root = document.getElementById(id);
    root.innerHTML = '';
    if (!itens || itens.length === 0) {
        root.innerHTML = `<div class="rounded-xl border border-dashed border-gray-300 px-3 py-3 text-gray-500">${esc(vazio)}</div>`;
        return;
    }

    itens.forEach(item => {
        const data = mapper ? mapper(item) : item;
        const div = document.createElement('div');
        div.className = 'rounded-xl border border-gray-200 bg-white/70 px-3 py-2';
        div.innerHTML = `<div class="font-medium">${esc(data.nome || 'Item')}</div>
            ${data.linha ? `<div class="text-xs text-gray-500 mt-1">${esc(data.linha)}</div>` : ''}`;
        root.appendChild(div);
    });
}

function renderPresencas() {
    const root = document.getElementById('lista-presencas');
    root.innerHTML = '';
    const presencas = dashboard?.presencas || [];
    if (presencas.length === 0) {
        root.innerHTML = '<div class="rounded-xl border border-dashed border-gray-300 px-3 py-3 text-gray-500">Nenhuma nominata prevista para esta sessão.</div>';
        return;
    }

    presencas.forEach(item => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = `w-full rounded-xl border px-3 py-3 text-left ${item.presente ? 'border-emerald-300 bg-emerald-50' : 'border-gray-200 bg-white/70'}`;
        button.innerHTML = `<div class="flex items-start justify-between gap-3">
            <div>
                <div class="font-medium">${esc(item.nome)}</div>
                <div class="text-xs text-gray-500 mt-1">CIM ${esc(item.cim || '-')} · Grau ${esc(item.grau || '-')}</div>
            </div>
            <div class="rounded-full px-2 py-1 text-xs font-medium ${item.presente ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-700'}">${item.presente ? 'Presente' : 'Ausente'}</div>
        </div>`;
        button.addEventListener('click', async () => {
            try {
                await api('/api/miniapp/chanceler/presenca', {
                    method: 'POST',
                    body: {
                        sessao_id: sessaoAtualId,
                        obreiro_id: item.id,
                        presente: !item.presente
                    }
                });
                await carregar(sessaoAtualId);
            } catch (err) {
                tg.showAlert(err.message);
            }
        });
        root.appendChild(button);
    });
}

function render() {
    if (!dashboard) return;
    document.getElementById('loading').classList.add('hidden');
    document.getElementById('conteudo').classList.remove('hidden');

    const sessao = dashboard.sessao_foco;
    document.getElementById('sessao-titulo').textContent = sessao ? (sessao.titulo || 'Sessão') : 'Sem sessão em foco';
    document.getElementById('sessao-meta').textContent = sessao ? `${sessao.data_hora_inicio || ''} · ${sessao.status || ''}` : 'Sem dados';
    document.getElementById('meta-confirmados').textContent = dashboard.confirmados?.length || 0;
    document.getElementById('meta-presentes').textContent = (dashboard.presencas || []).filter(item => item.presente).length;
    document.getElementById('meta-visitantes').textContent = dashboard.visitantes?.length || 0;
    document.getElementById('meta-agape').textContent = (dashboard.confirmados || []).filter(item => item.participara_agape).length;

    const select = document.getElementById('sessao-select');
    select.innerHTML = '';
    (dashboard.sessoes || []).forEach(item => {
        const option = document.createElement('option');
        option.value = item.id;
        option.textContent = `${item.titulo || 'Sessão'} · ${item.status || ''}`;
        if (sessao && item.id === sessao.id) option.selected = true;
        select.appendChild(option);
    });

    const certificado = document.getElementById('link-certificado');
    if (sessao) {
        const params = new URLSearchParams({
            data_sessao: (sessao.data_hora_inicio || '').slice(0, 10),
            tipo_sessao: sessao.tipo_sessao || 'Ordinária',
            grau_sessao: sessao.grau_sessao || 'Mestre Maçom',
            init_data: tg.initData
        });
        certificado.href = '/chancelaria/certificado?' + params.toString();
    }

    renderPresencas();
    renderLista('lista-confirmados', dashboard.confirmados, 'Sem confirmados nesta sessão.', item => ({
        nome: item.nome,
        linha: item.participara_agape ? 'Confirmado com ágape' : 'Confirmado sem ágape'
    }));
    renderLista('lista-visitantes', dashboard.visitantes, 'Sem visitantes resumidos nesta sessão.', item => ({
        nome: item.nome,
        linha: item.linha_resumida
    }));
}

async function carregar(sessaoId = null) {
    try {
        const query = sessaoId ? ('?sessao_id=' + encodeURIComponent(sessaoId)) : '';
        const json = await api('/api/miniapp/chanceler/dashboard' + query, { method: 'GET' });
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

document.getElementById('sessao-select').addEventListener('change', (event) => {
    carregar(event.target.value);
});

carregar();
</script>
</body>
</html>


