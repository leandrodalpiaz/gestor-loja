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
        .card { background: var(--tg-theme-secondary-bg-color, #f8fafc); }
    </style>
</head>
<body class="min-h-screen p-4">
<div class="mx-auto max-w-lg space-y-4">
    <div>
        <h1 class="text-xl font-bold">Tesouraria</h1>
        <p class="mt-1 text-sm text-gray-500">Painel consolidado com caixa, comprovantes, regularidade, fechamento, obrigacoes e sessoes.</p>
    </div>

    <div id="loading" class="text-sm text-gray-400">Carregando painel...</div>
    <div id="erro" class="hidden rounded-lg bg-red-50 p-3 text-sm text-red-700"></div>

    <div id="conteudo" class="hidden space-y-4">
        <div class="card rounded-2xl p-4">
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div class="rounded-xl bg-white/70 p-3">
                    <div class="text-gray-500">Entradas</div>
                    <div id="meta-entradas" class="mt-1 text-lg font-semibold"></div>
                </div>
                <div class="rounded-xl bg-white/70 p-3">
                    <div class="text-gray-500">Saidas</div>
                    <div id="meta-saidas" class="mt-1 text-lg font-semibold"></div>
                </div>
                <div class="rounded-xl bg-white/70 p-3">
                    <div class="text-gray-500">Saldo liquido</div>
                    <div id="meta-saldo" class="mt-1 text-lg font-semibold"></div>
                </div>
                <div class="rounded-xl bg-white/70 p-3">
                    <div class="text-gray-500">PIX pendentes</div>
                    <div id="meta-pix" class="mt-1 text-lg font-semibold"></div>
                </div>
            </div>
        </div>

        <div class="card rounded-2xl p-4">
            <div class="text-sm font-semibold">Atalhos do cargo</div>
            <div id="atalhos" class="mt-3 grid grid-cols-2 gap-2"></div>
        </div>

        <div class="card rounded-2xl p-4">
            <div class="text-sm font-semibold">Comprovantes pendentes</div>
            <div id="lista-comprovantes" class="mt-3 space-y-2 text-sm"></div>
        </div>

        <div class="card rounded-2xl p-4">
            <div class="text-sm font-semibold">Regularidade</div>
            <div class="mt-3 grid grid-cols-2 gap-3 text-sm">
                <div class="rounded-xl bg-white/70 p-3">
                    <div class="text-gray-500">Regulares</div>
                    <div id="meta-regular" class="mt-1 text-lg font-semibold"></div>
                </div>
                <div class="rounded-xl bg-white/70 p-3">
                    <div class="text-gray-500">Irregulares</div>
                    <div id="meta-irregular" class="mt-1 text-lg font-semibold"></div>
                </div>
            </div>
        </div>

        <div class="card rounded-2xl p-4">
            <div class="text-sm font-semibold">Fechamento mensal</div>
            <div id="fechamento" class="mt-3 rounded-xl bg-white/70 p-3 text-sm"></div>
        </div>

        <div class="card rounded-2xl p-4">
            <div class="text-sm font-semibold">Obrigacoes em alerta</div>
            <div id="lista-obrigacoes" class="mt-3 space-y-2 text-sm"></div>
        </div>

        <div class="card rounded-2xl p-4">
            <div class="text-sm font-semibold">Sessoes com reflexo financeiro</div>
            <div id="lista-sessoes" class="mt-3 space-y-2 text-sm"></div>
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

function moeda(v) {
    return Number(v || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

function abrirDestino(dest) {
    try {
        const url = new URL(dest, window.location.origin);
        url.searchParams.set('init_data', tg.initData);
        window.location.href = url.pathname + url.search;
    } catch (err) {
        tg.showAlert('Nao foi possivel abrir o atalho.');
    }
}

async function api(url, options = {}) {
    const finalOptions = { ...options };
    finalOptions.headers = { 'Content-Type': 'application/json', ...(options.headers || {}) };
    const joiner = url.includes('?') ? '&' : '?';
    const response = await fetch(url + joiner + 'initData=' + encodeURIComponent(tg.initData), finalOptions);
    const json = await response.json();
    if (!json.ok) throw new Error(json.erro || 'Falha ao carregar painel.');
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

    document.getElementById('meta-entradas').textContent = moeda(dashboard.caixa?.entradas);
    document.getElementById('meta-saidas').textContent = moeda(dashboard.caixa?.saidas);
    document.getElementById('meta-saldo').textContent = moeda(dashboard.caixa?.saldo_liquido);
    document.getElementById('meta-pix').textContent = dashboard.comprovantes?.pendentes || 0;
    document.getElementById('meta-regular').textContent = dashboard.regularidade?.regular || 0;
    document.getElementById('meta-irregular').textContent = dashboard.regularidade?.irregular || 0;

    const atalhos = document.getElementById('atalhos');
    atalhos.innerHTML = '';
    (dashboard.atalhos || []).forEach(item => {
        const button = document.createElement('button');
        button.className = 'rounded-xl bg-slate-900 px-3 py-3 text-sm font-medium text-white';
        button.textContent = item.label;
        button.addEventListener('click', () => abrirDestino(item.dest));
        atalhos.appendChild(button);
    });

    renderLista('lista-comprovantes', dashboard.comprovantes?.ultimos_pendentes, 'Nenhum comprovante pendente.', item => `
        <div class="font-medium">${esc(item.obreiro_nome || 'Comprovante')}</div>
        <div class="mt-1 text-xs text-gray-500">${esc(item.criado_em || '')}</div>
        <div class="mt-2 text-sm text-gray-700">${esc(moeda(item.valor_informado))}</div>
    `);

    document.getElementById('fechamento').innerHTML = `
        <div class="font-medium">Status: ${esc(dashboard.fechamento?.status || 'aberto')}</div>
        <div class="mt-1 text-xs text-gray-500">Saldo inicial ${esc(moeda(dashboard.fechamento?.saldo_inicial))}</div>
        <div class="mt-1 text-xs text-gray-500">Saldo final ${esc(moeda(dashboard.fechamento?.saldo_final))}</div>
    `;

    renderLista('lista-obrigacoes', dashboard.obrigacoes?.top_alertas, 'Nenhum obreiro em alerta financeiro.', item => `
        <div class="font-medium">${esc(item.nome || 'Obreiro')}</div>
        <div class="mt-1 text-xs text-gray-500">${esc(item.parcelas_atrasadas || 0)} parcela(s) atrasada(s)</div>
        <div class="mt-2 text-sm text-gray-700">Saldo em aberto ${esc(moeda(item.saldo_em_aberto))}</div>
    `);

    renderLista('lista-sessoes', dashboard.sessoes_financeiras, 'Nenhuma sessao financeira futura.', item => `
        <div class="font-medium">${esc(item.titulo || item.descricao_tipo || 'Sessao')}</div>
        <div class="mt-1 text-xs text-gray-500">${esc(item.data_hora_inicio || '')}</div>
        <div class="mt-2 text-sm text-gray-700">${esc(item.descricao_agape || '-')} · ${esc(item.descricao_modelo || '-')}</div>
        <div class="mt-2 text-xs text-gray-500">${esc(item.confirmados_agape || 0)} com agape · estimativa ${esc(moeda(item.estimativa_arrecadacao))}</div>
    `);
}

async function carregar() {
    try {
        const json = await api('/api/miniapp/tesouraria/dashboard', { method: 'GET' });
        dashboard = json.dados;
        render();
    } catch (err) {
        const erro = document.getElementById('erro');
        erro.textContent = err.message;
        erro.classList.remove('hidden');
        document.getElementById('loading').classList.add('hidden');
    }
}

carregar();
</script>
</body>
</html>
