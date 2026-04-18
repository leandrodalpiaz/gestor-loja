<?php
$lista = $itens ?? [];
$usuarioNome = $_SESSION['usuario_nome'] ?? 'Irmao';
$isTestSession = isset($_SESSION['usuario_id']) && (int) $_SESSION['usuario_id'] === 0;
$allowAllPanels = filter_var($_ENV['APP_TEST_ALLOW_ALL_PANELS'] ?? 'true', FILTER_VALIDATE_BOOL);
$showAllPanels = filter_var($_ENV['APP_TEST_OPEN_ACCESS'] ?? 'false', FILTER_VALIDATE_BOOL) || $isTestSession || $allowAllPanels;
$bibliotecaPermissions = is_array($bibliotecaPermissions ?? null) ? $bibliotecaPermissions : [];
$podeGerenciar = $showAllPanels || (bool) ($bibliotecaPermissions['biblioteca.manage'] ?? false);
$podeClassificar = $showAllPanels || (bool) ($bibliotecaPermissions['biblioteca.classificar'] ?? false);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biblioteca</title>
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
    <header class="bg-blue-900 text-white shadow-sm">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-4">
            <div>
                <div class="text-xs uppercase tracking-[0.18em] text-blue-100/80">Biblioteca</div>
                <h1 class="text-lg font-semibold">Biblioteca da Loja</h1>
            </div>
            <div class="text-sm">
                <?= htmlspecialchars($usuarioNome) ?> |
                <a href="/logout" class="underline">Sair</a>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto p-4 md:p-6">
        <div class="mb-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-start sm:justify-between">
            <div>
                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Catalogo</div>
                <h2 class="mt-1 text-2xl font-semibold text-blue-900">Catalogo</h2>
                <p class="mt-1 text-sm text-slate-700">Web e mobile usam o mesmo fluxo de biblioteca.</p>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                <a href="/biblioteca/meus-emprestimos" class="w-full rounded-lg bg-slate-200 px-3 py-2 text-center text-sm font-medium text-slate-700 hover:bg-slate-300 sm:w-auto">Meus emprestimos</a>
                <?php if ($podeGerenciar): ?>
                    <a href="/biblioteca/emprestimos" class="w-full rounded-lg bg-amber-600 px-3 py-2 text-center text-sm font-medium text-white hover:bg-amber-700 sm:w-auto">Gerenciar emprestimos</a>
                    <a href="/biblioteca/adicionar" class="w-full rounded-lg bg-emerald-600 px-3 py-2 text-center text-sm font-medium text-white hover:bg-emerald-700 sm:w-auto">Novo titulo</a>
                <?php endif; ?>
            </div>
            </div>
        </div>

        <div class="space-y-3 md:hidden">
            <?php if ($lista !== []): ?>
                <?php foreach ($lista as $item): ?>
                    <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="flex gap-3">
                            <div class="shrink-0">
                                <?php if (!empty($item['capa_url'])): ?>
                                    <img src="<?= htmlspecialchars((string) $item['capa_url']) ?>" alt="Capa" class="h-20 w-14 rounded border border-slate-200 object-cover">
                                <?php else: ?>
                                    <div class="h-20 w-14 rounded border border-slate-200 bg-slate-100"></div>
                                <?php endif; ?>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-start gap-2">
                                    <h3 class="min-w-0 flex-1 text-base font-semibold text-slate-900"><?= htmlspecialchars((string) ($item['titulo'] ?? '')) ?></h3>
                                    <?php if ((bool) ($item['disponivel'] ?? false)): ?>
                                        <span class="rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">Disponivel</span>
                                    <?php else: ?>
                                        <span class="rounded-full border border-rose-200 bg-rose-50 px-2.5 py-1 text-xs font-medium text-rose-700">Indisponivel</span>
                                    <?php endif; ?>
                                </div>
                                <p class="mt-1 text-sm text-slate-700"><?= htmlspecialchars((string) ($item['autor'] ?? '-')) ?></p>
                                <div class="mt-2 space-y-1 text-xs text-slate-700">
                                    <div>Codigo: <span class="font-mono"><?= htmlspecialchars((string) ($item['codigo_acervo'] ?? '')) ?></span></div>
                                    <div>ISBN: <?= htmlspecialchars((string) ($item['isbn'] ?? '-')) ?></div>
                                    <div>Exemplares livres: <?= (int) ($item['quantidade_disponivel'] ?? 0) ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 flex flex-wrap gap-2 text-xs text-slate-700">
                            <span class="rounded-full bg-slate-100 px-2.5 py-1">Gostou: <?= (int) ($item['total_gostei_sim'] ?? 0) ?></span>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1">Nao gostou: <?= (int) ($item['total_gostei_nao'] ?? 0) ?></span>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1">Comentarios: <?= (int) ($item['total_comentarios'] ?? 0) ?></span>
                        </div>

                        <div class="mt-4 flex flex-col gap-2">
                            <a href="/biblioteca/detalhes?id=<?= (int) ($item['id'] ?? 0) ?>" class="w-full rounded-lg bg-blue-700 px-3 py-2 text-center text-sm font-medium text-white hover:bg-blue-800">Ver detalhes</a>
                            <div class="flex flex-wrap gap-2">
                                <?php if ($podeGerenciar): ?>
                                    <a href="/biblioteca/editar?id=<?= (int) ($item['id'] ?? 0) ?>" class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-center text-sm text-slate-700 hover:bg-slate-50">Editar</a>
                                <?php endif; ?>
                                <?php if ($podeClassificar): ?>
                                    <button onclick="abrirModalClassificacao(<?= (int) ($item['id'] ?? 0) ?>, '<?= addslashes((string) ($item['titulo'] ?? '')) ?>', '<?= addslashes((string) ($item['grau_recomendado'] ?? 'Livre')) ?>', '<?= addslashes((string) ($item['nota_instrucao'] ?? '')) ?>')" class="flex-1 rounded-lg border border-purple-300 px-3 py-2 text-sm text-purple-700 hover:bg-purple-50">Classificar</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-8 text-center text-slate-700">
                    Nenhum titulo cadastrado.
                </div>
            <?php endif; ?>
        </div>

        <div class="hidden overflow-hidden rounded-lg border border-slate-200 bg-white md:block">
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
                                        <div class="text-xs text-slate-700">ISBN: <?= htmlspecialchars((string) ($item['isbn'] ?? '-')) ?></div>
                                    </td>
                                    <td class="px-4 py-3"><?= htmlspecialchars((string) ($item['autor'] ?? '-')) ?></td>
                                    <td class="px-4 py-3">
                                        <?php if ((bool) ($item['disponivel'] ?? false)): ?>
                                            <span class="rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">Disponivel (<?= (int) ($item['quantidade_disponivel'] ?? 0) ?>)</span>
                                        <?php else: ?>
                                            <span class="rounded-full border border-rose-200 bg-rose-50 px-2.5 py-1 text-xs font-medium text-rose-700">Indisponivel</span>
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
                                <td colspan="7" class="px-4 py-8 text-center text-slate-700">Nenhum titulo cadastrado.</td>
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
                    <p id="modal-livro-titulo" class="text-sm text-slate-700 mt-1"></p>
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

