<?php
$mensagemSucesso = $_SESSION['mensagem_sucesso'] ?? null;
$mensagemErro = $_SESSION['mensagem_erro'] ?? null;
unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']);

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
</head>
<body class="min-h-screen bg-[linear-gradient(180deg,#f8f4ea_0%,#eef2f7_100%)] font-sans text-slate-900">
    <div class="mx-auto max-w-7xl px-4 py-8">
        <header class="mb-8 rounded-3xl border border-white/40 bg-[radial-gradient(circle_at_top_left,#d6b672,transparent_28%),linear-gradient(135deg,#162033,#223145)] px-6 py-7 text-white shadow-2xl">
            <p class="text-xs uppercase tracking-[0.24em] text-amber-300">Painel do Chanceler</p>
            <h1 class="mt-2 text-3xl font-semibold">Check-in do quadro e visitantes</h1>
            <p class="mt-2 max-w-3xl text-sm text-slate-200">Aqui o Chanceler usa a sessao como base de check-in dos presentes do quadro e acompanha a lista resumida de visitantes que sustentara o Secretario e o Orador.</p>
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

        <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
            <section class="space-y-6">
                <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-2xl font-semibold text-slate-900">Proxima sessao</h2>
                    <?php if ($proximaSessao): ?>
                        <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div class="text-lg font-semibold text-slate-900"><?= htmlspecialchars($proximaSessao['titulo'] ?: (($proximaSessao['tipo_sessao'] ?? 'Sessao') . ' - ' . ($proximaSessao['grau_sessao'] ?? ''))) ?></div>
                            <div class="mt-1 text-sm text-slate-600"><?= htmlspecialchars((string) ($proximaSessao['data_hora_inicio'] ?? '')) ?></div>
                            <div class="mt-3 flex flex-wrap gap-2 text-xs">
                                <span class="rounded-full bg-white px-3 py-1 text-slate-700">Confirmados: <?= count($confirmados) ?></span>
                                <span class="rounded-full bg-white px-3 py-1 text-slate-700">Presentes efetivos: <?= count(array_filter($mapaPresencas, static fn (array $r): bool => !empty($r['presente']))) ?></span>
                                <span class="rounded-full bg-white px-3 py-1 text-slate-700">Visitantes resumidos: <?= count($visitantesResumo) ?></span>
                                <span class="rounded-full bg-white px-3 py-1 text-slate-700">Agape: <?= htmlspecialchars($descricaoAgape($proximaSessao)) ?></span>
                                <span class="rounded-full bg-white px-3 py-1 text-slate-700">Modelo financeiro: <?= htmlspecialchars($descricaoModeloFinanceiroAgape($proximaSessao)) ?></span>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">Nenhuma sessao futura cadastrada.</div>
                    <?php endif; ?>
                </article>

                <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-2xl font-semibold text-slate-900">Check-in do quadro da Loja</h2>
                    <p class="mt-2 text-sm text-slate-600">Somente os presentes efetivos entram na base da votacao do balaustre.</p>
                    <div class="mt-4 grid gap-3 md:grid-cols-2">
                        <?php foreach ($mapaPresencas as $registro): ?>
                            <form method="POST" action="/chanceler/sessao/presenca" class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <input type="hidden" name="sessao_id" value="<?= (int) ($proximaSessao['id'] ?? 0) ?>">
                                <input type="hidden" name="obreiro_id" value="<?= htmlspecialchars((string) ($registro['id'] ?? '')) ?>">
                                <div class="font-medium text-slate-900"><?= htmlspecialchars((string) ($registro['nome'] ?? 'Obreiro')) ?></div>
                                <div class="mt-1 text-xs text-slate-600">CIM: <?= htmlspecialchars((string) ($registro['cim'] ?? '-')) ?> · Grau: <?= htmlspecialchars((string) ($registro['grau'] ?? '-')) ?></div>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <button type="submit" name="presente" value="1" class="rounded-md px-3 py-1.5 text-sm <?= !empty($registro['presente']) ? 'bg-emerald-600 text-white' : 'border border-emerald-300 text-emerald-700' ?>">Presente</button>
                                    <button type="submit" name="presente" value="0" class="rounded-md px-3 py-1.5 text-sm <?= empty($registro['presente']) ? 'bg-slate-700 text-white' : 'border border-slate-300 text-slate-700' ?>">Nao presente</button>
                                </div>
                            </form>
                        <?php endforeach; ?>
                    </div>
                </article>
            </section>

            <aside class="space-y-6">
                <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-2xl font-semibold text-slate-900">Visitantes resumidos</h2>
                    <p class="mt-2 text-sm text-slate-600">Esta lista alimenta o Secretario para o balaustre e o Orador para a leitura nominal.</p>
                    <div class="mt-4 space-y-3">
                        <?php if ($visitantesResumo !== []): ?>
                            <?php foreach ($visitantesResumo as $visitante): ?>
                                <div class="rounded-2xl border border-amber-200 bg-[linear-gradient(135deg,#fffdf7,#f7f0df)] px-4 py-3">
                                    <div class="font-medium text-slate-900"><?= htmlspecialchars((string) ($visitante['nome'] ?? 'Visitante')) ?></div>
                                    <div class="mt-1 text-sm text-slate-700"><?= htmlspecialchars((string) ($visitante['linha_resumida'] ?? '')) ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">Ainda nao ha lista resumida de visitantes registrada para esta sessao.</div>
                        <?php endif; ?>
                    </div>
                </article>

                <article class="rounded-3xl border border-slate-200 bg-[linear-gradient(180deg,#fff,#f4efe3)] p-6 shadow-sm">
                    <h2 class="text-2xl font-semibold text-slate-900">Confirmados da sessao</h2>
                    <div class="mt-4 space-y-3">
                        <?php if ($confirmados !== []): ?>
                            <?php foreach ($confirmados as $confirmado): ?>
                                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                                    <div class="font-medium text-slate-900"><?= htmlspecialchars((string) ($confirmado['nome'] ?? 'Obreiro')) ?></div>
                                    <div class="mt-1 text-sm text-slate-600"><?= !empty($confirmado['participara_agape']) ? 'Confirmado com agape' : 'Confirmado sem agape' ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">Ainda nao ha confirmados registrados.</div>
                        <?php endif; ?>
                    </div>
                </article>
            </aside>
        </div>
    </div>
</body>
</html>
