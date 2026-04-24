<?php
$dashboard = is_array($dashboard ?? null) ? $dashboard : [];
$dashboardHeader = [
    'title' => (string) ($dashboard['title'] ?? 'Painel'),
    'subtitle' => (string) ($dashboard['subtitle'] ?? ''),
    'meta' => is_array($dashboard['meta'] ?? null) ? $dashboard['meta'] : [],
];
$dashboardActions = is_array($dashboard['actions'] ?? null) ? $dashboard['actions'] : [];
$dashboardBlocks = is_array($dashboard['blocks'] ?? null) ? $dashboard['blocks'] : [];
$dashboardAlerts = is_array($dashboard['alerts'] ?? null) ? $dashboard['alerts'] : [];
$dashboardActivity = is_array($dashboard['activity'] ?? null) ? $dashboard['activity'] : [];
$dashboardLinks = is_array($dashboard['links'] ?? null) ? $dashboard['links'] : [];
$dashboardRenderers = is_array($dashboardRenderers ?? null) ? $dashboardRenderers : [];
?>
<div class="space-y-4 mb-8">
    <?php require __DIR__ . '/../components/dashboard_header.php'; ?>
    <?php require __DIR__ . '/../components/dashboard_actions.php'; ?>

    <?php if ($dashboardBlocks !== []): ?>
        <div class="grid gap-4 md:grid-cols-2">
            <?php foreach ($dashboardBlocks as $index => $dashboardBlock): ?>
                <?php
                $renderer = $dashboardRenderers[$index] ?? null;
                $dashboardBlockContent = '';
                if (is_callable($renderer)) {
                    ob_start();
                    $renderer($dashboardBlock);
                    $dashboardBlockContent = (string) ob_get_clean();
                } elseif (isset($dashboardBlock['content_html'])) {
                    $dashboardBlockContent = (string) $dashboardBlock['content_html'];
                }
                require __DIR__ . '/../components/dashboard_block.php';
                ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php require __DIR__ . '/../components/dashboard_alerts.php'; ?>
    <?php require __DIR__ . '/../components/dashboard_activity.php'; ?>
    <?php require __DIR__ . '/../components/dashboard_links.php'; ?>
</div>

