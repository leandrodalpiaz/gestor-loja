<?php
$mensagemSucesso = $_SESSION['mensagem_sucesso'] ?? null;
$mensagemErro = $_SESSION['mensagem_erro'] ?? null;
unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']);

$sessaoEmFoco = $sessaoSelecionada ?? $proximaSessao ?? null;
$presentesEfetivos = array_values(array_filter(
    $mapaPresencas,
    static fn (array $registro): bool => !empty($registro['presente'])
));

$descricaoAgape = static function (array $sessao): string {
    $modalidade = strtolower(trim((string) ($sessao['agape_modalidade'] ?? 'nao_havera')));
    if ($modalidade === 'gratuito') {
        return 'Gratuito';
    }
    if ($modalidade === 'pago') {
        return 'Pago';
    }
    return 'Nao havera';
};

$descricaoModeloFinanceiroAgape = static function (array $sessao): string {
    $modalidade = strtolower(trim((string) ($sessao['agape_modalidade'] ?? 'nao_havera')));
    if ($modalidade === 'nao_havera') {
        return 'Nao se aplica';
    }

    $modelo = strtolower(trim((string) ($sessao['agape_modelo_financeiro'] ?? 'oficial_loja')));
    if ($modelo === 'particular') {
        return 'Particular';
    }
    if ($modelo === 'misto') {
        return 'Misto';
    }
    return 'Oficial da Loja';
};

$paramsCertificado = [];
if ($sessaoEmFoco && !empty($sessaoEmFoco['data_hora_inicio'])) {
    $paramsCertificado = [
        'data_sessao' => substr((string) $sessaoEmFoco['data_hora_inicio'], 0, 10),
        'tipo_sessao' => (string) ($sessaoEmFoco['tipo_sessao'] ?? 'Ordinaria'),
        'grau_sessao' => (string) ($sessaoEmFoco['grau_sessao'] ?? 'Mestre Macom'),
    ];
}
$urlCertificado = '/chancelaria/certificado' . ($paramsCertificado !== [] ? '?' . http_build_query($paramsCertificado) : '');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chanceler - Sessao</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
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
<body class="erp-readable min-h-screen bg-[linear-gradient(180deg,#f8f4ea_0%,#eef2f7_100%)] font-sans text-slate-900">
    <div class="mx-auto max-w-7xl px-4 py-8">
        <header class="mb-8 rounded-3xl border border-white/40 bg-[radial-gradient(circle_at_top_left,#d6b672,transparent_28%),linear-gradient(135deg,#162033,#223145)] px-6 py-7 text-white shadow-2xl">
            <p class="text-xs uppercase tracking-[0.24em] text-amber-300">Painel do Chanceler</p>
            <h1 class="mt-2 text-3xl font-semibold">Check-in do quadro e visitantes</h1>
            <p class="mt-2 max-w-3xl text-sm text-slate-200">Use esta tela para fazer o check-in da sessao, acompanhar a nominata prevista e manter a leitura objetiva dos visitantes para apoio da Secretaria e do Orador.</p>
            <div class="mt-4 flex flex-wrap gap-2">
                <a href="/secretaria" class="rounded-md bg-white/10 px-3 py-2 text-sm hover:bg-white/20">Ir para Secretaria</a>
                <a href="/dashboard" class="rounded-md bg-amber-400 px-3 py-2 text-sm font-semibold text-slate-900 hover:bg-amber-300">Voltar ao dashboard</a>
                <a href="/miniapp/chanceler" class="rounded-md bg-white/10 px-3 py-2 text-sm hover:bg-white/20">Abrir miniapp</a>
            </div>
        </header>

        <section class="mb-6 grid gap-3 md:grid-cols-3">
            <article class="rounded-2xl border border-white/60 bg-white/90 px-4 py-4 shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400">Sessao ativa</p>
                <p class="mt-2 text-lg font-semibold text-slate-900"><?= htmlspecialchars((string) ($sessaoEmFoco['titulo'] ?? (($sessaoEmFoco['tipo_sessao'] ?? 'Sessao') . ' - ' . ($sessaoEmFoco['grau_sessao'] ?? '')))) ?></p>
                <p class="mt-1 text-sm text-slate-700"><?= htmlspecialchars((string) ($sessaoEmFoco['data_hora_inicio'] ?? 'Sem data definida')) ?></p>
            </article>
            <article class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-4 shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-emerald-700">Presenca efetiva</p>
                <p class="mt-2 text-3xl font-semibold text-emerald-800"><?= count($presentesEfetivos) ?></p>
                <p class="mt-1 text-sm text-emerald-700">Obreiros confirmados no quadro final da sessao.</p>
            </article>
            <article class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-amber-800">Visitantes resumidos</p>
                <p class="mt-2 text-3xl font-semibold text-amber-900"><?= count($visitantesResumo) ?></p>
                <p class="mt-1 text-sm text-amber-800">Leitura rápida pronta para apoiar Secretaria e Orador.</p>
            </article>
        </section>

        <?php if ($mensagemSucesso): ?>
            <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-700"><?= htmlspecialchars($mensagemSucesso) ?></div>
        <?php endif; ?>
        <?php if ($mensagemErro): ?>
            <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-rose-700"><?= htmlspecialchars($mensagemErro) ?></div>
        <?php endif; ?>

        <?php
        $dashboard = [
            'title' => 'Dashboard operacional do Chanceler',
            'subtitle' => 'Controle de sessao, presenca e consulta de obreiros.',
            'meta' => ['Escopo: sessao e presenca', 'Obreiros: leitura operacional'],
            'actions' => [
                ['label' => 'Abrir sessao', 'href' => '/chanceler/sessao'],
                ['label' => 'Registrar presenca', 'href' => '/chanceler/sessao/presenca'],
            ],
            'blocks' => [
                ['title' => 'Sessao atual', 'subtitle' => 'Contexto ativo da operacao.', 'span' => 'half', 'metrics' => [
                    ['label' => 'Sessao em foco', 'value' => (string) ($sessaoEmFoco['titulo'] ?? 'Sessao')],
                    ['label' => 'Confirmados', 'value' => (string) count($confirmados)],
                ], 'list' => [['item' => 'Agape', 'meta' => $descricaoAgape((array) ($sessaoEmFoco ?? [])), 'status' => 'Contexto']]],
                ['title' => 'Presenca e obreiros', 'subtitle' => 'Registro de presença e apoio da nominata.', 'span' => 'half', 'metrics' => [
                    ['label' => 'Presentes efetivos', 'value' => (string) count($presentesEfetivos)],
                    ['label' => 'Visitantes resumidos', 'value' => (string) count($visitantesResumo)],
                ], 'list' => array_map(static fn (array $p): array => ['item' => (string) ($p['nome'] ?? 'Obreiro'), 'meta' => 'CIM ' . (string) ($p['cim'] ?? '-'), 'status' => 'Presente'], array_slice($presentesEfetivos, 0, 4))],
            ],
            'alerts' => [['title' => 'Base de presenca oficial', 'text' => 'Check-in alimenta leitura da sessao e suporte aos demais modulos.', 'tone' => 'warning']],
            'activity' => array_map(static fn (array $v): array => ['item' => (string) ($v['nome'] ?? 'Visitante'), 'meta' => (string) ($v['linha_resumida'] ?? '')], array_slice($visitantesResumo, 0, 4)),
            'links' => [['label' => 'Dashboard Chanceler', 'href' => '/chanceler/sessao/dashboard'], ['label' => 'Obreiros (leitura)', 'href' => '/obreiros']],
        ];
        $dashboardRenderers = [
            static function (array $block): void { $dashboardMetrics = $block['metrics'] ?? []; $dashboardListItems = $block['list'] ?? []; require __DIR__ . '/../components/dashboard_metrics.php'; echo '<div class="mt-3">'; require __DIR__ . '/../components/dashboard_list.php'; echo '</div>'; },
            static function (array $block): void { $dashboardMetrics = $block['metrics'] ?? []; $dashboardListItems = $block['list'] ?? []; require __DIR__ . '/../components/dashboard_metrics.php'; echo '<div class="mt-3">'; require __DIR__ . '/../components/dashboard_list.php'; echo '</div>'; },
        ];
        require __DIR__ . '/../layouts/dashboard.php';
        ?>

        <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
            <section class="space-y-6">
                <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400">Contexto da sessao</p>
                            <h2 class="text-2xl font-semibold text-slate-900">Sessao em foco</h2>
                            <p class="mt-2 text-sm text-slate-700">Troque a sessao em foco sem perder o contexto de confirmados, presenca efetiva e visitantes.</p>
                        </div>
                        <form method="GET" action="/chanceler/sessao" class="w-full max-w-md">
                            <label for="sessao_id" class="mb-1 block text-sm font-medium text-slate-700">Selecionar sessao</label>
                            <select id="sessao_id" name="sessao_id" onchange="this.form.submit()" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900">
                                <?php foreach ($sessoes as $sessaoOpcao): ?>
                                    <option value="<?= (int) ($sessaoOpcao['id'] ?? 0) ?>" <?= !empty($sessaoEmFoco['id']) && (int) $sessaoEmFoco['id'] === (int) ($sessaoOpcao['id'] ?? 0) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars((string) (($sessaoOpcao['titulo'] ?? '') !== '' ? $sessaoOpcao['titulo'] : (($sessaoOpcao['tipo_sessao'] ?? 'Sessão') . ' - ' . ($sessaoOpcao['grau_sessao'] ?? '')))) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>

                    <?php if ($sessaoEmFoco): ?>
                        <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <div class="text-lg font-semibold text-slate-900"><?= htmlspecialchars($sessaoEmFoco['titulo'] ?: (($sessaoEmFoco['tipo_sessao'] ?? 'Sessao') . ' - ' . ($sessaoEmFoco['grau_sessao'] ?? ''))) ?></div>
                                    <div class="mt-1 text-sm text-slate-700"><?= htmlspecialchars((string) ($sessaoEmFoco['data_hora_inicio'] ?? '')) ?></div>
                                </div>
                                <a href="<?= htmlspecialchars($urlCertificado) ?>" class="rounded-xl bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-800">Emitir certificado</a>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-2 text-xs">
                                <span class="rounded-full bg-white px-3 py-1 text-slate-700">Confirmados: <?= count($confirmados) ?></span>
                                <span class="rounded-full bg-sky-50 px-3 py-1 text-sky-700">Nominata prevista: <?= count($mapaPresencas) ?></span>
                                <span class="rounded-full bg-emerald-50 px-3 py-1 text-emerald-700">Presentes efetivos: <?= count($presentesEfetivos) ?></span>
                                <span class="rounded-full bg-white px-3 py-1 text-slate-700">Visitantes resumidos: <?= count($visitantesResumo) ?></span>
                                <span class="rounded-full bg-white px-3 py-1 text-slate-700">Agape: <?= htmlspecialchars($descricaoAgape($sessaoEmFoco)) ?></span>
                                <span class="rounded-full bg-white px-3 py-1 text-slate-700">Modelo financeiro: <?= htmlspecialchars($descricaoModeloFinanceiroAgape($sessaoEmFoco)) ?></span>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">Nenhuma sessao futura cadastrada.</div>
                    <?php endif; ?>
                </article>

                <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400">Acao principal</p>
                    <h2 class="text-2xl font-semibold text-slate-900">Check-in do quadro da Loja</h2>
                    <p class="mt-2 text-sm text-slate-700">Somente os presentes efetivos entram na base de votação do balaustre e na leitura final da nominata.</p>
                    <div class="mt-4 grid gap-3 md:grid-cols-2">
                        <?php if ($mapaPresencas !== []): ?>
                            <?php foreach ($mapaPresencas as $registro): ?>
                                <form method="POST" action="/chanceler/sessao/presenca" class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <input type="hidden" name="sessao_id" value="<?= (int) ($sessaoEmFoco['id'] ?? 0) ?>">
                                    <input type="hidden" name="obreiro_id" value="<?= htmlspecialchars((string) ($registro['id'] ?? '')) ?>">
                                    <div class="font-medium text-slate-900"><?= htmlspecialchars((string) ($registro['nome'] ?? 'Obreiro')) ?></div>
                                    <div class="mt-1 text-xs text-slate-700">CIM: <?= htmlspecialchars((string) ($registro['cim'] ?? '-')) ?> · Grau: <?= htmlspecialchars((string) ($registro['grau'] ?? '-')) ?></div>
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <button type="submit" name="presente" value="1" class="rounded-md px-3 py-1.5 text-sm <?= !empty($registro['presente']) ? 'bg-emerald-600 text-white' : 'border border-emerald-300 text-emerald-700' ?>">Presente</button>
                                        <button type="submit" name="presente" value="0" class="rounded-md px-3 py-1.5 text-sm <?= empty($registro['presente']) ? 'bg-slate-700 text-white' : 'border border-slate-300 text-slate-700' ?>">Nao presente</button>
                                    </div>
                                </form>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700 md:col-span-2">Nenhuma nominata prevista disponivel para esta sessao.</div>
                        <?php endif; ?>
                    </div>
                </article>

                <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400">Fechamento da lista</p>
                            <h2 class="text-2xl font-semibold text-slate-900">Lista final de presentes</h2>
                            <p class="mt-2 text-sm text-slate-700">Base de conferência rápida para fechar a nominata efetiva da sessão.</p>
                        </div>
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-sm font-medium text-emerald-700"><?= count($presentesEfetivos) ?> presentes</span>
                    </div>
                    <div class="mt-4 grid gap-3 md:grid-cols-2">
                        <?php if ($presentesEfetivos !== []): ?>
                            <?php foreach ($presentesEfetivos as $presente): ?>
                                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                                    <div class="font-medium text-slate-900"><?= htmlspecialchars((string) ($presente['nome'] ?? 'Obreiro')) ?></div>
                                    <div class="mt-1 text-xs text-slate-700">CIM: <?= htmlspecialchars((string) ($presente['cim'] ?? '-')) ?> · Grau: <?= htmlspecialchars((string) ($presente['grau'] ?? '-')) ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700 md:col-span-2">Ainda não há presentes efetivos marcados para esta sessão.</div>
                        <?php endif; ?>
                    </div>
                </article>
            </section>

            <aside class="space-y-6">
                <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400">Visitantes</p>
                    <h2 class="text-2xl font-semibold text-slate-900">Visitantes resumidos</h2>
                    <p class="mt-2 text-sm text-slate-700">Esta lista alimenta a Secretaria para o balaustre e o Orador para a leitura nominal.</p>
                    <div class="mt-4 space-y-3">
                        <?php if ($visitantesResumo !== []): ?>
                            <?php foreach ($visitantesResumo as $visitante): ?>
                                <div class="rounded-2xl border border-amber-200 bg-[linear-gradient(135deg,#fffdf7,#f7f0df)] px-4 py-3">
                                    <div class="font-medium text-slate-900"><?= htmlspecialchars((string) ($visitante['nome'] ?? 'Visitante')) ?></div>
                                    <div class="mt-1 text-sm text-slate-700"><?= htmlspecialchars((string) ($visitante['linha_resumida'] ?? '')) ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">Ainda não há lista resumida de visitantes registrada para esta sessão.</div>
                        <?php endif; ?>
                    </div>
                </article>

                <article class="rounded-3xl border border-slate-200 bg-[linear-gradient(180deg,#fff,#f4efe3)] p-6 shadow-sm">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400">Base de presenca</p>
                    <h2 class="text-2xl font-semibold text-slate-900">Confirmados da sessao</h2>
                    <div class="mt-4 space-y-3">
                        <?php if ($confirmados !== []): ?>
                            <?php foreach ($confirmados as $confirmado): ?>
                                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                                    <div class="font-medium text-slate-900"><?= htmlspecialchars((string) ($confirmado['nome'] ?? 'Obreiro')) ?></div>
                                    <div class="mt-1 text-sm text-slate-700"><?= !empty($confirmado['participara_agape']) ? 'Confirmado com agape' : 'Confirmado sem agape' ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">Ainda não há confirmados registrados.</div>
                        <?php endif; ?>
                    </div>
                </article>
            </aside>
        </div>
    </div>
</body>
</html>
