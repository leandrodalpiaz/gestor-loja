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
        <div class="rounded-xl p-4 text-sm" style="background:rgba(251,191,36,0.15);color:#fde68a;border:1px solid rgba(251,191,36,0.25);">
            <p class="font-semibold">Erro de Acesso</p>
            <p><?= htmlspecialchars((string) $erroDb) ?></p>
        </div>
    <?php endif; ?>

    <?php if (!$comunicado): ?>
        <div class="rounded-2xl p-5 text-center" style="background:rgba(255,255,255,0.055);border:1px solid rgba(255,255,255,0.09);">
            <div class="text-lg font-semibold" style="color:#f1f5f9;">Comunicado não encontrado</div>
            <p class="mt-1 text-sm" style="color:#94a3b8;">O comunicado que você procura não foi encontrado ou foi removido.</p>
        </div>
    <?php else: ?>
        <div class="rounded-2xl p-5 space-y-3 shadow-sm" style="background:rgba(255,255,255,0.055);border:1px solid rgba(255,255,255,0.09);">
            <h2 class="text-xl font-bold" style="color:#f1f5f9;"><?= htmlspecialchars((string) ($comunicado['titulo'] ?? 'Comunicado')) ?></h2>
            <p class="text-xs" style="color:#94a3b8;">
                Categoria: <?= htmlspecialchars((string) ($comunicado['categoria'] ?? 'geral')) ?>
                <?= !empty($comunicado['publicado_em']) ? ' · Publicado em ' . htmlspecialchars((string) $comunicado['publicado_em']) : '' ?>
            </p>
            <div class="prose prose-sm max-w-none whitespace-pre-line" style="color:#e2e8f0;">
                <?= nl2br(htmlspecialchars((string) ($comunicado['conteudo'] ?? ''))) ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
$pwaContent = ob_get_clean();
require __DIR__ . '/shell.php';
?>
