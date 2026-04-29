<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Mestre de Harmonia Mobile</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <link rel="stylesheet" href="/assets/css/tailwind.generated.css">
    <style>
        body { background: var(--tg-theme-bg-color, #fff); color: var(--tg-theme-text-color, #222); }
        .card { background: var(--tg-theme-secondary-bg-color, #f8fafc); }
        select, input {
            background: var(--tg-theme-secondary-bg-color, #f3f4f6);
            color: var(--tg-theme-text-color, #222);
            border-color: var(--tg-theme-hint-color, #d1d5db);
        }
    </style>
</head>
<body class="min-h-screen p-4">
<div class="mx-auto max-w-lg space-y-4">
    <div>
        <h1 class="text-xl font-bold">Mestre de Harmonia</h1>
        <p class="mt-1 text-sm text-gray-500">Operação ritual e controle remoto de etapas.</p>
    </div>

    <div id="loading" class="text-sm text-gray-400">Carregando painel...</div>
    <div id="erro" class="hidden rounded-lg bg-red-50 p-3 text-sm text-red-700"></div>

    <div id="conteudo" class="hidden space-y-4">
        <div class="card rounded-2xl p-4 space-y-3">
            <div class="text-sm font-semibold">Sessão musical</div>
            <select id="sessao_path" class="w-full rounded-lg border px-3 py-2 text-sm"></select>
            <div class="rounded-xl bg-white/70 p-3">
                <div id="sessao-nome" class="font-semibold"></div>
                <div id="sessao-resumo" class="mt-2 text-xs text-gray-500"></div>
            </div>
        </div>

        <form id="form-operador" class="card rounded-2xl p-4 space-y-3">
            <div class="text-sm font-semibold">Irmão em exercício</div>
            <input id="operador_nome" type="text" placeholder="Nome do operador" class="w-full rounded-lg border px-3 py-2 text-sm">
            <button type="submit" class="w-full rounded-xl bg-slate-900 px-4 py-3 text-sm font-medium text-white">Salvar operador</button>
        </form>

        <div class="card rounded-2xl p-4">
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div class="rounded-xl bg-white/70 p-3">
                    <div class="text-gray-500">Status</div>
                    <div id="estado-status" class="mt-1 text-lg font-semibold"></div>
                </div>
                <div class="rounded-xl bg-white/70 p-3">
                    <div class="text-gray-500">Volume</div>
                    <div id="estado-volume" class="mt-1 text-lg font-semibold"></div>
                </div>
                <div class="rounded-xl bg-white/70 p-3">
                    <div class="text-gray-500">Auto próxima</div>
                    <div id="estado-auto" class="mt-1 text-lg font-semibold"></div>
                </div>
                <div class="rounded-xl bg-white/70 p-3">
                    <div class="text-gray-500">Atualizado em</div>
                    <div id="estado-atualizado" class="mt-1 text-sm font-semibold"></div>
                </div>
            </div>
        </div>

        <div class="card rounded-2xl p-4">
            <div class="text-sm font-semibold">Faixa atual</div>
            <div id="faixa-atual" class="mt-3 rounded-xl bg-white/70 p-3 text-sm"></div>
        </div>

        <div class="card rounded-2xl p-4">
            <div class="text-sm font-semibold">Próxima faixa</div>
            <div id="proxima-faixa" class="mt-3 rounded-xl bg-white/70 p-3 text-sm"></div>
        </div>

        <div class="card rounded-2xl p-4">
            <div class="grid grid-cols-3 gap-2">
                <button data-acao="iniciar" class="rounded-xl bg-emerald-700 px-3 py-3 text-sm font-medium text-white">Iniciar</button>
                <button data-acao="pausar" class="rounded-xl bg-slate-700 px-3 py-3 text-sm font-medium text-white">Pausar</button>
                <button data-acao="parar" class="rounded-xl bg-rose-700 px-3 py-3 text-sm font-medium text-white">Parar</button>
                <button data-acao="anterior" class="rounded-xl bg-slate-900 px-3 py-3 text-sm font-medium text-white">Anterior</button>
                <button data-acao="proxima" class="rounded-xl bg-amber-600 px-3 py-3 text-sm font-medium text-white">Próxima</button>
                <button data-acao="silencio" class="rounded-xl bg-slate-500 px-3 py-3 text-sm font-medium text-white">Silêncio</button>
                <button data-acao="volume_down" class="rounded-xl bg-slate-800 px-3 py-3 text-sm font-medium text-white">Volume -</button>
                <button data-acao="volume_up" class="rounded-xl bg-slate-800 px-3 py-3 text-sm font-medium text-white">Volume +</button>
                <button data-acao="toggle_auto" class="rounded-xl bg-indigo-700 px-3 py-3 text-sm font-medium text-white">Auto próxima</button>
            </div>
        </div>

        <div class="card rounded-2xl p-4">
            <div class="text-sm font-semibold">Roteiro da sessão</div>
            <div id="lista-faixas" class="mt-3 space-y-2 text-sm"></div>
        </div>

        <div class="card rounded-2xl p-4">
            <div class="text-sm font-semibold">Alternativas da etapa</div>
            <div id="lista-alternativas" class="mt-3 space-y-2 text-sm"></div>
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
    if (finalOptions.body && typeof finalOptions.body !== 'string') {
        finalOptions.body = JSON.stringify({ ...finalOptions.body, initData: tg.initData });
    }
    const joiner = url.includes('?') ? '&' : '?';
    const response = await fetch(url + joiner + 'initData=' + encodeURIComponent(tg.initData), finalOptions);
    const json = await response.json();
    if (!json.ok) throw new Error(json.erro || 'Não conseguimos concluir sua solicitação agora. Tente novamente em alguns minutos.');
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

function render() {
    document.getElementById('loading').classList.add('hidden');
    document.getElementById('conteudo').classList.remove('hidden');

    const select = document.getElementById('sessao_path');
    select.innerHTML = '';
    (dashboard.sessoes || []).forEach(sessao => {
        const option = document.createElement('option');
        option.value = sessao.path;
        option.textContent = `${sessao.nome} · ${sessao.total_tracks} faixa(s)`;
        if ((dashboard.sessao_foco?.path || '') === sessao.path) option.selected = true;
        select.appendChild(option);
    });

    document.getElementById('sessao-nome').textContent = dashboard.sessao_foco?.nome || 'Sem sessão';
    const summary = dashboard.sessao_foco?.summary || {};
    document.getElementById('sessao-resumo').textContent = `Principais ${summary.principal || 0} · Transição ${summary.transicao || 0} · Extras ${summary.extra || 0}`;
    document.getElementById('operador_nome').value = dashboard.estado?.operador_nome || '';
    document.getElementById('estado-status').textContent = dashboard.estado?.status_player || 'parado';
    document.getElementById('estado-volume').textContent = `${dashboard.estado?.volume_percent || 0}%`;
    document.getElementById('estado-auto').textContent = dashboard.estado?.auto_proxima ? 'Ligado' : 'Desligado';
    document.getElementById('estado-atualizado').textContent = dashboard.estado?.updated_at || '-';

    document.getElementById('faixa-atual').innerHTML = `
        <div class="font-medium">${esc(dashboard.faixa_atual?.code || '--')} · ${esc(dashboard.faixa_atual?.phase || 'Etapa')}</div>
        <div class="mt-1 text-xs text-gray-500">${esc(dashboard.faixa_atual?.title || 'Sem faixa atual')}</div>
    `;
    document.getElementById('proxima-faixa').innerHTML = dashboard.proxima_faixa ? `
        <div class="font-medium">${esc(dashboard.proxima_faixa.code || '--')} · ${esc(dashboard.proxima_faixa.phase || 'Etapa')}</div>
        <div class="mt-1 text-xs text-gray-500">${esc(dashboard.proxima_faixa.title || '')}</div>
    ` : `<div class="text-gray-500">Sem próxima faixa.</div>`;

    renderLista('lista-faixas', dashboard.faixas, 'Nenhuma faixa encontrada.', item => `
        <button data-faixa="${esc(item.id)}" class="w-full text-left">
            <div class="font-medium">${esc(item.code || '--')} · ${esc(item.phase || 'Etapa')}</div>
            <div class="mt-1 text-xs text-gray-500">${esc(item.title || '')}</div>
            <div class="mt-2 text-[11px] uppercase tracking-wide text-gray-400">${esc(item.type || 'principal')}</div>
        </button>
    `);

    document.querySelectorAll('[data-faixa]').forEach(button => {
        button.addEventListener('click', async () => {
            try {
                await enviarAcao('selecionar_faixa', { faixa_id: button.getAttribute('data-faixa') });
            } catch (err) {
                tg.showAlert(err.message);
            }
        });
    });

    renderLista('lista-alternativas', dashboard.alternativas, 'Nenhuma alternativa nesta etapa.', item => `
        <button data-alternativa="${esc(item.id)}" class="w-full text-left">
            <div class="font-medium">${esc(item.code || '--')} · ${esc(item.phase || 'Etapa')}</div>
            <div class="mt-1 text-xs text-gray-500">${esc(item.title || '')}</div>
        </button>
    `);

    document.querySelectorAll('[data-alternativa]').forEach(button => {
        button.addEventListener('click', async () => {
            try {
                await enviarAcao('selecionar_faixa', { faixa_id: button.getAttribute('data-alternativa') });
            } catch (err) {
                tg.showAlert(err.message);
            }
        });
    });
}

async function carregar(sessaoPath = null) {
    try {
        const sufixo = sessaoPath ? `?sessao_path=${encodeURIComponent(sessaoPath)}` : '';
        const json = await api('/api/miniapp/mestre-harmonia/dashboard' + sufixo, { method: 'GET' });
        dashboard = json.dados;
        render();
    } catch (err) {
        const erro = document.getElementById('erro');
        erro.textContent = err.message;
        erro.classList.remove('hidden');
        document.getElementById('loading').classList.add('hidden');
    }
}

async function enviarAcao(acao, extra = {}) {
    const json = await api('/api/miniapp/mestre-harmonia/controle', {
        method: 'POST',
        body: {
            acao,
            sessao_path: document.getElementById('sessao_path').value,
            ...extra
        }
    });
    dashboard = json.dados;
    render();
}

document.getElementById('sessao_path').addEventListener('change', async (event) => {
    try {
        await enviarAcao('selecionar_sessao', { sessao_path: event.target.value });
    } catch (err) {
        tg.showAlert(err.message);
    }
});

document.getElementById('form-operador').addEventListener('submit', async (event) => {
    event.preventDefault();
    try {
        await api('/api/miniapp/mestre-harmonia/operador', {
            method: 'POST',
            body: {
                sessao_path: document.getElementById('sessao_path').value,
                nome: document.getElementById('operador_nome').value
            }
        });
        await carregar(document.getElementById('sessao_path').value);
        tg.showAlert('Operador salvo com sucesso.');
    } catch (err) {
        tg.showAlert(err.message);
    }
});

document.querySelectorAll('[data-acao]').forEach(button => {
    button.addEventListener('click', async () => {
        try {
            await enviarAcao(button.getAttribute('data-acao'));
        } catch (err) {
            tg.showAlert(err.message);
        }
    });
});

carregar();
</script>
</body>
</html>


