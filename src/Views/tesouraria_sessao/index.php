<?php
$mensagemSucesso = $_SESSION['mensagem_sucesso'] ?? null;
$mensagemErro = $_SESSION['mensagem_erro'] ?? null;
unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']);

$formatarMoeda = static function (float $valor): string {
    return 'R$ ' . number_format($valor, 2, ',', '.');
};

$sessaoFormatter = new \App\Models\Sessao();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tesouraria e Sessões - Gestor da Loja</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body class="min-h-screen bg-[linear-gradient(180deg,#f8f4ea_0%,#eef2f7_100%)] font-sans text-slate-900">
    <div class="mx-auto max-w-6xl px-4 py-8">
        <header class="mb-8 rounded-3xl border border-white/40 bg-[radial-gradient(circle_at_top_left,#8fd0aa,transparent_28%),linear-gradient(135deg,#14324a,#1f4d63)] px-6 py-7 text-white shadow-2xl">
            <p class="text-xs uppercase tracking-[0.24em] text-emerald-200">Tesouraria e Sessões</p>
            <h1 class="mt-2 text-3xl font-semibold">Reflexo financeiro do ágape</h1>
            <p class="mt-2 max-w-3xl text-sm text-slate-200">A sessão abastece a Tesouraria com a leitura do ágape pago, a quantidade confirmada, o valor unitário e a estimativa de arrecadação antes da realização.</p>
            <div class="mt-4 flex flex-wrap gap-2">
                <a href="/secretaria" class="rounded-md bg-white/10 px-3 py-2 text-sm hover:bg-white/20">Ir para Secretaria</a>
                <a href="/tesouraria/caixa" class="rounded-md bg-white/10 px-3 py-2 text-sm hover:bg-white/20">Caixa da Loja</a>
                <a href="/dashboard" class="rounded-md bg-emerald-300 px-3 py-2 text-sm font-semibold text-slate-900 hover:bg-emerald-200">Voltar ao Painel</a>
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
                    <h2 class="text-2xl font-semibold text-slate-900">Próxima sessão com leitura financeira</h2>
                    <?php if ($proximaSessao): ?>
                        <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div class="text-lg font-semibold text-slate-900"><?= htmlspecialchars($proximaSessao['titulo'] ?: (($proximaSessao['tipo_sessao'] ?? 'Sessão') . ' - ' . ($proximaSessao['grau_sessao'] ?? ''))) ?></div>
                            <div class="mt-1 text-sm text-slate-600"><?= htmlspecialchars((string) ($proximaSessao['data_hora_inicio'] ?? '')) ?></div>
                            <div class="mt-4 grid gap-3 md:grid-cols-2">
                                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                                    <div class="text-xs uppercase tracking-[0.18em] text-slate-500">Loja</div>
                                    <div class="mt-1 font-medium text-slate-900">
                                        <?= htmlspecialchars(trim((string) (($configuracaoLoja['nome_loja'] ?? '') . ((string) ($configuracaoLoja['numero_loja'] ?? '') !== '' ? ' nº ' . $configuracaoLoja['numero_loja'] : '')))) ?>
                                    </div>
                                </div>
                                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                                    <div class="text-xs uppercase tracking-[0.18em] text-slate-500">Ágape</div>
                                    <div class="mt-1 font-medium text-slate-900"><?= htmlspecialchars($sessaoFormatter->obterDescricaoAgape($proximaSessao)) ?></div>
                                </div>
                                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                                    <div class="text-xs uppercase tracking-[0.18em] text-slate-500">Modelo financeiro</div>
                                    <div class="mt-1 font-medium text-slate-900"><?= htmlspecialchars($sessaoFormatter->obterDescricaoModeloTesourariaAgape($proximaSessao)) ?></div>
                                </div>
                                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                                    <div class="text-xs uppercase tracking-[0.18em] text-slate-500">Confirmados com ágape</div>
                                    <div class="mt-1 text-2xl font-semibold text-slate-900"><?= count($participantesAgape) ?></div>
                                </div>
                                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                                    <div class="text-xs uppercase tracking-[0.18em] text-slate-500">Estimativa de arrecadação</div>
                                    <div class="mt-1 text-2xl font-semibold text-emerald-700"><?= $formatarMoeda($estimativaArrecadacao) ?></div>
                                </div>
                            </div>
                            <?php if (empty($proximaSessao['reflete_financeiro_oficial'])): ?>
                                <div class="mt-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                                    Esta sessão não gera reflexo automático no financeiro oficial da Loja.
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">Nenhuma sessão futura cadastrada.</div>
                    <?php endif; ?>
                </article>

                <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-2xl font-semibold text-slate-900">Participantes do ágape</h2>
                    <p class="mt-2 text-sm text-slate-600">Base operacional para conferência financeira da sessão, separando quem confirmou participação no ágape.</p>
                    <div class="mt-4 space-y-3">
                        <?php if ($participantesAgape !== []): ?>
                            <?php foreach ($participantesAgape as $participante): ?>
                                <div class="rounded-2xl border border-emerald-200 bg-[linear-gradient(135deg,#f7fffb,#eaf8f0)] px-4 py-3">
                                    <div class="font-medium text-slate-900"><?= htmlspecialchars((string) ($participante['nome'] ?? 'Obreiro')) ?></div>
                                    <div class="mt-1 text-sm text-slate-600">CIM: <?= htmlspecialchars((string) ($participante['cim'] ?? '-')) ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">Ainda não há confirmações com ágape para a próxima sessão.</div>
                        <?php endif; ?>
                    </div>
                </article>
            </section>

            <aside class="space-y-6">
                <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-2xl font-semibold text-slate-900">Agenda futura com foco financeiro</h2>
                    <div class="mt-4 space-y-3">
                        <?php if ($sessoesFinanceiras !== []): ?>
                            <?php foreach ($sessoesFinanceiras as $sessao): ?>
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                    <div class="font-medium text-slate-900"><?= htmlspecialchars($sessao['titulo'] ?: ($sessao['descricao_tipo'] ?: 'Sessão')) ?></div>
                                    <div class="mt-1 text-sm text-slate-600"><?= htmlspecialchars((string) ($sessao['data_hora_inicio'] ?? '')) ?></div>
                                    <div class="mt-3 grid gap-2 text-sm text-slate-700">
                                        <div>Tipo: <?= htmlspecialchars((string) ($sessao['descricao_tipo'] ?? '-')) ?></div>
                                        <div>Ágape: <?= htmlspecialchars((string) ($sessao['descricao_agape'] ?? '-')) ?></div>
                                        <div>Modelo financeiro: <?= htmlspecialchars((string) ($sessao['descricao_modelo_financeiro_agape'] ?? '-')) ?></div>
                                        <div>Confirmados com ágape: <?= (int) ($sessao['total_agape'] ?? 0) ?></div>
                                        <div>Estimativa: <?= $formatarMoeda((float) ($sessao['estimativa_arrecadacao'] ?? 0)) ?></div>
                                    </div>
                                    <?php if (empty($sessao['reflete_financeiro_oficial'])): ?>
                                        <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                                            Sem reflexo automatico no financeiro oficial da Loja.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">Nenhuma sessão futura disponível.</div>
                        <?php endif; ?>
                    </div>
                </article>

                <article class="rounded-3xl border border-slate-200 bg-[linear-gradient(180deg,#fff,#f4efe3)] p-6 shadow-sm">
                    <h2 class="text-2xl font-semibold text-slate-900">Uso pela Tesouraria</h2>
                    <div class="mt-4 space-y-3 text-sm text-slate-700">
                        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">O Secretário publica a sessão e define o modelo financeiro do ágape (oficial, particular ou misto).</div>
                        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">Os membros confirmam com ou sem ágape.</div>
                        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">A Tesouraria so consome automaticamente o que tiver reflexo oficial (oficial_loja ou parte oficial do misto).</div>
                        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">O lançamento financeiro detalhado continua no livro-caixa, sem perder a origem operacional da sessão.</div>
                    </div>
                </article>
            </aside>
        </div>
    </div>
</body>
</html>

