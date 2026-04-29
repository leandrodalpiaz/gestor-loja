document.addEventListener('DOMContentLoaded', function () {
    const mesSelect = document.getElementById('filter-mes');
    const anoSelect = document.getElementById('filter-ano');
    const modal = document.getElementById('modal-regularidade');
    const form = document.getElementById('form-regularidade');

    if (!mesSelect || !anoSelect || !modal || !form) {
        console.error('Um ou mais elementos essenciais da UI não foram encontrados.');
        return;
    }

    const fetchAndRender = async () => {
        const mes = mesSelect.value;
        const ano = anoSelect.value;
        const url = `/api/tesouraria/regularidade?mes=${mes}&ano=${ano}`;

        try {
            const response = await fetch(url);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();
            render(data.regularidade || []);
        } catch (error) {
            console.error('Erro ao buscar dados de regularidade:', error);
            renderError('Não foi possível carregar os dados. Verifique a conexão e tente novamente.');
        }
    };

    const render = (items) => {
        updateSummary(items);
        renderTable(items);
        renderCards(items);
        attachEventListeners();
    };

    const renderError = (message) => {
        const tableBody = document.getElementById('regularidade-table-body');
        const cardsContainer = document.getElementById('regularidade-cards');
        if (tableBody) {
            tableBody.innerHTML = `<tr><td colspan="4" class="p-4 text-center text-red-600">${message}</td></tr>`;
        }
        if (cardsContainer) {
            cardsContainer.innerHTML = `<div class="card-body text-center text-red-600">${message}</div>`;
        }
    };

    const updateSummary = (items) => {
        const regularCount = items.filter(item => item.status === 'regular').length;
        const irregularCount = items.length - regularCount;
        document.getElementById('count-regular').textContent = regularCount;
        document.getElementById('count-irregular').textContent = irregularCount;
    };

    const createStatusBadge = (status) => {
        const isRegular = status === 'regular';
        const baseClasses = 'badge';
        const colorClasses = isRegular ? 'badge-success' : 'badge-danger';
        return `<span class="${baseClasses} ${colorClasses}">${isRegular ? 'Regular' : 'Irregular'}</span>`;
    };

    const renderTable = (items) => {
        const tableBody = document.getElementById('regularidade-table-body');
        if (!tableBody) return;

        if (items.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="4" class="p-4 text-center text-gray-500">Nenhum obreiro encontrado para este período.</td></tr>';
            return;
        }

        tableBody.innerHTML = items.map(item => `
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                <td class="p-4 font-medium">${escapeHTML(item.obreiro_nome)}</td>
                <td class="p-4">${createStatusBadge(item.status)}</td>
                <td class="p-4 text-gray-500">${escapeHTML(item.observacao) || '—'}</td>
                <td class="p-4">
                    <button class="btn btn-sm btn-ghost edit-btn"
                        data-obreiro-id="${escapeHTML(item.obreiro_id)}"
                        data-obreiro-nome="${escapeHTML(item.obreiro_nome)}"
                        data-status="${escapeHTML(item.status)}"
                        data-observacao="${escapeHTML(item.observacao || '')}">
                        Editar
                    </button>
                </td>
            </tr>
        `).join('');
    };

    const renderCards = (items) => {
        const cardsContainer = document.getElementById('regularidade-cards');
        if (!cardsContainer) return;

        if (items.length === 0) {
            cardsContainer.innerHTML = '<div class="card-body text-center text-gray-500">Nenhum obreiro encontrado para este período.</div>';
            return;
        }

        cardsContainer.innerHTML = items.map(item => `
            <div class="card">
                <div class="card-body">
                    <div class="flex justify-between items-start">
                        <span class="font-semibold">${escapeHTML(item.obreiro_nome)}</span>
                        ${createStatusBadge(item.status)}
                    </div>
                    <p class="text-sm text-gray-500 mt-2 mb-4">${escapeHTML(item.observacao) || 'Sem observação.'}</p>
                    <button class="btn btn-secondary w-full edit-btn"
                        data-obreiro-id="${escapeHTML(item.obreiro_id)}"
                        data-obreiro-nome="${escapeHTML(item.obreiro_nome)}"
                        data-status="${escapeHTML(item.status)}"
                        data-observacao="${escapeHTML(item.observacao || '')}">
                        Editar Regularidade
                    </button>
                </div>
            </div>
        `).join('');
    };

    const attachEventListeners = () => {
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', handleEditClick);
        });
    };

    const handleEditClick = (event) => {
        const button = event.currentTarget;
        const { obreiroId, obreiroNome, status, observacao } = button.dataset;

        form.querySelector('#obreiro-id').value = obreiroId;
        form.querySelector('#obreiro-nome-modal').textContent = obreiroNome;
        form.querySelector('#observacao').value = observacao;
        
        const statusInput = form.querySelector(`input[name="status"][value="${status}"]`);
        if (statusInput) {
            statusInput.checked = true;
        }

        openModal();
    };

    const openModal = () => {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    };

    const closeModal = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        form.reset();
    };

    const handleFormSubmit = async (event) => {
        event.preventDefault();
        const statusInput = form.querySelector('input[name="status"]:checked');
        if (!statusInput) {
            alert('Por favor, selecione um status.');
            return;
        }

        const data = {
            obreiro_id: form.querySelector('#obreiro-id').value,
            mes: parseInt(mesSelect.value, 10),
            ano: parseInt(anoSelect.value, 10),
            status: statusInput.value,
            observacao: form.querySelector('#observacao').value
        };

        try {
            const response = await fetch('/api/tesouraria/regularidade/definir', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            if (result.ok) {
                closeModal();
                fetchAndRender();
            } else {
                throw new Error(result.erro || 'Não foi possível salvar os dados.');
            }
        } catch (error) {
            console.error('Erro ao salvar regularidade:', error);
            alert(`Erro: ${error.message}`);
        }
    };
    
    const defineAll = async (status) => {
        if (!confirm(`Tem certeza que deseja marcar TODOS os obreiros como '${status}' para o período selecionado?`)) {
            return;
        }

        const data = {
            status: status,
            mes: parseInt(mesSelect.value, 10),
            ano: parseInt(anoSelect.value, 10)
        };

        try {
            const response = await fetch('/api/tesouraria/regularidade/definir-todos', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            if (result.ok) {
                fetchAndRender();
            } else {
                throw new Error(result.erro || 'Não foi possível completar a ação.');
            }
        } catch (error) {
            console.error('Erro ao definir todos:', error);
            alert(`Erro: ${error.message}`);
        }
    };

    // Initial setup
    mesSelect.addEventListener('change', fetchAndRender);
    anoSelect.addEventListener('change', fetchAndRender);
    form.addEventListener('submit', handleFormSubmit);
    
    document.getElementById('btn-definir-regulares').addEventListener('click', () => defineAll('regular'));
    document.getElementById('btn-definir-irregulares').addEventListener('click', () => defineAll('irregular'));
    document.getElementById('btn-cancel-modal').addEventListener('click', closeModal);
    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });

    fetchAndRender();
});

function escapeHTML(str) {
    if (typeof str !== 'string') return '';
    return str.replace(/[&<>"']/g, function (match) {
        return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        }[match];
    });
}
