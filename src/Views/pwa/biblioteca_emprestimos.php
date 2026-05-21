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
        'atrasado' => ['Atrasado', 'rose'],
        'aprovado' => ['Aprovado', 'emerald'],
        'pendente' => ['Pendente', 'amber'],
        default => [ucfirst($status), 'slate'],
    };
};

ob_start();
?>

<div class="p-4 sm:p-6 space-y-4">
    <?php if ($mensagemSucesso): ?>
        <div class="rounded-xl px-4 py-3 text-sm font-medium" style="background:rgba(52,211,153,0.15);color:#6ee7b7;border:1px solid rgba(52,211,153,0.25);">
            <?= htmlspecialchars((string) $mensagemSucesso) ?>
        </div>
    <?php endif; ?>

    <?php if ($mensagemErro): ?>
        <div class="rounded-xl px-4 py-3 text-sm font-medium" style="background:rgba(248,113,113,0.12);color:#fca5a5;border:1px solid rgba(248,113,113,0.25);">
            <?= htmlspecialchars((string) $mensagemErro) ?>
        </div>
    <?php endif; ?>

    <div class="rounded-2xl p-5" style="background:rgba(255,255,255,0.055);border:1px solid rgba(255,255,255,0.09);">
        <h2 class="text-xl font-bold" style="color:#f1f5f9;">Gestão de empréstimos</h2>
        <p class="mt-1 text-sm" style="color:#94a3b8;">Pedidos pendentes e atrasados para ação rápida do Bibliotecário.</p>
    </div>

    <?php if ($emprestimos === []): ?>
        <div class="rounded-2xl p-5 text-center" style="background:rgba(255,255,255,0.055);border:1px solid rgba(255,255,255,0.09);">
            <div class="text-lg font-semibold" style="color:#f1f5f9;">Nenhum empréstimo pendente</div>
            <p class="mt-1 text-sm" style="color:#94a3b8;">A fila da biblioteca está limpa.</p>
        </div>
    <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($emprestimos as $emprestimo): ?>
                <?php
                $status = (string) ($emprestimo['status'] ?? 'pendente');
                $badge = $statusBadge($status);
                $badgeStyle = match ($badge[1]) {
                    'emerald' => 'background:rgba(52,211,153,0.15);color:#6ee7b7;',
                    'rose'    => 'background:rgba(248,113,113,0.12);color:#fca5a5;',
                    'amber'   => 'background:rgba(251,191,36,0.15);color:#fde68a;',
                    default   => 'background:rgba(255,255,255,0.04);color:#94a3b8;',
                };
                ?>
                <div class="rounded-2xl p-4 shadow-sm" style="background:rgba(255,255,255,0.055);border:1px solid rgba(255,255,255,0.09);">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h3 class="font-semibold" style="color:#f1f5f9;"><?= htmlspecialchars((string) ($emprestimo['titulo'] ?? 'Livro')) ?></h3>
                            <p class="mt-1 text-sm" style="color:#94a3b8;"><?= htmlspecialchars((string) ($emprestimo['obreiro_nome'] ?? 'Obreiro')) ?></p>
                            <p class="mt-1 text-xs" style="color:#94a3b8;">Previsto: <?= htmlspecialchars((string) ($emprestimo['data_devolucao_prevista'] ?? 'N/D')) ?></p>
                        </div>
                        <span class="inline-flex shrink-0 items-center text-xs font-semibold" style="<?= $badgeStyle ?>border-radius:999px;padding:0.2rem 0.55rem;">
                            <?= htmlspecialchars($badge[0]) ?>
                        </span>
                    </div>

                    <form method="post" action="/pwa/biblioteca/devolver" class="mt-4">
                        <input type="hidden" name="id" value="<?= (int) ($emprestimo['id'] ?? 0) ?>">
                        <button type="submit" class="w-full px-4 py-3 text-sm font-semibold" style="background:#1e3a5f;color:#f1f5f9;border-radius:0.625rem;">Registrar devolução</button>
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
