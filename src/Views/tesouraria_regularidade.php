<?php
declare(strict_types=1);

// #############################################################################
// CONFIGURAÃ‡ÃƒO DO APP SHELL
// #############################################################################

$appShellEyebrow = 'Tesouraria';
$appShellTitle = 'Regularidade de Obreiros';
$appShellDescription = 'Leitura clara do perÃ­odo e ediÃ§Ã£o rÃ¡pida da situaÃ§Ã£o financeira de cada obreiro.';
$appShellActiveHref = '/tesouraria/regularidade';

require __DIR__ . '/partials/erp_shell_open.php';
?>

<!-- Filtros e AÃ§Ãµes em Massa -->
<div class="card mb-6">
    <div class="card-body">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3 md:items-end">
            <div>
                <label for="filter-mes" class="form-label">MÃªs</label>
                <select id="filter-mes" class="form-select">
                    <?php
                    $mesesPT = ['Janeiro','Fevereiro','MarÃ§o','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
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

<!-- MÃ©tricas de Resumo -->
<div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
    <div class="card-metric"><p class="card-metric-label">Regulares</p><p id="count-regular" class="card-metric-value text-green-600 dark:text-green-400">0</p></div>
    <div class="card-metric"><p class="card-metric-label">Irregulares</p><p id="count-irregular" class="card-metric-value text-red-600 dark:text-red-400">0</p></div>
</div>

<!-- ConteÃºdo Principal: Tabela e Cards -->
<div class="card">
    <div class="card-body p-0">
        <!-- VisÃ£o em Cards para Mobile -->
        <div id="regularidade-cards" class="space-y-4 p-4 md:hidden">
            <div class="text-center text-gray-500 py-4">Carregando...</div>
        </div>
        <!-- VisÃ£o em Tabela para Desktop -->
        <div class="hidden md:block overflow-x-auto">
            <table class="table-default">
                <thead>
                    <tr>
                        <th class="text-left">Obreiro</th>
                        <th class="text-left">Status</th>
                        <th class="text-left">ObservaÃ§Ã£o</th>
                        <th class="text-center">AÃ§Ã£o</th>
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

<!-- Modal para EdiÃ§Ã£o -->
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
                <label for="observacao" class="form-label">ObservaÃ§Ã£o</label>
                <textarea id="observacao" rows="3" class="form-textarea"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" id="btn-cancel-modal" class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn btn-primary">Salvar</button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/partials/erp_shell_close.php'; ?>

<script src="/assets/js/tesouraria_regularidade.js"></script>



