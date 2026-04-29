<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Biblioteca Mobile</title>
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
        <h1 class="text-xl font-bold">Biblioteca</h1>
        <p class="mt-1 text-sm text-gray-500">Acervo, leitura em foco, comentÃ¡rios e operaÃ§Ã£o do bibliotecÃ¡rio.</p>
    </div>

    <div id="loading" class="text-sm text-gray-400">Carregando painel...</div>
    <div id="erro" class="hidden rounded-lg bg-red-50 p-3 text-sm text-red-700"></div>

    <div id="conteudo" class="hidden space-y-4">
        <div class="card rounded-2xl p-4 space-y-3">
            <div class="text-sm font-semibold">Livro em foco</div>
            <div id="scope-toggle" class="hidden rounded-xl border border-slate-200 bg-white/70 p-1 text-sm">
                <button id="scope-minha" type="button" class="rounded-lg px-3 py-2">Minha loja</button>
                <button id="scope-rede" type="button" class="rounded-lg px-3 py-2">Rede</button>
            </div>
            <select id="acervo_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></select>
            <div id="livro-foco" class="rounded-xl bg-white/70 p-3 text-sm"></div>
            <div class="grid grid-cols-3 gap-2">
                <button id="btn-solicitar" class="rounded-xl bg-emerald-700 px-3 py-3 text-sm font-medium text-white">Solicitar</button>
                <button id="btn-gostei" class="rounded-xl bg-blue-700 px-3 py-3 text-sm font-medium text-white">Gostei</button>
                <button id="btn-nao-gostei" class="rounded-xl bg-rose-700 px-3 py-3 text-sm font-medium text-white">NÃ£o gostei</button>
            </div>
            <textarea id="novo-comentario" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="Compartilhe sua opiniao sobre a leitura..."></textarea>
            <button id="btn-comentar" class="w-full rounded-xl bg-slate-900 px-3 py-3 text-sm font-medium text-white">Publicar comentario</button>
        </div>

        <div class="card rounded-2xl p-4">
            <div class="text-sm font-semibold">Resumo do acervo</div>
            <div class="mt-3 grid grid-cols-2 gap-3 text-sm">
                <div class="rounded-xl bg-white/70 p-3">
                    <div class="text-gray-500">Titulos</div>
                    <div id="meta-titulos" class="mt-1 text-lg font-semibold"></div>
                </div>
                <div class="rounded-xl bg-white/70 p-3">
                    <div class="text-gray-500">Disponiveis</div>
                    <div id="meta-disponiveis" class="mt-1 text-lg font-semibold"></div>
                </div>
            </div>
        </div>

        <div class="card rounded-2xl p-4">
            <div class="text-sm font-semibold">Comentarios recentes</div>
            <div id="lista-comentarios" class="mt-3 space-y-2 text-sm"></div>
        </div>

        <div class="card rounded-2xl p-4">
            <div class="text-sm font-semibold">Meus emprestimos</div>
            <div id="lista-meus-emprestimos" class="mt-3 space-y-2 text-sm"></div>
        </div>

        <div class="card rounded-2xl p-4">
            <div class="text-sm font-semibold">OperaÃ§Ã£o do bibliotecÃ¡rio</div>
            <div id="lista-pendentes" class="mt-3 space-y-2 text-sm"></div>
            <div class="mt-3 grid grid-cols-2 gap-2">
                <button id="atalho-catalogo" class="rounded-xl bg-slate-900 px-3 py-3 text-sm font-medium text-white">Abrir catalogo web</button>
                <button id="atalho-gerenciar" class="rounded-xl bg-amber-700 px-3 py-3 text-sm font-medium text-white">Gerenciar emprestimos</button>
                <button id="atalho-cadastrar" class="rounded-xl bg-emerald-700 px-3 py-3 text-sm font-medium text-white">Cadastrar manual</button>
                <button id="atalho-isbn" class="rounded-xl bg-indigo-700 px-3 py-3 text-sm font-medium text-white">Cadastrar por ISBN</button>
            </div>
        </div>
    </div>
</div>

<script>
const tg = window.Telegram.WebApp;
tg.ready();
tg.expand();
let dashboard = null;
let scopeAtual = 'minha';
let lojaIdAtual = null;

function parseAcervoValue(value) {
    const raw = String(value ?? '');
    if (!raw) return { acervoId: null, lojaId: null };
    if (raw.includes(':')) {
        const [idStr, lojaStr] = raw.split(':', 2);
        const acervoId = parseInt(idStr, 10);
        const lojaId = parseInt(lojaStr, 10);
        return {
            acervoId: Number.isFinite(acervoId) ? acervoId : null,
            lojaId: Number.isFinite(lojaId) ? lojaId : null,
        };
    }
    const acervoId = parseInt(raw, 10);
    return { acervoId: Number.isFinite(acervoId) ? acervoId : null, lojaId: null };
}

function esc(v) {
    return String(v ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function abrirDestino(dest) {
    try {
        const url = new URL(dest, window.location.origin);
        url.searchParams.set('init_data', tg.initData);
        window.location.href = url.pathname + url.search;
    } catch (err) {
        tg.showAlert('NÃ£o foi possÃ­vel abrir o destino.');
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
    if (!json.ok) throw new Error(json.erro || 'NÃ£o conseguimos carregar os dados da biblioteca agora. Atualize a tela e tente novamente.');
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

    const select = document.getElementById('acervo_id');
    select.innerHTML = '';
    (dashboard.acervo || []).forEach(item => {
        const option = document.createElement('option');
        option.value = `${item.id}:${item.loja_id || 0}`;
        const prefixLoja = (scopeAtual === 'rede' && (item.numero_loja || item.loja_sigla))
            ? `${item.numero_loja || ''}${item.loja_sigla ? '-' + item.loja_sigla : ''} Â· `
            : '';
        option.textContent = `${item.titulo} Â· ${item.autor}`;
        option.textContent = prefixLoja + option.textContent;
        if (Number(dashboard.item_foco?.id || 0) === Number(item.id)) {
            option.selected = true;
        }
        select.appendChild(option);
    });

    const rede = dashboard.rede || {};
    const toggle = document.getElementById('scope-toggle');
    if (rede.habilitada) {
        toggle.classList.remove('hidden');
        scopeAtual = rede.scope || scopeAtual;
        document.getElementById('scope-minha').className = `rounded-lg px-3 py-2 ${scopeAtual === 'minha' ? 'bg-slate-900 text-white' : 'text-slate-700'}`;
        document.getElementById('scope-rede').className = `rounded-lg px-3 py-2 ${scopeAtual === 'rede' ? 'bg-slate-900 text-white' : 'text-slate-700'}`;
    } else {
        toggle.classList.add('hidden');
        scopeAtual = 'minha';
    }

    document.getElementById('meta-titulos').textContent = (dashboard.acervo || []).length;
    document.getElementById('meta-disponiveis').textContent = (dashboard.acervo || []).filter(item => item.disponivel).length;

    const foco = dashboard.item_foco || {};
    document.getElementById('livro-foco').innerHTML = `
        <div class="font-medium">${esc(foco.titulo || 'Sem livro selecionado')}</div>
        <div class="mt-1 text-xs text-gray-500">${esc(foco.autor || '')}</div>
        ${(scopeAtual === 'rede' && (foco.loja_nome || foco.numero_loja)) ? `<div class="mt-1 text-xs text-gray-500">Loja ${esc(foco.numero_loja || '')}${foco.loja_sigla ? '-' + esc(foco.loja_sigla) : ''} Â· ${esc(foco.loja_nome || '')}</div>` : ''}
        <div class="mt-2 text-xs text-gray-500">Codigo ${esc(foco.codigo_acervo || '-')} Â· ISBN ${esc(foco.isbn || '-')}</div>
        <div class="mt-2 text-sm text-gray-700">${esc(foco.resumo || 'Sem resumo informado.')}</div>
        <div class="mt-2 text-xs text-gray-500">Grau ${esc(foco.grau_recomendado || 'Livre')} Â· ${esc(foco.quantidade_disponivel || 0)} exemplar(es)</div>
        <div class="mt-2 text-xs text-gray-500">${esc(foco.total_gostei_sim || 0)} gostei Â· ${esc(foco.total_gostei_nao || 0)} nÃ£o gostei</div>
    `;
    const podeSolicitar = !!foco.id && !!foco.pode_solicitar;
    const bloquearCross = scopeAtual === 'rede' && !rede.emprestimo_cruzado;
    document.getElementById('btn-solicitar').disabled = !podeSolicitar || bloquearCross;
    document.getElementById('btn-solicitar').classList.toggle('opacity-50', !podeSolicitar || bloquearCross);

    renderLista('lista-comentarios', dashboard.comentarios, 'Nenhum comentario recente para o livro em foco.', item => `
        <div class="font-medium">${esc(item.obreiro_nome || 'IrmÃ£o')}</div>
        <div class="mt-1 text-xs text-gray-500">${esc(item.criado_em || '')}</div>
        <div class="mt-2 text-sm text-gray-700">${esc(item.comentario || '')}</div>
    `);

    renderLista('lista-meus-emprestimos', dashboard.meus_emprestimos, 'Nenhum emprestimo registrado.', item => `
        <div class="font-medium">${esc(item.titulo || 'Livro')}</div>
        <div class="mt-1 text-xs text-gray-500">Codigo ${esc(item.codigo_acervo || '-')} Â· ${esc(item.status || '-')}</div>
        <div class="mt-2 text-sm text-gray-700">Previsto para ${esc(item.data_devolucao_prevista || '-')}</div>
    `);

    renderLista('lista-pendentes', dashboard.emprestimos_pendentes, 'Nenhum emprestimo pendente ou atrasado.', item => `
        <div class="font-medium">${esc(item.titulo || 'Livro')}</div>
        <div class="mt-1 text-xs text-gray-500">${esc(item.obreiro_nome || 'Obreiro')} Â· ${esc(item.status || '-')}</div>
        <div class="mt-2 text-sm text-gray-700">Devolucao prevista ${esc(item.data_devolucao_prevista || '-')}</div>
    `);
}

async function carregar(acervoValue = null) {
    try {
        const parsed = acervoValue ? parseAcervoValue(acervoValue) : { acervoId: null, lojaId: null };
        lojaIdAtual = parsed.lojaId;
        const params = new URLSearchParams();
        if (parsed.acervoId) params.set('acervo_id', String(parsed.acervoId));
        if (scopeAtual) params.set('scope', scopeAtual);
        if (parsed.lojaId) params.set('loja_id', String(parsed.lojaId));
        const sufixo = params.toString() ? `?${params.toString()}` : '';
        const json = await api('/api/miniapp/biblioteca/dashboard' + sufixo, { method: 'GET' });
        dashboard = json.dados;
        render();
    } catch (err) {
        const erro = document.getElementById('erro');
        erro.textContent = err.message;
        erro.classList.remove('hidden');
        document.getElementById('loading').classList.add('hidden');
    }
}

async function operarLivro(url, body, sucesso) {
    if (!dashboard?.item_foco?.id) {
        tg.showAlert('Selecione um livro para operar.');
        return;
    }
    try {
        await api(url, { method: 'POST', body });
        tg.showAlert(sucesso);
        document.getElementById('novo-comentario').value = '';
        await carregar(`${dashboard?.item_foco?.id || 0}:${dashboard?.item_foco?.loja_id || 0}`);
    } catch (err) {
        tg.showAlert(err.message);
    }
}

document.getElementById('acervo_id').addEventListener('change', async (event) => {
    await carregar(event.target.value);
});
document.getElementById('scope-minha').addEventListener('click', async () => {
    scopeAtual = 'minha';
    await carregar(document.getElementById('acervo_id').value);
});
document.getElementById('scope-rede').addEventListener('click', async () => {
    scopeAtual = 'rede';
    await carregar(document.getElementById('acervo_id').value);
});
document.getElementById('atalho-catalogo').addEventListener('click', () => abrirDestino('/biblioteca'));
document.getElementById('atalho-gerenciar').addEventListener('click', () => abrirDestino('/biblioteca/emprestimos'));
document.getElementById('atalho-cadastrar').addEventListener('click', () => abrirDestino('/biblioteca/novo'));
document.getElementById('atalho-isbn').addEventListener('click', () => abrirDestino('/biblioteca/scanner'));
document.getElementById('btn-solicitar').addEventListener('click', () => operarLivro('/api/miniapp/biblioteca/solicitar', { acervo_id: dashboard?.item_foco?.id || 0, loja_id: dashboard?.item_foco?.loja_id || 0, scope: scopeAtual }, 'Emprestimo solicitado com sucesso.'));
document.getElementById('btn-gostei').addEventListener('click', () => operarLivro('/api/miniapp/biblioteca/reagir', { acervo_id: dashboard?.item_foco?.id || 0, gostei: true }, 'ReaÃ§Ã£o registrada.'));
document.getElementById('btn-nao-gostei').addEventListener('click', () => operarLivro('/api/miniapp/biblioteca/reagir', { acervo_id: dashboard?.item_foco?.id || 0, gostei: false }, 'ReaÃ§Ã£o registrada.'));
document.getElementById('btn-comentar').addEventListener('click', () => operarLivro('/api/miniapp/biblioteca/comentar', { acervo_id: dashboard?.item_foco?.id || 0, comentario: document.getElementById('novo-comentario').value }, 'Comentario publicado.'));

carregar();
</script>
</body>
</html>


