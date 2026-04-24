<?php
$mensagemSucesso = $_SESSION['mensagem_sucesso'] ?? null;
$mensagemErro = $_SESSION['mensagem_erro'] ?? null;
unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospitalaria - Gestor da Loja</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        cobalto: '#17345c',
                        marfim: '#f7f4ec',
                        cobre: '#9d6f34',
                        grafite: '#2f3a49'
                    },
                    fontFamily: {
                        display: ['"Merriweather"', 'serif'],
                        sans: ['"Inter"', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Merriweather:wght@700&display=swap" rel="stylesheet">
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
<body class="erp-readable min-h-screen bg-marfim text-grafite font-sans">
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="mb-8 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-sm uppercase tracking-[0.24em] text-cobre">Hospitalaria</p>
                <h1 class="font-display text-3xl text-cobalto">Painel do Mestre Hospitaleiro</h1>
                <p class="mt-2 text-sm text-slate-700">Ocorrências assistenciais, visitas, retornos e encaminhamentos ao Venerável e à Tesouraria.</p>
            </div>
            <div class="flex gap-3">
                <a href="/dashboard" class="rounded-lg bg-cobalto px-4 py-2 text-sm font-medium text-white">Voltar ao painel</a>
                <a href="/miniapp/hospitaleiro" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium">Abrir miniapp</a>
            </div>
        </div>

        <?php if ($mensagemSucesso): ?>
            <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-700"><?= htmlspecialchars($mensagemSucesso) ?></div>
        <?php endif; ?>
        <?php if ($mensagemErro): ?>
            <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-rose-700"><?= htmlspecialchars($mensagemErro) ?></div>
        <?php endif; ?>

        <?php
        $dashboard = [
            'title' => 'Painel operacional do Hospitaleiro',
            'subtitle' => 'Assistência, visitas e status das ocorrências.',
            'meta' => ['Perfil: acompanhamento social', 'Encaminhamentos: Venerável/Tesouraria'],
            'actions' => [
                ['label' => 'Registrar ocorrência', 'href' => '/assistencia/ocorrencias/salvar'],
                ['label' => 'Atualizar status', 'href' => '/assistencia/ocorrencias/status'],
                ['label' => 'Registrar visita', 'href' => '/assistencia/ocorrencias/visita'],
            ],
            'blocks' => [
                ['title' => 'Ocorrências abertas', 'subtitle' => 'Leitura operacional das pendências.', 'span' => 'half', 'metrics' => [
                    ['label' => 'Abertas', 'value' => (string) ($resumo['abertas'] ?? 0)],
                    ['label' => 'Em acompanhamento', 'value' => (string) ($resumo['em_acompanhamento'] ?? 0)],
                ], 'list' => array_map(static fn (array $o): array => ['item' => (string) ($o['obreiro_nome'] ?? 'Ocorrência'), 'meta' => (string) ($o['tipo_ocorrencia'] ?? 'assistencia_geral'), 'status' => (string) ($o['status'] ?? 'aberta')], array_slice($ocorrencias, 0, 4))],
                ['title' => 'Visitas e atividade', 'subtitle' => 'Retornos e registro recente.', 'span' => 'half', 'metrics' => [
                    ['label' => 'Pendências de visita', 'value' => (string) count($pendenciasVisita)],
                    ['label' => 'Com apoio financeiro', 'value' => (string) ($resumo['com_apoio_financeiro'] ?? 0)],
                ], 'list' => array_map(static fn (array $o): array => ['item' => (string) ($o['obreiro_nome'] ?? 'Ocorrência'), 'meta' => 'Prioridade ' . (string) ($o['prioridade'] ?? 'media'), 'status' => (string) ($o['status'] ?? '-')], array_slice($pendenciasVisita, 0, 4))],
            ],
            'alerts' => [['title' => 'Pendências sociais', 'text' => 'Priorizar ocorrências abertas com visita pendente.', 'tone' => 'warning']],
            'activity' => array_map(static fn (array $o): array => ['item' => (string) ($o['tipo_ocorrencia'] ?? 'assistencia_geral'), 'meta' => (string) ($o['obreiro_nome'] ?? 'Sem obreiro')], array_slice($ocorrencias, 0, 5)),
            'links' => [['label' => 'Miniapp Hospitaleiro', 'href' => '/miniapp/hospitaleiro']],
        ];
        $dashboardRenderers = [
            static function (array $block): void { $dashboardMetrics = $block['metrics'] ?? []; $dashboardListItems = $block['list'] ?? []; require __DIR__ . '/../components/dashboard_metrics.php'; echo '<div class="mt-3">'; require __DIR__ . '/../components/dashboard_list.php'; echo '</div>'; },
            static function (array $block): void { $dashboardMetrics = $block['metrics'] ?? []; $dashboardListItems = $block['list'] ?? []; require __DIR__ . '/../components/dashboard_metrics.php'; echo '<div class="mt-3">'; require __DIR__ . '/../components/dashboard_list.php'; echo '</div>'; },
        ];
        require __DIR__ . '/../layouts/dashboard.php';
        ?>

        <div class="mb-8 grid gap-4 md:grid-cols-5">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm text-slate-700">Total de ocorrências</div>
                <div class="mt-2 text-3xl font-semibold text-cobalto"><?= (int) ($resumo['total'] ?? 0) ?></div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm text-slate-700">Abertas</div>
                <div class="mt-2 text-3xl font-semibold text-cobalto"><?= (int) ($resumo['abertas'] ?? 0) ?></div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm text-slate-700">Em acompanhamento</div>
                <div class="mt-2 text-3xl font-semibold text-cobalto"><?= (int) ($resumo['em_acompanhamento'] ?? 0) ?></div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm text-slate-700">Concluídas</div>
                <div class="mt-2 text-3xl font-semibold text-cobalto"><?= (int) ($resumo['concluidas'] ?? 0) ?></div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm text-slate-700">Com apoio financeiro</div>
                <div class="mt-2 text-3xl font-semibold text-cobalto"><?= (int) ($resumo['com_apoio_financeiro'] ?? 0) ?></div>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
            <section class="space-y-6">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="font-display text-xl text-cobalto">Pendências de visita e retorno</h2>
                    <p class="mb-4 text-sm text-slate-700">Ocorrências que pedem presença em campo ou acompanhamento ativo.</p>
                    <div class="space-y-3">
                        <?php foreach ($pendenciasVisita as $ocorrencia): ?>
                            <article class="rounded-xl border border-slate-200 p-4">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <div class="font-semibold text-cobalto"><?= htmlspecialchars((string) ($ocorrencia['obreiro_nome'] ?? 'Sem obreiro vinculado')) ?></div>
                                        <div class="mt-1 text-sm text-slate-700"><?= htmlspecialchars((string) ($ocorrencia['tipo_ocorrencia'] ?? 'assistencia_geral')) ?> · Prioridade <?= htmlspecialchars((string) ($ocorrencia['prioridade'] ?? 'media')) ?></div>
                                    </div>
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700"><?= htmlspecialchars((string) ($ocorrencia['status'] ?? 'aberta')) ?></span>
                                </div>
                                <p class="mt-3 text-sm text-slate-700"><?= nl2br(htmlspecialchars((string) ($ocorrencia['descricao'] ?? ''))) ?></p>
                                <?php if ($podeOperarOcorrencias): ?>
                                    <form method="POST" action="/assistencia/ocorrencias/visita" class="mt-4 grid gap-3 md:grid-cols-[1fr_180px_auto]">
                                        <input type="hidden" name="ocorrencia_id" value="<?= (int) ($ocorrencia['id'] ?? 0) ?>">
                                        <input type="text" name="observacao_visita" placeholder="Observação da visita ou retorno" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
                                        <input type="date" name="data_proxima_acao" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
                                        <button type="submit" class="rounded-md border border-cobalto px-3 py-2 text-sm text-cobalto">Registrar visita</button>
                                    </form>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                        <?php if ($pendenciasVisita === []): ?>
                            <div class="rounded-xl border border-dashed border-slate-300 px-4 py-4 text-sm text-slate-700">Nenhuma pendência de visita no momento.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="font-display text-xl text-cobalto">Ocorrências assistenciais recentes</h2>
                    <p class="mb-4 text-sm text-slate-700">Fluxo assistencial de saude, nascimento, falecimento, solidariedade e apoio geral.</p>

                    <div class="space-y-3">
                        <?php foreach ($ocorrencias as $ocorrencia): ?>
                            <?php $status = (string) ($ocorrencia['status'] ?? 'aberta'); ?>
                            <article class="rounded-xl border border-slate-200 p-4">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <div class="font-semibold text-cobalto"><?= htmlspecialchars((string) ($ocorrencia['tipo_ocorrencia'] ?? 'assistencia_geral')) ?></div>
                                        <div class="mt-1 text-sm text-slate-700">
                                            <?= htmlspecialchars((string) ($ocorrencia['obreiro_nome'] ?? 'Sem obreiro vinculado')) ?>
                                            <?php if (!empty($ocorrencia['nome_familiar'])): ?>
                                                · Familiar: <?= htmlspecialchars((string) $ocorrencia['nome_familiar']) ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700"><?= htmlspecialchars($status) ?></span>
                                </div>
                                <p class="mt-3 text-sm text-slate-700"><?= nl2br(htmlspecialchars((string) ($ocorrencia['descricao'] ?? ''))) ?></p>
                                <div class="mt-3 grid gap-2 text-xs text-slate-700 md:grid-cols-2">
                                    <div>Prioridade: <?= htmlspecialchars((string) ($ocorrencia['prioridade'] ?? 'media')) ?></div>
                                    <div>Encaminhar para: <?= htmlspecialchars((string) ($ocorrencia['encaminhar_para'] ?? 'nenhum')) ?></div>
                                    <div>Data da ocorrência: <?= htmlspecialchars((string) ($ocorrencia['data_ocorrencia'] ?? '-')) ?></div>
                                    <div>Próxima ação: <?= htmlspecialchars((string) ($ocorrencia['data_proxima_acao'] ?? '-')) ?></div>
                                </div>

                                <?php if ($podeOperarOcorrencias || $podeTratarTesouraria): ?>
                                    <form method="POST" action="/assistencia/ocorrencias/status" class="mt-4 flex flex-wrap items-center gap-2">
                                        <input type="hidden" name="ocorrencia_id" value="<?= (int) ($ocorrencia['id'] ?? 0) ?>">
                                        <select name="status" class="rounded-md border border-slate-300 px-2 py-1 text-sm">
                                            <option value="aberta" <?= $status === 'aberta' ? 'selected' : '' ?>>Aberta</option>
                                            <option value="em_acompanhamento" <?= $status === 'em_acompanhamento' ? 'selected' : '' ?>>Em acompanhamento</option>
                                            <option value="concluida" <?= $status === 'concluida' ? 'selected' : '' ?>>Concluída</option>
                                            <option value="cancelada" <?= $status === 'cancelada' ? 'selected' : '' ?>>Cancelada</option>
                                        </select>
                                        <input type="text" name="observacao_status" placeholder="Observação de status" class="min-w-[220px] flex-1 rounded-md border border-slate-300 px-2 py-1 text-sm">
                                        <button type="submit" class="rounded-md border border-cobalto px-3 py-1.5 text-sm text-cobalto">Atualizar status</button>
                                    </form>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <section class="space-y-6">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="font-display text-xl text-cobalto">Nova ocorrência assistencial</h2>
                    <p class="mb-4 text-sm text-slate-700">Registro de ocorrência, visita, apoio financeiro e encaminhamento institucional.</p>

                    <?php if ($podeOperarOcorrencias): ?>
                        <form method="POST" action="/assistencia/ocorrencias/salvar" class="grid gap-4">
                            <div>
                                <label class="mb-1 block text-sm font-medium">Tipo de ocorrência</label>
                                <select name="tipo_ocorrencia" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                                    <option value="assistencia_geral">Assistência geral</option>
                                    <option value="saude">Saúde</option>
                                    <option value="nascimento">Nascimento</option>
                                    <option value="falecimento">Falecimento</option>
                                    <option value="solidariedade">Solidariedade</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium">Obreiro vinculado</label>
                                <select name="obreiro_id" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                                    <option value="">Não vincular obreiro</option>
                                    <?php foreach ($obreiros as $obreiro): ?>
                                        <option value="<?= htmlspecialchars((string) ($obreiro['id'] ?? '')) ?>">
                                            <?= htmlspecialchars((string) ($obreiro['nome_historico'] ?? $obreiro['nome'] ?? '')) ?> - CIM <?= htmlspecialchars((string) ($obreiro['cim'] ?? '-')) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="grid gap-3 md:grid-cols-2">
                                <input type="text" name="nome_familiar" placeholder="Nome do familiar" class="rounded-lg border border-slate-300 px-3 py-2">
                                <input type="text" name="parentesco" placeholder="Parentesco" class="rounded-lg border border-slate-300 px-3 py-2">
                            </div>
                            <div class="grid gap-3 md:grid-cols-2">
                                <select name="prioridade" class="rounded-lg border border-slate-300 px-3 py-2">
                                    <option value="media">Media</option>
                                    <option value="baixa">Baixa</option>
                                    <option value="alta">Alta</option>
                                    <option value="urgente">Urgente</option>
                                </select>
                                <select name="encaminhar_para" class="rounded-lg border border-slate-300 px-3 py-2">
                                    <option value="nenhum">Nenhum</option>
                                    <option value="veneravel">Venerável Mestre</option>
                                    <option value="tesoureiro">Tesoureiro</option>
                                    <option value="ambos">Venerável + Tesoureiro</option>
                                </select>
                            </div>
                            <div class="grid gap-3 md:grid-cols-2">
                                <input type="date" name="data_ocorrencia" class="rounded-lg border border-slate-300 px-3 py-2">
                                <input type="date" name="data_proxima_acao" class="rounded-lg border border-slate-300 px-3 py-2">
                            </div>
                            <div class="grid gap-3 md:grid-cols-2">
                                <input type="text" name="valor_solicitado" placeholder="Valor solicitado" class="rounded-lg border border-slate-300 px-3 py-2">
                                <input type="text" name="valor_aprovado" placeholder="Valor aprovado" class="rounded-lg border border-slate-300 px-3 py-2">
                            </div>
                            <textarea name="descricao" rows="5" required class="w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="Descrição detalhada da ocorrência"></textarea>
                            <label class="inline-flex items-center gap-2 text-sm">
                                <input type="checkbox" name="necessita_visita" value="1">
                                Necessita visita presencial
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm">
                                <input type="checkbox" name="necessita_apoio_financeiro" value="1">
                                Necessita apoio financeiro
                            </label>
                            <button type="submit" class="rounded-lg bg-cobalto px-4 py-2 font-medium text-white">Registrar ocorrência</button>
                        </form>
                    <?php endif; ?>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="font-display text-xl text-cobalto">Funções do cargo no painel</h2>
                    <ul class="mt-4 list-disc space-y-2 pl-5 text-sm text-slate-700">
                        <li>Registrar ocorrências assistenciais.</li>
                        <li>Priorizar casos e marcar necessidade de visita.</li>
                        <li>Controlar retorno e próxima ação.</li>
                        <li>Encaminhar casos ao Venerável e à Tesouraria.</li>
                        <li>Acompanhar apoio financeiro e status de resolução.</li>
                    </ul>
                </div>
            </section>
        </div>
    </div>
</body>
</html>

