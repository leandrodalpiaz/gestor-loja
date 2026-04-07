<?php
$lista = $itens ?? [];
$usuarioNome = $_SESSION['usuario_nome'] ?? 'Irmao';
$usuarioCargos = $_SESSION['usuario_cargos'] ?? [$_SESSION['usuario_cargo'] ?? ''];
$usuarioCargos = array_values(array_unique(array_filter(array_map(
    static fn ($role) => strtolower(trim((string) $role)),
    $usuarioCargos
))));

$isTestSession = isset($_SESSION['usuario_id']) && (int) $_SESSION['usuario_id'] === 0;
$allowAllPanels = filter_var($_ENV['APP_TEST_ALLOW_ALL_PANELS'] ?? 'true', FILTER_VALIDATE_BOOL);
$showAllPanels = filter_var($_ENV['APP_TEST_OPEN_ACCESS'] ?? 'false', FILTER_VALIDATE_BOOL) || $isTestSession || $allowAllPanels;

$podeGerenciar = $showAllPanels || count(array_intersect($usuarioCargos, ['bibliotecario', 'veneravel', 'admin'])) > 0;
$podeClassificar = $showAllPanels || count(array_intersect($usuarioCargos, ['primeiro_vigilante', 'segundo_vigilante', 'bibliotecario', 'veneravel', 'admin'])) > 0;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biblioteca</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen text-slate-800">
    <header class="bg-blue-900 text-white">
        <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
            <h1 class="font-semibold">Biblioteca da Loja</h1>
            <div class="text-sm">
                <?= htmlspecialchars($usuarioNome) ?> |
                <a href="/logout" class="underline">Sair</a>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto p-4 md:p-6">
        <div class="mb-4 flex flex-wrap gap-2 justify-between">
            <div>
                <h2 class="text-2xl font-semibold text-blue-900">Catalogo</h2>
                <p class="text-sm text-slate-500">Web e mobile usam o mesmo fluxo de biblioteca.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="/biblioteca/meus-emprestimos" class="px-3 py-2 rounded bg-slate-200 hover:bg-slate-300 text-sm">Meus emprestimos</a>
                <?php if ($podeGerenciar): ?>
                    <a href="/biblioteca/emprestimos" class="px-3 py-2 rounded bg-amber-600 hover:bg-amber-700 text-white text-sm">Gerenciar emprestimos</a>
                    <a href="/biblioteca/adicionar" class="px-3 py-2 rounded bg-emerald-600 hover:bg-emerald-700 text-white text-sm">Novo titulo</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-100">
                        <tr>
                            <th class="text-left px-4 py-3">Capa</th>
                            <th class="text-left px-4 py-3">Codigo</th>
                            <th class="text-left px-4 py-3">Titulo</th>
                            <th class="text-left px-4 py-3">Autor</th>
                            <th class="text-left px-4 py-3">Disponibilidade</th>
                            <th class="text-left px-4 py-3">Reacao</th>
                            <th class="text-right px-4 py-3">Acoes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if ($lista !== []): ?>
                            <?php foreach ($lista as $item): ?>
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3">
                                        <?php if (!empty($item['capa_url'])): ?>
                                            <img src="<?= htmlspecialchars((string) $item['capa_url']) ?>" alt="Capa" class="h-16 w-12 object-cover rounded border border-slate-200">
                                        <?php else: ?>
                                            <div class="h-16 w-12 rounded border border-slate-200 bg-slate-100"></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 font-mono text-xs"><?= htmlspecialchars((string) ($item['codigo_acervo'] ?? '')) ?></td>
                                    <td class="px-4 py-3">
                                        <div class="font-medium"><?= htmlspecialchars((string) ($item['titulo'] ?? '')) ?></div>
                                        <div class="text-xs text-slate-500">ISBN: <?= htmlspecialchars((string) ($item['isbn'] ?? '-')) ?></div>
                                    </td>
                                    <td class="px-4 py-3"><?= htmlspecialchars((string) ($item['autor'] ?? '-')) ?></td>
                                    <td class="px-4 py-3">
                                        <?php if ((bool) ($item['disponivel'] ?? false)): ?>
                                            <span class="px-2 py-1 rounded-full bg-emerald-100 text-emerald-800 text-xs">Disponivel (<?= (int) ($item['quantidade_disponivel'] ?? 0) ?>)</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 rounded-full bg-rose-100 text-rose-800 text-xs">Indisponivel</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-xs">
                                        <div>Gostou: <?= (int) ($item['total_gostei_sim'] ?? 0) ?></div>
                                        <div>Nao gostou: <?= (int) ($item['total_gostei_nao'] ?? 0) ?></div>
                                        <div>Comentarios: <?= (int) ($item['total_comentarios'] ?? 0) ?></div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="/biblioteca/detalhes?id=<?= (int) ($item['id'] ?? 0) ?>" class="text-blue-700 hover:underline mr-3">Detalhes</a>
                                        <?php if ($podeGerenciar): ?>
                                            <a href="/biblioteca/editar?id=<?= (int) ($item['id'] ?? 0) ?>" class="text-indigo-700 hover:underline mr-3">Editar</a>
                                        <?php endif; ?>
                                        <?php if ($podeClassificar): ?>
                                            <button onclick="abrirModalClassificacao(<?= (int) ($item['id'] ?? 0) ?>, '<?= addslashes((string) ($item['titulo'] ?? '')) ?>', '<?= addslashes((string) ($item['grau_recomendado'] ?? 'Livre')) ?>', '<?= addslashes((string) ($item['nota_instrucao'] ?? '')) ?>')" class="text-purple-700 hover:underline">Classificar</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-slate-500">Nenhum titulo cadastrado.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div id="modalClassificacao" class="fixed inset-0 bg-black/50 hidden items-center justify-center p-4">
        <div class="bg-white rounded-lg w-full max-w-lg">
            <form action="/biblioteca/classificar" method="POST">
                <div class="p-4 border-b border-slate-200">
                    <h3 class="font-semibold text-lg">Classificar leitura sugerida</h3>
                    <p id="modal-livro-titulo" class="text-sm text-slate-500 mt-1"></p>
                </div>
                <div class="p-4 space-y-3">
                    <input type="hidden" name="livro_id" id="modal-livro-id">
                    <div>
                        <label class="text-sm font-medium">Grau sugerido</label>
                        <select name="grau_recomendado" id="modal-grau" class="mt-1 w-full border border-slate-300 rounded px-3 py-2">
                            <option value="Livre">Livre</option>
                            <option value="Aprendiz">Aprendiz</option>
                            <option value="Companheiro">Companheiro</option>
                            <option value="Mestre">Mestre</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-medium">Nota de instrucao</label>
                        <textarea name="nota_instrucao" id="modal-nota" rows="3" class="mt-1 w-full border border-slate-300 rounded px-3 py-2"></textarea>
                    </div>
                </div>
                <div class="p-4 border-t border-slate-200 flex justify-end gap-2">
                    <button type="button" onclick="fecharModal()" class="px-3 py-2 rounded border border-slate-300">Cancelar</button>
                    <button type="submit" class="px-3 py-2 rounded bg-purple-700 text-white">Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('modalClassificacao');
        function abrirModalClassificacao(id, titulo, grauAtual, notaAtual) {
            document.getElementById('modal-livro-id').value = id;
            document.getElementById('modal-livro-titulo').innerText = titulo;
            document.getElementById('modal-grau').value = grauAtual || 'Livre';
            document.getElementById('modal-nota').value = notaAtual || '';
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
        function fecharModal() {
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }
    </script>
</body>
</html>
