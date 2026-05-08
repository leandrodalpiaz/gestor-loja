<?php
declare(strict_types=1);

$appShellEyebrow = 'Biblioteca';
$appShellTitle = 'Trabalhos & Publicações';
$appShellDescription = 'Material arquivado pela Secretaria para pesquisa e consulta.';
$appShellActiveHref = '/biblioteca/trabalhos';
$appShellActions = [['label' => 'Voltar', 'href' => '/biblioteca']];

$itens = is_array($itens ?? null) ? $itens : [];

require __DIR__ . '/../partials/erp_shell_open.php';
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Trabalhos recentes</h2>
        <p class="card-subtitle">Quando houver PDF arquivado, ele aparece como link.</p>
    </div>
    <div class="card-body space-y-3">
        <?php if ($itens === []): ?>
            <div class="alert alert-info">Nenhum trabalho encontrado.</div>
        <?php else: ?>
            <?php foreach ($itens as $t): ?>
                <div class="list-item-condensed">
                    <div class="flex items-center justify-between gap-3">
                        <div class="font-semibold"><?= htmlspecialchars((string) ($t['titulo'] ?? '')) ?></div>
                        <span class="text-xs text-gray-500"><?= htmlspecialchars((string) ($t['tipo_trabalho'] ?? '')) ?></span>
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                        Sessão: <?= htmlspecialchars((string) ($t['sessao_titulo'] ?? '')) ?>
                        <?php if (!empty($t['autor_nome'])): ?> · Autor: <?= htmlspecialchars((string) $t['autor_nome']) ?><?php endif; ?>
                    </div>
                    <?php if (!empty($t['arquivo_pdf_path'])): ?>
                        <div class="mt-2">
                            <a class="btn btn-secondary btn-sm" href="<?= htmlspecialchars((string) $t['arquivo_pdf_path']) ?>" target="_blank" rel="noopener">Abrir PDF</a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../partials/erp_shell_close.php'; ?>

