<?php
// src/Views/tesouraria_regularidade.php
if (!isset($_SESSION["usuario_logado"])) {
    header("Location: /login");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Regularidade - Tesouraria</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media (min-width: 1440px) {
            .erp-readable {
                font-size: 1.08rem;
            }
            .erp-readable .text-xs,
            .erp-readable .text-\[11px\] {
                font-size: 0.92rem !important;
                line-height: 1.4rem !important;
            }
            .erp-readable .text-sm {
                font-size: 1.03rem !important;
                line-height: 1.58rem !important;
            }
        }
    </style>
</head>
<body class="erp-readable bg-gray-50 min-h-screen text-gray-800">
    <div class="max-w-6xl mx-auto px-4 py-8">
        <header class="mb-6 rounded-3xl border border-white/40 bg-[radial-gradient(circle_at_top_left,#d6b672,transparent_30%),linear-gradient(135deg,#162033,#223145)] px-6 py-7 text-white shadow-xl">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-xs uppercase tracking-[0.24em] text-amber-300">Tesouraria</p>
                    <h1 class="mt-2 text-3xl font-semibold">Regularidade de Obreiros</h1>
                    <p class="mt-2 max-w-3xl text-sm text-slate-200">Leitura clara do periodo e edicao rapida da situacao financeira de cada obreiro.</p>
                </div>
                <a href="/dashboard" class="rounded-md bg-white/10 px-3 py-2 text-sm hover:bg-white/20">Voltar ao dashboard</a>
            </div>
        </header>

        <div class="mb-6 rounded-3xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3 md:items-end">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Mes</label>
                    <select id="filter-mes" class="w-full rounded border border-gray-300 px-3 py-2" onchange="filtrarRegularidade()">
                        <?php
                        $mesesPT = ['Janeiro','Fevereiro','Marco','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
                        $mesAtual = (int) date('n');
                        for ($m = 1; $m <= 12; $m++) {
                            $selected = ($m === $mesAtual) ? 'selected' : '';
                            echo "<option value=\"$m\" $selected>{$mesesPT[$m - 1]}</option>";
                        }
                        ?>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Ano</label>
                    <select id="filter-ano" class="w-full rounded border border-gray-300 px-3 py-2" onchange="filtrarRegularidade()">
                        <?php
                        $anoAtual = (int) date('Y');
                        for ($a = $anoAtual - 1; $a <= $anoAtual; $a++) {
                            $selected = ($a === $anoAtual) ? 'selected' : '';
                            echo "<option value=\"$a\" $selected>$a</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="flex flex-col gap-2 sm:flex-row">
                    <button onclick="definirTodos('regular')" class="w-full rounded bg-green-700 px-4 py-2 text-sm font-medium text-white hover:bg-green-800">
                        Marcar todos como Regular
                    </button>
                    <button onclick="definirTodos('irregular')" class="w-full rounded bg-red-700 px-4 py-2 text-sm font-medium text-white hover:bg-red-800">
                        Marcar todos como Irregular
                    </button>
                </div>
            </div>
        </div>

        <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="rounded-lg border border-green-200 bg-green-50 p-4">
                <p class="text-sm font-medium text-green-600">Regulares</p>
                <p class="text-3xl font-bold text-green-700" id="count-regular">0</p>
            </div>
            <div class="rounded-lg border border-red-200 bg-red-50 p-4">
                <p class="text-sm font-medium text-red-600">Irregulares</p>
                <p class="text-3xl font-bold text-red-700" id="count-irregular">0</p>
            </div>
        </div>

        <div class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm">
            <div id="regularidade-cards" class="space-y-3 p-4 md:hidden">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-700">Carregando...</div>
            </div>
            <div class="hidden overflow-x-auto md:block">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-gray-200 bg-gray-100">
                        <tr>
                            <th class="px-4 py-2 text-left">Obreiro</th>
                            <th class="px-4 py-2 text-left">Status Atual</th>
                            <th class="px-4 py-2 text-left">Observacao</th>
                            <th class="px-4 py-2 text-left">Acao</th>
                        </tr>
                    </thead>
                    <tbody id="regularidade-table">
                        <tr>
                            <td colspan="4" class="px-4 py-4 text-center text-gray-500">Carregando...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="modal-regularidade" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-40 p-4">
        <div class="w-full max-w-md rounded-lg bg-white shadow-lg">
            <div class="border-b border-gray-200 p-6">
                <h2 class="text-lg font-bold">Definir Regularidade</h2>
            </div>
            <form id="form-regularidade" class="space-y-4 p-6">
                <input type="hidden" id="obreiro-id">
                <p class="text-gray-600" id="obreiro-nome-modal"></p>

                <div>
                    <label class="mb-2 block text-sm font-medium">Status *</label>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="radio" name="status" value="regular" class="rounded" required>
                            <span class="ml-2">Regular</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="status" value="irregular" class="rounded" required>
                            <span class="ml-2">Irregular</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Observacao</label>
                    <textarea id="observacao" rows="3" class="w-full rounded border border-gray-300 px-3 py-2"></textarea>
                </div>

                <div class="flex gap-2">
                    <button type="button" onclick="fecharModalRegularidade()" class="flex-1 rounded bg-gray-200 px-4 py-2 text-gray-800 hover:bg-gray-300">Cancelar</button>
                    <button type="submit" class="flex-1 rounded bg-blue-700 px-4 py-2 text-white hover:bg-blue-800">Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function escaparAtributo(valor) {
            return encodeURIComponent(String(valor ?? ''));
        }

        async function filtrarRegularidade() {
            const mes = document.getElementById('filter-mes').value;
            const ano = document.getElementById('filter-ano').value;
            try {
                const res = await fetch(`/api/tesouraria/regularidade?mes=${mes}&ano=${ano}`);
                const json = await res.json();
                atualizarTabela(json.regularidade || []);
                atualizarResumo(json.regularidade || []);
            } catch (err) {
                console.error('Erro:', err);
            }
        }

        function atualizarResumo(lista) {
            const regular = lista.filter(r => r.status === 'regular').length;
            const irregular = lista.filter(r => r.status === 'irregular').length;
            document.getElementById('count-regular').textContent = regular;
            document.getElementById('count-irregular').textContent = irregular;
        }

        function atualizarTabela(lista) {
            const tbody = document.getElementById('regularidade-table');
            const cards = document.getElementById('regularidade-cards');
            if (lista.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="px-4 py-4 text-center text-gray-500">Nenhum obreiro neste periodo</td></tr>';
                cards.innerHTML = '<div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-700">Nenhum obreiro neste periodo.</div>';
                return;
            }

            tbody.innerHTML = lista.map(r => `
                <tr class="border-b border-gray-200 hover:bg-gray-50">
                    <td class="px-4 py-2 font-medium">${r.obreiro_nome}</td>
                    <td class="px-4 py-2">
                        <span class="rounded px-2 py-1 text-xs font-semibold ${
                            r.status === 'regular'
                                ? 'bg-green-100 text-green-700'
                                : 'bg-red-100 text-red-700'
                        }">
                            ${r.status === 'regular' ? 'Regular' : 'Irregular'}
                        </span>
                    </td>
                    <td class="px-4 py-2 text-gray-600">${r.observacao || '-'}</td>
                    <td class="px-4 py-2">
                        <button 
                                data-obreiro-id="${escaparAtributo(r.obreiro_id)}"
                                data-obreiro-nome="${escaparAtributo(r.obreiro_nome)}"
                                data-status="${escaparAtributo(r.status)}"
                                data-observacao="${escaparAtributo(r.observacao || '')}"
                                onclick="abrirEditarRegularidade(this)" 
                                class="text-sm font-medium text-blue-600 hover:text-blue-800">
                            Editar
                        </button>
                    </td>
                </tr>
            `).join('');

            cards.innerHTML = lista.map(r => `
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="text-base font-semibold text-slate-900">${r.obreiro_nome}</div>
                            <div class="mt-1 text-sm text-slate-700">${r.observacao || 'Sem observacao registrada'}</div>
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-semibold ${r.status === 'regular' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}">
                            ${r.status === 'regular' ? 'Regular' : 'Irregular'}
                        </span>
                    </div>
                    <button 
                            data-obreiro-id="${escaparAtributo(r.obreiro_id)}"
                            data-obreiro-nome="${escaparAtributo(r.obreiro_nome)}"
                            data-status="${escaparAtributo(r.status)}"
                            data-observacao="${escaparAtributo(r.observacao || '')}"
                            onclick="abrirEditarRegularidade(this)" 
                            class="mt-4 w-full rounded bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">
                        Editar regularidade
                    </button>
                </article>
            `).join('');
        }

        function abrirEditarRegularidade(botao) {
            const obreiroId = decodeURIComponent(botao.dataset.obreiroId || '');
            const nome = decodeURIComponent(botao.dataset.obreiroNome || '');
            const statusAtual = decodeURIComponent(botao.dataset.status || 'irregular');
            const observacao = decodeURIComponent(botao.dataset.observacao || '');

            document.getElementById('obreiro-id').value = obreiroId;
            document.getElementById('obreiro-nome-modal').textContent = nome;
            document.getElementById('observacao').value = observacao;
            document.querySelector(`input[name="status"][value="${statusAtual}"]`).checked = true;
            const modal = document.getElementById('modal-regularidade');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function fecharModalRegularidade() {
            const modal = document.getElementById('modal-regularidade');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        async function definirTodos(status) {
            if (!confirm(`Marcar TODOS como ${status}? Esta acao nao pode ser desfeita facilmente.`)) return;

            const mes = document.getElementById('filter-mes').value;
            const ano = document.getElementById('filter-ano').value;

            try {
                const res = await fetch('/api/tesouraria/regularidade/definir-todos', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ status, mes: parseInt(mes), ano: parseInt(ano) })
                });
                const json = await res.json();
                if (json.ok) {
                    filtrarRegularidade();
                }
            } catch (err) {
                console.error('Erro:', err);
            }
        }

        document.getElementById('form-regularidade').addEventListener('submit', async (e) => {
            e.preventDefault();
            const data = {
                obreiro_id: document.getElementById('obreiro-id').value,
                mes: parseInt(document.getElementById('filter-mes').value),
                ano: parseInt(document.getElementById('filter-ano').value),
                status: document.querySelector('input[name="status"]:checked').value,
                observacao: document.getElementById('observacao').value
            };

            try {
                const res = await fetch('/api/tesouraria/regularidade/definir', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const json = await res.json();
                if (json.ok) {
                    fecharModalRegularidade();
                    filtrarRegularidade();
                }
            } catch (err) {
                console.error('Erro:', err);
            }
        });

        filtrarRegularidade();
    </script>
</body>
</html>

