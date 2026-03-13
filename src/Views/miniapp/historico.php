<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Histórico da Ordem</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background: var(--tg-theme-bg-color, #fff); color: var(--tg-theme-text-color, #222); }
        input, textarea, select {
            background: var(--tg-theme-secondary-bg-color, #f3f4f6);
            color: var(--tg-theme-text-color, #222);
            border-color: var(--tg-theme-hint-color, #d1d5db);
        }
        .tab-btn.active { border-color: #2563eb; color: #2563eb; font-weight: 600; }
    </style>
</head>
<body class="min-h-screen p-4">

<div class="max-w-lg mx-auto">
    <h1 class="text-lg font-bold mb-1">📜 Histórico da Ordem</h1>
    <p class="text-sm text-gray-500 mb-4">Datas históricas que disparam mensagem automaticamente todo ano no dia cadastrado.</p>

    <!-- Tabs -->
    <div class="flex gap-2 mb-5 border-b border-gray-200">
        <button onclick="showTab('novo')"  class="tab-btn active pb-2 border-b-2 text-sm px-1" id="tab-novo">Novo registro</button>
        <button onclick="showTab('lista')" class="tab-btn pb-2 border-b-2 border-transparent text-sm px-1 text-gray-400" id="tab-lista">Registros cadastrados</button>
    </div>

    <!-- Aba: Novo -->
    <div id="pane-novo">
        <div id="alert-ok"  class="hidden mb-3 rounded p-3 bg-green-100 text-green-800 text-sm">✅ Registro salvo com sucesso!</div>
        <div id="alert-err" class="hidden mb-3 rounded p-3 bg-red-100 text-red-800 text-sm"></div>

        <form id="form" class="space-y-4">
            <input type="hidden" name="tipo" value="História">

            <div>
                <label class="block text-sm font-medium mb-1">Título do evento <span class="text-red-500">*</span></label>
                <input name="nome" type="text" required autocomplete="off"
                       placeholder="Ex.: Fundação da Grande Loja do RS"
                       class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium mb-1">Mês <span class="text-red-500">*</span></label>
                    <select name="mes" required class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Selecione</option>
                        <?php
                        $meses = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
                        foreach ($meses as $i => $m) {
                            $v = str_pad($i + 1, 2, '0', STR_PAD_LEFT);
                            echo "<option value=\"{$v}\">{$m}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Dia <span class="text-red-500">*</span></label>
                    <input name="dia" type="number" required min="1" max="31"
                           placeholder="Ex.: 8"
                           class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Ano do evento <span class="text-red-500">*</span></label>
                <input name="ano_ref" type="number" required min="1700" max="2100"
                       placeholder="Ex.: 1928"
                       class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <p class="text-xs text-gray-400 mt-1">Usado para calcular "há X anos". Ex.: 1928.</p>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Texto da mensagem <span class="text-red-500">*</span></label>
                <textarea name="mensagem_custom" rows="6" required
                          placeholder="Escreva aqui o texto completo que será enviado no Telegram nessa data, todo ano."
                          class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
                <p class="text-xs text-gray-400 mt-1">Use <code>&lt;b&gt;negrito&lt;/b&gt;</code> e <code>&lt;i&gt;itálico&lt;/i&gt;</code> para formatar.</p>
            </div>

            <button type="submit"
                    class="w-full py-3 rounded-xl bg-blue-600 text-white font-semibold text-sm hover:bg-blue-700 active:scale-95 transition-transform">
                Salvar Registro Histórico
            </button>
        </form>
    </div>

    <!-- Aba: Lista -->
    <div id="pane-lista" class="hidden">
        <div id="lista-loading" class="text-sm text-gray-400 py-4 text-center">Carregando…</div>
        <div id="lista-items" class="space-y-3"></div>
    </div>
</div>

<script>
const tg = window.Telegram.WebApp;
tg.ready();
tg.expand();

function showTab(tab) {
    ['novo','lista'].forEach(t => {
        document.getElementById('pane-' + t).classList.toggle('hidden', t !== tab);
        const btn = document.getElementById('tab-' + t);
        btn.classList.toggle('active',            t === tab);
        btn.classList.toggle('border-transparent', t !== tab);
        btn.classList.toggle('text-gray-400',      t !== tab);
    });
    if (tab === 'lista') loadLista();
}

async function loadLista() {
    document.getElementById('lista-loading').classList.remove('hidden');
    document.getElementById('lista-items').innerHTML = '';
    try {
        const res = await fetch('/api/miniapp/historico/listar?initData=' + encodeURIComponent(tg.initData));
        const json = await res.json();
        document.getElementById('lista-loading').classList.add('hidden');
        const container = document.getElementById('lista-items');
        if (!json.registros || json.registros.length === 0) {
            container.innerHTML = '<p class="text-sm text-gray-400 text-center py-4">Nenhum registro histórico cadastrado.</p>';
            return;
        }
        json.registros.forEach(r => {
            const div = document.createElement('div');
            div.className = 'rounded-lg border p-3 text-sm flex justify-between items-start gap-2 ' +
                            (r.ativo ? 'bg-white border-gray-200' : 'bg-gray-50 border-gray-100 opacity-60');
            div.innerHTML = `
                <div class="flex-1 min-w-0">
                    <p class="font-medium truncate">${escHtml(r.nome)}</p>
                    <p class="text-xs text-gray-400 mt-0.5">${escHtml(r.data_evento)} · ${r.ativo ? '<span class="text-green-600">Ativo</span>' : '<span class="text-gray-400">Inativo</span>'}</p>
                </div>
                <button onclick="desativar(${r.id})"
                        class="flex-shrink-0 text-xs px-2 py-1 rounded ${r.ativo ? 'bg-red-50 text-red-600 hover:bg-red-100' : 'bg-gray-100 text-gray-400 cursor-not-allowed'}"
                        ${r.ativo ? '' : 'disabled'}>
                    ${r.ativo ? 'Desativar' : 'Inativo'}
                </button>`;
            container.appendChild(div);
        });
    } catch {
        document.getElementById('lista-loading').textContent = 'Erro ao carregar registros.';
    }
}

async function desativar(id) {
    if (!confirm('Desativar este registro histórico?')) return;
    await fetch('/api/miniapp/efemeride/desativar', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ id, initData: tg.initData })
    });
    loadLista();
}

document.getElementById('form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = this.querySelector('button[type=submit]');
    btn.disabled = true;
    btn.textContent = 'Salvando…';

    const raw = Object.fromEntries(new FormData(this));
    // Monta data_evento como ANO_REF-MM-DD para que o filtro mensal/dia funcione
    raw.data_evento = `${raw.ano_ref}-${raw.mes}-${String(raw.dia).padStart(2,'0')}`;
    delete raw.mes; delete raw.dia; delete raw.ano_ref;
    raw.initData = tg.initData;

    try {
        const res = await fetch('/api/miniapp/efemeride/salvar', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(raw)
        });
        const json = await res.json();
        if (json.ok) {
            document.getElementById('alert-ok').classList.remove('hidden');
            document.getElementById('alert-err').classList.add('hidden');
            this.reset();
            setTimeout(() => tg.close(), 1800);
        } else {
            throw new Error(json.erro || 'Erro ao salvar.');
        }
    } catch (err) {
        const el = document.getElementById('alert-err');
        el.textContent = '❌ ' + err.message;
        el.classList.remove('hidden');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Salvar Registro Histórico';
    }
});

function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
</script>
</body>
</html>
