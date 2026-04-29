<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Mestre de Banquetes Mobile</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <link rel="stylesheet" href="/assets/css/tailwind.generated.css">
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
        <h1 class="text-xl font-bold">Mestre de Banquetes</h1>
        <p class="mt-1 text-sm text-gray-500">Gestão do ágape por sessão, com previsão e acompanhamento logístico.</p>
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
                    <div class="text-gray-500">Ágape</div>
                    <div id="meta-agape" class="mt-1 text-lg font-semibold"></div>
                </div>
                <div class="rounded-xl bg-white/70 p-3">
                    <div class="text-gray-500">Previsão</div>
                    <div id="meta-previsao" class="mt-1 text-lg font-semibold"></div>
                </div>
                <div class="rounded-xl bg-white/70 p-3">
                    <div class="text-gray-500">Status</div>
                    <div id="meta-status" class="mt-1 text-sm font-semibold"></div>
                </div>
            </div>
        </div>

        <form id="form-operacao" class="card rounded-2xl p-4 space-y-3">
            <div class="text-sm font-semibold">Operação do banquete</div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Status operacional</label>
                <select id="status_operacional" class="w-full rounded-lg border px-3 py-2 text-sm">
                    <option value="planejamento">Planejamento</option>
                    <option value="preparacao">Preparacao</option>
                    <option value="abastecimento">Abastecimento</option>
                    <option value="fechado">Fechado</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Previsão de participantes</label>
                <input id="previsao_participantes" type="number" min="0" class="w-full rounded-lg border px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Observacoes logisticas</label>
                <textarea id="observacoes" rows="4" class="w-full rounded-lg border px-3 py-2 text-sm"></textarea>
            </div>
            <button type="submit" class="w-full rounded-xl bg-slate-900 px-4 py-3 text-sm font-medium text-white">Salvar operação</button>
        </form>

        <div class="grid gap-4 md:grid-cols-2">
            <div class="card rounded-2xl p-4">
                <div class="text-sm font-semibold">Participantes do ágape</div>
                <div id="lista-agape" class="mt-3 space-y-2 text-sm"></div>
            </div>
            <div class="card rounded-2xl p-4">
                <div class="text-sm font-semibold">Confirmados sem ágape</div>
                <div id="lista-sem-agape" class="mt-3 space-y-2 text-sm"></div>
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
    if (!json.ok) throw new Error(json.erro || 'Não conseguimos concluir sua solicitação agora. Tente novamente em alguns minutos.');
    return json;
}

function renderLista(id, itens, vazio) {
    const root = document.getElementById(id);
    root.innerHTML = '';
    if (!itens || itens.length === 0) {
        root.innerHTML = `<div class="rounded-xl border border-dashed border-gray-300 px-3 py-3 text-gray-500">${esc(vazio)}</div>`;
        return;
    }
    itens.forEach(item => {
        const div = document.createElement('div');
        div.className = 'rounded-xl border border-gray-200 bg-white/70 px-3 py-2';
        div.innerHTML = `<div class="font-medium">${esc(item.nome || 'Item')}</div>${item.cim ? `<div class="text-xs text-gray-500 mt-1">CIM ${esc(item.cim)}</div>` : ''}`;
        root.appendChild(div);
    });
}

function render() {
    document.getElementById('loading').classList.add('hidden');
    document.getElementById('conteudo').classList.remove('hidden');
    const sessao = dashboard.sessao_foco;
    const operacao = dashboard.operacao || {};
    document.getElementById('sessao-titulo').textContent = sessao ? (sessao.titulo || 'Sessão') : 'Sem sessão em foco';
    document.getElementById('sessao-meta').textContent = sessao ? `${sessao.data_hora_inicio || ''} · ${sessao.descricao_agape || ''}` : 'Sem dados';
    document.getElementById('meta-confirmados').textContent = dashboard.confirmados?.length || 0;
    document.getElementById('meta-agape').textContent = dashboard.participantes_agape?.length || 0;
    document.getElementById('meta-previsao').textContent = operacao.previsao_participantes ?? '-';
    document.getElementById('meta-status').textContent = operacao.status_operacional || 'planejamento';

    const select = document.getElementById('sessao-select');
    select.innerHTML = '';
    (dashboard.sessoes || []).forEach(item => {
        const option = document.createElement('option');
        option.value = item.id;
        option.textContent = `${item.titulo || 'Sessão'} · ${item.status || ''}`;
        if (sessao && item.id === sessao.id) option.selected = true;
        select.appendChild(option);
    });

    document.getElementById('status_operacional').value = operacao.status_operacional || 'planejamento';
    document.getElementById('previsao_participantes').value = operacao.previsao_participantes ?? '';
    document.getElementById('observacoes').value = operacao.observacoes || '';

    renderLista('lista-agape', dashboard.participantes_agape, 'Sem participantes do ágape.');
    renderLista('lista-sem-agape', dashboard.confirmados_sem_agape, 'Sem confirmados sem ágape.');
}

async function carregar(sessaoId = null) {
    try {
        const query = sessaoId ? ('?sessao_id=' + encodeURIComponent(sessaoId)) : '';
        const json = await api('/api/miniapp/mestre-banquetes/dashboard' + query, { method: 'GET' });
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
document.getElementById('form-operacao').addEventListener('submit', async (event) => {
    event.preventDefault();
    try {
        await api('/api/miniapp/mestre-banquetes/operacao/salvar', {
            method: 'POST',
            body: {
                sessao_id: sessaoAtualId,
                status_operacional: document.getElementById('status_operacional').value,
                previsao_participantes: document.getElementById('previsao_participantes').value,
                observacoes: document.getElementById('observacoes').value
            }
        });
        tg.showAlert('Dados da operação salvos com sucesso.');
        await carregar(sessaoAtualId);
    } catch (err) {
        tg.showAlert(err.message);
    }
});

carregar();
</script>
</body>
</html>


