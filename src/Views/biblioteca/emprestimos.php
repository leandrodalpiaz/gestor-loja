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
    <header class="bg-blue-900 text-white px-6 py-4 flex items-center justify-between">
        <h1 class="font-semibold">Biblioteca - Controle de Emprestimos</h1>
        <div class="text-sm">
            <?= htmlspecialchars($usuarioNome) ?> |
            <a class="underline" href="/logout">Sair</a>
        </div>
    </header>

    <main class="max-w-6xl mx-auto p-6">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Emprestimos pendentes/atrasados</h2>
            <a href="/biblioteca" class="text-blue-700 hover:underline">Voltar ao catalogo</a>
        </div>

        <div class="bg-white rounded shadow overflow-x-auto">
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
    </main>
</body>
</html>
