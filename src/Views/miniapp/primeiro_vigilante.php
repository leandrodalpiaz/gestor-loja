<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>1o Vigilante Mobile</title>
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
        <h1 class="text-xl font-bold">1o Vigilante</h1>
        <p class="mt-1 text-sm text-gray-500">Trilha, leitura sugerida e certificado dos Aprendizes.</p>
    </div>

    <div id="loading" class="text-sm text-gray-400">Carregando painel...</div>
    <div id="erro" class="hidden rounded-lg bg-red-50 p-3 text-sm text-red-700"></div>

    <div id="conteudo" class="hidden space-y-4">
        <div class="card rounded-2xl p-4">
            <div class="text-xs uppercase tracking-wide text-gray-400">Aprendiz em foco</div>
            <div id="aprendiz-nome" class="mt-1 text-base font-semibold"></div>
            <div id="aprendiz-meta" class="mt-1 text-sm text-gray-600"></div>
            <div class="mt-4">
                <label class="mb-1 block text-sm font-medium text-gray-700">Trocar Aprendiz</label>
                <select id="aprendiz-select" class="w-full rounded-lg border px-3 py-2 text-sm"></select>
            </div>
        </div>

        <div class="card rounded-2xl p-4">
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div class="rounded-xl bg-white/70 p-3">
                    <div class="text-gray-500">Etapa atual</div>
                    <div id="meta-etapa" class="mt-1 text-lg font-semibold"></div>
                </div>
                <div class="rounded-xl bg-white/70 p-3">
                    <div class="text-gray-500">ConclusÃ£o</div>
                    <div id="meta-percentual" class="mt-1 text-lg font-semibold"></div>
                </div>
            </div>
        </div>

        <form id="form-trilha" class="card rounded-2xl p-4 space-y-3">
            <div class="text-sm font-semibold">Atualizar trilha</div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Etapa</label>
                <select id="etapa_ordem" class="w-full rounded-lg border px-3 py-2 text-sm"></select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Status</label>
                <select id="status_trilha" class="w-full rounded-lg border px-3 py-2 text-sm">
                    <option value="nao_iniciado">nao_iniciado</option>
                    <option value="disponibilizado">disponibilizado</option>
                    <option value="aguardando_entrega">aguardando_entrega</option>
                    <option value="recebido">recebido</option>
                    <option value="revisado">revisado</option>
                    <option value="concluido">concluido</option>
                    <option value="apto_para_certificado">apto_para_certificado</option>
                    <option value="certificado_solicitado">certificado_solicitado</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Observacao do vigilante</label>
                <textarea id="observacao_vigilante" rows="4" class="w-full rounded-lg border px-3 py-2 text-sm"></textarea>
            </div>
            <button type="submit" class="w-full rounded-xl bg-emerald-700 px-4 py-3 text-sm font-medium text-white">Salvar andamento da trilha</button>
        </form>

        <form id="form-leitura" class="card rounded-2xl p-4 space-y-3">
            <div class="text-sm font-semibold">Leitura sugerida</div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Item do acervo</label>
                <select id="acervo_id" class="w-full rounded-lg border px-3 py-2 text-sm"></select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Orientacao</label>
                <textarea id="observacao_leitura" rows="4" class="w-full rounded-lg border px-3 py-2 text-sm"></textarea>
            </div>
            <button type="submit" class="w-full rounded-xl bg-slate-900 px-4 py-3 text-sm font-medium text-white">Salvar leitura sugerida</button>
        </form>

        <form id="form-certificado" class="card rounded-2xl p-4 space-y-3">
            <div class="text-sm font-semibold">Certificado</div>
            <div id="certificado-status" class="rounded-xl bg-white/70 px-3 py-3 text-sm text-slate-700"></div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Observacao da solicitacao</label>
                <textarea id="observacao_certificado" rows="4" class="w-full rounded-lg border px-3 py-2 text-sm"></textarea>
            </div>
            <button type="submit" class="w-full rounded-xl bg-amber-500 px-4 py-3 text-sm font-medium text-slate-900">Solicitar certificado</button>
        </form>

        <div class="card rounded-2xl p-4">
            <div class="text-sm font-semibold">Etapas da trilha</div>
            <div id="lista-etapas" class="mt-3 space-y-2 text-sm"></div>
        </div>

        <div class="card rounded-2xl p-4">
            <div class="text-sm font-semibold">HistÃ³rico formativo</div>
            <div id="lista-historico" class="mt-3 space-y-2 text-sm"></div>
        </div>
    </div>
</div>

<script>
const tg = window.Telegram.WebApp;
tg.ready();
tg.expand();

let dashboard = null;
let aprendizAtualId = null;

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

function renderLista(id, itens, vazio, mapper) {
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

function renderEtapas() {
    const etapas = dashboard?.aprendiz_foco?.etapas || [];
    renderLista('lista-etapas', etapas, 'Sem etapas registradas.', item => ({
        nome: `Etapa ${item.ordem} Â· ${item.titulo}`,
        linha: item.status
    }));
}

function render() {
    document.getElementById('loading').classList.add('hidden');
    document.getElementById('conteudo').classList.remove('hidden');

    const foco = dashboard.aprendiz_foco;
    const aprendiz = foco?.aprendiz;
    document.getElementById('aprendiz-nome').textContent = aprendiz?.nome || 'Sem Aprendiz';
    document.getElementById('aprendiz-meta').textContent = `CIM ${aprendiz?.cim || '-'} Â· IniciaÃ§Ã£o ${aprendiz?.data_iniciacao || '-'}`;
    document.getElementById('meta-etapa').textContent = foco?.resumo?.etapa_atual ? `${foco.resumo.etapa_atual.ordem}` : '-';
    document.getElementById('meta-percentual').textContent = `${foco?.resumo?.percentual_conclusao || 0}%`;

    const select = document.getElementById('aprendiz-select');
    select.innerHTML = '';
    (dashboard.aprendizes || []).forEach(item => {
        const option = document.createElement('option');
        option.value = item.id;
        option.textContent = `${item.nome} Â· CIM ${item.cim || '-'}`;
        if (aprendiz && item.id === aprendiz.id) option.selected = true;
        select.appendChild(option);
    });

    const acervo = document.getElementById('acervo_id');
    acervo.innerHTML = '<option value="">Sem vincular livro especifico</option>';
    (dashboard.leituras_disponiveis || []).forEach(item => {
        const option = document.createElement('option');
        option.value = item.id;
        option.textContent = `${item.titulo} - ${item.autor || ''}`;
        if ((foco?.leitura_sugerida?.acervo_id || 0) === item.id) option.selected = true;
        acervo.appendChild(option);
    });

    document.getElementById('observacao_leitura').value = foco?.leitura_sugerida?.observacao || '';
    document.getElementById('observacao_certificado').value = foco?.certificado?.observacao || '';
    document.getElementById('certificado-status').textContent = `Status atual: ${foco?.certificado?.status || 'nao_solicitado'}`;
    const etapaSelect = document.getElementById('etapa_ordem');
    etapaSelect.innerHTML = '';
    (foco?.etapas || []).forEach(item => {
        const option = document.createElement('option');
        option.value = item.ordem;
        option.textContent = `Etapa ${item.ordem} - ${item.titulo}`;
        if ((foco?.resumo?.etapa_atual?.ordem || 0) === item.ordem) option.selected = true;
        etapaSelect.appendChild(option);
    });
    document.getElementById('status_trilha').value = foco?.resumo?.etapa_atual?.status || 'nao_iniciado';
    const etapaAtual = (foco?.etapas || []).find(item => item.ordem === (foco?.resumo?.etapa_atual?.ordem || 0));
    document.getElementById('observacao_vigilante').value = etapaAtual?.observacao_vigilante || '';

    renderEtapas();
    renderLista('lista-historico', foco?.historico_formativo || [], 'Sem histÃ³rico formativo registrado.', item => ({
        nome: item.titulo || item.tipo,
        linha: `${item.momento || '-'} Â· ${item.descricao || ''}`
    }));
}

async function carregar(aprendizId = null) {
    try {
        const query = aprendizId ? ('?aprendiz_id=' + encodeURIComponent(aprendizId)) : '';
        const json = await api('/api/miniapp/primeiro-vigilante/dashboard' + query, { method: 'GET' });
        dashboard = json.dados;
        aprendizAtualId = dashboard.aprendiz_foco?.aprendiz?.id || null;
        render();
    } catch (err) {
        const erro = document.getElementById('erro');
        erro.textContent = err.message;
        erro.classList.remove('hidden');
        document.getElementById('loading').classList.add('hidden');
    }
}

document.getElementById('aprendiz-select').addEventListener('change', (event) => carregar(event.target.value));
document.getElementById('form-leitura').addEventListener('submit', async (event) => {
    event.preventDefault();
    try {
        await api('/api/miniapp/primeiro-vigilante/leitura/salvar', {
            method: 'POST',
            body: {
                aprendiz_id: aprendizAtualId,
                acervo_id: document.getElementById('acervo_id').value,
                observacao_leitura: document.getElementById('observacao_leitura').value
            }
        });
        tg.showAlert('Leitura sugerida salva com sucesso.');
        await carregar(aprendizAtualId);
    } catch (err) {
        tg.showAlert(err.message);
    }
});

document.getElementById('form-trilha').addEventListener('submit', async (event) => {
    event.preventDefault();
    try {
        await api('/api/miniapp/primeiro-vigilante/trilha/atualizar', {
            method: 'POST',
            body: {
                aprendiz_id: aprendizAtualId,
                etapa_ordem: document.getElementById('etapa_ordem').value,
                status: document.getElementById('status_trilha').value,
                observacao_vigilante: document.getElementById('observacao_vigilante').value
            }
        });
        tg.showAlert('Andamento da trilha salvo com sucesso.');
        await carregar(aprendizAtualId);
    } catch (err) {
        tg.showAlert(err.message);
    }
});

document.getElementById('form-certificado').addEventListener('submit', async (event) => {
    event.preventDefault();
    try {
        await api('/api/miniapp/primeiro-vigilante/certificado/solicitar', {
            method: 'POST',
            body: {
                aprendiz_id: aprendizAtualId,
                observacao_certificado: document.getElementById('observacao_certificado').value
            }
        });
        tg.showAlert('Solicitacao de certificado registrada.');
        await carregar(aprendizAtualId);
    } catch (err) {
        tg.showAlert(err.message);
    }
});

carregar();
</script>
</body>
</html>


