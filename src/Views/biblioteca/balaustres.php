<?php
declare(strict_types=1);

$appShellEyebrow = 'Biblioteca';
$appShellTitle = 'Arquivo de Balaústres';
$appShellDescription = 'Balaústres aprovados disponíveis para leitura (filtrado por grau).';
$appShellActiveHref = '/biblioteca/balaustres';
$appShellActions = [['label' => 'Voltar', 'href' => '/biblioteca']];

$itens = is_array($itens ?? null) ? $itens : [];

$fmtDataHora = static function (?string $dataHora): ?string {
    $dataHora = trim((string) $dataHora);
    if ($dataHora === '') return null;
    try {
        return (new DateTimeImmutable($dataHora))->format('d-m-Y H:i');
    } catch (Throwable $e) {
        return $dataHora;
    }
};

require __DIR__ . '/../partials/erp_shell_open.php';
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Balaústres aprovados</h2>
        <p class="card-subtitle">Clique para visualizar. Se o seu grau não for suficiente, o item não abre.</p>
    </div>
    <div class="card-body space-y-3">
        <?php if ($itens === []): ?>
            <div class="alert alert-info">Nenhum balaústre aprovado encontrado.</div>
        <?php else: ?>
            <?php foreach ($itens as $b): ?>
                <a class="list-item-action" href="/biblioteca/balaustres/visualizar?id=<?= urlencode((string) ($b['id'] ?? 0)) ?>">
                    <span>
                        <?= htmlspecialchars((string) (($b['sessao_titulo'] ?? '') !== '' ? $b['sessao_titulo'] : 'Sessão')) ?>
                        <?php $dh = $fmtDataHora($b['data_hora_inicio'] ?? null); ?>
                        <?php if ($dh): ?> · <?= htmlspecialchars((string) $dh) ?><?php endif; ?>
                    </span>
                    <span>›</span>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../partials/erp_shell_close.php'; ?>

