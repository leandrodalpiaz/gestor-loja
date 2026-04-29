document.addEventListener('DOMContentLoaded', function () {
    // Smooth scroll to detail view if hash is present
    const detalhe = document.getElementById('detalhe-individual');
    if (detalhe && window.location.hash === '#detalhe-individual') {
        detalhe.scrollIntoView({ behavior: 'smooth', block: 'start' });
        detalhe.classList.add('ring-2', 'ring-offset-2', 'ring-blue-500');
        setTimeout(() => detalhe.classList.remove('ring-2', 'ring-offset-2', 'ring-blue-500'), 2500);
    }

    // Form logic for creating new obligations
    const form = document.querySelector('form[action="/tesouraria/obrigacoes/criar"]');
    if (!form) return;

    const tipo = form.querySelector('#tipo_obrigacao');
    const recorrencia = form.querySelector('#recorrencia');
    const titulo = form.querySelector('#titulo_obrigacao');
    const valorBase = form.querySelector('#valor_base');
    const parcelasTotal = form.querySelector('#parcelas_total');
    const inicio = form.querySelector('#inicio_competencia');
    const fim = form.querySelector('#fim_competencia');

    // These values are passed from the PHP view
    const salarioMinimo = window.SALARIO_MINIMO_PADRAO || 1621.00;
    const mensalidadePadrao = window.MENSALIDADE_PADRAO || 150.00;
    const bibliotecaPadrao = window.BIBLIOTECA_PADRAO || 44.00;

    function preencherDefaultsPorTipo() {
        const tipoValor = tipo.value;
        switch (tipoValor) {
            case 'mensalidade':
                valorBase.value = Number(mensalidadePadrao).toFixed(2);
                titulo.value = 'Contribuição mensal da Loja';
                recorrencia.value = 'mensal';
                parcelasTotal.value = '1';
                break;
            case 'biblioteca':
                valorBase.value = Number(bibliotecaPadrao).toFixed(2);
                titulo.value = 'Contribuição Biblioteca';
                recorrencia.value = 'mensal';
                parcelasTotal.value = '1';
                break;
            case 'joia':
                valorBase.value = Number(salarioMinimo).toFixed(2);
                titulo.value = 'Jóia de Admissão';
                recorrencia.value = 'parcelado';
                parcelasTotal.value = '5';
                break;
            default:
                titulo.value = '';
                break;
        }
        atualizarFimCompetencia();
    }

    function atualizarFimCompetencia() {
        const recValue = recorrencia.value;
        const parcelas = parseInt(parcelasTotal.value, 10) || 1;
        
        if (recValue === 'mensal' || recValue === 'anual') {
            parcelasTotal.value = '1';
            fim.value = inicio.value;
            parcelasTotal.readOnly = true;
            fim.readOnly = true;
        } else if (recValue === 'parcelado') {
            parcelasTotal.readOnly = false;
            fim.readOnly = true;
            const inicioData = new Date(inicio.value + 'T00:00:00');
            if (!isNaN(inicioData.getTime())) {
                inicioData.setMonth(inicioData.getMonth() + parcelas - 1);
                fim.value = inicioData.toISOString().slice(0, 10);
            }
        } else { // avulsa
            parcelasTotal.value = '1';
            parcelasTotal.readOnly = true;
            fim.readOnly = false;
        }
    }

    tipo.addEventListener('change', preencherDefaultsPorTipo);
    recorrencia.addEventListener('change', atualizarFimCompetencia);
    parcelasTotal.addEventListener('input', atualizarFimCompetencia);
    inicio.addEventListener('change', atualizarFimCompetencia);

    // Initial setup
    preencherDefaultsPorTipo();
    atualizarFimCompetencia();
});
