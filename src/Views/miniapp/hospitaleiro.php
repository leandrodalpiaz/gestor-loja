<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Hospitaleiro Mobile</title>
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
        <h1 class="text-xl font-bold">Mestre Hospitaleiro</h1>
        <p class="mt-1 text-sm text-gray-500">Ocorrencias, visitas e retornos no mobile.</p>
    </div>

    <div id="loading" class="text-sm text-gray-400">Carregando painel...</div>
    <div id="erro" class="hidden rounded-lg bg-red-50 p-3 text-sm text-red-700"></div>

    <div id="conteudo" class="hidden space-y-4">
        <div class="card rounded-2xl p-4">
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div class="rounded-xl bg-white/70 p-3">
                    <div class="text-gray-500">Abertas</div>
                    <div id="meta-abertas" class="mt-1 text-lg font-semibold"></div>
                </div>
                <div class="rounded-xl bg-white/70 p-3">
                    <div class="text-gray-500">Em acompanhamento</div>
                    <div id="meta-acompanhamento" class="mt-1 text-lg font-semibold"></div>
                </div>
                <div class="rounded-xl bg-white/70 p-3">
                    <div class="text-gray-500">Concluidas</div>
                    <div id="meta-concluidas" class="mt-1 text-lg font-semibold"></div>
                </div>
                <div class="rounded-xl bg-white/70 p-3">
                    <div class="text-gray-500">Apoio financeiro</div>
                    <div id="meta-financeiro" class="mt-1 text-lg font-semibold"></div>
                </div>
            </div>
        </div>

        <form id="form-ocorrencia" class="card rounded-2xl p-4 space-y-3">
            <div class="text-sm font-semibold">Nova ocorrencia</div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Tipo</label>
                <select id="tipo_ocorrencia" class="w-full rounded-lg border px-3 py-2 text-sm">
                    <option value="assistencia_geral">Assistencia geral</option>
                    <option value="saude">Saude</option>
                    <option value="nascimento">Nascimento</option>
                    <option value="falecimento">Falecimento</option>
                    <option value="solidariedade">Solidariedade</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Obreiro</label>
                <select id="obreiro_id" class="w-full rounded-lg border px-3 py-2 text-sm"></select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <input id="nome_familiar" type="text" placeholder="Nome do familiar" class="rounded-lg border px-3 py-2 text-sm">
                <input id="parentesco" type="text" placeholder="Parentesco" class="rounded-lg border px-3 py-2 text-sm">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <select id="prioridade" class="rounded-lg border px-3 py-2 text-sm">
                    <option value="media">Media</option>
                    <option value="baixa">Baixa</option>
                    <option value="alta">Alta</option>
                    <option value="urgente">Urgente</option>
                </select>
                <select id="encaminhar_para" class="rounded-lg border px-3 py-2 text-sm">
                    <option value="nenhum">Nenhum</option>
                    <option value="veneravel">Veneravel</option>
                    <option value="tesoureiro">Tesoureiro</option>
                    <option value="ambos">Ambos</option>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <input id="data_ocorrencia" type="date" class="rounded-lg border px-3 py-2 text-sm">
                <input id="data_proxima_acao" type="date" class="rounded-lg border px-3 py-2 text-sm">
            </div>
            <textarea id="descricao" rows="4" placeholder="Descricao da ocorrencia" class="w-full rounded-lg border px-3 py-2 text-sm"></textarea>
            <label class="flex items-center gap-2 text-sm"><input id="necessita_visita" type="checkbox"> Necessita visita</label>
            <label class="flex items-center gap-2 text-sm"><input id="necessita_apoio_financeiro" type="checkbox"> Necessita apoio financeiro</label>
            <button type="submit" class="w-full rounded-xl bg-slate-900 px-4 py-3 text-sm font-medium text-white">Registrar ocorrencia</button>
        </form>

        <div class="card rounded-2xl p-4">
            <div class="text-sm font-semibold">Pendencias de visita</div>
            <div id="lista-pendencias" class="mt-3 space-y-2 text-sm"></div>
        </div>

        <div class="card rounded-2xl p-4">
            <div class="text-sm font-semibold">Ocorrencias recentes</div>
            <div id="lista-ocorrencias" class="mt-3 space-y-2 text-sm"></div>
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
    if (!json.ok) throw new Error(json.erro || 'Falha na operacao.');
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
    document.getElementById('meta-abertas').textContent = dashboard.resumo?.abertas || 0;
    document.getElementById('meta-acompanhamento').textContent = dashboard.resumo?.em_acompanhamento || 0;
    document.getElementById('meta-concluidas').textContent = dashboard.resumo?.concluidas || 0;
    document.getElementById('meta-financeiro').textContent = dashboard.resumo?.com_apoio_financeiro || 0;

    const obreiroSelect = document.getElementById('obreiro_id');
    obreiroSelect.innerHTML = '<option value="">Nao vincular obreiro</option>';
    (dashboard.obreiros || []).forEach(item => {
        const option = document.createElement('option');
        option.value = item.id;
        option.textContent = `${item.nome} · CIM ${item.cim || '-'}`;
        obreiroSelect.appendChild(option);
    });

    renderLista('lista-pendencias', dashboard.pendencias_visita, 'Nenhuma pendencia de visita.', item => `
        <div class="font-medium">${esc(item.obreiro_nome || 'Sem obreiro')}</div>
        <div class="mt-1 text-xs text-gray-500">${esc(item.tipo_ocorrencia || '')} · ${esc(item.prioridade || '')}</div>
        <div class="mt-2 text-sm text-gray-700">${esc(item.descricao || '')}</div>
        <div class="mt-3 grid grid-cols-[1fr_140px_auto] gap-2">
            <input data-obs="${item.id}" type="text" placeholder="Observacao da visita" class="rounded-lg border px-2 py-2 text-sm">
            <input data-data="${item.id}" type="date" class="rounded-lg border px-2 py-2 text-sm">
            <button data-visita="${item.id}" class="rounded-lg bg-cobalto px-3 py-2 text-sm font-medium text-white">Registrar</button>
        </div>
    `);

    document.querySelectorAll('[data-visita]').forEach(button => {
        button.addEventListener('click', async () => {
            const id = button.getAttribute('data-visita');
            try {
                await api('/api/miniapp/hospitaleiro/visita', {
                    method: 'POST',
                    body: {
                        ocorrencia_id: id,
                        observacao_visita: document.querySelector(`[data-obs="${id}"]`).value,
                        data_proxima_acao: document.querySelector(`[data-data="${id}"]`).value
                    }
                });
                tg.showAlert('Visita registrada com sucesso.');
                await carregar();
            } catch (err) {
                tg.showAlert(err.message);
            }
        });
    });

    renderLista('lista-ocorrencias', dashboard.ocorrencias, 'Sem ocorrencias recentes.', item => `
        <div class="font-medium">${esc(item.obreiro_nome || item.nome_familiar || 'Ocorrencia')}</div>
        <div class="mt-1 text-xs text-gray-500">${esc(item.tipo_ocorrencia || '')} · ${esc(item.status || '')}</div>
        <div class="mt-2 text-sm text-gray-700">${esc(item.descricao || '')}</div>
    `);
}

async function carregar() {
    try {
        const json = await api('/api/miniapp/hospitaleiro/dashboard', { method: 'GET' });
        dashboard = json.dados;
        render();
    } catch (err) {
        const erro = document.getElementById('erro');
        erro.textContent = err.message;
        erro.classList.remove('hidden');
        document.getElementById('loading').classList.add('hidden');
    }
}

document.getElementById('form-ocorrencia').addEventListener('submit', async (event) => {
    event.preventDefault();
    try {
        await api('/api/miniapp/hospitaleiro/ocorrencias/salvar', {
            method: 'POST',
            body: {
                tipo_ocorrencia: document.getElementById('tipo_ocorrencia').value,
                obreiro_id: document.getElementById('obreiro_id').value,
                nome_familiar: document.getElementById('nome_familiar').value,
                parentesco: document.getElementById('parentesco').value,
                prioridade: document.getElementById('prioridade').value,
                encaminhar_para: document.getElementById('encaminhar_para').value,
                data_ocorrencia: document.getElementById('data_ocorrencia').value,
                data_proxima_acao: document.getElementById('data_proxima_acao').value,
                descricao: document.getElementById('descricao').value,
                necessita_visita: document.getElementById('necessita_visita').checked,
                necessita_apoio_financeiro: document.getElementById('necessita_apoio_financeiro').checked
            }
        });
        tg.showAlert('Ocorrencia registrada com sucesso.');
        event.target.reset();
        await carregar();
    } catch (err) {
        tg.showAlert(err.message);
    }
});

carregar();
</script>
</body>
</html>
