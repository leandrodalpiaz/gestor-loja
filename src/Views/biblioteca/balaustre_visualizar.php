<?php
declare(strict_types=1);

$appShellEyebrow = 'Biblioteca';
$appShellTitle = 'Balaústre';
$appShellDescription = 'Leitura para pesquisa e consulta.';
$appShellActiveHref = '/biblioteca/balaustres';
$appShellActions = [['label' => 'Voltar', 'href' => '/biblioteca/balaustres']];

$balaustre = is_array($balaustre ?? null) ? $balaustre : [];

$fmtDataHora = static function (?string $dataHora): ?string {
    $dataHora = trim((string) $dataHora);
    if ($dataHora === '') return null;
    try {
        return (new DateTimeImmutable($dataHora))->format('d-m-Y H:i');
    } catch (Throwable $e) {
        return $dataHora;
    }
};

$texto = trim((string) ($balaustre['texto_final'] ?? ''));

require __DIR__ . '/../partials/erp_shell_open.php';
?>

<div class="space-y-6">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= htmlspecialchars((string) ($balaustre['sessao_titulo'] ?? 'Sessão')) ?></h2>
            <p class="card-subtitle">
                <?php $dh = $fmtDataHora($balaustre['data_hora_inicio'] ?? null); ?>
                <?php if ($dh): ?><?= htmlspecialchars((string) $dh) ?><?php endif; ?>
            </p>
        </div>
        <div class="card-body">
            <?php if ($texto === ''): ?>
                <div class="alert alert-info">Texto não disponível.</div>
            <?php else: ?>
                <pre class="whitespace-pre-wrap text-sm leading-relaxed"><?= htmlspecialchars($texto) ?></pre>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../partials/erp_shell_close.php'; ?>

