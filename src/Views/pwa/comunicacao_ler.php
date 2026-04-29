<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

$comunicado = is_array($comunicado ?? null) ? $comunicado : null;
$erroDb = $erroDb ?? null;

$pwaPageTitle = 'Ler Comunicado';
$pwaShowBackButton = true;
$pwaBackUrl = '/pwa/comunicacao';

ob_start();
?>

<div class="p-4 sm:p-6 space-y-4">
    <?php if ($erroDb): ?>
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            <p class="font-semibold">Erro de Acesso</p>
            <p><?= htmlspecialchars((string) $erroDb) ?></p>
        </div>
    <?php endif; ?>

    <?php if (!$comunicado): ?>
        <div class="rounded-2xl border border-erpBorder bg-erpSurface p-5 text-center">
            <div class="text-lg font-semibold text-erpNavy">Comunicado não encontrado</div>
            <p class="mt-1 text-sm text-erpMuted">O comunicado que você procura não foi encontrado ou foi removido.</p>
        </div>
    <?php else: ?>
        <div class="rounded-2xl border border-erpBorder bg-erpSurface p-5 space-y-3 shadow-sm">
            <h2 class="text-xl font-bold text-erpNavy"><?= htmlspecialchars((string) ($comunicado['titulo'] ?? 'Comunicado')) ?></h2>
            <p class="text-xs text-erpMuted">
                Categoria: <?= htmlspecialchars((string) ($comunicado['categoria'] ?? 'geral')) ?>
                <?= !empty($comunicado['publicado_em']) ? ' · Publicado em ' . htmlspecialchars((string) $comunicado['publicado_em']) : '' ?>
            </p>
            <div class="prose prose-sm max-w-none text-erpText whitespace-pre-line">
                <?= nl2br(htmlspecialchars((string) ($comunicado['conteudo'] ?? ''))) ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
$pwaContent = ob_get_clean();
require __DIR__ . '/shell.php';
?>
