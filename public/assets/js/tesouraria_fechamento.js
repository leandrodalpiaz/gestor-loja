document.addEventListener('DOMContentLoaded', () => {
    let fechamentoAtual = null;
    const ui = {
        filterMes: document.getElementById('filter-mes'),
        filterAno: document.getElementById('filter-ano'),
        statusFechamento: document.getElementById('status-fechamento'),
        saldoInicial: document.getElementById('saldo-inicial'),
        totalEntradas: document.getElementById('total-entradas'),
        totalSaidas: document.getElementById('total-saidas'),
        saldoFinal: document.getElementById('saldo-final'),
        btnFecharMes: document.getElementById('btn-fechar-mes'),
        fechamentoContent: document.getElementById('fechamento-content'),
        modalSaldoInicial: document.getElementById('modal-saldo-inicial'),
        saldoInicialInput: document.getElementById('saldo-inicial-input'),
    };

    function formatarMoeda(valor) {
        return Number(valor || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    }

    function renderLoading() {
        return `
            <div class="text-center text-gray-500 dark:text-gray-400 py-8">
                <svg class="mx-auto h-12 w-12 text-gray-400 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.75V6.25m0 11.5v1.5m8.25-13.5l-1.06 1.06M6.81 17.19l-1.06 1.06m13.5 0l-1.06-1.06M6.81 6.81l-1.06-1.06M4.75 12H6.25m11.5 0H19.5" /></svg>
                <p class="mt-2">Carregando...</p>
            </div>
        `;
    }

    function renderError(message) {
        return `<div class="alert alert-danger">${message}</div>`;
    }

    async function carregarFechamento() {
        const mes = parseInt(ui.filterMes.value);
        const ano = parseInt(ui.filterAno.value);
        ui.fechamentoContent.innerHTML = renderLoading();

        try {
            const res = await fetch(`/api/tesouraria/fechamento?mes=${mes}&ano=${ano}`);
            if (!res.ok) throw new Error('Falha na resposta da rede');
            const json = await res.json();
            const fechamento = json.fechamento || {};
            const totais = json.totais || {};
            const totalEntradas = totais.entrada ?? fechamento.total_entradas ?? 0;
            const totalSaidas = totais.saida ?? fechamento.total_saidas ?? 0;
            fechamentoAtual = fechamento;

            const isFechado = fechamento.status === 'fechado';
            
            ui.statusFechamento.textContent = fechamento.status || 'Aberto';
            ui.statusFechamento.className = `font-bold ${isFechado ? 'text-green-600 dark:text-green-400' : 'text-blue-600 dark:text-blue-400'}`;
            
            ui.saldoInicial.textContent = formatarMoeda(fechamento.saldo_inicial || 0);
            ui.totalEntradas.textContent = formatarMoeda(totalEntradas);
            ui.totalSaidas.textContent = formatarMoeda(totalSaidas);
            ui.saldoFinal.textContent = formatarMoeda(fechamento.saldo_final || 0);

            ui.btnFecharMes.disabled = isFechado;
            ui.btnFecharMes.textContent = isFechado ? 'Mês Fechado' : 'Fechar Mês';
            ui.btnFecharMes.classList.toggle('btn-disabled', isFechado);

            ui.fechamentoContent.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="card-list-item">
                        <h3 class="font-semibold text-gray-800 dark:text-gray-100 mb-2">Resumo do Período</h3>
                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between"><dt class="text-gray-600 dark:text-gray-400">Período:</dt><dd class="font-medium text-gray-800 dark:text-gray-200">${String(mes).padStart(2, '0')}/${ano}</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-600 dark:text-gray-400">Status:</dt><dd class="font-medium text-gray-800 dark:text-gray-200">${fechamento.status || 'Aberto'}</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-600 dark:text-gray-400">Data de Fechamento:</dt><dd class="font-medium text-gray-800 dark:text-gray-200">${fechamento.data_fechamento ? new Date(fechamento.data_fechamento).toLocaleString('pt-BR') : 'N/A'}</dd></div>
                        </dl>
                    </div>
                    <div class="card-list-item">
                        <h3 class="font-semibold text-gray-800 dark:text-gray-100 mb-2">Observações</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Use esta tela para consolidar o período após validar o livro-caixa, os comprovantes e a regularidade dos obreiros.</p>
                    </div>
                </div>
            `;
        } catch (err) {
            console.error('Erro ao carregar fechamento:', err);
            ui.fechamentoContent.innerHTML = renderError('Não foi possível carregar os dados do fechamento. Tente novamente.');
        }
    }

    window.editarSaldoInicial = function() {
        const saldoAtual = ui.saldoInicial.textContent;
        const valorNumerico = parseFloat(saldoAtual.replace('R$', '').replace(/\./g, '').replace(',', '.').trim());
        ui.saldoInicialInput.value = valorNumerico.toFixed(2);
        ui.modalSaldoInicial.classList.remove('hidden');
    }

    window.fecharModalSaldoInicial = function() {
        ui.modalSaldoInicial.classList.add('hidden');
    }

    window.salvarSaldoInicial = async function() {
        const mes = parseInt(ui.filterMes.value);
        const ano = parseInt(ui.filterAno.value);
        const saldoInicial = parseFloat(ui.saldoInicialInput.value || '0');
        const fechamentoId = parseInt(fechamentoAtual?.id || 0);

        if (!fechamentoId) {
            alert('Não foi possível identificar o fechamento desta competência.');
            return;
        }

        try {
            const res = await fetch('/api/tesouraria/fechamento/saldo-inicial', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    fechamento_id: fechamentoId,
                    novo_saldo: saldoInicial,
                    justificativa: `Ajuste manual do saldo inicial de ${String(mes).padStart(2, '0')}/${ano}`
                })
            });
            const json = await res.json();
            if (json.ok) {
                fecharModalSaldoInicial();
                carregarFechamento();
                // TODO: Adicionar notificação de sucesso
            } else {
                alert(json.erro || 'Erro ao salvar saldo.');
            }
        } catch (err) {
            console.error('Erro ao salvar saldo inicial:', err);
            alert('Ocorreu um erro de comunicação.');
        }
    }

    window.fecharMes = async function() {
        if (!confirm('Confirma o fechamento deste mês? Esta ação não pode ser desfeita e consolida todos os lançamentos do período.')) return;

        const mes = parseInt(ui.filterMes.value);
        const ano = parseInt(ui.filterAno.value);

        try {
            const res = await fetch('/api/tesouraria/fechamento/fechar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ mes, ano })
            });
            const json = await res.json();
            if (json.ok) {
                carregarFechamento();
                // TODO: Adicionar notificação de sucesso
            } else {
                alert(json.erro || 'Não foi possível fechar o mês.');
            }
        } catch (err) {
            console.error('Erro ao fechar mês:', err);
            alert('Ocorreu um erro de comunicação.');
        }
    }

    ui.filterMes.addEventListener('change', carregarFechamento);
    ui.filterAno.addEventListener('change', carregarFechamento);

    carregarFechamento();
});
