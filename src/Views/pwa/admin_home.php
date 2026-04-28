<?php
if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}

$links = is_array($links ?? null) ? $links : [];
$usuarioNome = (string) ($_SESSION['usuario_nome'] ?? 'Irmão');
$usuarioCargo = (string) ($_SESSION['usuario_cargo'] ?? '');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin (PWA)</title>
    <link rel="manifest" href="/manifest.php">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen text-slate-800">
    <header class="bg-blue-900 text-white shadow-sm">
        <div class="mx-auto flex max-w-3xl items-center justify-between gap-3 px-4 py-4">
            <div>
                <div class="text-xs uppercase tracking-[0.18em] text-blue-100/80">Admin</div>
                <h1 class="text-lg font-semibold">Atalhos operacionais</h1>
                <div class="text-xs text-blue-100/80"><?= htmlspecialchars($usuarioNome) ?><?= $usuarioCargo !== '' ? ' · ' . htmlspecialchars($usuarioCargo) : '' ?></div>
            </div>
            <a class="rounded-md bg-white/10 px-3 py-2 text-sm font-medium hover:bg-white/15" href="/pwa">PWA</a>
        </div>
    </header>

    <main class="mx-auto max-w-3xl px-4 py-6 space-y-3">
        <?php foreach ($links as $item): ?>
            <?php
            $label = (string) ($item['label'] ?? 'Módulo');
            $href = (string) ($item['href'] ?? '/dashboard');
            $desc = (string) ($item['desc'] ?? '');
            ?>
            <a href="<?= htmlspecialchars($href) ?>" class="block rounded-xl border border-slate-200 bg-white p-4 hover:border-slate-300">
                <div class="text-base font-semibold"><?= htmlspecialchars($label) ?></div>
                <?php if ($desc !== ''): ?>
                    <div class="text-sm text-slate-600"><?= htmlspecialchars($desc) ?></div>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>

        <div class="text-xs text-slate-500">
            Estes atalhos reaproveitam módulos existentes (desktop-first) enquanto a migração PWA completa dos CRUDs avança por fase.
        </div>
    </main>

    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js').catch(() => {});
        }
    </script>
</body>
</html>

