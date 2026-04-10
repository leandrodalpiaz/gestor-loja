<?php
$rotulosBloco = [
    'receitas_ordinarias' => 'Receitas ordinárias',
    'receitas_eventuais' => 'Receitas eventuais',
    'receitas_financeiras' => 'Receitas financeiras',
    'capitacoes' => 'Capitações',
    'agapes_eventos' => 'Ágapes e eventos',
    'despesas_potencia' => 'Despesas com a Potência',
    'despesas_administrativas' => 'Despesas administrativas',
    'despesas_bancarias' => 'Despesas bancárias',
    'despesas_ritualisticas' => 'Despesas ritualísticas',
    'tronco' => 'Tronco de solidariedade',
    'entidades_auxiliadas' => 'Entidades auxiliadas',
    'outros' => 'Outros',
];

$formatarMoeda = static fn ($valor) => 'R$ ' . number_format((float) $valor, 2, ',', '.');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório Financeiro da Gestão</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-[linear-gradient(180deg,#f7f3ea_0%,#eef2f6_100%)] text-slate-800">
    <main class="mx-auto max-w-7xl px-4 py-8">
        <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between mb-8">
            <div>
                <p class="text-xs uppercase tracking-[0.25em] text-slate-500">Tesouraria</p>
                <h1 class="text-3xl font-semibold text-slate-900">Relatório financeiro da gestão</h1>
                <p class="mt-2 text-sm text-slate-600">Consolidação por período de gestão para leitura administrativa, prestação de contas e relatório final.</p>
            </div>
            <div class="flex gap-2">
                <a href="/tesouraria/caixa" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium">Livro-caixa</a>
                <a href="/dashboard" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white">Painel</a>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm mb-6">
            <form method="GET" action="/tesouraria/relatorio-gestao" class="grid gap-4 md:grid-cols-[1fr_1fr_auto]">
                <div>
                    <label class="block text-sm font-medium mb-1">Gestão</label>
                    <select name="gestao_id" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        <?php foreach ($gestoes as $gestao): ?>
                            <option value="<?= (int) $gestao['id'] ?>" <?= (int) $gestao['id'] === (int) ($relatorio['gestao']['id'] ?? 0) ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string) ($gestao['titulo'] ?? 'Gestão')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Data final informada</label>
                    <input type="date" name="encerramento_em" value="<?= htmlspecialchars((string) ($relatorio['periodo']['fim_data'] ?? '')) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white">Atualizar</button>
                </div>
            </form>
        </div>

        <div class="grid gap-4 md:grid-cols-4 mb-8">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm text-slate-500">Saldo inicial</div>
                <div class="mt-2 text-3xl font-semibold text-slate-900"><?= htmlspecialchars($formatarMoeda($relatorio['saldo_inicial'] ?? 0)) ?></div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm text-slate-500">Receitas</div>
                <div class="mt-2 text-3xl font-semibold text-emerald-700"><?= htmlspecialchars($formatarMoeda($relatorio['totais']['entradas'] ?? 0)) ?></div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm text-slate-500">Despesas</div>
                <div class="mt-2 text-3xl font-semibold text-rose-700"><?= htmlspecialchars($formatarMoeda($relatorio['totais']['saidas'] ?? 0)) ?></div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm text-slate-500">Saldo final</div>
                <div class="mt-2 text-3xl font-semibold text-slate-900"><?= htmlspecialchars($formatarMoeda($relatorio['saldo_final'] ?? 0)) ?></div>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
            <section class="space-y-6">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-semibold text-slate-900">Síntese por blocos</h2>
                    <p class="mt-1 text-sm text-slate-600">Agrupamentos pensados para o relatório da gestão, incluindo mensalidades, captações, ágaes, despesas da Potência e entidades auxiliadas.</p>
                    <div class="mt-5 space-y-3">
                        <?php foreach (($relatorio['blocos'] ?? []) as $bloco => $totais): ?>
                            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <div class="font-medium text-slate-900"><?= htmlspecialchars($rotulosBloco[$bloco] ?? $bloco) ?></div>
                                <div class="mt-2 grid gap-2 sm:grid-cols-2 text-sm">
                                    <div class="flex items-center justify-between"><span>Entradas</span><strong class="text-emerald-700"><?= htmlspecialchars($formatarMoeda($totais['entrada'] ?? 0)) ?></strong></div>
                                    <div class="flex items-center justify-between"><span>Saídas</span><strong class="text-rose-700"><?= htmlspecialchars($formatarMoeda($totais['saida'] ?? 0)) ?></strong></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-semibold text-slate-900">Categorias consolidadas</h2>
                    <div class="mt-5 space-y-2">
                        <?php foreach (($relatorio['categorias'] ?? []) as $linha): ?>
                            <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm gap-4">
                                <div>
                                    <div class="font-medium text-slate-900"><?= htmlspecialchars((string) ($linha['nome'] ?? '-')) ?></div>
                                    <div class="text-xs text-slate-500"><?= htmlspecialchars($rotulosBloco[(string) ($linha['bloco_relatorio'] ?? 'outros')] ?? (string) ($linha['bloco_relatorio'] ?? 'outros')) ?> • <?= htmlspecialchars((string) ($linha['tipo'] ?? '')) ?></div>
                                </div>
                                <div class="text-right">
                                    <div class="font-semibold"><?= htmlspecialchars($formatarMoeda($linha['total'] ?? 0)) ?></div>
                                    <div class="text-xs text-slate-500"><?= (int) ($linha['quantidade'] ?? 0) ?> lançamento(s)</div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <section class="space-y-6">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-semibold text-slate-900">Tronco e entidades auxiliadas</h2>
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="text-sm text-slate-500">Entradas do tronco</div>
                            <div class="mt-2 text-2xl font-semibold text-emerald-700"><?= htmlspecialchars($formatarMoeda($relatorio['tronco']['entradas'] ?? 0)) ?></div>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="text-sm text-slate-500">Saídas do tronco</div>
                            <div class="mt-2 text-2xl font-semibold text-rose-700"><?= htmlspecialchars($formatarMoeda($relatorio['tronco']['saidas'] ?? 0)) ?></div>
                        </div>
                    </div>
                    <div class="mt-5 space-y-2">
                        <?php foreach (($relatorio['entidades_auxiliadas'] ?? []) as $entidade): ?>
                            <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                                <span><?= htmlspecialchars((string) ($entidade['entidade'] ?? 'Nao informada')) ?></span>
                                <strong><?= htmlspecialchars($formatarMoeda($entidade['total'] ?? 0)) ?></strong>
                            </div>
                        <?php endforeach; ?>
                        <?php if (($relatorio['entidades_auxiliadas'] ?? []) === []): ?>
                            <div class="rounded-xl border border-dashed border-slate-300 px-4 py-4 text-sm text-slate-500">Ainda nao ha entidades auxiliadas registradas no periodo.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-semibold text-slate-900">Lançamentos recentes do período</h2>
                    <div class="mt-5 space-y-3">
                        <?php foreach (($relatorio['lancamentos'] ?? []) as $lancamento): ?>
                            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <div class="font-medium text-slate-900"><?= htmlspecialchars((string) ($lancamento['categoria_nome'] ?? '-')) ?></div>
                                        <div class="text-sm text-slate-600 mt-1"><?= htmlspecialchars((string) ($lancamento['descricao'] ?? 'Sem descricao')) ?></div>
                                        <div class="text-xs text-slate-500 mt-1">
                                            <?= htmlspecialchars((string) ($lancamento['data_lancamento'] ?? '-')) ?>
                                            <?php if (!empty($lancamento['obreiro_nome'])): ?> • <?= htmlspecialchars((string) $lancamento['obreiro_nome']) ?><?php endif; ?>
                                            <?php if (!empty($lancamento['entidade_auxiliada'])): ?> • <?= htmlspecialchars((string) $lancamento['entidade_auxiliada']) ?><?php endif; ?>
                                        </div>
                                    </div>
                                    <strong class="<?= ($lancamento['tipo'] ?? '') === 'entrada' ? 'text-emerald-700' : 'text-rose-700' ?>">
                                        <?= htmlspecialchars($formatarMoeda($lancamento['valor'] ?? 0)) ?>
                                    </strong>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        </div>
    </main>
</body>
</html>
