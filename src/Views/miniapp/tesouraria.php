<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Tesouraria Mobile</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <link rel="stylesheet" href="/assets/css/tailwind.generated.css">
    <style>
        body { background: var(--tg-theme-bg-color, #fff); color: var(--tg-theme-text-color, #222); }
        .card { background: var(--tg-theme-secondary-bg-color, #f8fafc); }
    </style>
</head>
<body class="min-h-screen p-4">
<div class="mx-auto max-w-lg space-y-4">
    <div>
        <h1 class="text-xl font-bold">Tesouraria</h1>
        <p class="mt-1 text-sm text-gray-500">Caixa, comprovantes, regularidade, fechamento e obrigaÃ§Ãµes.</p>
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
                    <div class="text-gray-500">SaÃ­das</div>
                    <div id="meta-saidas" class="mt-1 text-lg font-semibold"></div>
                </div>
                <div class="rounded-xl bg-white/70 p-3">
                    <div class="text-gray-500">Saldo lÃ­quido</div>
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
            <div id="lista-regularidade-alertas" class="mt-3 space-y-2 text-sm"></div>
        </div>

        <div class="card rounded-2xl p-4">
            <div class="text-sm font-semibold">Fechamento mensal</div>
            <div id="fechamento" class="mt-3 rounded-xl bg-white/70 p-3 text-sm"></div>
            <button id="btn-fechar-competencia" class="mt-3 w-full rounded-xl bg-emerald-700 px-3 py-3 text-sm font-medium text-white">Fechar competÃªncia atual</button>
        </div>

        <div class="card rounded-2xl p-4">
            <div class="text-sm font-semibold">ObrigaÃ§Ãµes em alerta</div>
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
        tg.showAlert('NÃ£o foi possÃ­vel abrir o atalho.');
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
    if (!json.ok) throw new Error(json.erro || 'NÃ£o conseguimos carregar este painel no momento. Atualize a tela e tente novamente.');
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
        <div class="mt-3 grid grid-cols-2 gap-2">
            <button class="rounded-lg bg-emerald-700 px-3 py-2 text-xs font-medium text-white" onclick="aprovarComprovante(${Number(item.id || 0)})">Aprovar</button>
            <button class="rounded-lg bg-rose-700 px-3 py-2 text-xs font-medium text-white" onclick="rejeitarComprovante(${Number(item.id || 0)})">Rejeitar</button>
        </div>
    `);

    renderLista('lista-regularidade-alertas', dashboard.regularidade_alertas || [], 'Nenhum alerta de regularidade no perÃ­odo.', item => `
        <div class="font-medium">${esc(item.obreiro_nome || 'Obreiro')}</div>
        <div class="mt-1 text-xs text-gray-500">${esc(item.status || 'irregular')}</div>
        <div class="mt-3">
            <button class="rounded-lg bg-blue-700 px-3 py-2 text-xs font-medium text-white" onclick="regularizarObreiro('${esc(item.obreiro_id || '')}')">Marcar regular</button>
        </div>
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

    renderLista('lista-sessoes', dashboard.sessoes_financeiras, 'Nenhuma sessÃ£o financeira futura.', item => `
        <div class="font-medium">${esc(item.titulo || item.descricao_tipo || 'SessÃ£o')}</div>
        <div class="mt-1 text-xs text-gray-500">${esc(item.data_hora_inicio || '')}</div>
        <div class="mt-2 text-sm text-gray-700">${esc(item.descricao_agape || '-')} Â· ${esc(item.descricao_modelo || '-')}</div>
        <div class="mt-2 text-xs text-gray-500">${esc(item.confirmados_agape || 0)} com Ã¡gape Â· estimativa ${esc(moeda(item.estimativa_arrecadacao))}</div>
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

async function aprovarComprovante(id) {
    const item = (dashboard?.comprovantes?.ultimos_pendentes || []).find(row => Number(row.id) === Number(id));
    if (!item) return;
    try {
        await api('/api/miniapp/tesouraria/comprovante/aprovar', {
            method: 'POST',
            body: {
                id: item.id,
                valor: item.valor_informado,
                mes: item.mes_ref_informado,
                ano: item.ano_ref_informado,
                rotulo_pagamento: item.rotulo_pagamento
            }
        });
        tg.showAlert('Comprovante aprovado.');
        await carregar();
    } catch (err) {
        tg.showAlert(err.message);
    }
}

async function rejeitarComprovante(id) {
    const motivo = window.prompt('Informe o motivo da rejeiÃ§Ã£o:');
    if (!motivo) return;
    try {
        await api('/api/miniapp/tesouraria/comprovante/rejeitar', {
            method: 'POST',
            body: { id, motivo }
        });
        tg.showAlert('Comprovante rejeitado.');
        await carregar();
    } catch (err) {
        tg.showAlert(err.message);
    }
}

async function regularizarObreiro(obreiroId) {
    try {
        await api('/api/miniapp/tesouraria/regularidade/definir', {
            method: 'POST',
            body: {
                obreiro_id: obreiroId,
                mes: dashboard?.mes_ref,
                ano: dashboard?.ano_ref,
                status: 'regular'
            }
        });
        tg.showAlert('Regularidade atualizada.');
        await carregar();
    } catch (err) {
        tg.showAlert(err.message);
    }
}

document.getElementById('btn-fechar-competencia').addEventListener('click', async () => {
    try {
        await api('/api/miniapp/tesouraria/fechamento/fechar', {
            method: 'POST',
            body: { mes: dashboard?.mes_ref, ano: dashboard?.ano_ref }
        });
        tg.showAlert('CompetÃªncia fechada com sucesso.');
        await carregar();
    } catch (err) {
        tg.showAlert(err.message);
    }
});

carregar();
</script>
</body>
</html>


