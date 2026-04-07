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
    <title>Veneravel Mestre - Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        onix: '#111827',
                        bronze: '#b88938',
                        areia: '#f3ede2',
                        vinho: '#6b1f2b'
                    },
                    fontFamily: {
                        display: ['"Fraunces"', 'serif'],
                        sans: ['"Inter"', 'sans-serif']
                    }
                }
            }
        }
    </script>
</head>
<body class="min-h-screen bg-[radial-gradient(circle_at_top,#f8f2e7_0%,#eceff3_42%,#e5e7eb_100%)] font-sans text-slate-900">
    <div class="mx-auto max-w-7xl px-4 py-8">
        <header class="mb-8 overflow-hidden rounded-3xl border border-white/40 bg-[radial-gradient(circle_at_top_left,#d7b77a,transparent_28%),linear-gradient(135deg,#111827,#1f2937_55%,#374151)] px-6 py-7 text-white shadow-2xl">
            <p class="text-xs uppercase tracking-[0.2em] text-amber-300">Painel de governanca</p>
            <h1 class="mt-2 font-display text-3xl font-bold">Dashboard do Veneravel Mestre</h1>
            <p class="mt-2 text-sm text-slate-200">Centro de decisao para votacoes de balaustre, acompanhamento de sessoes e visao da nominata oficial.</p>
            <div class="mt-4 flex flex-wrap gap-2">
                <a href="/secretaria" class="rounded-md bg-white/10 px-3 py-2 text-sm hover:bg-white/20">Ir para Secretaria</a>
                <a href="/secretaria/votacao" class="rounded-md bg-white/10 px-3 py-2 text-sm hover:bg-white/20">Painel de votacao</a>
                <a href="/dashboard" class="rounded-md bg-amber-400 px-3 py-2 text-sm font-semibold text-slate-900 hover:bg-amber-300">Voltar ao dashboard geral</a>
            </div>
        </header>

        <?php if ($mensagemSucesso): ?>
            <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-700"><?= htmlspecialchars($mensagemSucesso) ?></div>
        <?php endif; ?>
        <?php if ($mensagemErro): ?>
            <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-rose-700"><?= htmlspecialchars($mensagemErro) ?></div>
        <?php endif; ?>

        <section class="mb-6 grid gap-4 md:grid-cols-4">
            <article class="rounded-2xl border border-white/70 bg-white/90 p-5 shadow-sm">
                <div class="text-sm text-slate-500">Balaustres aptos</div>
                <div class="mt-2 text-3xl font-bold text-onix"><?= count($balaustresAptos) ?></div>
                <div class="mt-1 text-xs text-slate-500">Prontos para abrir votacao</div>
            </article>
            <article class="rounded-2xl border border-white/70 bg-white/90 p-5 shadow-sm">
                <div class="text-sm text-slate-500">Em votacao</div>
                <div class="mt-2 text-3xl font-bold text-onix"><?= count($balaustresEmVotacao) ?></div>
                <div class="mt-1 text-xs text-slate-500">Aguardando encerramento</div>
            </article>
            <article class="rounded-2xl border border-white/70 bg-white/90 p-5 shadow-sm">
                <div class="text-sm text-slate-500">Sessoes futuras</div>
                <div class="mt-2 text-3xl font-bold text-onix"><?= count($sessoes) ?></div>
                <div class="mt-1 text-xs text-slate-500">Planejamento da loja</div>
            </article>
            <article class="rounded-2xl border border-white/70 bg-white/90 p-5 shadow-sm">
                <div class="text-sm text-slate-500">Cargos com titular</div>
                <div class="mt-2 text-3xl font-bold text-onix"><?= count(array_filter($nominata, static fn(array $c): bool => trim((string) ($c['titular_nome'] ?? '')) !== '')) ?></div>
                <div class="mt-1 text-xs text-slate-500">Nominata oficial ativa</div>
            </article>
        </section>

        <div class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
            <section class="space-y-6">
                <article class="rounded-3xl border border-white/70 bg-white/90 p-6 shadow-sm">
                    <h2 class="font-display text-2xl font-semibold">Proxima Sessao</h2>
                    <?php if ($proximaSessao): ?>
                        <div class="mt-3 rounded-2xl border border-slate-200 bg-[linear-gradient(135deg,#f9fafb,#f4efe6)] p-4">
                            <div class="font-semibold"><?= htmlspecialchars($proximaSessao['titulo'] ?: (($proximaSessao['tipo_sessao'] ?? 'Sessao') . ' - ' . ($proximaSessao['grau_sessao'] ?? ''))) ?></div>
                            <div class="mt-1 text-sm text-slate-600"><?= htmlspecialchars((string) ($proximaSessao['data_hora_inicio'] ?? '')) ?></div>
                            <div class="mt-1 text-xs text-slate-500">Status: <?= htmlspecialchars((string) ($proximaSessao['status'] ?? '')) ?></div>
                        </div>
                    <?php else: ?>
                        <p class="mt-3 text-sm text-slate-600">Nenhuma sessao futura cadastrada.</p>
                    <?php endif; ?>
                </article>

                <article class="rounded-3xl border border-white/70 bg-white/90 p-6 shadow-sm">
                    <h2 class="font-display text-2xl font-semibold">Acoes Exclusivas do Veneravel Mestre</h2>
                    <p class="mt-1 text-sm text-slate-600">Decisoes de governanca sobre a agenda oficial da loja.</p>

                    <div class="mt-4 space-y-3">
                        <?php foreach (array_slice($sessoes, 0, 6) as $sessao): ?>
                            <?php $statusSessao = (string) ($sessao['status'] ?? ''); ?>
                            <div class="rounded-2xl border border-slate-200 p-4">
                                <div class="font-medium"><?= htmlspecialchars($sessao['titulo'] ?: (($sessao['tipo_sessao'] ?? 'Sessao') . ' - ' . ($sessao['grau_sessao'] ?? ''))) ?></div>
                                <div class="mt-1 text-sm text-slate-600"><?= htmlspecialchars((string) ($sessao['data_hora_inicio'] ?? '')) ?></div>
                                <div class="mt-2 flex flex-wrap gap-2 text-xs">
                                    <span class="rounded-full bg-slate-100 px-2 py-1 text-slate-700">Status: <?= htmlspecialchars($statusSessao) ?></span>
                                    <span class="rounded-full bg-slate-100 px-2 py-1 text-slate-700">Confirmados: <?= (int) ($sessao['total_confirmados'] ?? 0) ?></span>
                                </div>

                                <div class="mt-3 flex flex-wrap gap-2">
                                    <?php if (in_array($statusSessao, ['planejada', 'alterada'], true)): ?>
                                        <form method="POST" action="/veneravel/sessoes/publicar">
                                            <input type="hidden" name="sessao_id" value="<?= (int) $sessao['id'] ?>">
                                            <button type="submit" class="rounded-md bg-onix px-3 py-1.5 text-sm text-white">Publicar sessao</button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if (in_array($statusSessao, ['planejada', 'publicada', 'alterada'], true)): ?>
                                        <form method="POST" action="/veneravel/sessoes/cancelar">
                                            <input type="hidden" name="sessao_id" value="<?= (int) $sessao['id'] ?>">
                                            <button type="submit" class="rounded-md border border-vinho px-3 py-1.5 text-sm text-vinho">Cancelar sessao</button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($statusSessao === 'cancelada'): ?>
                                        <form method="POST" action="/veneravel/sessoes/reabrir">
                                            <input type="hidden" name="sessao_id" value="<?= (int) $sessao['id'] ?>">
                                            <button type="submit" class="rounded-md border border-slate-400 px-3 py-1.5 text-sm text-slate-700">Reabrir sessao</button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if (in_array($statusSessao, ['publicada', 'alterada'], true)): ?>
                                        <form method="POST" action="/veneravel/sessoes/realizar">
                                            <input type="hidden" name="sessao_id" value="<?= (int) $sessao['id'] ?>">
                                            <button type="submit" class="rounded-md bg-bronze px-3 py-1.5 text-sm font-medium text-slate-900">Marcar realizada</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </article>

                <article class="rounded-3xl border border-white/70 bg-white/90 p-6 shadow-sm">
                    <h2 class="font-display text-2xl font-semibold">Decisoes de Votacao</h2>
                    <p class="mt-1 text-sm text-slate-600">Abertura e encerramento das votacoes de balaustre sob responsabilidade do Veneravel Mestre.</p>

                    <div class="mt-4 space-y-3">
                        <?php foreach (array_slice($balaustresPendentesDecisao, 0, 12) as $balaustre): ?>
                            <?php $status = (string) ($balaustre['status'] ?? ''); ?>
                            <div class="rounded-2xl border border-slate-200 p-4">
                                <div class="font-medium"><?= htmlspecialchars($balaustre['numero_balaustre'] ?: 'Sem numero') ?></div>
                                <div class="text-sm text-slate-600"><?= htmlspecialchars($balaustre['sessao_titulo'] ?: (string) ($balaustre['data_hora_inicio'] ?? '')) ?></div>
                                <div class="mt-1 text-xs text-slate-500">Status atual: <?= htmlspecialchars($status) ?></div>

                                <div class="mt-3 flex flex-wrap gap-2">
                                    <?php if ($status === 'apto_votacao'): ?>
                                        <form method="POST" action="/secretaria/balaustres/abrir-votacao">
                                            <input type="hidden" name="balaustre_id" value="<?= (int) $balaustre['id'] ?>">
                                            <button type="submit" class="rounded-md bg-onix px-3 py-1.5 text-sm text-white">Abrir votacao</button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($status === 'em_votacao'): ?>
                                        <form method="POST" action="/secretaria/balaustres/encerrar-votacao">
                                            <input type="hidden" name="balaustre_id" value="<?= (int) $balaustre['id'] ?>">
                                            <button type="submit" class="rounded-md bg-bronze px-3 py-1.5 text-sm font-medium text-slate-900">Encerrar votacao</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php if ($balaustresPendentesDecisao === []): ?>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                                Nenhum balaustre pede decisao imediata neste momento.
                            </div>
                        <?php endif; ?>
                    </div>
                </article>

                <article class="rounded-3xl border border-white/70 bg-white/90 p-6 shadow-sm">
                    <h2 class="font-display text-2xl font-semibold">Sessoes que Pedem Atencao</h2>
                    <p class="mt-1 text-sm text-slate-600">Sessões com status sensível ou baixa confirmação para acompanhamento direto.</p>

                    <div class="mt-4 space-y-3">
                        <?php foreach (array_slice($sessoesPendentesAtencao, 0, 8) as $sessao): ?>
                            <div class="rounded-2xl border border-slate-200 p-4">
                                <div class="font-medium"><?= htmlspecialchars($sessao['titulo'] ?: (($sessao['tipo_sessao'] ?? 'Sessao') . ' - ' . ($sessao['grau_sessao'] ?? ''))) ?></div>
                                <div class="mt-1 text-sm text-slate-600"><?= htmlspecialchars((string) ($sessao['data_hora_inicio'] ?? '')) ?></div>
                                <div class="mt-2 flex flex-wrap gap-2 text-xs">
                                    <span class="rounded-full bg-slate-100 px-2 py-1 text-slate-700">Status: <?= htmlspecialchars((string) ($sessao['status'] ?? '')) ?></span>
                                    <span class="rounded-full bg-slate-100 px-2 py-1 text-slate-700">Confirmados: <?= (int) ($sessao['total_confirmados'] ?? 0) ?></span>
                                    <span class="rounded-full bg-slate-100 px-2 py-1 text-slate-700">Agape: <?= (int) ($sessao['total_agape'] ?? 0) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php if ($sessoesPendentesAtencao === []): ?>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                                Nenhuma sessao precisa de atencao adicional agora.
                            </div>
                        <?php endif; ?>
                    </div>
                </article>
            </section>

            <aside class="space-y-6">
                <article class="rounded-3xl border border-white/70 bg-white/90 p-6 shadow-sm">
                    <h2 class="font-display text-2xl font-semibold">Nominata Principal</h2>
                    <p class="mt-1 text-sm text-slate-600">Visao rapida dos cargos mais sensiveis para a governanca da sessao.</p>

                    <div class="mt-4 max-h-[540px] space-y-2 overflow-y-auto pr-1">
                        <?php foreach ($nominataPrincipal as $cargo): ?>
                            <div class="rounded-2xl border border-slate-200 px-3 py-2">
                                <div class="text-xs uppercase tracking-wide text-slate-500"><?= htmlspecialchars((string) ($cargo['codigo'] ?? '')) ?></div>
                                <div class="text-sm font-medium"><?= htmlspecialchars((string) ($cargo['nome_exibicao'] ?? 'Cargo')) ?></div>
                                <div class="text-sm text-slate-700"><?= htmlspecialchars(trim((string) ($cargo['titular_nome'] ?? '')) ?: 'A definir') ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </article>

                <article class="rounded-3xl border border-white/70 bg-[linear-gradient(180deg,#fff,#f6f1e8)] p-6 shadow-sm">
                    <h2 class="font-display text-2xl font-semibold">Direcao do Cargo</h2>
                    <ul class="mt-4 space-y-2 text-sm text-slate-700">
                        <li>Abrir votacoes apenas quando o balaustre estiver apto e a presenca da sessao estiver consistente.</li>
                        <li>Encerrar votacoes observando pendencias de correcoes antes de consolidar o resultado.</li>
                        <li>Monitorar sessoes com baixa adesao para intervir cedo na comunicacao da loja.</li>
                    </ul>
                </article>
            </aside>
        </div>
    </div>
</body>
</html>
