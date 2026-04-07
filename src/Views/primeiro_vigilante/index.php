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
    <title>1º Vigilante - Painel de Instrução</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        ardosia: '#162131',
                        dourado: '#bc9a43',
                        pergaminho: '#f5efe2',
                        pinho: '#315348',
                        areia: '#ebe2d2'
                    },
                    fontFamily: {
                        display: ['"Cormorant Garamond"', 'serif'],
                        sans: ['"Inter"', 'sans-serif']
                    }
                }
            }
        }
    </script>
</head>
<body class="min-h-screen bg-[radial-gradient(circle_at_top,#f8f3e7_0%,#eef1f4_45%,#e7eaef_100%)] font-sans text-slate-900">
    <div class="mx-auto max-w-7xl px-4 py-8">
        <header class="overflow-hidden rounded-3xl border border-white/50 bg-[radial-gradient(circle_at_top_left,#d6bb7d,transparent_30%),linear-gradient(135deg,#162131,#203349_55%,#315348)] px-6 py-7 text-white shadow-2xl">
            <p class="text-xs uppercase tracking-[0.22em] text-amber-200">Coluna do Norte</p>
            <h1 class="mt-2 font-display text-4xl font-bold">Painel do 1º Vigilante</h1>
            <p class="mt-2 max-w-3xl text-sm text-slate-200">
                Acompanhamento formativo dos Aprendizes, com foco na trilha de estudos, orientação das instruções,
                recebimento dos trabalhos e preparo para a conclusão da docência maçônica.
            </p>
            <div class="mt-5 flex flex-wrap gap-2">
                <a href="/dashboard" class="rounded-md bg-white/10 px-3 py-2 text-sm hover:bg-white/20">Voltar ao dashboard</a>
                <a href="/obreiros" class="rounded-md bg-amber-300 px-3 py-2 text-sm font-semibold text-slate-900 hover:bg-amber-200">Ver Aprendizes</a>
                <a href="/biblioteca" class="rounded-md bg-white/10 px-3 py-2 text-sm hover:bg-white/20">Biblioteca e classificação</a>
            </div>
        </header>

        <?php if ($mensagemSucesso): ?>
            <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-700"><?= htmlspecialchars($mensagemSucesso) ?></div>
        <?php endif; ?>
        <?php if ($mensagemErro): ?>
            <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-rose-700"><?= htmlspecialchars($mensagemErro) ?></div>
        <?php endif; ?>
        <?php if (!empty($avisoInfra)): ?>
            <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-amber-800"><?= htmlspecialchars((string) $avisoInfra) ?></div>
        <?php endif; ?>

        <section class="mt-6 grid gap-4 md:grid-cols-4">
            <article class="rounded-2xl border border-white/70 bg-white/90 p-5 shadow-sm">
                <div class="text-sm text-slate-500">Aprendizes ativos</div>
                <div class="mt-2 text-3xl font-bold text-ardosia"><?= (int) ($resumo['aprendizes_ativos'] ?? 0) ?></div>
            </article>
            <article class="rounded-2xl border border-white/70 bg-white/90 p-5 shadow-sm">
                <div class="text-sm text-slate-500">Na etapa inicial</div>
                <div class="mt-2 text-3xl font-bold text-ardosia"><?= (int) ($resumo['etapa_inicial'] ?? 0) ?></div>
            </article>
            <article class="rounded-2xl border border-white/70 bg-white/90 p-5 shadow-sm">
                <div class="text-sm text-slate-500">Trabalhos aguardando recebimento</div>
                <div class="mt-2 text-3xl font-bold text-ardosia"><?= (int) ($resumo['trabalhos_aguardando_recebimento'] ?? 0) ?></div>
            </article>
            <article class="rounded-2xl border border-white/70 bg-white/90 p-5 shadow-sm">
                <div class="text-sm text-slate-500">Aptos para certificado</div>
                <div class="mt-2 text-3xl font-bold text-ardosia"><?= (int) ($resumo['aptos_certificado'] ?? 0) ?></div>
            </article>
        </section>

        <div class="mt-6 grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
            <section class="space-y-6">
                <article class="rounded-3xl border border-white/70 bg-white/90 p-6 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="font-display text-2xl font-semibold">Aprendizes em acompanhamento</h2>
                            <p class="mt-1 text-sm text-slate-600">Primeira entrega do painel com trilha-base pronta para evolução nas próximas fases.</p>
                        </div>
                        <div class="rounded-full bg-amber-100 px-3 py-1 text-xs font-medium text-amber-800">Fundação inicial</div>
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Aprendiz</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Iniciação</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Etapa atual</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Status</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Próxima ação</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($aprendizes as $aprendiz): ?>
                                    <tr class="align-top">
                                        <td class="px-4 py-3 text-sm text-slate-900">
                                            <div class="font-medium"><?= htmlspecialchars((string) ($aprendiz['nome_historico'] ?? $aprendiz['nome'] ?? 'Aprendiz')) ?></div>
                                            <div class="text-xs text-slate-500">CIM <?= htmlspecialchars((string) ($aprendiz['cim'] ?? '-')) ?></div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-slate-700">
                                            <?= !empty($aprendiz['data_iniciacao']) ? htmlspecialchars(date('d/m/Y', strtotime((string) $aprendiz['data_iniciacao']))) : 'Não informada' ?>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-slate-700">
                                            <div class="font-medium">Etapa <?= (int) ($aprendiz['trilha_etapa_atual'] ?? 1) ?></div>
                                            <div class="text-xs text-slate-500"><?= htmlspecialchars((string) ($aprendiz['trilha_titulo_atual'] ?? '')) ?></div>
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700">
                                                <?= htmlspecialchars((string) ($aprendiz['trilha_status_atual'] ?? 'nao_iniciado')) ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-slate-700">
                                            <?= htmlspecialchars((string) ($aprendiz['trilha_proxima_acao'] ?? 'A definir')) ?>
                                            <div class="mt-2">
                                                <a href="/primeiro-vigilante/aprendiz?id=<?= urlencode((string) ($aprendiz['id'] ?? '')) ?>" class="text-xs font-medium text-amber-700 hover:underline">
                                                    Abrir linha do tempo
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if ($aprendizes === []): ?>
                                    <tr>
                                        <td colspan="5" class="px-4 py-6 text-center text-sm text-slate-500">Nenhum Aprendiz ativo encontrado.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </article>
            </section>

            <aside class="space-y-6">
                <article class="rounded-3xl border border-white/70 bg-white/90 p-6 shadow-sm">
                    <h2 class="font-display text-2xl font-semibold">Titular do cargo</h2>
                    <div class="mt-4 rounded-2xl border border-slate-200 bg-[linear-gradient(180deg,#fff,#f6f0e5)] p-4">
                        <div class="text-xs uppercase tracking-wide text-slate-500">PRIMEIRO_VIGILANTE</div>
                        <div class="mt-2 text-lg font-semibold text-ardosia">
                            <?= htmlspecialchars(trim((string) ($titularCargo['titular_nome'] ?? '')) ?: 'A definir') ?>
                        </div>
                        <div class="mt-1 text-sm text-slate-600">Cargo orientado à instrução, revisão de trabalhos e incentivo ao estudo dos Aprendizes.</div>
                    </div>
                </article>

                <article class="rounded-3xl border border-white/70 bg-white/90 p-6 shadow-sm">
                    <h2 class="font-display text-2xl font-semibold">Trilha de estudo</h2>
                    <div class="mt-4 space-y-3">
                        <?php foreach ($trilhaEstudo as $ordem => $titulo): ?>
                            <div class="rounded-2xl border border-slate-200 px-4 py-3">
                                <div class="text-xs uppercase tracking-wide text-slate-500">Etapa <?= (int) $ordem ?></div>
                                <div class="text-sm font-medium text-slate-800"><?= htmlspecialchars($titulo) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </article>

                <article class="rounded-3xl border border-white/70 bg-[linear-gradient(180deg,#fff,#f6f1e8)] p-6 shadow-sm">
                    <h2 class="font-display text-2xl font-semibold">Próximo passo</h2>
                    <p class="mt-3 text-sm text-slate-700">
                        A próxima fase vai conectar essa trilha ao banco para salvar progresso real, recebimento de trabalhos,
                        revisão das instruções, sugestões de leitura e solicitação do certificado final.
                    </p>
                </article>
            </aside>
        </div>
    </div>
</body>
</html>
