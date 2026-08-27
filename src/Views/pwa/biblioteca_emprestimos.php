<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

$emprestimos = is_array($emprestimos ?? null) ? $emprestimos : [];
$mensagemSucesso = $mensagemSucesso ?? null;
$mensagemErro = $mensagemErro ?? null;

$pwaPageTitle = 'Empréstimos';
$pwaShowBackButton = true;
$pwaBackUrl = '/pwa/biblioteca-gestao';
$pwaActiveTab = 'biblioteca';

$statusBadge = static function (string $status): array {
    return match ($status) {
        'atrasado' => ['Atrasado', 'pwa-badge-danger'],
        'aprovado' => ['Aprovado', 'pwa-badge-success'],
        'pendente' => ['Pendente', 'pwa-badge-warn'],
        default => [ucfirst($status), 'pwa-badge-muted'],
    };
};

ob_start();
?>

<div class="px-4 py-4 space-y-4">
    <?php if ($mensagemSucesso): ?>
        <div class="pwa-alert-success"><?= htmlspecialchars((string) $mensagemSucesso) ?></div>
    <?php endif; ?>

    <?php if ($mensagemErro): ?>
        <div class="pwa-alert-error"><?= htmlspecialchars((string) $mensagemErro) ?></div>
    <?php endif; ?>

    <section class="pwa-hero">
        <p class="pwa-eyebrow">Biblioteca</p>
        <h2 class="mt-2 text-xl font-bold tracking-tight text-white">Gestão de Empréstimos</h2>
        <p class="pwa-muted mt-1.5 text-xs">Gerencie pedidos pendentes e atrasados na biblioteca.</p>
    </section>

    <?php if ($emprestimos === []): ?>
        <div class="p-8 text-center text-xs text-slate-500 bg-slate-900/40 rounded-2xl border border-dashed border-white/10 select-none">
            <div class="text-sm font-semibold text-slate-300">Nenhum empréstimo pendente</div>
            <p class="mt-1">A fila da biblioteca está limpa.</p>
        </div>
    <?php else: ?>
        <div class="space-y-3 pb-4">
            <?php foreach ($emprestimos as $emprestimo): ?>
                <?php
                $status = (string) ($emprestimo['status'] ?? 'pendente');
                $badge = $statusBadge($status);
                ?>
                <div class="pwa-card border border-white/5 flex flex-col gap-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h3 class="text-xs font-bold text-slate-200 truncate"><?= htmlspecialchars((string) ($emprestimo['titulo'] ?? 'Livro')) ?></h3>
                            <p class="text-[10px] text-slate-400 mt-0.5 truncate"><?= htmlspecialchars((string) ($emprestimo['obreiro_nome'] ?? 'Obreiro')) ?></p>
                            <p class="text-[10px] text-slate-500 mt-1 font-medium">Devolução: <?= htmlspecialchars((string) ($emprestimo['data_devolucao_prevista'] ?? 'N/D')) ?></p>
                        </div>
                        <span class="pwa-badge <?= $badge[1] ?> font-bold shrink-0 select-none">
                            <?= htmlspecialchars($badge[0]) ?>
                        </span>
                    </div>

                    <form method="post" action="/pwa/biblioteca/devolver" class="mt-1 select-none">
                        <input type="hidden" name="id" value="<?= (int) ($emprestimo['id'] ?? 0) ?>">
                        <button type="submit" class="pwa-btn-primary py-2 text-xs font-bold bg-amber-500 text-slate-950">Registrar devolução</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
$pwaContent = ob_get_clean();
require __DIR__ . '/shell.php';
?>
