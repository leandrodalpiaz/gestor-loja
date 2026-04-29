<?php
declare(strict_types=1);

// #############################################################################
// LÓGICA DE NEGÓCIO E HELPERS
// #############################################################################

$mesAtual = (int) date('n');
$anoAtual = (int) date('Y');

// #############################################################################
// CONFIGURAÇÃO DO APP SHELL
// #############################################################################

$appShellEyebrow = 'Tesouraria';
$appShellTitle = 'Fechamento Mensal';
$appShellDescription = 'Conferência final do período com leitura clara de saldos e movimentos.';
$appShellActiveHref = '/tesouraria/fechamento';

require __DIR__ . '/partials/erp_shell_open.php';

?>

<?php require __DIR__ . '/partials/erp_tesouraria_topbar.php'; ?>

<div class="space-y-8">
    <!-- Filtros -->
    <div class="card">
        <div class="card-body">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
                <div>
                    <label for="filter-mes" class="form-label">Mês</label>
                    <select id="filter-mes" class="form-select">
                        <?php
                        $mesesPT = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
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
                        for ($a = $anoAtual - 1; $a <= $anoAtual; $a++) {
                            $selected = ($a === $anoAtual) ? 'selected' : '';
                            echo "<option value=\"$a\" $selected>$a</option>";
                        }
                        ?>
                    </select>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Status: <span id="status-fechamento" class="font-bold text-blue-600 dark:text-blue-400">Aberto</span></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Cards de Resumo -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="card-metric">
            <p class="card-metric-label">Saldo Inicial</p>
            <p class="card-metric-value text-blue-600 dark:text-blue-400" id="saldo-inicial">R$ 0,00</p>
            <button onclick="editarSaldoInicial()" class="text-xs font-semibold text-blue-600 dark:text-blue-400 hover:underline mt-2">EDITAR SALDO</button>
        </div>
        <div class="card-metric">
            <p class="card-metric-label">Total Entradas</p>
            <p class="card-metric-value text-green-600 dark:text-green-400" id="total-entradas">R$ 0,00</p>
        </div>
        <div class="card-metric">
            <p class="card-metric-label">Total Saídas</p>
            <p class="card-metric-value text-red-600 dark:text-red-400" id="total-saidas">R$ 0,00</p>
        </div>
        <div class="card-metric">
            <p class="card-metric-label">Saldo Final</p>
            <p class="card-metric-value text-purple-600 dark:text-purple-400" id="saldo-final">R$ 0,00</p>
        </div>
    </div>

    <!-- Ação de Fechamento -->
    <div class="card">
        <div class="card-body flex flex-col md:flex-row items-start md:items-center justify-between">
            <div>
                <h2 class="card-title">Ação de Fechamento</h2>
                <p class="card-description">Após conferir todos os lançamentos, efetue o fechamento do mês.</p>
            </div>
            <button id="btn-fechar-mes" onclick="fecharMes()" class="btn btn-primary mt-4 md:mt-0">
                Fechar Mês
            </button>
        </div>
        <div id="fechamento-content" class="border-t border-gray-200 dark:border-gray-700 p-6 hidden">
            <!-- Conteúdo carregado via JS -->
        </div>
    </div>
</div>

<!-- Modal Saldo Inicial -->
<div id="modal-saldo-inicial" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4">
    <div class="modal-content max-w-md">
        <div class="modal-header">
            <h2 class="modal-title">Definir Saldo Inicial</h2>
            <button onclick="fecharModalSaldoInicial()" class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <div>
                <label for="saldo-inicial-input" class="form-label">Valor (R$)</label>
                <input type="number" id="saldo-inicial-input" step="0.01" min="0" class="form-input">
            </div>
        </div>
        <div class="modal-footer">
            <button onclick="fecharModalSaldoInicial()" class="btn btn-secondary">Cancelar</button>
            <button onclick="salvarSaldoInicial()" class="btn btn-primary">Salvar</button>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/partials/erp_shell_close.php'; ?>

<script src="/assets/js/tesouraria_fechamento.js"></script>




