<?php
$mensagemSucesso = $_SESSION['mensagem_sucesso'] ?? null;
$mensagemErro = $_SESSION['mensagem_erro'] ?? null;
unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']);

$erpPageTitle = '1o Vigilante - Painel de Instrução';
$appShellEyebrow = '1o Vigilante';
$appShellTitle = 'Painel do 1o Vigilante';
$appShellDescription = 'Acompanhamento formativo dos Aprendizes com trilha, devolutivas, leitura orientada e preparo para conclusão da docência maçônica.';
$appShellActiveHref = '/primeiro-vigilante';
$appShellActions = [
    ['label' => 'Voltar ao dashboard', 'href' => '/dashboard'],
    ['label' => 'Ver Aprendizes', 'href' => '/obreiros', 'primary' => true],
    ['label' => 'Biblioteca e classificação', 'href' => '/biblioteca'],
    ['label' => 'Abrir miniapp do cargo', 'href' => '/miniapp/primeiro-vigilante'],
];
$appShellSidebarSections = [
    [
        'title' => 'Vigilância',
        'items' => [
            ['label' => 'Painel do 1o Vigilante', 'href' => '/primeiro-vigilante'],
            ['label' => 'Lista de obreiros', 'href' => '/obreiros'],
            ['label' => 'Biblioteca e classificação', 'href' => '/biblioteca'],
            ['label' => 'Dashboard', 'href' => '/dashboard'],
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
            'title' => 'Dashboard operacional do 1o Vigilante',
            'subtitle' => 'Gestão de Aprendizes, trilhas, leituras e certificados.',
            'meta' => ['Perfil: acompanhamento formativo', 'Obreiros: consulta em leitura'],
            'actions' => [
                ['label' => 'Atualizar trilha', 'href' => '/primeiro-vigilante'],
                ['label' => 'Ação rápida', 'href' => '/primeiro-vigilante'],
                ['label' => 'Registrar leitura', 'href' => '/primeiro-vigilante'],
                ['label' => 'Solicitar certificado', 'href' => '/primeiro-vigilante'],
                ['label' => 'Classificar', 'href' => '/biblioteca/classificar'],
            ],
            'blocks' => [
                ['title' => 'Aprendizes', 'subtitle' => 'Base ativa e indicadores do ciclo.', 'span' => 'half', 'metrics' => [
                    ['label' => 'Ativos', 'value' => (string) ($resumo['aprendizes_ativos'] ?? 0)],
                    ['label' => 'Etapa inicial', 'value' => (string) ($resumo['etapa_inicial'] ?? 0)],
                ], 'list' => array_map(static fn (array $a): array => ['item' => (string) ($a['nome_historico'] ?? $a['nome'] ?? 'Aprendiz'), 'meta' => 'Etapa ' . (int) ($a['trilha_etapa_atual'] ?? 1), 'status' => (string) ($a['trilha_status_atual'] ?? '-')], array_slice($aprendizes, 0, 5))],
                ['title' => 'Leituras e certificados', 'subtitle' => 'Controle do fluxo de docência.', 'span' => 'half', 'metrics' => [
                    ['label' => 'Leituras sugeridas', 'value' => (string) ($resumo['leituras_sugeridas'] ?? 0)],
                    ['label' => 'Aptos certificado', 'value' => (string) ($resumo['aptos_certificado'] ?? 0)],
                ], 'list' => [['item' => 'Meu aprendizado', 'meta' => 'Acesso individual do Aprendiz', 'status' => 'Ativo'], ['item' => 'Biblioteca/classificação', 'meta' => 'Apoio pedagógico', 'status' => 'Ativo']]],
            ],
            'alerts' => [['title' => 'Acompanhamento contínuo', 'text' => 'Priorizar trilha e devolutiva dos Aprendizes com pendência.', 'tone' => 'warning']],
            'activity' => array_map(static fn (array $a): array => ['item' => 'Linha do tempo: ' . (string) ($a['nome_historico'] ?? $a['nome'] ?? 'Aprendiz'), 'meta' => (string) ($a['trilha_proxima_acao'] ?? 'A definir')], array_slice($aprendizes, 0, 4)),
            'links' => [['label' => 'Meu aprendizado', 'href' => '/meu-aprendizado'], ['label' => 'Obreiros (leitura)', 'href' => '/obreiros']],
        ];
        $dashboardRenderers = [
            static function (array $block): void { $dashboardMetrics = $block['metrics'] ?? []; $dashboardListItems = $block['list'] ?? []; require __DIR__ . '/../components/dashboard_metrics.php'; echo '<div class="mt-3">'; require __DIR__ . '/../components/dashboard_list.php'; echo '</div>'; },
            static function (array $block): void { $dashboardMetrics = $block['metrics'] ?? []; $dashboardListItems = $block['list'] ?? []; require __DIR__ . '/../components/dashboard_metrics.php'; echo '<div class="mt-3">'; require __DIR__ . '/../components/dashboard_list.php'; echo '</div>'; },
        ];
        require __DIR__ . '/../layouts/dashboard.php';
        ?>

        <section class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <article class="rounded-erp-lg border border-erp-border bg-white px-5 py-5 shadow-erp">
                <div class="text-xs font-semibold uppercase tracking-[0.22em] text-erp-muted">Aprendizes ativos</div>
                <div class="mt-2 text-4xl font-semibold text-erp-navy"><?= (int) ($resumo['aprendizes_ativos'] ?? 0) ?></div>
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
                <div class="mt-2 text-4xl font-semibold text-erp-navy"><?= (int) ($resumo['aptos_certificado'] ?? 0) ?></div>
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
                        <h2 class="mt-2 text-2xl font-semibold text-erp-navy">Aprendizes em acompanhamento</h2>
                        <p class="mt-2 max-w-4xl text-sm leading-6 text-erp-muted">Painel central com trilha, leitura formativa, devolutivas e preparo do certificado.</p>
                    </div>
                    <div class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">
                        Ativo
                    </div>
                </div>

                <div class="overflow-x-auto px-2 py-2">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-white">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.18em] text-erp-muted">Aprendiz</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.18em] text-erp-muted">Iniciação</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.18em] text-erp-muted">Etapa atual</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.18em] text-erp-muted">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.18em] text-erp-muted">Próxima ação</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <?php foreach ($aprendizes as $aprendiz): ?>
                                <tr class="align-top">
                                    <td class="px-4 py-3 text-sm text-slate-900">
                                        <div class="font-semibold"><?= htmlspecialchars((string) ($aprendiz['nome_historico'] ?? $aprendiz['nome'] ?? 'Aprendiz')) ?></div>
                                        <div class="text-xs text-erp-muted">CIM <?= htmlspecialchars((string) ($aprendiz['cim'] ?? '-')) ?></div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-700">
                                        <?= !empty($aprendiz['data_iniciacao']) ? htmlspecialchars(date('d/m/Y', strtotime((string) $aprendiz['data_iniciacao']))) : 'Não informada' ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-700">
                                        <div class="font-semibold">Etapa <?= (int) ($aprendiz['trilha_etapa_atual'] ?? 1) ?></div>
                                        <div class="text-xs text-erp-muted"><?= htmlspecialchars((string) ($aprendiz['trilha_titulo_atual'] ?? '')) ?></div>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="inline-flex rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                            <?= htmlspecialchars((string) ($aprendiz['trilha_status_atual'] ?? 'nao_iniciado')) ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-700">
                                        <?= htmlspecialchars((string) ($aprendiz['trilha_proxima_acao'] ?? 'A definir')) ?>
                                        <div class="mt-2">
                                            <a href="/primeiro-vigilante/aprendiz?id=<?= urlencode((string) ($aprendiz['id'] ?? '')) ?>" class="text-xs font-semibold text-erp-navy hover:underline">
                                                Abrir linha do tempo
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if ($aprendizes === []): ?>
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-sm text-erp-muted">Nenhum Aprendiz ativo encontrado.</td>
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
                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-erp-muted">PRIMEIRO_VIGILANTE</div>
                        <div class="mt-2 text-lg font-semibold text-slate-900">
                            <?= htmlspecialchars(trim((string) ($titularCargo['titular_nome'] ?? '')) ?: 'A definir') ?>
                        </div>
                        <div class="mt-2 text-sm leading-6 text-erp-muted">Cargo orientado à instrução, revisão de trabalhos e incentivo ao estudo dos Aprendizes.</div>
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

                <article class="rounded-erp-xl border border-amber-200 bg-amber-50 p-6 shadow-erp">
                    <h2 class="text-2xl font-semibold text-erp-navy">Status do cargo</h2>
                    <p class="mt-3 text-sm leading-6 text-slate-700">Ciclo formativo principal centralizado neste painel. O foco agora é manter consistência visual com o restante do ERP.</p>
                </article>
            </aside>
        </section>

<?php require __DIR__ . '/../partials/erp_shell_close.php'; ?>
