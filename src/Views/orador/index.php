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
<?php
$erpPageTitle = 'Orador - Gestor de Loja';
$appShellEyebrow = 'Orador';
$appShellTitle = 'Pauta ritual, leitura e visitantes';
$appShellDescription = 'Painel consolidado para apoiar a palavra a bem da ordem, a leitura ritual e a menção correta de visitantes, cargos e eventos registrados na sessão.';
$appShellActiveHref = '/orador';
$appShellActions = [
    ['label' => 'Abrir miniapp', 'href' => '/miniapp/orador'],
];
$appShellSidebarSections = [
    [
        'title' => 'Orador',
        'items' => [
            ['label' => 'Painel do Orador', 'href' => '/orador'],
        ],
    ],
    [
        'title' => 'Navegacao',
        'items' => [
            ['label' => 'Dashboard', 'href' => '/dashboard'],
        ],
    ],
];
require __DIR__ . '/../partials/erp_head.php';
?>
<?php require __DIR__ . '/../partials/erp_shell_open.php'; ?>

        <?php if ($mensagemSucesso): ?>
            <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-700"><?= htmlspecialchars($mensagemSucesso) ?></div>
        <?php endif; ?>
        <?php if ($mensagemErro): ?>
            <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-rose-700"><?= htmlspecialchars($mensagemErro) ?></div>
        <?php endif; ?>

        <form method="get" action="/orador" class="mb-6 rounded-2xl border border-erp-border bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                <div class="w-full lg:max-w-md">
                    <label for="sessao_id" class="mb-2 block text-sm font-medium text-erp-text">Sessao em foco</label>
                    <select id="sessao_id" name="sessao_id" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-erp-text">
                        <option value="">Usar proxima sessao publicada</option>
                        <?php foreach ($sessoes as $sessao): ?>
                            <option value="<?= (int) ($sessao['id'] ?? 0) ?>" <?= (int) ($sessaoEmFoco['id'] ?? 0) === (int) ($sessao['id'] ?? 0) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($tituloSessao($sessao)) ?> · <?= htmlspecialchars($formatarData($sessao['data_hora_inicio'] ?? null)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="rounded-xl bg-erp-navy px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800">Atualizar contexto</button>
            </div>
        </form>

        <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
            <section class="space-y-6">
                <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="text-xs uppercase tracking-[0.22em] text-erp-gold">Sessao em foco</p>
                            <h2 class="mt-2 font-sans text-2xl text-erp-navy"><?= htmlspecialchars($tituloSessao($sessaoEmFoco)) ?></h2>
                            <p class="mt-2 text-sm text-slate-700"><?= htmlspecialchars($formatarData($sessaoEmFoco['data_hora_inicio'] ?? null)) ?></p>
                        </div>
                        <div class="flex flex-wrap gap-2 text-xs">
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-700">Grau: <?= htmlspecialchars((string) ($sessaoEmFoco['grau_sessao'] ?? '-')) ?></span>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-700">Tipo: <?= htmlspecialchars((string) ($sessaoEmFoco['tipo_sessao'] ?? '-')) ?></span>
                            <span class="rounded-full bg-amber-100 px-3 py-1 text-amber-900">Status: <?= htmlspecialchars((string) ($sessaoEmFoco['status'] ?? '-')) ?></span>
                        </div>
                    </div>
                    <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-sm font-semibold text-erp-navy">Resumo ritual</div>
                        <p class="mt-2 text-sm leading-6 text-slate-700"><?= nl2br(htmlspecialchars((string) ($sessaoEmFoco['ordem_dia'] ?? $sessaoEmFoco['resumo_publico'] ?? 'Sem resumo ritual registrado para esta sessao.'))) ?></p>
                    </div>
                </article>

                <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="font-sans text-2xl text-erp-navy">Visitantes para leitura</h2>
                            <p class="mt-2 text-sm text-slate-700">Nominata resumida para saudacao nominal durante a palavra a bem.</p>
                        </div>
                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-medium text-amber-900"><?= count($visitantesResumo) ?> visitante(s)</span>
                    </div>

                    <?php if ($visitantesResumo !== []): ?>
                        <div class="mt-4 grid gap-3">
                            <?php foreach ($visitantesResumo as $visitante): ?>
                                <div class="rounded-2xl border border-amber-200 bg-[linear-gradient(135deg,#fffdf7,#f7f0df)] p-4">
                                    <div class="font-semibold text-erp-navy"><?= htmlspecialchars((string) ($visitante['nome'] ?? 'Visitante')) ?></div>
                                    <div class="mt-1 text-sm text-slate-700"><?= htmlspecialchars((string) ($visitante['linha_resumida'] ?? 'Sem linha resumida registrada.')) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="mt-4 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-700">Nenhum visitante resumido foi registrado para a sessao em foco.</div>
                    <?php endif; ?>
                </article>

                <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="grid gap-6 lg:grid-cols-2">
                        <div>
                            <h2 class="font-sans text-2xl text-erp-navy">Cargos e composicao</h2>
                            <p class="mt-2 text-sm text-slate-700">Apoio rapido para leitura coerente da ocupacao da sessao.</p>
                            <?php if ($cargosSessao !== []): ?>
                                <div class="mt-4 space-y-3">
                                    <?php foreach ($cargosSessao as $cargo): ?>
                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                            <div class="font-semibold text-erp-navy"><?= htmlspecialchars((string) ($cargo['cargo_nome'] ?? $cargo['codigo'] ?? 'Cargo')) ?></div>
                                            <div class="mt-1 text-sm text-slate-700"><?= htmlspecialchars((string) ($cargo['ocupante_nome'] ?? 'Sem ocupante definido')) ?></div>
                                            <div class="mt-2 text-xs text-slate-700">Tipo: <?= htmlspecialchars((string) ($cargo['tipo_ocupacao'] ?? 'regular')) ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="mt-4 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-700">Sem composicao de cargos capturada no balaustre.</div>
                            <?php endif; ?>
                        </div>

                        <div>
                            <h2 class="font-sans text-2xl text-erp-navy">Eventos registrados</h2>
                            <p class="mt-2 text-sm text-slate-700">Congressos, palestras e outros registros que podem merecer mencao.</p>
                            <?php if ($eventosSessao !== []): ?>
                                <div class="mt-4 space-y-3">
                                    <?php foreach ($eventosSessao as $evento): ?>
                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                            <div class="flex items-center justify-between gap-2">
                                                <div class="font-semibold text-erp-navy"><?= htmlspecialchars((string) ($evento['titulo'] ?? 'Evento')) ?></div>
                                                <span class="rounded-full bg-rose-800/10 px-3 py-1 text-xs font-medium uppercase tracking-wide text-rose-800"><?= htmlspecialchars((string) ($evento['tipo'] ?? 'evento')) ?></span>
                                            </div>
                                            <div class="mt-1 text-sm text-slate-700"><?= htmlspecialchars((string) ($evento['linha'] ?? '')) ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="mt-4 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-700">Sem eventos ritualisticos registrados para esta sessao.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            </section>

            <aside class="space-y-6">
                <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="font-sans text-2xl text-erp-navy">Lembretes do cargo</h2>
                    <div class="mt-4 space-y-3">
                        <?php foreach ($lembretes as $lembrete): ?>
                            <div class="rounded-2xl border border-slate-200 bg-[linear-gradient(180deg,#fff,#f4efe3)] px-4 py-3 text-sm text-slate-700"><?= htmlspecialchars($lembrete) ?></div>
                        <?php endforeach; ?>
                    </div>
                </article>

                <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="font-sans text-2xl text-erp-navy">Uso do cargo</h2>
                    <ul class="mt-4 space-y-2 text-sm text-slate-700">
                        <li>Revisar a pauta resumida da sessao antes da leitura ritual.</li>
                        <li>Conferir visitantes e cargos ad hoc para mencao correta em Loja.</li>
                        <li>Usar os lembretes do painel como roteiro da palavra a bem.</li>
                        <li>Consultar o miniapp quando a leitura precisar ser feita pelo Telegram.</li>
                    </ul>
                </article>

                <article class="rounded-2xl border border-slate-200 bg-[linear-gradient(180deg,#fff,#f4efe3)] p-6 shadow-sm">
                    <h2 class="font-sans text-2xl text-erp-navy">Agenda futura</h2>
                    <div class="mt-4 space-y-3">
                        <?php foreach ($sessoes as $sessao): ?>
                            <a href="/orador?sessao_id=<?= (int) ($sessao['id'] ?? 0) ?>" class="block rounded-2xl border border-slate-200 bg-white px-4 py-3 transition hover:-translate-y-0.5 hover:shadow-sm">
                                <div class="font-medium text-erp-navy"><?= htmlspecialchars($tituloSessao($sessao)) ?></div>
                                <div class="mt-1 text-sm text-slate-700"><?= htmlspecialchars($formatarData($sessao['data_hora_inicio'] ?? null)) ?></div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </article>
            </aside>
        </div>

<?php require __DIR__ . '/../partials/erp_shell_close.php'; ?>
