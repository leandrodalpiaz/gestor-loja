<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sessao do Chanceler - Efemerides</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen text-gray-800">
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-6">
            <h1 class="text-2xl font-bold">Sessao do Chanceler - Gestao de Efemerides</h1>
            <a href="/dashboard" class="text-sm text-blue-700 hover:underline">Voltar ao dashboard</a>
        </div>

        <?php if (!empty($sucessoMensagem)): ?>
            <div class="mb-4 rounded border border-green-200 bg-green-50 text-green-700 px-4 py-3">
                <?= htmlspecialchars($sucessoMensagem) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($erroMensagem)): ?>
            <div class="mb-4 rounded border border-red-200 bg-red-50 text-red-700 px-4 py-3">
                <?= htmlspecialchars($erroMensagem) ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <section class="bg-white rounded-lg border border-gray-200 shadow-sm p-4">
                <div class="flex items-center justify-between mb-2">
                    <h2 class="font-semibold">Previa diaria para revisao</h2>
                    <span class="text-xs text-gray-500">Gerada automaticamente apos 00:01</span>
                </div>
                <?php
                    $previewRaw = (string) ($mensagemPreview ?? '');
                    $previewRender = strip_tags($previewRaw, '<b><i><u><strong><em>');
                    $previewRender = nl2br($previewRender, false);
                ?>
                <style>
                    .telegram-format b, .telegram-format strong { font-weight: bold; }
                    .telegram-format i, .telegram-format em { font-style: italic; }
                </style>
                <div class="telegram-format mb-2 rounded border border-slate-200 bg-slate-50 px-3 py-2 text-sm leading-6">
                    <?= $previewRender ?>
                </div>
                <form method="POST" action="/chancelaria/efemerides/salvar-previa">
                    <textarea id="previewMsg" name="mensagem_preview" class="w-full h-72 p-3 text-sm border border-gray-300 rounded bg-white"><?= htmlspecialchars($mensagemPreview ?? '') ?></textarea>
                    <p class="mt-2 text-xs text-gray-500">Mantem HTML do Telegram (ex.: <b> e <i>).</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <button type="submit" class="px-3 py-2 text-sm rounded bg-gray-800 text-white hover:bg-gray-900">Salvar edicao</button>
                        <button type="button" onclick="copiarPreview()" class="px-3 py-2 text-sm rounded bg-blue-700 text-white hover:bg-blue-800">Copiar mensagem</button>
                    </div>
                </form>
                <div class="mt-3 flex flex-wrap gap-2">
                    <form method="POST" action="/chancelaria/efemerides/enviar-previa" onsubmit="return confirm('Enviar a previa para o Telegram privado do chanceler?');">
                        <button type="submit" class="px-3 py-2 text-sm rounded bg-indigo-700 text-white hover:bg-indigo-800">Enviar previa no privado</button>
                    </form>
                    <form method="POST" action="/chancelaria/efemerides/enviar-grupo" onsubmit="return confirm('Confirmar envio da mensagem no grupo oficial?');">
                        <button type="submit" class="px-3 py-2 text-sm rounded bg-emerald-700 text-white hover:bg-emerald-800">Enviar no grupo oficial</button>
                    </form>
                </div>
                <p class="mt-2 text-xs text-gray-500">Fluxo recomendado: gerar automatica -> revisar/editar -> enviar no privado -> enviar no grupo.</p>
            </section>

            <section class="bg-white rounded-lg border border-gray-200 shadow-sm p-4">
                <h2 class="font-semibold mb-3">Adicionar novo registro</h2>
                <form method="POST" action="/chancelaria/efemerides/salvar" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="md:col-span-2">
                        <label class="text-sm block mb-1">Nome</label>
                        <input type="text" name="nome" required class="w-full border border-gray-300 rounded px-3 py-2">
                    </div>

                    <div>
                        <label class="text-sm block mb-1">Tipo</label>
                        <select name="tipo" required class="w-full border border-gray-300 rounded px-3 py-2">
                            <?php foreach (($tiposEfemeride ?? []) as $tipoOpcao): ?>
                                <option value="<?= htmlspecialchars($tipoOpcao) ?>"><?= htmlspecialchars($tipoOpcao) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="text-sm block mb-1">Data do evento</label>
                        <input type="date" name="data_evento" required class="w-full border border-gray-300 rounded px-3 py-2">
                    </div>

                    <div>
                        <label class="text-sm block mb-1">Codigo vinculo (1-6)</label>
                        <input type="number" name="cod_vinculo" min="1" max="6" class="w-full border border-gray-300 rounded px-3 py-2" placeholder="Opcional">
                    </div>

                    <div>
                        <label class="text-sm block mb-1">Vinculo</label>
                        <input type="text" name="vinculo" class="w-full border border-gray-300 rounded px-3 py-2" placeholder="Ex.: esposa, filho">
                    </div>

                    <div>
                        <label class="text-sm block mb-1">Parentesco (irmao relacionado)</label>
                        <input type="text" name="parentesco" class="w-full border border-gray-300 rounded px-3 py-2" placeholder="Ex.: Leandro Dalpiaz">
                    </div>

                    <div>
                        <label class="text-sm block mb-1">Local</label>
                        <input type="text" name="local" class="w-full border border-gray-300 rounded px-3 py-2" placeholder="Ex.: Loja Renascenca n 270">
                    </div>

                    <div class="md:col-span-2">
                        <label class="text-sm block mb-1">Mensagem complementar/custom</label>
                        <textarea name="mensagem_custom" rows="4" class="w-full border border-gray-300 rounded px-3 py-2" placeholder="Para Historia, informe o texto completo aqui."></textarea>
                    </div>

                    <div class="md:col-span-2 flex flex-wrap gap-2">
                        <button type="submit" class="px-4 py-2 rounded bg-emerald-700 text-white hover:bg-emerald-800">Salvar registro</button>
                        <a href="/miniapp/aniversario" class="px-4 py-2 rounded bg-slate-200 text-slate-800 hover:bg-slate-300">Miniapp Aniversario</a>
                        <a href="/miniapp/data-maconica" class="px-4 py-2 rounded bg-slate-200 text-slate-800 hover:bg-slate-300">Miniapp Data Maconica</a>
                        <a href="/miniapp/historico" class="px-4 py-2 rounded bg-slate-200 text-slate-800 hover:bg-slate-300">Miniapp Historico</a>
                        <a href="/miniapp/fallback" class="px-4 py-2 rounded bg-slate-200 text-slate-800 hover:bg-slate-300">Miniapp Fallback</a>
                    </div>
                </form>
            </section>
        </div>

        <section class="bg-white rounded-lg border border-gray-200 shadow-sm p-4 mt-6">
            <h2 class="font-semibold mb-3">Pesquisa e manutencao de registros</h2>
            <form method="GET" action="/chancelaria/efemerides" class="grid grid-cols-1 md:grid-cols-6 gap-3 mb-4">
                <div class="md:col-span-2">
                    <label class="text-sm block mb-1">Pesquisar por irmao/nome/vinculo</label>
                    <input type="text" name="termo" value="<?= htmlspecialchars($filtroTermo ?? '') ?>" class="w-full border border-gray-300 rounded px-3 py-2" placeholder="Ex.: nome do irmao">
                </div>
                <div>
                    <label class="text-sm block mb-1">Tipo de evento</label>
                    <select name="tipo" class="w-full border border-gray-300 rounded px-3 py-2">
                        <option value="">Todos</option>
                        <?php foreach (($tiposEfemeride ?? []) as $tipoOpcao): ?>
                            <option value="<?= htmlspecialchars($tipoOpcao) ?>" <?= (($filtroTipo ?? '') === $tipoOpcao) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($tipoOpcao) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="text-sm block mb-1">Status</label>
                    <select name="ativo" class="w-full border border-gray-300 rounded px-3 py-2">
                        <option value="1" <?= (($filtroAtivo ?? '1') === '1') ? 'selected' : '' ?>>Ativos</option>
                        <option value="0" <?= (($filtroAtivo ?? '') === '0') ? 'selected' : '' ?>>Inativos</option>
                        <option value="all" <?= (($filtroAtivo ?? '') === 'all') ? 'selected' : '' ?>>Todos</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm block mb-1">Data inicial</label>
                    <input type="date" name="data_ini" value="<?= htmlspecialchars($filtroDataIni ?? '') ?>" class="w-full border border-gray-300 rounded px-3 py-2">
                </div>
                <div>
                    <label class="text-sm block mb-1">Data final</label>
                    <input type="date" name="data_fim" value="<?= htmlspecialchars($filtroDataFim ?? '') ?>" class="w-full border border-gray-300 rounded px-3 py-2">
                </div>
                <div class="md:col-span-6 flex gap-2">
                    <button type="submit" class="px-4 py-2 rounded bg-blue-700 text-white hover:bg-blue-800">Filtrar</button>
                    <a href="/chancelaria/efemerides" class="px-4 py-2 rounded bg-gray-200 text-gray-700 hover:bg-gray-300">Limpar filtros</a>
                </div>
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left px-3 py-2">Data</th>
                            <th class="text-left px-3 py-2">Nome</th>
                            <th class="text-left px-3 py-2">Tipo</th>
                            <th class="text-left px-3 py-2">Vinculo/Parentesco</th>
                            <th class="text-left px-3 py-2">Status</th>
                            <th class="text-left px-3 py-2">Acao</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach (($registrosRecentes ?? []) as $r): ?>
                        <tr class="border-t align-top">
                            <td class="px-3 py-2"><?= htmlspecialchars((string) ($r['data_evento'] ?? '')) ?></td>
                            <td class="px-3 py-2"><?= htmlspecialchars((string) ($r['nome'] ?? '')) ?></td>
                            <td class="px-3 py-2"><?= htmlspecialchars((string) ($r['tipo'] ?? '')) ?></td>
                            <td class="px-3 py-2">
                                <div><?= htmlspecialchars((string) ($r['vinculo'] ?? '-')) ?></div>
                                <div class="text-xs text-gray-500"><?= htmlspecialchars((string) ($r['parentesco'] ?? '-')) ?></div>
                            </td>
                            <td class="px-3 py-2"><?= !empty($r['ativo']) ? 'Ativo' : 'Inativo' ?></td>
                            <td class="px-3 py-2">
                                <?php if (!empty($r['ativo'])): ?>
                                    <form method="POST" action="/chancelaria/efemerides/atualizar" class="space-y-2 mb-2">
                                        <input type="hidden" name="registro_id" value="<?= (int) ($r['id'] ?? 0) ?>">
                                        <input type="text" name="nome" value="<?= htmlspecialchars((string) ($r['nome'] ?? '')) ?>" class="w-full border border-gray-300 rounded px-2 py-1" required>
                                        <select name="tipo" class="w-full border border-gray-300 rounded px-2 py-1" required>
                                            <?php foreach (($tiposEfemeride ?? []) as $tipoOpcao): ?>
                                                <option value="<?= htmlspecialchars($tipoOpcao) ?>" <?= (($r['tipo'] ?? '') === $tipoOpcao) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($tipoOpcao) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="date" name="data_evento" value="<?= htmlspecialchars((string) ($r['data_evento'] ?? '')) ?>" class="w-full border border-gray-300 rounded px-2 py-1" required>
                                        <input type="number" name="cod_vinculo" min="1" max="6" value="<?= htmlspecialchars((string) ($r['cod_vinculo'] ?? '')) ?>" class="w-full border border-gray-300 rounded px-2 py-1" placeholder="Cod. vinculo">
                                        <input type="text" name="vinculo" value="<?= htmlspecialchars((string) ($r['vinculo'] ?? '')) ?>" class="w-full border border-gray-300 rounded px-2 py-1" placeholder="Vinculo">
                                        <input type="text" name="parentesco" value="<?= htmlspecialchars((string) ($r['parentesco'] ?? '')) ?>" class="w-full border border-gray-300 rounded px-2 py-1" placeholder="Parentesco">
                                        <input type="text" name="local" value="<?= htmlspecialchars((string) ($r['local'] ?? '')) ?>" class="w-full border border-gray-300 rounded px-2 py-1" placeholder="Local">
                                        <textarea name="mensagem_custom" rows="2" class="w-full border border-gray-300 rounded px-2 py-1" placeholder="Mensagem custom"><?= htmlspecialchars((string) ($r['mensagem_custom'] ?? '')) ?></textarea>
                                        <button type="submit" class="w-full text-left text-blue-700 hover:underline">Salvar edicao</button>
                                    </form>
                                    <form method="POST" action="/chancelaria/efemerides/desativar" onsubmit="return confirm('Desativar este registro?');">
                                        <input type="hidden" name="id" value="<?= (int) ($r['id'] ?? 0) ?>">
                                        <button type="submit" class="text-red-700 hover:underline">Desativar</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-gray-500">Sem acoes para inativos</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <script>
        function copiarPreview() {
            const el = document.getElementById('previewMsg');
            el.select();
            document.execCommand('copy');
            alert('Mensagem copiada para a area de transferencia.');
        }
    </script>
</body>
</html>
