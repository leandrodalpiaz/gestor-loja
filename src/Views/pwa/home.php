<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

$links = is_array($links ?? null) ? $links : [];
$usuarioNome = (string) ($_SESSION['usuario_nome'] ?? 'Irmão');
$usuarioCargo = (string) ($_SESSION['usuario_cargo'] ?? '');
$tenantSlug = (string) ($_SESSION['tenant_slug'] ?? '');
$tenantName = trim((string) ($_SESSION['tenant_name'] ?? 'Oficina Digital'));
$logoUrl = \App\Core\Tenant\TenantAssetResolver::resolveLogo($tenantSlug);

$proximaSessao = $proximaSessao ?? null;
$proximaSessaoResposta = $proximaSessaoResposta ?? null;
$ultimosComunicados = is_array($ultimosComunicados ?? null) ? $ultimosComunicados : [];

$pwaPageTitle = 'Acesso PWA';
$pwaActiveTab = 'inicio';

ob_start();
?>

<div class="pwa-premium-page pwa-stack">
    <section class="pwa-card p-4">
        <div class="flex items-center gap-4">
            <?php if ($logoUrl && !str_contains($logoUrl, 'placeholder')): ?>
                <img src="<?= htmlspecialchars($logoUrl) ?>" alt="Logo da Loja"
                     class="h-14 w-14 shrink-0 rounded-2xl border border-white/10 bg-white p-1.5 object-contain shadow-lg shadow-slate-950/30">
            <?php endif; ?>
            <div class="min-w-0">
                <p class="pwa-eyebrow">Oficina Digital</p>
                <h2 class="mt-1 truncate text-2xl font-bold tracking-tight text-white">Olá, <?= htmlspecialchars($usuarioNome) ?></h2>
                <p class="pwa-muted mt-0.5 truncate text-sm font-medium">
                    <?= $usuarioCargo !== '' ? htmlspecialchars($usuarioCargo) . ' · ' : '' ?><?= htmlspecialchars($tenantName !== '' ? $tenantName : 'Acesso rápido') ?>
                </p>
            </div>
        </div>
    </section>

    <?php if ($proximaSessao): ?>
        <?php
        $dataFormatada = '';
        $horaFormatada = '';
        $diaSemana = '';
        if (!empty($proximaSessao['data_hora_inicio'])) {
            try {
                $dtObj = new DateTimeImmutable($proximaSessao['data_hora_inicio'], new DateTimeZone('America/Sao_Paulo'));
                $diasSemana = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'];
                $diaSemana = $diasSemana[(int) $dtObj->format('w')];
                $dataFormatada = $dtObj->format('d/m/Y');
                $horaFormatada = $dtObj->format('H:i');
            } catch (Throwable $e) {
                $dataFormatada = (string) $proximaSessao['data_hora_inicio'];
            }
        }

        $tituloSessao = trim((string) ($proximaSessao['titulo'] ?? 'Sessão'));
        $sessaoModel = new \App\Models\Sessao();
        $tipoDesc = $sessaoModel->obterDescricaoTipoSessao($proximaSessao);
        $trajeDesc = $sessaoModel->obterDescricaoTraje($proximaSessao);
        $agapeDesc = $sessaoModel->obterDescricaoAgape($proximaSessao);
        $grau = trim((string) ($proximaSessao['grau_sessao'] ?? ''));
        $ordemDia = trim((string) ($proximaSessao['ordem_dia'] ?? $proximaSessao['resumo_publico'] ?? ''));
        $totalConfirmados = (int) ($proximaSessao['total_confirmados'] ?? 0);

        $statusConf = (string) ($proximaSessaoResposta['status_confirmacao'] ?? '');
        $participaAgape = (bool) ($proximaSessaoResposta['participara_agape'] ?? false);
        $badgeConf = match ($statusConf) {
            'confirmado' => $participaAgape
                ? ['Confirmado com ágape', 'border-emerald-300/30 bg-emerald-400/15 text-emerald-100']
                : ['Confirmado', 'border-emerald-300/30 bg-emerald-400/15 text-emerald-100'],
            'ausente' => ['Ausente', 'border-rose-300/30 bg-rose-400/15 text-rose-100'],
            default => ['Sem resposta', 'border-amber-300/30 bg-amber-400/15 text-amber-100'],
        };
        ?>

        <section class="pwa-hero p-5">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <div class="pwa-glass inline-flex items-center gap-2 rounded-full px-3 py-1 text-[0.62rem] font-bold uppercase tracking-[0.24em] text-amber-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Próxima Sessão
                    </div>
                    <h3 class="mt-4 text-2xl font-bold leading-tight tracking-tight text-white"><?= htmlspecialchars($tituloSessao) ?></h3>
                </div>
                <span class="pwa-glass shrink-0 rounded-2xl px-3 py-2 text-center text-xs font-semibold text-slate-200">
                    <?= $horaFormatada !== '' ? htmlspecialchars($horaFormatada) : 'A definir' ?>
                </span>
            </div>

            <div class="mt-5 grid grid-cols-2 gap-3">
                <div class="pwa-glass rounded-2xl p-3">
                    <p class="pwa-muted text-[0.65rem] font-semibold uppercase tracking-[0.18em]">Data</p>
                    <p class="mt-1 text-base font-bold text-white"><?= htmlspecialchars($dataFormatada !== '' ? $dataFormatada : 'A definir') ?></p>
                    <?php if ($diaSemana !== ''): ?>
                        <p class="pwa-muted mt-0.5 text-xs font-medium"><?= htmlspecialchars($diaSemana) ?></p>
                    <?php endif; ?>
                </div>
                <div class="pwa-glass rounded-2xl p-3">
                    <p class="pwa-muted text-[0.65rem] font-semibold uppercase tracking-[0.18em]">Ágape</p>
                    <p class="mt-1 text-base font-bold text-white"><?= htmlspecialchars($agapeDesc !== '' ? $agapeDesc : 'A definir') ?></p>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <?php if ($tipoDesc !== ''): ?>
                    <span class="pwa-glass rounded-full px-3 py-1.5 text-[0.68rem] font-semibold uppercase tracking-wide text-slate-200"><?= htmlspecialchars($tipoDesc) ?></span>
                <?php endif; ?>
                <?php if ($trajeDesc !== ''): ?>
                    <span class="pwa-glass rounded-full px-3 py-1.5 text-[0.68rem] font-semibold uppercase tracking-wide text-slate-200">Traje: <?= htmlspecialchars($trajeDesc) ?></span>
                <?php endif; ?>
                <?php if ($grau !== ''): ?>
                    <span class="pwa-glass rounded-full px-3 py-1.5 text-[0.68rem] font-semibold uppercase tracking-wide text-slate-200"><?= htmlspecialchars($grau) ?></span>
                <?php endif; ?>
            </div>

            <?php if ($ordemDia !== ''): ?>
                <details class="pwa-glass group mt-4 rounded-2xl">
                    <summary class="flex cursor-pointer items-center justify-between px-4 py-3 text-sm font-semibold text-slate-100">
                        Ordem do dia
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform group-open:rotate-180" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </summary>
                    <div class="px-4 pb-4 text-sm leading-relaxed text-slate-300 whitespace-pre-line"><?= htmlspecialchars($ordemDia) ?></div>
                </details>
            <?php endif; ?>

            <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
                <span class="pwa-confirm-pill inline-flex items-center rounded-full px-3 py-1.5 text-[0.64rem] font-bold uppercase tracking-[0.18em]">
                    <?= htmlspecialchars($badgeConf[0]) ?>
                </span>
                <a href="/pwa/sessoes"
                   class="pwa-cta inline-flex items-center gap-2 rounded-2xl px-4 py-3 text-sm font-bold transition active:scale-95">
                    <?= $statusConf === '' ? 'Confirmar Presença' : 'Ver Detalhes' ?>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>

            <?php if ($totalConfirmados > 0): ?>
                <p class="pwa-muted mt-3 text-xs font-medium"><?= $totalConfirmados ?> irmão(s) confirmado(s)</p>
            <?php endif; ?>
        </section>
    <?php else: ?>
        <section class="pwa-hero p-6">
            <div class="flex items-center gap-4">
                <div class="pwa-glass flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl text-amber-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <div>
                    <p class="pwa-eyebrow">Próxima Sessão</p>
                    <h3 class="mt-1 text-2xl font-bold tracking-tight text-white">Nenhuma sessão futura</h3>
                    <p class="pwa-muted mt-1 text-sm font-medium">Não há sessões publicadas no momento.</p>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php if (!empty($ultimosComunicados)): ?>
        <section>
            <div class="mb-3 flex items-center justify-between">
                <div>
                    <p class="pwa-eyebrow">Comunicação</p>
                    <h3 class="mt-1 text-lg font-bold text-white">Mural de Avisos</h3>
                </div>
                <a href="/pwa/comunicacao" class="pwa-glass rounded-full px-3 py-1.5 text-xs font-semibold text-slate-300 transition hover:text-white">Ver todos</a>
            </div>

            <div class="pwa-carousel pwa-scrollbar-none">
                <?php foreach ($ultimosComunicados as $comunicado): ?>
                    <?php
                    $cId = (int) ($comunicado['id'] ?? 0);
                    $cTitulo = (string) ($comunicado['titulo'] ?? 'Comunicado');
                    $cCategoria = (string) ($comunicado['categoria'] ?? 'geral');
                    $cPublicadoEm = (string) ($comunicado['publicado_em'] ?? '');
                    $cLido = (bool) ($comunicado['lido_pelo_usuario'] ?? false);
                    $cLeituras = (int) ($comunicado['total_leituras'] ?? 0);

                    $cDataCurta = '';
                    if ($cPublicadoEm !== '') {
                        try {
                            $cDt = new DateTimeImmutable($cPublicadoEm);
                            $cDataCurta = $cDt->format('d/m');
                        } catch (Throwable $e) {
                            $cDataCurta = $cPublicadoEm;
                        }
                    }
                    ?>
                    <a href="/pwa/comunicacao/ler?id=<?= $cId ?>"
                       class="pwa-carousel-card rounded-2xl border p-4 transition active:scale-[0.99] <?= $cLido ? 'pwa-card' : 'border-amber-300/30 bg-amber-300/10' ?>">
                        <div class="flex items-start justify-between gap-3">
                            <h4 class="line-clamp-2 text-base font-bold leading-snug text-white"><?= htmlspecialchars($cTitulo) ?></h4>
                            <span class="pwa-status-pill shrink-0 px-2.5 py-1 text-[0.6rem] font-bold uppercase tracking-[0.18em]">
                                <?= $cLido ? 'Lido' : 'Novo' ?>
                            </span>
                        </div>
                        <div class="pwa-muted mt-4 flex flex-wrap items-center gap-2 text-[0.68rem] font-semibold uppercase tracking-[0.16em]">
                            <span><?= htmlspecialchars($cCategoria) ?></span>
                            <?php if ($cDataCurta !== ''): ?>
                                <span class="text-slate-600">/</span>
                                <span><?= htmlspecialchars($cDataCurta) ?></span>
                            <?php endif; ?>
                            <span class="text-slate-600">/</span>
                            <span><?= $cLeituras ?> leitura(s)</span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <section>
        <div class="mb-3">
            <p class="pwa-eyebrow">Acesso rápido</p>
            <h3 class="mt-1 text-lg font-bold text-white">Módulos principais</h3>
        </div>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
            <?php
            $renderIcon = function (string $href, string $label, string $description, string $iconPath): void {
                $baseClasses = 'pwa-module-card flex flex-col justify-between p-4 text-left transition active:scale-[0.98]';
                echo "<a href='{$href}' class='{$baseClasses}'>";
                echo "<div class='pwa-glass flex h-11 w-11 items-center justify-center rounded-2xl text-amber-200'><svg xmlns='http://www.w3.org/2000/svg' class='h-6 w-6' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='1.9'>{$iconPath}</svg></div>";
                echo "<div>";
                echo "<div class='text-base font-bold leading-tight text-white'>{$label}</div>";
                echo "<p class='pwa-muted mt-1 text-xs font-medium leading-snug'>{$description}</p>";
                echo "</div>";
                echo "</a>";
            };

            if (!empty($links['sessoes'])) {
                $renderIcon('/pwa/sessoes', 'Sessões', 'Frequência e ágape', '<path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />');
            }
            if (!empty($links['biblioteca'])) {
                $renderIcon('/pwa/biblioteca', 'Biblioteca', 'Consultar acervo', '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />');
            }
            if (!empty($links['comunicacao'])) {
                $renderIcon('/pwa/comunicacao', 'Comunicação', 'Recados oficiais', '<path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />');
            }
            if (!empty($links['tesouraria'])) {
                $renderIcon('/pwa/tesouraria', 'Tesouraria', 'Caixa e obrigações', '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .672-3 1.5S10.343 11 12 11s3 .672 3 1.5S13.657 14 12 14m0-6v6m0 0v2m8-6a8 8 0 11-16 0 8 8 0 0116 0z" />');
            }
            if (!empty($links['chancelaria'])) {
                $renderIcon('/pwa/chancelaria', 'Chancelaria', 'Presença e visitantes', '<path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5V4H2v16h5m10 0v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6m10 0H7" />');
            }
            if (!empty($_SESSION['is_system_admin']) && !empty($_ENV['FEATURE_PWA_ADMIN_CRUD']) && filter_var((string) $_ENV['FEATURE_PWA_ADMIN_CRUD'], FILTER_VALIDATE_BOOL)) {
                $renderIcon('/pwa/admin', 'Sistema', 'Ajustes técnicos', '<path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />');
            }
            foreach ((array) ($links['cargo_modules'] ?? []) as $cargoModule) {
                $renderIcon(
                    htmlspecialchars((string) ($cargoModule['href'] ?? '/pwa/admin')),
                    htmlspecialchars((string) ($cargoModule['label'] ?? 'Cargo')),
                    htmlspecialchars((string) ($cargoModule['description'] ?? 'Módulo do cargo')),
                    '<path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />'
                );
            }
            ?>
        </div>
    </section>

    <p class="pwa-muted text-center text-xs font-medium">
        Instale na tela inicial para abrir como app.
    </p>
</div>

<?php
$pwaContent = ob_get_clean();
require __DIR__ . '/shell.php';
?>
