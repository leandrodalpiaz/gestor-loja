<?php
$mensagemSucesso = $_SESSION['mensagem_sucesso'] ?? null;
$mensagemErro = $_SESSION['mensagem_erro'] ?? null;
unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']);

$erpPageTitle = '2o Vigilante - Painel de InstruÃ§Ã£o';
$appShellEyebrow = '2o Vigilante';
$appShellTitle = 'Painel do 2o Vigilante';
$appShellDescription = 'Acompanhamento formativo dos Companheiros com trilha, leitura orientada, docÃªncia, certificado e recomendaÃ§Ã£o de exaltaÃ§Ã£o.';
$appShellActiveHref = '/segundo-vigilante';
$appShellActions = [
    ['label' => 'Voltar ao Painel', 'href' => '/dashboard'],
    ['label' => 'Ver Companheiros', 'href' => '/obreiros', 'primary' => true],
    ['label' => 'Biblioteca e classificaÃ§Ã£o', 'href' => '/biblioteca'],
    ['label' => 'Abrir miniapp do cargo', 'href' => '/miniapp/segundo-vigilante'],
];
$appShellSidebarSections = [
    [
        'title' => 'VigilÃ¢ncia',
        'items' => [
            ['label' => 'Painel do 2o Vigilante', 'href' => '/segundo-vigilante'],
            ['label' => 'Lista de obreiros', 'href' => '/obreiros'],
            ['label' => 'Biblioteca e classificaÃ§Ã£o', 'href' => '/biblioteca'],
            ['label' => 'Painel', 'href' => '/dashboard'],
        ],
    ],
];

require __DIR__ . '/../partials/erp_head.php';
require __DIR__ . '/../partials/erp_shell_open.php';
?>

        <?php if ($mensagemSucesso): ?>
            <div class="rounded-erp-md border border-emerald-200 bg-emerald-50 px-5 py-4 text-base text-emerald-800">
                <?= htmlspecialchars($mensagemSucesso) ?>
            </div>
        <?php endif; ?>
        <?php if ($mensagemErro): ?>
            <div class="rounded-erp-md border border-rose-200 bg-rose-50 px-5 py-4 text-base text-rose-800">
                <?= htmlspecialchars($mensagemErro) ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($avisoInfra)): ?>
            <div class="rounded-erp-md border border-amber-200 bg-amber-50 px-5 py-4 text-base text-amber-800">
                <?= htmlspecialchars((string) $avisoInfra) ?>
            </div>
        <?php endif; ?>

        <?php
        $dashboard = [
            'title' => 'Painel operacional do 2o Vigilante',
            'subtitle' => 'GestÃ£o de Companheiros, trilhas, leituras, certificados e exaltaÃ§Ã£o.',
            'meta' => ['Perfil: acompanhamento formativo', 'Obreiros: consulta em leitura'],
            'actions' => [
                ['label' => 'Atualizar trilha', 'href' => '/segundo-vigilante'],
                ['label' => 'AÃ§Ã£o rÃ¡pida', 'href' => '/segundo-vigilante'],
                ['label' => 'Registrar leitura', 'href' => '/segundo-vigilante'],
                ['label' => 'Solicitar certificado', 'href' => '/segundo-vigilante'],
                ['label' => 'Recomendar exaltaÃ§Ã£o', 'href' => '/segundo-vigilante'],
                ['label' => 'Classificar', 'href' => '/biblioteca/classificar'],
            ],
            'blocks' => [
                ['title' => 'Companheiros', 'subtitle' => 'Base ativa e situaÃ§Ã£o da trilha.', 'span' => 'half', 'metrics' => [
                    ['label' => 'Regulars', 'value' => (string) ($resumo['companheiros_ativos'] ?? 0)],
                    ['label' => 'Aptos Ã  exaltaÃ§Ã£o', 'value' => (string) ($resumo['aptos_exaltacao'] ?? 0)],
                ], 'list' => array_map(static fn (array $c): array => ['item' => (string) ($c['nome_historico'] ?? $c['nome'] ?? 'Companheiro'), 'meta' => 'Etapa ' . (int) ($c['trilha_etapa_atual'] ?? 1), 'status' => (string) ($c['trilha_status_atual'] ?? '-')], array_slice($companheiros, 0, 5))],
                ['title' => 'Leituras, certificados e exaltaÃ§Ã£o', 'subtitle' => 'Fluxo de acompanhamento do cargo.', 'span' => 'half', 'metrics' => [
                    ['label' => 'Leituras sugeridas', 'value' => (string) ($resumo['leituras_sugeridas'] ?? 0)],
                    ['label' => 'Aptos Ã  docÃªncia', 'value' => (string) ($resumo['aptos_docencia'] ?? 0)],
                ], 'list' => [['item' => 'Meu companheirismo', 'meta' => 'Consulta individual', 'status' => 'Regular'], ['item' => 'Biblioteca/classificaÃ§Ã£o', 'meta' => 'Apoio pedagÃ³gico', 'status' => 'Regular']]],
            ],
            'alerts' => [['title' => 'Ritmo de exaltaÃ§Ã£o', 'text' => 'Monitorar companheiros aptos e pendÃªncias de trilha.', 'tone' => 'warning']],
            'activity' => array_map(static fn (array $c): array => ['item' => 'Linha do tempo: ' . (string) ($c['nome_historico'] ?? $c['nome'] ?? 'Companheiro'), 'meta' => (string) ($c['trilha_proxima_acao'] ?? 'A definir')], array_slice($companheiros, 0, 4)),
            'links' => [['label' => 'Meu companheirismo', 'href' => '/meu-companheirismo'], ['label' => 'Obreiros (leitura)', 'href' => '/obreiros']],
        ];
        $dashboardRenderers = [
            static function (array $block): void { $dashboardMetrics = $block['metrics'] ?? []; $dashboardListItems = $block['list'] ?? []; require __DIR__ . '/../components/dashboard_metrics.php'; echo '<div class="mt-3">'; require __DIR__ . '/../components/dashboard_list.php'; echo '</div>'; },
            static function (array $block): void { $dashboardMetrics = $block['metrics'] ?? []; $dashboardListItems = $block['list'] ?? []; require __DIR__ . '/../components/dashboard_metrics.php'; echo '<div class="mt-3">'; require __DIR__ . '/../components/dashboard_list.php'; echo '</div>'; },
        ];
        require __DIR__ . '/../layouts/dashboard.php';
        ?>

        <section class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-6">
            <article class="rounded-erp-lg border border-erp-border bg-white px-5 py-5 shadow-erp">
                <div class="text-xs font-semibold uppercase tracking-[0.22em] text-erp-muted">Companheiros ativos</div>
                <div class="mt-2 text-4xl font-semibold text-erp-navy"><?= (int) ($resumo['companheiros_ativos'] ?? 0) ?></div>
            </article>
            <article class="rounded-erp-lg border border-erp-border bg-white px-5 py-5 shadow-erp">
                <div class="text-xs font-semibold uppercase tracking-[0.22em] text-erp-muted">Na etapa inicial</div>
                <div class="mt-2 text-4xl font-semibold text-erp-navy"><?= (int) ($resumo['etapa_inicial'] ?? 0) ?></div>
            </article>
            <article class="rounded-erp-lg border border-erp-border bg-white px-5 py-5 shadow-erp">
                <div class="text-xs font-semibold uppercase tracking-[0.22em] text-erp-muted">Aguardando recebimento</div>
                <div class="mt-2 text-4xl font-semibold text-erp-navy"><?= (int) ($resumo['trabalhos_aguardando_recebimento'] ?? 0) ?></div>
            </article>
            <article class="rounded-erp-lg border border-erp-border bg-white px-5 py-5 shadow-erp">
                <div class="text-xs font-semibold uppercase tracking-[0.22em] text-erp-muted">Aptos para certificado</div>
                <div class="mt-2 text-4xl font-semibold text-erp-navy"><?= (int) ($resumo['aptos_docencia'] ?? 0) ?></div>
            </article>
            <article class="rounded-erp-lg border border-erp-border bg-white px-5 py-5 shadow-erp">
                <div class="text-xs font-semibold uppercase tracking-[0.22em] text-erp-muted">Aptos para exaltaÃ§Ã£o</div>
                <div class="mt-2 text-4xl font-semibold text-erp-navy"><?= (int) ($resumo['aptos_exaltacao'] ?? 0) ?></div>
            </article>
            <article class="rounded-erp-lg border border-erp-border bg-white px-5 py-5 shadow-erp">
                <div class="text-xs font-semibold uppercase tracking-[0.22em] text-erp-muted">Leituras sugeridas</div>
                <div class="mt-2 text-4xl font-semibold text-erp-navy"><?= (int) ($resumo['leituras_sugeridas'] ?? 0) ?></div>
            </article>
        </section>

        <section class="mt-6 grid gap-6 xl:grid-cols-12">
            <article class="overflow-hidden rounded-erp-xl border border-erp-border bg-white shadow-erp xl:col-span-8 2xl:col-span-9">
                <div class="flex flex-wrap items-start justify-between gap-4 border-b border-erp-border bg-slate-50 px-6 py-5">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-[0.22em] text-erp-muted">Fluxo operacional</div>
                        <h2 class="mt-2 text-2xl font-semibold text-erp-navy">Companheiros em acompanhamento</h2>
                        <p class="mt-2 max-w-4xl text-sm leading-6 text-erp-muted">Painel central com trilha, docÃªncia, certificado e indicaÃ§Ã£o de exaltaÃ§Ã£o.</p>
                    </div>
                    <div class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">
                        Regular
                    </div>
                </div>

                <div class="overflow-x-auto px-2 py-2">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-white">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.18em] text-erp-muted">Companheiro</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.18em] text-erp-muted">ElevaÃ§Ã£o</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.18em] text-erp-muted">Etapa atual</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.18em] text-erp-muted">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.18em] text-erp-muted">PrÃ³xima aÃ§Ã£o</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <?php foreach ($companheiros as $companheiro): ?>
                                <tr class="align-top">
                                    <td class="px-4 py-3 text-sm text-slate-900">
                                        <div class="font-semibold"><?= htmlspecialchars((string) ($companheiro['nome_historico'] ?? $companheiro['nome'] ?? 'Companheiro')) ?></div>
                                        <div class="text-xs text-erp-muted">CIM <?= htmlspecialchars((string) ($companheiro['cim'] ?? '-')) ?></div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-700">
                                        <?= !empty($companheiro['data_elevacao']) ? htmlspecialchars(date('d/m/Y', strtotime((string) $companheiro['data_elevacao']))) : 'NÃ£o informada' ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-700">
                                        <div class="font-semibold">Etapa <?= (int) ($companheiro['trilha_etapa_atual'] ?? 1) ?></div>
                                        <div class="text-xs text-erp-muted"><?= htmlspecialchars((string) ($companheiro['trilha_titulo_atual'] ?? '')) ?></div>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="inline-flex rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                            <?= htmlspecialchars((string) ($companheiro['trilha_status_atual'] ?? 'nao_iniciado')) ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-700">
                                        <?= htmlspecialchars((string) ($companheiro['trilha_proxima_acao'] ?? 'A definir')) ?>
                                        <div class="mt-2">
                                            <a href="/segundo-vigilante/companheiro?id=<?= urlencode((string) ($companheiro['id'] ?? '')) ?>" class="text-xs font-semibold text-erp-navy hover:underline">
                                                Abrir linha do tempo
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if ($companheiros === []): ?>
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-sm text-erp-muted">Nenhum Companheiro ativo encontrado.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </article>

            <aside class="space-y-6 xl:col-span-4 2xl:col-span-3">
                <article class="rounded-erp-xl border border-erp-border bg-white p-6 shadow-erp">
                    <h2 class="text-2xl font-semibold text-erp-navy">Titular do cargo</h2>
                    <div class="mt-4 rounded-erp-lg border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-erp-muted">SEGUNDO_VIGILANTE</div>
                        <div class="mt-2 text-lg font-semibold text-slate-900">
                            <?= htmlspecialchars(trim((string) ($titularCargo['titular_nome'] ?? '')) ?: 'A definir') ?>
                        </div>
                        <div class="mt-2 text-sm leading-6 text-erp-muted">Cargo orientado Ã  instruÃ§Ã£o dos Companheiros, revisÃ£o de trabalhos e preparo para exaltaÃ§Ã£o.</div>
                    </div>
                </article>

                <article class="rounded-erp-xl border border-erp-border bg-white p-6 shadow-erp">
                    <h2 class="text-2xl font-semibold text-erp-navy">Trilha de estudo</h2>
                    <div class="mt-4 space-y-3">
                        <?php foreach ($trilhaEstudo as $ordem => $titulo): ?>
                            <div class="rounded-erp-md border border-slate-200 bg-slate-50 px-4 py-3">
                                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-erp-muted">Etapa <?= (int) $ordem ?></div>
                                <div class="mt-1 text-sm font-semibold text-slate-800"><?= htmlspecialchars($titulo) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </article>
            </aside>
        </section>

<?php require __DIR__ . '/../partials/erp_shell_close.php'; ?>

