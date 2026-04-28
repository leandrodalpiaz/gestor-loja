<?php
if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}

$lista = is_array($itens ?? null) ? $itens : [];
$catalogScope = (string) ($catalogScope ?? 'minha');
$redeHabilitada = (bool) ($bibliotecaRedeHabilitada ?? false);
$q = trim((string) ($_GET['q'] ?? ''));

$usuarioNome = (string) ($_SESSION['usuario_nome'] ?? 'Irmão');
$usuarioCargo = (string) ($_SESSION['usuario_cargo'] ?? '');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biblioteca</title>
    <link rel="manifest" href="/manifest.php">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen text-slate-800">
    <header class="bg-blue-900 text-white shadow-sm">
        <div class="mx-auto flex max-w-3xl items-center justify-between gap-3 px-4 py-4">
            <div>
                <div class="text-xs uppercase tracking-[0.18em] text-blue-100/80">Biblioteca</div>
                <h1 class="text-lg font-semibold">Acervo</h1>
                <div class="text-xs text-blue-100/80"><?= htmlspecialchars($usuarioNome) ?><?= $usuarioCargo !== '' ? ' · ' . htmlspecialchars($usuarioCargo) : '' ?></div>
            </div>
            <a class="rounded-md bg-white/10 px-3 py-2 text-sm font-medium hover:bg-white/15" href="/dashboard">Painel</a>
        </div>
    </header>

    <main class="mx-auto max-w-3xl px-4 py-6 space-y-4">
        <form method="get" action="/pwa/biblioteca" class="space-y-3">
            <div class="flex gap-2">
                <input type="text" name="q" value="<?= htmlspecialchars($q) ?>"
                       class="w-full rounded-lg border border-slate-300 bg-white px-3 py-3 text-sm focus:border-blue-600 focus:outline-none"
                       placeholder="Buscar por título, autor ou tipo...">
                <button class="shrink-0 rounded-lg bg-slate-900 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800">
                    Buscar
                </button>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="/pwa/biblioteca?scope=minha<?= $q !== '' ? '&q=' . urlencode($q) : '' ?>"
                   class="rounded-full px-3 py-1 text-xs font-semibold <?= $catalogScope === 'minha' ? 'bg-blue-600 text-white' : 'bg-white text-slate-700 border border-slate-200' ?>">
                    Minha loja
                </a>
                <?php if ($redeHabilitada): ?>
                    <a href="/pwa/biblioteca?scope=rede<?= $q !== '' ? '&q=' . urlencode($q) : '' ?>"
                       class="rounded-full px-3 py-1 text-xs font-semibold <?= $catalogScope === 'rede' ? 'bg-blue-600 text-white' : 'bg-white text-slate-700 border border-slate-200' ?>">
                        Rede
                    </a>
                <?php endif; ?>
            </div>
        </form>

        <?php if ($lista === []): ?>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="text-sm text-slate-600">Nenhum item encontrado.</div>
            </div>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($lista as $item): ?>
                    <?php
                    $titulo = (string) ($item['titulo'] ?? 'Livro');
                    $autor = (string) ($item['autor'] ?? '');
                    $tipo = (string) ($item['tipo'] ?? '');
                    $lojaNome = (string) ($item['loja_nome'] ?? '');
                    $disponivel = (bool) ($item['disponivel'] ?? false);
                    $totalGostei = (int) ($item['total_gostei_sim'] ?? 0);
                    $totalNaoGostei = (int) ($item['total_gostei_nao'] ?? 0);
                    $totalComentarios = (int) ($item['total_comentarios'] ?? 0);
                    ?>
                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="text-base font-semibold truncate"><?= htmlspecialchars($titulo) ?></div>
                                <div class="text-sm text-slate-600 truncate">
                                    <?= $autor !== '' ? htmlspecialchars($autor) : 'Autor não informado' ?>
                                    <?= $tipo !== '' ? ' · ' . htmlspecialchars($tipo) : '' ?>
                                </div>
                                <?php if ($lojaNome !== ''): ?>
                                    <div class="mt-1 text-xs text-slate-500 truncate"><?= htmlspecialchars($lojaNome) ?></div>
                                <?php endif; ?>
                            </div>
                            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold text-white <?= $disponivel ? 'bg-emerald-600' : 'bg-slate-500' ?>">
                                <?= $disponivel ? 'Disponível' : 'Indisponível' ?>
                            </span>
                        </div>

                        <div class="mt-3 flex flex-wrap items-center gap-2 text-xs text-slate-600">
                            <span class="rounded-full bg-slate-100 px-3 py-1">👍 <?= $totalGostei ?></span>
                            <span class="rounded-full bg-slate-100 px-3 py-1">👎 <?= $totalNaoGostei ?></span>
                            <span class="rounded-full bg-slate-100 px-3 py-1">💬 <?= $totalComentarios ?></span>
                        </div>

                        <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
                            <a href="/biblioteca/detalhes?id=<?= (int) ($item['id'] ?? 0) ?><?= $catalogScope === 'rede' ? '&scope=rede' : '' ?>"
                               class="w-full rounded-lg bg-blue-600 px-4 py-3 text-center text-sm font-semibold text-white hover:bg-blue-700">
                                Ver detalhes
                            </a>
                            <a href="/biblioteca"
                               class="w-full rounded-lg bg-slate-100 px-4 py-3 text-center text-sm font-semibold text-slate-800 hover:bg-slate-200">
                                Abrir módulo completo
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js').catch(() => {});
        }
    </script>
</body>
</html>
