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
        'atrasado' => ['Atrasado', 'bg-rose-100 text-rose-800'],
        'aprovado' => ['Aprovado', 'bg-emerald-100 text-emerald-800'],
        'pendente' => ['Pendente', 'bg-amber-100 text-amber-800'],
        default => [ucfirst($status), 'bg-slate-100 text-slate-700'],
    };
};

ob_start();
?>

<div class="p-4 sm:p-6 space-y-4">
    <?php if ($mensagemSucesso): ?>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            <?= htmlspecialchars((string) $mensagemSucesso) ?>
        </div>
    <?php endif; ?>

    <?php if ($mensagemErro): ?>
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">
            <?= htmlspecialchars((string) $mensagemErro) ?>
        </div>
    <?php endif; ?>

    <div class="rounded-2xl border border-erpBorder bg-erpSurface p-5">
        <h2 class="text-xl font-bold text-erpNavy">Gestão de empréstimos</h2>
        <p class="mt-1 text-sm text-erpMuted">Pedidos pendentes e atrasados para ação rápida do Bibliotecário.</p>
    </div>

    <?php if ($emprestimos === []): ?>
        <div class="rounded-2xl border border-erpBorder bg-erpSurface p-5 text-center">
            <div class="text-lg font-semibold text-erpNavy">Nenhum empréstimo pendente</div>
            <p class="mt-1 text-sm text-erpMuted">A fila da biblioteca está limpa.</p>
        </div>
    <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($emprestimos as $emprestimo): ?>
                <?php
                $status = (string) ($emprestimo['status'] ?? 'pendente');
                $badge = $statusBadge($status);
                ?>
                <div class="rounded-2xl border border-erpBorder bg-erpSurface p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h3 class="font-semibold text-erpNavy"><?= htmlspecialchars((string) ($emprestimo['titulo'] ?? 'Livro')) ?></h3>
                            <p class="mt-1 text-sm text-erpMuted"><?= htmlspecialchars((string) ($emprestimo['obreiro_nome'] ?? 'Obreiro')) ?></p>
                            <p class="mt-1 text-xs text-erpMuted">Previsto: <?= htmlspecialchars((string) ($emprestimo['data_devolucao_prevista'] ?? 'N/D')) ?></p>
                        </div>
                        <span class="inline-flex shrink-0 items-center rounded-full px-3 py-1 text-xs font-semibold <?= htmlspecialchars($badge[1]) ?>">
                            <?= htmlspecialchars($badge[0]) ?>
                        </span>
                    </div>

                    <form method="post" action="/pwa/biblioteca/devolver" class="mt-4">
                        <input type="hidden" name="id" value="<?= (int) ($emprestimo['id'] ?? 0) ?>">
                        <button type="submit" class="w-full rounded-lg bg-erpNavy px-4 py-3 text-sm font-semibold text-white">Registrar devolução</button>
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
