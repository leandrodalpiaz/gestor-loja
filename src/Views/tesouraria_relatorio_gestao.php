<?php
declare(strict_types=1);

/** @var array<int, array<string, mixed>> $gestoes */
/** @var array<string, mixed> $relatorio */

// #############################################################################
// LÓGICA DE NEGÓCIO E HELPERS
// #############################################################################

$rotulosBloco = [
    'receitas_ordinarias' => 'Entradas ordinárias',
    'receitas_eventuais' => 'Entradas eventuais',
    'receitas_financeiras' => 'Entradas financeiras',
    'capitacoes' => 'Capitações',
    'agapes_eventos' => 'Ágapes e eventos',
    'despesas_potencia' => 'Saídas com a Potência',
    'despesas_administrativas' => 'Saídas administrativas',
    'despesas_bancarias' => 'Saídas bancárias',
    'despesas_ritualisticas' => 'Saídas ritualísticas',
    'tronco' => 'Tronco de solidariedade',
    'entidades_auxiliadas' => 'Entidades auxiliadas',
    'outros' => 'Outros',
];

$formatCurrency = static fn($valor) => 'R$ ' . number_format((float) $valor, 2, ',', '.');
$formatDate = static fn($dateStr) => !empty($dateStr) ? (new \DateTime($dateStr))->format('d/m/Y') : '';

// #############################################################################
// CONFIGURAÇÃO DO APP SHELL
// #############################################################################

$appShellEyebrow = 'Tesouraria';
$appShellTitle = 'Relatório Financeiro da Gestão';
$appShellDescription = 'Consolidação por período para prestação de contas e análise administrativa.';
$appShellActiveHref = '/tesouraria/relatorio-gestao';
$appShellActions = [
    ['label' => 'Painel', 'href' => '/dashboard'],
];

require __DIR__ . '/partials/erp_shell_open.php';

?>

<style>
@media print {
    @page { size: A4; margin: 14mm; }
    body { background: #fff !important; color: #111827 !important; }
    aside, nav, .no-print, button, form, .sticky { display: none !important; }
    main { margin: 0 !important; padding: 0 !important; max-width: none !important; }
    .card, .card-metric, .card-metric-simple {
        break-inside: avoid;
        box-shadow: none !important;
        border: 1px solid #d1d5db !important;
        background: #fff !important;
    }
    .print-header { display: block !important; }
}
.print-header { display: none; }
</style>

<section class="print-header mb-6 border-b border-gray-300 pb-4">
    <h1 class="text-xl font-bold">Relatório Financeiro da Gestão</h1>
    <p class="text-sm">Período: <?= htmlspecialchars((string) ($relatorio['periodo']['inicio_label'] ?? '')) ?> a <?= htmlspecialchars((string) ($relatorio['periodo']['fim_label'] ?? '')) ?></p>
    <p class="text-xs">Emitido em <?= date('d/m/Y H:i') ?></p>
</section>

<div class="no-print mb-4 flex justify-end">
    <button type="button" class="btn btn-primary" onclick="window.print()">Imprimir A4</button>
</div>

<!-- Filtros -->
<div class="card mb-6 no-print">
    <form method="GET" action="/tesouraria/relatorio-gestao" class="card-body">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
            <div>
                <label for="gestao_id" class="form-label">Gestão</label>
                <select name="gestao_id" id="gestao_id" class="form-select">
                    <?php foreach ($gestoes as $gestao): ?>
                        <option value="<?= (int) $gestao['id'] ?>" <?= (int) ($gestao['id'] ?? 0) === (int) ($relatorio['gestao']['id'] ?? 0) ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string) ($gestao['titulo'] ?? 'Gestão')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="encerramento_em" class="form-label">Data Final</label>
                <input type="date" name="encerramento_em" id="encerramento_em" value="<?= htmlspecialchars((string) ($relatorio['periodo']['fim_data'] ?? '')) ?>" class="form-input">
            </div>
            <button type="submit" class="btn btn-primary w-full md:w-auto">Atualizar Relatório</button>
        </div>
    </form>
</div>

<!-- Métricas de Resumo -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="card-metric"><p class="card-metric-label">Saldo Inicial</p><p class="card-metric-value"><?= $formatCurrency($relatorio['saldo_inicial'] ?? 0) ?></p></div>
    <div class="card-metric"><p class="card-metric-label">Total de Entradas</p><p class="card-metric-value text-green-600 dark:text-green-400"><?= $formatCurrency($relatorio['totais']['entradas'] ?? 0) ?></p></div>
    <div class="card-metric"><p class="card-metric-label">Total de Saídas</p><p class="card-metric-value text-red-600 dark:text-red-400"><?= $formatCurrency($relatorio['totais']['saidas'] ?? 0) ?></p></div>
    <div class="card-metric"><p class="card-metric-label">Saldo Final</p><p class="card-metric-value text-blue-600 dark:text-blue-400"><?= $formatCurrency($relatorio['saldo_final'] ?? 0) ?></p></div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Coluna Principal -->
    <div class="lg:col-span-2 space-y-8">
        <div class="card">
            <div class="card-header"><h2 class="card-title">Síntese por Blocos</h2><p class="card-description">Agrupamentos para o relatório da gestão.</p></div>
            <div class="card-body space-y-4">
                <?php foreach (($relatorio['blocos'] ?? []) as $bloco => $totais): ?>
                    <div class="list-item-report">
                        <p class="font-semibold"><?= htmlspecialchars($rotulosBloco[$bloco] ?? $bloco) ?></p>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div class="flex justify-between items-baseline"><span class="text-gray-500">Entradas:</span><strong class="text-green-600 dark:text-green-400"><?= $formatCurrency($totais['entrada'] ?? 0) ?></strong></div>
                            <div class="flex justify-between items-baseline"><span class="text-gray-500">Saídas:</span><strong class="text-red-600 dark:text-red-400"><?= $formatCurrency($totais['saida'] ?? 0) ?></strong></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2 class="card-title">Categorias Consolidadas</h2></div>
            <div class="card-body space-y-3">
                <?php foreach (($relatorio['categorias'] ?? []) as $linha): ?>
                    <div class="list-item-detail">
                        <div>
                            <p class="font-semibold"><?= htmlspecialchars((string) ($linha['nome'] ?? '-')) ?></p>
                            <p class="text-xs text-gray-500">
                                <?= htmlspecialchars($rotulosBloco[(string) ($linha['bloco_relatorio'] ?? 'outros')] ?? (string) ($linha['bloco_relatorio'] ?? 'outros')) ?> | <?= htmlspecialchars((string) ($linha['tipo'] ?? '')) ?>
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-lg <?= ((string) ($linha['tipo'] ?? '') === 'entrada') ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' ?>">
                                <?= $formatCurrency($linha['total'] ?? 0) ?>
                            </p>
                            <p class="text-xs text-gray-500"><?= (int) ($linha['quantidade'] ?? 0) ?> lançamentos</p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Coluna Lateral -->
    <div class="space-y-8">
        <div class="card">
            <div class="card-header"><h2 class="card-title">Tronco e Entidades</h2></div>
            <div class="card-body">
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div class="card-metric-simple text-center"><p class="card-metric-label">Entradas Tronco</p><p class="card-metric-value text-green-600 dark:text-green-400 text-xl"><?= $formatCurrency($relatorio['tronco']['entradas'] ?? 0) ?></p></div>
                    <div class="card-metric-simple text-center"><p class="card-metric-label">Saídas Tronco</p><p class="card-metric-value text-red-600 dark:text-red-400 text-xl"><?= $formatCurrency($relatorio['tronco']['saidas'] ?? 0) ?></p></div>
                </div>
                <h3 class="font-semibold text-gray-800 dark:text-gray-200 mb-3">Entidades Auxiliadas</h3>
                <div class="space-y-3">
                    <?php if (empty($relatorio['entidades_auxiliadas'])): ?>
                        <div class="text-center py-4 px-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg"><p class="text-sm text-gray-500">Nenhuma entidade auxiliada no período.</p></div>
                    <?php else: ?>
                        <?php foreach (($relatorio['entidades_auxiliadas'] ?? []) as $entidade): ?>
                            <div class="list-item-param">
                                <span><?= htmlspecialchars((string) ($entidade['entidade'] ?? 'Não informada')) ?></span>
                                <strong><?= $formatCurrency($entidade['total'] ?? 0) ?></strong>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2 class="card-title">Lançamentos Recentes</h2></div>
            <div class="card-body space-y-4">
                <?php foreach (($relatorio['lancamentos'] ?? []) as $lancamento): ?>
                    <div class="list-item-detail">
                        <div>
                            <p class="font-semibold text-sm"><?= htmlspecialchars((string) ($lancamento['categoria_nome'] ?? '-')) ?></p>
                            <p class="text-xs text-gray-600 mt-1"><?= htmlspecialchars((string) ($lancamento['descricao'] ?? 'Sem descrição')) ?></p>
                            <p class="text-xs text-gray-500 mt-1">
                                <?= $formatDate((string)($lancamento['data_lancamento'] ?? '')) ?>
                                <?php if (!empty($lancamento['obreiro_nome'])): ?> | <?= htmlspecialchars((string) $lancamento['obreiro_nome']) ?><?php endif; ?>
                            </p>
                        </div>
                        <strong class="text-sm font-bold <?= ($lancamento['tipo'] ?? '') === 'entrada' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' ?>">
                            <?= $formatCurrency($lancamento['valor'] ?? 0) ?>
                        </strong>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/partials/erp_shell_close.php'; ?>


