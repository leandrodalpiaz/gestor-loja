<?php
/**
 * Componente: Badge Status
 * @param string $label O texto a ser exibido no badge.
 * @param string $type O tipo visual do badge (info, neutral, warning, success, danger). Padrão: neutral.
 * @param string $classes Classes adicionais (opcional).
 */

$label = (string) ($label ?? '');
$type = (string) ($type ?? 'neutral');
$classes = (string) ($classes ?? '');

$typeClass = match ($type) {
    'info' => 'badge-status-info',
    'warning' => 'badge-status-warning',
    'success' => 'badge-status-success',
    'danger' => 'badge-status-danger',
    default => 'badge-status-neutral',
};

// Se não houver CSS predefinido para as classes 'badge-status-*', o fallback utiliza as cores do Tailwind do projeto.
// As classes .badge-status estão supostamente definidas em tailwind.generated.css ou erp_shell_open.php
?>
<span class="badge-status <?= htmlspecialchars($typeClass) ?> <?= htmlspecialchars($classes) ?>">
    <?= htmlspecialchars($label) ?>
</span>
