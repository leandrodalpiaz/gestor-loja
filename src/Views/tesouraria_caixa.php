<?php
declare(strict_types=1);

// #############################################################################
// LÃ“GICA DE NEGÃ“CIO E HELPERS
// #############################################################################

$mesAtual = (int) date('n');
$anoAtual = (int) date('Y');

// #############################################################################
// CONFIGURAÃ‡ÃƒO DO APP SHELL
// #############################################################################

$appShellEyebrow = 'Tesouraria';
$appShellTitle = 'Livro-Caixa';
$appShellDescription = 'Entradas, saÃ­das, saldo do perÃ­odo e aÃ§Ãµes operacionais da tesouraria.';
$appShellActiveHref = '/tesouraria/caixa';

require __DIR__ . '/../partials/erp_shell_open.php';
?>

<div class="space-y-8">
    <!-- Filtros e AÃ§Ãµes -->
    <div class="card">
        <div class="card-body">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 items-end">
                <div>
                    <label for="filter-mes" class="form-label">MÃªs</label>
                    <select id="filter-mes" class="form-select">
                        <?php
                        $mesesPT = ['Janeiro', 'Fevereiro', 'MarÃ§o', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
                        for ($m = 1; $m <= 12; $m++) {
                            $selected = ($m === $mesAtual) ? 'selected' : '';
                            echo "<option value=\"$m\" $selected>{$mesesPT[$m - 1]}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div>
                    <label for="filter-ano" class="form-label">Ano</label>
                    <select id="filter-ano" class="form-select">
                        <?php
                        for ($a = $anoAtual - 2; $a <= $anoAtual; $a++) {
                            $selected = ($a === $anoAtual) ? 'selected' : '';
                            echo "<option value=\"$a\" $selected>$a</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="md:col-span-2 flex flex-col sm:flex-row gap-3 pt-4 sm:pt-0">
                    <button onclick="abrirModalLancamento('entrada')" class="btn btn-success w-full">Nova Entrada</button>
                    <button onclick="abrirModalLancamento('saida')" class="btn btn-danger w-full">Nova SaÃ­da</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MÃ©tricas -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
        <div class="card-metric"><p class="card-metric-label">Total Entradas</p><p class="card-metric-value text-green-600 dark:text-green-400" id="total-entradas">R$ 0,00</p></div>
        <div class="card-metric"><p class="card-metric-label">Total SaÃ­das</p><p class="card-metric-value text-red-600 dark:text-red-400" id="total-saidas">R$ 0,00</p></div>
        <div class="card-metric"><p class="card-metric-label">Saldo LÃ­quido</p><p class="card-metric-value" id="saldo-liquido">R$ 0,00</p></div>
    </div>

    <!-- LanÃ§amentos RÃ¡pidos -->
    <div class="card">
        <div class="card-header-subtle" onclick="toggleSugestoes()">
            <h3 class="card-title-subtle">LanÃ§amentos rÃ¡pidos</h3>
            <span id="sugestoes-toggle-icon" class="text-xs font-medium text-gray-500 dark:text-gray-400 cursor-pointer">Ocultar</span>
        </div>
        <div id="sugestoes-panel" class="card-body">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-green-600 dark:text-green-400">SugestÃµes de Entradas</p>
                    <div id="sugestoes-entradas" class="flex flex-wrap gap-2"><span class="text-sm text-gray-500">Carregando...</span></div>
                </div>
                <div>
                    <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-red-600 dark:text-red-400">SugestÃµes de SaÃ­das</p>
                    <div id="sugestoes-saidas" class="flex flex-wrap gap-2"><span class="text-sm text-gray-500">Carregando...</span></div>
                </div>
            </div>
        </div>
    </div>

    <!-- GrÃ¡ficos -->
    <div class="grid grid-cols-1 xl:grid-cols-5 gap-8">
        <div class="card xl:col-span-2">
            <div class="card-header"><h2 class="card-title">ComposiÃ§Ã£o do perÃ­odo</h2></div>
            <div class="card-body flex flex-col items-center justify-center h-80">
                <div id="chartCaixaPizza" class="h-52 w-52"></div>
                <div class="mt-4 flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-sm">
                    <div class="flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-green-500"></span><span id="legenda-entradas" class="text-gray-600 dark:text-gray-400">Entradas: R$ 0,00</span></div>
                    <div class="flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-red-500"></span><span id="legenda-saidas" class="text-gray-600 dark:text-gray-400">SaÃ­das: R$ 0,00</span></div>
                </div>
            </div>
        </div>
        <div class="card xl:col-span-3">
            <div class="card-header">
                <h2 class="card-title">EvoluÃ§Ã£o do caixa</h2>
                <p class="card-description">Comparativo entre o mÃªs anterior, o atual e uma projeÃ§Ã£o para o prÃ³ximo.</p>
            </div>
            <div class="card-body h-80">
                <div id="chartCaixaTendencia" class="h-full w-full"></div>
            </div>
        </div>
    </div>

    <!-- Tabela de LanÃ§amentos -->
    <div class="card">
        <div class="card-header"><h2 class="card-title">LanÃ§amentos do perÃ­odo</h2></div>
        <div id="lancamentos-cards" class="space-y-4 p-4 md:hidden">
            <div class="text-center text-sm text-gray-500 py-4">Carregando...</div>
        </div>
        <div class="hidden md:block overflow-x-auto">
            <table class="table-default">
                <thead>
                    <tr>
                        <th>Data</th><th>Tipo</th><th>Categoria</th><th>DescriÃ§Ã£o</th><th>Obreiro</th><th class="text-right">Valor</th><th class="text-center">AÃ§Ãµes</th>
                    </tr>
                </thead>
                <tbody id="lancamentos-table" class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr><td colspan="7" class="px-4 py-6 text-center text-gray-500">Carregando...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal de LanÃ§amento -->
<div id="modal-lancamento" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4">
    <div class="modal-content" @click.away="fecharModalLancamento()">
        <div class="modal-header">
            <h2 class="modal-title" id="modal-title">Novo LanÃ§amento</h2>
            <button class="modal-close" onclick="fecharModalLancamento()">&times;</button>
        </div>
        <form id="form-lancamento" class="modal-body">
            <input type="hidden" id="tipo-lancamento" name="tipo">
            <input type="hidden" id="lancamento_id" name="lancamento_id">

            <div><label for="categoria_id" class="form-label">Categoria *</label><select id="categoria_id" name="categoria_id" class="form-select" required></select></div>
            <div><label for="valor" class="form-label">Valor *</label><input type="number" id="valor" name="valor" step="0.01" min="0" class="form-input" required></div>
            <div><label for="data_lancamento" class="form-label">Data *</label><input type="date" id="data_lancamento" name="data_lancamento" class="form-input" required></div>
            <div><label for="descricao" class="form-label">DescriÃ§Ã£o</label><textarea id="descricao" name="descricao" rows="3" class="form-textarea"></textarea></div>
            
            <div class="modal-footer">
                <button type="button" onclick="fecharModalLancamento()" class="btn btn-secondary">Cancelar</button>
                <button type="submit" id="modal-submit-button" class="btn btn-primary">Salvar</button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../partials/erp_shell_close.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script src="/assets/js/tesouraria_caixa.js"></script>


