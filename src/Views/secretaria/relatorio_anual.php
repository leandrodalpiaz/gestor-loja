<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatorio Anual da Secretaria</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-[linear-gradient(180deg,#f7f3ea_0%,#eef2f6_100%)] text-slate-800">
    <div class="mx-auto max-w-6xl px-4 py-8">
        <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between mb-8">
            <div>
                <p class="text-xs uppercase tracking-[0.25em] text-slate-500">Secretaria</p>
                <h1 class="text-3xl font-semibold text-slate-900">Relatorio anual</h1>
                <p class="mt-2 text-sm text-slate-600">Consolidacao anual da atividade da Loja sob responsabilidade da Secretaria.</p>
            </div>
            <div class="flex gap-2">
                <a href="/secretaria" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium">Voltar para Secretaria</a>
                <a href="/dashboard" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white">Painel</a>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm mb-6">
            <form method="GET" action="/secretaria/relatorio-anual" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div>
                    <label class="block text-sm font-medium mb-1">Ano de referencia</label>
                    <select name="ano" class="rounded-lg border border-slate-300 px-3 py-2">
                        <?php foreach ($anosDisponiveis as $anoOpcao): ?>
                            <option value="<?= (int) $anoOpcao ?>" <?= (int) $anoOpcao === (int) $relatorio['ano'] ? 'selected' : '' ?>>
                                <?= (int) $anoOpcao ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white">Atualizar relatorio</button>
            </form>
        </div>

        <div class="grid gap-4 md:grid-cols-5 mb-8">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm text-slate-500">Visitantes no periodo</div>
                <div class="mt-2 text-3xl font-semibold text-slate-900"><?= (int) ($relatorio['visitantes']['total'] ?? 0) ?></div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm text-slate-500">Visitas a outras Lojas</div>
                <div class="mt-2 text-3xl font-semibold text-slate-900"><?= (int) ($relatorio['visitas_externas']['total'] ?? 0) ?></div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm text-slate-500">Congressos</div>
                <div class="mt-2 text-3xl font-semibold text-slate-900"><?= (int) ($relatorio['congressos']['total'] ?? 0) ?></div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm text-slate-500">Palestras</div>
                <div class="mt-2 text-3xl font-semibold text-slate-900"><?= (int) ($relatorio['palestras']['total'] ?? 0) ?></div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm text-slate-500">Sessoes no periodo</div>
                <div class="mt-2 text-3xl font-semibold text-slate-900"><?= (int) ($relatorio['sessoes_por_grau']['total'] ?? 0) ?></div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
            <section class="space-y-6">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-semibold text-slate-900">Visitantes</h2>
                    <p class="mt-1 text-sm text-slate-600">Quantidade total de visitantes extraida dos registros estruturados da palavra a bem da ordem no balaustre.</p>
                    <div class="mt-4 text-xs text-slate-500">Fonte: <?= htmlspecialchars((string) ($relatorio['visitantes']['fonte'] ?? '')) ?></div>

                    <div class="mt-5">
                        <h3 class="text-sm font-semibold text-slate-700 mb-2">Lojas mais frequentes</h3>
                        <div class="space-y-2">
                            <?php foreach (($relatorio['visitantes']['lojas_mais_frequentes'] ?? []) as $linha): ?>
                                <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                                    <span><?= htmlspecialchars((string) ($linha['loja'] ?? 'Loja nao informada')) ?></span>
                                    <strong><?= (int) ($linha['total'] ?? 0) ?></strong>
                                </div>
                            <?php endforeach; ?>
                            <?php if (($relatorio['visitantes']['lojas_mais_frequentes'] ?? []) === []): ?>
                                <div class="rounded-xl border border-dashed border-slate-300 px-4 py-4 text-sm text-slate-500">Nao ha visitantes estruturados no periodo selecionado.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-semibold text-slate-900">Visitas a outras Lojas</h2>
                    <p class="mt-1 text-sm text-slate-600">Quantidade de vezes em que membros do quadro da Loja informaram visitas externas no saco de propostas.</p>
                    <div class="mt-4 text-xs text-slate-500">Fonte: <?= htmlspecialchars((string) ($relatorio['visitas_externas']['fonte'] ?? '')) ?></div>

                    <div class="mt-5">
                        <h3 class="text-sm font-semibold text-slate-700 mb-2">Lojas mais visitadas</h3>
                        <div class="space-y-2">
                            <?php foreach (($relatorio['visitas_externas']['lojas_mais_visitadas'] ?? []) as $linha): ?>
                                <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                                    <span><?= htmlspecialchars((string) ($linha['loja'] ?? 'Loja nao informada')) ?></span>
                                    <strong><?= (int) ($linha['total'] ?? 0) ?></strong>
                                </div>
                            <?php endforeach; ?>
                            <?php if (($relatorio['visitas_externas']['lojas_mais_visitadas'] ?? []) === []): ?>
                                <div class="rounded-xl border border-dashed border-slate-300 px-4 py-4 text-sm text-slate-500">Nao ha visitas externas estruturadas no periodo selecionado.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-semibold text-slate-900">Sessoes por grau</h2>
                    <p class="mt-1 text-sm text-slate-600"><?= htmlspecialchars((string) ($relatorio['sessoes_por_grau']['regra'] ?? '')) ?></p>
                    <div class="mt-5 space-y-2">
                        <?php foreach (($relatorio['sessoes_por_grau']['itens'] ?? []) as $linha): ?>
                            <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                                <span><?= htmlspecialchars((string) ($linha['grau_sessao'] ?? 'Nao informado')) ?></span>
                                <strong><?= (int) ($linha['total'] ?? 0) ?></strong>
                            </div>
                        <?php endforeach; ?>
                        <?php if (($relatorio['sessoes_por_grau']['itens'] ?? []) === []): ?>
                            <div class="rounded-xl border border-dashed border-slate-300 px-4 py-4 text-sm text-slate-500">Nao ha sessoes no periodo selecionado.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <section class="space-y-6">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-semibold text-slate-900">Congressos e palestras</h2>
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="text-sm text-slate-500">Congressos realizados</div>
                            <div class="mt-2 text-2xl font-semibold text-slate-900"><?= (int) ($relatorio['congressos']['total'] ?? 0) ?></div>
                            <div class="mt-2 text-xs text-slate-500">Fonte: <?= htmlspecialchars((string) ($relatorio['congressos']['fonte'] ?? '')) ?></div>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="text-sm text-slate-500">Palestras realizadas</div>
                            <div class="mt-2 text-2xl font-semibold text-slate-900"><?= (int) ($relatorio['palestras']['total'] ?? 0) ?></div>
                            <div class="mt-2 text-xs text-slate-500">Fonte: <?= htmlspecialchars((string) ($relatorio['palestras']['fonte'] ?? '')) ?></div>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-semibold text-slate-900">Quadro da Loja</h2>
                    <p class="mt-1 text-sm text-slate-600">Panorama anual da composicao do quadro com base na trilha cadastral disponivel hoje.</p>

                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="text-sm text-slate-500">Comecaram o ano no quadro</div>
                            <div class="mt-2 text-2xl font-semibold text-slate-900"><?= htmlspecialchars((string) (($relatorio['quadro']['inicio_ano'] ?? null) !== null ? $relatorio['quadro']['inicio_ano'] : '-')) ?></div>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="text-sm text-slate-500">Terminaram o ano no quadro</div>
                            <div class="mt-2 text-2xl font-semibold text-slate-900"><?= htmlspecialchars((string) (($relatorio['quadro']['fim_ano'] ?? null) !== null ? $relatorio['quadro']['fim_ano'] : '-')) ?></div>
                        </div>
                    </div>

                    <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                        <?= htmlspecialchars((string) ($relatorio['quadro']['observacao'] ?? '')) ?>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-semibold text-slate-900">Leitura administrativa</h2>
                    <ul class="mt-4 space-y-2 text-sm text-slate-600 list-disc pl-5">
                        <li>Visitantes refletem os registros estruturados no balaustre.</li>
                        <li>Visitas externas refletem os registros feitos no saco de propostas durante a sessao.</li>
                        <li>Congressos e palestras sao contabilizados a partir dos eventos informados no balaustre.</li>
                        <li>Sessoes por grau usam as sessoes do periodo com status diferente de cancelada.</li>
                        <li>O quadro anual depende da trilha cadastral dos obreiros; quanto melhor a disciplina de cadastro, melhor a precisao do indicador.</li>
                    </ul>
                </div>
            </section>
        </div>
    </div>
</body>
</html>
