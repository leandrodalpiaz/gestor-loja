<?php
$usuarioNome = $_SESSION['usuario_nome'] ?? 'Irmao';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo livro</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen text-slate-800">
    <header class="bg-blue-900 text-white">
        <div class="max-w-4xl mx-auto px-4 py-4 flex items-center justify-between">
            <h1 class="font-semibold">Biblioteca</h1>
            <div class="text-sm"><?= htmlspecialchars($usuarioNome) ?></div>
        </div>
    </header>

    <main class="max-w-4xl mx-auto p-4 md:p-6">
        <h2 class="text-2xl font-semibold text-blue-900 mb-1">Cadastrar livro</h2>
        <p class="text-sm text-slate-500 mb-4">Cadastro manual com apoio automatico por ISBN para capa e resumo.</p>

        <form action="/biblioteca/adicionar" method="POST" class="bg-white border border-slate-200 rounded-lg p-4 md:p-6 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm font-medium">Titulo *</label>
                    <input type="text" name="titulo" required class="mt-1 w-full border border-slate-300 rounded px-3 py-2">
                </div>
                <div>
                    <label class="text-sm font-medium">Autor *</label>
                    <input type="text" name="autor" required class="mt-1 w-full border border-slate-300 rounded px-3 py-2">
                </div>
                <div>
                    <label class="text-sm font-medium">ISBN</label>
                    <input type="text" name="isbn" class="mt-1 w-full border border-slate-300 rounded px-3 py-2">
                </div>
                <div>
                    <label class="text-sm font-medium">URL da capa</label>
                    <input type="url" name="capa_url" class="mt-1 w-full border border-slate-300 rounded px-3 py-2">
                </div>
                <div class="md:col-span-2">
                    <label class="text-sm font-medium">Resumo</label>
                    <textarea name="resumo" rows="4" class="mt-1 w-full border border-slate-300 rounded px-3 py-2"></textarea>
                </div>
                <div>
                    <label class="text-sm font-medium">Tipo *</label>
                    <select name="tipo" required class="mt-1 w-full border border-slate-300 rounded px-3 py-2 bg-white">
                        <option value="Livro Fisico">Livro Fisico</option>
                        <option value="Digital (PDF)">Digital (PDF)</option>
                        <option value="Ritual">Ritual</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium">Quantidade disponivel *</label>
                    <input type="number" name="quantidade_disponivel" min="1" value="1" required class="mt-1 w-full border border-slate-300 rounded px-3 py-2">
                </div>
                <div>
                    <label class="text-sm font-medium">Grau recomendado</label>
                    <select name="grau_recomendado" class="mt-1 w-full border border-slate-300 rounded px-3 py-2 bg-white">
                        <option value="Livre">Livre</option>
                        <option value="Aprendiz">Aprendiz</option>
                        <option value="Companheiro">Companheiro</option>
                        <option value="Mestre">Mestre</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium">Nota de instrucao</label>
                    <input type="text" name="nota_instrucao" class="mt-1 w-full border border-slate-300 rounded px-3 py-2">
                </div>
            </div>

            <div class="pt-2 flex justify-end gap-2">
                <a href="/biblioteca" class="px-3 py-2 rounded border border-slate-300">Cancelar</a>
                <button type="submit" class="px-3 py-2 rounded bg-emerald-600 hover:bg-emerald-700 text-white">Salvar livro</button>
            </div>
        </form>
    </main>
</body>
</html>
