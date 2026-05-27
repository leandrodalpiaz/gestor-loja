<?php
declare(strict_types=1);

$appShellEyebrow = 'Biblioteca';
$appShellTitle = 'Trabalhos & Peças';
$appShellDescription = 'Trabalhos de arquitetura e instruções arquivados pela Secretaria para pesquisa.';
$appShellActiveHref = '/biblioteca/trabalhos';
$appShellActions = [['label' => 'Voltar', 'href' => '/biblioteca']];

$itens = is_array($itens ?? null) ? $itens : [];

require __DIR__ . '/../partials/erp_shell_open.php';
?>

<div class="card depth-1">
    <div class="card-header border-b border-erp-border/50 p-6">
        <h2 class="text-xl font-black text-white tracking-tight">Trabalhos Disponíveis</h2>
        <p class="text-sm text-erp-muted mt-1 font-medium font-sans">Acervo digitalizado para consulta por grau de instrução.</p>
    </div>
    <div class="card-body p-6 space-y-4">
        <?php if ($itens === []): ?>
            <div class="text-center py-12 bg-white/[0.01] border border-white/5 rounded-2xl">
                <span class="text-4xl block mb-3 opacity-30">📚</span>
                <h3 class="text-sm font-bold text-white mb-1">Nenhum trabalho localizado</h3>
                <p class="text-xs text-slate-400 max-w-md mx-auto">Você visualiza apenas os trabalhos correspondentes ou inferiores ao seu grau atual de instrução.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach ($itens as $t): ?>
                    <?php 
                    $grau = trim((string) ($t['grau_sessao'] ?? ''));
                    $grauLower = strtolower($grau);
                    if (str_contains($grauLower, 'mestre')) {
                        $badgeClass = 'bg-rose-900/30 text-rose-300 border-rose-800/40';
                        $grauLabel = 'Mestre';
                    } elseif (str_contains($grauLower, 'companheiro')) {
                        $badgeClass = 'bg-amber-900/30 text-amber-300 border-amber-800/40';
                        $grauLabel = 'Companheiro';
                    } else {
                        $badgeClass = 'bg-blue-900/30 text-blue-300 border-blue-800/40';
                        $grauLabel = 'Aprendiz';
                    }
                    ?>
                    <div class="p-5 rounded-2xl bg-white/[0.02] border border-white/5 hover:border-white/10 transition-all flex flex-col justify-between gap-4">
                        <div>
                            <div class="flex items-start justify-between gap-3 mb-2">
                                <h3 class="font-bold text-white text-base leading-snug line-clamp-2"><?= htmlspecialchars((string) ($t['titulo'] ?? '')) ?></h3>
                                <span class="badge border text-[10px] font-black uppercase tracking-widest px-2 py-0.5 rounded-full shrink-0 <?= $badgeClass ?>">
                                    <?= $grauLabel ?>
                                </span>
                            </div>
                            <div class="text-xs text-slate-400 space-y-1 mt-2">
                                <p><span class="text-slate-500">Apresentado em:</span> <?= htmlspecialchars((string) ($t['sessao_titulo'] ?? 'Sessão')) ?></p>
                                <?php if (!empty($t['autor_nome'])): ?>
                                    <p><span class="text-slate-500">Autor:</span> <?= htmlspecialchars((string) $t['autor_nome']) ?></p>
                                <?php endif; ?>
                                <p><span class="text-slate-500">Tipo:</span> <?= htmlspecialchars(str_replace('_', ' ', (string) ($t['tipo_trabalho'] ?? 'Peça'))) ?></p>
                            </div>
                        </div>
                        <?php if (!empty($t['arquivo_pdf_path'])): ?>
                            <div class="mt-2 border-t border-white/5 pt-3 text-right">
                                <a class="btn border border-erp-gold/30 hover:border-erp-gold text-erp-gold hover:bg-erp-gold/10 text-xs font-black uppercase tracking-widest px-4 py-2" 
                                   href="<?= htmlspecialchars((string) $t['arquivo_pdf_path']) ?>" 
                                   target="_blank" 
                                   rel="noopener">
                                    Visualizar PDF
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../partials/erp_shell_close.php'; ?>
