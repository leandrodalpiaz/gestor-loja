<?php
$usuarioNome = $_SESSION['usuario_nome'] ?? 'Irmao';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar livro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media (min-width: 1440px) {
            .erp-readable {
                font-size: 1.08rem;
            }
            .erp-readable .text-xs,
            .erp-readable .text-[11px] {
                font-size: 0.92rem !important;
                line-height: 1.4rem !important;
            }
            .erp-readable .text-sm {
                font-size: 1.03rem !important;
                line-height: 1.58rem !important;
            }
        }
    </style>
</head>
<body class="erp-readable bg-slate-50 min-h-screen text-slate-800">
    <header class="bg-blue-900 text-white">
        <div class="max-w-4xl mx-auto px-4 py-4 flex items-center justify-between">
            <h1 class="font-semibold">Biblioteca</h1>
            <div class="text-sm"><?= htmlspecialchars($usuarioNome) ?></div>
        </div>
    </header>

    <main class="max-w-4xl mx-auto p-4 md:p-6">
        <h2 class="text-2xl font-semibold text-blue-900 mb-4">Editar livro</h2>

        <form action="/biblioteca/editar" method="POST" class="bg-white border border-slate-200 rounded-lg p-4 md:p-6 space-y-4">
            <input type="hidden" name="id" value="<?= (int) ($item['id'] ?? 0) ?>">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm font-medium">Titulo *</label>
                    <input type="text" name="titulo" required value="<?= htmlspecialchars((string) ($item['titulo'] ?? '')) ?>" class="mt-1 w-full border border-slate-300 rounded px-3 py-2">
                </div>
                <div>
                    <label class="text-sm font-medium">Autor *</label>
                    <input type="text" name="autor" required value="<?= htmlspecialchars((string) ($item['autor'] ?? '')) ?>" class="mt-1 w-full border border-slate-300 rounded px-3 py-2">
                </div>
                <div>
                    <label class="text-sm font-medium">ISBN</label>
                    <input type="text" name="isbn" value="<?= htmlspecialchars((string) ($item['isbn'] ?? '')) ?>" class="mt-1 w-full border border-slate-300 rounded px-3 py-2">
                </div>
                <div>
                    <label class="text-sm font-medium">URL da capa</label>
                    <input type="url" name="capa_url" value="<?= htmlspecialchars((string) ($item['capa_url'] ?? '')) ?>" class="mt-1 w-full border border-slate-300 rounded px-3 py-2">
                </div>
                <div class="md:col-span-2">
                    <label class="text-sm font-medium">Resumo</label>
                    <textarea name="resumo" rows="4" class="mt-1 w-full border border-slate-300 rounded px-3 py-2"><?= htmlspecialchars((string) ($item['resumo'] ?? '')) ?></textarea>
                </div>
                <div>
                    <label class="text-sm font-medium">Tipo *</label>
                    <select name="tipo" required class="mt-1 w-full border border-slate-300 rounded px-3 py-2 bg-white">
                        <?php $tipo = (string) ($item['tipo'] ?? 'Livro Fisico'); ?>
                        <option value="Livro Fisico" <?= $tipo === 'Livro Fisico' ? 'selected' : '' ?>>Livro Fisico</option>
                        <option value="Digital (PDF)" <?= $tipo === 'Digital (PDF)' ? 'selected' : '' ?>>Digital (PDF)</option>
                        <option value="Ritual" <?= $tipo === 'Ritual' ? 'selected' : '' ?>>Ritual</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium">Quantidade disponivel *</label>
                    <input type="number" name="quantidade_disponivel" min="0" value="<?= (int) ($item['quantidade_disponivel'] ?? 0) ?>" required class="mt-1 w-full border border-slate-300 rounded px-3 py-2">
                </div>
                <div>
                    <label class="text-sm font-medium">Grau recomendado</label>
                    <?php $grau = (string) ($item['grau_recomendado'] ?? 'Livre'); ?>
                    <select name="grau_recomendado" class="mt-1 w-full border border-slate-300 rounded px-3 py-2 bg-white">
                        <option value="Livre" <?= $grau === 'Livre' ? 'selected' : '' ?>>Livre</option>
                        <option value="Aprendiz" <?= $grau === 'Aprendiz' ? 'selected' : '' ?>>Aprendiz</option>
                        <option value="Companheiro" <?= $grau === 'Companheiro' ? 'selected' : '' ?>>Companheiro</option>
                        <option value="Mestre" <?= $grau === 'Mestre' ? 'selected' : '' ?>>Mestre</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium">Nota de instrucao</label>
                    <input type="text" name="nota_instrucao" value="<?= htmlspecialchars((string) ($item['nota_instrucao'] ?? '')) ?>" class="mt-1 w-full border border-slate-300 rounded px-3 py-2">
                </div>
            </div>

            <div class="pt-2 flex justify-end gap-2">
                <a href="/biblioteca" class="px-3 py-2 rounded border border-slate-300">Cancelar</a>
                <button type="submit" class="px-3 py-2 rounded bg-indigo-700 text-white">Salvar alteracoes</button>
            </div>
        </form>
    </main>
</body>
</html>

