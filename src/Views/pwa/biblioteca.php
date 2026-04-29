<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

$lista = is_array($itens ?? null) ? $itens : [];
$catalogScope = (string) ($catalogScope ?? 'minha');
$redeHabilitada = (bool) ($bibliotecaRedeHabilitada ?? false);
$q = trim((string) ($_GET['q'] ?? ''));

$pwaPageTitle = 'Biblioteca';
$pwaShowBackButton = true;

ob_start();
?>

<div class="p-4 sm:p-6 space-y-4">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-[1fr_auto]">
        <form method="get" action="/pwa/biblioteca" class="flex gap-3">
            <input type="text" name="q" value="<?= htmlspecialchars($q) ?>"
                   class="w-full rounded-xl border border-erpBorder bg-erpSurface px-4 py-3 text-sm placeholder-erpMuted focus:border-erpNavy focus:outline-none"
                   placeholder="Buscar no acervo...">
            <button class="shrink-0 rounded-xl bg-erpNavy px-5 py-3 text-sm font-semibold text-white transition hover:opacity-90">
                Buscar
            </button>
        </form>
        <a href="/pwa/biblioteca/meus-emprestimos" class="flex items-center justify-center rounded-xl border border-erpBorder bg-erpSurface px-5 py-3 text-sm font-semibold text-erpNavy transition hover:bg-erpBg">
            Meus Empréstimos
        </a>
    </div>

    <?php if ($redeHabilitada): ?>
        <div class="flex flex-wrap gap-2">
            <a href="/pwa/biblioteca?scope=minha<?= $q !== '' ? '&q=' . urlencode($q) : '' ?>"
               class="rounded-full px-3 py-1.5 text-xs font-semibold <?= $catalogScope === 'minha' ? 'bg-erpNavy text-white' : 'bg-erpSurface text-erpText border border-erpBorder' ?>">
                Acervo da Loja
            </a>
            <a href="/pwa/biblioteca?scope=rede<?= $q !== '' ? '&q=' . urlencode($q) : '' ?>"
               class="rounded-full px-3 py-1.5 text-xs font-semibold <?= $catalogScope === 'rede' ? 'bg-erpNavy text-white' : 'bg-erpSurface text-erpText border border-erpBorder' ?>">
                Acervo da Rede
            </a>
        </div>
    <?php endif; ?>

    <?php if ($lista === []): ?>
        <div class="rounded-2xl border border-erpBorder bg-erpSurface p-5 text-center">
            <div class="text-lg font-semibold text-erpNavy">Nenhum item encontrado</div>
            <p class="mt-1 text-sm text-erpMuted">Ajuste os filtros ou o termo de busca e tente novamente.</p>
        </div>
    <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($lista as $item): ?>
                <?php
                $titulo = (string) ($item['titulo'] ?? 'Livro');
                $autor = (string) ($item['autor'] ?? '');
                $tipo = (string) ($item['tipo'] ?? '');
                $lojaNome = (string) ($item['loja_nome'] ?? '');
                $disponivel = (bool) ($item['disponivel'] ?? false);
                ?>
                <div class="rounded-2xl border border-erpBorder bg-erpSurface p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h3 class="font-semibold text-erpNavy truncate"><?= htmlspecialchars($titulo) ?></h3>
                            <p class="text-sm text-erpMuted truncate">
                                <?= $autor !== '' ? htmlspecialchars($autor) : 'Autor não informado' ?>
                            </p>
                            <p class="text-xs text-erpMuted truncate">
                                <?= $tipo !== '' ? htmlspecialchars($tipo) : 'Não classificado' ?>
                                <?php if ($lojaNome !== ''): ?>
                                    · <span class="font-medium"><?= htmlspecialchars($lojaNome) ?></span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold <?= $disponivel ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' ?>">
                            <?= $disponivel ? 'Disponível' : 'Indisponível' ?>
                        </span>
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <a href="/biblioteca/detalhes?id=<?= (int) ($item['id'] ?? 0) ?><?= $catalogScope === 'rede' ? '&scope=rede' : '' ?>"
                           class="w-full rounded-lg border border-erpBorder bg-erpSurface px-4 py-2.5 text-center text-sm font-semibold text-erpNavy transition hover:bg-erpBg">
                            Ver detalhes
                        </a>
                        <form method="post" action="/biblioteca/solicitar" class="w-full">
                            <input type="hidden" name="acervo_id" value="<?= (int) ($item['id'] ?? 0) ?>">
                            <input type="hidden" name="scope" value="<?= htmlspecialchars($catalogScope) ?>">
                            <input type="hidden" name="loja_id" value="<?= (int) ($item['loja_id'] ?? 0) ?>">
                            <button class="w-full rounded-lg bg-erpNavy px-4 py-2.5 text-center text-sm font-semibold text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50" <?= $disponivel ? '' : 'disabled' ?>>
                                <?= $disponivel ? 'Solicitar Empréstimo' : 'Indisponível' ?>
                            </button>
                        </form>
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
