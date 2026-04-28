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
    <title>PWA</title>
    <link rel="manifest" href="/manifest.php">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen text-slate-800">
    <header class="bg-blue-900 text-white shadow-sm">
        <div class="mx-auto flex max-w-3xl items-center justify-between gap-3 px-4 py-4">
            <div>
                <div class="text-xs uppercase tracking-[0.18em] text-blue-100/80">Gestor Loja</div>
                <h1 class="text-lg font-semibold">Acesso rápido (PWA)</h1>
                <div class="text-xs text-blue-100/80"><?= htmlspecialchars($usuarioNome) ?><?= $usuarioCargo !== '' ? ' · ' . htmlspecialchars($usuarioCargo) : '' ?></div>
            </div>
            <a class="rounded-md bg-white/10 px-3 py-2 text-sm font-medium hover:bg-white/15" href="/dashboard">Painel</a>
        </div>
    </header>

    <main class="mx-auto max-w-3xl px-4 py-6 space-y-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <div class="text-sm text-slate-700">
                Instale este app no celular para abrir em tela cheia e com navegação mais rápida.
            </div>
            <div class="mt-2 text-xs text-slate-500">
                iOS: Compartilhar → “Adicionar à Tela de Início”. Android/Chrome: menu → “Instalar app”.
            </div>
        </div>

        <div class="grid gap-3">
            <?php if (!empty($links['sessoes'])): ?>
                <a href="/pwa/sessoes" class="block rounded-xl border border-slate-200 bg-white p-4 hover:border-slate-300">
                    <div class="text-base font-semibold">Sessões</div>
                    <div class="text-sm text-slate-600">Confirmar presença, ágape e justificar ausência.</div>
                </a>
            <?php endif; ?>

            <?php if (!empty($links['biblioteca'])): ?>
                <a href="/pwa/biblioteca" class="block rounded-xl border border-slate-200 bg-white p-4 hover:border-slate-300">
                    <div class="text-base font-semibold">Biblioteca</div>
                    <div class="text-sm text-slate-600">Consulta rápida do acervo (cards).</div>
                </a>
            <?php endif; ?>

            <?php if (!empty($links['comunicacao'])): ?>
                <a href="/pwa/comunicacao" class="block rounded-xl border border-slate-200 bg-white p-4 hover:border-slate-300">
                    <div class="text-base font-semibold">Comunicação oficial</div>
                    <div class="text-sm text-slate-600">Avisos estruturados, com confirmação de leitura.</div>
                </a>
            <?php endif; ?>

            <?php if (array_filter($links) === []): ?>
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-900">
                    Nenhum módulo PWA está habilitado neste ambiente. Ative as `FEATURE_PWA_*` no `.env`.
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js').catch(() => {});
        }
    </script>
</body>
</html>

