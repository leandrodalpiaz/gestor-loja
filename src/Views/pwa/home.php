<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

$links = is_array($links ?? null) ? $links : [];
$usuarioNome = (string) ($_SESSION['usuario_nome'] ?? 'Irmão');
$usuarioCargo = (string) ($_SESSION['usuario_cargo'] ?? '');
$tenantSlug = (string) ($_SESSION['tenant_slug'] ?? '');
$logoUrl = \App\Core\Tenant\TenantAssetResolver::resolveLogo($tenantSlug);

// Dados injetados pelo PwaHomeController
$proximaSessao = $proximaSessao ?? null;
$proximaSessaoResposta = $proximaSessaoResposta ?? null;
$ultimosComunicados = is_array($ultimosComunicados ?? null) ? $ultimosComunicados : [];

$pwaPageTitle = 'Acesso Rápido';
$pwaActiveTab = 'inicio';

ob_start();
?>

<div class="p-4 sm:p-6 space-y-5">

    <!-- ═══════════════════════════════════════════════════════════════════
         SAUDAÇÃO + LOGO DA LOJA
    ════════════════════════════════════════════════════════════════════ -->
    <div class="rounded-2xl border border-erpBorder bg-erpSurface p-5 shadow-sm">
        <div class="flex items-center gap-4">
            <?php if ($logoUrl && !str_contains($logoUrl, 'placeholder')): ?>
                <img src="<?= htmlspecialchars($logoUrl) ?>" alt="Logo da Loja"
                     class="h-14 w-14 flex-shrink-0 rounded-xl border border-erpBorder bg-white object-contain p-1 shadow-sm">
            <?php endif; ?>
            <div class="min-w-0">
                <h2 class="text-xl font-bold text-erpNavy truncate">Olá, <?= htmlspecialchars($usuarioNome) ?></h2>
                <p class="mt-0.5 text-sm text-erpMuted">
                    <?= $usuarioCargo !== '' ? htmlspecialchars($usuarioCargo) . ' · ' : '' ?>Oficina Digital
                </p>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════
         HERO CARD — PRÓXIMA SESSÃO
    ════════════════════════════════════════════════════════════════════ -->
    <?php if ($proximaSessao): ?>
        <?php
        // Formatação da data/hora
        $dtObj = null;
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

        // Status de confirmação do obreiro
        $statusConf = (string) ($proximaSessaoResposta['status_confirmacao'] ?? '');
        $participaAgape = (bool) ($proximaSessaoResposta['participara_agape'] ?? false);
        $badgeConf = match ($statusConf) {
            'confirmado' => $participaAgape
                ? ['Confirmado (com ágape)', 'bg-emerald-100 text-emerald-800 border-emerald-300']
                : ['Confirmado', 'bg-emerald-100 text-emerald-800 border-emerald-300'],
            'ausente'    => ['Ausente', 'bg-rose-100 text-rose-800 border-rose-300'],
            default      => ['Sem resposta', 'bg-amber-100 text-amber-800 border-amber-300'],
        };
        ?>

        <div class="rounded-2xl border border-erpNavy/20 bg-gradient-to-br from-erpNavyDeep to-erpNavy p-5 text-white shadow-lg">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-2.5 py-1 text-[0.6rem] font-bold uppercase tracking-widest text-erpGold">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Próxima Sessão
                    </div>
                    <h3 class="mt-3 text-xl font-bold leading-snug"><?= htmlspecialchars($tituloSessao) ?></h3>
                </div>
            </div>

            <!-- Data, hora e dia da semana -->
            <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-white/80">
                <?php if ($dataFormatada !== ''): ?>
                    <span class="font-semibold text-white"><?= htmlspecialchars($dataFormatada) ?></span>
                <?php endif; ?>
                <?php if ($horaFormatada !== ''): ?>
                    <span><?= htmlspecialchars($horaFormatada) ?></span>
                <?php endif; ?>
                <?php if ($diaSemana !== ''): ?>
                    <span class="text-white/60"><?= htmlspecialchars($diaSemana) ?></span>
                <?php endif; ?>
            </div>

            <!-- Metadados visuais (tipo, traje, grau) -->
            <div class="mt-3 flex flex-wrap gap-2 text-[0.65rem] uppercase tracking-wider">
                <?php if ($tipoDesc !== ''): ?>
                    <span class="rounded-full bg-white/10 px-2.5 py-1 font-semibold"><?= htmlspecialchars($tipoDesc) ?></span>
                <?php endif; ?>
                <?php if ($trajeDesc !== ''): ?>
                    <span class="rounded-full bg-white/10 px-2.5 py-1 font-semibold">Traje: <?= htmlspecialchars($trajeDesc) ?></span>
                <?php endif; ?>
                <?php if ($grau !== ''): ?>
                    <span class="rounded-full bg-white/10 px-2.5 py-1 font-semibold"><?= htmlspecialchars($grau) ?></span>
                <?php endif; ?>
            </div>

            <!-- Ordem do dia (progressive disclosure) -->
            <?php if ($ordemDia !== ''): ?>
                <details class="mt-4 rounded-xl bg-white/10 group">
                    <summary class="cursor-pointer px-4 py-2.5 text-sm font-semibold text-white/90 flex items-center justify-between">
                        Ordem do dia
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform group-open:rotate-180" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </summary>
                    <div class="px-4 pb-3 text-sm text-white/80 whitespace-pre-line leading-relaxed"><?= htmlspecialchars($ordemDia) ?></div>
                </details>
            <?php endif; ?>

            <!-- Ágape -->
            <div class="mt-3 text-sm text-white/70">
                Ágape: <strong class="text-white/90"><?= htmlspecialchars($agapeDesc) ?></strong>
            </div>

            <!-- Footer: badge de status + CTA -->
            <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                <span class="inline-flex items-center rounded-full border px-3 py-1 text-[0.6rem] font-bold uppercase tracking-wider <?= htmlspecialchars($badgeConf[1]) ?>">
                    <?= htmlspecialchars($badgeConf[0]) ?>
                </span>
                <a href="/pwa/sessoes"
                   class="inline-flex items-center gap-1.5 rounded-xl bg-white/20 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/30 active:scale-95">
                    <?= $statusConf === '' ? 'Confirmar Presença' : 'Ver Detalhes' ?>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>

            <?php if ($totalConfirmados > 0): ?>
                <div class="mt-2 text-xs text-white/50"><?= $totalConfirmados ?> irmão(s) confirmado(s)</div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <!-- Fallback: sem sessão futura -->
        <div class="rounded-2xl border border-erpBorder bg-erpSurface p-5 text-center shadow-sm">
            <div class="flex justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-erpMuted/40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>
            <p class="mt-2 text-sm font-semibold text-erpNavy">Nenhuma sessão futura</p>
            <p class="mt-1 text-xs text-erpMuted">Não há sessões publicadas no momento.</p>
        </div>
    <?php endif; ?>

    <!-- ═══════════════════════════════════════════════════════════════════
         MURAL DE AVISOS — CARROSSEL HORIZONTAL
    ════════════════════════════════════════════════════════════════════ -->
    <?php if (!empty($ultimosComunicados)): ?>
        <div>
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-bold uppercase tracking-widest text-erpNavy">Mural de Avisos</h3>
                <a href="/pwa/comunicacao" class="text-xs font-semibold text-erpMuted hover:text-erpNavy transition-colors">Ver todos →</a>
            </div>

            <!-- Container do carrossel — scroll interno, sem afetar a página -->
            <div class="flex gap-3 overflow-x-auto snap-x snap-mandatory pb-2 -mx-4 px-4 scrollbar-hide"
                 style="-webkit-overflow-scrolling: touch; scroll-padding-left: 1rem;">
                <?php foreach ($ultimosComunicados as $comunicado): ?>
                    <?php
                    $cId = (int) ($comunicado['id'] ?? 0);
                    $cTitulo = (string) ($comunicado['titulo'] ?? 'Comunicado');
                    $cCategoria = (string) ($comunicado['categoria'] ?? 'geral');
                    $cPublicadoEm = (string) ($comunicado['publicado_em'] ?? '');
                    $cLido = (bool) ($comunicado['lido_pelo_usuario'] ?? false);
                    $cLeituras = (int) ($comunicado['total_leituras'] ?? 0);

                    // Formatar data curta
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
                       class="flex-shrink-0 w-[72%] max-w-xs snap-start rounded-2xl border bg-erpSurface p-4 shadow-sm transition hover:border-erpNavy <?= $cLido ? 'border-erpBorder' : 'border-amber-300 bg-amber-50/30' ?>">
                        <div class="flex items-start justify-between gap-2">
                            <h4 class="text-sm font-semibold text-erpNavy leading-tight line-clamp-2"><?= htmlspecialchars($cTitulo) ?></h4>
                            <span class="shrink-0 inline-flex items-center rounded-full px-2 py-0.5 text-[0.58rem] font-bold uppercase tracking-wider <?= $cLido ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' ?>">
                                <?= $cLido ? 'Lido' : 'Novo' ?>
                            </span>
                        </div>
                        <div class="mt-2 flex items-center gap-2 text-[0.65rem] text-erpMuted">
                            <span class="uppercase tracking-wider font-semibold"><?= htmlspecialchars($cCategoria) ?></span>
                            <?php if ($cDataCurta !== ''): ?>
                                <span>·</span>
                                <span><?= htmlspecialchars($cDataCurta) ?></span>
                            <?php endif; ?>
                            <span>·</span>
                            <span><?= $cLeituras ?> leitura(s)</span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- ═══════════════════════════════════════════════════════════════════
         ATALHOS RÁPIDOS (grid original, agora como seção secundária)
    ════════════════════════════════════════════════════════════════════ -->
    <div>
        <h3 class="mb-3 text-sm font-bold uppercase tracking-widest text-erpNavy">Acesso Rápido</h3>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
            <?php
            $renderIcon = function (string $href, string $label, string $description, string $iconPath) {
                $baseClasses = 'flex flex-col items-center justify-center text-center rounded-2xl border border-erpBorder bg-erpSurface p-4 aspect-square transition-all duration-150 hover:border-erpNavy hover:shadow-md hover:-translate-y-0.5 active:scale-95';
                echo "<a href='{$href}' class='{$baseClasses}'>";
                echo "<div class='flex h-11 w-11 items-center justify-center rounded-full bg-erpBg'><svg xmlns='http://www.w3.org/2000/svg' class='h-6 w-6 text-erpNavy' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='2'>{$iconPath}</svg></div>";
                echo "<div class='mt-2 text-sm font-semibold text-erpNavy'>{$label}</div>";
                echo "<p class='mt-0.5 text-[0.65rem] text-erpMuted leading-tight'>{$description}</p>";
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
            if (!empty($_SESSION['is_system_admin']) && !empty($_ENV['FEATURE_PWA_ADMIN_CRUD']) && filter_var((string) $_ENV['FEATURE_PWA_ADMIN_CRUD'], FILTER_VALIDATE_BOOL)) {
                $renderIcon('/pwa/admin', 'Sistema', 'Ajustes técnicos', '<path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />');
            }
            ?>
        </div>
    </div>

    <!-- Dica de instalação -->
    <div class="text-center text-xs text-erpMuted">
        Para uma experiência completa, instale este app na tela de início do seu celular.
    </div>
</div>

<?php
$pwaContent = ob_get_clean();
require __DIR__ . '/shell.php';
?>
