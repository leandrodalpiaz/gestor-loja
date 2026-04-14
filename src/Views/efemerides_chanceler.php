<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sessao do Chanceler - Efemerides</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-[linear-gradient(180deg,#f8f4ea_0%,#eef2f7_100%)] text-slate-800">
    <div class="max-w-7xl mx-auto px-4 py-8">
        <?php $focoTela = trim((string) ($focoEfemeride ?? '')); ?>
        <header class="mb-6 rounded-3xl border border-white/40 bg-[radial-gradient(circle_at_top_left,#d6b672,transparent_30%),linear-gradient(135deg,#162033,#223145)] px-6 py-7 text-white shadow-xl">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-xs uppercase tracking-[0.24em] text-amber-300">Chancelaria</p>
                    <h1 class="mt-2 text-3xl font-semibold">Efemerides e mensagem do dia</h1>
                    <p class="mt-2 max-w-3xl text-sm text-slate-200">Aqui a Chancelaria revisa a mensagem do dia, atualiza os registros e mantem a memoria da Loja em ordem.</p>
                </div>
                <a href="/dashboard" class="rounded-md bg-white/10 px-3 py-2 text-sm hover:bg-white/20">Voltar ao dashboard</a>
            </div>
        </header>

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
        <?php $vinculosPadrao = is_array($vinculosPadrao ?? null) ? $vinculosPadrao : []; ?>
        <?php $modoListaCompleta = (($filtroIrmaoRef ?? '') === '') && trim((string) ($filtroTermo ?? '')) === ''; ?>
        <?php
        $formatarDataVisual = static function (?string $valor): string {
            $valor = trim((string) $valor);
            if ($valor === '') {
                return '-';
            }

            try {
                return (new DateTimeImmutable($valor))->format('d-m-Y');
            } catch (Throwable $e) {
                return $valor;
            }
        };
        ?>

        <div class="mb-6 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4">
                <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400">Acesso rapido</p>
                <h2 class="mt-2 text-2xl font-semibold text-slate-900">Tarefas da Chancelaria</h2>
                <p class="mt-1 text-sm text-slate-600">Aqui voce encontra os atalhos mais usados: certificado, mensagem do dia e atualizacao dos registros.</p>
            </div>
            <div class="mb-4 flex flex-wrap gap-2">
                <a href="/chancelaria/certificado" class="px-3 py-2 text-sm rounded bg-cobalto text-white hover:bg-blue-800">Emitir certificado</a>
                <a href="/miniapp/aniversario" class="px-3 py-2 text-sm rounded bg-slate-100 text-slate-700 hover:bg-slate-200">Aniversarios</a>
                <a href="/miniapp/data-maconica" class="px-3 py-2 text-sm rounded bg-slate-100 text-slate-700 hover:bg-slate-200">Datas maconicas</a>
                <a href="/miniapp/historico" class="px-3 py-2 text-sm rounded bg-slate-100 text-slate-700 hover:bg-slate-200">Fatos historicos</a>
                <a href="/miniapp/fallback" class="px-3 py-2 text-sm rounded bg-slate-100 text-slate-700 hover:bg-slate-200">Fallback</a>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="/chancelaria/efemerides?foco=mensagem" class="px-3 py-2 text-sm rounded <?= $focoTela === 'mensagem' ? 'bg-blue-700 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' ?>">Revisar mensagem</a>
                <a href="/chancelaria/efemerides?foco=dados" class="px-3 py-2 text-sm rounded <?= $focoTela === 'dados' ? 'bg-emerald-700 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' ?>">Corrigir dados</a>
                <a href="/chancelaria/efemerides" class="px-3 py-2 text-sm rounded bg-slate-100 text-slate-700 hover:bg-slate-200">Ver tudo</a>
            </div>
            <p class="mt-3 text-sm text-gray-600">
                <strong>Revisar mensagem:</strong> ajusta apenas o texto que sera enviado hoje.
                <strong class="ml-2">Corrigir dados:</strong> ajusta nome, data, vinculo, local e demais registros no banco.
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <section id="secao-mensagem" class="rounded-3xl border bg-white p-5 shadow-sm <?= $focoTela === 'mensagem' ? 'border-blue-400 ring-2 ring-blue-100' : 'border-slate-200' ?>">
                <div class="flex items-center justify-between mb-2">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400">Mensagem do dia</p>
                        <h2 class="mt-2 text-xl font-semibold text-slate-900">Revisar mensagem do dia</h2>
                    </div>
                    <span class="text-xs text-gray-500">Gerada automaticamente apos 00:01</span>
                </div>
                <p class="mb-3 text-sm text-gray-600">Altera apenas a previa que sera enviada hoje. Nao corrige o banco de dados.</p>
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
                        <button type="submit" class="px-3 py-2 text-sm rounded bg-gray-800 text-white hover:bg-gray-900">Salvar mensagem</button>
                        <button type="button" onclick="copiarPreview()" class="px-3 py-2 text-sm rounded bg-blue-700 text-white hover:bg-blue-800">Copiar texto</button>
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
                <p class="mt-2 text-xs text-gray-500">Sugestao de uso: revisar, enviar no privado e, depois da conferencia, publicar no grupo.</p>
            </section>

            <section id="secao-dados" class="rounded-3xl border bg-white p-5 shadow-sm <?= $focoTela === 'dados' ? 'border-emerald-400 ring-2 ring-emerald-100' : 'border-slate-200' ?>">
                <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400">Cadastro de efemerides</p>
                <h2 class="mt-2 text-xl font-semibold text-slate-900">Atualizar registros da Loja</h2>
                <p class="mb-3 text-sm text-slate-600">Os dados salvos aqui passam a valer tambem para os proximos envios.</p>
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
                        <label class="text-sm block mb-1">Vinculo</label>
                        <select name="vinculo" class="w-full border border-gray-300 rounded px-3 py-2">
                            <option value="">Sem vinculo</option>
                            <?php foreach ($vinculosPadrao as $itemVinculo): ?>
                                <?php $nomeVinculo = trim((string) ($itemVinculo['nome'] ?? '')); ?>
                                <?php if ($nomeVinculo === '') { continue; } ?>
                                <option value="<?= htmlspecialchars($nomeVinculo) ?>"><?= htmlspecialchars($nomeVinculo) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Tratamento no texto é automático (sobrinho, sobrinha, cunhada).</p>
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
                        <a href="/chancelaria/certificado" class="px-4 py-2 rounded bg-cobalto text-white hover:bg-blue-800">Emitir certificado</a>
                    </div>
                </form>
            </section>
        </div>

        <section class="mt-6 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400">Consulta de registros</p>
            <h2 class="mt-2 text-xl font-semibold text-slate-900">Buscar e atualizar registros</h2>
            <p class="mb-4 text-sm text-slate-600">Escolha um irmao para ver apenas os registros dele. Se preferir, voce tambem pode abrir a lista completa.</p>

            <div class="mb-4 grid gap-3 md:grid-cols-[1.8fr_1.5fr_1fr_1fr_auto]">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400">Modo recomendado</p>
                    <p class="mt-2 text-sm text-slate-700">Selecione um irmao e veja abaixo apenas os registros vinculados a ele.</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400">Lista completa</p>
                    <p class="mt-2 text-sm text-slate-700"><?= $modoListaCompleta ? 'Ativa no momento.' : 'Use "Limpar filtros" para voltar a ver todos os registros.' ?></p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400">Registros</p>
                    <p class="mt-2 text-lg font-semibold text-slate-900"><?= count($registrosRecentes ?? []) ?></p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400">Filtro atual</p>
                    <p class="mt-2 text-sm text-slate-700"><?= htmlspecialchars((string) (($filtroIrmaoRef ?? '') !== '' ? ($filtroIrmaoRef ?? '') : 'Todos os irmaos')) ?></p>
                </div>
                <a href="/chancelaria/efemerides" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 hover:bg-slate-50">Ver lista completa</a>
            </div>

            <form method="GET" action="/chancelaria/efemerides" class="grid grid-cols-1 gap-3 mb-5 md:grid-cols-[2fr_2fr_1fr_1fr_auto]">
                <div>
                    <label class="text-sm block mb-1">Selecionar irmao</label>
                    <select name="irmao_ref" class="w-full border border-gray-300 rounded px-3 py-2">
                        <option value="">Todos os irmaos</option>
                        <?php foreach (($obreirosFiltro ?? []) as $obreiroFiltro): ?>
                            <?php
                                $nomeFiltro = trim((string) ($obreiroFiltro['nome_historico'] ?? $obreiroFiltro['nome'] ?? ''));
                                if ($nomeFiltro === '') { continue; }
                            ?>
                            <option value="<?= htmlspecialchars($nomeFiltro) ?>" <?= (($filtroIrmaoRef ?? '') === $nomeFiltro) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($nomeFiltro) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="text-sm block mb-1">Pesquisar por irmao, nome ou vinculo</label>
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
                    <label class="text-sm block mb-1">Vinculo</label>
                    <select name="vinculo" class="w-full border border-gray-300 rounded px-3 py-2">
                        <option value="">Todos</option>
                        <?php foreach ($vinculosPadrao as $itemVinculo): ?>
                            <?php $nomeVinculo = trim((string) ($itemVinculo['nome'] ?? '')); ?>
                            <?php if ($nomeVinculo === '') { continue; } ?>
                            <option value="<?= htmlspecialchars($nomeVinculo) ?>" <?= (($filtroVinculo ?? '') === $nomeVinculo) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($nomeVinculo) ?>
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
                <div class="flex items-end gap-2">
                    <button type="submit" class="px-4 py-2 rounded bg-blue-700 text-white hover:bg-blue-800">Filtrar</button>
                    <a href="/chancelaria/efemerides" class="px-4 py-2 rounded bg-gray-200 text-gray-700 hover:bg-gray-300">Limpar filtros</a>
                </div>
            </form>

            <details class="mb-5 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <summary class="cursor-pointer text-sm font-medium text-slate-700">Filtrar por data</summary>
                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    <div>
                        <label class="text-sm block mb-1">Data inicial</label>
                        <input form="filtro-avancado-efemerides" type="date" name="data_ini" value="<?= htmlspecialchars($filtroDataIni ?? '') ?>" class="w-full border border-gray-300 rounded px-3 py-2">
                    </div>
                    <div>
                        <label class="text-sm block mb-1">Data final</label>
                        <input form="filtro-avancado-efemerides" type="date" name="data_fim" value="<?= htmlspecialchars($filtroDataFim ?? '') ?>" class="w-full border border-gray-300 rounded px-3 py-2">
                    </div>
                </div>
                <form id="filtro-avancado-efemerides" method="GET" action="/chancelaria/efemerides" class="mt-4 flex flex-wrap gap-2">
                    <input type="hidden" name="irmao_ref" value="<?= htmlspecialchars((string) ($filtroIrmaoRef ?? '')) ?>">
                    <input type="hidden" name="termo" value="<?= htmlspecialchars((string) ($filtroTermo ?? '')) ?>">
                    <input type="hidden" name="tipo" value="<?= htmlspecialchars((string) ($filtroTipo ?? '')) ?>">
                    <input type="hidden" name="vinculo" value="<?= htmlspecialchars((string) ($filtroVinculo ?? '')) ?>">
                    <input type="hidden" name="ativo" value="<?= htmlspecialchars((string) ($filtroAtivo ?? '1')) ?>">
                    <button type="submit" class="rounded bg-slate-900 px-4 py-2 text-sm text-white hover:bg-slate-800">Aplicar datas</button>
                </form>
            </details>

            <div class="space-y-3 md:hidden">
                <?php foreach (($registrosRecentes ?? []) as $r): ?>
                    <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="text-base font-semibold text-slate-900"><?= htmlspecialchars((string) ($r['nome'] ?? '')) ?></div>
                                <div class="mt-1 text-sm text-slate-600"><?= htmlspecialchars((string) ($r['tipo'] ?? '')) ?> • <?= htmlspecialchars($formatarDataVisual((string) ($r['data_evento'] ?? ''))) ?></div>
                            </div>
                            <span class="rounded-full px-3 py-1 text-xs font-semibold <?= !empty($r['ativo']) ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-200 text-slate-700' ?>">
                                <?= !empty($r['ativo']) ? 'Ativo' : 'Inativo' ?>
                            </span>
                        </div>
                        <div class="mt-3 text-sm text-slate-600">
                            <div>Vinculo: <?= htmlspecialchars((string) ($r['vinculo'] ?? '-')) ?></div>
                            <div>Parentesco: <?= htmlspecialchars((string) ($r['parentesco'] ?? '-')) ?></div>
                        </div>
                        <?php if (!empty($r['ativo'])): ?>
                            <details class="mt-4 rounded-2xl border border-slate-200 bg-white p-3">
                                <summary class="cursor-pointer text-sm font-medium text-blue-700">Editar registro</summary>
                                <form method="POST" action="/chancelaria/efemerides/atualizar" class="mt-3 space-y-2">
                                    <input type="hidden" name="registro_id" value="<?= (int) ($r['id'] ?? 0) ?>">
                                    <input type="text" name="nome" value="<?= htmlspecialchars((string) ($r['nome'] ?? '')) ?>" class="w-full rounded border border-gray-300 px-3 py-2" required>
                                    <select name="tipo" class="w-full rounded border border-gray-300 px-3 py-2" required>
                                        <?php foreach (($tiposEfemeride ?? []) as $tipoOpcao): ?>
                                            <option value="<?= htmlspecialchars($tipoOpcao) ?>" <?= (($r['tipo'] ?? '') === $tipoOpcao) ? 'selected' : '' ?>><?= htmlspecialchars($tipoOpcao) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="date" name="data_evento" value="<?= htmlspecialchars((string) ($r['data_evento'] ?? '')) ?>" class="w-full rounded border border-gray-300 px-3 py-2" required>
                                    <input type="text" name="parentesco" value="<?= htmlspecialchars((string) ($r['parentesco'] ?? '')) ?>" class="w-full rounded border border-gray-300 px-3 py-2" placeholder="Nome do irmao relacionado">
                                    <input type="text" name="local" value="<?= htmlspecialchars((string) ($r['local'] ?? '')) ?>" class="w-full rounded border border-gray-300 px-3 py-2" placeholder="Ex.: Loja Renascenca n 270">
                                    <textarea name="mensagem_custom" rows="2" class="w-full rounded border border-gray-300 px-3 py-2" placeholder="Texto customizado para o envio"><?= htmlspecialchars((string) ($r['mensagem_custom'] ?? '')) ?></textarea>
                                    <div class="flex gap-2">
                                        <button type="submit" class="flex-1 rounded bg-blue-700 px-3 py-2 text-sm text-white hover:bg-blue-800">Salvar</button>
                                        <button type="submit" formaction="/chancelaria/efemerides/desativar" name="id" value="<?= (int) ($r['id'] ?? 0) ?>" class="flex-1 rounded bg-slate-200 px-3 py-2 text-sm text-slate-700 hover:bg-slate-300">Desativar</button>
                                    </div>
                                </form>
                            </details>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="hidden overflow-x-auto md:block">
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
                            <td class="px-3 py-2"><?= htmlspecialchars($formatarDataVisual((string) ($r['data_evento'] ?? ''))) ?></td>
                            <td class="px-3 py-2"><?= htmlspecialchars((string) ($r['nome'] ?? '')) ?></td>
                            <td class="px-3 py-2"><?= htmlspecialchars((string) ($r['tipo'] ?? '')) ?></td>
                            <td class="px-3 py-2">
                                <div><?= htmlspecialchars((string) ($r['vinculo'] ?? '-')) ?></div>
                                <div class="text-xs text-gray-500"><?= htmlspecialchars((string) ($r['parentesco'] ?? '-')) ?></div>
                            </td>
                            <td class="px-3 py-2"><?= !empty($r['ativo']) ? 'Ativo' : 'Inativo' ?></td>
                            <td class="px-3 py-2">
                                <?php if (!empty($r['ativo'])): ?>
                                    <details class="rounded-xl border border-gray-200 bg-gray-50 p-3">
                                        <summary class="cursor-pointer text-sm font-medium text-blue-700">Editar</summary>
                                        <form method="POST" action="/chancelaria/efemerides/atualizar" class="mt-3 space-y-2">
                                            <input type="hidden" name="registro_id" value="<?= (int) ($r['id'] ?? 0) ?>">
                                            <div>
                                                <label class="block text-xs text-gray-600 mb-1">Nome</label>
                                                <input type="text" name="nome" value="<?= htmlspecialchars((string) ($r['nome'] ?? '')) ?>" class="w-full border border-gray-300 rounded px-2 py-1" required>
                                            </div>
                                            <div>
                                                <label class="block text-xs text-gray-600 mb-1">Tipo de evento</label>
                                                <select name="tipo" class="w-full border border-gray-300 rounded px-2 py-1" required>
                                                <?php foreach (($tiposEfemeride ?? []) as $tipoOpcao): ?>
                                                    <option value="<?= htmlspecialchars($tipoOpcao) ?>" <?= (($r['tipo'] ?? '') === $tipoOpcao) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($tipoOpcao) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-xs text-gray-600 mb-1">Data do evento</label>
                                                <input type="date" name="data_evento" value="<?= htmlspecialchars((string) ($r['data_evento'] ?? '')) ?>" class="w-full border border-gray-300 rounded px-2 py-1" required>
                                            </div>
                                            <div>
                                                <label class="block text-xs text-gray-600 mb-1">Vinculo</label>
                                                <select name="vinculo" class="w-full border border-gray-300 rounded px-2 py-1">
                                                    <option value="">Sem vinculo</option>
                                                    <?php foreach ($vinculosPadrao as $itemVinculo): ?>
                                                        <?php $nomeVinculo = trim((string) ($itemVinculo['nome'] ?? '')); ?>
                                                        <?php if ($nomeVinculo === '') { continue; } ?>
                                                        <option value="<?= htmlspecialchars($nomeVinculo) ?>" <?= (strcasecmp((string) ($r['vinculo'] ?? ''), $nomeVinculo) === 0) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($nomeVinculo) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-xs text-gray-600 mb-1">Irmao relacionado (parentesco)</label>
                                                <input type="text" name="parentesco" value="<?= htmlspecialchars((string) ($r['parentesco'] ?? '')) ?>" class="w-full border border-gray-300 rounded px-2 py-1" placeholder="Nome do irmao relacionado">
                                            </div>
                                            <div>
                                                <label class="block text-xs text-gray-600 mb-1">Local/Loja</label>
                                                <input type="text" name="local" value="<?= htmlspecialchars((string) ($r['local'] ?? '')) ?>" class="w-full border border-gray-300 rounded px-2 py-1" placeholder="Ex.: Loja Renascenca n 270">
                                            </div>
                                            <div>
                                                <label class="block text-xs text-gray-600 mb-1">Mensagem custom (opcional)</label>
                                                <textarea name="mensagem_custom" rows="2" class="w-full border border-gray-300 rounded px-2 py-1" placeholder="Texto customizado para o envio"><?= htmlspecialchars((string) ($r['mensagem_custom'] ?? '')) ?></textarea>
                                            </div>
                                            <div class="flex gap-2">
                                                <button type="submit" class="flex-1 rounded bg-blue-700 px-3 py-2 text-sm text-white hover:bg-blue-800">Salvar edicao</button>
                                                <button type="submit" formaction="/chancelaria/efemerides/desativar" name="id" value="<?= (int) ($r['id'] ?? 0) ?>" class="flex-1 rounded bg-slate-200 px-3 py-2 text-sm text-slate-700 hover:bg-slate-300">Desativar</button>
                                            </div>
                                        </form>
                                    </details>
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
            alert('Texto copiado para a area de transferencia.');
        }

        (function () {
            const foco = <?= json_encode($focoTela) ?>;
            if (foco === 'mensagem') {
                document.getElementById('secao-mensagem')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
            if (foco === 'dados') {
                document.getElementById('secao-dados')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        })();
    </script>
</body>
</html>
