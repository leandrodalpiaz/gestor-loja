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
    <title>Orador - Gestor da Loja</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        onix: '#162033',
                        ouro: '#b58a2c',
                        pergaminho: '#f4efe3'
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
<body class="min-h-screen bg-[linear-gradient(180deg,#f8f4ea_0%,#eef2f7_100%)] font-sans text-slate-900">
    <div class="mx-auto max-w-6xl px-4 py-8">
        <header class="mb-8 rounded-3xl border border-white/40 bg-[radial-gradient(circle_at_top_left,#dbc07c,transparent_30%),linear-gradient(135deg,#162033,#243147)] px-6 py-7 text-white shadow-2xl">
            <p class="text-xs uppercase tracking-[0.24em] text-amber-300">Painel do Orador</p>
            <h1 class="mt-2 font-display text-3xl">Leitura ritual e visitantes</h1>
            <p class="mt-2 max-w-2xl text-sm text-slate-200">Visao preparada para apoiar a leitura resumida da sessao e o agradecimento nominal aos visitantes na palavra a bem da ordem.</p>
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

        <div class="grid gap-6 lg:grid-cols-[1.05fr_0.95fr]">
            <section class="space-y-6">
                <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="font-display text-2xl text-onix">Proxima sessao</h2>
                    <?php if ($proximaSessao): ?>
                        <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div class="text-lg font-semibold text-onix"><?= htmlspecialchars($proximaSessao['titulo'] ?: (($proximaSessao['tipo_sessao'] ?? 'Sessao') . ' - ' . ($proximaSessao['grau_sessao'] ?? ''))) ?></div>
                            <div class="mt-1 text-sm text-slate-600"><?= htmlspecialchars((string) ($proximaSessao['data_hora_inicio'] ?? '')) ?></div>
                            <div class="mt-3 flex flex-wrap gap-2 text-xs">
                                <span class="rounded-full bg-white px-3 py-1 text-slate-700">Grau: <?= htmlspecialchars((string) ($proximaSessao['grau_sessao'] ?? '-')) ?></span>
                                <span class="rounded-full bg-white px-3 py-1 text-slate-700">Tipo: <?= htmlspecialchars((string) ($proximaSessao['tipo_sessao'] ?? '-')) ?></span>
                                <span class="rounded-full bg-white px-3 py-1 text-slate-700">Status: <?= htmlspecialchars((string) ($proximaSessao['status'] ?? '-')) ?></span>
                            </div>
                            <div class="mt-3 text-sm text-slate-700">
                                <strong>Resumo da sessao:</strong>
                                <?= htmlspecialchars((string) ($proximaSessao['ordem_dia'] ?? $proximaSessao['resumo_publico'] ?? 'Sem resumo registrado.')) ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">Nenhuma sessao futura cadastrada.</div>
                    <?php endif; ?>
                </article>

                <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="font-display text-2xl text-onix">Visitantes para leitura</h2>
                    <p class="mt-2 text-sm text-slate-600">Esta lista resumida deve ser usada pelo Orador no momento de agradecer e citar nominalmente os visitantes.</p>

                    <?php if ($visitantesResumo !== []): ?>
                        <div class="mt-4 space-y-3">
                            <?php foreach ($visitantesResumo as $visitante): ?>
                                <div class="rounded-2xl border border-amber-200 bg-[linear-gradient(135deg,#fffdf7,#f7f0df)] p-4">
                                    <div class="font-semibold text-onix"><?= htmlspecialchars((string) ($visitante['nome'] ?? 'Visitante')) ?></div>
                                    <div class="mt-1 text-sm text-slate-700"><?= htmlspecialchars((string) ($visitante['linha_resumida'] ?? '')) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">Ainda nao ha lista resumida de visitantes registrada no balaustre desta sessao.</div>
                    <?php endif; ?>
                </article>
            </section>

            <aside class="space-y-6">
                <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="font-display text-2xl text-onix">Uso do cargo</h2>
                    <ul class="mt-4 space-y-2 text-sm text-slate-700">
                        <li>Receber do Chanceler a nominata resumida dos visitantes.</li>
                        <li>Usar a lista para leitura no momento de agradecimento aos visitantes.</li>
                        <li>Atuar com base na sessao e no balaustre, sem redigitar visitantes.</li>
                    </ul>
                </article>

                <article class="rounded-3xl border border-slate-200 bg-[linear-gradient(180deg,#fff,#f4efe3)] p-6 shadow-sm">
                    <h2 class="font-display text-2xl text-onix">Agenda futura</h2>
                    <div class="mt-4 space-y-3">
                        <?php foreach ($sessoes as $sessao): ?>
                            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                                <div class="font-medium text-onix"><?= htmlspecialchars($sessao['titulo'] ?: (($sessao['tipo_sessao'] ?? 'Sessao') . ' - ' . ($sessao['grau_sessao'] ?? ''))) ?></div>
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
