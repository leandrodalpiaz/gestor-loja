<?php
// src/Views/tesouraria_caixa.php
if (!isset($_SESSION["usuario_logado"])) {
    header("Location: /login");
    exit;
}

$erpPageTitle = 'Livro-Caixa';
$appShellEyebrow = 'Tesouraria';
$appShellTitle = 'Livro-Caixa';
$appShellDescription = 'Entradas, saídas, saldo do período e ações operacionais da tesouraria em leitura administrativa direta.';
$appShellActiveHref = '/tesouraria/caixa';
$appShellActions = [
    ['label' => 'Voltar ao dashboard', 'href' => '/dashboard'],
    ['label' => 'Obrigações', 'href' => '/tesouraria/obrigacoes', 'primary' => true],
];
$appShellSidebarSections = [
    [
        'title' => 'Tesouraria',
        'items' => [
            ['label' => 'Livro-Caixa', 'href' => '/tesouraria/caixa'],
            ['label' => 'Obrigações', 'href' => '/tesouraria/obrigacoes'],
            ['label' => 'Comprovantes', 'href' => '/tesouraria/comprovantes'],
            ['label' => 'Regularidade', 'href' => '/tesouraria/regularidade'],
            ['label' => 'Dashboard', 'href' => '/dashboard'],
        ],
    ],
];

require __DIR__ . '/partials/erp_head.php';
require __DIR__ . '/partials/erp_shell_open.php';
?>
        <?php
        $dashboard = [
            'title' => 'Dashboard financeiro operacional',
            'subtitle' => 'Controle rigoroso da Tesouraria com foco em inadimplência e operação rápida.',
            'meta' => [
                'Perfil: tesoureiro',
                'Fonte oficial: tesouraria',
                'Miniapp alinhado ao cargo',
            ],
            'actions' => [
                ['label' => 'Criar obrigação', 'href' => '/tesouraria/obrigacoes'],
                ['label' => 'Gerar mensalidades', 'href' => '/tesouraria/obrigacoes'],
                ['label' => 'Quitar parcela', 'href' => '/tesouraria/obrigacoes'],
                ['label' => 'Emitir recibo', 'href' => '/tesouraria/obrigacoes'],
                ['label' => 'Criar isenção', 'href' => '/tesouraria/obrigacoes'],
                ['label' => 'Abrir miniapp da tesouraria', 'href' => '/miniapp/tesouraria'],
            ],
            'blocks' => [
                [
                    'title' => 'Caixa e inadimplência',
                    'subtitle' => 'Resumo operacional de caixa e risco financeiro.',
                    'span' => 'half',
                    'metrics' => [
                        ['label' => 'Entradas', 'value' => 'R$ 0,00', 'hint' => 'Atualizado pelo periodo filtrado'],
                        ['label' => 'Saídas', 'value' => 'R$ 0,00', 'hint' => 'Atualizado pelo periodo filtrado'],
                        ['label' => 'Saldo líquido', 'value' => 'R$ 0,00'],
                    ],
                    'list' => [
                        ['item' => 'Obrigações em atraso', 'meta' => 'Operar no módulo de obrigações', 'status' => 'Prioritário'],
                        ['item' => 'Comprovantes pendentes', 'meta' => 'Validação diária', 'status' => 'Prioritário'],
                    ],
                ],
                [
                    'title' => 'Sessões e operação financeira',
                    'subtitle' => 'Impacto financeiro das sessões e atalhos operacionais.',
                    'span' => 'half',
                    'metrics' => [
                        ['label' => 'Comprovantes', 'value' => 'Validação ativa'],
                        ['label' => 'Relatórios', 'value' => 'Fechamento e gestão'],
                    ],
                    'list' => [
                        ['item' => 'Sessões com impacto financeiro', 'meta' => 'Acompanhar em /tesouraria/sessoes', 'status' => 'Ativo'],
                        ['item' => 'Minhas obrigações', 'meta' => 'Consulta individual', 'status' => 'Ativo'],
                    ],
                ],
            ],
            'alerts' => [
                ['title' => 'Inadimplência visível', 'text' => 'Manter obrigações atrasadas e comprovantes como prioridade diária.', 'tone' => 'danger'],
            ],
            'activity' => [
                ['item' => 'Livro-caixa em operação', 'meta' => 'Lançamentos e filtros por competência'],
                ['item' => 'Comprovantes e obrigações', 'meta' => 'Fluxo financeiro oficial da tesouraria'],
            ],
            'links' => [
                ['label' => 'Obrigações', 'href' => '/tesouraria/obrigacoes'],
                ['label' => 'Comprovantes', 'href' => '/tesouraria/comprovantes'],
                ['label' => 'Regularidade', 'href' => '/tesouraria/regularidade'],
                ['label' => 'Relatório de gestão', 'href' => '/tesouraria/relatorio-gestao'],
            ],
        ];

        $dashboardRenderers = [
            static function (array $block): void {
                $dashboardMetrics = is_array($block['metrics'] ?? null) ? $block['metrics'] : [];
                $dashboardListItems = is_array($block['list'] ?? null) ? $block['list'] : [];
                require __DIR__ . '/components/dashboard_metrics.php';
                echo '<div class="mt-3">';
                require __DIR__ . '/components/dashboard_list.php';
                echo '</div>';
            },
            static function (array $block): void {
                $dashboardMetrics = is_array($block['metrics'] ?? null) ? $block['metrics'] : [];
                $dashboardListItems = is_array($block['list'] ?? null) ? $block['list'] : [];
                require __DIR__ . '/components/dashboard_metrics.php';
                echo '<div class="mt-3">';
                require __DIR__ . '/components/dashboard_list.php';
                echo '</div>';
            },
        ];
        require __DIR__ . '/layouts/dashboard.php';
        ?>
        <section class="mb-6 grid gap-3 md:grid-cols-3">
            <article class="rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400">Movimentação do caixa</p>
                <p class="mt-2 text-base font-semibold text-slate-900">Registre entradas e saídas do período</p>
                <p class="mt-1 text-sm text-slate-600">Os lançamentos rápidos continuam disponíveis logo abaixo.</p>
            </article>
            <article class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-4 shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-emerald-700">Entradas</p>
                <p class="mt-2 text-2xl font-semibold text-emerald-800" id="resumo-entradas-mobile">R$ 0,00</p>
            </article>
            <article class="rounded-2xl border border-red-200 bg-red-50 px-4 py-4 shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-red-700">Saídas</p>
                <p class="mt-2 text-2xl font-semibold text-red-800" id="resumo-saidas-mobile">R$ 0,00</p>
            </article>
        </section>

        <div class="mb-6 rounded-3xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4 md:items-end">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Mês</label>
                    <select id="filter-mes" class="w-full rounded border border-gray-300 px-3 py-2" onchange="filtrarCaixa()">
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
                    <label class="mb-1 block text-sm font-medium text-gray-700">Ano</label>
                    <select id="filter-ano" class="w-full rounded border border-gray-300 px-3 py-2" onchange="filtrarCaixa()">
                        <?php
                        $anoAtual = (int) date('Y');
                        for ($a = $anoAtual - 2; $a <= $anoAtual; $a++) {
                            $selected = ($a === $anoAtual) ? 'selected' : '';
                            echo "<option value=\"$a\" $selected>$a</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="md:col-span-2 flex flex-col gap-2 sm:flex-row">
                    <button onclick="abrirModalEntrada()" class="flex-1 rounded bg-green-700 px-4 py-2 text-sm font-medium text-white hover:bg-green-800">
                        Nova Entrada
                    </button>
                    <button onclick="abrirModalSaida()" class="flex-1 rounded bg-red-700 px-4 py-2 text-sm font-medium text-white hover:bg-red-800">
                        Nova Saída
                    </button>
                </div>
            </div>
        </div>

        <div class="mb-6 rounded-3xl border border-gray-200 bg-white shadow-sm">
            <div class="flex cursor-pointer items-center justify-between border-b border-gray-200 p-4 select-none" onclick="toggleSugestoes()">
                <h2 class="font-semibold text-gray-700">Lançamentos rápidos</h2>
                <span id="sugestoes-toggle-icon" class="text-xs font-medium text-gray-400">Ocultar</span>
            </div>
            <div id="sugestoes-panel" class="p-4">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-green-700">Entradas</p>
                        <div id="sugestoes-entradas" class="flex flex-wrap gap-2">
                            <span class="text-sm text-gray-400">Carregando...</span>
                        </div>
                    </div>
                    <div>
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-red-700">Saídas</p>
                        <div id="sugestoes-saidas" class="flex flex-wrap gap-2">
                            <span class="text-sm text-gray-400">Carregando...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="rounded-lg border border-green-200 bg-green-50 p-4">
                <p class="text-sm font-medium text-green-600">Total Entradas</p>
                <p class="text-2xl font-bold text-green-700" id="total-entradas">R$ 0,00</p>
            </div>
            <div class="rounded-lg border border-red-200 bg-red-50 p-4">
                <p class="text-sm font-medium text-red-600">Total Saídas</p>
                <p class="text-2xl font-bold text-red-700" id="total-saidas">R$ 0,00</p>
            </div>
            <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
                <p class="text-sm font-medium text-blue-600">Saldo Líquido</p>
                <p class="text-2xl font-bold text-blue-700" id="saldo-liquido">R$ 0,00</p>
            </div>
        </div>

        <div class="mb-6 grid grid-cols-1 gap-4 xl:grid-cols-5">
            <div class="min-h-[320px] rounded-lg border border-gray-200 bg-white p-4 shadow-sm xl:col-span-2">
                <h2 class="mb-4 font-semibold">Composição do período</h2>
                <div class="flex h-72 flex-col items-center justify-center gap-4">
                    <div id="chartCaixaPizza" class="h-52 w-52 rounded-full border border-gray-200" aria-label="Gráfico de composição do período"></div>
                    <div class="flex flex-wrap items-center justify-center gap-4 text-sm">
                        <div class="flex items-center gap-2">
                            <span class="inline-block h-3 w-3 rounded-full bg-green-600"></span>
                            <span id="legenda-entradas">Entradas: R$ 0,00</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="inline-block h-3 w-3 rounded-full bg-red-600"></span>
                            <span id="legenda-saidas">Saídas: R$ 0,00</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="min-h-[320px] rounded-lg border border-gray-200 bg-white p-4 shadow-sm xl:col-span-3">
                <h2 class="mb-1 font-semibold">Evolução do caixa</h2>
                <p class="mb-4 text-xs text-gray-500">Compara o mês anterior, o mês atual e uma estimativa simples do próximo período.</p>
                <div id="chartCaixaTendencia" class="h-72"></div>
            </div>
        </div>

        <div class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 bg-gray-50 p-4">
                <h2 class="font-semibold">Lançamentos do período</h2>
            </div>
            <div id="lancamentos-cards" class="space-y-3 p-4 md:hidden">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-500">Carregando...</div>
            </div>
            <div class="hidden overflow-x-auto md:block">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-gray-200 bg-gray-100">
                        <tr>
                            <th class="px-4 py-2 text-left">Data</th>
                            <th class="px-4 py-2 text-left">Tipo</th>
                            <th class="px-4 py-2 text-left">Categoria</th>
                            <th class="px-4 py-2 text-left">Descrição</th>
                            <th class="px-4 py-2 text-left">Obreiro</th>
                            <th class="px-4 py-2 text-right">Valor</th>
                            <th class="px-4 py-2 text-center">Ação</th>
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

    <div id="modal-lancamento" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-40 p-4">
        <div class="w-full max-w-lg rounded-lg bg-white shadow-lg">
            <div class="border-b border-gray-200 p-6">
                <h2 class="text-lg font-bold" id="modal-title">Nova Entrada</h2>
            </div>
            <form id="form-lancamento" class="space-y-4 p-6">
                <input type="hidden" id="tipo-lancamento" value="entrada">

                <div>
                    <label class="mb-1 block text-sm font-medium">Categoria *</label>
                    <select id="categoria_id" class="w-full rounded border border-gray-300 px-3 py-2" required>
                        <option value="">Selecione uma categoria</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Valor *</label>
                    <input type="number" id="valor" step="0.01" min="0" class="w-full rounded border border-gray-300 px-3 py-2" required>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Data *</label>
                    <input type="date" id="data_lancamento" class="w-full rounded border border-gray-300 px-3 py-2" required>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Descrição</label>
                    <textarea id="descricao" rows="3" class="w-full rounded border border-gray-300 px-3 py-2"></textarea>
                </div>

                <div class="flex gap-2">
                    <button type="button" onclick="fecharModalLancamento()" class="flex-1 rounded bg-gray-200 px-4 py-2 text-gray-800 hover:bg-gray-300">Cancelar</button>
                    <button type="submit" class="flex-1 rounded bg-blue-700 px-4 py-2 text-white hover:bg-blue-800">Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const nomesMeses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
        document.getElementById('data_lancamento').valueAsDate = new Date();

        function toggleSugestoes() {
            const panel = document.getElementById('sugestoes-panel');
            const icon = document.getElementById('sugestoes-toggle-icon');
            if (panel.classList.contains('hidden')) {
                panel.classList.remove('hidden');
                icon.textContent = 'Ocultar';
            } else {
                panel.classList.add('hidden');
                icon.textContent = 'Mostrar';
            }
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
                    <button onclick="lancarRapido(${c.id}, '${tipo}')"
                        class="rounded-full border px-3 py-1.5 text-xs font-medium transition-colors ${
                            tipo === 'entrada'
                                ? 'border-green-300 text-green-700 hover:bg-green-50'
                                : 'border-red-300 text-red-700 hover:bg-red-50'
                        }">
                        ${c.nome}
                    </button>
                `).join('');

                document.getElementById('sugestoes-entradas').innerHTML = renderPills(entradas, 'entrada') || '<span class="text-sm text-gray-400">Nenhuma categoria</span>';
                document.getElementById('sugestoes-saidas').innerHTML = renderPills(saidas, 'saida') || '<span class="text-sm text-gray-400">Nenhuma categoria</span>';
            } catch (err) {
                console.error('Erro ao carregar sugestões:', err);
            }
        }

        async function lancarRapido(categoriaId, tipo) {
            document.getElementById('tipo-lancamento').value = tipo;
            document.getElementById('modal-title').textContent = tipo === 'entrada' ? 'Nova Entrada' : 'Nova Saída';
            await carregarCategorias(tipo);
            const select = document.getElementById('categoria_id');
            select.value = categoriaId;
            const nomeCategoria = select.options[select.selectedIndex]?.text ?? '';
            if (nomeCategoria) {
                document.getElementById('modal-title').textContent =
                    (tipo === 'entrada' ? 'Nova Entrada' : 'Nova Saída') + ` - ${nomeCategoria}`;
            }
            document.getElementById('modal-lancamento').classList.remove('hidden');
            document.getElementById('modal-lancamento').classList.add('flex');
        }

        async function abrirModalEntrada() {
            document.getElementById('tipo-lancamento').value = 'entrada';
            document.getElementById('modal-title').textContent = 'Nova Entrada';
            await carregarCategorias('entrada');
            document.getElementById('modal-lancamento').classList.remove('hidden');
            document.getElementById('modal-lancamento').classList.add('flex');
        }

        async function abrirModalSaida() {
            document.getElementById('tipo-lancamento').value = 'saida';
            document.getElementById('modal-title').textContent = 'Nova Saída';
            await carregarCategorias('saida');
            document.getElementById('modal-lancamento').classList.remove('hidden');
            document.getElementById('modal-lancamento').classList.add('flex');
        }

        function fecharModalLancamento() {
            const modal = document.getElementById('modal-lancamento');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.getElementById('form-lancamento').reset();
        }

        async function carregarCategorias(tipo) {
            try {
                const res = await fetch(`/api/tesouraria/categorias?tipo=${tipo}`);
                const json = await res.json();
                const select = document.getElementById('categoria_id');
                select.innerHTML = '<option value="">Selecione uma categoria</option>';
                (json.categorias || []).forEach(cat => {
                    select.innerHTML += `<option value="${cat.id}">${cat.nome}</option>`;
                });
            } catch (err) {
                console.error('Erro ao carregar categorias:', err);
            }
        }

        function formatarMoeda(valor) {
            return Number(valor || 0).toLocaleString('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            });
        }

        function obterPeriodoAnterior(mes, ano) {
            if (mes === 1) {
                return { mes: 12, ano: ano - 1 };
            }
            return { mes: mes - 1, ano };
        }

        function obterProximoPeriodo(mes, ano) {
            if (mes === 12) {
                return { mes: 1, ano: ano + 1 };
            }
            return { mes: mes + 1, ano };
        }

        function projetarProximoPeriodo(totaisAnterior, totaisAtual) {
            const entradaAnterior = Number(totaisAnterior.entrada || 0);
            const entradaAtual = Number(totaisAtual.entrada || 0);
            const saidaAnterior = Number(totaisAnterior.saida || 0);
            const saidaAtual = Number(totaisAtual.saida || 0);

            return {
                entrada: Number(((entradaAnterior + entradaAtual) / 2).toFixed(2)),
                saida: Number(((saidaAnterior + saidaAtual) / 2).toFixed(2)),
            };
        }

        async function buscarTotaisPeriodo(mes, ano) {
            const res = await fetch(`/api/tesouraria/caixa?mes=${mes}&ano=${ano}`);
            const json = await res.json();
            return json.totais || { entrada: 0, saida: 0 };
        }

        async function filtrarCaixa() {
            const mes = Number(document.getElementById('filter-mes').value);
            const ano = Number(document.getElementById('filter-ano').value);
            try {
                const res = await fetch(`/api/tesouraria/caixa?mes=${mes}&ano=${ano}`);
                const json = await res.json();
                atualizarTabelaCaixa(json.lancamentos || [], json.totais || {});
                await atualizarGraficos(mes, ano, json.totais || {});
            } catch (err) {
                console.error('Erro ao carregar caixa:', err);
            }
        }

        function atualizarTabelaCaixa(lancamentos, totais) {
            const tbody = document.getElementById('lancamentos-table');
            const cards = document.getElementById('lancamentos-cards');
            if (lancamentos.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="px-4 py-4 text-center text-gray-500">Nenhum lançamento neste período</td></tr>';
                cards.innerHTML = '<div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-500">Nenhum lançamento neste período.</div>';
            } else {
                tbody.innerHTML = lancamentos.map(l => `
                    <tr class="border-b border-gray-200 hover:bg-gray-50">
                        <td class="px-4 py-2">${new Date(l.data_lancamento).toLocaleDateString('pt-BR')}</td>
                        <td class="px-4 py-2">
                            <span class="rounded px-2 py-1 text-xs font-semibold ${
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
                            ${l.tipo === 'entrada' ? '+' : '-'} ${formatarMoeda(l.valor)}
                        </td>
                        <td class="px-4 py-2 text-center">
                            <button onclick="deletarLancamento(${l.id})" class="text-xs text-red-600 hover:text-red-800">Excluir</button>
                        </td>
                    </tr>
                `).join('');

                cards.innerHTML = lancamentos.map(l => `
                    <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="text-base font-semibold text-slate-900">${l.categoria_nome}</div>
                                <div class="mt-1 text-sm text-slate-600">${new Date(l.data_lancamento).toLocaleDateString('pt-BR')}</div>
                            </div>
                            <span class="rounded-full px-3 py-1 text-xs font-semibold ${l.tipo === 'entrada' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}">
                                ${l.tipo === 'entrada' ? 'Entrada' : 'Saída'}
                            </span>
                        </div>
                        <div class="mt-3 text-sm text-slate-600">
                            <div>Descrição: ${l.descricao || '-'}</div>
                            <div>Obreiro: ${l.obreiro_nome || '-'}</div>
                        </div>
                        <div class="mt-4 flex items-center justify-between gap-3">
                            <div class="text-lg font-semibold ${l.tipo === 'entrada' ? 'text-green-700' : 'text-red-700'}">
                                ${l.tipo === 'entrada' ? '+' : '-'} ${formatarMoeda(l.valor)}
                            </div>
                            <button onclick="deletarLancamento(${l.id})" class="rounded border border-red-200 px-3 py-2 text-sm text-red-700 hover:bg-red-50">
                                Excluir
                            </button>
                        </div>
                    </article>
                `).join('');
            }

            document.getElementById('total-entradas').textContent = formatarMoeda(totais.entrada);
            document.getElementById('total-saidas').textContent = formatarMoeda(totais.saida);
            document.getElementById('saldo-liquido').textContent = formatarMoeda((totais.entrada || 0) - (totais.saida || 0));
            document.getElementById('resumo-entradas-mobile').textContent = formatarMoeda(totais.entrada);
            document.getElementById('resumo-saidas-mobile').textContent = formatarMoeda(totais.saida);
        }

        function atualizarGraficoPizza(totais) {
            const entradas = Number(totais.entrada || 0);
            const saidas = Number(totais.saida || 0);
            const total = entradas + saidas;
            const percentualEntradas = total > 0 ? (entradas / total) * 100 : 50;

            const grafico = document.getElementById('chartCaixaPizza');
            grafico.style.background = `conic-gradient(#16a34a 0% ${percentualEntradas}%, #dc2626 ${percentualEntradas}% 100%)`;

            if (total === 0) {
                grafico.style.background = 'conic-gradient(#d1d5db 0% 100%)';
            }

            document.getElementById('legenda-entradas').textContent = `Entradas: ${formatarMoeda(entradas)}`;
            document.getElementById('legenda-saidas').textContent = `Saidas: ${formatarMoeda(saidas)}`;
        }

        function atualizarGraficoTendencia(labels, totaisAnterior, totaisAtual, totaisProjecao) {
            const entradas = [totaisAnterior.entrada, totaisAtual.entrada, totaisProjecao.entrada].map(valor => Number(valor || 0));
            const saidas = [totaisAnterior.saida, totaisAtual.saida, totaisProjecao.saida].map(valor => Number(valor || 0));
            const saldos = entradas.map((entrada, index) => Number((entrada - saidas[index]).toFixed(2)));

            const container = document.getElementById('chartCaixaTendencia');
            const maiorValor = Math.max(...entradas, ...saidas, ...saldos.map(valor => Math.abs(valor)), 1);
            const pontosLinha = saldos.map((saldo, index) => {
                const x = 40 + (index * 180);
                const y = 220 - ((saldo + maiorValor) / (maiorValor * 2)) * 180;
                return `${x},${y}`;
            }).join(' ');

            container.innerHTML = `
                <div class="flex h-full flex-col gap-3">
                    <div class="flex items-center justify-center gap-4 text-xs text-gray-600">
                        <span class="flex items-center gap-2"><span class="inline-block h-3 w-3 rounded-sm bg-green-500"></span>Entradas</span>
                        <span class="flex items-center gap-2"><span class="inline-block h-3 w-3 rounded-sm bg-red-500"></span>Saidas</span>
                        <span class="flex items-center gap-2"><span class="inline-block h-0.5 w-4 bg-blue-600"></span>Saldo Liquido</span>
                    </div>
                    <div class="grid flex-1 grid-cols-3 items-end gap-4">
                        ${labels.map((label, index) => {
                            const entradaAltura = Math.max((entradas[index] / maiorValor) * 140, entradas[index] > 0 ? 10 : 2);
                            const saidaAltura = Math.max((saidas[index] / maiorValor) * 140, saidas[index] > 0 ? 10 : 2);
                            return `
                                <div class="relative flex h-full flex-col items-center justify-end gap-3">
                                    <div class="min-h-[32px] text-center text-xs text-gray-500">${label}</div>
                                    <div class="flex h-40 items-end gap-2">
                                        <div class="w-10 rounded-t-md bg-green-500" style="height:${entradaAltura}px" title="Entradas: ${formatarMoeda(entradas[index])}"></div>
                                        <div class="w-10 rounded-t-md bg-red-500" style="height:${saidaAltura}px" title="Saidas: ${formatarMoeda(saidas[index])}"></div>
                                    </div>
                                    <div class="text-xs font-semibold ${saldos[index] >= 0 ? 'text-blue-700' : 'text-red-700'}">Saldo: ${formatarMoeda(saldos[index])}</div>
                                </div>
                            `;
                        }).join('')}
                    </div>
                    <div class="-mt-44 pointer-events-none">
                        <svg viewBox="0 0 400 220" class="h-44 w-full overflow-visible">
                            <polyline fill="none" stroke="#2563eb" stroke-width="3" points="${pontosLinha}" />
                            ${saldos.map((saldo, index) => {
                                const x = 40 + (index * 180);
                                const y = 220 - ((saldo + maiorValor) / (maiorValor * 2)) * 180;
                                return `<circle cx="${x}" cy="${y}" r="5" fill="#2563eb"></circle>`;
                            }).join('')}
                        </svg>
                    </div>
                </div>
            `;
        }

        async function atualizarGraficos(mes, ano, totaisAtual) {
            atualizarGraficoPizza(totaisAtual);

            const periodoAnterior = obterPeriodoAnterior(mes, ano);
            const proximoPeriodo = obterProximoPeriodo(mes, ano);
            const totaisAnterior = await buscarTotaisPeriodo(periodoAnterior.mes, periodoAnterior.ano);
            const totaisProjecao = projetarProximoPeriodo(totaisAnterior, totaisAtual);

            atualizarGraficoTendencia([
                nomesMeses[periodoAnterior.mes - 1],
                nomesMeses[mes - 1],
                `${nomesMeses[proximoPeriodo.mes - 1]} (proj.)`
            ], totaisAnterior, totaisAtual, totaisProjecao);
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
            if (!confirm('Tem certeza que deseja excluir este lancamento?')) return;
            try {
                const res = await fetch(`/api/tesouraria/lancamento/${id}`, { method: 'DELETE' });
                const json = await res.json();
                if (json.ok) {
                    filtrarCaixa();
                }
            } catch (err) {
                console.error('Erro ao excluir:', err);
            }
        }

        window.addEventListener('load', () => {
            filtrarCaixa();
            carregarSugestoes();
        });
    </script>
<?php require __DIR__ . '/partials/erp_shell_close.php'; ?>
