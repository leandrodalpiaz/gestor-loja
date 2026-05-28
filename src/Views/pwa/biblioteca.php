<?php
declare(strict_types=1);

$lista          = is_array($itens ?? null) ? $itens : [];
$catalogScope   = (string) ($catalogScope ?? 'minha');
$redeHabilitada = (bool) ($bibliotecaRedeHabilitada ?? false);
$q              = trim((string) ($_GET['q'] ?? ''));

$pwaPageTitle     = 'Biblioteca';
$pwaShowBackButton = false;
$pwaActiveTab     = 'biblioteca';

ob_start();
?>

<div class="px-4 py-4 space-y-4">

    <!-- Barra de busca + ações -->
    <div class="space-y-3">
        <form method="get" action="/pwa/biblioteca" class="flex gap-2 select-none">
            <input type="text" name="q" value="<?= htmlspecialchars($q) ?>"
                   class="pwa-input flex-1"
                   placeholder="Buscar no acervo...">
            <button type="submit" class="pwa-btn-primary py-0 w-auto px-4 text-xs font-bold">
                Buscar
            </button>
        </form>

        <div class="flex gap-2 select-none">
            <a href="/pwa/biblioteca/meus-emprestimos" class="pwa-btn-secondary flex-1 text-xs font-bold py-2">
                Meus Empréstimos
            </a>
            <?php
            $authorizer = $GLOBALS['gestor_loja_authorizer'] ?? null;
            if ($authorizer instanceof \App\Core\Authorization\Authorizer && $authorizer->hasPermission('biblioteca.manage')):
            ?>
                <a href="/pwa/biblioteca/adicionar" class="pwa-btn-secondary flex-1 text-xs font-bold py-2 bg-indigo-500/10 border-indigo-500/20 text-indigo-300">
                    <svg class="h-4 w-4 mr-1.5 inline-block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Adicionar Livro
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filtros de escopo (rede) -->
    <?php if ($redeHabilitada): ?>
    <div class="flex bg-slate-900/60 p-1 rounded-lg border border-white/5 select-none">
        <a href="/pwa/biblioteca?scope=minha<?= $q !== '' ? '&q=' . urlencode($q) : '' ?>"
           class="flex-1 py-1.5 text-center text-xs rounded-md transition-all active:scale-[0.98] no-underline font-semibold <?= $catalogScope === 'minha' ? 'bg-slate-800 text-white shadow-sm' : 'text-slate-400' ?>">
            Acervo da Loja
        </a>
        <a href="/pwa/biblioteca?scope=rede<?= $q !== '' ? '&q=' . urlencode($q) : '' ?>"
           class="flex-1 py-1.5 text-center text-xs rounded-md transition-all active:scale-[0.98] no-underline font-semibold <?= $catalogScope === 'rede' ? 'bg-slate-800 text-white shadow-sm' : 'text-slate-400' ?>">
            Acervo da Rede
        </a>
    </div>
    <?php endif; ?>

    <!-- Lista de livros -->
    <div class="space-y-3.5 pb-4">
        <div class="flex items-center gap-3">
            <p class="text-[10px] font-bold tracking-wider uppercase text-slate-500">
                Acervo Disponível
            </p>
            <div class="flex-1 h-[1px] bg-white/5"></div>
        </div>

        <?php if ($lista === []): ?>
            <div class="p-8 text-center text-xs text-slate-500 bg-slate-900/40 rounded-2xl border border-dashed border-white/10 select-none">
                <div class="text-xl mb-1.5 select-none">📚</div>
                <p class="font-bold text-slate-300">Nenhum livro encontrado</p>
                <p class="mt-0.5">Ajuste os filtros ou busque por outro termo.</p>
            </div>
        <?php else: ?>
            <?php foreach ($lista as $item): ?>
                <?php
                $titulo          = (string) ($item['titulo'] ?? 'Livro');
                $autor           = (string) ($item['autor'] ?? '');
                $tipo            = (string) ($item['tipo'] ?? '');
                $lojaNome        = (string) ($item['loja_nome'] ?? '');
                $disponivel      = (bool)   ($item['disponivel'] ?? false);
                $grauRecomendado = (string) ($item['grau_recomendado'] ?? 'Livre');
                ?>
                <div class="pwa-card border border-white/5 flex flex-col gap-3 bg-slate-900/40">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <h3 class="text-xs font-bold text-slate-200 truncate">
                                <?= htmlspecialchars($titulo) ?>
                            </h3>
                            <p class="text-[10px] text-slate-400 mt-0.5 truncate">
                                <?= $autor !== '' ? htmlspecialchars($autor) : 'Autor não informado' ?>
                            </p>
                            <div class="flex items-center flex-wrap gap-1.5 mt-2 select-none">
                                <?php if ($tipo !== ''): ?>
                                    <span class="pwa-badge pwa-badge-muted"><?= htmlspecialchars($tipo) ?></span>
                                <?php endif; ?>
                                <?php if ($grauRecomendado !== 'Livre'): ?>
                                    <span class="pwa-badge bg-indigo-500/10 text-indigo-300"><?= htmlspecialchars($grauRecomendado) ?></span>
                                <?php endif; ?>
                                <?php if ($lojaNome !== ''): ?>
                                    <span class="pwa-badge pwa-badge-muted"><?= htmlspecialchars($lojaNome) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <span class="pwa-badge <?= $disponivel ? 'pwa-badge-success' : 'pwa-badge-muted' ?> shrink-0 select-none">
                            <?= $disponivel ? 'Disponível' : 'Indisponível' ?>
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-2 mt-1 select-none">
                        <a href="/pwa/biblioteca/detalhes?id=<?= (int) ($item['id'] ?? 0) ?><?= $catalogScope === 'rede' ? '&loja_id=' . (int)($item['loja_id']??0) : '' ?>"
                           class="pwa-btn-secondary py-1.5 text-[11px] font-bold">
                            Detalhes
                        </a>
                        <form method="post" action="/pwa/biblioteca/solicitar" class="w-full">
                            <input type="hidden" name="acervo_id" value="<?= (int) ($item['id'] ?? 0) ?>">
                            <input type="hidden" name="scope" value="<?= htmlspecialchars($catalogScope) ?>">
                            <input type="hidden" name="loja_id" value="<?= (int) ($item['loja_id'] ?? 0) ?>">
                            <button class="pwa-btn-primary py-1.5 text-[11px] font-bold disabled:opacity-50" <?= $disponivel ? '' : 'disabled' ?>>
                                Solicitar
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php
$pwaContent = ob_get_clean();
require __DIR__ . '/shell.php';
?>
