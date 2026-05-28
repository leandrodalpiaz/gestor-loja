<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

$emprestimos = is_array($emprestimos ?? null) ? $emprestimos : [];
$mensagemErro = $mensagemErro ?? null;

$pwaPageTitle = 'Meus Empréstimos';
$pwaShowBackButton = true;
$pwaBackUrl = '/pwa/biblioteca';

ob_start();
?>

<div class="px-4 py-4 space-y-4">
    <?php if ($mensagemErro): ?>
        <div class="pwa-alert-error"><?= htmlspecialchars((string) $mensagemErro) ?></div>
    <?php endif; ?>

    <section class="pwa-hero">
        <p class="pwa-eyebrow">Minhas Leituras</p>
        <h2 class="mt-2 text-xl font-bold tracking-tight text-white">Meus Empréstimos</h2>
        <p class="pwa-muted mt-1.5 text-xs">Acompanhe seus prazos de devolução e o status das solicitações.</p>
    </section>

    <?php if ($emprestimos === []): ?>
        <div class="p-8 text-center text-xs text-slate-500 bg-slate-900/40 rounded-2xl border border-dashed border-white/10 select-none">
            <div class="text-sm font-semibold text-slate-300">Nenhum empréstimo ativo</div>
            <p class="mt-1">Você não possui empréstimos pendentes no momento.</p>
        </div>
    <?php else: ?>
        <div class="space-y-3 pb-4">
            <?php foreach ($emprestimos as $item): ?>
                <?php
                $titulo = (string) ($item['titulo'] ?? 'Livro');
                $codigo = (string) ($item['codigo_acervo'] ?? '');
                $status = (string) ($item['status'] ?? 'pendente');
                $dataEmprestimo = (string) ($item['data_emprestimo'] ?? '');
                $prevista = (string) ($item['data_devolucao_prevista'] ?? '');

                $badge = match ($status) {
                    'aprovado' => ['Aprovado', 'pwa-badge-success'],
                    'atrasado' => ['Atrasado', 'pwa-badge-danger'],
                    'pendente' => ['Pendente', 'pwa-badge-warn'],
                    default => [ucfirst($status), 'pwa-badge-muted'],
                };
                ?>
                <div class="pwa-card border border-white/5 flex flex-col gap-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h3 class="text-xs font-bold text-slate-200 truncate"><?= htmlspecialchars($titulo) ?></h3>
                            <p class="text-[10px] text-slate-400 mt-0.5">
                                <?= $codigo !== '' ? 'Cód: ' . htmlspecialchars($codigo) : '' ?>
                            </p>
                        </div>
                        <span class="pwa-badge <?= $badge[1] ?> font-bold shrink-0 select-none">
                            <?= htmlspecialchars($badge[0]) ?>
                        </span>
                    </div>
                    <div class="pwa-list-group text-[11px] p-3 space-y-1.5 bg-slate-950/40">
                        <div class="flex justify-between"><span class="text-slate-400">Empréstimo:</span> <strong class="text-slate-200"><?= $dataEmprestimo !== '' ? htmlspecialchars($dataEmprestimo) : 'N/D' ?></strong></div>
                        <div class="flex justify-between"><span class="text-slate-400">Devolução Prevista:</span> <strong class="text-slate-200"><?= $prevista !== '' ? htmlspecialchars($prevista) : 'N/D' ?></strong></div>
                    </div>
                    <p class="text-[10px] text-slate-500 text-center select-none">
                        Para devolução ou renovação, procure o Bibliotecário.
                    </p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
$pwaContent = ob_get_clean();
require __DIR__ . '/shell.php';
?>
