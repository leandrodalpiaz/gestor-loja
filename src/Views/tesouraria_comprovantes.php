<?php
// src/Views/tesouraria_comprovantes.php
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
    <title>Validação de Comprovantes - Tesouraria</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen text-gray-800">
    <div class="max-w-6xl mx-auto px-4 py-8">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Caixa de Entrada - Comprovantes PIX</h1>
            <a href="/dashboard" class="text-sm text-blue-700 hover:underline">← Voltar</a>
        </div>

        <!-- Abas de Status -->
        <div class="flex gap-4 mb-6 border-b border-gray-200">
            <button type="button" data-status="pendente" onclick="filtrarStatus('pendente')" class="tab-status px-4 py-2 border-b-2 border-blue-700 text-blue-700 font-semibold">
                Pendentes (<span id="count-pendentes">0</span>)
            </button>
            <button type="button" data-status="aprovado" onclick="filtrarStatus('aprovado')" class="tab-status px-4 py-2 border-b-2 border-transparent text-gray-600 hover:text-gray-800">
                Aprovados (<span id="count-aprovados">0</span>)
            </button>
            <button type="button" data-status="rejeitado" onclick="filtrarStatus('rejeitado')" class="tab-status px-4 py-2 border-b-2 border-transparent text-gray-600 hover:text-gray-800">
                Rejeitados (<span id="count-rejeitados">0</span>)
            </button>
        </div>

        <!-- Lista de Comprovantes -->
        <div id="comprovantes-container" class="space-y-4">
            <p class="text-center text-gray-500">Carregando...</p>
        </div>
    </div>

    <!-- Modal de Validação -->
    <div id="modal-validacao" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl max-h-96 overflow-y-auto">
            <div class="sticky top-0 p-6 border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-bold">Validar Comprovante</h2>
            </div>
            <form id="form-validacao" class="p-6 space-y-4">
                <input type="hidden" id="comprovante-id">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Obreiro</label>
                        <input type="text" id="obreiro-info" class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" readonly>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Valor Informado (R$)</label>
                        <input type="text" id="valor-informado" class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" readonly>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Período Informado</label>
                        <input type="text" id="periodo-informado" class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" readonly>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Data do Envio</label>
                        <input type="text" id="data-envio" class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50" readonly>
                    </div>
                </div>

                <hr>

                <div>
                    <label class="block text-sm font-medium mb-1">Valor Validado (R$) *</label>
                    <input type="number" id="valor-validado" step="0.01" min="0" class="w-full border border-gray-300 rounded px-3 py-2" required>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Mês de Referência *</label>
                    <select id="mes-validado" class="w-full border border-gray-300 rounded px-3 py-2" required>
                        <option value="">Selecionar</option>
                        <option value="1">Janeiro</option>
                        <option value="2">Fevereiro</option>
                        <option value="3">Março</option>
                        <option value="4">Abril</option>
                        <option value="5">Maio</option>
                        <option value="6">Junho</option>
                        <option value="7">Julho</option>
                        <option value="8">Agosto</option>
                        <option value="9">Setembro</option>
                        <option value="10">Outubro</option>
                        <option value="11">Novembro</option>
                        <option value="12">Dezembro</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Ano de Referência *</label>
                    <select id="ano-validado" class="w-full border border-gray-300 rounded px-3 py-2" required>
                        <option value="">Selecionar</option>
                        <?php
                        $anoAtual = (int) date('Y');
                        for ($a = $anoAtual - 1; $a <= $anoAtual; $a++) {
                            echo "<option value=\"$a\">$a</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="flex gap-2">
                    <button type="button" onclick="fecharModalValidacao()" class="flex-1 px-4 py-2 rounded bg-gray-200 text-gray-800 hover:bg-gray-300">
                        Cancelar
                    </button>
                    <button type="button" onclick="rejeitarComprovante()" class="flex-1 px-4 py-2 rounded bg-red-700 text-white hover:bg-red-800">
                        Rejeitar
                    </button>
                    <button type="submit" class="flex-1 px-4 py-2 rounded bg-green-700 text-white hover:bg-green-800">
                        Aprovar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de Rejeição -->
    <div id="modal-rejeicao" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6">
            <h2 class="text-lg font-bold mb-4">Motivo da Rejeição</h2>
            <textarea id="motivo-rejeicao" class="w-full border border-gray-300 rounded px-3 py-2 h-24 mb-4" placeholder="Explique por que este comprovante está sendo rejeitado..."></textarea>
            <div class="flex gap-2">
                <button type="button" onclick="fecharModalRejeicao()" class="flex-1 px-4 py-2 rounded bg-gray-200 text-gray-800 hover:bg-gray-300">
                    Cancelar
                </button>
                <button type="button" onclick="confirmarRejeicao()" class="flex-1 px-4 py-2 rounded bg-red-700 text-white hover:bg-red-800">
                    Confirmar Rejeição
                </button>
            </div>
        </div>
    </div>

    <script>
        let statusAtual = 'pendente';
        let comprovanteSendoRejeitado = null;

        function formatarMoeda(valor) {
            return Number(valor || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        }

        function aplicarTabAtiva() {
            document.querySelectorAll('.tab-status').forEach((aba) => {
                const ativa = aba.dataset.status === statusAtual;
                aba.classList.toggle('border-blue-700', ativa);
                aba.classList.toggle('text-blue-700', ativa);
                aba.classList.toggle('font-semibold', ativa);
                aba.classList.toggle('border-transparent', !ativa);
                aba.classList.toggle('text-gray-600', !ativa);
            });
        }

        async function carregarComprovantes() {
            try {
                const res = await fetch('/api/tesouraria/comprovantes');
                const json = await res.json();
                atualizarLista(json.comprovantes);
                atualizarContadores(json.comprovantes);
                aplicarTabAtiva();
            } catch (err) {
                console.error('Erro ao carregar:', err);
            }
        }

        function filtrarStatus(status) {
            statusAtual = status;
            aplicarTabAtiva();
            carregarComprovantes();
        }

        function atualizarContadores(comprovantes) {
            const pendentes = comprovantes.filter(c => c.status === 'pendente').length;
            const aprovados = comprovantes.filter(c => c.status === 'aprovado').length;
            const rejeitados = comprovantes.filter(c => c.status === 'rejeitado').length;

            document.getElementById('count-pendentes').textContent = pendentes;
            document.getElementById('count-aprovados').textContent = aprovados;
            document.getElementById('count-rejeitados').textContent = rejeitados;
        }

        function atualizarLista(comprovantes) {
            const filtrados = comprovantes.filter(c => c.status === statusAtual);
            const container = document.getElementById('comprovantes-container');

            if (filtrados.length === 0) {
                container.innerHTML = `<p class="text-center text-gray-500">Nenhum comprovante com status "${statusAtual}"</p>`;
                return;
            }

            container.innerHTML = filtrados.map(c => `
                <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-900">${c.obreiro_nome || 'ID Telegram: ' + c.telegram_user_id}</h3>
                            <p class="text-sm text-gray-600 mt-1">Valor informado: <strong>${formatarMoeda(c.valor_informado)}</strong></p>
                            <p class="text-sm text-gray-600">Período: <strong>${c.mes_ref_informado ? String(c.mes_ref_informado).padStart(2, '0') : '?'}/${c.ano_ref_informado || '?'}</strong></p>
                            <p class="text-xs text-gray-500 mt-2">Recebido em: ${new Date(c.criado_em).toLocaleString('pt-BR')}</p>
                            ${c.status === 'aprovado' ? `<p class="text-xs text-green-700 mt-2"><strong>Aprovado:</strong> ${formatarMoeda(c.valor_validado)} em ${String(c.mes_ref_validado || '').padStart(2, '0')}/${c.ano_ref_validado || '?'}</p>` : ''}
                            ${c.status === 'rejeitado' ? `<p class="text-xs text-red-600 mt-2"><strong>Motivo:</strong> ${c.motivo_rejeicao || '-'}</p>` : ''}
                        </div>
                        <div class="flex gap-2 ml-4">
                            ${c.status === 'pendente' ? `
                                <button onclick="abrirValidacao(${c.id})" class="px-4 py-2 rounded bg-blue-700 text-white hover:bg-blue-800 text-sm">
                                    Validar
                                </button>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `).join('');
        }

        async function abrirValidacao(id) {
            try {
                const res = await fetch(`/api/tesouraria/comprovantes/${id}`);
                const json = await res.json();
                const c = json.comprovante;

                document.getElementById('comprovante-id').value = c.id;
                document.getElementById('obreiro-info').value = c.obreiro_nome || ('Telegram: ' + c.telegram_user_id);
                document.getElementById('valor-informado').value = formatarMoeda(c.valor_informado);
                document.getElementById('periodo-informado').value = `${String(c.mes_ref_informado || '').padStart(2, '0')}/${c.ano_ref_informado || '?'}`;
                document.getElementById('data-envio').value = new Date(c.criado_em).toLocaleString('pt-BR');

                // Sugere valores
                document.getElementById('valor-validado').value = c.valor_informado || '';
                document.getElementById('mes-validado').value = c.mes_ref_informado || new Date().getMonth() + 1;
                document.getElementById('ano-validado').value = c.ano_ref_informado || new Date().getFullYear();

                document.getElementById('modal-validacao').classList.remove('hidden');
            } catch (err) {
                console.error('Erro:', err);
            }
        }

        function fecharModalValidacao() {
            document.getElementById('modal-validacao').classList.add('hidden');
            document.getElementById('form-validacao').reset();
        }

        function rejeitarComprovante() {
            comprovanteSendoRejeitado = document.getElementById('comprovante-id').value;
            document.getElementById('modal-rejeicao').classList.remove('hidden');
        }

        function fecharModalRejeicao() {
            document.getElementById('modal-rejeicao').classList.add('hidden');
            document.getElementById('motivo-rejeicao').value = '';
        }

        async function confirmarRejeicao() {
            const motivo = document.getElementById('motivo-rejeicao').value;
            if (!motivo.trim()) {
                alert('Por favor, informe um motivo');
                return;
            }

            try {
                const res = await fetch('/api/tesouraria/comprovantes/rejeitar', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: comprovanteSendoRejeitado, motivo })
                });
                const json = await res.json();
                if (json.ok) {
                    fecharModalRejeicao();
                    fecharModalValidacao();
                    carregarComprovantes();
                }
            } catch (err) {
                console.error('Erro:', err);
            }
        }

        document.getElementById('form-validacao').addEventListener('submit', async (e) => {
            e.preventDefault();
            const data = {
                id: document.getElementById('comprovante-id').value,
                valor: parseFloat(document.getElementById('valor-validado').value),
                mes: parseInt(document.getElementById('mes-validado').value),
                ano: parseInt(document.getElementById('ano-validado').value)
            };

            try {
                const res = await fetch('/api/tesouraria/comprovantes/aprovar', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const json = await res.json();
                if (json.ok) {
                    fecharModalValidacao();
                    carregarComprovantes();
                }
            } catch (err) {
                console.error('Erro:', err);
            }
        });

        // Carrega na página
        carregarComprovantes();
        setInterval(carregarComprovantes, 10000); // Recarrega a cada 10s
    </script>
</body>
</html>
