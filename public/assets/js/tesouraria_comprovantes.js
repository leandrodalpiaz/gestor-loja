document.addEventListener('DOMContentLoaded', () => {
    let statusAtual = 'pendente';
    let comprovanteSendoRejeitado = null;
    let allComprovantes = [];

    const ui = {
        container: document.getElementById('comprovantes-container'),
        countPendentes: document.getElementById('count-pendentes'),
        countAprovados: document.getElementById('count-aprovados'),
        countRejeitados: document.getElementById('count-rejeitados'),
        tabs: document.querySelectorAll('.tab-status'),
        
        modalValidacao: document.getElementById('modal-validacao'),
        formValidacao: document.getElementById('form-validacao'),
        comprovanteIdInput: document.getElementById('comprovante-id'),
        obreiroInfo: document.getElementById('obreiro-info'),
        valorInformado: document.getElementById('valor-informado'),
        periodoInformado: document.getElementById('periodo-informado'),
        dataEnvio: document.getElementById('data-envio'),
        valorValidado: document.getElementById('valor-validado'),
        mesValidado: document.getElementById('mes-validado'),
        anoValidado: document.getElementById('ano-validado'),
        rotuloPagamento: document.getElementById('rotulo-pagamento'),
        categoriaId: document.getElementById('categoria-id'),
        obrigacaoParcelaId: document.getElementById('obrigacao-parcela-id'),

        modalRejeicao: document.getElementById('modal-rejeicao'),
        motivoRejeicao: document.getElementById('motivo-rejeicao'),
    };

    function formatarMoeda(valor) {
        return Number(valor || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    }

    function aplicarTabAtiva() {
        ui.tabs.forEach((aba) => {
            const ativa = aba.dataset.status === statusAtual;
            aba.classList.toggle('active', ativa);
        });
    }

    async function carregarComprovantes() {
        try {
            const res = await fetch('/api/tesouraria/comprovantes');
            const json = await res.json();
            allComprovantes = json.comprovantes || [];
            atualizarContadores();
            atualizarLista();
            aplicarTabAtiva();
        } catch (err) {
            console.error('Erro ao carregar:', err);
            ui.container.innerHTML = `<div class="alert alert-danger">Falha ao carregar comprovantes.</div>`;
        }
    }

    window.filtrarStatus = function(status) {
        statusAtual = status;
        aplicarTabAtiva();
        atualizarLista();
    }

    function atualizarContadores() {
        ui.countPendentes.textContent = allComprovantes.filter(c => c.status === 'pendente').length;
        ui.countAprovados.textContent = allComprovantes.filter(c => c.status === 'aprovado').length;
        ui.countRejeitados.textContent = allComprovantes.filter(c => c.status === 'rejeitado').length;
    }

    function renderComprovante(c) {
        let statusBadge;
        switch(c.status) {
            case 'pendente': statusBadge = `<span class="badge badge-warning">Pendente</span>`; break;
            case 'aprovado': statusBadge = `<span class="badge badge-success">Aprovado</span>`; break;
            case 'rejeitado': statusBadge = `<span class="badge badge-danger">Rejeitado</span>`; break;
            case 'cancelado': statusBadge = `<span class="badge badge-secondary">Cancelado</span>`; break;
            default: statusBadge = `<span class="badge badge-secondary">${c.status || 'Sem status'}</span>`;
        }

        return `
        <div class="card-list-item">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                <div class="flex-1">
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                        <h3 class="font-semibold text-gray-800 dark:text-gray-100">${c.obreiro_nome || 'ID Telegram: ' + c.telegram_user_id}</h3>
                        ${statusBadge}
                    </div>
                    <div class="mt-2 space-y-1 text-sm">
                        <p><span class="text-gray-500 dark:text-gray-400">Valor:</span> <strong class="text-gray-800 dark:text-gray-200">${formatarMoeda(c.valor_informado)}</strong></p>
                        <p><span class="text-gray-500 dark:text-gray-400">Rótulo:</span> <strong class="text-gray-800 dark:text-gray-200">${c.rotulo_pagamento || c.descricao_usuario || '-'}</strong></p>
                        <p><span class="text-gray-500 dark:text-gray-400">Período:</span> <strong class="text-gray-800 dark:text-gray-200">${c.mes_ref_informado ? String(c.mes_ref_informado).padStart(2, '0') : '?'}/${c.ano_ref_informado || '?'}</strong></p>
                    </div>
                    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">Recebido em: ${new Date(c.criado_em).toLocaleString('pt-BR')}</p>
                    
                    ${c.status === 'aprovado' ? `<p class="mt-2 text-xs text-green-600 dark:text-green-400"><strong>Aprovado:</strong> ${formatarMoeda(c.valor_validado)} em ${String(c.mes_ref_validado || '').padStart(2, '0')}/${c.ano_ref_validado || '?'}</p>` : ''}
                    ${c.status === 'rejeitado' ? `<p class="mt-2 text-xs text-red-600 dark:text-red-400"><strong>Motivo:</strong> ${c.motivo_rejeicao || '-'}</p>` : ''}
                    ${c.status === 'cancelado' ? `<p class="mt-2 text-xs text-gray-600 dark:text-gray-400"><strong>Cancelado:</strong> ${c.motivo_cancelamento || '-'}</p>` : ''}
                </div>
                <div class="flex-shrink-0 flex flex-wrap gap-2 justify-end">
                    ${c.status === 'pendente' ? `<button onclick="abrirValidacao(${c.id})" class="btn btn-primary">Validar</button>` : ''}
                    ${c.status === 'aprovado' ? `<button onclick="cancelarComprovante(${c.id})" class="btn btn-secondary">Desfazer aprovação</button>` : ''}
                </div>
            </div>
        </div>
        `;
    }

    function atualizarLista() {
        const filtrados = allComprovantes.filter(c => c.status === statusAtual);
        if (filtrados.length === 0) {
            ui.container.innerHTML = `<div class="text-center text-sm text-gray-500 dark:text-gray-400 py-8">Nenhum comprovante com status "${statusAtual}".</div>`;
            return;
        }
        ui.container.innerHTML = filtrados.map(renderComprovante).join('');
    }

    window.abrirValidacao = async function(id) {
        try {
            const res = await fetch(`/api/tesouraria/comprovantes/${id}`);
            const json = await res.json();
            const c = json.comprovante;

            ui.comprovanteIdInput.value = c.id;
            ui.obreiroInfo.value = c.obreiro_nome || ('Telegram: ' + c.telegram_user_id);
            ui.valorInformado.value = formatarMoeda(c.valor_informado);
            ui.periodoInformado.value = `${String(c.mes_ref_informado || '').padStart(2, '0')}/${c.ano_ref_informado || '?'}`;
            ui.dataEnvio.value = new Date(c.criado_em).toLocaleString('pt-BR');
            ui.valorValidado.value = c.valor_informado || '';
            ui.mesValidado.value = c.mes_ref_informado || new Date().getMonth() + 1;
            ui.anoValidado.value = c.ano_ref_informado || new Date().getFullYear();
            ui.rotuloPagamento.value = c.rotulo_pagamento || c.descricao_usuario || '';
            ui.categoriaId.value = c.categoria_id || '';
            await carregarObrigacoesAbertas(c.obreiro_id, c.obrigacao_parcela_id || '');

            ui.modalValidacao.classList.remove('hidden');
        } catch (err) {
            console.error('Erro ao abrir validação:', err);
            alert('Não foi possível carregar os dados do comprovante.');
        }
    }

    async function carregarObrigacoesAbertas(obreiroId, selecionada) {
        ui.obrigacaoParcelaId.innerHTML = '<option value="">Lançar sem vincular parcela</option>';
        if (!obreiroId) return;

        try {
            const res = await fetch(`/api/tesouraria/obrigacoes-abertas?obreiro_id=${encodeURIComponent(obreiroId)}`);
            const json = await res.json();
            (json.parcelas || []).forEach((p) => {
                const option = new Option(`${p.titulo} • ${p.competencia_label || '-'} • ${formatarMoeda(p.valor_previsto)}`, p.id);
                if (String(selecionada) === String(p.id)) option.selected = true;
                ui.obrigacaoParcelaId.add(option);
            });
        } catch (err) {
            console.error('Erro ao carregar obrigações abertas:', err);
        }
    }

    window.fecharModalValidacao = function() {
        ui.modalValidacao.classList.add('hidden');
        ui.formValidacao.reset();
    }

    window.rejeitarComprovante = function() {
        comprovanteSendoRejeitado = ui.comprovanteIdInput.value;
        ui.modalRejeicao.classList.remove('hidden');
    }

    window.fecharModalRejeicao = function() {
        ui.modalRejeicao.classList.add('hidden');
        ui.motivoRejeicao.value = '';
    }

    window.confirmarRejeicao = async function() {
        const motivo = ui.motivoRejeicao.value;
        if (!motivo.trim()) {
            alert('Por favor, informe um motivo para a rejeição.');
            return;
        }

        try {
            const res = await fetch('/api/tesouraria/comprovantes/rejeitar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: comprovanteSendoRejeitado, motivo })
            });
            if (!res.ok) throw new Error('Falha na API');
            fecharModalRejeicao();
            fecharModalValidacao();
            carregarComprovantes();
        } catch (err) {
            console.error('Erro ao rejeitar:', err);
            alert('Não foi possível rejeitar o comprovante.');
        }
    }

    window.cancelarComprovante = async function(id) {
        const motivo = prompt('Informe o motivo do cancelamento da aprovação:');
        if (!motivo || !motivo.trim()) {
            return;
        }

        try {
            const res = await fetch(`/api/tesouraria/comprovantes/${id}/cancelar`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ motivo: motivo.trim() })
            });
            const json = await res.json();
            if (!json.ok) {
                alert(json.erro || 'Não foi possível cancelar o comprovante.');
                return;
            }
            carregarComprovantes();
        } catch (err) {
            console.error('Erro ao cancelar comprovante:', err);
            alert('Não foi possível cancelar o comprovante.');
        }
    }

    ui.formValidacao.addEventListener('submit', async (e) => {
        e.preventDefault();
        const data = {
            id: ui.comprovanteIdInput.value,
            valor: parseFloat(ui.valorValidado.value),
            mes: parseInt(ui.mesValidado.value),
            ano: parseInt(ui.anoValidado.value),
            rotulo_pagamento: ui.rotuloPagamento.value,
            categoria_id: ui.categoriaId.value ? parseInt(ui.categoriaId.value) : null,
            obrigacao_parcela_id: ui.obrigacaoParcelaId.value ? parseInt(ui.obrigacaoParcelaId.value) : null
        };

        try {
            const res = await fetch('/api/tesouraria/comprovantes/aprovar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            if (!res.ok) throw new Error('Falha na API');
            fecharModalValidacao();
            carregarComprovantes();
        } catch (err) {
            console.error('Erro ao aprovar:', err);
            alert('Não foi possível aprovar o comprovante.');
        }
    });

    // Initial Load
    carregarComprovantes();
    setInterval(carregarComprovantes, 30000); // Refresh every 30 seconds
});
