<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Secretaria Mobile</title>
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
        <h1 class="text-xl font-bold">Secretaria Mobile</h1>
        <p class="mt-1 text-sm text-gray-500">Sessões, confirmados, ágape, trabalhos, balaústres e relatório.</p>
    </div>

    <div id="loading" class="text-sm text-gray-400">Carregando painel...</div>
    <div id="erro" class="hidden rounded-lg bg-red-50 p-3 text-sm text-red-700"></div>

    <div id="conteudo" class="hidden space-y-4">
        <div class="card rounded-2xl p-4">
            <div class="text-xs uppercase tracking-wide text-gray-400">Registros</div>
            <div class="mt-3 grid grid-cols-2 gap-3 text-sm">
                <div class="rounded-xl bg-white/70 p-3"><div class="text-gray-500">Total</div><div id="cad-total" class="mt-1 text-lg font-semibold"></div></div>
                <div class="rounded-xl bg-white/70 p-3"><div class="text-gray-500">Alertas</div><div id="cad-alerta" class="mt-1 text-lg font-semibold"></div></div>
                <div class="rounded-xl bg-white/70 p-3"><div class="text-gray-500">Regulars</div><div id="cad-ativos" class="mt-1 text-lg font-semibold"></div></div>
                <div class="rounded-xl bg-white/70 p-3"><div class="text-gray-500">Com bot</div><div id="cad-bot" class="mt-1 text-lg font-semibold"></div></div>
            </div>
        </div>

        <div class="card rounded-2xl p-4">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <div class="text-xs uppercase tracking-wide text-gray-400">Sessão em foco</div>
                    <div id="sessao-titulo" class="mt-1 text-base font-semibold"></div>
                    <div id="sessao-meta" class="mt-1 text-sm text-gray-600"></div>
                </div>
                <button id="toggle-form" type="button" class="rounded-xl bg-slate-900 px-3 py-2 text-xs font-medium text-white">Nova sessão</button>
            </div>
            <div class="mt-4">
                <label class="mb-1 block text-sm font-medium text-gray-700">Trocar sessão</label>
                <select id="sessao-select" class="w-full rounded-lg border px-3 py-2 text-sm"></select>
            </div>
            <div class="mt-4 grid grid-cols-3 gap-3 text-sm">
                <div class="rounded-xl bg-white/70 p-3"><div class="text-gray-500">Confirmados</div><div id="sessao-confirmados" class="mt-1 text-lg font-semibold"></div></div>
                <div class="rounded-xl bg-white/70 p-3"><div class="text-gray-500">Ausentes</div><div id="sessao-ausentes" class="mt-1 text-lg font-semibold"></div></div>
                <div class="rounded-xl bg-white/70 p-3"><div class="text-gray-500">Ágape</div><div id="sessao-agape" class="mt-1 text-lg font-semibold"></div></div>
            </div>
            <div class="mt-4 flex flex-wrap gap-2">
                <button id="btn-publicar" type="button" class="rounded-xl bg-amber-600 px-3 py-2 text-sm font-medium text-white">Publicar</button>
                <button id="btn-cancelar" type="button" class="rounded-xl bg-rose-600 px-3 py-2 text-sm font-medium text-white">Cancelar</button>
                <button id="btn-reabrir" type="button" class="rounded-xl bg-emerald-600 px-3 py-2 text-sm font-medium text-white">Reabrir</button>
            </div>
        </div>

        <form id="form-sessao" class="card hidden rounded-2xl p-4 space-y-3">
            <div class="flex items-center justify-between gap-3">
                <div><div class="text-xs uppercase tracking-wide text-gray-400">Sessão simplificada</div><div id="form-titulo" class="mt-1 text-base font-semibold">Nova sessão</div></div>
                <button id="cancel-form" type="button" class="text-sm text-gray-500">Fechar</button>
            </div>
            <input type="hidden" id="form-sessao-id">
            <div><label class="mb-1 block text-sm font-medium text-gray-700">Título</label><input id="titulo" type="text" class="w-full rounded-lg border px-3 py-2 text-sm" required></div>
            <div><label class="mb-1 block text-sm font-medium text-gray-700">Data e hora de início</label><input id="data_hora_inicio" type="datetime-local" class="w-full rounded-lg border px-3 py-2 text-sm" required></div>
            <div><label class="mb-1 block text-sm font-medium text-gray-700">Grau</label><select id="grau_sessao" class="w-full rounded-lg border px-3 py-2 text-sm"><option value="Aprendiz">Aprendiz</option><option value="Companheiro">Companheiro</option><option value="Mestre">Mestre</option><option value="Outro">Outro</option></select></div>
            <div><label class="mb-1 block text-sm font-medium text-gray-700">Tipo principal</label><select id="tipo_sessao_principal" class="w-full rounded-lg border px-3 py-2 text-sm"><option value="economica">Econômica</option><option value="magna">Magna</option><option value="outra">Outra</option></select></div>
            <div><label class="mb-1 block text-sm font-medium text-gray-700">Subtipo</label><select id="tipo_sessao_subtipo" class="w-full rounded-lg border px-3 py-2 text-sm"><option value="economica_1">Econômica de 1 Grau</option><option value="economica_2">Econômica de 2 Grau</option><option value="economica_3">Econômica de 3 Grau</option><option value="magna_iniciacao">Magna de Iniciação</option><option value="magna_elevacao">Magna de Elevação</option><option value="magna_exaltacao">Magna de Exaltação</option><option value="magna_instalacao">Magna de Instalação</option><option value="outra">Outra</option></select></div>
            <div><label class="mb-1 block text-sm font-medium text-gray-700">Ágape</label><select id="agape_modalidade" class="w-full rounded-lg border px-3 py-2 text-sm"><option value="nao_havera">Não haverá</option><option value="gratuito">Gratuito</option><option value="pago">Pago</option></select></div>
            <div><label class="mb-1 block text-sm font-medium text-gray-700">Ordem do dia</label><textarea id="ordem_dia" rows="3" class="w-full rounded-lg border px-3 py-2 text-sm"></textarea></div>
            <button type="submit" class="w-full rounded-xl bg-slate-900 px-4 py-3 text-sm font-medium text-white">Salvar sessão</button>
        </form>

        <div class="grid gap-4 md:grid-cols-2">
            <div class="card rounded-2xl p-4"><div class="text-sm font-semibold">Confirmados</div><div id="lista-confirmados" class="mt-3 space-y-2 text-sm"></div></div>
            <div class="card rounded-2xl p-4"><div class="text-sm font-semibold">Ágape</div><div id="lista-agape" class="mt-3 space-y-2 text-sm"></div></div>
        </div>

        <div class="card rounded-2xl p-4"><div class="text-sm font-semibold">Histórico recente</div><div id="lista-historico" class="mt-3 space-y-2 text-sm"></div></div>

        <div class="grid gap-4 md:grid-cols-2">
            <div class="card rounded-2xl p-4">
                <div class="text-sm font-semibold">Trabalhos recentes</div>
                <div class="mt-3 space-y-2">
                    <input id="trabalho-titulo" type="text" class="w-full rounded-lg border px-3 py-2 text-sm" placeholder="Título do trabalho">
                    <select id="trabalho-tipo" class="w-full rounded-lg border px-3 py-2 text-sm"><option value="peca_arquitetura">Peça de arquitetura</option><option value="instrucao">Instrução</option><option value="prancha">Prancha</option></select>
                    <button id="btn-salvar-trabalho" class="w-full rounded-xl bg-blue-700 px-3 py-3 text-sm font-medium text-white">Registrar trabalho</button>
                </div>
                <div id="lista-trabalhos" class="mt-3 space-y-2 text-sm"></div>
            </div>
            <div class="card rounded-2xl p-4">
                <div class="text-sm font-semibold">Balaustres recentes</div>
                <div class="mt-3 space-y-2">
                    <input id="balaustre-numero" type="text" class="w-full rounded-lg border px-3 py-2 text-sm" placeholder="Número do balaústre">
                    <textarea id="balaustre-texto" rows="3" class="w-full rounded-lg border px-3 py-2 text-sm" placeholder="Resumo do balaustre"></textarea>
                    <button id="btn-salvar-balaustre" class="w-full rounded-xl bg-slate-900 px-3 py-3 text-sm font-medium text-white">Salvar balaustre</button>
                    <div class="grid grid-cols-3 gap-2">
                        <button id="btn-balaustre-apto" class="rounded-xl bg-amber-700 px-3 py-2 text-xs font-medium text-white">Apto</button>
                        <button id="btn-balaustre-abrir" class="rounded-xl bg-emerald-700 px-3 py-2 text-xs font-medium text-white">Abrir votação</button>
                        <button id="btn-balaustre-encerrar" class="rounded-xl bg-rose-700 px-3 py-2 text-xs font-medium text-white">Encerrar</button>
                    </div>
                </div>
                <div id="lista-balaustres" class="mt-3 space-y-2 text-sm"></div>
            </div>
        </div>

        <div class="card rounded-2xl p-4">
            <div class="text-sm font-semibold">Relatório anual</div>
            <div class="mt-3 grid grid-cols-2 gap-3 text-sm">
                <div class="rounded-xl bg-white/70 p-3"><div class="text-gray-500">Sessões</div><div id="rel-sessoes" class="mt-1 text-lg font-semibold"></div></div>
                <div class="rounded-xl bg-white/70 p-3"><div class="text-gray-500">Visitantes</div><div id="rel-visitantes" class="mt-1 text-lg font-semibold"></div></div>
                <div class="rounded-xl bg-white/70 p-3"><div class="text-gray-500">Visitas externas</div><div id="rel-visitas" class="mt-1 text-lg font-semibold"></div></div>
                <div class="rounded-xl bg-white/70 p-3"><div class="text-gray-500">Congressos/Palestras</div><div id="rel-eventos" class="mt-1 text-lg font-semibold"></div></div>
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

function esc(v) { return String(v ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); }

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
        div.innerHTML = `<div class="font-medium">${esc(item.nome || item.acao || 'Item')}</div>${item.cim ? `<div class="text-xs text-gray-500">CIM ${esc(item.cim)}</div>` : ''}${item.observacao ? `<div class="text-xs text-gray-500 mt-1">${esc(item.observacao)}</div>` : ''}${item.autor_nome ? `<div class="text-xs text-gray-500 mt-1">${esc(item.autor_nome)} · ${esc(item.created_at || '')}</div>` : ''}`;
        root.appendChild(div);
    });
}

function preencherFormulario(sessao = null) {
    document.getElementById('form-sessao-id').value = sessao?.id || '';
    document.getElementById('titulo').value = sessao?.titulo || '';
    document.getElementById('data_hora_inicio').value = sessao?.data_hora_inicio ? String(sessao.data_hora_inicio).slice(0, 16) : '';
    document.getElementById('grau_sessao').value = sessao?.grau_sessao || 'Aprendiz';
    document.getElementById('tipo_sessao_principal').value = sessao?.tipo_sessao_principal || 'economica';
    document.getElementById('tipo_sessao_subtipo').value = sessao?.tipo_sessao_subtipo || 'economica_1';
    document.getElementById('agape_modalidade').value = sessao?.agape_modalidade || 'nao_havera';
    document.getElementById('ordem_dia').value = sessao?.ordem_dia || '';
    document.getElementById('form-titulo').textContent = sessao ? 'Editar sessão' : 'Nova sessão';
    document.getElementById('form-sessao').classList.remove('hidden');
}

function render() {
    if (!dashboard) return;
    document.getElementById('loading').classList.add('hidden');
    document.getElementById('conteudo').classList.remove('hidden');
    document.getElementById('cad-total').textContent = dashboard.resumo_cadastros.total ?? 0;
    document.getElementById('cad-alerta').textContent = dashboard.resumo_cadastros.com_alerta ?? 0;
    document.getElementById('cad-ativos').textContent = dashboard.resumo_cadastros.ativos ?? 0;
    document.getElementById('cad-bot').textContent = dashboard.resumo_cadastros.com_telegram ?? 0;

    const sessao = dashboard.sessao_foco;
    document.getElementById('sessao-titulo').textContent = sessao ? (sessao.titulo || sessao.tipo_descricao || 'Sessão') : 'Sem sessão em foco';
    document.getElementById('sessao-meta').textContent = sessao ? `${sessao.data_hora_inicio || ''} · ${sessao.status || ''}` : 'Sem dados';
    document.getElementById('sessao-confirmados').textContent = sessao ? (sessao.total_confirmados ?? 0) : 0;
    document.getElementById('sessao-ausentes').textContent = sessao ? (sessao.total_ausentes ?? 0) : 0;
    document.getElementById('sessao-agape').textContent = sessao ? (sessao.total_agape ?? 0) : 0;

    const select = document.getElementById('sessao-select');
    select.innerHTML = '';
    dashboard.sessoes.forEach(item => {
        const option = document.createElement('option');
        option.value = item.id;
        option.textContent = `${item.titulo || item.tipo_descricao || 'Sessão'} · ${item.status}`;
        if (sessao && item.id === sessao.id) option.selected = true;
        select.appendChild(option);
    });

    renderLista('lista-confirmados', dashboard.confirmados, 'Sem confirmados nesta sessão.');
    renderLista('lista-agape', dashboard.participantes_agape, 'Sem participantes confirmados para o ágape.');
    renderLista('lista-historico', dashboard.historico, 'Sem histórico recente.');
    renderLista('lista-trabalhos', (dashboard.trabalhos_recentes || []).map(item => ({ nome: item.titulo || 'Trabalho', observacao: `${item.sessao_titulo || 'Sessão'} · ${item.status_envio_potencia || 'pendente'}` })), 'Sem trabalhos recentes.');
    renderLista('lista-balaustres', (dashboard.balaustres_recentes || []).map(item => ({ nome: item.numero_balaustre || 'Balaústre sem número', observacao: `${item.sessao_titulo || 'Sessão'} · ${item.status || ''}` })), 'Sem balaústres recentes.');
    document.getElementById('rel-sessoes').textContent = dashboard.relatorio_anual?.sessoes ?? 0;
    document.getElementById('rel-visitantes').textContent = dashboard.relatorio_anual?.visitantes ?? 0;
    document.getElementById('rel-visitas').textContent = dashboard.relatorio_anual?.visitas_externas ?? 0;
    document.getElementById('rel-eventos').textContent = `${dashboard.relatorio_anual?.congressos ?? 0} / ${dashboard.relatorio_anual?.palestras ?? 0}`;
}

async function carregar(sessaoId = null) {
    try {
        const query = sessaoId ? ('?sessao_id=' + encodeURIComponent(sessaoId)) : '';
        const json = await api('/api/miniapp/secretaria/dashboard' + query, { method: 'GET' });
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

async function acaoSessao(url) {
    if (!sessaoAtualId) return;
    try {
        await api(url, { method: 'POST', body: { sessao_id: sessaoAtualId } });
        tg.showAlert('Operação realizada com sucesso.');
        await carregar(sessaoAtualId);
    } catch (err) {
        tg.showAlert(err.message);
    }
}

async function acaoMiniapp(url, body, sucesso) {
    try {
        await api(url, { method: 'POST', body });
        tg.showAlert(sucesso);
        await carregar(sessaoAtualId);
    } catch (err) {
        tg.showAlert(err.message);
    }
}

document.getElementById('sessao-select').addEventListener('change', (event) => carregar(event.target.value));
document.getElementById('btn-publicar').addEventListener('click', () => acaoSessao('/api/miniapp/secretaria/sessao/publicar'));
document.getElementById('btn-cancelar').addEventListener('click', () => acaoSessao('/api/miniapp/secretaria/sessao/cancelar'));
document.getElementById('btn-reabrir').addEventListener('click', () => acaoSessao('/api/miniapp/secretaria/sessao/reabrir'));
document.getElementById('toggle-form').addEventListener('click', () => preencherFormulario());
document.getElementById('cancel-form').addEventListener('click', () => document.getElementById('form-sessao').classList.add('hidden'));

document.getElementById('form-sessao').addEventListener('submit', async (event) => {
    event.preventDefault();
    try {
        const payload = {
            sessao_id: document.getElementById('form-sessao-id').value || null,
            titulo: document.getElementById('titulo').value,
            data_hora_inicio: document.getElementById('data_hora_inicio').value,
            grau_sessao: document.getElementById('grau_sessao').value,
            tipo_sessao_principal: document.getElementById('tipo_sessao_principal').value,
            tipo_sessao_subtipo: document.getElementById('tipo_sessao_subtipo').value,
            agape_modalidade: document.getElementById('agape_modalidade').value,
            ordem_dia: document.getElementById('ordem_dia').value
        };
        const json = await api('/api/miniapp/secretaria/sessao/salvar', { method: 'POST', body: payload });
        document.getElementById('form-sessao').classList.add('hidden');
        tg.showAlert('Sessão salva com sucesso.');
        await carregar(json.sessao_id || null);
    } catch (err) {
        tg.showAlert(err.message);
    }
});

document.getElementById('btn-salvar-trabalho').addEventListener('click', () => acaoMiniapp('/api/miniapp/secretaria/trabalho/salvar', { sessao_id: sessaoAtualId, titulo: document.getElementById('trabalho-titulo').value, tipo_trabalho: document.getElementById('trabalho-tipo').value }, 'Trabalho registrado com sucesso.'));
document.getElementById('btn-salvar-balaustre').addEventListener('click', () => acaoMiniapp('/api/miniapp/secretaria/balaustre/salvar', { sessao_id: sessaoAtualId, numero_balaustre: document.getElementById('balaustre-numero').value, texto_final: document.getElementById('balaustre-texto').value }, 'Balaustre salvo com sucesso.'));
document.getElementById('btn-balaustre-apto').addEventListener('click', () => acaoMiniapp('/api/miniapp/secretaria/balaustre/apto', { balaustre_id: dashboard?.balaustre_foco?.id || 0 }, 'Balaustre marcado como apto.'));
document.getElementById('btn-balaustre-abrir').addEventListener('click', () => acaoMiniapp('/api/miniapp/secretaria/balaustre/abrir-votacao', { balaustre_id: dashboard?.balaustre_foco?.id || 0 }, 'Votação aberta com sucesso.'));
document.getElementById('btn-balaustre-encerrar').addEventListener('click', () => acaoMiniapp('/api/miniapp/secretaria/balaustre/encerrar-votacao', { balaustre_id: dashboard?.balaustre_foco?.id || 0 }, 'Votação encerrada com sucesso.'));

carregar();
</script>
</body>
</html>



