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

<div class="pwa-premium-page" style="padding-top:0.75rem;">

    <!-- Barra de busca + ações -->
    <div style="display:flex;flex-direction:column;gap:0.625rem;margin-bottom:1rem;">
        <form method="get" action="/pwa/biblioteca" style="display:flex;gap:0.5rem;">
            <input type="text" name="q" value="<?= htmlspecialchars($q) ?>"
                   class="pwa-input"
                   style="flex:1;"
                   placeholder="Buscar no acervo...">
            <button type="submit" class="pwa-btn-primary" style="width:auto;padding:0 1.125rem;white-space:nowrap;">
                Buscar
            </button>
        </form>

        <div style="display:flex;gap:0.5rem;">
            <a href="/pwa/biblioteca/meus-emprestimos" class="pwa-btn-secondary" style="flex:1;text-align:center;font-size:0.8125rem;">
                Meus Empréstimos
            </a>
            <?php
            $authorizer = $GLOBALS['gestor_loja_authorizer'] ?? null;
            if ($authorizer instanceof \App\Core\Authorization\Authorizer && $authorizer->hasPermission('biblioteca.manage')):
            ?>
                <a href="/pwa/biblioteca/adicionar" style="
                    flex:1;display:flex;align-items:center;justify-content:center;gap:0.35rem;
                    padding:0.6875rem 0.75rem;
                    background:rgba(99,102,241,0.22);
                    border:1px solid rgba(99,102,241,0.35);
                    border-radius:0.75rem;
                    font-size:0.8125rem;font-weight:700;
                    color:#a5b4fc;text-decoration:none;
                    white-space:nowrap;
                ">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Adicionar
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filtros de escopo (rede) -->
    <?php if ($redeHabilitada): ?>
    <div style="display:flex;gap:0.5rem;margin-bottom:1rem;">
        <a href="/pwa/biblioteca?scope=minha<?= $q !== '' ? '&q=' . urlencode($q) : '' ?>"
           style="
               flex:1;text-align:center;padding:0.5rem 0.75rem;border-radius:999px;
               font-size:0.75rem;font-weight:700;text-decoration:none;
               <?= $catalogScope === 'minha'
                   ? 'background:#C9A227;color:#0f172a;'
                   : 'background:rgba(255,255,255,0.06);color:#94a3b8;border:1px solid rgba(255,255,255,0.10);' ?>
           ">Acervo da Loja</a>
        <a href="/pwa/biblioteca?scope=rede<?= $q !== '' ? '&q=' . urlencode($q) : '' ?>"
           style="
               flex:1;text-align:center;padding:0.5rem 0.75rem;border-radius:999px;
               font-size:0.75rem;font-weight:700;text-decoration:none;
               <?= $catalogScope === 'rede'
                   ? 'background:#C9A227;color:#0f172a;'
                   : 'background:rgba(255,255,255,0.06);color:#94a3b8;border:1px solid rgba(255,255,255,0.10);' ?>
           ">Acervo da Rede</a>
    </div>
    <?php endif; ?>

    <!-- Lista de livros -->
    <?php if ($lista === []): ?>
        <div style="
            border:1px solid rgba(255,255,255,0.09);
            background:rgba(255,255,255,0.04);
            border-radius:1.25rem;
            padding:2rem 1rem;
            text-align:center;
        ">
            <div style="font-size:2.5rem;margin-bottom:0.5rem;">📚</div>
            <p style="font-size:0.875rem;font-weight:700;color:#f1f5f9;margin:0 0 0.25rem;">Nenhum livro encontrado</p>
            <p style="font-size:0.8rem;color:#94a3b8;margin:0;">Ajuste os filtros ou busque por outro termo.</p>
        </div>
    <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:0.625rem;">
            <?php foreach ($lista as $item): ?>
            <?php
            $titulo          = (string) ($item['titulo'] ?? 'Livro');
            $autor           = (string) ($item['autor'] ?? '');
            $tipo            = (string) ($item['tipo'] ?? '');
            $lojaNome        = (string) ($item['loja_nome'] ?? '');
            $disponivel      = (bool)   ($item['disponivel'] ?? false);
            $grauRecomendado = (string) ($item['grau_recomendado'] ?? 'Livre');
            ?>
            <div style="
                border:1px solid rgba(255,255,255,0.09);
                background:rgba(255,255,255,0.05);
                border-radius:1.125rem;
                padding:1rem;
            ">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:0.75rem;">
                    <div style="min-width:0;flex:1;">
                        <h3 style="font-size:0.9375rem;font-weight:700;color:#f1f5f9;margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            <?= htmlspecialchars($titulo) ?>
                        </h3>
                        <p style="font-size:0.8125rem;color:#94a3b8;margin:0.15rem 0 0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            <?= $autor !== '' ? htmlspecialchars($autor) : 'Autor não informado' ?>
                        </p>
                        <div style="display:flex;align-items:center;flex-wrap:wrap;gap:0.4rem;margin-top:0.5rem;">
                            <?php if ($tipo !== ''): ?>
                            <span class="pwa-badge pwa-badge-muted"><?= htmlspecialchars($tipo) ?></span>
                            <?php endif; ?>
                            <?php if ($grauRecomendado !== 'Livre'): ?>
                            <span class="pwa-badge" style="background:rgba(167,139,250,0.18);color:#c4b5fd;"><?= htmlspecialchars($grauRecomendado) ?></span>
                            <?php endif; ?>
                            <?php if ($lojaNome !== ''): ?>
                            <span class="pwa-badge pwa-badge-muted"><?= htmlspecialchars($lojaNome) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <span class="pwa-badge <?= $disponivel ? 'pwa-badge-success' : 'pwa-badge-muted' ?>" style="flex-shrink:0;">
                        <?= $disponivel ? 'Disponível' : 'Indisponível' ?>
                    </span>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.5rem;margin-top:0.875rem;">
                    <a href="/pwa/biblioteca/detalhes?id=<?= (int) ($item['id'] ?? 0) ?><?= $catalogScope === 'rede' ? '&loja_id=' . (int)($item['loja_id']??0) : '' ?>"
                       class="pwa-btn-secondary" style="font-size:0.8125rem;text-align:center;">
                        Ver detalhes
                    </a>
                    <form method="post" action="/pwa/biblioteca/solicitar">
                        <input type="hidden" name="acervo_id" value="<?= (int) ($item['id'] ?? 0) ?>">
                        <input type="hidden" name="scope" value="<?= htmlspecialchars($catalogScope) ?>">
                        <input type="hidden" name="loja_id" value="<?= (int) ($item['loja_id'] ?? 0) ?>">
                        <button class="pwa-btn-primary" style="font-size:0.8125rem;" <?= $disponivel ? '' : 'disabled' ?>>
                            Solicitar
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
