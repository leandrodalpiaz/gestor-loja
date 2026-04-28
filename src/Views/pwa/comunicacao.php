<?php
if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}

$lista = is_array($comunicados ?? null) ? $comunicados : [];
$erroDb = $erroDb ?? null;

$usuarioNome = (string) ($_SESSION['usuario_nome'] ?? 'Irmão');
$usuarioCargo = (string) ($_SESSION['usuario_cargo'] ?? '');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comunicação Oficial</title>
    <link rel="manifest" href="/manifest.php">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen text-slate-800">
    <header class="bg-blue-900 text-white shadow-sm">
        <div class="mx-auto flex max-w-3xl items-center justify-between gap-3 px-4 py-4">
            <div>
                <div class="text-xs uppercase tracking-[0.18em] text-blue-100/80">Comunicação</div>
                <h1 class="text-lg font-semibold">Central oficial</h1>
                <div class="text-xs text-blue-100/80"><?= htmlspecialchars($usuarioNome) ?><?= $usuarioCargo !== '' ? ' · ' . htmlspecialchars($usuarioCargo) : '' ?></div>
            </div>
            <a class="rounded-md bg-white/10 px-3 py-2 text-sm font-medium hover:bg-white/15" href="/dashboard">Painel</a>
        </div>
    </header>

    <main class="mx-auto max-w-3xl px-4 py-6 space-y-4">
        <?php if (!empty($_SESSION['usuario_cargo']) && in_array((string) $_SESSION['usuario_cargo'], ['secretario', 'admin', 'veneravel'], true)): ?>
            <a href="/pwa/comunicacao/novo" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-3 text-sm font-semibold text-white hover:bg-blue-700">
                Novo comunicado
            </a>
        <?php endif; ?>

        <?php if ($erroDb): ?>
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-amber-900">
                <?= htmlspecialchars((string) $erroDb) ?>
                <div class="mt-1 text-xs text-amber-800">Aplique `database/phase2_comunicacao.sql` no schema do ambiente e tente novamente.</div>
            </div>
        <?php endif; ?>

        <?php if ($lista === []): ?>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="text-sm text-slate-600">Nenhum comunicado publicado.</div>
            </div>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($lista as $item): ?>
                    <?php
                    $id = (int) ($item['id'] ?? 0);
                    $titulo = (string) ($item['titulo'] ?? 'Comunicado');
                    $categoria = (string) ($item['categoria'] ?? 'geral');
                    $publicadoEm = (string) ($item['publicado_em'] ?? '');
                    $leituras = (int) ($item['total_leituras'] ?? 0);
                    ?>
                    <a href="/pwa/comunicacao/ler?id=<?= $id ?>" class="block rounded-xl border border-slate-200 bg-white p-4 hover:border-slate-300">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="text-base font-semibold truncate"><?= htmlspecialchars($titulo) ?></div>
                                <div class="text-xs text-slate-500 truncate">
                                    <?= htmlspecialchars($categoria) ?><?= $publicadoEm !== '' ? ' · ' . htmlspecialchars($publicadoEm) : '' ?>
                                </div>
                            </div>
                            <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                                Leituras: <?= $leituras ?>
                            </span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="text-xs text-slate-500">
            Observação: isto não é chat. É um canal de avisos estruturados e rastreáveis.
        </div>
    </main>

    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js').catch(() => {});
        }
    </script>
</body>
</html>
