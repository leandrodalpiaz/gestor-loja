<?php
if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}

$emprestimos = is_array($emprestimos ?? null) ? $emprestimos : [];
$mensagemErro = $mensagemErro ?? null;

$usuarioNome = (string) ($_SESSION['usuario_nome'] ?? 'Irmão');
$usuarioCargo = (string) ($_SESSION['usuario_cargo'] ?? '');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus empréstimos</title>
    <link rel="manifest" href="/manifest.php">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen text-slate-800">
    <header class="bg-blue-900 text-white shadow-sm">
        <div class="mx-auto flex max-w-3xl items-center justify-between gap-3 px-4 py-4">
            <div>
                <div class="text-xs uppercase tracking-[0.18em] text-blue-100/80">Biblioteca</div>
                <h1 class="text-lg font-semibold">Meus empréstimos</h1>
                <div class="text-xs text-blue-100/80"><?= htmlspecialchars($usuarioNome) ?><?= $usuarioCargo !== '' ? ' · ' . htmlspecialchars($usuarioCargo) : '' ?></div>
            </div>
            <a class="rounded-md bg-white/10 px-3 py-2 text-sm font-medium hover:bg-white/15" href="/pwa/biblioteca">Voltar</a>
        </div>
    </header>

    <main class="mx-auto max-w-3xl px-4 py-6 space-y-4">
        <?php if ($mensagemErro): ?>
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-rose-800">
                <?= htmlspecialchars((string) $mensagemErro) ?>
            </div>
        <?php endif; ?>

        <?php if ($emprestimos === []): ?>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="text-sm text-slate-600">Nenhum empréstimo pendente.</div>
            </div>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($emprestimos as $item): ?>
                    <?php
                    $titulo = (string) ($item['titulo'] ?? 'Livro');
                    $codigo = (string) ($item['codigo_acervo'] ?? '');
                    $status = (string) ($item['status'] ?? 'pendente');
                    $dataEmprestimo = (string) ($item['data_emprestimo'] ?? '');
                    $prevista = (string) ($item['data_devolucao_prevista'] ?? '');

                    $badge = match ($status) {
                        'aprovado' => ['Aprovado', 'bg-emerald-600'],
                        'atrasado' => ['Atrasado', 'bg-rose-600'],
                        'pendente' => ['Pendente', 'bg-amber-600'],
                        default => [ucfirst($status), 'bg-slate-500'],
                    };
                    ?>
                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="text-base font-semibold truncate"><?= htmlspecialchars($titulo) ?></div>
                                <div class="text-xs text-slate-500 truncate">
                                    <?= $codigo !== '' ? htmlspecialchars($codigo) . ' · ' : '' ?>
                                    <?= $dataEmprestimo !== '' ? 'Empréstimo: ' . htmlspecialchars($dataEmprestimo) : '' ?>
                                    <?= $prevista !== '' ? ' · Prevista: ' . htmlspecialchars($prevista) : '' ?>
                                </div>
                            </div>
                            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold text-white <?= htmlspecialchars($badge[1]) ?>">
                                <?= htmlspecialchars($badge[0]) ?>
                            </span>
                        </div>
                        <div class="mt-3 text-xs text-slate-500">
                            Para devolução/ajustes, use o módulo completo quando solicitado pelo bibliotecário.
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

