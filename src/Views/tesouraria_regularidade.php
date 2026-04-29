<?php
declare(strict_types=1);

// #############################################################################
// CONFIGURAÇÃO DO APP SHELL
// #############################################################################

$appShellEyebrow = 'Tesouraria';
$appShellTitle = 'Regularidade de Obreiros';
$appShellDescription = 'Leitura clara do período e edição rápida da situação financeira de cada obreiro.';
$appShellActiveHref = '/tesouraria/regularidade';

require __DIR__ . '/partials/erp_shell_open.php';
?>

<!-- Filtros e Ações em Massa -->
<div class="card mb-6">
    <div class="card-body">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3 md:items-end">
            <div>
                <label for="filter-mes" class="form-label">Mês</label>
                <select id="filter-mes" class="form-select">
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
                <label for="filter-ano" class="form-label">Ano</label>
                <select id="filter-ano" class="form-select">
                    <?php
                    $anoAtual = (int) date('Y');
                    for ($a = $anoAtual - 1; $a <= $anoAtual; $a++) {
                        $selected = ($a === $anoAtual) ? 'selected' : '';
                        echo "<option value=\"$a\" $selected>$a</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row">
                <button id="btn-definir-regulares" class="btn btn-success w-full">Todos Regulares</button>
                <button id="btn-definir-irregulares" class="btn btn-danger w-full">Todos Irregulares</button>
            </div>
        </div>
    </div>
</div>

<!-- Métricas de Resumo -->
<div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
    <div class="card-metric"><p class="card-metric-label">Regulares</p><p id="count-regular" class="card-metric-value text-green-600 dark:text-green-400">0</p></div>
    <div class="card-metric"><p class="card-metric-label">Irregulares</p><p id="count-irregular" class="card-metric-value text-red-600 dark:text-red-400">0</p></div>
</div>

<!-- Conteúdo Principal: Tabela e Cards -->
<div class="card">
    <div class="card-body p-0">
        <!-- Visão em Cards para Mobile -->
        <div id="regularidade-cards" class="space-y-4 p-4 md:hidden">
            <div class="text-center text-gray-500 py-4">Carregando...</div>
        </div>
        <!-- Visão em Tabela para Desktop -->
        <div class="hidden md:block overflow-x-auto">
            <table class="table-default">
                <thead>
                    <tr>
                        <th class="text-left">Obreiro</th>
                        <th class="text-left">Status</th>
                        <th class="text-left">Observação</th>
                        <th class="text-center">Ação</th>
                    </tr>
                </thead>
                <tbody id="regularidade-table-body">
                    <tr>
                        <td colspan="4" class="p-4 text-center text-gray-500">Carregando...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para Edição -->
<div id="modal-regularidade" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4" x-cloak>
    <div class="modal-content max-w-md" @click.away="fecharModalRegularidade()">
        <div class="modal-header">
            <h2 class="modal-title">Definir Regularidade</h2>
            <p id="obreiro-nome-modal" class="card-description"></p>
        </div>
        <form id="form-regularidade" class="modal-body">
            <input type="hidden" id="obreiro-id">
            <div>
                <label class="form-label">Status *</label>
                <div class="flex gap-4 mt-2">
                    <label class="flex items-center"><input type="radio" name="status" value="regular" class="form-radio" required><span class="ml-2">Regular</span></label>
                    <label class="flex items-center"><input type="radio" name="status" value="irregular" class="form-radio" required><span class="ml-2">Irregular</span></label>
                </div>
            </div>
            <div>
                <label for="observacao" class="form-label">Observação</label>
                <textarea id="observacao" rows="3" class="form-textarea"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" id="btn-cancel-modal" class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn btn-primary">Salvar</button>
            </div>
        </form>
    </div>
</div>

<style>
    .card { @apply bg-white dark:bg-gray-800 rounded-lg shadow-md; }
    .card-header { @apply p-5 border-b border-gray-200 dark:border-gray-700; }
    .card-title { @apply text-lg font-bold text-gray-800 dark:text-gray-100; }
    .card-description { @apply mt-1 text-sm text-gray-600 dark:text-gray-400; }
    .card-body { @apply p-5; }

    .card-metric { @apply bg-white dark:bg-gray-800 rounded-lg shadow-md p-5; }
    .card-metric-label { @apply text-sm font-medium text-gray-500 dark:text-gray-400; }
    .card-metric-value { @apply mt-1 text-3xl font-bold; }

    .form-label { @apply block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1; }
    .form-select, .form-textarea { @apply w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500; }
    .form-radio { @apply h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500; }

    .table-default { @apply min-w-full text-sm; }
    .table-default thead { @apply bg-gray-50 dark:bg-gray-700/50; }
    .table-default th { @apply px-4 py-3 font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider text-xs; }
    .table-default tbody { @apply divide-y divide-gray-200 dark:divide-gray-700; }
    .table-default td { @apply px-4 py-3; }

    .badge { @apply inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold; }
    .badge-success { @apply bg-green-100 text-green-800 dark:bg-green-800/30 dark:text-green-200; }
    .badge-danger { @apply bg-red-100 text-red-800 dark:bg-red-800/30 dark:text-red-200; }
    
    .modal-content { @apply w-full rounded-xl bg-white dark:bg-gray-800 shadow-xl; }
    .modal-header { @apply flex justify-between items-center p-5 border-b border-gray-200 dark:border-gray-700; }
    .modal-title { @apply text-lg font-bold text-gray-900 dark:text-gray-100; }
    .modal-body { @apply p-5 space-y-4; }
    .modal-footer { @apply flex justify-end gap-3 p-5 border-t border-gray-200 dark:border-gray-700; }
</style>

<?php require __DIR__ . '/partials/erp_shell_close.php'; ?>

<script src="/assets/js/tesouraria_regularidade.js"></script>


