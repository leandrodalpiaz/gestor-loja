<?php
$mensagemSucesso = $_SESSION['mensagem_sucesso'] ?? null;
$mensagemErro = $_SESSION['mensagem_erro'] ?? null;
unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']);

$formatarData = static function (?string $valor): string {
    if (!$valor) {
        return '-';
    }
    $timestamp = strtotime($valor);
    if ($timestamp === false) {
        return (string) $valor;
    }
    return date('d/m/Y H:i', $timestamp);
};

$tituloSessao = static function (?array $sessao): string {
    if (!$sessao) {
        return 'Nenhuma sessao em foco';
    }
    $titulo = trim((string) ($sessao['titulo'] ?? ''));
    if ($titulo !== '') {
        return $titulo;
    }
    return trim(((string) ($sessao['tipo_sessao'] ?? 'Sessao')) . ' - ' . ((string) ($sessao['grau_sessao'] ?? '')));
};
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
                        pergaminho: '#f4efe3',
                        vinho: '#6f1d1b'
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
    <div class="mx-auto max-w-7xl px-4 py-8">
        <header class="mb-8 rounded-3xl border border-white/40 bg-[radial-gradient(circle_at_top_left,#dbc07c,transparent_30%),linear-gradient(135deg,#162033,#243147)] px-6 py-7 text-white shadow-2xl">
            <p class="text-xs uppercase tracking-[0.24em] text-amber-300">Painel do Orador</p>
            <h1 class="mt-2 font-display text-3xl">Pauta ritual, leitura e visitantes</h1>
            <p class="mt-2 max-w-3xl text-sm text-slate-200">Painel consolidado para apoiar a palavra a bem da ordem, a leitura ritual e a mencao correta de visitantes, cargos e eventos registrados na sessao.</p>
            <div class="mt-4 flex flex-wrap gap-2">
                <a href="/dashboard" class="rounded-md bg-amber-400 px-3 py-2 text-sm font-semibold text-slate-900 hover:bg-amber-300">Voltar ao dashboard</a>
                <a href="/miniapp/orador" class="rounded-md bg-white/10 px-3 py-2 text-sm hover:bg-white/20">Abrir miniapp</a>
            </div>
        </header>

        <?php if ($mensagemSucesso): ?>
            <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-700"><?= htmlspecialchars($mensagemSucesso) ?></div>
        <?php endif; ?>
        <?php if ($mensagemErro): ?>
            <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-rose-700"><?= htmlspecialchars($mensagemErro) ?></div>
        <?php endif; ?>

        <form method="get" action="/orador" class="mb-6 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                <div class="w-full lg:max-w-md">
                    <label for="sessao_id" class="mb-2 block text-sm font-medium text-slate-700">Sessao em foco</label>
                    <select id="sessao_id" name="sessao_id" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800">
                        <option value="">Usar proxima sessao publicada</option>
                        <?php foreach ($sessoes as $sessao): ?>
                            <option value="<?= (int) ($sessao['id'] ?? 0) ?>" <?= (int) ($sessaoEmFoco['id'] ?? 0) === (int) ($sessao['id'] ?? 0) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($tituloSessao($sessao)) ?> · <?= htmlspecialchars($formatarData($sessao['data_hora_inicio'] ?? null)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="rounded-xl bg-onix px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800">Atualizar contexto</button>
            </div>
        </form>

        <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
            <section class="space-y-6">
                <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="text-xs uppercase tracking-[0.22em] text-ouro">Sessao em foco</p>
                            <h2 class="mt-2 font-display text-2xl text-onix"><?= htmlspecialchars($tituloSessao($sessaoEmFoco)) ?></h2>
                            <p class="mt-2 text-sm text-slate-600"><?= htmlspecialchars($formatarData($sessaoEmFoco['data_hora_inicio'] ?? null)) ?></p>
                        </div>
                        <div class="flex flex-wrap gap-2 text-xs">
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-700">Grau: <?= htmlspecialchars((string) ($sessaoEmFoco['grau_sessao'] ?? '-')) ?></span>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-700">Tipo: <?= htmlspecialchars((string) ($sessaoEmFoco['tipo_sessao'] ?? '-')) ?></span>
                            <span class="rounded-full bg-amber-100 px-3 py-1 text-amber-900">Status: <?= htmlspecialchars((string) ($sessaoEmFoco['status'] ?? '-')) ?></span>
                        </div>
                    </div>
                    <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-sm font-semibold text-onix">Resumo ritual</div>
                        <p class="mt-2 text-sm leading-6 text-slate-700"><?= nl2br(htmlspecialchars((string) ($sessaoEmFoco['ordem_dia'] ?? $sessaoEmFoco['resumo_publico'] ?? 'Sem resumo ritual registrado para esta sessao.'))) ?></p>
                    </div>
                </article>

                <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="font-display text-2xl text-onix">Visitantes para leitura</h2>
                            <p class="mt-2 text-sm text-slate-600">Nominata resumida para saudacao nominal durante a palavra a bem.</p>
                        </div>
                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-medium text-amber-900"><?= count($visitantesResumo) ?> visitante(s)</span>
                    </div>

                    <?php if ($visitantesResumo !== []): ?>
                        <div class="mt-4 grid gap-3">
                            <?php foreach ($visitantesResumo as $visitante): ?>
                                <div class="rounded-2xl border border-amber-200 bg-[linear-gradient(135deg,#fffdf7,#f7f0df)] p-4">
                                    <div class="font-semibold text-onix"><?= htmlspecialchars((string) ($visitante['nome'] ?? 'Visitante')) ?></div>
                                    <div class="mt-1 text-sm text-slate-700"><?= htmlspecialchars((string) ($visitante['linha_resumida'] ?? 'Sem linha resumida registrada.')) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="mt-4 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-600">Nenhum visitante resumido foi registrado para a sessao em foco.</div>
                    <?php endif; ?>
                </article>

                <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="grid gap-6 lg:grid-cols-2">
                        <div>
                            <h2 class="font-display text-2xl text-onix">Cargos e composicao</h2>
                            <p class="mt-2 text-sm text-slate-600">Apoio rapido para leitura coerente da ocupacao da sessao.</p>
                            <?php if ($cargosSessao !== []): ?>
                                <div class="mt-4 space-y-3">
                                    <?php foreach ($cargosSessao as $cargo): ?>
                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                            <div class="font-semibold text-onix"><?= htmlspecialchars((string) ($cargo['cargo_nome'] ?? $cargo['codigo'] ?? 'Cargo')) ?></div>
                                            <div class="mt-1 text-sm text-slate-700"><?= htmlspecialchars((string) ($cargo['ocupante_nome'] ?? 'Sem ocupante definido')) ?></div>
                                            <div class="mt-2 text-xs text-slate-500">Tipo: <?= htmlspecialchars((string) ($cargo['tipo_ocupacao'] ?? 'regular')) ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="mt-4 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-600">Sem composicao de cargos capturada no balaustre.</div>
                            <?php endif; ?>
                        </div>

                        <div>
                            <h2 class="font-display text-2xl text-onix">Eventos registrados</h2>
                            <p class="mt-2 text-sm text-slate-600">Congressos, palestras e outros registros que podem merecer mencao.</p>
                            <?php if ($eventosSessao !== []): ?>
                                <div class="mt-4 space-y-3">
                                    <?php foreach ($eventosSessao as $evento): ?>
                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                            <div class="flex items-center justify-between gap-2">
                                                <div class="font-semibold text-onix"><?= htmlspecialchars((string) ($evento['titulo'] ?? 'Evento')) ?></div>
                                                <span class="rounded-full bg-vinho/10 px-3 py-1 text-xs font-medium uppercase tracking-wide text-vinho"><?= htmlspecialchars((string) ($evento['tipo'] ?? 'evento')) ?></span>
                                            </div>
                                            <div class="mt-1 text-sm text-slate-700"><?= htmlspecialchars((string) ($evento['linha'] ?? '')) ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="mt-4 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-600">Sem eventos ritualisticos registrados para esta sessao.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            </section>

            <aside class="space-y-6">
                <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="font-display text-2xl text-onix">Lembretes do cargo</h2>
                    <div class="mt-4 space-y-3">
                        <?php foreach ($lembretes as $lembrete): ?>
                            <div class="rounded-2xl border border-slate-200 bg-[linear-gradient(180deg,#fff,#f4efe3)] px-4 py-3 text-sm text-slate-700"><?= htmlspecialchars($lembrete) ?></div>
                        <?php endforeach; ?>
                    </div>
                </article>

                <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="font-display text-2xl text-onix">Uso do cargo</h2>
                    <ul class="mt-4 space-y-2 text-sm text-slate-700">
                        <li>Revisar a pauta resumida da sessao antes da leitura ritual.</li>
                        <li>Conferir visitantes e cargos ad hoc para mencao correta em Loja.</li>
                        <li>Usar os lembretes do painel como roteiro da palavra a bem.</li>
                        <li>Consultar o miniapp quando a leitura precisar ser feita pelo Telegram.</li>
                    </ul>
                </article>

                <article class="rounded-3xl border border-slate-200 bg-[linear-gradient(180deg,#fff,#f4efe3)] p-6 shadow-sm">
                    <h2 class="font-display text-2xl text-onix">Agenda futura</h2>
                    <div class="mt-4 space-y-3">
                        <?php foreach ($sessoes as $sessao): ?>
                            <a href="/orador?sessao_id=<?= (int) ($sessao['id'] ?? 0) ?>" class="block rounded-2xl border border-slate-200 bg-white px-4 py-3 transition hover:-translate-y-0.5 hover:shadow-sm">
                                <div class="font-medium text-onix"><?= htmlspecialchars($tituloSessao($sessao)) ?></div>
                                <div class="mt-1 text-sm text-slate-600"><?= htmlspecialchars($formatarData($sessao['data_hora_inicio'] ?? null)) ?></div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </article>
            </aside>
        </div>
    </div>
</body>
</html>
