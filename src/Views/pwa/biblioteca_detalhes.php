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

$pwaPageTitle = 'Detalhes';
$pwaShowBackButton = true;
$pwaBackUrl = '/pwa/biblioteca';

ob_start();
?>

<div class="px-4 py-4 space-y-4">
    <!-- Bloco Principal -->
    <div class="pwa-card flex flex-col gap-4 border border-white/5 bg-slate-900/40">
        <div class="flex items-start gap-4">
            <?php if ($capaUrl !== ''): ?>
                <img src="<?= htmlspecialchars($capaUrl) ?>" alt="Capa" class="h-28 w-20 rounded-lg object-cover shadow-md shrink-0">
            <?php else: ?>
                <div class="flex h-28 w-20 shrink-0 items-center justify-center rounded-lg bg-slate-950 border border-white/5 text-slate-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </div>
            <?php endif; ?>
            
            <div class="flex-1 min-w-0">
                <h2 class="text-sm font-bold text-slate-100 leading-snug"><?= htmlspecialchars($titulo) ?></h2>
                <p class="text-xs text-slate-400 mt-1 font-medium"><?= $autor !== '' ? htmlspecialchars($autor) : 'Autor não informado' ?></p>
                <div class="mt-2.5 flex flex-wrap gap-1.5 select-none">
                    <span class="pwa-badge <?= $disponivel ? 'pwa-badge-success' : 'pwa-badge-muted' ?>">
                        <?= $disponivel ? 'Disponível (' . $quantidade . ')' : 'Indisponível' ?>
                    </span>
                    <span class="pwa-badge pwa-badge-muted">
                        <?= htmlspecialchars($tipo) ?>
                    </span>
                    <?php if ($lojaNome !== ''): ?>
                        <span class="pwa-badge bg-indigo-500/10 text-indigo-300">
                            <?= htmlspecialchars($lojaNome) ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($resumo !== ''): ?>
            <div class="rounded-xl p-3 text-xs leading-relaxed bg-slate-950 border border-white/5 text-slate-300 select-text">
                <?= nl2br(htmlspecialchars($resumo)) ?>
            </div>
        <?php endif; ?>

        <?php if ($grauRecomendado !== 'Livre' || $notaInstrucao !== ''): ?>
            <div class="rounded-xl p-3.5 bg-indigo-500/5 border border-indigo-500/10 space-y-1 text-xs">
                <p class="text-[10px] font-bold uppercase tracking-wider text-indigo-400">Recomendação de Instrução</p>
                <p class="font-semibold text-indigo-300">Grau: <?= htmlspecialchars($grauRecomendado) ?></p>
                <?php if ($notaInstrucao !== ''): ?>
                    <p class="mt-2 italic text-indigo-300">"<?= nl2br(htmlspecialchars($notaInstrucao)) ?>"</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="/pwa/biblioteca/solicitar" class="pt-1 select-none">
            <input type="hidden" name="acervo_id" value="<?= (int) ($item['id'] ?? 0) ?>">
            <input type="hidden" name="loja_id" value="<?= (int) ($item['loja_id'] ?? 0) ?>">
            <button type="submit" class="pwa-btn-primary" <?= $disponivel ? '' : 'disabled' ?>>
                <?= $disponivel ? 'Solicitar Empréstimo' : 'Indisponível no Momento' ?>
            </button>
        </form>

        <div class="grid grid-cols-2 gap-2 select-none">
            <form method="post" action="/pwa/biblioteca/reagir" class="w-full">
                <input type="hidden" name="acervo_id" value="<?= (int) ($item['id'] ?? 0) ?>">
                <input type="hidden" name="gostei" value="1">
                <button type="submit" class="pwa-btn-secondary text-xs py-1.5 font-bold border-emerald-500/20 bg-emerald-500/10 text-emerald-300 hover:bg-emerald-500/20">Gostei</button>
            </form>
            <form method="post" action="/pwa/biblioteca/reagir" class="w-full">
                <input type="hidden" name="acervo_id" value="<?= (int) ($item['id'] ?? 0) ?>">
                <input type="hidden" name="gostei" value="0">
                <button type="submit" class="pwa-btn-secondary text-xs py-1.5 font-bold">Não gostei</button>
            </form>
        </div>
    </div>

    <!-- Bloco Administrativo: Classificação -->
    <?php if ($podeClassificar): ?>
        <details class="group pwa-card p-0 overflow-hidden border border-indigo-500/25 bg-indigo-500/5">
            <summary class="cursor-pointer p-4 list-none flex items-center justify-between gap-3 select-none active:bg-indigo-500/10 transition-colors">
                <div class="flex items-center gap-2 text-indigo-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.381z" clip-rule="evenodd" />
                    </svg>
                    <span class="font-bold text-xs">Classificar Obra (Admin)</span>
                </div>
                <div class="transition-transform group-open:rotate-180 text-indigo-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </div>
            </summary>
            
            <div class="p-4 pt-0 space-y-4 border-t border-indigo-500/20">
                <form method="post" action="/pwa/biblioteca/classificar" class="space-y-3.5 pt-2">
                    <input type="hidden" name="livro_id" value="<?= (int) ($item['id'] ?? 0) ?>">
                    
                    <div>
                        <label for="grau_recomendado" class="pwa-label text-indigo-400">Grau Recomendado</label>
                        <select name="grau_recomendado" id="grau_recomendado" class="pwa-select">
                            <option value="Livre" <?= $grauRecomendado === 'Livre' ? 'selected' : '' ?>>Livre (Todos)</option>
                            <option value="Aprendiz" <?= $grauRecomendado === 'Aprendiz' ? 'selected' : '' ?>>Aprendiz</option>
                            <option value="Companheiro" <?= $grauRecomendado === 'Companheiro' ? 'selected' : '' ?>>Companheiro</option>
                            <option value="Mestre" <?= $grauRecomendado === 'Mestre' ? 'selected' : '' ?>>Mestre</option>
                        </select>
                    </div>

                    <div>
                        <label for="nota_instrucao" class="pwa-label text-indigo-400">Nota de Instrução (Opcional)</label>
                        <textarea name="nota_instrucao" id="nota_instrucao" rows="2" class="pwa-textarea" placeholder="Ex: Livro excelente para entender o simbolismo do painel..."><?= htmlspecialchars($notaInstrucao) ?></textarea>
                    </div>

                    <button type="submit" class="pwa-btn-secondary text-xs font-bold py-2 bg-indigo-500/10 border-indigo-500/20 text-indigo-300 w-full select-none">
                        Salvar Classificação
                    </button>
                </form>
            </div>
        </details>
    <?php endif; ?>
    
    <!-- Comentar Form -->
    <div class="pwa-card space-y-3 border border-white/5">
        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Escrever Comentário</h3>
        <form method="post" action="/pwa/biblioteca/comentar" class="space-y-3">
            <input type="hidden" name="acervo_id" value="<?= (int) ($item['id'] ?? 0) ?>">
            <textarea name="comentario" rows="3" class="pwa-textarea" placeholder="Registre uma impressão ou recomendação para os irmãos."></textarea>
            <button type="submit" class="pwa-btn-primary">Publicar comentário</button>
        </form>
    </div>

    <!-- Lista de Comentários -->
    <?php if ($comentarios !== []): ?>
        <div class="pwa-card space-y-3.5 border border-white/5">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Comentários dos Irmãos</h3>
            <div class="pwa-list-group select-text">
                <?php foreach ($comentarios as $comentario): ?>
                    <div class="pwa-list-item flex flex-col items-start gap-1.5 justify-center">
                        <p class="text-xs font-bold text-slate-200"><?= htmlspecialchars((string) ($comentario['obreiro_nome'] ?? 'Irmão')) ?></p>
                        <p class="text-xs text-slate-300 leading-relaxed"><?= nl2br(htmlspecialchars((string) ($comentario['comentario'] ?? ''))) ?></p>
                        <p class="text-[9px] text-slate-500 font-medium"><?= htmlspecialchars((string) ($comentario['criado_em'] ?? '')) ?></p>
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
