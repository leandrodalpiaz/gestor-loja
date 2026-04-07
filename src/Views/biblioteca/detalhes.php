<?php
$usuarioNome = $_SESSION['usuario_nome'] ?? 'Irmao';
$podeSolicitar = isset($_SESSION['usuario_id']) && (int) $_SESSION['usuario_id'] > 0;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do livro</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen text-slate-800">
    <header class="bg-blue-900 text-white">
        <div class="max-w-5xl mx-auto px-4 py-4 flex items-center justify-between">
            <h1 class="font-semibold">Biblioteca</h1>
            <div class="text-sm"><?= htmlspecialchars($usuarioNome) ?></div>
        </div>
    </header>

    <main class="max-w-5xl mx-auto p-4 md:p-6">
        <a href="/biblioteca" class="text-sm text-blue-700 hover:underline">Voltar ao catalogo</a>

        <div class="mt-3 bg-white border border-slate-200 rounded-lg p-4 md:p-6 grid grid-cols-1 md:grid-cols-[180px_1fr] gap-5">
            <div>
                <?php if (!empty($item['capa_url'])): ?>
                    <img src="<?= htmlspecialchars((string) $item['capa_url']) ?>" alt="Capa" class="w-40 rounded border border-slate-200">
                <?php else: ?>
                    <div class="w-40 h-56 rounded border border-slate-200 bg-slate-100"></div>
                <?php endif; ?>
            </div>
            <div>
                <h2 class="text-2xl font-semibold text-blue-900"><?= htmlspecialchars((string) ($item['titulo'] ?? '')) ?></h2>
                <p class="text-slate-600 mt-1"><?= htmlspecialchars((string) ($item['autor'] ?? '')) ?></p>
                <div class="mt-3 text-sm space-y-1">
                    <div><span class="font-medium">Codigo:</span> <span class="font-mono"><?= htmlspecialchars((string) ($item['codigo_acervo'] ?? '')) ?></span></div>
                    <div><span class="font-medium">ISBN:</span> <?= htmlspecialchars((string) ($item['isbn'] ?? '-')) ?></div>
                    <div><span class="font-medium">Disponivel:</span> <?= (int) ($item['quantidade_disponivel'] ?? 0) ?></div>
                    <div><span class="font-medium">Grau sugerido:</span> <?= htmlspecialchars((string) ($item['grau_recomendado'] ?? 'Livre')) ?></div>
                </div>
                <p class="mt-4 text-sm text-slate-700 whitespace-pre-wrap"><?= htmlspecialchars((string) ($item['resumo'] ?? 'Resumo ainda nao informado.')) ?></p>

                <div class="mt-4 flex flex-wrap gap-2">
                    <?php if ($podeSolicitar): ?>
                        <form action="/biblioteca/solicitar" method="POST">
                            <input type="hidden" name="acervo_id" value="<?= (int) ($item['id'] ?? 0) ?>">
                            <button type="submit" class="px-3 py-2 rounded bg-emerald-600 hover:bg-emerald-700 text-white text-sm">Solicitar emprestimo</button>
                        </form>
                    <?php endif; ?>

                    <form action="/biblioteca/reagir" method="POST">
                        <input type="hidden" name="acervo_id" value="<?= (int) ($item['id'] ?? 0) ?>">
                        <input type="hidden" name="gostei" value="sim">
                        <button type="submit" class="px-3 py-2 rounded border border-emerald-300 text-emerald-700 text-sm">Gostei (<?= (int) ($item['total_gostei_sim'] ?? 0) ?>)</button>
                    </form>
                    <form action="/biblioteca/reagir" method="POST">
                        <input type="hidden" name="acervo_id" value="<?= (int) ($item['id'] ?? 0) ?>">
                        <input type="hidden" name="gostei" value="nao">
                        <button type="submit" class="px-3 py-2 rounded border border-rose-300 text-rose-700 text-sm">Nao gostei (<?= (int) ($item['total_gostei_nao'] ?? 0) ?>)</button>
                    </form>
                </div>
            </div>
        </div>

        <section class="mt-5 bg-white border border-slate-200 rounded-lg p-4 md:p-6">
            <h3 class="font-semibold text-lg">Comentarios (<?= (int) ($item['total_comentarios'] ?? 0) ?>)</h3>
            <form action="/biblioteca/comentar" method="POST" class="mt-3">
                <input type="hidden" name="acervo_id" value="<?= (int) ($item['id'] ?? 0) ?>">
                <textarea name="comentario" rows="3" required class="w-full border border-slate-300 rounded px-3 py-2" placeholder="Compartilhe sua opiniao sobre a leitura..."></textarea>
                <div class="mt-2 text-right">
                    <button type="submit" class="px-3 py-2 rounded bg-blue-700 text-white text-sm">Publicar comentario</button>
                </div>
            </form>

            <div class="mt-4 space-y-3">
                <?php if (!empty($comentarios)): ?>
                    <?php foreach ($comentarios as $comentario): ?>
                        <article class="border border-slate-200 rounded p-3">
                            <div class="text-sm font-medium text-slate-900"><?= htmlspecialchars((string) ($comentario['obreiro_nome'] ?? 'Irmao')) ?></div>
                            <div class="text-xs text-slate-500 mt-1"><?= htmlspecialchars((string) ($comentario['criado_em'] ?? '')) ?></div>
                            <p class="text-sm text-slate-700 mt-2 whitespace-pre-wrap"><?= htmlspecialchars((string) ($comentario['comentario'] ?? '')) ?></p>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-sm text-slate-500">Ainda nao ha comentarios para este livro.</p>
                <?php endif; ?>
            </div>
        </section>
    </main>
</body>
</html>
