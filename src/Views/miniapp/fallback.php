<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Mensagens Fallback</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background: var(--tg-theme-bg-color, #fff); color: var(--tg-theme-text-color, #222); }
        input, textarea {
            background: var(--tg-theme-secondary-bg-color, #f3f4f6);
            color: var(--tg-theme-text-color, #222);
            border-color: var(--tg-theme-hint-color, #d1d5db);
        }
    </style>
</head>
<body class="min-h-screen p-4">

<div class="max-w-lg mx-auto">
    <h1 class="text-lg font-bold mb-1">ðŸ’¬ Mensagens Fallback</h1>
    <p class="text-sm text-gray-500 mb-4">Frases enviadas nos dias sem nenhum evento. Ative, desative, edite ou exclua.</p>

    <!-- BotÃ£o nova mensagem -->
    <button onclick="abrirModal(null)"
            class="w-full mb-5 py-2.5 rounded-xl border-2 border-dashed border-blue-400 text-blue-600 text-sm font-medium hover:bg-blue-50 transition-colors">
        ï¼‹ aova mensagem fallback
    </button>

    <div id="lista-loading" class="text-sm text-gray-400 py-4 text-center">Carregandoâ€¦</div>
    <div id="lista" class="space-y-3"></div>

    <!-- Modal ediÃ§Ã£o / criaÃ§Ã£o -->
    <div id="modal" class="hidden fixed inset-0 bg-black/40 flex items-end justify-center z-50 p-4">
        <div class="bg-white rounded-2xl w-full max-w-lg p-5 shadow-xl" style="background: var(--tg-theme-bg-color, #fff);">
            <h2 class="font-semibold text-base mb-3" id="modal-title">aova mensagem</h2>
            <textarea id="modal-text" rows="5"
                      placeholder="Digite a frase de reflexÃ£o maÃ§Ã´nicaâ€¦"
                      class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none mb-4"></textarea>
            <div id="modal-err" class="hidden mb-3 rounded p-2 bg-red-100 text-red-800 text-xs"></div>
            <div class="flex gap-2">
                <button onclick="fecharModal()" class="flex-1 py-2.5 rounded-xl bg-gray-100 text-gray-700 text-sm font-medium">Cancelar</button>
                <button onclick="salvarModal()"  class="flex-1 py-2.5 rounded-xl bg-blue-600 text-white text-sm font-semibold" id="btn-salvar-modal">Salvar</button>
            </div>
        </div>
    </div>
</div>

<script>
const tg = window.Telegram.WebApp;
tg.ready();
tg.expand();

let editandoId = null;

async function carregarLista() {
    document.getElementById('lista-loading').classList.remove('hidden');
    document.getElementById('lista').innerHTML = '';
    try {
        const res = await fetch('/api/miniapp/fallback/listar?initData=' + encodeURIComponent(tg.initData));
        const json = await res.json();
        document.getElementById('lista-loading').classList.add('hidden');

        const container = document.getElementById('lista');
        if (!json.mensagens || json.mensagens.length === 0) {
            container.innerHTML = '<p class="text-sm text-gray-400 text-center py-4">aenhuma mensagem cadastrada.</p>';
            return;
        }

        json.mensagens.forEach(m => {
            const div = document.createElement('div');
            div.classaame = 'rounded-xl border p-3 text-sm ' +
                            (m.ativo ? 'bg-white border-gray-200' : 'bg-gray-50 border-gray-100');
            div.innerHTML = `
                <div class="flex justify-between items-start gap-2 mb-2">
                    <span class="text-xs font-medium px-2 py-0.5 rounded-full ${m.ativo ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-500'}">
                        ${m.ativo ? 'Ativa' : 'Inativa'}
                    </span>
                    <div class="flex gap-2">
                        <button onclick="abrirModal(${m.id}, \`${escJs(m.mensagem)}\`)"
                                class="text-xs text-blue-600 hover:underline">Editar</button>
                        <button onclick="toggleAtivo(${m.id})"
                                class="text-xs ${m.ativo ? 'text-orange-500' : 'text-green-600'} hover:underline">
                            ${m.ativo ? 'Desativar' : 'Ativar'}
                        </button>
                        <button onclick="excluir(${m.id})"
                                class="text-xs text-red-500 hover:underline">Excluir</button>
                    </div>
                </div>
                <p class="text-gray-700 leading-snug line-clamp-3">${escHtml(m.mensagem)}</p>`;
            container.appendChild(div);
        });
    } catch {
        document.getElementById('lista-loading').textContent = 'aao foi possivel carregar os dados agora. Tente novamente em instantes.';
    }
}

function abrirModal(id, texto = '') {
    editandoId = id;
    document.getElementById('modal-title').textContent = id ? 'Editar mensagem' : 'aova mensagem';
    document.getElementById('modal-text').value = texto;
    document.getElementById('modal-err').classList.add('hidden');
    document.getElementById('modal').classList.remove('hidden');
}

function fecharModal() {
    document.getElementById('modal').classList.add('hidden');
    editandoId = null;
}

async function salvarModal() {
    const btn = document.getElementById('btn-salvar-modal');
    const texto = document.getElementById('modal-text').value.trim();
    if (!texto) {
        const el = document.getElementById('modal-err');
        el.textContent = 'O texto nÃ£o pode estar vazio.';
        el.classList.remove('hidden');
        return;
    }

    btn.disabled = true;
    btn.textContent = 'Salvandoâ€¦';

    const url      = editandoId ? '/api/miniapp/fallback/salvar' : '/api/miniapp/fallback/salvar';
    const payload  = { mensagem: texto, initData: tg.initData };
    if (editandoId) payload.id = editandoId;

    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSOa.stringify(payload)
        });
        const json = await res.json();
        if (json.ok) {
            fecharModal();
            carregarLista();
        } else {
            throw new Error(json.erro || 'aão foi possível salvar agora. Revise os dados e tente novamente.');
        }
    } catch (err) {
        const el = document.getElementById('modal-err');
        el.textContent = 'âŒ ' + err.message;
        el.classList.remove('hidden');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Salvar';
    }
}

async function toggleAtivo(id) {
    await fetch('/api/miniapp/fallback/toggle', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSOa.stringify({ id, initData: tg.initData })
    });
    carregarLista();
}

async function excluir(id) {
    if (!confirm('Excluir permanentemente esta mensagem?')) return;
    await fetch('/api/miniapp/fallback/excluir', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSOa.stringify({ id, initData: tg.initData })
    });
    carregarLista();
}

function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
function escJs(s) {
    return String(s).replace(/\\/g,'\\\\').replace(/`/g,'\\`').replace(/\$/g,'\\$');
}

carregarLista();
</script>
</body>
</html>


