<?php
$usuarioNome = $_SESSION['usuario_nome'] ?? 'Irmão';
$podeSolicitar = isset($_SESSION['usuario_id']) && (int) $_SESSION['usuario_id'] > 0;
$lojaIdDetalhe = (int) ($_GET['loja_id'] ?? 0);
$voltarHref = $lojaIdDetalhe > 0 ? '/biblioteca?acervo=rede' : '/biblioteca';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do livro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media (min-width: 1440px) {
            .erp-readable {
                font-size: 1.08rem;
            }
            .erp-readable .text-xs,
            .erp-readable .text-\[11px\] {
                font-size: 0.92rem !important;
                line-height: 1.4rem !important;
            }
            .erp-readable .text-sm {
                font-size: 1.03rem !important;
                line-height: 1.58rem !important;
            }
        }
    </style>
</head>
<body class="erp-readable bg-slate-50 min-h-screen text-slate-800">
    <header class="bg-blue-900 text-white shadow-sm">
        <div class="mx-auto flex max-w-5xl items-center justify-between gap-3 px-4 py-4">
            <div>
                <div class="text-xs uppercase tracking-[0.18em] text-blue-100/80">Biblioteca</div>
                <h1 class="text-lg font-semibold">Detalhes do livro</h1>
            </div>
            <div class="text-sm"><?= htmlspecialchars($usuarioNome) ?></div>
        </div>
    </header>

    <main class="max-w-5xl mx-auto p-4 md:p-6">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <a href="<?= htmlspecialchars($voltarHref) ?>" class="text-sm font-medium text-blue-700 hover:underline">Voltar ao catálogo</a>

            <div class="mt-4 grid grid-cols-1 gap-5 md:grid-cols-[180px_1fr]">
                <div>
                    <?php if (!empty($item['capa_url'])): ?>
                        <img src="<?= htmlspecialchars((string) $item['capa_url']) ?>" alt="Capa" class="w-40 rounded border border-slate-200">
                    <?php else: ?>
                        <div class="h-56 w-40 rounded border border-slate-200 bg-slate-100"></div>
                    <?php endif; ?>
                </div>
                <article class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="flex flex-wrap items-start gap-2">
                        <h2 class="min-w-0 flex-1 text-2xl font-semibold text-blue-900"><?= htmlspecialchars((string) ($item['titulo'] ?? '')) ?></h2>
                        <?php if ((int) ($item['quantidade_disponivel'] ?? 0) > 0): ?>
                            <span class="rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">Disponível</span>
                        <?php else: ?>
                            <span class="rounded-full border border-rose-200 bg-rose-50 px-2.5 py-1 text-xs font-medium text-rose-700">Indisponível</span>
                        <?php endif; ?>
                    </div>
                    <p class="mt-1 text-slate-700"><?= htmlspecialchars((string) ($item['autor'] ?? '')) ?></p>
                    <div class="mt-3 text-sm space-y-1">
                        <div><span class="font-medium">Código:</span> <span class="font-mono"><?= htmlspecialchars((string) ($item['codigo_acervo'] ?? '')) ?></span></div>
                        <div><span class="font-medium">ISBN:</span> <?= htmlspecialchars((string) ($item['isbn'] ?? '-')) ?></div>
                        <div><span class="font-medium">Exemplares livres:</span> <?= (int) ($item['quantidade_disponivel'] ?? 0) ?></div>
                        <div><span class="font-medium">Grau sugerido:</span> <?= htmlspecialchars((string) ($item['grau_recomendado'] ?? 'Livre')) ?></div>
                    </div>
                    <p class="mt-4 whitespace-pre-wrap text-sm text-slate-700"><?= htmlspecialchars((string) ($item['resumo'] ?? 'Resumo ainda não informado.')) ?></p>

                    <div class="mt-4">
                        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Ações</div>
                        <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                            <?php if ($podeSolicitar): ?>
                                <form action="/biblioteca/solicitar" method="POST">
                                    <input type="hidden" name="acervo_id" value="<?= (int) ($item['id'] ?? 0) ?>">
                                    <?php if ($lojaIdDetalhe > 0): ?>
                                        <input type="hidden" name="loja_id" value="<?= (int) $lojaIdDetalhe ?>">
                                    <?php endif; ?>
                                    <button type="submit" class="w-full rounded-lg bg-emerald-600 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-700 sm:w-auto">Solicitar emprestimo</button>
                                </form>
                            <?php endif; ?>

                            <form action="/biblioteca/reagir" method="POST">
                                <input type="hidden" name="acervo_id" value="<?= (int) ($item['id'] ?? 0) ?>">
                                <input type="hidden" name="gostei" value="sim">
                                <button type="submit" class="w-full rounded-lg border border-emerald-300 px-3 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-50 sm:w-auto">Gostei (<?= (int) ($item['total_gostei_sim'] ?? 0) ?>)</button>
                            </form>
                            <form action="/biblioteca/reagir" method="POST">
                                <input type="hidden" name="acervo_id" value="<?= (int) ($item['id'] ?? 0) ?>">
                                <input type="hidden" name="gostei" value="nao">
                                <button type="submit" class="w-full rounded-lg border border-rose-300 px-3 py-2 text-sm font-medium text-rose-700 hover:bg-rose-50 sm:w-auto">Não gostei (<?= (int) ($item['total_gostei_nao'] ?? 0) ?>)</button>
                            </form>
                        </div>
                    </div>
                </article>
            </div>
        </div>
        
        <section class="mt-5 rounded-xl border border-slate-200 bg-white p-4 shadow-sm md:p-6">
            <div class="border-b border-slate-200 pb-3">
                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Comentarios</div>
                <h3 class="mt-1 text-lg font-semibold">Comentarios (<?= (int) ($item['total_comentarios'] ?? 0) ?>)</h3>
            </div>
            <form action="/biblioteca/comentar" method="POST" class="mt-4">
                <input type="hidden" name="acervo_id" value="<?= (int) ($item['id'] ?? 0) ?>">
                <label class="block text-sm font-medium text-slate-700">Novo comentario</label>
                <textarea name="comentario" rows="3" required class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="Compartilhe sua opiniao sobre a leitura..."></textarea>
                <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:justify-end">
                    <button type="submit" class="w-full rounded-lg bg-blue-700 px-3 py-2 text-sm font-medium text-white hover:bg-blue-800 sm:w-auto">Publicar comentario</button>
                </div>
            </form>

            <div class="mt-4 space-y-3">
                <?php if (!empty($comentarios)): ?>
                    <?php foreach ($comentarios as $comentario): ?>
                        <article class="border border-slate-200 rounded p-3">
                            <div class="text-sm font-medium text-slate-900"><?= htmlspecialchars((string) ($comentario['obreiro_nome'] ?? 'Irmão')) ?></div>
                            <div class="text-xs text-slate-700 mt-1"><?= htmlspecialchars((string) ($comentario['criado_em'] ?? '')) ?></div>
                            <p class="text-sm text-slate-700 mt-2 whitespace-pre-wrap"><?= htmlspecialchars((string) ($comentario['comentario'] ?? '')) ?></p>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-sm text-slate-700">Ainda não há comentários para este livro.</p>
                <?php endif; ?>
            </div>
        </section>
    </main>
</body>
</html>

