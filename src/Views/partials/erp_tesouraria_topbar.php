<?php
// Partial: Top bar padrão da Tesouraria (UX consistente).
// Espera ser incluído dentro do <main> do ERP shell.
?>
<section class="sticky top-16 z-10 -mx-4 sm:-mx-6 lg:-mx-8 mb-6 border-b border-erp-border bg-erp-surface/95 backdrop-blur">
    <div class="px-4 py-3 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-4">
                <div class="text-sm font-semibold text-erp-navy">
                    Competência <span id="teso-competencia" class="font-bold">--/----</span>
                </div>
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <a href="/tesouraria/comprovantes" class="inline-flex items-center gap-2 rounded-full border border-erp-border bg-erp-bg px-3 py-1.5 font-semibold text-erp-text hover:bg-erp-surface-2">
                        <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                        PIX pendentes <span id="teso-badge-pix" class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-[0.7rem] font-bold text-amber-800">--</span>
                    </a>
                    <a href="/tesouraria/regularidade" class="inline-flex items-center gap-2 rounded-full border border-erp-border bg-erp-bg px-3 py-1.5 font-semibold text-erp-text hover:bg-erp-surface-2">
                        <span class="h-2 w-2 rounded-full bg-rose-500"></span>
                        Irregulares <span id="teso-badge-irregular" class="ml-1 rounded-full bg-rose-100 px-2 py-0.5 text-[0.7rem] font-bold text-rose-800">--</span>
                    </a>
                    <a href="/tesouraria/obrigacoes?somente_em_aberto=1" class="inline-flex items-center gap-2 rounded-full border border-erp-border bg-erp-bg px-3 py-1.5 font-semibold text-erp-text hover:bg-erp-surface-2">
                        <span class="h-2 w-2 rounded-full bg-indigo-500"></span>
                        Parcelas em aberto <span id="teso-badge-parcelas" class="ml-1 rounded-full bg-indigo-100 px-2 py-0.5 text-[0.7rem] font-bold text-indigo-800">Ver</span>
                    </a>
                    <a href="/tesouraria/fechamento" class="inline-flex items-center gap-2 rounded-full border border-erp-border bg-erp-bg px-3 py-1.5 font-semibold text-erp-text hover:bg-erp-surface-2">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        Fechamento <span id="teso-badge-fechamento" class="ml-1 rounded-full bg-emerald-100 px-2 py-0.5 text-[0.7rem] font-bold text-emerald-800">--</span>
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap sm:items-center sm:justify-end">
                <button type="button" onclick="window.abrirModalLancamento ? window.abrirModalLancamento('entrada') : (window.location.href='/tesouraria/caixa')" class="btn btn-success">
                    Novo lançamento
                </button>
                <a href="/tesouraria/comprovantes" class="btn btn-secondary text-center">Validar PIX</a>
                <a href="/tesouraria/obrigacoes" class="btn btn-secondary text-center">Quitar parcela</a>
                <a href="/tesouraria/fechamento" class="btn btn-secondary text-center">Fechar competência</a>
                <a href="/tesouraria/relatorio-gestao" class="btn btn-secondary text-center">Exportar relatório</a>
            </div>
        </div>
    </div>
</section>

<script>
    (function () {
        function $(id) { return document.getElementById(id); }
        var competenciaEl = $('teso-competencia');
        if (!competenciaEl) return;

        function pad2(n) { return String(n).padStart(2, '0'); }
        function getMesAno() {
            var mesEl = document.getElementById('filter-mes');
            var anoEl = document.getElementById('filter-ano');
            var mes = mesEl ? parseInt(mesEl.value || '0', 10) : (new Date().getMonth() + 1);
            var ano = anoEl ? parseInt(anoEl.value || '0', 10) : (new Date().getFullYear());
            return { mes: mes || (new Date().getMonth() + 1), ano: ano || (new Date().getFullYear()) };
        }

        function syncCompetencia() {
            var ma = getMesAno();
            competenciaEl.textContent = pad2(ma.mes) + '/' + String(ma.ano);
        }

        async function safeJson(url) {
            var res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            if (!res.ok) throw new Error('http ' + res.status);
            return await res.json();
        }

        async function carregarBadges() {
            var ma = getMesAno();
            syncCompetencia();

            try {
                var pix = await safeJson('/api/tesouraria/comprovantes?status=pendente');
                var countPix = (pix.comprovantes || []).length;
                var el = $('teso-badge-pix');
                if (el) el.textContent = String(countPix);
            } catch (e) {}

            try {
                var reg = await safeJson('/api/tesouraria/regularidade?mes=' + encodeURIComponent(ma.mes) + '&ano=' + encodeURIComponent(ma.ano));
                var irregular = (reg.regularidade || []).filter(function (r) { return String(r.status || '') === 'irregular'; }).length;
                var el2 = $('teso-badge-irregular');
                if (el2) el2.textContent = String(irregular);
            } catch (e) {}

            try {
                var fech = await safeJson('/api/tesouraria/fechamento?mes=' + encodeURIComponent(ma.mes) + '&ano=' + encodeURIComponent(ma.ano));
                var status = (fech.fechamento && fech.fechamento.status) ? String(fech.fechamento.status) : '--';
                var el3 = $('teso-badge-fechamento');
                if (el3) el3.textContent = status;
            } catch (e) {}
        }

        var mesEl = document.getElementById('filter-mes');
        var anoEl = document.getElementById('filter-ano');
        if (mesEl) mesEl.addEventListener('change', carregarBadges);
        if (anoEl) anoEl.addEventListener('change', carregarBadges);

        carregarBadges();
    })();
</script>
