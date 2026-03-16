<?php
// src/Views/tesouraria_caixa.php
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
    <title>Livro-Caixa - Tesouraria</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
</head>
<body class="bg-gray-50 min-h-screen text-gray-800">
    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Livro-Caixa</h1>
            <a href="/dashboard" class="text-sm text-blue-700 hover:underline">← Voltar</a>
        </div>

        <!-- Filtros e Ações -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mês</label>
                    <select id="filter-mes" class="w-full border border-gray-300 rounded px-3 py-2" onchange="filtrarCaixa()">
                        <?php
                        $mesAtual = (int) date('n');
                        for ($m = 1; $m <= 12; $m++) {
                            $selected = ($m === $mesAtual) ? 'selected' : '';
                            echo "<option value=\"$m\" $selected>" . strftime('%B', mktime(0, 0, 0, $m)) . "</option>";
                        }
                        ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ano</label>
                    <select id="filter-ano" class="w-full border border-gray-300 rounded px-3 py-2" onchange="filtrarCaixa()">
                        <?php
                        $anoAtual = (int) date('Y');
                        for ($a = $anoAtual - 2; $a <= $anoAtual; $a++) {
                            $selected = ($a === $anoAtual) ? 'selected' : '';
                            echo "<option value=\"$a\" $selected>$a</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="md:col-span-2 flex gap-2">
                    <button onclick="abrirModalEntrada()" class="flex-1 px-4 py-2 rounded bg-green-700 text-white hover:bg-green-800 text-sm font-medium">
                        ➕ Nova Entrada
                    </button>
                    <button onclick="abrirModalSaida()" class="flex-1 px-4 py-2 rounded bg-red-700 text-white hover:bg-red-800 text-sm font-medium">
                        ➖ Nova Saída
                    </button>
                </div>
            </div>
        </div>

        <!-- Resumo -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <p class="text-sm text-green-600 font-medium">Total Entradas</p>
                <p class="text-2xl font-bold text-green-700" id="total-entradas">R$ 0,00</p>
            </div>
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <p class="text-sm text-red-600 font-medium">Total Saídas</p>
                <p class="text-2xl font-bold text-red-700" id="total-saidas">R$ 0,00</p>
            </div>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <p class="text-sm text-blue-600 font-medium">Saldo Líquido</p>
                <p class="text-2xl font-bold text-blue-700" id="saldo-liquido">R$ 0,00</p>
            </div>
        </div>

        <!-- Gráfico -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
            <h2 class="font-semibold mb-4">Comparação Entrada vs Saída</h2>
            <canvas id="chartCaixa" height="80"></canvas>
        </div>

        <!-- Tabela de Lançamentos -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-4 border-b border-gray-200 bg-gray-50">
                <h2 class="font-semibold">Lançamentos do Período</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-100 border-b border-gray-200">
                        <tr>
                            <th class="text-left px-4 py-2">Data</th>
                            <th class="text-left px-4 py-2">Tipo</th>
                            <th class="text-left px-4 py-2">Categoria</th>
                            <th class="text-left px-4 py-2">Descrição</th>
                            <th class="text-left px-4 py-2">Obreiro</th>
                            <th class="text-right px-4 py-2">Valor</th>
                            <th class="text-center px-4 py-2">Ação</th>
                        </tr>
                    </thead>
                    <tbody id="lancamentos-table">
                        <tr>
                            <td colspan="7" class="px-4 py-4 text-center text-gray-500">Carregando...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Nova Entrada/Saída -->
    <div id="modal-lancamento" class="hidden fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-lg">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-bold" id="modal-title">Nova Entrada</h2>
            </div>
            <form id="form-lancamento" class="p-6 space-y-4">
                <input type="hidden" id="tipo-lancamento" value="entrada">

                <div>
                    <label class="block text-sm font-medium mb-1">Categoria *</label>
                    <select id="categoria_id" class="w-full border border-gray-300 rounded px-3 py-2" required>
                        <option value="">Selecione uma categoria</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Valor *</label>
                    <input type="number" id="valor" step="0.01" min="0" class="w-full border border-gray-300 rounded px-3 py-2" required>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Data *</label>
                    <input type="date" id="data_lancamento" class="w-full border border-gray-300 rounded px-3 py-2" required>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Descrição</label>
                    <textarea id="descricao" rows="3" class="w-full border border-gray-300 rounded px-3 py-2"></textarea>
                </div>

                <div class="flex gap-2">
                    <button type="button" onclick="fecharModalLancamento()" class="flex-1 px-4 py-2 rounded bg-gray-200 text-gray-800 hover:bg-gray-300">
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
        const mesAtual = new Date().getMonth() + 1;
        const anoAtual = new Date().getFullYear();

        document.getElementById('data_lancamento').valueAsDate = new Date();

        async function abrirModalEntrada() {
            document.getElementById('tipo-lancamento').value = 'entrada';
            document.getElementById('modal-title').textContent = 'Nova Entrada';
            await carregarCategorias('entrada');
            document.getElementById('modal-lancamento').classList.remove('hidden');
        }

        async function abrirModalSaida() {
            document.getElementById('tipo-lancamento').value = 'saida';
            document.getElementById('modal-title').textContent = 'Nova Saída';
            await carregarCategorias('saida');
            document.getElementById('modal-lancamento').classList.remove('hidden');
        }

        function fecharModalLancamento() {
            document.getElementById('modal-lancamento').classList.add('hidden');
            document.getElementById('form-lancamento').reset();
        }

        async function carregarCategorias(tipo) {
            try {
                const res = await fetch(`/api/tesouraria/categorias?tipo=${tipo}`);
                const json = await res.json();
                const select = document.getElementById('categoria_id');
                select.innerHTML = '<option value="">Selecione uma categoria</option>';
                json.categorias.forEach(cat => {
                    select.innerHTML += `<option value="${cat.id}">${cat.nome}</option>`;
                });
            } catch (err) {
                console.error('Erro ao carregar categorias:', err);
            }
        }

        async function filtrarCaixa() {
            const mes = document.getElementById('filter-mes').value;
            const ano = document.getElementById('filter-ano').value;
            try {
                const res = await fetch(`/api/tesouraria/caixa?mes=${mes}&ano=${ano}`);
                const json = await res.json();
                atualizarTabelaCaixa(json.lancamentos, json.totais);
                atualizarGrafico(json.totais);
            } catch (err) {
                console.error('Erro ao carregar caixa:', err);
            }
        }

        function atualizarTabelaCaixa(lancamentos, totais) {
            const tbody = document.getElementById('lancamentos-table');
            if (lancamentos.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="px-4 py-4 text-center text-gray-500">Nenhum lançamento neste período</td></tr>';
                return;
            }

            tbody.innerHTML = lancamentos.map(l => `
                <tr class="border-b border-gray-200 hover:bg-gray-50">
                    <td class="px-4 py-2">${new Date(l.data_lancamento).toLocaleDateString('pt-BR')}</td>
                    <td class="px-4 py-2">
                        <span class="text-xs font-semibold px-2 py-1 rounded ${
                            l.tipo === 'entrada' 
                                ? 'bg-green-100 text-green-700' 
                                : 'bg-red-100 text-red-700'
                        }">
                            ${l.tipo === 'entrada' ? 'Entrada' : 'Saída'}
                        </span>
                    </td>
                    <td class="px-4 py-2">${l.categoria_nome}</td>
                    <td class="px-4 py-2 text-gray-600">${l.descricao || '-'}</td>
                    <td class="px-4 py-2 text-gray-600">${l.obreiro_nome || '-'}</td>
                    <td class="px-4 py-2 text-right font-semibold ${
                        l.tipo === 'entrada' ? 'text-green-700' : 'text-red-700'
                    }">
                        ${l.tipo === 'entrada' ? '+' : '-'} R$ ${parseFloat(l.valor).toFixed(2)}
                    </td>
                    <td class="px-4 py-2 text-center">
                        <button onclick="deletarLancamento(${l.id})" class="text-red-600 hover:text-red-800 text-xs">
                            Excluir
                        </button>
                    </td>
                </tr>
            `).join('');

            document.getElementById('total-entradas').textContent = `R$ ${totais.entrada.toFixed(2)}`;
            document.getElementById('total-saidas').textContent = `R$ ${totais.saida.toFixed(2)}`;
            document.getElementById('saldo-liquido').textContent = `R$ ${(totais.entrada - totais.saida).toFixed(2)}`;
        }

        function atualizarGrafico(totais) {
            const ctx = document.getElementById('chartCaixa').getContext('2d');
            if (window.chartCaixa) {
                window.chartCaixa.destroy();
            }
            window.chartCaixa = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Entrada', 'Saída'],
                    datasets: [{
                        label: 'Valor (R$)',
                        data: [totais.entrada, totais.saida],
                        backgroundColor: ['#22c55e', '#ef4444'],
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true } }
                }
            });
        }

        document.getElementById('form-lancamento').addEventListener('submit', async (e) => {
            e.preventDefault();
            const mes = document.getElementById('filter-mes').value;
            const ano = document.getElementById('filter-ano').value;
            const data = {
                tipo: document.getElementById('tipo-lancamento').value,
                categoria_id: document.getElementById('categoria_id').value,
                valor: document.getElementById('valor').value,
                data_lancamento: document.getElementById('data_lancamento').value,
                descricao: document.getElementById('descricao').value,
                mes_ref: parseInt(mes),
                ano_ref: parseInt(ano)
            };

            try {
                const res = await fetch('/api/tesouraria/lancamento/criar', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const json = await res.json();
                if (json.ok) {
                    fecharModalLancamento();
                    filtrarCaixa();
                }
            } catch (err) {
                console.error('Erro ao salvar:', err);
            }
        });

        async function deletarLancamento(id) {
            if (!confirm('Tem certeza que deseja excluir este lançamento?')) return;
            try {
                const res = await fetch(`/api/tesouraria/lancamento/${id}`, { method: 'DELETE' });
                const json = await res.json();
                if (json.ok) {
                    const mes = document.getElementById('filter-mes').value;
                    const ano = document.getElementById('filter-ano').value;
                    filtrarCaixa();
                }
            } catch (err) {
                console.error('Erro ao excluir:', err);
            }
        }

        // Carrega na página
        filtrarCaixa();
    </script>
</body>
</html>
