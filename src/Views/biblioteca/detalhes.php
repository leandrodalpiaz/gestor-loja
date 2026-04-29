<?php
declare(strict_types=1);

// #############################################################################
// LÓGICA DE NEGÓCIO E HELPERS
// #############################################################################

$podeSolicitar = $auth->isLoggedIn();
$lojaIdDetalhe = (int) ($_GET['loja_id'] ?? 0);
$voltarHref = $lojaIdDetalhe > 0 ? '/biblioteca?acervo=rede' : '/biblioteca';

$formatDate = static fn($dateStr) => !empty($dateStr) ? (new DateTime($dateStr))->format('d/m/Y H:i') : '';
$formatGrau = static fn($grau) => $grau ? ucfirst(strtolower($grau)) : 'Livre';

// #############################################################################
// CONFIGURAÇÃO DO APP SHELL
// #############################################################################

$appShellEyebrow = 'Biblioteca';
$appShellTitle = 'Detalhes do Título';
$appShellDescription = 'Veja informações completas, solicite empréstimos e participe das discussões.';
$appShellActiveHref = '/biblioteca';

require __DIR__ . '/../partials/erp_shell_open.php';
?>

<div class="mb-4">
    <a href="<?= htmlspecialchars($voltarHref) ?>" class="btn btn-secondary">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
        Voltar ao Catálogo
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Coluna da Capa e Ações -->
    <div class="lg:col-span-1 space-y-6">
        <div class="card">
            <div class="card-body text-center">
                <?php if (!empty($item['capa_url'])): ?>
                    <img src="<?= htmlspecialchars((string) $item['capa_url']) ?>" alt="Capa do livro" class="w-48 mx-auto rounded-md shadow-lg border-4 border-white dark:border-gray-700">
                <?php else: ?>
                    <div class="w-48 h-64 mx-auto rounded-md bg-gray-100 dark:bg-gray-700 flex items-center justify-center border-4 border-white dark:border-gray-700">
                        <span class="text-gray-400">Sem capa</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2 class="card-title">Ações</h2></div>
            <div class="card-body space-y-3">
                <?php if ($podeSolicitar): ?>
                    <form action="/biblioteca/solicitar" method="POST">
                        <input type="hidden" name="acervo_id" value="<?= (int) ($item['id'] ?? 0) ?>">
                        <?php if ($lojaIdDetalhe > 0): ?>
                            <input type="hidden" name="loja_id" value="<?= (int) $lojaIdDetalhe ?>">
                        <?php endif; ?>
                        <button type="submit" class="btn btn-primary w-full">Solicitar Empréstimo</button>
                    </form>
                <?php endif; ?>
                <div class="grid grid-cols-2 gap-3">
                    <form action="/biblioteca/reagir" method="POST">
                        <input type="hidden" name="acervo_id" value="<?= (int) ($item['id'] ?? 0) ?>">
                        <input type="hidden" name="gostei" value="sim">
                        <button type="submit" class="btn btn-secondary-green w-full">Gostei (<?= (int) ($item['total_gostei_sim'] ?? 0) ?>)</button>
                    </form>
                    <form action="/biblioteca/reagir" method="POST">
                        <input type="hidden" name="acervo_id" value="<?= (int) ($item['id'] ?? 0) ?>">
                        <input type="hidden" name="gostei" value="nao">
                        <button type="submit" class="btn btn-secondary-red w-full">Não Gostei (<?= (int) ($item['total_gostei_nao'] ?? 0) ?>)</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Coluna de Detalhes e Comentários -->
    <div class="lg:col-span-2 space-y-8">
        <div class="card">
            <div class="card-header">
                <div class="flex justify-between items-start">
                    <div>
                        <h1 class="card-title text-2xl"><?= htmlspecialchars((string) ($item['titulo'] ?? '')) ?></h1>
                        <p class="card-description text-lg"><?= htmlspecialchars((string) ($item['autor'] ?? '')) ?></p>
                    </div>
                    <?php if ((int) ($item['quantidade_disponivel'] ?? 0) > 0): ?>
                        <span class="badge-status success">Disponível</span>
                    <?php else: ?>
                        <span class="badge-status danger">Indisponível</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm mb-6">
                    <div class="list-item-param"><span>Código</span><strong class="font-mono"><?= htmlspecialchars((string) ($item['codigo_acervo'] ?? '')) ?></strong></div>
                    <div class="list-item-param"><span>ISBN</span><strong><?= htmlspecialchars((string) ($item['isbn'] ?? '-')) ?></strong></div>
                    <div class="list-item-param"><span>Exemplares</span><strong><?= (int) ($item['quantidade_disponivel'] ?? 0) ?></strong></div>
                    <div class="list-item-param col-span-2 md:col-span-1"><span>Grau Sugerido</span><strong class="badge-grau"><?= $formatGrau((string) ($item['grau_recomendado'] ?? '')) ?></strong></div>
                    <?php if (!empty($item['nota_instrucao'])): ?>
                        <div class="list-item-param col-span-2"><p><?= htmlspecialchars((string) $item['nota_instrucao']) ?></p></div>
                    <?php endif; ?>
                </div>
                <h3 class="font-semibold text-gray-800 dark:text-gray-200 mb-2">Resumo</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed whitespace-pre-wrap"><?= htmlspecialchars((string) ($item['resumo'] ?? 'Resumo ainda não informado.')) ?></p>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2 class="card-title">Comentários (<?= (int) ($item['total_comentarios'] ?? 0) ?>)</h2></div>
            <div class="card-body">
                <form action="/biblioteca/comentar" method="POST" class="mb-6">
                    <input type="hidden" name="acervo_id" value="<?= (int) ($item['id'] ?? 0) ?>">
                    <label for="comentario" class="form-label sr-only">Novo comentário</label>
                    <textarea id="comentario" name="comentario" rows="3" required class="form-textarea" placeholder="Compartilhe sua opinião sobre a leitura..."></textarea>
                    <div class="mt-3 flex justify-end">
                        <button type="submit" class="btn btn-primary">Publicar Comentário</button>
                    </div>
                </form>

                <div class="space-y-4">
                    <?php if (empty($comentarios)): ?>
                        <div class="card-placeholder-inner">Ainda não há comentários para este livro.</div>
                    <?php else: ?>
                        <?php foreach ($comentarios as $comentario): ?>
                            <div class="list-item-comment">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center font-bold text-sm text-gray-500 dark:text-gray-300">
                                        <?= strtoupper(substr((string) ($comentario['obreiro_nome'] ?? 'I'), 0, 1)) ?>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-sm"><?= htmlspecialchars((string) ($comentario['obreiro_nome'] ?? 'Irmão')) ?></p>
                                        <p class="text-xs text-gray-500"><?= $formatDate((string) ($comentario['criado_em'] ?? '')) ?></p>
                                    </div>
                                </div>
                                <p class="mt-3 text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap"><?= htmlspecialchars((string) ($comentario['comentario'] ?? '')) ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .card { @apply bg-white dark:bg-gray-800 rounded-lg shadow-md; }
    .card-header { @apply p-5 border-b border-gray-200 dark:border-gray-700; }
    .card-title { @apply text-lg font-bold text-gray-800 dark:text-gray-100; }
    .card-description { @apply mt-1 text-gray-600 dark:text-gray-400; }
    .card-body { @apply p-5; }
    .card-placeholder-inner { @apply text-center py-8 px-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg text-gray-500; }

    .btn { @apply inline-flex items-center justify-center px-4 py-2 rounded-md text-sm font-medium shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-100 dark:focus:ring-offset-gray-900; }
    .btn-primary { @apply bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500; }
    .btn-secondary { @apply bg-gray-200 text-gray-800 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 focus:ring-gray-500; }
    .btn-secondary-green { @apply bg-green-100 text-green-800 hover:bg-green-200 dark:bg-green-900/50 dark:text-green-300 dark:hover:bg-green-900 focus:ring-green-500; }
    .btn-secondary-red { @apply bg-red-100 text-red-800 hover:bg-red-200 dark:bg-red-900/50 dark:text-red-300 dark:hover:bg-red-900 focus:ring-red-500; }

    .badge-status { @apply inline-block px-2.5 py-1 text-xs font-medium rounded-full; }
    .badge-status.success { @apply bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300; }
    .badge-status.danger { @apply bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300; }
    .badge-grau { @apply inline-block px-2 py-0.5 text-xs font-medium rounded bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300; }

    .list-item-param { @apply flex justify-between items-center bg-gray-50 dark:bg-gray-700/50 p-3 rounded-md; }
    .list-item-comment { @apply p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg; }

    .form-label { @apply block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1; }
    .form-textarea { @apply w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500; }
</style>

<?php require __DIR__ . '/../partials/erp_shell_close.php'; ?>

