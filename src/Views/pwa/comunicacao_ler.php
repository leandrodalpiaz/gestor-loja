<?php
if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}

$comunicado = is_array($comunicado ?? null) ? $comunicado : null;
$erroDb = $erroDb ?? null;

$usuarioNome = (string) ($_SESSION['usuario_nome'] ?? 'Irmão');
$usuarioCargo = (string) ($_SESSION['usuario_cargo'] ?? '');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comunicado</title>
    <link rel="manifest" href="/manifest.php">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen text-slate-800">
    <header class="bg-blue-900 text-white shadow-sm">
        <div class="mx-auto flex max-w-3xl items-center justify-between gap-3 px-4 py-4">
            <div>
                <div class="text-xs uppercase tracking-[0.18em] text-blue-100/80">Comunicação</div>
                <h1 class="text-lg font-semibold">Comunicado</h1>
                <div class="text-xs text-blue-100/80"><?= htmlspecialchars($usuarioNome) ?><?= $usuarioCargo !== '' ? ' · ' . htmlspecialchars($usuarioCargo) : '' ?></div>
            </div>
            <a class="rounded-md bg-white/10 px-3 py-2 text-sm font-medium hover:bg-white/15" href="/pwa/comunicacao">Voltar</a>
        </div>
    </header>

    <main class="mx-auto max-w-3xl px-4 py-6 space-y-4">
        <?php if ($erroDb): ?>
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-amber-900">
                <?= htmlspecialchars((string) $erroDb) ?>
            </div>
        <?php endif; ?>

        <?php if (!$comunicado): ?>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="text-sm text-slate-600">Comunicado não encontrado.</div>
            </div>
        <?php else: ?>
            <div class="rounded-xl border border-slate-200 bg-white p-4 space-y-2">
                <div class="text-base font-semibold"><?= htmlspecialchars((string) ($comunicado['titulo'] ?? 'Comunicado')) ?></div>
                <div class="text-xs text-slate-500">
                    <?= htmlspecialchars((string) ($comunicado['categoria'] ?? 'geral')) ?>
                    <?= !empty($comunicado['publicado_em']) ? ' · ' . htmlspecialchars((string) $comunicado['publicado_em']) : '' ?>
                </div>
                <div class="mt-2 rounded-lg bg-slate-50 p-3 text-sm text-slate-800 whitespace-pre-line">
                    <?= htmlspecialchars((string) ($comunicado['conteudo'] ?? '')) ?>
                </div>
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
