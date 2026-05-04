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
    <div class="rounded-2xl border border-erpBorder bg-erpSurface p-5 shadow-sm space-y-5">
        <div class="flex items-start gap-4">
            <?php if ($capaUrl !== ''): ?>
                <img src="<?= htmlspecialchars($capaUrl) ?>" alt="Capa" class="h-32 w-24 rounded-lg object-cover shadow-sm">
            <?php else: ?>
                <div class="flex h-32 w-24 shrink-0 items-center justify-center rounded-lg bg-erpBg text-erpMuted">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </div>
            <?php endif; ?>
            
            <div class="flex-1 min-w-0">
                <h2 class="text-xl font-bold text-erpNavy leading-tight"><?= htmlspecialchars($titulo) ?></h2>
                <p class="mt-1 text-sm font-medium text-erpMuted"><?= $autor !== '' ? htmlspecialchars($autor) : 'Autor não informado' ?></p>
                <div class="mt-2 flex flex-wrap gap-2">
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[0.65rem] font-semibold uppercase tracking-wider <?= $disponivel ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' ?>">
                        <?= $disponivel ? 'Disponível (' . $quantidade . ')' : 'Indisponível' ?>
                    </span>
                    <span class="inline-flex items-center rounded-full bg-erpBg px-2.5 py-0.5 text-[0.65rem] font-semibold text-erpText uppercase tracking-wider border border-erpBorder">
                        <?= htmlspecialchars($tipo) ?>
                    </span>
                    <?php if ($lojaNome !== ''): ?>
                        <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-0.5 text-[0.65rem] font-semibold text-indigo-700 uppercase tracking-wider border border-indigo-100">
                            Loja: <?= htmlspecialchars($lojaNome) ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($resumo !== ''): ?>
            <div class="rounded-lg bg-erpBg p-3 text-sm text-erpText leading-relaxed">
                <?= nl2br(htmlspecialchars($resumo)) ?>
            </div>
        <?php endif; ?>

        <?php if ($grauRecomendado !== 'Livre' || $notaInstrucao !== ''): ?>
            <div class="rounded-lg border border-indigo-100 bg-indigo-50/50 p-4">
                <p class="text-xs font-bold uppercase tracking-wider text-indigo-800 mb-1">Recomendação de Instrução</p>
                <p class="text-sm font-semibold text-indigo-900">Grau: <?= htmlspecialchars($grauRecomendado) ?></p>
                <?php if ($notaInstrucao !== ''): ?>
                    <p class="mt-2 text-sm text-indigo-800/80 italic">"<?= nl2br(htmlspecialchars($notaInstrucao)) ?>"</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="/biblioteca/solicitar" class="pt-2">
            <input type="hidden" name="acervo_id" value="<?= (int) ($item['id'] ?? 0) ?>">
            <input type="hidden" name="loja_id" value="<?= (int) ($item['loja_id'] ?? 0) ?>">
            <!-- Utiliza o mesmo form desktop, mas no PWA -->
            <button type="submit" class="w-full rounded-lg bg-erpNavy px-4 py-3 text-sm font-semibold text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50" <?= $disponivel ? '' : 'disabled' ?>>
                <?= $disponivel ? 'Solicitar Empréstimo' : 'Indisponível no Momento' ?>
            </button>
        </form>
    </div>

    <!-- Bloco Administrativo: Classificação -->
    <?php if ($podeClassificar): ?>
        <details class="group rounded-2xl border border-indigo-200 bg-indigo-50 shadow-sm overflow-hidden">
            <summary class="cursor-pointer p-4 list-none flex items-center justify-between gap-3 transition-colors hover:bg-indigo-100/50">
                <div class="flex items-center gap-2 text-indigo-900">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.381z" clip-rule="evenodd" />
                    </svg>
                    <span class="font-bold">Classificar Obra (Admin)</span>
                </div>
                <div class="text-indigo-700 transition-transform group-open:rotate-180">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </div>
            </summary>
            
            <div class="p-4 pt-0 border-t border-indigo-100 mt-2">
                <form method="post" action="/pwa/biblioteca/classificar" class="space-y-4 pt-2">
                    <input type="hidden" name="livro_id" value="<?= (int) ($item['id'] ?? 0) ?>">
                    
                    <div class="space-y-1.5">
                        <label for="grau_recomendado" class="block text-sm font-semibold text-indigo-900">Grau Recomendado</label>
                        <select name="grau_recomendado" id="grau_recomendado" class="w-full rounded-lg border border-indigo-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
                            <option value="Livre" <?= $grauRecomendado === 'Livre' ? 'selected' : '' ?>>Livre (Todos)</option>
                            <option value="Aprendiz" <?= $grauRecomendado === 'Aprendiz' ? 'selected' : '' ?>>Aprendiz</option>
                            <option value="Companheiro" <?= $grauRecomendado === 'Companheiro' ? 'selected' : '' ?>>Companheiro</option>
                            <option value="Mestre" <?= $grauRecomendado === 'Mestre' ? 'selected' : '' ?>>Mestre</option>
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label for="nota_instrucao" class="block text-sm font-semibold text-indigo-900">Nota de Instrução (Opcional)</label>
                        <textarea name="nota_instrucao" id="nota_instrucao" rows="2" class="w-full rounded-lg border border-indigo-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none" placeholder="Ex: Livro excelente para entender o simbolismo do painel..."><?= htmlspecialchars($notaInstrucao) ?></textarea>
                    </div>

                    <button type="submit" class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700">
                        Salvar Classificação
                    </button>
                </form>
            </div>
        </details>
    <?php endif; ?>
    
    <!-- Comentários (apenas listagem simples para o PWA) -->
    <?php if ($comentarios !== []): ?>
        <div class="rounded-2xl border border-erpBorder bg-erpSurface p-5 shadow-sm space-y-4">
            <h3 class="font-bold text-erpNavy">Comentários dos Irmãos</h3>
            <div class="space-y-3">
                <?php foreach ($comentarios as $comentario): ?>
                    <div class="rounded-lg bg-erpBg p-3">
                        <p class="text-xs font-semibold text-erpNavy"><?= htmlspecialchars((string) ($comentario['obreiro_nome'] ?? 'Irmão')) ?></p>
                        <p class="mt-1 text-sm text-erpText"><?= nl2br(htmlspecialchars((string) ($comentario['comentario'] ?? ''))) ?></p>
                        <p class="mt-1 text-[0.65rem] text-erpMuted"><?= htmlspecialchars((string) ($comentario['criado_em'] ?? '')) ?></p>
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
