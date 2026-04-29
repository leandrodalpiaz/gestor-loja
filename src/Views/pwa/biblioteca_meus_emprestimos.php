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

<div class="p-4 sm:p-6 space-y-4">
    <?php if ($mensagemErro): ?>
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">
            <?= htmlspecialchars((string) $mensagemErro) ?>
        </div>
    <?php endif; ?>

    <?php if ($emprestimos === []): ?>
        <div class="rounded-2xl border border-erpBorder bg-erpSurface p-5 text-center">
            <div class="text-lg font-semibold text-erpNavy">Nenhum empréstimo ativo</div>
            <p class="mt-1 text-sm text-erpMuted">Você não possui empréstimos pendentes no momento.</p>
        </div>
    <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($emprestimos as $item): ?>
                <?php
                $titulo = (string) ($item['titulo'] ?? 'Livro');
                $codigo = (string) ($item['codigo_acervo'] ?? '');
                $status = (string) ($item['status'] ?? 'pendente');
                $dataEmprestimo = (string) ($item['data_emprestimo'] ?? '');
                $prevista = (string) ($item['data_devolucao_prevista'] ?? '');

                $badge = match ($status) {
                    'aprovado' => ['Aprovado', 'bg-emerald-100 text-emerald-800'],
                    'atrasado' => ['Atrasado', 'bg-rose-100 text-rose-800'],
                    'pendente' => ['Pendente', 'bg-amber-100 text-amber-800'],
                    default => [ucfirst($status), 'bg-slate-100 text-slate-700'],
                };
                ?>
                <div class="rounded-2xl border border-erpBorder bg-erpSurface p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h3 class="font-semibold text-erpNavy truncate"><?= htmlspecialchars($titulo) ?></h3>
                            <p class="text-xs text-erpMuted truncate">
                                <?= $codigo !== '' ? 'Cód: ' . htmlspecialchars($codigo) : '' ?>
                            </p>
                        </div>
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold <?= htmlspecialchars($badge[1]) ?>">
                            <?= htmlspecialchars($badge[0]) ?>
                        </span>
                    </div>
                    <div class="mt-3 space-y-1 text-xs text-erpMuted">
                        <p><span class="font-semibold">Empréstimo:</span> <?= $dataEmprestimo !== '' ? htmlspecialchars($dataEmprestimo) : 'N/D' ?></p>
                        <p><span class="font-semibold">Devolução Prevista:</span> <?= $prevista !== '' ? htmlspecialchars($prevista) : 'N/D' ?></p>
                    </div>
                    <div class="mt-3 text-xs text-erpMuted">
                        Para devolução ou renovação, procure o Bibliotecário.
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
$pwaContent = ob_get_clean();
require __DIR__ . '/shell.php';
?>

