<?php
$mensagemSucesso = $_SESSION['mensagem_sucesso'] ?? null;
$mensagemErro = $_SESSION['mensagem_erro'] ?? null;
unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']);

$appShellEyebrow = 'Veneravel';
$appShellTitle = 'Veneravel Mestre';
$appShellDescription = 'Painel estrategico e analitico da Loja.';
$appShellActiveHref = '/veneravel';
$appShellActions = [
    ['label' => 'Painel da Loja', 'href' => '/dashboard'],
    ['label' => 'Painel de votacao', 'href' => '/secretaria/votacao', 'primary' => true],
];
$appShellSidebarSections = [
    [
        'title' => 'Veneravel',
        'items' => [
            ['label' => 'Painel estrategico', 'href' => '/veneravel'],
            ['label' => 'Balaustres / votacao', 'href' => '/secretaria/votacao'],
            ['label' => 'Secretaria', 'href' => '/secretaria'],
            ['label' => 'Tesouraria', 'href' => '/tesouraria/caixa'],
        ],
    ],
    [
        'title' => 'Geral',
        'items' => [
            ['label' => 'Painel', 'href' => '/dashboard'],
        ],
    ],
];

require __DIR__ . '/../partials/erp_head.php';
require __DIR__ . '/../partials/erp_shell_open.php';
?>
<?php if ($mensagemSucesso): ?>
    <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-700"><?= htmlspecialchars($mensagemSucesso) ?></div>
<?php endif; ?>
<?php if ($mensagemErro): ?>
    <div class="mb-4 rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-rose-700"><?= htmlspecialchars($mensagemErro) ?></div>
<?php endif; ?>
<?php
$dashboardRenderers = [
    static function (array $block): void {
        $dashboardMetrics = is_array($block['metrics'] ?? null) ? $block['metrics'] : [];
        $dashboardListItems = is_array($block['list'] ?? null) ? $block['list'] : [];
        require __DIR__ . '/../components/dashboard_metrics.php';
        echo '<div class="mt-3">';
        require __DIR__ . '/../components/dashboard_list.php';
        echo '</div>';
    },
    static function (array $block): void {
        $dashboardMetrics = is_array($block['metrics'] ?? null) ? $block['metrics'] : [];
        $dashboardListItems = is_array($block['list'] ?? null) ? $block['list'] : [];
        require __DIR__ . '/../components/dashboard_metrics.php';
        echo '<div class="mt-3">';
        require __DIR__ . '/../components/dashboard_list.php';
        echo '</div>';
    },
    static function (array $block): void {
        $dashboardMetrics = is_array($block['metrics'] ?? null) ? $block['metrics'] : [];
        $dashboardListItems = is_array($block['list'] ?? null) ? $block['list'] : [];
        require __DIR__ . '/../components/dashboard_metrics.php';
        echo '<div class="mt-3">';
        require __DIR__ . '/../components/dashboard_list.php';
        echo '</div>';
    },
    static function (array $block) use ($balaustresAptos, $balaustresEmVotacao): void {
        $dashboardMetrics = is_array($block['metrics'] ?? null) ? $block['metrics'] : [];
        $dashboardListItems = is_array($block['list'] ?? null) ? $block['list'] : [];
        require __DIR__ . '/../components/dashboard_metrics.php';
        echo '<div class="mt-3">';
        require __DIR__ . '/../components/dashboard_list.php';
        echo '</div>';
        echo '<div class="mt-3 flex flex-wrap gap-2">';
        if (!empty($balaustresAptos[0]['id'])) {
            echo '<form method="POST" action="/veneravel/balaustres/abrir-votacao">';
            echo '<input type="hidden" name="balaustre_id" value="' . (int) $balaustresAptos[0]['id'] . '">';
            echo '<button type="submit" class="rounded-md border border-erp-navy bg-erp-navy px-3 py-2 text-sm font-semibold text-white">Abrir votacao</button>';
            echo '</form>';
        }
        if (!empty($balaustresEmVotacao[0]['id'])) {
            echo '<form method="POST" action="/veneravel/balaustres/encerrar-votacao">';
            echo '<input type="hidden" name="balaustre_id" value="' . (int) $balaustresEmVotacao[0]['id'] . '">';
            echo '<button type="submit" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700">Encerrar votacao</button>';
            echo '</form>';
        }
        echo '</div>';
    },
];
require __DIR__ . '/../layouts/dashboard.php';
?>
<?php require __DIR__ . '/../partials/erp_shell_close.php'; ?>

