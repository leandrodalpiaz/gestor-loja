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
            <h1 class="font-semibold">Biblioteca</h1>
            <div class="text-sm"><?= htmlspecialchars($usuarioNome) ?></div>
        </div>
    </header>

    <main class="max-w-6xl mx-auto p-4 md:p-6">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-xl font-semibold text-blue-900">Meus emprestimos</h2>
            <a href="/biblioteca" class="text-blue-700 hover:underline text-sm">Voltar ao catalogo</a>
        </div>

        <div class="bg-white rounded border border-slate-200 overflow-x-auto">
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
