<?php
declare(strict_types=1);

$appShellEyebrow = 'Segundo Vigilante';
$appShellTitle = 'Mentoria de Trabalhos';
$appShellDescription = 'Peças de Arquitetura em fase de mentoria formativa (Companheiros).';
$appShellActiveHref = '/segundo-vigilante/trabalhos';
$appShellActions = [['label' => 'Voltar', 'href' => '/segundo-vigilante']];

$itens = is_array($itens ?? null) ? $itens : [];

require __DIR__ . '/../partials/erp_shell_open.php';
?>

<?php if (!empty($_SESSION['mensagem_sucesso'])): ?>
    <div class="alert alert-success mb-6"><?= htmlspecialchars((string) $_SESSION['mensagem_sucesso']) ?></div>
    <?php unset($_SESSION['mensagem_sucesso']); ?>
<?php endif; ?>
<?php if (!empty($_SESSION['mensagem_erro'])): ?>
    <div class="alert alert-danger mb-6"><?= htmlspecialchars((string) $_SESSION['mensagem_erro']) ?></div>
    <?php unset($_SESSION['mensagem_erro']); ?>
<?php endif; ?>

<div class="card depth-1 p-6">
    <div class="card-header border-b border-white/5 pb-3 mb-4">
        <h2 class="card-title">Trabalhos sob Mentoria</h2>
    </div>
    <div class="card-body space-y-4">
        <?php if ($itens === []): ?>
            <div class="alert alert-info">Nenhuma peça sob mentoria no momento.</div>
        <?php else: ?>
            <?php foreach ($itens as $t): ?>
                <div class="rounded-xl border border-white/5 bg-white/[0.02] p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div class="min-w-0 flex-grow">
                        <div class="flex items-center justify-between gap-3">
                            <div class="font-bold text-white text-base"><?= htmlspecialchars((string) ($t['titulo'] ?? '')) ?></div>
                            <span class="text-xs text-slate-400 font-medium"><?= htmlspecialchars((string) ($t['obreiro_nome'] ?? '')) ?></span>
                        </div>
                        <div class="text-xs text-slate-300 mt-1">
                            Tipo: <span class="font-semibold text-white"><?= htmlspecialchars((string) ($t['tipo_trabalho'] ?? '')) ?></span>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3 flex-shrink-0">
                        <?php if (!empty($t['arquivo_pdf_path'])): ?>
                            <a class="btn border border-white/10 text-slate-300 hover:bg-white/5 py-1.5 px-3 text-xs font-semibold" href="<?= htmlspecialchars((string) $t['arquivo_pdf_path']) ?>" target="_blank" rel="noopener">Abrir PDF</a>
                        <?php endif; ?>
                        <form method="POST" action="/segundo-vigilante/trabalhos/decidir" class="flex flex-wrap items-center gap-2">
                            <input type="hidden" name="id" value="<?= htmlspecialchars((string) ($t['id'] ?? '')) ?>">
                            <input class="form-input text-xs py-1.5 px-3 md:w-64" name="observacao" placeholder="Orientação/Ajuste (opcional)">
                            <button class="btn btn-success py-1.5 px-3 text-xs" name="acao" value="aprovar" type="submit">Orientação Concluída</button>
                            <button class="btn btn-warning py-1.5 px-3 text-xs text-black" name="acao" value="rejeitar" type="submit">Solicitar Ajustes</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../partials/erp_shell_close.php'; ?>
