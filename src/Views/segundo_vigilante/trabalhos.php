<?php
declare(strict_types=1);

$appShellEyebrow = 'Segundo Vigilante';
$appShellTitle = 'Validação de Trabalhos';
$appShellDescription = 'Pendentes de validação (Companheiros).';
$appShellActiveHref = '/segundo-vigilante/trabalhos';
$appShellActions = [['label' => 'Voltar', 'href' => '/segundo-vigilante']];

$itens = is_array($itens ?? null) ? $itens : [];

require __DIR__ . '/../partials/erp_shell_open.php';
?>

<?php if (!empty($_SESSION['mensagem_sucesso'])): ?><div class="alert alert-success mb-6"><?= htmlspecialchars((string) $_SESSION['mensagem_sucesso']) ?></div><?php unset($_SESSION['mensagem_sucesso']); endif; ?>
<?php if (!empty($_SESSION['mensagem_erro'])): ?><div class="alert alert-danger mb-6"><?= htmlspecialchars((string) $_SESSION['mensagem_erro']) ?></div><?php unset($_SESSION['mensagem_erro']); endif; ?>

<div class="card">
    <div class="card-header"><h2 class="card-title">Pendentes</h2></div>
    <div class="card-body space-y-3">
        <?php if ($itens === []): ?>
            <div class="alert alert-info">Nenhum trabalho pendente.</div>
        <?php else: ?>
            <?php foreach ($itens as $t): ?>
                <div class="list-item-condensed">
                    <div class="flex items-center justify-between gap-3">
                        <div class="font-semibold"><?= htmlspecialchars((string) ($t['titulo'] ?? '')) ?></div>
                        <span class="text-xs text-gray-500"><?= htmlspecialchars((string) ($t['obreiro_nome'] ?? '')) ?></span>
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                        <?= htmlspecialchars((string) ($t['tipo_trabalho'] ?? '')) ?>
                    </div>
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <?php if (!empty($t['arquivo_pdf_path'])): ?>
                            <a class="btn btn-secondary btn-sm" href="<?= htmlspecialchars((string) $t['arquivo_pdf_path']) ?>" target="_blank" rel="noopener">Abrir PDF</a>
                        <?php endif; ?>
                        <form method="POST" action="/segundo-vigilante/trabalhos/decidir" class="flex flex-wrap gap-2">
                            <input type="hidden" name="id" value="<?= htmlspecialchars((string) ($t['id'] ?? '')) ?>">
                            <input class="form-input form-input-sm" name="observacao" placeholder="Observação (opcional)">
                            <button class="btn btn-primary btn-sm" name="acao" value="aprovar" type="submit">Aprovar</button>
                            <button class="btn btn-secondary btn-sm" name="acao" value="rejeitar" type="submit">Rejeitar</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../partials/erp_shell_close.php'; ?>

