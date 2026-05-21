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
        <div class="rounded-xl px-4 py-3 text-sm font-medium" style="background:rgba(248,113,113,0.12);color:#fca5a5;border:1px solid rgba(248,113,113,0.25);">
            <?= htmlspecialchars((string) $mensagemErro) ?>
        </div>
    <?php endif; ?>

    <?php if ($emprestimos === []): ?>
        <div class="rounded-2xl p-5 text-center" style="background:rgba(255,255,255,0.055);border:1px solid rgba(255,255,255,0.09);">
            <div class="text-lg font-semibold" style="color:#f1f5f9;">Nenhum empréstimo ativo</div>
            <p class="mt-1 text-sm" style="color:#94a3b8;">Você não possui empréstimos pendentes no momento.</p>
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
                    'aprovado' => ['Aprovado', 'emerald'],
                    'atrasado' => ['Atrasado', 'rose'],
                    'pendente' => ['Pendente', 'amber'],
                    default => [ucfirst($status), 'slate'],
                };
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
                            <h3 class="font-semibold truncate" style="color:#f1f5f9;"><?= htmlspecialchars($titulo) ?></h3>
                            <p class="text-xs truncate" style="color:#94a3b8;">
                                <?= $codigo !== '' ? 'Cód: ' . htmlspecialchars($codigo) : '' ?>
                            </p>
                        </div>
                        <span class="inline-flex items-center text-xs font-semibold" style="<?= $badgeStyle ?>border-radius:999px;padding:0.2rem 0.55rem;">
                            <?= htmlspecialchars($badge[0]) ?>
                        </span>
                    </div>
                    <div class="mt-3 space-y-1 text-xs" style="color:#94a3b8;">
                        <p><span class="font-semibold">Empréstimo:</span> <?= $dataEmprestimo !== '' ? htmlspecialchars($dataEmprestimo) : 'N/D' ?></p>
                        <p><span class="font-semibold">Devolução Prevista:</span> <?= $prevista !== '' ? htmlspecialchars($prevista) : 'N/D' ?></p>
                    </div>
                    <div class="mt-3 text-xs" style="color:#94a3b8;">
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
