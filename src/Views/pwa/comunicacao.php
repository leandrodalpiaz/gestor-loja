<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

$lista = is_array($comunicados ?? null) ? $comunicados : [];
$erroDb = $erroDb ?? null;
$podeCriar = !empty($_SESSION['usuario_cargo']) && in_array((string) $_SESSION['usuario_cargo'], ['secretario', 'admin', 'veneravel'], true);

$pwaPageTitle = 'Comunicação';
$pwaShowBackButton = true;
$pwaActiveTab = 'cargo';

ob_start();
?>

<div class="p-4 sm:p-6 space-y-4">
    <?php if ($podeCriar): ?>
        <a href="/pwa/comunicacao/novo" class="flex items-center justify-center rounded-xl px-5 py-3 text-sm font-semibold transition hover:opacity-90" style="background:#C9A227;color:#0f172a;">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            <span>Novo Comunicado</span>
        </a>
    <?php endif; ?>

    <?php if ($erroDb): ?>
        <div class="rounded-xl p-4 text-sm" style="background:rgba(251,191,36,0.15);color:#fde68a;border:1px solid rgba(251,191,36,0.25);">
            <p class="font-semibold">Erro de Acesso</p>
            <p><?= htmlspecialchars((string) $erroDb) ?></p>
            <p class="mt-1 text-xs">Ação: Aplique a migração `database/phase2_comunicacao.sql` no schema do ambiente e tente novamente.</p>
        </div>
    <?php endif; ?>

    <?php if ($lista === []): ?>
        <div class="rounded-2xl p-5 text-center" style="background:rgba(255,255,255,0.055);border:1px solid rgba(255,255,255,0.09);">
            <div class="text-lg font-semibold" style="color:#f1f5f9;">Nenhum comunicado</div>
            <p class="mt-1 text-sm" style="color:#94a3b8;">Ainda não há comunicados oficiais publicados.</p>
        </div>
    <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($lista as $item): ?>
                <?php
                $id = (int) ($item['id'] ?? 0);
                $titulo = (string) ($item['titulo'] ?? 'Comunicado');
                $categoria = (string) ($item['categoria'] ?? 'geral');
                $publicadoEm = (string) ($item['publicado_em'] ?? '');
                $leituras = (int) ($item['total_leituras'] ?? 0);
                $lido = (bool) ($item['lido_pelo_usuario'] ?? false);
                ?>
                <a href="/pwa/comunicacao/ler?id=<?= $id ?>" class="block rounded-2xl p-4 shadow-sm transition" style="background:rgba(255,255,255,0.055);border:1px solid rgba(255,255,255,0.09);">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h3 class="font-semibold truncate" style="color:#f1f5f9;"><?= htmlspecialchars($titulo) ?></h3>
                            <p class="text-xs truncate" style="color:#94a3b8;">
                                Categoria: <?= htmlspecialchars($categoria) ?>
                                <?= $publicadoEm !== '' ? ' · ' . htmlspecialchars($publicadoEm) : '' ?>
                            </p>
                        </div>
                        <span class="inline-flex items-center text-xs font-semibold" style="<?= $lido ? 'background:rgba(52,211,153,0.15);color:#6ee7b7;' : 'background:rgba(251,191,36,0.15);color:#fde68a;' ?>border-radius:999px;padding:0.2rem 0.55rem;">
                            <?= $lido ? 'Lido' : 'Não Lido' ?>
                        </span>
                    </div>
                    <div class="mt-3 text-xs" style="color:#94a3b8;">
                        Confirmado por <?= $leituras ?> irmão(s).
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="text-center text-xs" style="color:#94a3b8;">
        Este é um canal de avisos estruturados e rastreáveis, não um chat.
    </div>
</div>

<?php
$pwaContent = ob_get_clean();
require __DIR__ . '/shell.php';
?>
