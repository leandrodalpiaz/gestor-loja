<?php
$usuarioNome = $_SESSION['usuario_nome'] ?? 'Irmao';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emprestimos - Biblioteca</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <header class="bg-blue-900 text-white shadow-sm">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-4 sm:px-6">
            <div>
                <div class="text-xs uppercase tracking-[0.18em] text-blue-100/80">Biblioteca</div>
                <h1 class="text-lg font-semibold">Controle de Emprestimos</h1>
            </div>
            <div class="text-sm">
                <?= htmlspecialchars($usuarioNome) ?> |
                <a class="underline" href="/logout">Sair</a>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-6xl p-4 sm:p-6">
        <div class="mb-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Emprestimos</div>
                    <h2 class="mt-1 text-xl font-semibold text-gray-800">Emprestimos pendentes/atrasados</h2>
                </div>
                <a href="/biblioteca" class="text-sm font-medium text-blue-700 hover:underline">Voltar ao catalogo</a>
            </div>
        </div>

        <div class="space-y-3 md:hidden">
            <?php if (!empty($emprestimos)): ?>
                <?php foreach ($emprestimos as $emp): ?>
                    <?php
                    $status = (string) ($emp['status'] ?? '-');
                    $statusLabel = ucfirst($status);
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
                                <div class="text-base font-semibold text-slate-900">
                                    <?= htmlspecialchars((string) ($emp['titulo'] ?? $emp['acervo_id'] ?? '-')) ?>
                                </div>
                                <div class="mt-1 text-sm text-slate-600">
                                    <?= htmlspecialchars((string) ($emp['obreiro_nome'] ?? $emp['obreiro_id'] ?? '-')) ?>
                                </div>
                            </div>
                            <span class="shrink-0 rounded-full border px-2.5 py-1 text-xs font-medium <?= $statusClasses ?>">
                                <?= htmlspecialchars($statusLabel) ?>
                            </span>
                        </div>

                        <div class="mt-3 space-y-2 text-sm text-slate-600">
                            <div><span class="font-medium text-slate-700">Data do emprestimo:</span> <?= htmlspecialchars((string) ($emp['data_emprestimo'] ?? '-')) ?></div>
                            <div><span class="font-medium text-slate-700">Data prevista:</span> <?= htmlspecialchars((string) ($emp['data_devolucao_prevista'] ?? '-')) ?></div>
                        </div>

                        <div class="mt-4">
                            <?php if ($status === 'pendente' || $status === 'atrasado'): ?>
                                <form action="/biblioteca/devolver" method="POST">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars((string) ($emp['id'] ?? '')) ?>">
                                    <button type="submit" class="w-full rounded-lg bg-green-600 px-3 py-2 text-sm font-medium text-white hover:bg-green-700">
                                        Registrar devolucao
                                    </button>
                                </form>
                            <?php else: ?>
                                <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-center text-sm text-slate-500">
                                    Nenhuma acao disponivel
                                </div>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-8 text-center text-slate-500 shadow-sm">
                    Nenhum emprestimo pendente ou atrasado.
                </div>
            <?php endif; ?>
        </div>

        <div class="hidden overflow-x-auto rounded shadow md:block">
        <div class="bg-white">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left">ID</th>
                        <th class="px-4 py-3 text-left">Codigo</th>
                        <th class="px-4 py-3 text-left">Livro</th>
                        <th class="px-4 py-3 text-left">Obreiro</th>
                        <th class="px-4 py-3 text-left">Emprestimo</th>
                        <th class="px-4 py-3 text-left">Prev. Devolucao</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-right">Acao</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (!empty($emprestimos)): ?>
                        <?php foreach ($emprestimos as $emp): ?>
                            <tr>
                                <td class="px-4 py-3"><?= htmlspecialchars((string) ($emp['id'] ?? '')) ?></td>
                                <td class="px-4 py-3 font-mono text-xs"><?= htmlspecialchars((string) ($emp['codigo_acervo'] ?? '')) ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars((string) ($emp['titulo'] ?? $emp['acervo_id'] ?? '-')) ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars((string) ($emp['obreiro_nome'] ?? $emp['obreiro_id'] ?? '-')) ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars((string) ($emp['data_emprestimo'] ?? '-')) ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars((string) ($emp['data_devolucao_prevista'] ?? '-')) ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars(ucfirst((string) ($emp['status'] ?? '-'))) ?></td>
                                <td class="px-4 py-3 text-right">
                                    <?php if (($emp['status'] ?? '') === 'pendente' || ($emp['status'] ?? '') === 'atrasado'): ?>
                                        <form action="/biblioteca/devolver" method="POST" class="inline">
                                            <input type="hidden" name="id" value="<?= htmlspecialchars((string) ($emp['id'] ?? '')) ?>">
                                            <button type="submit" class="px-3 py-1 rounded bg-green-600 text-white hover:bg-green-700">
                                                Registrar devolucao
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-gray-400">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td class="px-4 py-6 text-center text-gray-500" colspan="8">
                                Nenhum emprestimo pendente ou atrasado.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        </div>
    </main>
</body>
</html>
