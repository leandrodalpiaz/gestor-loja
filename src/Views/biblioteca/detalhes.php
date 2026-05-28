<?php
declare(strict_types=1);

// #############################################################################
// LÓGICA DE NEGÓCIO E HELPERS
// #############################################################################

$podeSolicitar = (bool) ($podeSolicitar ?? !empty($_SESSION['usuario_logado']) || !empty($_SESSION['usuario_id']));
$lojaIdDetalhe = (int) ($_GET['loja_id'] ?? 0);
$voltarHref = $lojaIdDetalhe > 0 ? '/biblioteca?acervo=rede' : '/biblioteca';

$formatDate = static fn($dateStr) => !empty($dateStr) ? (new DateTime($dateStr))->format('d/m/Y H:i') : '';
$formatGrau = static fn($grau) => $grau ? ucfirst(strtolower($grau)) : 'Livre';
$item = $item ?? [];
$comentarios = $comentarios ?? [];

// #############################################################################
// CONFIGURAÇÃO DO APP SHELL
// #############################################################################

$appShellEyebrow = 'Biblioteca';
$appShellTitle = 'Ficha Editorial';
$appShellDescription = 'Detalhes do livro, orientações de mentoria, resenhas e solicitações de empréstimo.';
$appShellActiveHref = '/biblioteca';
$appShellActions = [
    ['label' => 'Voltar', 'href' => $voltarHref],
    ['label' => 'Painel', 'href' => '/dashboard'],
];

require __DIR__ . '/../partials/erp_shell_open.php';
?>

<!-- Botão Voltar -->
<div class="mb-6">
    <a href="<?= htmlspecialchars($voltarHref) ?>" class="btn border border-white/10 text-slate-300 hover:bg-white/5 py-2 px-4 text-xs font-semibold inline-flex items-center gap-1.5">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        Voltar ao Catálogo
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Coluna da Capa e Ações Rápidas (1/3) -->
    <div class="lg:col-span-1 space-y-6">
        <!-- Visualização da Capa -->
        <div class="card depth-1 p-6 text-center">
            <div class="aspect-[3/4] w-full rounded-2xl overflow-hidden bg-black/35 border border-white/5 relative shadow-xl">
                <?php if (!empty($item['capa_url'])): ?>
                    <img src="<?= htmlspecialchars((string) $item['capa_url']) ?>" alt="Capa do livro" class="h-full w-full object-cover">
                <?php else: ?>
                    <div class="h-full w-full flex flex-col items-center justify-center p-6 bg-gradient-to-br from-slate-900 to-black text-center">
                        <svg class="w-12 h-12 text-slate-700 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                        <span class="text-xs text-slate-500 font-bold uppercase tracking-wider">Capa Indisponível</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Botões de Ação de Empréstimo e Reação -->
        <div class="card depth-1 p-6 space-y-4">
            <div class="card-header border-b border-white/5 pb-3">
                <h2 class="card-title text-white">Interações</h2>
            </div>
            <div class="card-body space-y-4 pt-1">
                <?php if ($podeSolicitar): ?>
                    <form action="/biblioteca/solicitar" method="POST">
                        <input type="hidden" name="acervo_id" value="<?= (int) ($item['id'] ?? 0) ?>">
                        <?php if ($lojaIdDetalhe > 0): ?>
                            <input type="hidden" name="loja_id" value="<?= (int) $lojaIdDetalhe ?>">
                        <?php endif; ?>
                        <button type="submit" class="btn btn-primary w-full py-3 font-bold text-xs uppercase tracking-wider flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                            Solicitar Empréstimo
                        </button>
                    </form>
                <?php endif; ?>
                
                <div class="grid grid-cols-2 gap-3 pt-2">
                    <form action="/biblioteca/reagir" method="POST">
                        <input type="hidden" name="acervo_id" value="<?= (int) ($item['id'] ?? 0) ?>">
                        <input type="hidden" name="gostei" value="sim">
                        <button type="submit" class="btn border border-emerald-500/20 bg-emerald-500/5 text-emerald-400 hover:bg-emerald-500/10 py-2.5 w-full text-center text-xs font-bold transition flex items-center justify-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"></path></svg>
                            Recomendo (<?= (int) ($item['total_gostei_sim'] ?? 0) ?>)
                        </button>
                    </form>
                    <form action="/biblioteca/reagir" method="POST">
                        <input type="hidden" name="acervo_id" value="<?= (int) ($item['id'] ?? 0) ?>">
                        <input type="hidden" name="gostei" value="nao">
                        <button type="submit" class="btn border border-red-500/20 bg-red-500/5 text-red-400 hover:bg-red-500/10 py-2.5 w-full text-center text-xs font-bold transition flex items-center justify-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14H5.236a2 2 0 01-1.789-2.894l3.5-7A2 2 0 018.736 3h4.018c.163 0 .326.02.485.06L17 4m-7 10v5a2 2 0 002 2h.095c.5 0 .905-.405.905-.905 0-.714.211-1.412.608-2.006L17 13V4m-7 10h2m7-10h2a2 2 0 012 2v6a2 2 0 01-2 2h-2.5"></path></svg>
                            Evitar (<?= (int) ($item['total_gostei_nao'] ?? 0) ?>)
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Coluna de Metadados, Nota de Mentoria e Resumo (2/3) -->
    <div class="lg:col-span-2 space-y-6">
        
        <!-- Ficha Editorial -->
        <div class="card depth-1 p-6">
            <div class="card-header border-b border-white/5 pb-4 mb-6 flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-white leading-tight"><?= htmlspecialchars((string) ($item['titulo'] ?? '')) ?></h1>
                    <p class="text-slate-400 mt-1 text-base"><?= htmlspecialchars((string) ($item['autor'] ?? 'Autor não especificado')) ?></p>
                </div>
                <span class="badge-status uppercase text-xs <?= (int) ($item['quantidade_disponivel'] ?? 0) > 0 ? 'badge-status-success' : 'badge-status-danger' ?> shrink-0">
                    <?= (int) ($item['quantidade_disponivel'] ?? 0) > 0 ? 'Disponível (' . (int) $item['quantidade_disponivel'] . ')' : 'Indisponível' ?>
                </span>
            </div>

            <div class="card-body space-y-6">
                <!-- Metadados -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6 bg-white/[0.01] border border-white/5 rounded-xl p-5 text-xs text-slate-300">
                    <div>
                        <p class="text-slate-500 uppercase tracking-widest font-bold text-[9px]">Código Acervo</p>
                        <p class="font-mono text-white text-sm mt-1.5"><?= htmlspecialchars((string) ($item['codigo_acervo'] ?? '-')) ?></p>
                    </div>
                    <div>
                        <p class="text-slate-500 uppercase tracking-widest font-bold text-[9px]">ISBN</p>
                        <p class="text-white text-sm mt-1.5"><?= htmlspecialchars((string) ($item['isbn'] ?? '-')) ?></p>
                    </div>
                    <div>
                        <p class="text-slate-500 uppercase tracking-widest font-bold text-[9px]">Categoria / Local</p>
                        <p class="text-white text-sm mt-1.5"><?= htmlspecialchars((string) ($item['localizacao'] ?? 'Estante Geral')) ?></p>
                    </div>
                    <div>
                        <p class="text-slate-500 uppercase tracking-widest font-bold text-[9px]">Grau Recomendado</p>
                        <p class="text-white text-sm mt-1.5">
                            <span class="inline-flex items-center rounded bg-erp-gold/10 px-2.5 py-0.5 text-xs font-semibold text-erp-gold border border-erp-gold/20">
                                <?= $formatGrau((string) ($item['grau_recomendado'] ?? '')) ?>
                            </span>
                        </p>
                    </div>
                </div>

                <!-- Nota de Mentoria / Instrução -->
                <?php if (!empty($item['nota_instrucao'])): ?>
                    <div class="bg-purple-950/15 border border-purple-500/20 rounded-xl p-5 text-xs text-purple-200">
                        <p class="font-bold text-erp-gold uppercase tracking-wider text-[9px] mb-2">Nota de Formação / Orientação dos Vigilantes</p>
                        <p class="leading-relaxed text-slate-300">"<?= htmlspecialchars((string) $item['nota_instrucao']) ?>"</p>
                    </div>
                <?php endif; ?>

                <!-- Resumo / Sinopse -->
                <div class="space-y-2">
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider">Resumo Editorial</h3>
                    <p class="text-slate-300 text-sm leading-relaxed whitespace-pre-wrap bg-black/10 border border-white/5 rounded-xl p-4">
                        <?= htmlspecialchars((string) ($item['resumo'] ?? 'Nenhum resumo cadastrado para este título.')) ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Seção de Comentários / Resenhas -->
        <div class="card depth-1 p-6">
            <div class="card-header border-b border-white/5 pb-3 mb-6">
                <h2 class="card-title text-white">Resenhas & Discussões (<?= (int) ($item['total_comentarios'] ?? 0) ?>)</h2>
                <p class="card-subtitle mt-0.5">Espaço de partilha e debate sobre a obra.</p>
            </div>
            
            <div class="card-body space-y-6">
                <!-- Escrever Comentário -->
                <form action="/biblioteca/comentar" method="POST" class="space-y-3">
                    <input type="hidden" name="acervo_id" value="<?= (int) ($item['id'] ?? 0) ?>">
                    <textarea id="comentario" name="comentario" rows="3" required class="form-textarea w-full" placeholder="Compartilhe suas percepções sobre a obra, lições rituais ou impressões gerais de leitura..."></textarea>
                    <div class="flex justify-end">
                        <button type="submit" class="btn btn-primary font-bold text-xs py-2 px-6">
                            Publicar Resenha
                        </button>
                    </div>
                </form>

                <!-- Listagem de Comentários -->
                <div class="space-y-4 pt-4 border-t border-white/5">
                    <?php if (empty($comentarios)): ?>
                        <p class="text-center text-slate-400 py-6 text-xs">Seja o primeiro a compartilhar sua opinião sobre a leitura deste livro.</p>
                    <?php else: ?>
                        <?php foreach ($comentarios as $com): ?>
                            <div class="bg-white/[0.01] border border-white/5 rounded-xl p-4 space-y-3 text-xs">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-blue-950 border border-blue-500/20 flex items-center justify-center font-bold text-xs text-erp-gold">
                                        <?= strtoupper(substr((string) ($com['obreiro_nome'] ?? 'I'), 0, 1)) ?>
                                    </div>
                                    <div>
                                        <p class="font-bold text-white text-xs"><?= htmlspecialchars((string) ($com['obreiro_nome'] ?? 'Irmão')) ?></p>
                                        <p class="text-[10px] text-slate-500 mt-0.5"><?= $formatDate((string) ($com['criado_em'] ?? '')) ?></p>
                                    </div>
                                </div>
                                <p class="text-slate-300 text-sm leading-relaxed whitespace-pre-wrap pl-1">
                                    <?= htmlspecialchars((string) ($com['comentario'] ?? '')) ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require __DIR__ . '/../partials/erp_shell_close.php'; ?>
