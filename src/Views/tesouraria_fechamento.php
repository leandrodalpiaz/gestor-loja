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
    <div class="max-w-4xl mx-auto px-4 py-8">
        <header class="mb-6 rounded-3xl border border-white/40 bg-[radial-gradient(circle_at_top_left,#d6b672,transparent_30%),linear-gradient(135deg,#162033,#223145)] px-6 py-7 text-white shadow-xl">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-xs uppercase tracking-[0.24em] text-amber-300">Tesouraria</p>
                    <h1 class="mt-2 text-3xl font-semibold">Fechamento Mensal</h1>
                    <p class="mt-2 max-w-3xl text-sm text-slate-200">Conferencia final do periodo com leitura mais clara para saldo inicial, movimento e saldo final.</p>
                </div>
                <a href="/dashboard" class="rounded-md bg-white/10 px-3 py-2 text-sm hover:bg-white/20">Voltar ao Painel</a>
            </div>
        </header>

        <div class="mb-6 rounded-3xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Mes</label>
                    <select id="filter-mes" class="w-full rounded border border-gray-300 px-3 py-2" onchange="carregarFechamento()">
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
                    <select id="filter-ano" class="w-full rounded border border-gray-300 px-3 py-2" onchange="carregarFechamento()">
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
                    <p class="mb-2 text-sm text-gray-600">Status: <span id="status-fechamento" class="font-bold text-blue-700">Aberto</span></p>
                </div>
            </div>
        </div>

        <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-4">
            <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
                <p class="mb-1 text-sm font-medium text-blue-600">Saldo Inicial</p>
                <p class="text-2xl font-bold text-blue-700" id="saldo-inicial">R$ 0,00</p>
                <button onclick="editarSaldoInicial()" class="mt-2 text-xs text-blue-600 hover:underline">Editar</button>
            </div>

            <div class="rounded-lg border border-green-200 bg-green-50 p-4">
                <p class="mb-1 text-sm font-medium text-green-600">Total Entradas</p>
                <p class="text-2xl font-bold text-green-700" id="total-entradas">R$ 0,00</p>
            </div>

            <div class="rounded-lg border border-red-200 bg-red-50 p-4">
                <p class="mb-1 text-sm font-medium text-red-600">Total Saidas</p>
                <p class="text-2xl font-bold text-red-700" id="total-saidas">R$ 0,00</p>
            </div>

            <div class="rounded-lg border border-purple-200 bg-purple-50 p-4">
                <p class="mb-1 text-sm font-medium text-purple-600">Saldo Final</p>
                <p class="text-2xl font-bold text-purple-700" id="saldo-final">R$ 0,00</p>
            </div>
        </div>

        <div class="mb-6 rounded-3xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold">Fechamento do periodo</h2>
                <button id="btn-fechar-mes" onclick="fecharMes()" class="rounded bg-blue-700 px-4 py-2 text-white hover:bg-blue-800">
                    Fechar Mes
                </button>
            </div>
            <div id="fechamento-content" class="space-y-4">
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-center text-gray-500">
                    Carregando dados do fechamento...
                </div>
            </div>
        </div>
    </div>

    <div id="modal-saldo-inicial" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-40 p-4">
        <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-lg">
            <h2 class="mb-4 text-lg font-bold">Definir Saldo Inicial</h2>
            <div class="space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium">Valor</label>
                    <input type="number" id="saldo-inicial-input" step="0.01" min="0" class="w-full rounded border border-gray-300 px-3 py-2">
                </div>
                <div class="flex gap-2">
                    <button onclick="fecharModalSaldoInicial()" class="flex-1 rounded bg-gray-200 px-4 py-2 text-gray-800 hover:bg-gray-300">Cancelar</button>
                    <button onclick="salvarSaldoInicial()" class="flex-1 rounded bg-blue-700 px-4 py-2 text-white hover:bg-blue-800">Salvar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function formatarMoeda(valor) {
            return Number(valor || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        }

        async function carregarFechamento() {
            const mes = parseInt(document.getElementById('filter-mes').value);
            const ano = parseInt(document.getElementById('filter-ano').value);

            try {
                const res = await fetch(`/api/tesouraria/fechamento?mes=${mes}&ano=${ano}`);
                const json = await res.json();
                const fechamento = json.fechamento || {};
                const totais = json.totais || {};

                document.getElementById('status-fechamento').textContent = fechamento.status || 'Aberto';
                document.getElementById('saldo-inicial').textContent = formatarMoeda(fechamento.saldo_inicial || 0);
                document.getElementById('total-entradas').textContent = formatarMoeda(totais.entrada || 0);
                document.getElementById('total-saidas').textContent = formatarMoeda(totais.saida || 0);
                document.getElementById('saldo-final').textContent = formatarMoeda(fechamento.saldo_final || 0);

                const btnFechar = document.getElementById('btn-fechar-mes');
                btnFechar.disabled = fechamento.status === 'fechado';
                btnFechar.className = fechamento.status === 'fechado'
                    ? 'rounded bg-gray-300 px-4 py-2 text-gray-500 cursor-not-allowed'
                    : 'rounded bg-blue-700 px-4 py-2 text-white hover:bg-blue-800';
                btnFechar.textContent = fechamento.status === 'fechado' ? 'MÃªs Fechado' : 'Fechar MÃªs';

                document.getElementById('fechamento-content').innerHTML = `
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                            <h3 class="mb-2 font-semibold">Resumo do fechamento</h3>
                            <p><strong>PerÃ­odo:</strong> ${String(mes).padStart(2, '0')}/${ano}</p>
                            <p><strong>Status:</strong> ${fechamento.status || 'Aberto'}</p>
                            <p><strong>Data de fechamento:</strong> ${fechamento.data_fechamento ? new Date(fechamento.data_fechamento).toLocaleString('pt-BR') : 'Ainda nÃ£o fechado'}</p>
                        </div>
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                            <h3 class="mb-2 font-semibold">ObservaÃ§Ãµes</h3>
                            <p class="text-sm text-gray-600">Use esta tela para consolidar o perÃ­odo apÃ³s validar caixa, comprovantes e regularidade.</p>
                        </div>
                    </div>
                `;
            } catch (err) {
                console.error('Erro ao carregar fechamento:', err);
                document.getElementById('fechamento-content').innerHTML = '<div class="rounded-lg border border-red-200 bg-red-50 p-4 text-red-700">Erro ao carregar dados do fechamento.</div>';
            }
        }

        function editarSaldoInicial() {
            document.getElementById('saldo-inicial-input').value =
                document.getElementById('saldo-inicial').textContent.replace(/[^\d,]/g, '').replace(',', '.');
            const modal = document.getElementById('modal-saldo-inicial');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function fecharModalSaldoInicial() {
            const modal = document.getElementById('modal-saldo-inicial');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        async function salvarSaldoInicial() {
            const mes = parseInt(document.getElementById('filter-mes').value);
            const ano = parseInt(document.getElementById('filter-ano').value);
            const saldoInicial = parseFloat(document.getElementById('saldo-inicial-input').value || '0');

            try {
                const res = await fetch('/api/tesouraria/fechamento/saldo-inicial', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ mes, ano, saldo_inicial: saldoInicial })
                });
                const json = await res.json();
                if (json.ok) {
                    fecharModalSaldoInicial();
                    carregarFechamento();
                }
            } catch (err) {
                console.error('Erro ao salvar saldo inicial:', err);
            }
        }

        async function fecharMes() {
            if (!confirm('Confirma o fechamento deste mÃªs? A aÃ§Ã£o nÃ£o deve ser feita sem conferÃªncia final.')) return;

            const mes = parseInt(document.getElementById('filter-mes').value);
            const ano = parseInt(document.getElementById('filter-ano').value);

            try {
                const res = await fetch('/api/tesouraria/fechamento/fechar', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ mes, ano })
                });
                const json = await res.json();
                if (json.ok) {
                    carregarFechamento();
                } else {
                    alert(json.erro || 'NÃ£o foi possÃ­vel fechar o mÃªs.');
                }
            } catch (err) {
                console.error('Erro ao fechar mÃªs:', err);
            }
        }

        carregarFechamento();
    </script>
</body>
</html>


