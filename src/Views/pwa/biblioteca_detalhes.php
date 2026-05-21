<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

$item = is_array($item ?? null) ? $item : [];
$comentarios = is_array($comentarios ?? null) ? $comentarios : [];
$permissoes = is_array($permissoes ?? null) ? $permissoes : [];

$podeClassificar = !empty($permissoes['biblioteca.classificar']);

$titulo = (string) ($item['titulo'] ?? 'Livro');
$autor = (string) ($item['autor'] ?? '');
$resumo = (string) ($item['resumo'] ?? '');
$capaUrl = (string) ($item['capa_url'] ?? '');
$disponivel = (bool) ($item['disponivel'] ?? false);
$quantidade = (int) ($item['quantidade_disponivel'] ?? 0);
$grauRecomendado = (string) ($item['grau_recomendado'] ?? 'Livre');
$notaInstrucao = (string) ($item['nota_instrucao'] ?? '');
$tipo = (string) ($item['tipo'] ?? '');
$lojaNome = (string) ($item['loja_nome'] ?? '');

$pwaPageTitle = 'Detalhes do Acervo';
$pwaShowBackButton = true;
$pwaBackUrl = '/pwa/biblioteca';

ob_start();
?>

<div class="p-4 sm:p-6 space-y-4">
    <!-- Bloco Principal -->
    <div class="rounded-2xl p-5 shadow-sm space-y-5" style="background:rgba(255,255,255,0.055);border:1px solid rgba(255,255,255,0.09);">
        <div class="flex items-start gap-4">
            <?php if ($capaUrl !== ''): ?>
                <img src="<?= htmlspecialchars($capaUrl) ?>" alt="Capa" class="h-32 w-24 rounded-lg object-cover shadow-sm">
            <?php else: ?>
                <div class="flex h-32 w-24 shrink-0 items-center justify-center rounded-lg" style="background:rgba(255,255,255,0.03);color:#94a3b8;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </div>
            <?php endif; ?>
            
            <div class="flex-1 min-w-0">
                <h2 class="text-xl font-bold leading-tight" style="color:#f1f5f9;"><?= htmlspecialchars($titulo) ?></h2>
                <p class="mt-1 text-sm font-medium" style="color:#94a3b8;"><?= $autor !== '' ? htmlspecialchars($autor) : 'Autor não informado' ?></p>
                <div class="mt-2 flex flex-wrap gap-2">
                    <span class="inline-flex items-center text-[0.65rem] font-semibold uppercase tracking-wider <?= $disponivel ? '' : '' ?>" style="<?= $disponivel ? 'background:rgba(52,211,153,0.15);color:#6ee7b7;border-radius:999px;padding:0.2rem 0.55rem;font-size:0.65rem;font-weight:700;' : 'background:rgba(255,255,255,0.04);color:#94a3b8;border-radius:999px;padding:0.2rem 0.55rem;font-size:0.65rem;font-weight:700;' ?>">
                        <?= $disponivel ? 'Disponível (' . $quantidade . ')' : 'Indisponível' ?>
                    </span>
                    <span class="inline-flex items-center text-[0.65rem] font-semibold uppercase tracking-wider" style="background:rgba(255,255,255,0.03);color:#e2e8f0;border-radius:999px;padding:0.2rem 0.55rem;border:1px solid rgba(255,255,255,0.09);">
                        <?= htmlspecialchars($tipo) ?>
                    </span>
                    <?php if ($lojaNome !== ''): ?>
                        <span class="inline-flex items-center text-[0.65rem] font-semibold uppercase tracking-wider" style="background:rgba(99,102,241,0.15);color:#a5b4fc;border-radius:999px;padding:0.2rem 0.55rem;border:1px solid rgba(99,102,241,0.25);">
                            Loja: <?= htmlspecialchars($lojaNome) ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($resumo !== ''): ?>
            <div class="rounded-lg p-3 text-sm leading-relaxed" style="background:rgba(255,255,255,0.03);color:#e2e8f0;">
                <?= nl2br(htmlspecialchars($resumo)) ?>
            </div>
        <?php endif; ?>

        <?php if ($grauRecomendado !== 'Livre' || $notaInstrucao !== ''): ?>
            <div class="rounded-lg p-4" style="background:rgba(99,102,241,0.1);border:1px solid rgba(99,102,241,0.25);">
                <p class="text-xs font-bold uppercase tracking-wider mb-1" style="color:#a5b4fc;">Recomendação de Instrução</p>
                <p class="text-sm font-semibold" style="color:#c7d2fe;">Grau: <?= htmlspecialchars($grauRecomendado) ?></p>
                <?php if ($notaInstrucao !== ''): ?>
                    <p class="mt-2 text-sm italic" style="color:#a5b4fc;">"<?= nl2br(htmlspecialchars($notaInstrucao)) ?>"</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="/pwa/biblioteca/solicitar" class="pt-2">
            <input type="hidden" name="acervo_id" value="<?= (int) ($item['id'] ?? 0) ?>">
            <input type="hidden" name="loja_id" value="<?= (int) ($item['loja_id'] ?? 0) ?>">
            <!-- Utiliza o mesmo form desktop, mas no PWA -->
            <button type="submit" class="w-full px-4 py-3 text-sm font-semibold transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50" style="background:#C9A227;color:#0f172a;border-radius:0.625rem;" <?= $disponivel ? '' : 'disabled' ?>>
                <?= $disponivel ? 'Solicitar Empréstimo' : 'Indisponível no Momento' ?>
            </button>
        </form>

        <div class="grid grid-cols-2 gap-2">
            <form method="post" action="/pwa/biblioteca/reagir">
                <input type="hidden" name="acervo_id" value="<?= (int) ($item['id'] ?? 0) ?>">
                <input type="hidden" name="gostei" value="1">
                <button type="submit" class="w-full px-3 py-2 text-sm font-semibold" style="background:rgba(52,211,153,0.15);color:#6ee7b7;border-radius:0.5rem;border:1px solid rgba(52,211,153,0.25);">Gostei</button>
            </form>
            <form method="post" action="/pwa/biblioteca/reagir">
                <input type="hidden" name="acervo_id" value="<?= (int) ($item['id'] ?? 0) ?>">
                <input type="hidden" name="gostei" value="0">
                <button type="submit" class="w-full px-3 py-2 text-sm font-semibold" style="background:rgba(255,255,255,0.04);color:#94a3b8;border-radius:0.5rem;border:1px solid rgba(255,255,255,0.09);">Nao gostei</button>
            </form>
        </div>
    </div>

    <!-- Bloco Administrativo: Classificação -->
    <?php if ($podeClassificar): ?>
        <details class="group rounded-2xl shadow-sm overflow-hidden" style="background:rgba(99,102,241,0.1);border:1px solid rgba(99,102,241,0.25);">
            <summary class="cursor-pointer p-4 list-none flex items-center justify-between gap-3 transition-colors hover:bg-white/5">
                <div class="flex items-center gap-2" style="color:#a5b4fc;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.381z" clip-rule="evenodd" />
                    </svg>
                    <span class="font-bold">Classificar Obra (Admin)</span>
                </div>
                <div class="transition-transform group-open:rotate-180" style="color:#a5b4fc;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </div>
            </summary>
            
            <div class="p-4 pt-0 mt-2" style="border-top:1px solid rgba(99,102,241,0.25);">
                <form method="post" action="/pwa/biblioteca/classificar" class="space-y-4 pt-2">
                    <input type="hidden" name="livro_id" value="<?= (int) ($item['id'] ?? 0) ?>">
                    
                    <div class="space-y-1.5">
                        <label for="grau_recomendado" class="block text-sm font-semibold" style="color:#a5b4fc;">Grau Recomendado</label>
                        <select name="grau_recomendado" id="grau_recomendado" class="w-full rounded-lg px-3 py-2 text-sm focus:outline-none" style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.10);color:#f1f5f9;">
                            <option value="Livre" <?= $grauRecomendado === 'Livre' ? 'selected' : '' ?>>Livre (Todos)</option>
                            <option value="Aprendiz" <?= $grauRecomendado === 'Aprendiz' ? 'selected' : '' ?>>Aprendiz</option>
                            <option value="Companheiro" <?= $grauRecomendado === 'Companheiro' ? 'selected' : '' ?>>Companheiro</option>
                            <option value="Mestre" <?= $grauRecomendado === 'Mestre' ? 'selected' : '' ?>>Mestre</option>
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label for="nota_instrucao" class="block text-sm font-semibold" style="color:#a5b4fc;">Nota de Instrução (Opcional)</label>
                        <textarea name="nota_instrucao" id="nota_instrucao" rows="2" class="w-full rounded-lg px-3 py-2 text-sm focus:outline-none" style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.10);color:#f1f5f9;" placeholder="Ex: Livro excelente para entender o simbolismo do painel..."><?= htmlspecialchars($notaInstrucao) ?></textarea>
                    </div>

                    <button type="submit" class="w-full px-4 py-2 text-sm font-semibold transition hover:opacity-90" style="background:#1e3a5f;color:#f1f5f9;border-radius:0.625rem;">
                        Salvar Classificação
                    </button>
                </form>
            </div>
        </details>
    <?php endif; ?>
    
    <div class="rounded-2xl p-5 shadow-sm" style="background:rgba(255,255,255,0.055);border:1px solid rgba(255,255,255,0.09);">
        <h3 class="font-bold" style="color:#f1f5f9;">Comentar</h3>
        <form method="post" action="/pwa/biblioteca/comentar" class="mt-3 space-y-3">
            <input type="hidden" name="acervo_id" value="<?= (int) ($item['id'] ?? 0) ?>">
            <textarea name="comentario" rows="3" class="w-full rounded-lg px-3 py-2 text-sm focus:outline-none" style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.10);color:#f1f5f9;" placeholder="Registre uma impressao ou recomendacao para os irmaos."></textarea>
            <button type="submit" class="w-full px-4 py-2.5 text-sm font-semibold" style="background:#1e3a5f;color:#f1f5f9;border-radius:0.625rem;">Publicar comentario</button>
        </form>
    </div>

    <!-- Comentários -->
    <?php if ($comentarios !== []): ?>
        <div class="rounded-2xl p-5 shadow-sm space-y-4" style="background:rgba(255,255,255,0.055);border:1px solid rgba(255,255,255,0.09);">
            <h3 class="font-bold" style="color:#f1f5f9;">Comentários dos Irmãos</h3>
            <div class="space-y-3">
                <?php foreach ($comentarios as $comentario): ?>
                    <div class="rounded-lg p-3" style="background:rgba(255,255,255,0.04);border-radius:0.5rem;">
                        <p class="text-xs font-semibold" style="color:#f1f5f9;"><?= htmlspecialchars((string) ($comentario['obreiro_nome'] ?? 'Irmão')) ?></p>
                        <p class="mt-1 text-sm" style="color:#e2e8f0;"><?= nl2br(htmlspecialchars((string) ($comentario['comentario'] ?? ''))) ?></p>
                        <p class="mt-1 text-[0.65rem]" style="color:#94a3b8;"><?= htmlspecialchars((string) ($comentario['criado_em'] ?? '')) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php
$pwaContent = ob_get_clean();
require __DIR__ . '/shell.php';
?>
