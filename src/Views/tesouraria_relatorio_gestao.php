<?php
$rotulosBloco = [
    'receitas_ordinarias' => 'Entradas ordinÃ¡rias',
    'receitas_eventuais' => 'Entradas eventuais',
    'receitas_financeiras' => 'Entradas financeiras',
    'capitacoes' => 'CapitaÃ§Ãµes',
    'agapes_eventos' => 'Ãgapes e eventos',
    'despesas_potencia' => 'Saidas com a PotÃªncia',
    'despesas_administrativas' => 'Saidas administrativas',
    'despesas_bancarias' => 'Saidas bancÃ¡rias',
    'despesas_ritualisticas' => 'Saidas ritualÃ­sticas',
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
    <title>RelatÃ³rio Tesouraria da GestÃ£o</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media (min-width: 1440px) {
            .erp-readable {
                font-size: 1.08rem;
            }
            .erp-readable .text-xs,
            .erp-readable .text-\[11px\] {
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
<body class="erp-readable min-h-screen bg-[linear-gradient(180deg,#f7f3ea_0%,#eef2f6_100%)] text-slate-800">
    <main class="mx-auto max-w-7xl px-4 py-8">
        <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between mb-8">
            <div>
                <p class="text-xs uppercase tracking-[0.25em] text-slate-700">Tesouraria</p>
                <h1 class="text-3xl font-semibold text-slate-900">RelatÃ³rio financeiro da gestÃ£o</h1>
                <p class="mt-2 text-sm text-slate-700">ConsolidaÃ§Ã£o por perÃ­odo de gestÃ£o para leitura administrativa, prestaÃ§Ã£o de contas e relatÃ³rio final.</p>
            </div>
            <div class="flex gap-2">
                <a href="/tesouraria/caixa" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium">Caixa da Loja</a>
                <a href="/dashboard" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white">Painel</a>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm mb-6">
            <form method="GET" action="/tesouraria/relatorio-gestao" class="grid gap-4 md:grid-cols-[1fr_1fr_auto]">
                <div>
                    <label class="block text-sm font-medium mb-1">GestÃ£o</label>
                    <select name="gestao_id" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        <?php foreach ($gestoes as $gestao): ?>
                            <option value="<?= (int) $gestao['id'] ?>" <?= (int) $gestao['id'] === (int) ($relatorio['gestao']['id'] ?? 0) ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string) ($gestao['titulo'] ?? 'GestÃ£o')) ?>
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
                <div class="text-sm text-slate-700">Saldo inicial</div>
                <div class="mt-2 text-3xl font-semibold text-slate-900"><?= htmlspecialchars($formatarMoeda($relatorio['saldo_inicial'] ?? 0)) ?></div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm text-slate-700">Entradas</div>
                <div class="mt-2 text-3xl font-semibold text-emerald-700"><?= htmlspecialchars($formatarMoeda($relatorio['totais']['entradas'] ?? 0)) ?></div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm text-slate-700">Saidas</div>
                <div class="mt-2 text-3xl font-semibold text-rose-700"><?= htmlspecialchars($formatarMoeda($relatorio['totais']['saidas'] ?? 0)) ?></div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm text-slate-700">Saldo final</div>
                <div class="mt-2 text-3xl font-semibold text-slate-900"><?= htmlspecialchars($formatarMoeda($relatorio['saldo_final'] ?? 0)) ?></div>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
            <section class="space-y-6">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-semibold text-slate-900">SÃ­ntese por blocos</h2>
                    <p class="mt-1 text-sm text-slate-700">Agrupamentos pensados para o relatÃ³rio da gestÃ£o, incluindo mensalidades, captaÃ§Ãµes, Ã¡gaes, despesas da PotÃªncia e entidades auxiliadas.</p>
                    <div class="mt-5 space-y-3">
                        <?php foreach (($relatorio['blocos'] ?? []) as $bloco => $totais): ?>
                            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <div class="font-medium text-slate-900"><?= htmlspecialchars($rotulosBloco[$bloco] ?? $bloco) ?></div>
                                <div class="mt-2 grid gap-2 sm:grid-cols-2 text-sm">
                                    <div class="flex items-center justify-between"><span>Entradas</span><strong class="text-emerald-700"><?= htmlspecialchars($formatarMoeda($totais['entrada'] ?? 0)) ?></strong></div>
                                    <div class="flex items-center justify-between"><span>SaÃ­das</span><strong class="text-rose-700"><?= htmlspecialchars($formatarMoeda($totais['saida'] ?? 0)) ?></strong></div>
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
                                    <div class="text-xs text-slate-700"><?= htmlspecialchars($rotulosBloco[(string) ($linha['bloco_relatorio'] ?? 'outros')] ?? (string) ($linha['bloco_relatorio'] ?? 'outros')) ?> â€¢ <?= htmlspecialchars((string) ($linha['tipo'] ?? '')) ?></div>
                                </div>
                                <div class="text-right">
                                    <div class="font-semibold"><?= htmlspecialchars($formatarMoeda($linha['total'] ?? 0)) ?></div>
                                    <div class="text-xs text-slate-700"><?= (int) ($linha['quantidade'] ?? 0) ?> lanÃ§amento(s)</div>
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
                            <div class="text-sm text-slate-700">Entradas do tronco</div>
                            <div class="mt-2 text-2xl font-semibold text-emerald-700"><?= htmlspecialchars($formatarMoeda($relatorio['tronco']['entradas'] ?? 0)) ?></div>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="text-sm text-slate-700">SaÃ­das do tronco</div>
                            <div class="mt-2 text-2xl font-semibold text-rose-700"><?= htmlspecialchars($formatarMoeda($relatorio['tronco']['saidas'] ?? 0)) ?></div>
                        </div>
                    </div>
                    <div class="mt-5 space-y-2">
                        <?php foreach (($relatorio['entidades_auxiliadas'] ?? []) as $entidade): ?>
                            <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                                <span><?= htmlspecialchars((string) ($entidade['entidade'] ?? 'NÃ£o informada')) ?></span>
                                <strong><?= htmlspecialchars($formatarMoeda($entidade['total'] ?? 0)) ?></strong>
                            </div>
                        <?php endforeach; ?>
                        <?php if (($relatorio['entidades_auxiliadas'] ?? []) === []): ?>
                            <div class="rounded-xl border border-dashed border-slate-300 px-4 py-4 text-sm text-slate-700">Ainda nÃ£o hÃ¡ entidades auxiliadas registradas no perÃ­odo.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-semibold text-slate-900">LanÃ§amentos recentes do perÃ­odo</h2>
                    <div class="mt-5 space-y-3">
                        <?php foreach (($relatorio['lancamentos'] ?? []) as $lancamento): ?>
                            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <div class="font-medium text-slate-900"><?= htmlspecialchars((string) ($lancamento['categoria_nome'] ?? '-')) ?></div>
                                        <div class="text-sm text-slate-700 mt-1"><?= htmlspecialchars((string) ($lancamento['descricao'] ?? 'Sem descricao')) ?></div>
                                        <div class="text-xs text-slate-700 mt-1">
                                            <?= htmlspecialchars((string) ($lancamento['data_lancamento'] ?? '-')) ?>
                                            <?php if (!empty($lancamento['obreiro_nome'])): ?> â€¢ <?= htmlspecialchars((string) $lancamento['obreiro_nome']) ?><?php endif; ?>
                                            <?php if (!empty($lancamento['entidade_auxiliada'])): ?> â€¢ <?= htmlspecialchars((string) $lancamento['entidade_auxiliada']) ?><?php endif; ?>
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


