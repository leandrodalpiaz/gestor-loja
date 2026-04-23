<?php
$mensagemSucesso = $_SESSION['mensagem_sucesso'] ?? null;
$mensagemErro = $_SESSION['mensagem_erro'] ?? null;
unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']);

$sessaoEmFoco = $sessaoEmFoco ?? $proximaSessao ?? null;
$semAgape = array_values(array_filter(
    $confirmados,
    static fn (array $item): bool => empty($item['participara_agape'])
));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mestre de Banquetes - Gestor da Loja</title>
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
    <div class="mx-auto max-w-6xl px-4 py-8">
        <header class="mb-8 rounded-3xl border border-white/40 bg-[radial-gradient(circle_at_top_left,#d3b269,transparent_28%),linear-gradient(135deg,#172030,#27364a)] px-6 py-7 text-white shadow-2xl">
            <p class="text-xs uppercase tracking-[0.24em] text-amber-300">Painel do Mestre de Banquetes</p>
            <h1 class="mt-2 text-3xl font-semibold">Controle do agape e confirmados</h1>
            <p class="mt-2 max-w-2xl text-sm text-slate-200">O cargo agora trabalha por sessao em foco, com previsao de participantes, observacoes logisticas e status operacional do banquete.</p>
            <div class="mt-4 flex flex-wrap gap-2">
                <a href="/secretaria" class="rounded-md bg-white/10 px-3 py-2 text-sm hover:bg-white/20">Ir para Secretaria</a>
                <a href="/dashboard" class="rounded-md bg-amber-400 px-3 py-2 text-sm font-semibold text-slate-900 hover:bg-amber-300">Voltar ao dashboard</a>
            </div>
        </header>

        <?php if ($mensagemSucesso): ?>
            <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-700"><?= htmlspecialchars($mensagemSucesso) ?></div>
        <?php endif; ?>
        <?php if ($mensagemErro): ?>
            <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-rose-700"><?= htmlspecialchars($mensagemErro) ?></div>
        <?php endif; ?>

        <?php
        $dashboard = [
            'title' => 'Dashboard operacional do Mestre de Banquetes',
            'subtitle' => 'Execucao operacional e registro de operacao do cargo.',
            'meta' => ['Perfil: operacao de sessao'],
            'actions' => [
                ['label' => 'Registrar operacao', 'href' => '/mestre-banquetes/operacao/salvar'],
            ],
            'blocks' => [
                ['title' => 'Operacoes recentes', 'subtitle' => 'Status e previsao de participantes.', 'span' => 'half', 'metrics' => [
                    ['label' => 'Confirmados', 'value' => (string) count($confirmados)],
                    ['label' => 'Participantes agape', 'value' => (string) count($participantesAgape)],
                ], 'list' => [['item' => 'Status operacional', 'meta' => (string) ($operacaoBanquete['status_operacional'] ?? 'planejamento'), 'status' => 'Ativo']]],
                ['title' => 'Sessoes relacionadas e historico', 'subtitle' => 'Leitura simples da agenda.', 'span' => 'half', 'metrics' => [
                    ['label' => 'Sessoes na agenda', 'value' => (string) count($sessoes)],
                    ['label' => 'Sem agape', 'value' => (string) count($semAgape)],
                ], 'list' => array_map(static fn (array $s): array => ['item' => (string) ($s['titulo'] ?: (($s['tipo_sessao'] ?? 'Sessao') . ' - ' . ($s['grau_sessao'] ?? ''))), 'meta' => (string) ($s['data_hora_inicio'] ?? ''), 'status' => (string) ($s['status'] ?? '-')], array_slice($sessoes, 0, 4))],
            ],
            'alerts' => [['title' => 'Operacao de agape', 'text' => 'Manter previsao e status alinhados com a sessao em foco.', 'tone' => 'warning']],
            'activity' => array_map(static fn (array $p): array => ['item' => (string) ($p['nome'] ?? 'Participante'), 'meta' => 'CIM ' . (string) ($p['cim'] ?? '-')], array_slice($participantesAgape, 0, 4)),
            'links' => [['label' => 'Dashboard do cargo', 'href' => '/mestre-banquetes/dashboard']],
        ];
        $dashboardRenderers = [
            static function (array $block): void { $dashboardMetrics = $block['metrics'] ?? []; $dashboardListItems = $block['list'] ?? []; require __DIR__ . '/../components/dashboard_metrics.php'; echo '<div class="mt-3">'; require __DIR__ . '/../components/dashboard_list.php'; echo '</div>'; },
            static function (array $block): void { $dashboardMetrics = $block['metrics'] ?? []; $dashboardListItems = $block['list'] ?? []; require __DIR__ . '/../components/dashboard_metrics.php'; echo '<div class="mt-3">'; require __DIR__ . '/../components/dashboard_list.php'; echo '</div>'; },
        ];
        require __DIR__ . '/../layouts/dashboard.php';
        ?>

        <div class="grid gap-6 lg:grid-cols-[1.05fr_0.95fr]">
            <section class="space-y-6">
                <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <h2 class="text-2xl font-semibold text-slate-900">Sessao em foco</h2>
                            <p class="mt-2 text-sm text-slate-700">O Mestre de Banquetes pode alternar a sessão de trabalho para ajustar previsão e operação do ágape.</p>
                        </div>
                        <form method="GET" action="/mestre-banquetes" class="w-full max-w-md">
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
                            <div class="text-lg font-semibold text-slate-900"><?= htmlspecialchars($sessaoEmFoco['titulo'] ?: (($sessaoEmFoco['tipo_sessao'] ?? 'Sessao') . ' - ' . ($sessaoEmFoco['grau_sessao'] ?? ''))) ?></div>
                            <div class="mt-1 text-sm text-slate-700"><?= htmlspecialchars((string) ($sessaoEmFoco['data_hora_inicio'] ?? '')) ?></div>
                            <div class="mt-3 flex flex-wrap gap-2 text-xs">
                                <span class="rounded-full bg-white px-3 py-1 text-slate-700">Confirmados: <?= count($confirmados) ?></span>
                                <span class="rounded-full bg-white px-3 py-1 text-slate-700">Agape: <?= count($participantesAgape) ?></span>
                                <span class="rounded-full bg-white px-3 py-1 text-slate-700">Status: <?= htmlspecialchars((string) ($sessaoEmFoco['status'] ?? '-')) ?></span>
                            </div>
                            <div class="mt-3 grid gap-2 text-xs text-slate-700">
                                <div>Configuracao do agape: <?= htmlspecialchars((string) ($descricaoAgape ?? 'Nao informado')) ?></div>
                                <div>Modelo financeiro: <?= htmlspecialchars((string) ($descricaoModeloFinanceiroAgape ?? 'Nao informado')) ?></div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">Nenhuma sessao futura cadastrada.</div>
                    <?php endif; ?>
                </article>

                <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-2xl font-semibold text-slate-900">Operação do banquete</h2>
                            <p class="mt-2 text-sm text-slate-700">Registre previsão, observações e o status logístico do ágape.</p>
                        </div>
                        <a href="/miniapp/mestre-banquetes" class="rounded-xl bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-800">Abrir mobile</a>
                    </div>
                    <form method="POST" action="/mestre-banquetes/operacao/salvar" class="mt-4 space-y-3">
                        <input type="hidden" name="sessao_id" value="<?= (int) ($sessaoEmFoco['id'] ?? 0) ?>">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Status operacional</label>
                            <select name="status_operacional" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900">
                                <?php
                                $statusAtual = (string) ($operacaoBanquete['status_operacional'] ?? 'planejamento');
                                foreach (['planejamento' => 'Planejamento', 'preparacao' => 'Preparacao', 'abastecimento' => 'Abastecimento', 'fechado' => 'Fechado'] as $valor => $label):
                                ?>
                                    <option value="<?= $valor ?>" <?= $statusAtual === $valor ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Previsão de participantes</label>
                            <input type="number" min="0" name="previsao_participantes" value="<?= htmlspecialchars((string) ($operacaoBanquete['previsao_participantes'] ?? '')) ?>" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Observações logísticas</label>
                            <textarea name="observacoes" rows="4" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900"><?= htmlspecialchars((string) ($operacaoBanquete['observacoes'] ?? '')) ?></textarea>
                        </div>
                        <button type="submit" class="rounded-xl bg-amber-600 px-4 py-3 text-sm font-semibold text-white hover:bg-amber-500">Salvar operação</button>
                    </form>
                </article>

                <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-2xl font-semibold text-slate-900">Participantes do agape</h2>
                    <p class="mt-2 text-sm text-slate-700">Lista pratica dos confirmados que optaram por participar do agape.</p>
                    <div class="mt-4 space-y-3">
                        <?php if ($participantesAgape !== []): ?>
                            <?php foreach ($participantesAgape as $participante): ?>
                                <div class="rounded-2xl border border-amber-200 bg-[linear-gradient(135deg,#fffdf7,#f7f0df)] px-4 py-3">
                                    <div class="font-medium text-slate-900"><?= htmlspecialchars((string) ($participante['nome'] ?? 'Obreiro')) ?></div>
                                    <div class="mt-1 text-sm text-slate-700">CIM: <?= htmlspecialchars((string) ($participante['cim'] ?? '-')) ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">Ainda não há participantes confirmados com ágape.</div>
                        <?php endif; ?>
                    </div>
                </article>
            </section>

            <aside class="space-y-6">
                <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-2xl font-semibold text-slate-900">Confirmados sem agape</h2>
                    <div class="mt-4 space-y-3">
                        <?php if ($semAgape !== []): ?>
                            <?php foreach ($semAgape as $confirmado): ?>
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                    <div class="font-medium text-slate-900"><?= htmlspecialchars((string) ($confirmado['nome'] ?? 'Obreiro')) ?></div>
                                    <div class="mt-1 text-sm text-slate-700">CIM: <?= htmlspecialchars((string) ($confirmado['cim'] ?? '-')) ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">Não há confirmados sem ágape neste momento.</div>
                        <?php endif; ?>
                    </div>
                </article>

                <article class="rounded-3xl border border-slate-200 bg-[linear-gradient(180deg,#fff,#f4efe3)] p-6 shadow-sm">
                    <h2 class="text-2xl font-semibold text-slate-900">Agenda futura</h2>
                    <div class="mt-4 space-y-3">
                        <?php foreach ($sessoes as $sessao): ?>
                            <a href="/mestre-banquetes?sessao_id=<?= (int) ($sessao['id'] ?? 0) ?>" class="block rounded-2xl border border-slate-200 bg-white px-4 py-3 hover:border-amber-300">
                                <div class="font-medium text-slate-900"><?= htmlspecialchars($sessao['titulo'] ?: (($sessao['tipo_sessao'] ?? 'Sessao') . ' - ' . ($sessao['grau_sessao'] ?? ''))) ?></div>
                                <div class="mt-1 text-sm text-slate-700"><?= htmlspecialchars((string) ($sessao['data_hora_inicio'] ?? '')) ?></div>
                                <div class="mt-2 text-xs text-slate-700">Confirmados: <?= (int) ($sessao['total_confirmados'] ?? 0) ?> · Agape: <?= (int) ($sessao['total_agape'] ?? 0) ?></div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </article>
            </aside>
        </div>
    </div>
</body>
</html>
