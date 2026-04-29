document.addEventListener('DOMContentLoaded', function() {
    const nomesMeses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
    
    const ui = {
        filterMes: document.getElementById('filter-mes'),
        filterAno: document.getElementById('filter-ano'),
        totalEntradas: document.getElementById('total-entradas'),
        totalSaidas: document.getElementById('total-saidas'),
        saldoLiquido: document.getElementById('saldo-liquido'),
        saldoAcumulado: document.getElementById('saldo-acumulado'),
        lancamentosTable: document.getElementById('lancamentos-table'),
        lancamentosCards: document.getElementById('lancamentos-cards'),
        sugestoesPanel: document.getElementById('sugestoes-panel'),
        sugestoesToggleIcon: document.getElementById('sugestoes-toggle-icon'),
        sugestoesEntradas: document.getElementById('sugestoes-entradas'),
        sugestoesSaidas: document.getElementById('sugestoes-saidas'),
        legendaEntradas: document.getElementById('legenda-entradas'),
        legendaSaidas: document.getElementById('legenda-saidas'),
        chartCaixaPizzaEl: document.querySelector("#chartCaixaPizza"),
        chartCaixaTendenciaEl: document.querySelector("#chartCaixaTendencia"),
        modal: document.getElementById('modal-lancamento'),
        modalTitle: document.getElementById('modal-title'),
        modalSubmitButton: document.getElementById('modal-submit-button'),
        form: document.getElementById('form-lancamento'),
        tipoLancamentoInput: document.getElementById('tipo-lancamento'),
        lancamentoIdInput: document.getElementById('lancamento_id'),
        categoriaIdInput: document.getElementById('categoria_id'),
        valorInput: document.getElementById('valor'),
        dataLancamentoInput: document.getElementById('data_lancamento'),
        descricaoInput: document.getElementById('descricao'),
    };

    let chartPizza, chartTendencia;

    function formatarMoeda(valor) {
        return Number(valor || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    }

    window.toggleSugestoes = function() {
        const isHidden = ui.sugestoesPanel.classList.toggle('hidden');
        ui.sugestoesToggleIcon.textContent = isHidden ? 'Mostrar' : 'Ocultar';
    }

    async function carregarSugestoes() {
        try {
            const [resEnt, resSai] = await Promise.all([
                fetch('/api/tesouraria/categorias?tipo=entrada'),
                fetch('/api/tesouraria/categorias?tipo=saida')
            ]);
            const entradas = (await resEnt.json()).categorias || [];
            const saidas = (await resSai.json()).categorias || [];

            const renderPills = (cats, tipo) => cats.map(c => `
                <button onclick="abrirModalLancamento('${tipo}', ${c.id})"
                    class="btn btn-pill ${tipo === 'entrada' ? 'btn-pill-success' : 'btn-pill-danger'}">
                    ${c.nome}
                </button>
            `).join('') || `<span class="text-sm text-gray-500 dark:text-gray-400">Nenhuma categoria de ${tipo}</span>`;

            ui.sugestoesEntradas.innerHTML = renderPills(entradas, 'entrada');
            ui.sugestoesSaidas.innerHTML = renderPills(saidas, 'saida');
        } catch (err) {
            console.error('Erro ao carregar sugestões:', err);
            ui.sugestoesEntradas.innerHTML = '<span class="text-sm text-red-500">Erro ao carregar</span>';
            ui.sugestoesSaidas.innerHTML = '<span class="text-sm text-red-500">Erro ao carregar</span>';
        }
    }

    window.abrirModalLancamento = async function(tipo, categoriaId = null, lancamento = null) {
        ui.form.reset();
        ui.lancamentoIdInput.value = lancamento ? lancamento.id : '';
        ui.tipoLancamentoInput.value = tipo;
        ui.dataLancamentoInput.valueAsDate = lancamento ? new Date(lancamento.data_lancamento + 'T00:00:00') : new Date();
        ui.valorInput.value = lancamento ? lancamento.valor : '';
        ui.descricaoInput.value = lancamento ? lancamento.descricao : '';

        const isEdit = lancamento !== null;
        ui.modalTitle.textContent = isEdit 
            ? (tipo === 'entrada' ? 'Editar Entrada' : 'Editar Saída')
            : (tipo === 'entrada' ? 'Nova Entrada' : 'Nova Saída');
        
        ui.modalSubmitButton.className = `btn ${tipo === 'entrada' ? 'btn-success' : 'btn-danger'}`;
        ui.modalSubmitButton.textContent = isEdit ? 'Salvar Alterações' : 'Salvar';

        await carregarCategorias(tipo);
        ui.categoriaIdInput.value = lancamento ? lancamento.categoria_id : (categoriaId || '');

        ui.modal.classList.remove('hidden');
    }

    window.fecharModalLancamento = function() {
        ui.modal.classList.add('hidden');
    }

    async function carregarCategorias(tipo) {
        try {
            const res = await fetch(`/api/tesouraria/categorias?tipo=${tipo}`);
            const json = await res.json();
            const select = ui.categoriaIdInput;
            select.innerHTML = '<option value="">Selecione uma categoria</option>';
            (json.categorias || []).forEach(cat => {
                select.innerHTML += `<option value="${cat.id}">${cat.nome}</option>`;
            });
        } catch (err) {
            console.error('Erro ao carregar categorias:', err);
        }
    }

    function obterPeriodoAnterior(mes, ano) {
        return mes === 1 ? { mes: 12, ano: ano - 1 } : { mes: mes - 1, ano };
    }

    function obterProximoPeriodo(mes, ano) {
        return mes === 12 ? { mes: 1, ano: ano + 1 } : { mes: mes + 1, ano };
    }

    async function buscarTotaisPeriodo(mes, ano) {
        try {
            const res = await fetch(`/api/tesouraria/caixa?mes=${mes}&ano=${ano}`);
            const json = await res.json();
            return json.totais || { entrada: 0, saida: 0 };
        } catch (e) {
            return { entrada: 0, saida: 0 };
        }
    }

    async function buscarFechamentoPeriodo(mes, ano) {
        try {
            const res = await fetch(`/api/tesouraria/fechamento?mes=${mes}&ano=${ano}`);
            const json = await res.json();
            return json.fechamento || null;
        } catch (e) {
            return null;
        }
    }

    async function filtrarCaixa() {
        const mes = Number(ui.filterMes.value);
        const ano = Number(ui.filterAno.value);
        try {
            const res = await fetch(`/api/tesouraria/caixa?mes=${mes}&ano=${ano}`);
            const json = await res.json();
            const fechamento = await buscarFechamentoPeriodo(mes, ano);
            atualizarResumo(json.totais || {}, fechamento);
            atualizarTabela(json.lancamentos || []);
            await atualizarGraficos(mes, ano, json.totais || {});
        } catch (err) {
            console.error('Erro ao carregar caixa:', err);
        }
    }

    function atualizarResumo(totais, fechamento = null) {
        const totalEntradas = parseFloat(totais.entrada || 0);
        const totalSaidas = parseFloat(totais.saida || 0);
        const saldoLiquido = totalEntradas - totalSaidas;
        const saldoAcumulado = fechamento && fechamento.saldo_final !== undefined
            ? parseFloat(fechamento.saldo_final || 0)
            : saldoLiquido;

        ui.totalEntradas.textContent = formatarMoeda(totalEntradas);
        ui.totalSaidas.textContent = formatarMoeda(totalSaidas);
        ui.saldoLiquido.textContent = formatarMoeda(saldoLiquido);
        if (ui.saldoAcumulado) {
            ui.saldoAcumulado.textContent = formatarMoeda(saldoAcumulado);
        }
    }

    function renderLancamentoRow(l) {
        return `
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                <td class="px-4 py-3 text-gray-800 dark:text-gray-200">${new Date(l.data_lancamento + 'T00:00:00').toLocaleDateString('pt-BR')}</td>
                <td class="px-4 py-3"><span class="badge ${l.tipo === 'entrada' ? 'badge-success' : 'badge-danger'}">${l.tipo}</span></td>
                <td class="px-4 py-3 text-gray-800 dark:text-gray-200">${l.categoria_nome}</td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">${l.descricao || '-'}</td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">${l.obreiro_nome || '-'}</td>
                <td class="px-4 py-3 text-right font-semibold ${l.tipo === 'entrada' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'}">
                    ${l.tipo === 'entrada' ? '+' : '-'} ${formatarMoeda(l.valor)}
                </td>
                <td class="px-4 py-3 text-right">
                    <button onclick="deletarLancamento(${l.id})" class="text-xs font-medium text-red-600 hover:underline">Excluir</button>
                </td>
            </tr>
        `;
    }

    function renderLancamentoCard(l) {
        return `
            <div class="card-list-item">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-semibold text-gray-800 dark:text-gray-200">${l.categoria_nome}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">${new Date(l.data_lancamento + 'T00:00:00').toLocaleDateString('pt-BR')}</p>
                    </div>
                    <span class="badge ${l.tipo === 'entrada' ? 'badge-success' : 'badge-danger'}">${l.tipo}</span>
                </div>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">${l.descricao || '-'}</p>
                <div class="mt-3 flex items-end justify-between">
                    <p class="text-lg font-bold ${l.tipo === 'entrada' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'}">${formatarMoeda(l.valor)}</p>
                    <button onclick="deletarLancamento(${l.id})" class="text-xs font-medium text-red-600 hover:underline">Excluir</button>
                </div>
            </div>
        `;
    }

    function atualizarTabela(lancamentos) {
        if (lancamentos.length === 0) {
            ui.lancamentosTable.innerHTML = `
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                        <div class="font-medium">Nenhum lançamento encontrado para esta competência.</div>
                        <div class="mt-1 text-sm">Use <button type="button" class="font-semibold text-erp-navy underline underline-offset-4" onclick="abrirModalLancamento('entrada')">Novo lançamento</button> para registrar uma entrada ou saída.</div>
                    </td>
                </tr>
            `;
            ui.lancamentosCards.innerHTML = `
                <div class="rounded-xl border border-erp-border bg-erp-surface p-4 text-center text-sm text-erp-muted">
                    <div class="font-semibold text-erp-text">Nenhum lançamento encontrado para esta competência.</div>
                    <div class="mt-2">
                        <button type="button" class="btn btn-success w-full" onclick="abrirModalLancamento('entrada')">Novo lançamento</button>
                    </div>
                </div>
            `;
            return;
        }
        ui.lancamentosTable.innerHTML = lancamentos.map(renderLancamentoRow).join('');
        ui.lancamentosCards.innerHTML = lancamentos.map(renderLancamentoCard).join('');
    }

    async function atualizarGraficos(mes, ano, totaisAtuais) {
        const { mes: mesAnterior, ano: anoAnterior } = obterPeriodoAnterior(mes, ano);
        const totaisAnterior = await buscarTotaisPeriodo(mesAnterior, anoAnterior);

        const entradaAtual = parseFloat(totaisAtuais.entrada || 0);
        const saidaAtual = parseFloat(totaisAtuais.saida || 0);
        const entradaAnterior = parseFloat(totaisAnterior.entrada || 0);
        const saidaAnterior = parseFloat(totaisAnterior.saida || 0);
        
        const projecaoEntrada = (entradaAnterior + entradaAtual) / 2;
        const projecaoSaida = (saidaAnterior + saidaAtual) / 2;

        const pizzaVazio = entradaAtual === 0 && saidaAtual === 0;
        const pizzaOptions = {
            series: pizzaVazio ? [1, 1] : [entradaAtual, saidaAtual],
            chart: { type: 'donut', height: 208, sparkline: { enabled: true } },
            colors: pizzaVazio ? ['#e5e7eb', '#e5e7eb'] : ['#16a34a', '#dc2626'],
            stroke: { width: 0 },
            legend: { show: false },
            tooltip: { enabled: false },
            dataLabels: { enabled: false },
            plotOptions: {
                pie: {
                    donut: {
                        labels: {
                            show: pizzaVazio,
                            name: { show: false },
                            value: { show: false },
                            total: {
                                show: pizzaVazio,
                                label: 'Sem dados',
                                formatter: function () { return ''; }
                            }
                        }
                    }
                }
            }
        };
        if (chartPizza) {
            chartPizza.updateOptions(pizzaOptions);
        } else {
            chartPizza = new ApexCharts(ui.chartCaixaPizzaEl, pizzaOptions);
            chartPizza.render();
        }
        ui.legendaEntradas.textContent = `Entradas: ${formatarMoeda(entradaAtual)}`;
        ui.legendaSaidas.textContent = `Saídas: ${formatarMoeda(saidaAtual)}`;

        const tendenciaOptions = {
            series: [
                { name: 'Entradas', data: [entradaAnterior, entradaAtual, projecaoEntrada] },
                { name: 'Saídas', data: [saidaAnterior, saidaAtual, projecaoSaida] }
            ],
            chart: { type: 'bar', height: 288, toolbar: { show: false }, parentHeightOffset: 0 },
            colors: ['#16a34a', '#dc2626'],
            plotOptions: { bar: { horizontal: false, columnWidth: '55%', borderRadius: 4 } },
            dataLabels: { enabled: false },
            stroke: { show: true, width: 2, colors: ['transparent'] },
            xaxis: {
                categories: [nomesMeses[mesAnterior - 1], nomesMeses[mes - 1], nomesMeses[obterProximoPeriodo(mes, ano).mes - 1]],
                labels: { style: { colors: '#6b7280' } },
                axisBorder: { show: false },
                axisTicks: { show: false },
            },
            yaxis: { 
                labels: { 
                    style: { colors: '#6b7280' }, 
                    formatter: (val) => val.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL', minimumFractionDigits: 0, maximumFractionDigits: 0 })
                } 
            },
            fill: { opacity: 1 },
            grid: { borderColor: '#e5e7eb', strokeDashArray: 4 },
            legend: { labels: { useSeriesColors: true }, markers: { radius: 4 } },
            tooltip: { y: { formatter: (val) => formatarMoeda(val) } }
        };
        if (chartTendencia) {
            chartTendencia.updateOptions(tendenciaOptions);
        } else {
            chartTendencia = new ApexCharts(ui.chartCaixaTendenciaEl, tendenciaOptions);
            chartTendencia.render();
        }
    }

    window.deletarLancamento = async function(id) {
        if (confirm('Tem certeza que deseja excluir este lançamento?')) {
            try {
                const res = await fetch(`/api/tesouraria/lancamento/${id}`, { method: 'DELETE' });
                if (!res.ok) throw new Error('Falha ao excluir');
                filtrarCaixa();
            } catch (err) {
                console.error('Erro ao deletar lançamento:', err);
                alert('Não foi possível excluir o lançamento.');
            }
        }
    }

    ui.form.addEventListener('submit', async function(e) {
        e.preventDefault();
        const id = ui.lancamentoIdInput.value;
        const body = {
            tipo: ui.tipoLancamentoInput.value,
            categoria_id: ui.categoriaIdInput.value,
            valor: ui.valorInput.value,
            data_lancamento: ui.dataLancamentoInput.value,
            descricao: ui.descricaoInput.value,
            mes_ref: parseInt(ui.filterMes?.value || (new Date().getMonth() + 1)),
            ano_ref: parseInt(ui.filterAno?.value || new Date().getFullYear()),
        };

        try {
            if (id) {
                alert('Edição de lançamento ainda não está habilitada neste painel. Exclua e recrie o lançamento, se necessário.');
                return;
            }

            const res = await fetch('/api/tesouraria/lancamento/criar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body)
            });
            if (!res.ok) throw new Error('Falha ao salvar');
            fecharModalLancamento();
            filtrarCaixa();
        } catch (err) {
            console.error('Erro ao salvar lançamento:', err);
            alert('Não foi possível salvar o lançamento.');
        }
    });

    ui.filterMes.addEventListener('change', filtrarCaixa);
    ui.filterAno.addEventListener('change', filtrarCaixa);

    // Initial Load
    carregarSugestoes();
    filtrarCaixa();
});
