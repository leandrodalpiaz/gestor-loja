<?php
// src/Views/tesouraria_fechamento.php
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
    <title>Fechamento Mensal - Tesouraria</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen text-gray-800">
    <div class="max-w-4xl mx-auto px-4 py-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Fechamento Mensal</h1>
            <a href="/dashboard" class="text-sm text-blue-700 hover:underline">← Voltar</a>
        </div>

        <!-- Seleção de Período -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mês</label>
                    <select id="filter-mes" class="w-full border border-gray-300 rounded px-3 py-2" onchange="carregarFechamento()">
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
                    <select id="filter-ano" class="w-full border border-gray-300 rounded px-3 py-2" onchange="carregarFechamento()">
                        <?php
                        $anoAtual = (int) date('Y');
                        for ($a = $anoAtual - 1; $a <= $anoAtual; $a++) {
                            $selected = ($a === $anoAtual) ? 'selected' : '';
                            echo "<option value=\"$a\" $selected>$a</option>";
                        }
                        ?>
                    </select>
                </div>

                <div>
                    <p class="text-sm text-gray-600 mb-2">Status: <span id="status-fechamento" class="font-bold text-blue-700">Aberto</span></p>
                </div>
            </div>
        </div>

        <!-- Resumo Financeiro -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <p class="text-sm text-blue-600 font-medium mb-1">Saldo Inicial</p>
                <p class="text-2xl font-bold text-blue-700" id="saldo-inicial">R$ 0,00</p>
                <button onclick="editarSaldoInicial()" class="text-xs text-blue-600 hover:underline mt-2">Editar</button>
            </div>

            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <p class="text-sm text-green-600 font-medium mb-1">Total Entradas</p>
                <p class="text-2xl font-bold text-green-700" id="total-entradas">R$ 0,00</p>
            </div>

            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <p class="text-sm text-red-600 font-medium mb-1">Total Saídas</p>
                <p class="text-2xl font-bold text-red-700" id="total-saidas">R$ 0,00</p>
            </div>

            <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                <p class="text-sm text-purple-600 font-medium mb-1">Saldo Final</p>
                <p class="text-2xl font-bold text-purple-700" id="saldo-final">R$ 0,00</p>
            </div>
        </div>

        <!-- Abas -->
        <div class="flex gap-4 mb-6 border-b border-gray-200">
            <button type="button" data-aba="lancamentos" onclick="mudarAba('lancamentos')" class="tab-fechamento px-4 py-2 border-b-2 border-blue-700 text-blue-700 font-semibold">
                Lançamentos do Período
            </button>
            <button type="button" data-aba="auditoria" onclick="mudarAba('auditoria')" class="tab-fechamento px-4 py-2 border-b-2 border-transparent text-gray-600 hover:text-gray-800">
                Auditoria de Ajustes
            </button>
        </div>

        <!-- Aba: Lançamentos -->
        <div id="aba-lancamentos" class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
            <h2 class="font-semibold mb-4">Movimentação do Período</h2>
            <div id="lancamentos-container" class="space-y-2">
                <p class="text-gray-500">Carregando...</p>
            </div>
        </div>

        <!-- Aba: Auditoria -->
        <div id="aba-auditoria" class="hidden bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
            <h2 class="font-semibold mb-4">Histórico de Ajustes</h2>
            <div id="auditoria-container" class="space-y-2">
                <p class="text-gray-500">Carregando...</p>
            </div>
        </div>

        <!-- Ações -->
        <div class="flex gap-2">
            <button type="button" onclick="sugerirSaldoProximo()" class="px-4 py-2 rounded bg-gray-700 text-white hover:bg-gray-800">
                💡 Sugerir Saldo Próximo Mês
            </button>
            <button type="button" onclick="fecharMes()" id="btn-fechar" class="px-4 py-2 rounded bg-green-700 text-white hover:bg-green-800">
                🔒 Fechar Período
            </button>
        </div>

        <div id="feedback-fechamento" class="hidden mt-4 rounded-lg border px-4 py-3 text-sm"></div>
    </div>

    <!-- Modal de Edição de Saldo -->
    <div id="modal-saldo" class="hidden fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-md">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-bold">Editar Saldo Inicial</h2>
            </div>
            <form id="form-saldo" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Saldo Inicial (R$) *</label>
                    <input type="number" id="novo-saldo" step="0.01" min="0" class="w-full border border-gray-300 rounded px-3 py-2" required>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Justificativa da Alteração *</label>
                    <textarea id="justificativa-saldo" rows="4" class="w-full border border-gray-300 rounded px-3 py-2" required placeholder="Por que o saldo inicial está sendo alterado?"></textarea>
                </div>

                <div class="flex gap-2">
                    <button type="button" onclick="fecharModalSaldo()" class="flex-1 px-4 py-2 rounded bg-gray-200 text-gray-800 hover:bg-gray-300">
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
        let fechamentoAtual = null;
        let abaAtual = 'lancamentos';

        function formatarMoeda(valor) {
            return Number(valor || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        }

        function exibirFeedback(tipo, mensagem) {
            const box = document.getElementById('feedback-fechamento');
            box.textContent = mensagem;
            box.className = 'mt-4 rounded-lg border px-4 py-3 text-sm';

            if (tipo === 'erro') {
                box.classList.add('bg-red-50', 'border-red-200', 'text-red-700');
            } else {
                box.classList.add('bg-green-50', 'border-green-200', 'text-green-700');
            }

            box.classList.remove('hidden');
        }

        function atualizarAbas() {
            document.getElementById('aba-lancamentos').classList.toggle('hidden', abaAtual !== 'lancamentos');
            document.getElementById('aba-auditoria').classList.toggle('hidden', abaAtual !== 'auditoria');
            document.querySelectorAll('.tab-fechamento').forEach((botao) => {
                const ativa = botao.dataset.aba === abaAtual;
                botao.classList.toggle('border-blue-700', ativa);
                botao.classList.toggle('text-blue-700', ativa);
                botao.classList.toggle('font-semibold', ativa);
                botao.classList.toggle('border-transparent', !ativa);
                botao.classList.toggle('text-gray-600', !ativa);
            });
        }

        async function carregarFechamento() {
            const mes = document.getElementById('filter-mes').value;
            const ano = document.getElementById('filter-ano').value;
            try {
                const res = await fetch(`/api/tesouraria/fechamento?mes=${mes}&ano=${ano}`);
                const json = await res.json();
                if (!json.ok || !json.fechamento) {
                    exibirFeedback('erro', 'Não foi possível carregar o fechamento do período.');
                    return;
                }
                fechamentoAtual = json.fechamento;
                atualizarDisplay();
            } catch (err) {
                console.error('Erro:', err);
                exibirFeedback('erro', 'Erro ao carregar fechamento mensal.');
            }
        }

        function atualizarDisplay() {
            if (!fechamentoAtual) return;

            document.getElementById('saldo-inicial').textContent = formatarMoeda(fechamentoAtual.saldo_inicial);
            document.getElementById('total-entradas').textContent = formatarMoeda(fechamentoAtual.total_entradas);
            document.getElementById('total-saidas').textContent = formatarMoeda(fechamentoAtual.total_saidas);
            document.getElementById('saldo-final').textContent = formatarMoeda(fechamentoAtual.saldo_final);
            document.getElementById('status-fechamento').textContent = fechamentoAtual.status === 'fechado' ? '🔒 Fechado' : 'Aberto';

            const botaoFechar = document.getElementById('btn-fechar');
            botaoFechar.disabled = fechamentoAtual.status === 'fechado';
            botaoFechar.classList.toggle('opacity-50', fechamentoAtual.status === 'fechado');
            botaoFechar.classList.toggle('cursor-not-allowed', fechamentoAtual.status === 'fechado');

            atualizarAbas();

            atualizarAbaLancamentos();
            atualizarAbaAuditoria();
        }

        async function atualizarAbaLancamentos() {
            const container = document.getElementById('lancamentos-container');
            const mes = document.getElementById('filter-mes').value;
            const ano = document.getElementById('filter-ano').value;

            try {
                const res = await fetch(`/api/tesouraria/caixa?mes=${mes}&ano=${ano}`);
                const json = await res.json();

                if (!json.ok || !Array.isArray(json.lancamentos)) {
                    container.innerHTML = '<p class="text-red-600">Não foi possível carregar os lançamentos deste período.</p>';
                    return;
                }

                if (json.lancamentos.length === 0) {
                    container.innerHTML = '<p class="text-gray-500">Nenhum lançamento neste período</p>';
                    return;
                }

                container.innerHTML = json.lancamentos.map(l => `
                    <div class="flex justify-between items-center p-2 border-b border-gray-200">
                        <div>
                            <p class="font-medium">${l.categoria_nome}</p>
                            <p class="text-xs text-gray-500">${l.descricao || '-'}</p>
                        </div>
                        <p class="font-semibold ${l.tipo === 'entrada' ? 'text-green-700' : 'text-red-700'}">
                            ${l.tipo === 'entrada' ? '+' : '-'} ${formatarMoeda(l.valor)}
                        </p>
                    </div>
                `).join('');
            } catch (err) {
                console.error('Erro ao carregar lançamentos do fechamento:', err);
                container.innerHTML = '<p class="text-red-600">Erro ao carregar os lançamentos deste período.</p>';
            }
        }

        async function atualizarAbaAuditoria() {
            const container = document.getElementById('auditoria-container');

            if (!fechamentoAtual?.id) {
                container.innerHTML = '<p class="text-gray-500">Nenhum ajuste registrado</p>';
                return;
            }

            try {
                const res = await fetch(`/api/tesouraria/fechamento/${fechamentoAtual.id}/auditoria`);
                const json = await res.json();

                if (!json.ok || !Array.isArray(json.auditoria) || json.auditoria.length === 0) {
                    container.innerHTML = '<p class="text-gray-500">Nenhum ajuste registrado</p>';
                    return;
                }

                container.innerHTML = json.auditoria.map(a => `
                    <div class="p-3 border border-yellow-200 bg-yellow-50 rounded">
                        <p class="font-medium text-sm">Alteração de ${a.campo_alterado}</p>
                        <p class="text-xs text-gray-600 mt-1">De ${formatarMoeda(a.valor_anterior)} para ${formatarMoeda(a.valor_novo)}</p>
                        <p class="text-xs text-gray-500 mt-1"><strong>Justificativa:</strong> ${a.justificativa}</p>
                        <p class="text-xs text-gray-400 mt-1">Por ${a.alterado_por_nome || 'Sistema'} em ${new Date(a.alterado_em).toLocaleString('pt-BR')}</p>
                    </div>
                `).join('');
            } catch (err) {
                console.error('Erro ao carregar auditoria:', err);
                container.innerHTML = '<p class="text-red-600">Erro ao carregar auditoria do período.</p>';
            }
        }

        function mudarAba(aba) {
            abaAtual = aba;
            atualizarAbas();
        }

        function editarSaldoInicial() {
            if (!fechamentoAtual) {
                exibirFeedback('erro', 'Fechamento ainda não carregado.');
                return;
            }
            document.getElementById('novo-saldo').value = fechamentoAtual.saldo_inicial;
            document.getElementById('modal-saldo').classList.remove('hidden');
        }

        function fecharModalSaldo() {
            document.getElementById('modal-saldo').classList.add('hidden');
        }

        document.getElementById('form-saldo').addEventListener('submit', async (e) => {
            e.preventDefault();
            const data = {
                fechamento_id: fechamentoAtual.id,
                novo_saldo: parseFloat(document.getElementById('novo-saldo').value),
                justificativa: document.getElementById('justificativa-saldo').value
            };

            try {
                const res = await fetch('/api/tesouraria/fechamento/atualizar-saldo', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const json = await res.json();
                if (json.ok) {
                    fecharModalSaldo();
                    exibirFeedback('sucesso', 'Saldo inicial atualizado com sucesso.');
                    carregarFechamento();
                } else {
                    exibirFeedback('erro', 'Não foi possível atualizar o saldo inicial.');
                }
            } catch (err) {
                console.error('Erro:', err);
                exibirFeedback('erro', 'Erro ao atualizar saldo inicial.');
            }
        });

        async function sugerirSaldoProximo() {
            if (!fechamentoAtual) {
                exibirFeedback('erro', 'Fechamento ainda não carregado.');
                return;
            }
            alert('Sugestão de saldo para próximo período:\n' + formatarMoeda(fechamentoAtual.saldo_final));
        }

        async function fecharMes() {
            if (!fechamentoAtual) {
                exibirFeedback('erro', 'Fechamento ainda não carregado.');
                return;
            }

            if (!confirm('Tem certeza que deseja fechar este mês? Esta ação é irreversível.')) return;

            try {
                const res = await fetch('/api/tesouraria/fechamento/fechar', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        fechamento_id: fechamentoAtual.id,
                        mes: parseInt(document.getElementById('filter-mes').value),
                        ano: parseInt(document.getElementById('filter-ano').value)
                    })
                });
                const json = await res.json();
                if (json.ok) {
                    exibirFeedback('sucesso', 'Período fechado com sucesso.');
                    carregarFechamento();
                } else {
                    exibirFeedback('erro', 'Não foi possível fechar o período.');
                }
            } catch (err) {
                console.error('Erro ao fechar mês:', err);
                exibirFeedback('erro', 'Erro ao fechar o período.');
            }
        }

        carregarFechamento();
    </script>
</body>
</html>
