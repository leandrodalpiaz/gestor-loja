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
</head>
<body class="bg-gray-50 min-h-screen text-gray-800">
    <div class="max-w-6xl mx-auto px-4 py-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Regularidade de Obreiros</h1>
            <a href="/dashboard" class="text-sm text-blue-700 hover:underline">← Voltar</a>
        </div>

        <!-- Filtros -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mês</label>
                    <select id="filter-mes" class="w-full border border-gray-300 rounded px-3 py-2" onchange="filtrarRegularidade()">
                        <?php
                        $mesesPT = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
                        $mesAtual = (int) date('n');
                        for ($m = 1; $m <= 12; $m++) {
                            $selected = ($m === $mesAtual) ? 'selected' : '';
                            echo "<option value=\"$m\" $selected>{$mesesPT[$m - 1]}</option>";
                        }
                        ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ano</label>
                    <select id="filter-ano" class="w-full border border-gray-300 rounded px-3 py-2" onchange="filtrarRegularidade()">
                        <?php
                        $anoAtual = (int) date('Y');
                        for ($a = $anoAtual - 1; $a <= $anoAtual; $a++) {
                            $selected = ($a === $anoAtual) ? 'selected' : '';
                            echo "<option value=\"$a\" $selected>$a</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="flex gap-2">
                    <button onclick="definirTodos('regular')" class="w-full px-4 py-2 rounded bg-green-700 text-white hover:bg-green-800 font-medium text-sm">
                        Marcar Todos como Regular
                    </button>
                    <button onclick="definirTodos('irregular')" class="w-full px-4 py-2 rounded bg-red-700 text-white hover:bg-red-800 font-medium text-sm">
                        Marcar Todos como Irregular
                    </button>
                </div>
            </div>
        </div>

        <!-- Resumo -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <p class="text-sm text-green-600 font-medium">Regulares</p>
                <p class="text-3xl font-bold text-green-700" id="count-regular">0</p>
            </div>
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <p class="text-sm text-red-600 font-medium">Irregulares</p>
                <p class="text-3xl font-bold text-red-700" id="count-irregular">0</p>
            </div>
        </div>

        <!-- Tabela -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-100 border-b border-gray-200">
                        <tr>
                            <th class="text-left px-4 py-2">Obreiro</th>
                            <th class="text-left px-4 py-2">Status Atual</th>
                            <th class="text-left px-4 py-2">Observação</th>
                            <th class="text-left px-4 py-2">Ação</th>
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

    <!-- Modal de Edição -->
    <div id="modal-regularidade" class="hidden fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-md">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-bold">Definir Regularidade</h2>
            </div>
            <form id="form-regularidade" class="p-6 space-y-4">
                <input type="hidden" id="obreiro-id">
                <p class="text-gray-600" id="obreiro-nome-modal"></p>

                <div>
                    <label class="block text-sm font-medium mb-2">Status *</label>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="radio" name="status" value="regular" class="rounded" required> 
                            <span class="ml-2">Regular ✅</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="status" value="irregular" class="rounded" required>
                            <span class="ml-2">Irregular ❌</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Observação</label>
                    <textarea id="observacao" rows="3" class="w-full border border-gray-300 rounded px-3 py-2"></textarea>
                </div>

                <div class="flex gap-2">
                    <button type="button" onclick="fecharModalRegularidade()" class="flex-1 px-4 py-2 rounded bg-gray-200 text-gray-800 hover:bg-gray-300">
                        Cancelar
                    </button>
                    <button type="submit" class="flex-1 px-4 py-2 rounded bg-blue-700 text-white hover:bg-blue-800">
                        Salvar
                    </button>
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
                atualizarTabela(json.regularidade);
                atualizarResumo(json.regularidade);
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
            if (lista.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="px-4 py-4 text-center text-gray-500">Nenhum obreiro neste período</td></tr>';
                return;
            }

            tbody.innerHTML = lista.map(r => `
                <tr class="border-b border-gray-200 hover:bg-gray-50">
                    <td class="px-4 py-2 font-medium">${r.obreiro_nome}</td>
                    <td class="px-4 py-2">
                        <span class="text-xs font-semibold px-2 py-1 rounded ${
                            r.status === 'regular' 
                                ? 'bg-green-100 text-green-700' 
                                : 'bg-red-100 text-red-700'
                        }">
                            ${r.status === 'regular' ? '✅ Regular' : '❌ Irregular'}
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
                                class="text-blue-600 hover:text-blue-800 font-medium text-sm">
                            Editar
                        </button>
                    </td>
                </tr>
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
            document.getElementById('modal-regularidade').classList.remove('hidden');
        }

        function fecharModalRegularidade() {
            document.getElementById('modal-regularidade').classList.add('hidden');
        }

        async function definirTodos(status) {
            if (!confirm(`Marcar TODOS como ${status}? Esta ação não pode ser desfeita facilmente.`)) return;

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
