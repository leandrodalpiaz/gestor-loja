<?php
$usuarioNome = $_SESSION['usuario_nome'] ?? 'Irmao';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus emprestimos</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen text-slate-800">
    <header class="bg-blue-900 text-white">
        <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
            <div>
                <div class="text-xs uppercase tracking-[0.18em] text-blue-100/80">Biblioteca</div>
                <h1 class="font-semibold">Meus emprestimos</h1>
            </div>
            <div class="text-sm"><?= htmlspecialchars($usuarioNome) ?></div>
        </div>
    </header>

    <main class="max-w-6xl mx-auto p-4 md:p-6">
        <div class="mb-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Leitura pessoal</div>
                    <h2 class="mt-1 text-xl font-semibold text-blue-900">Historico de emprestimos</h2>
                    <p class="mt-1 text-sm text-slate-500">Acompanhe seus livros, prazos e situacao de devolucao.</p>
                </div>
                <a href="/biblioteca" class="text-blue-700 hover:underline text-sm font-medium">Voltar ao catalogo</a>
            </div>
        </div>

        <div class="space-y-3 md:hidden">
            <?php if (!empty($emprestimos)): ?>
                <?php foreach ($emprestimos as $emp): ?>
                    <?php
                    $status = strtolower(trim((string) ($emp['status'] ?? '')));
                    $statusClasses = 'border-slate-200 bg-slate-50 text-slate-700';
                    if ($status === 'atrasado') {
                        $statusClasses = 'border-rose-200 bg-rose-50 text-rose-700';
                    } elseif ($status === 'pendente') {
                        $statusClasses = 'border-amber-200 bg-amber-50 text-amber-700';
                    } elseif ($status === 'devolvido') {
                        $statusClasses = 'border-emerald-200 bg-emerald-50 text-emerald-700';
                    }
                    ?>
                    <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="text-base font-semibold text-slate-900"><?= htmlspecialchars((string) ($emp['titulo'] ?? '-')) ?></div>
                                <div class="mt-1 text-xs font-mono text-slate-500"><?= htmlspecialchars((string) ($emp['codigo_acervo'] ?? '')) ?></div>
                            </div>
                            <span class="rounded-full border px-2.5 py-1 text-xs font-medium <?= $statusClasses ?>">
                                <?= htmlspecialchars(ucfirst((string) ($emp['status'] ?? '-'))) ?>
                            </span>
                        </div>

                        <div class="mt-3 space-y-2 text-sm text-slate-600">
                            <div><span class="font-medium text-slate-700">Emprestimo:</span> <?= htmlspecialchars((string) ($emp['data_emprestimo'] ?? '-')) ?></div>
                            <div><span class="font-medium text-slate-700">Devolucao prevista:</span> <?= htmlspecialchars((string) ($emp['data_devolucao_prevista'] ?? '-')) ?></div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-8 text-center text-slate-500 shadow-sm">Nenhum emprestimo registrado.</div>
            <?php endif; ?>
        </div>

        <div class="hidden bg-white rounded border border-slate-200 overflow-x-auto md:block">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-100">
                    <tr>
                        <th class="px-4 py-3 text-left">Codigo</th>
                        <th class="px-4 py-3 text-left">Livro</th>
                        <th class="px-4 py-3 text-left">Emprestimo</th>
                        <th class="px-4 py-3 text-left">Devolucao prevista</th>
                        <th class="px-4 py-3 text-left">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (!empty($emprestimos)): ?>
                        <?php foreach ($emprestimos as $emp): ?>
                            <tr>
                                <td class="px-4 py-3 font-mono text-xs"><?= htmlspecialchars((string) ($emp['codigo_acervo'] ?? '')) ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars((string) ($emp['titulo'] ?? '-')) ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars((string) ($emp['data_emprestimo'] ?? '-')) ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars((string) ($emp['data_devolucao_prevista'] ?? '-')) ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars(ucfirst((string) ($emp['status'] ?? '-'))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-slate-500">Nenhum emprestimo registrado.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
