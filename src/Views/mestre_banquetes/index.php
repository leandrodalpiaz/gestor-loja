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
    <title>Mestre de Banquetes - Gestor da Loja</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body class="min-h-screen bg-[linear-gradient(180deg,#f8f4ea_0%,#eef2f7_100%)] font-sans text-slate-900">
    <div class="mx-auto max-w-6xl px-4 py-8">
        <header class="mb-8 rounded-3xl border border-white/40 bg-[radial-gradient(circle_at_top_left,#d3b269,transparent_28%),linear-gradient(135deg,#172030,#27364a)] px-6 py-7 text-white shadow-2xl">
            <p class="text-xs uppercase tracking-[0.24em] text-amber-300">Painel do Mestre de Banquetes</p>
            <h1 class="mt-2 text-3xl font-semibold">Controle do agape e confirmados</h1>
            <p class="mt-2 max-w-2xl text-sm text-slate-200">A sessao fornece a base operacional para o planejamento do agape, separando os presentes confirmados entre com e sem participacao.</p>
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

        <div class="grid gap-6 lg:grid-cols-[1fr_1fr]">
            <section class="space-y-6">
                <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-2xl font-semibold text-slate-900">Proxima sessao</h2>
                    <?php if ($proximaSessao): ?>
                        <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div class="text-lg font-semibold text-slate-900"><?= htmlspecialchars($proximaSessao['titulo'] ?: (($proximaSessao['tipo_sessao'] ?? 'Sessao') . ' - ' . ($proximaSessao['grau_sessao'] ?? ''))) ?></div>
                            <div class="mt-1 text-sm text-slate-600"><?= htmlspecialchars((string) ($proximaSessao['data_hora_inicio'] ?? '')) ?></div>
                            <div class="mt-3 flex flex-wrap gap-2 text-xs">
                                <span class="rounded-full bg-white px-3 py-1 text-slate-700">Confirmados: <?= (int) ($proximaSessao['total_confirmados'] ?? 0) ?></span>
                                <span class="rounded-full bg-white px-3 py-1 text-slate-700">Agape: <?= (int) ($proximaSessao['total_agape'] ?? 0) ?></span>
                                <span class="rounded-full bg-white px-3 py-1 text-slate-700">Status: <?= htmlspecialchars((string) ($proximaSessao['status'] ?? '-')) ?></span>
                            </div>
                            <div class="mt-3 grid gap-2 text-xs text-slate-700">
                                <div>Configuracao do agape: <?= htmlspecialchars((string) ($descricaoAgape ?? 'Nao informado')) ?></div>
                                <div>Modelo financeiro: <?= htmlspecialchars((string) ($descricaoModeloFinanceiroAgape ?? 'Nao informado')) ?></div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">Nenhuma sessao futura cadastrada.</div>
                    <?php endif; ?>
                </article>

                <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-2xl font-semibold text-slate-900">Participantes do agape</h2>
                    <p class="mt-2 text-sm text-slate-600">Lista pratica dos confirmados que optaram por participar do agape.</p>
                    <div class="mt-4 space-y-3">
                        <?php if ($participantesAgape !== []): ?>
                            <?php foreach ($participantesAgape as $participante): ?>
                                <div class="rounded-2xl border border-amber-200 bg-[linear-gradient(135deg,#fffdf7,#f7f0df)] px-4 py-3">
                                    <div class="font-medium text-slate-900"><?= htmlspecialchars((string) ($participante['nome'] ?? 'Obreiro')) ?></div>
                                    <div class="mt-1 text-sm text-slate-600">CIM: <?= htmlspecialchars((string) ($participante['cim'] ?? '-')) ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">Ainda nao ha participantes confirmados com agape.</div>
                        <?php endif; ?>
                    </div>
                </article>
            </section>

            <aside class="space-y-6">
                <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-2xl font-semibold text-slate-900">Confirmados sem agape</h2>
                    <div class="mt-4 space-y-3">
                        <?php
                        $semAgape = array_values(array_filter(
                            $confirmados,
                            static fn (array $item): bool => empty($item['participara_agape'])
                        ));
                        ?>
                        <?php if ($semAgape !== []): ?>
                            <?php foreach ($semAgape as $confirmado): ?>
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                    <div class="font-medium text-slate-900"><?= htmlspecialchars((string) ($confirmado['nome'] ?? 'Obreiro')) ?></div>
                                    <div class="mt-1 text-sm text-slate-600">CIM: <?= htmlspecialchars((string) ($confirmado['cim'] ?? '-')) ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">Nao ha confirmados sem agape neste momento.</div>
                        <?php endif; ?>
                    </div>
                </article>

                <article class="rounded-3xl border border-slate-200 bg-[linear-gradient(180deg,#fff,#f4efe3)] p-6 shadow-sm">
                    <h2 class="text-2xl font-semibold text-slate-900">Agenda futura</h2>
                    <div class="mt-4 space-y-3">
                        <?php foreach ($sessoes as $sessao): ?>
                            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                                <div class="font-medium text-slate-900"><?= htmlspecialchars($sessao['titulo'] ?: (($sessao['tipo_sessao'] ?? 'Sessao') . ' - ' . ($sessao['grau_sessao'] ?? ''))) ?></div>
                                <div class="mt-1 text-sm text-slate-600"><?= htmlspecialchars((string) ($sessao['data_hora_inicio'] ?? '')) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </article>
            </aside>
        </div>
    </div>
</body>
</html>
