<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Administracao Mobile</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background: var(--tg-theme-bg-color, #fff); color: var(--tg-theme-text-color, #222); }
        .card { background: var(--tg-theme-secondary-bg-color, #f8fafc); }
    </style>
</head>
<body class="min-h-screen p-4">
<div class="mx-auto max-w-lg space-y-4">
    <div>
        <h1 class="text-xl font-bold">Administracao</h1>
        <p class="mt-1 text-sm text-gray-500">Gestoes, cargos, parametros da Loja e auditoria critica.</p>
    </div>

    <div id="loading" class="text-sm text-gray-400">Carregando painel...</div>
    <div id="erro" class="hidden rounded-lg bg-red-50 p-3 text-sm text-red-700"></div>

    <div id="conteudo" class="hidden space-y-4">
        <div class="card rounded-2xl p-4">
            <div class="text-sm font-semibold">Gestão atual</div>
            <div id="gestao-atual" class="mt-3 rounded-xl bg-white/70 p-3 text-sm"></div>
            <div class="mt-3 grid grid-cols-2 gap-2">
                <input id="nova-gestao-titulo" type="text" class="col-span-2 rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="Título da nova gestão">
                <input id="nova-gestao-inicio" type="date" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
                <button id="btn-abrir-gestao" class="rounded-xl bg-emerald-700 px-3 py-3 text-sm font-medium text-white">Abrir gestão</button>
                <input id="encerrar-gestao-data" type="date" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
                <button id="btn-encerrar-gestao" class="rounded-xl bg-amber-700 px-3 py-3 text-sm font-medium text-white">Encerrar atual</button>
            </div>
        </div>

        <div class="card rounded-2xl p-4">
            <div class="text-sm font-semibold">Parametros da Loja</div>
            <div id="configuracao" class="mt-3 rounded-xl bg-white/70 p-3 text-sm"></div>
            <div class="mt-3 grid grid-cols-2 gap-2">
                <input id="cfg-mensalidade" type="number" step="0.01" class="rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="Mensalidade">
                <input id="cfg-pix-tipo" type="text" class="rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="Tipo PIX">
                <input id="cfg-pix-valor" type="text" class="col-span-2 rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="Chave PIX">
                <button id="btn-salvar-config" class="col-span-2 rounded-xl bg-slate-900 px-3 py-3 text-sm font-medium text-white">Salvar parametros principais</button>
            </div>
        </div>

        <div class="card rounded-2xl p-4">
            <div class="text-sm font-semibold">Cargos da gestão</div>
            <div class="mt-3 grid grid-cols-2 gap-2">
                <select id="cargo-codigo" class="rounded-lg border border-gray-300 px-3 py-2 text-sm"></select>
                <select id="cargo-obreiro" class="rounded-lg border border-gray-300 px-3 py-2 text-sm"></select>
                <input id="cargo-inicio" type="datetime-local" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
                <button id="btn-atribuir-cargo" class="rounded-xl bg-blue-700 px-3 py-3 text-sm font-medium text-white">Atribuir cargo</button>
            </div>
            <div id="lista-cargos" class="mt-3 space-y-2 text-sm"></div>
        </div>

        <div class="card rounded-2xl p-4">
            <div class="text-sm font-semibold">Gestoes cadastradas</div>
            <div id="lista-gestoes" class="mt-3 space-y-2 text-sm"></div>
        </div>

        <div class="card rounded-2xl p-4">
            <div class="text-sm font-semibold">Auditoria critica</div>
            <div id="lista-auditoria" class="mt-3 space-y-2 text-sm"></div>
        </div>

        <div class="card rounded-2xl p-4">
            <div class="grid grid-cols-2 gap-2">
                <button id="atalho-cargos" class="rounded-xl bg-slate-900 px-3 py-3 text-sm font-medium text-white">Gestão de cargos</button>
                <button id="atalho-loja" class="rounded-xl bg-blue-700 px-3 py-3 text-sm font-medium text-white">Parametros da Loja</button>
                <button id="atalho-auditoria" class="rounded-xl bg-amber-700 px-3 py-3 text-sm font-medium text-white">Auditoria</button>
                <button id="atalho-dashboard" class="rounded-xl bg-slate-700 px-3 py-3 text-sm font-medium text-white">Dashboard</button>
            </div>
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

function abrirDestino(dest) {
    try {
        const url = new URL(dest, window.location.origin);
        url.searchParams.set('init_data', tg.initData);
        window.location.href = url.pathname + url.search;
    } catch (err) {
        tg.showAlert('Não foi possível abrir o destino.');
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
    if (!json.ok) throw new Error(json.erro || 'Não conseguimos carregar o painel administrativo no momento. Atualize a tela e tente novamente.');
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

    document.getElementById('gestao-atual').innerHTML = dashboard.gestao_atual ? `
        <div class="font-medium">${esc(dashboard.gestao_atual.titulo || 'Gestão')}</div>
        <div class="mt-1 text-xs text-gray-500">${esc(dashboard.gestao_atual.status || '')} · ${esc(dashboard.gestao_atual.inicio_em || '')}</div>
    ` : `<div class="text-gray-500">Nenhuma gestão aberta.</div>`;

    document.getElementById('configuracao').innerHTML = `
        <div class="font-medium">${esc(dashboard.configuracao?.nome_loja || 'Loja')} ${esc(dashboard.configuracao?.numero_loja || '')}</div>
        <div class="mt-1 text-xs text-gray-500">${esc(dashboard.configuracao?.cidade || '')}/${esc(dashboard.configuracao?.uf || '')} · ${esc(dashboard.configuracao?.rito || '')}</div>
        <div class="mt-2 text-sm text-gray-700">Mensalidade ${esc(Number(dashboard.configuracao?.mensalidade_valor_padrao || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }))}</div>
        <div class="mt-1 text-xs text-gray-500">PIX ${esc(dashboard.configuracao?.pix_chave_tipo || '')} ${esc(dashboard.configuracao?.pix_chave_valor || '')}</div>
    `;
    document.getElementById('cfg-mensalidade').value = dashboard.configuracao?.mensalidade_valor_padrao || '';
    document.getElementById('cfg-pix-tipo').value = dashboard.configuracao?.pix_chave_tipo || '';
    document.getElementById('cfg-pix-valor').value = dashboard.configuracao?.pix_chave_valor || '';

    const cargoSelect = document.getElementById('cargo-codigo');
    cargoSelect.innerHTML = '';
    (dashboard.cargos || []).forEach(item => {
        const option = document.createElement('option');
        option.value = item.codigo;
        option.textContent = item.nome_exibicao || item.codigo;
        cargoSelect.appendChild(option);
    });

    const obreiroSelect = document.getElementById('cargo-obreiro');
    obreiroSelect.innerHTML = '';
    (dashboard.obreiros || []).forEach(item => {
        const option = document.createElement('option');
        option.value = item.id;
        option.textContent = `${item.nome} · CIM ${item.cim || '-'}`;
        obreiroSelect.appendChild(option);
    });

    renderLista('lista-cargos', dashboard.cargos, 'Nenhum cargo encontrado para a gestão atual.', item => `
        <div class="font-medium">${esc(item.nome_exibicao || item.codigo || 'Cargo')}</div>
        <div class="mt-1 text-xs text-gray-500">${esc(item.titular_nome || 'Sem titular')} · CIM ${esc(item.titular_cim || '-')}</div>
    `);

    renderLista('lista-gestoes', dashboard.gestoes, 'Nenhuma gestão cadastrada.', item => `
        <div class="font-medium">${esc(item.titulo || 'Gestão')}</div>
        <div class="mt-1 text-xs text-gray-500">${esc(item.status || '')} · inicio ${esc(item.inicio_em || '')}</div>
    `);

    renderLista('lista-auditoria', dashboard.auditoria, 'Nenhum registro critico de auditoria.', item => `
        <div class="font-medium">${esc(item.resumo || 'Registro')}</div>
        <div class="mt-1 text-xs text-gray-500">${esc(item.entidade || '')} · ${esc(item.acao || '')} · ${esc(item.created_at || '')}</div>
        <div class="mt-2 text-sm text-gray-700">${esc(item.criado_por_nome || 'Sistema')}</div>
    `);
}

async function carregar() {
    try {
        const json = await api('/api/miniapp/admin/dashboard', { method: 'GET' });
        dashboard = json.dados;
        render();
    } catch (err) {
        const erro = document.getElementById('erro');
        erro.textContent = err.message;
        erro.classList.remove('hidden');
        document.getElementById('loading').classList.add('hidden');
    }
}

document.getElementById('atalho-cargos').addEventListener('click', () => abrirDestino('/admin/cargos'));
document.getElementById('atalho-loja').addEventListener('click', () => abrirDestino('/admin/loja'));
document.getElementById('atalho-auditoria').addEventListener('click', () => abrirDestino('/admin/auditoria'));
document.getElementById('atalho-dashboard').addEventListener('click', () => abrirDestino('/dashboard'));

document.getElementById('btn-abrir-gestao').addEventListener('click', async () => {
    try {
        await api('/api/miniapp/admin/gestao/abrir', {
            method: 'POST',
            body: {
                titulo: document.getElementById('nova-gestao-titulo').value,
                inicio_em: document.getElementById('nova-gestao-inicio').value
            }
        });
        tg.showAlert('Gestão aberta com sucesso.');
        await carregar();
    } catch (err) {
        tg.showAlert(err.message);
    }
});

document.getElementById('btn-encerrar-gestao').addEventListener('click', async () => {
    if (!dashboard?.gestao_atual?.id) {
        tg.showAlert('Não existe gestão aberta.');
        return;
    }
    try {
        await api('/api/miniapp/admin/gestao/encerrar', {
            method: 'POST',
            body: {
                gestao_id: dashboard.gestao_atual.id,
                encerrada_em: document.getElementById('encerrar-gestao-data').value
            }
        });
        tg.showAlert('Gestão encerrada com sucesso.');
        await carregar();
    } catch (err) {
        tg.showAlert(err.message);
    }
});

document.getElementById('btn-atribuir-cargo').addEventListener('click', async () => {
    try {
        await api('/api/miniapp/admin/cargo/atribuir', {
            method: 'POST',
            body: {
                cargo_codigo: document.getElementById('cargo-codigo').value,
                obreiro_id: document.getElementById('cargo-obreiro').value,
                gestao_id: dashboard?.gestao_atual?.id || null,
                inicio_em: document.getElementById('cargo-inicio').value
            }
        });
        tg.showAlert('Cargo atribuido com sucesso.');
        await carregar();
    } catch (err) {
        tg.showAlert(err.message);
    }
});

document.getElementById('btn-salvar-config').addEventListener('click', async () => {
    try {
        await api('/api/miniapp/admin/configuracao/salvar', {
            method: 'POST',
            body: {
                mensalidade_valor_padrao: document.getElementById('cfg-mensalidade').value,
                pix_chave_tipo: document.getElementById('cfg-pix-tipo').value,
                pix_chave_valor: document.getElementById('cfg-pix-valor').value
            }
        });
        tg.showAlert('Parametros principais atualizados.');
        await carregar();
    } catch (err) {
        tg.showAlert(err.message);
    }
});

carregar();
</script>
</body>
</html>


