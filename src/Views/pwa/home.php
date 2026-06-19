<?php
declare(strict_types=1);

/**
 * PWA Home — App Launcher + Mural de Efemérides
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

$links                = is_array($links ?? null) ? $links : [];
$usuarioNome          = (string) ($_SESSION['usuario_nome'] ?? 'Irmão');
$usuarioCargo         = (string) ($_SESSION['usuario_cargo'] ?? '');
$tenantName           = trim((string) ($_SESSION['tenant_name'] ?? 'Oficina Digital'));
$efemerides_reais     = is_array($efemerides_reais ?? null) ? $efemerides_reais : [];
$proximaSessao        = $proximaSessao ?? null;
$ultimosComunicados   = is_array($ultimosComunicados ?? null) ? $ultimosComunicados : [];
$resumoFinanceiro     = is_array($resumoFinanceiro ?? null) ? $resumoFinanceiro : [];

$pwaPageTitle = $tenantName ?: 'Minha Loja';
$pwaActiveTab = 'inicio';

// ── Greeting ─────────────────────────────────────────────────────────────────
$hora = (int) date('H');
$saudacao = match(true) {
    $hora < 12 => 'Bom dia',
    $hora < 18 => 'Boa tarde',
    default    => 'Boa noite',
};
$primeiroNome = explode(' ', trim($usuarioNome))[0] ?? $usuarioNome;

// ── Atalhos (launcher icons) ──────────────────────────────────────────────────
$atalhos = [
    [
        'id'    => 'sessoes',
        'label' => 'Sessões',
        'href'  => '/pwa/sessoes',
        'icon'  => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        'color' => 'rgba(96,165,250,0.06)',   // blue
        'iconColor' => '#60a5fa',
        'cond'  => !empty($links['sessoes']),
    ],
    [
        'id'    => 'secretaria',
        'label' => 'Secretaria',
        'href'  => '/pwa/secretaria',
        'icon'  => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        'color' => 'rgba(167,139,250,0.06)',  // violet
        'iconColor' => '#a78bfa',
        'cond'  => true,
    ],
    [
        'id'    => 'tesouraria',
        'label' => 'Tesouraria',
        'href'  => '/pwa/tesouraria',
        'icon'  => 'M12 8c-1.657 0-3 .672-3 1.5S10.343 11 12 11s3 .672 3 1.5S13.657 14 12 14m0-6v6m0 0v2m8-6a8 8 0 11-16 0 8 8 0 0116 0z',
        'color' => 'rgba(52,211,153,0.06)',   // emerald
        'iconColor' => '#34d399',
        'cond'  => true,
    ],
    [
        'id'    => 'biblioteca',
        'label' => 'Biblioteca',
        'href'  => '/pwa/biblioteca',
        'icon'  => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
        'color' => 'rgba(251,191,36,0.06)',   // amber
        'iconColor' => '#fbbf24',
        'cond'  => true,
    ],
    [
        'id'    => 'chancelaria',
        'label' => 'Chancelaria',
        'href'  => '/pwa/chancelaria',
        'icon'  => 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z',
        'color' => 'rgba(201,162,39,0.06)',   // gold
        'iconColor' => '#C9A227',
        'cond'  => !empty($links['chancelaria']),
    ],
    [
        'id'    => 'comunicacao',
        'label' => 'Comunicados',
        'href'  => '/pwa/comunicacao',
        'icon'  => 'M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z',
        'color' => 'rgba(244,114,182,0.06)',  // pink
        'iconColor' => '#f472b6',
        'cond'  => !empty($links['comunicacao']),
    ],
    [
        'id'    => 'perfil',
        'label' => 'Meu CIM',
        'href'  => '/pwa/perfil',
        'icon'  => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
        'color' => 'rgba(56,189,248,0.06)',   // sky
        'iconColor' => '#38bdf8',
        'cond'  => true,
    ],
    [
        'id'    => 'obrigacoes',
        'label' => 'Obrigações',
        'href'  => '/pwa/obrigacoes',
        'icon'  => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
        'color' => 'rgba(251,146,60,0.06)',   // orange
        'iconColor' => '#fb923c',
        'cond'  => true,
    ],
    [
        'id'    => 'sistema',
        'label' => 'Sistema',
        'href'  => '/pwa/admin',
        'icon'  => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
        'color' => 'rgba(148,163,184,0.06)',  // slate
        'iconColor' => '#94a3b8',
        'cond'  => !empty($_SESSION['is_system_admin']),
    ],
];

$atalhosFiltrados = array_values(array_filter($atalhos, fn($a) => (bool) ($a['cond'] ?? false)));

// ── Resumo financeiro ─────────────────────────────────────────────────────────
$saldoAberto    = (float) ($resumoFinanceiro['saldo_em_aberto'] ?? 0);
$atrasadas      = (int)   ($resumoFinanceiro['parcelas_atrasadas'] ?? 0);
$proximoVenc    = $resumoFinanceiro['proximo_vencimento'] ?? null;
$formatReal     = fn(float $v): string => 'R$ ' . number_format($v, 2, ',', '.');

ob_start();
?>

<div class="pb-6">

    <!-- ══════════════════════════════════════════════════════════════
         SECTION 1 — GREETING + STATUS FINANCEIRO (Widget Nativo)
         ══════════════════════════════════════════════════════════════ -->
    <div class="px-4 pt-4">
        <div class="pwa-card relative overflow-hidden border border-amber-500/5">
            <!-- Saudação e Badge de Alerta -->
            <div class="flex items-start justify-between gap-2">
                <div>
                    <p class="pwa-eyebrow text-amber-500/70">
                        <?= $saudacao ?>
                    </p>
                    <h2 class="text-lg font-bold text-slate-100 tracking-tight leading-tight">
                        <?= htmlspecialchars($primeiroNome) ?>
                    </h2>
                </div>
                <?php if ($saldoAberto > 0): ?>
                    <a href="/pwa/tesouraria" class="flex-shrink-0 bg-red-500/10 border border-red-500/20 rounded-lg px-2.5 py-1 text-[10px] font-bold text-red-400 no-underline whitespace-nowrap active:scale-[0.97] transition-transform">
                        <?= $atrasadas > 0 ? "⚠ $atrasadas em atraso" : 'Ver financeiro' ?>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Divisor sutil -->
            <div class="pwa-divider my-3.5"></div>

            <!-- Informações Financeiras -->
            <div class="flex items-center justify-between gap-2">
                <div>
                    <p class="text-[10px] text-slate-400 font-medium">
                        <?php if ($saldoAberto > 0): ?>
                            Saldo em aberto
                        <?php elseif ($proximoVenc): ?>
                            Próximo vencimento
                        <?php else: ?>
                            Situação financeira
                        <?php endif; ?>
                    </p>
                    <p class="text-xl font-bold tracking-tight <?= $saldoAberto > 0 ? 'text-red-300' : 'text-emerald-400' ?>">
                        <?php if ($saldoAberto > 0): ?>
                            <?= $formatReal($saldoAberto) ?>
                        <?php else: ?>
                            Em dia ✓
                        <?php endif; ?>
                    </p>
                </div>
                <a href="/pwa/tesouraria" class="pwa-btn-secondary py-2 px-3.5 w-auto text-xs font-bold active:scale-[0.97] transition-transform <?= $saldoAberto > 0 ? 'bg-red-500/10 text-red-300 border-red-500/20 active:bg-red-500/20' : '' ?>">
                    Detalhar
                </a>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         SECTION 2 — EFEMÉRIDES DO DIA (Lista Compacta + Modal)
         ══════════════════════════════════════════════════════════════ -->
    <?php if (!empty($efemerides_reais)): ?>
    <div class="mt-5 px-4">
        <div class="flex items-center justify-between mb-3">
            <p class="text-[10px] font-bold tracking-wider uppercase text-slate-500">
                Efemérides de hoje
            </p>
            <a href="/pwa/chancelaria/efemerides" class="text-[11px] font-semibold text-slate-400 no-underline hover:text-white">
                Gerenciar →
            </a>
        </div>

        <div class="space-y-2">
            <?php foreach ($efemerides_reais as $card): ?>
            <?php 
                $tipoLower = strtolower($card['legenda_tipo']);
                // Ícone SVG temático com base na categoria/tipo da efeméride
                $iconSvg = match(true) {
                    str_contains($tipoLower, 'anivers') => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8.25v-1.5m0 1.5c-1.355 0-2.697.056-4.024.166C6.845 8.51 6 9.473 6 10.608v2.513m6-4.871c1.355 0 2.697.056 4.024.166C17.155 8.51 18 9.473 18 10.608v2.513M15 8.25v-1.5m-6 1.5v-1.5m12 9.75-1.011-.035A11.64 11.64 0 0 0 12 16.5c-2.738 0-5.347.94-7.424 2.665L3.5 19.5m17-3.75V19.5m0-3.75a11.64 11.64 0 0 1-3.424-1.885 11.64 11.64 0 0 0-3.424 1.885 11.64 11.64 0 0 1-3.424-1.885 11.64 11.64 0 0 0-3.424 1.885M3.5 19.5v-3.75m0 3.75 1.01-.035A11.64 11.64 0 0 1 12 18c2.738 0 5.347.94 7.424 2.665l1.076-.035M12 12v-1.5" /></svg>',
                    str_contains($tipoLower, 'casament') => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" /></svg>',
                    default => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499c.151-.577.98-.577 1.132 0l1.458 5.564a.75.75 0 0 0 .713.518h5.848c.613 0 .868.796.37 1.186l-4.73 3.719a.75.75 0 0 0-.272.838l1.79 5.595c.19.594-.482 1.082-1.002.68L12 17.202l-4.73 3.69c-.52.402-1.192-.086-1.002-.68l1.79-5.595a.75.75 0 0 0-.272-.838L3.056 10.77c-.498-.39-.243-1.186.37-1.186h5.848a.75.75 0 0 0 .713-.518L11.48 3.5Z" /></svg>'
                };
            ?>
            <div onclick="abrirModalEfemeride('<?= htmlspecialchars($card['url_imagem']) ?>')"
                 class="pwa-list-item-celebration">
                <!-- Ícone da Categoria -->
                <div class="w-9 h-9 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-500 flex-shrink-0">
                    <?= $iconSvg ?>
                </div>
                <!-- Detalhes do Homenageado -->
                <div class="flex-1 min-w-0">
                    <p class="text-[9px] font-bold tracking-wider uppercase text-amber-500 mb-0.5">
                        <?= htmlspecialchars($card['legenda_tipo']) ?>
                    </p>
                    <p class="text-xs font-semibold text-slate-100 truncate">
                        <?= htmlspecialchars($card['titulo_homenagem']) ?>
                    </p>
                </div>
                <!-- Indicador de visualização -->
                <div class="flex items-center gap-1.5 text-[10px] font-bold text-slate-400 flex-shrink-0">
                    <span>Ver Homenagem</span>
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ══════════════════════════════════════════════════════════════
         SECTION 3 — PRÓXIMA SESSÃO (se existir)
         ══════════════════════════════════════════════════════════════ -->
    <?php if ($proximaSessao): ?>
    <?php
    $dataHora = trim((string) ($proximaSessao['data_hora_inicio'] ?? ''));
    $tituloSessao = trim((string) ($proximaSessao['titulo'] ?? 'Próxima Sessão'));
    ?>
    <div class="px-4 pt-4">
        <a href="/pwa/sessoes" class="flex items-center gap-3.5 bg-blue-500/5 border border-blue-500/10 rounded-2xl p-4 no-underline active:scale-[0.97] transition-transform">
            <div class="w-10 h-10 rounded-xl bg-blue-500/10 flex items-center justify-center flex-shrink-0">
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="#60a5fa" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-[10px] font-bold tracking-wider uppercase text-blue-400 mb-0.5">
                    Próxima Sessão
                </p>
                <p class="text-sm font-bold text-slate-100 truncate">
                    <?= htmlspecialchars($tituloSessao) ?>
                </p>
                <?php if ($dataHora !== ''): ?>
                <p class="text-xs text-slate-400 mt-0.5 font-medium">
                    <?= htmlspecialchars($dataHora) ?>
                </p>
                <?php endif; ?>
            </div>
            <svg class="w-4 h-4 text-slate-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
        </a>
    </div>
    <?php endif; ?>

    <!-- ══════════════════════════════════════════════════════════════
         SECTION 4 — LAUNCHER GRID (App Icons)
         ══════════════════════════════════════════════════════════════ -->
    <div class="px-4 pt-5">
        <div class="flex items-center gap-3 mb-3">
            <p class="text-[10px] font-bold tracking-wider uppercase text-slate-500">
                Escritório Digital
            </p>
            <div class="flex-1 h-[1px] bg-white/5"></div>
        </div>

        <?php 
        $colsClass = match(count($atalhosFiltrados)) {
            1       => 'grid-cols-1 max-w-[150px] mx-auto',
            2       => 'grid-cols-2 max-w-[320px] mx-auto',
            3       => 'grid-cols-3 max-w-[480px] mx-auto',
            default => 'grid-cols-4'
        };
        ?>
        <div class="grid <?= $colsClass ?> gap-2.5">
            <?php foreach ($atalhosFiltrados as $a): ?>
            <a href="<?= htmlspecialchars($a['href']) ?>"
               id="atalho-<?= htmlspecialchars($a['id']) ?>"
               class="pwa-module-card flex flex-col items-center justify-center gap-2"
               style="background: <?= htmlspecialchars($a['color']) ?>;">
                <div class="w-10 h-10 rounded-xl bg-black/25 border border-white/5 flex items-center justify-center flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none"
                         viewBox="0 0 24 24" stroke="<?= htmlspecialchars($a['iconColor']) ?>" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="<?= htmlspecialchars($a['icon']) ?>" />
                    </svg>
                </div>
                <span class="text-[9px] font-bold text-slate-300 text-center leading-tight break-words max-w-full">
                    <?= htmlspecialchars($a['label']) ?>
                </span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         SECTION 5 — COMUNICADOS RECENTES (se houver)
         ══════════════════════════════════════════════════════════════ -->
    <?php if (!empty($ultimosComunicados)): ?>
    <div class="px-4 pt-5">
        <div class="flex items-center justify-between mb-2.5">
            <p class="text-[10px] font-bold tracking-wider uppercase text-slate-500">
                Comunicados
            </p>
            <a href="/pwa/comunicacao" class="text-[11px] font-semibold text-slate-400 no-underline">
                Ver todos →
            </a>
        </div>
        <div class="pwa-list-group">
            <?php foreach (array_slice($ultimosComunicados, 0, 3) as $i => $com): ?>
            <?php $lido = !empty($com['lido_pelo_usuario']); ?>
            <a href="/pwa/comunicacao/ler?id=<?= (int)($com['id'] ?? 0) ?>" 
               class="pwa-list-item flex items-center gap-3">
                <div class="w-1.5 h-1.5 rounded-full flex-shrink-0 <?= !$lido ? 'bg-amber-500' : 'bg-slate-700' ?>"></div>
                <p class="flex-1 min-w-0 text-xs <?= $lido ? 'font-medium text-slate-400' : 'font-semibold text-slate-200' ?> truncate">
                    <?= htmlspecialchars((string)($com['titulo'] ?? 'Comunicado')) ?>
                </p>
                <svg class="w-3.5 h-3.5 text-slate-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ══════════════════════════════════════════════════════════════
         LIGHTBOX MODAL FOR EFEMÉRIDES (TAP TO EXPAND)
         ══════════════════════════════════════════════════════════════ -->
    <div id="efemeride-modal" class="efemeride-modal-overlay" onclick="fecharModalEfemeride()">
        <button type="button" class="efemeride-modal-close-btn" onclick="fecharModalEfemeride(event)">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>
        <div class="efemeride-modal-container">
            <div class="efemeride-modal-image-wrapper" onclick="event.stopPropagation()">
                <img id="efemeride-modal-img" src="" alt="Cartão de Homenagem" class="efemeride-modal-img">
            </div>
        </div>
    </div>

    <script>
        function abrirModalEfemeride(imageUrl) {
            const modal = document.getElementById('efemeride-modal');
            const img = document.getElementById('efemeride-modal-img');
            if (modal && img) {
                img.src = imageUrl;
                modal.classList.add('active');
                
                // Desativa scroll do conteúdo de fundo para experiência nativa
                const contentArea = document.querySelector('.app-content');
                if (contentArea) {
                    contentArea.style.overflowY = 'hidden';
                }
            }
        }

        function fecharModalEfemeride(event) {
            if (event) {
                event.stopPropagation();
            }
            const modal = document.getElementById('efemeride-modal');
            if (modal) {
                modal.classList.remove('active');
                
                // Reativa scroll do conteúdo de fundo
                const contentArea = document.querySelector('.app-content');
                if (contentArea) {
                    contentArea.style.overflowY = 'auto';
                }
            }
        }
    </script>
</div>

<?php
$pwaContent = ob_get_clean();
require __DIR__ . '/shell.php';
?>
