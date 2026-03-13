<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sessão do Chanceler - Efemérides</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen text-gray-800">
    <div class="max-w-6xl mx-auto px-4 py-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">Sessão do Chanceler - Prévia de Efemérides</h1>
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
                    <h2 class="font-semibold">Prévia diária para revisão</h2>
                    <span class="text-xs text-gray-500">Gerada automaticamente após 00:01 para o chanceler revisar</span>
                </div>
                <?php
                    $previewRaw = (string) ($mensagemPreview ?? '');
                    $previewRender = strip_tags($previewRaw, '<b><i><u><strong><em>');
                    $previewRender = nl2br($previewRender, false);
                ?>
                <div class="mb-2 rounded border border-slate-200 bg-slate-50 px-3 py-2 text-sm leading-6">
                    <?= $previewRender ?>
                </div>
                <form method="POST" action="/chancelaria/efemerides/salvar-previa">
                    <textarea id="previewMsg" name="mensagem_preview" class="w-full h-72 p-3 text-sm border border-gray-300 rounded bg-white"><?= htmlspecialchars($mensagemPreview ?? '') ?></textarea>
                    <p class="mt-2 text-xs text-gray-500">Campo de edição mantém o HTML do Telegram (ex.: &lt;b&gt; e &lt;i&gt;).</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <button type="submit" class="px-3 py-2 text-sm rounded bg-gray-800 text-white hover:bg-gray-900">Salvar edição</button>
                        <button type="button" onclick="copiarPreview()" class="px-3 py-2 text-sm rounded bg-blue-700 text-white hover:bg-blue-800">Copiar mensagem</button>
                    </div>
                </form>
                <div class="mt-3 flex flex-wrap gap-2">
                    <form method="POST" action="/chancelaria/efemerides/enviar-previa" onsubmit="return confirm('Enviar a prévia para o Telegram privado do chanceler?');">
                        <button type="submit" class="px-3 py-2 text-sm rounded bg-indigo-700 text-white hover:bg-indigo-800">Enviar prévia no privado</button>
                    </form>
                    <form method="POST" action="/chancelaria/efemerides/enviar-grupo" onsubmit="return confirm('Confirmar envio da mensagem no grupo oficial?');">
                        <button type="submit" class="px-3 py-2 text-sm rounded bg-emerald-700 text-white hover:bg-emerald-800">Enviar no grupo oficial</button>
                    </form>
                </div>
                <p class="mt-2 text-xs text-gray-500">Fluxo recomendado: gerar automática (cron) → revisar/editar/salvar → enviar no privado → enviar no grupo.</p>
            </section>

            <section class="bg-white rounded-lg border border-gray-200 shadow-sm p-4">
                <h2 class="font-semibold mb-3">Adicionar novo registro (sem planilha)</h2>
                <form method="POST" action="/chancelaria/efemerides/salvar" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="md:col-span-2">
                        <label class="text-sm block mb-1">Nome</label>
                        <input type="text" name="nome" required class="w-full border border-gray-300 rounded px-3 py-2">
                    </div>

                    <div>
                        <label class="text-sm block mb-1">Tipo</label>
                        <select name="tipo" required class="w-full border border-gray-300 rounded px-3 py-2">
                            <option value="Aniversário">Aniversário</option>
                            <option value="Iniciação">Iniciação</option>
                            <option value="Elevação">Elevação</option>
                            <option value="Exaltação">Exaltação</option>
                            <option value="Instalação">Instalação</option>
                            <option value="Oriente Eterno">Oriente Eterno</option>
                            <option value="História">História</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-sm block mb-1">Data do evento</label>
                        <input type="date" name="data_evento" required class="w-full border border-gray-300 rounded px-3 py-2">
                    </div>

                    <div>
                        <label class="text-sm block mb-1">Código vínculo (1-6)</label>
                        <input type="number" name="cod_vinculo" min="1" max="6" class="w-full border border-gray-300 rounded px-3 py-2" placeholder="Opcional">
                    </div>

                    <div>
                        <label class="text-sm block mb-1">Vínculo</label>
                        <input type="text" name="vinculo" class="w-full border border-gray-300 rounded px-3 py-2" placeholder="Ex.: esposa, filho">
                    </div>

                    <div>
                        <label class="text-sm block mb-1">Parentesco</label>
                        <input type="text" name="parentesco" class="w-full border border-gray-300 rounded px-3 py-2" placeholder="Ex.: Leandro Dalpiaz">
                    </div>

                    <div>
                        <label class="text-sm block mb-1">Local</label>
                        <input type="text" name="local" class="w-full border border-gray-300 rounded px-3 py-2" placeholder="Ex.: Loja Renascença nº 270">
                    </div>

                    <div class="md:col-span-2">
                        <label class="text-sm block mb-1">Mensagem complementar/custom (opcional)</label>
                        <textarea name="mensagem_custom" rows="4" class="w-full border border-gray-300 rounded px-3 py-2" placeholder="Para História, informe o texto completo aqui."></textarea>
                    </div>

                    <div class="md:col-span-2">
                        <button type="submit" class="px-4 py-2 rounded bg-emerald-700 text-white hover:bg-emerald-800">Salvar registro</button>
                    </div>
                </form>
            </section>
        </div>

        <section class="bg-white rounded-lg border border-gray-200 shadow-sm p-4 mt-6">
            <h2 class="font-semibold mb-3">Registros recentes</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left px-3 py-2">Data</th>
                            <th class="text-left px-3 py-2">Nome</th>
                            <th class="text-left px-3 py-2">Tipo</th>
                            <th class="text-left px-3 py-2">Status</th>
                            <th class="text-left px-3 py-2">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach (($registrosRecentes ?? []) as $r): ?>
                        <tr class="border-t">
                            <td class="px-3 py-2"><?= htmlspecialchars($r['data_evento'] ?? '') ?></td>
                            <td class="px-3 py-2"><?= htmlspecialchars($r['nome'] ?? '') ?></td>
                            <td class="px-3 py-2"><?= htmlspecialchars($r['tipo'] ?? '') ?></td>
                            <td class="px-3 py-2"><?= !empty($r['ativo']) ? 'Ativo' : 'Inativo' ?></td>
                            <td class="px-3 py-2">
                                <?php if (!empty($r['ativo'])): ?>
                                    <form method="POST" action="/chancelaria/efemerides/desativar" onsubmit="return confirm('Desativar este registro?');">
                                        <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                        <button type="submit" class="text-red-700 hover:underline">Desativar</button>
                                    </form>
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
            alert('Mensagem copiada para a área de transferência.');
        }
    </script>
</body>
</html>
