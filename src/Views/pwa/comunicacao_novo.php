<?php
if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}

$payload = is_array($payload ?? null) ? $payload : ['titulo' => '', 'categoria' => 'geral', 'conteudo' => ''];
$erroDb = $erroDb ?? null;
$mensagemSucesso = $mensagemSucesso ?? null;
$mensagemErro = $mensagemErro ?? null;

$usuarioNome = (string) ($_SESSION['usuario_nome'] ?? 'Irmão');
$usuarioCargo = (string) ($_SESSION['usuario_cargo'] ?? '');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo comunicado</title>
    <link rel="manifest" href="/manifest.php">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen text-slate-800">
    <header class="bg-blue-900 text-white shadow-sm">
        <div class="mx-auto flex max-w-3xl items-center justify-between gap-3 px-4 py-4">
            <div>
                <div class="text-xs uppercase tracking-[0.18em] text-blue-100/80">Comunicação</div>
                <h1 class="text-lg font-semibold">Novo comunicado</h1>
                <div class="text-xs text-blue-100/80"><?= htmlspecialchars($usuarioNome) ?><?= $usuarioCargo !== '' ? ' · ' . htmlspecialchars($usuarioCargo) : '' ?></div>
            </div>
            <a class="rounded-md bg-white/10 px-3 py-2 text-sm font-medium hover:bg-white/15" href="/pwa/comunicacao">Voltar</a>
        </div>
    </header>

    <main class="mx-auto max-w-3xl px-4 py-6 space-y-4">
        <?php if ($erroDb): ?>
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-amber-900">
                <?= htmlspecialchars((string) $erroDb) ?>
                <div class="mt-1 text-xs text-amber-800">Aplique `database/phase2_comunicacao.sql` no schema do ambiente e tente novamente.</div>
            </div>
        <?php endif; ?>

        <?php if ($mensagemSucesso): ?>
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800">
                <?= htmlspecialchars((string) $mensagemSucesso) ?>
            </div>
        <?php endif; ?>
        <?php if ($mensagemErro): ?>
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-rose-800">
                <?= htmlspecialchars((string) $mensagemErro) ?>
            </div>
        <?php endif; ?>

        <form method="post" action="/pwa/comunicacao/novo" class="rounded-xl border border-slate-200 bg-white p-4 space-y-3">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">Título</label>
                <input name="titulo" value="<?= htmlspecialchars((string) ($payload['titulo'] ?? '')) ?>"
                       class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-600 focus:outline-none"
                       placeholder="Ex.: Sessão magna — orientações">
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">Categoria</label>
                <input name="categoria" value="<?= htmlspecialchars((string) ($payload['categoria'] ?? 'geral')) ?>"
                       class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-600 focus:outline-none"
                       placeholder="geral, sessão, ágape...">
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">Conteúdo</label>
                <textarea name="conteudo" rows="7"
                          class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-600 focus:outline-none"
                          placeholder="Texto do comunicado..."><?= htmlspecialchars((string) ($payload['conteudo'] ?? '')) ?></textarea>
            </div>

            <button class="w-full rounded-lg bg-blue-600 px-4 py-3 text-sm font-semibold text-white hover:bg-blue-700">
                Publicar comunicado
            </button>
        </form>
    </main>

    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js').catch(() => {});
        }
    </script>
</body>
</html>

