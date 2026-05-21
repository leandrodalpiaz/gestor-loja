<?php
declare(strict_types=1);

/**
 * PWA Home — App Launcher + Mural de Efemérides
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

$links           = is_array($links ?? null) ? $links : [];
$usuarioNome     = (string) ($_SESSION['usuario_nome'] ?? 'Irmão');
$usuarioCargo    = (string) ($_SESSION['usuario_cargo'] ?? '');
$tenantName      = trim((string) ($_SESSION['tenant_name'] ?? 'Oficina Digital'));
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
// Cada ícone: id, label, href, icon (SVG path), color (cor de destaque), cond
$atalhos = [
    [
        'id'    => 'sessoes',
        'label' => 'Sessões',
        'href'  => '/pwa/sessoes',
        'icon'  => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        'color' => 'rgba(96,165,250,0.18)',   // blue
        'iconColor' => '#60a5fa',
        'cond'  => !empty($links['sessoes']),
    ],
    [
        'id'    => 'secretaria',
        'label' => 'Secretaria',
        'href'  => '/pwa/secretaria',
        'icon'  => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        'color' => 'rgba(167,139,250,0.18)',  // violet
        'iconColor' => '#a78bfa',
        'cond'  => true,
    ],
    [
        'id'    => 'tesouraria',
        'label' => 'Tesouraria',
        'href'  => '/pwa/tesouraria',
        'icon'  => 'M12 8c-1.657 0-3 .672-3 1.5S10.343 11 12 11s3 .672 3 1.5S13.657 14 12 14m0-6v6m0 0v2m8-6a8 8 0 11-16 0 8 8 0 0116 0z',
        'color' => 'rgba(52,211,153,0.18)',   // emerald
        'iconColor' => '#34d399',
        'cond'  => true,
    ],
    [
        'id'    => 'biblioteca',
        'label' => 'Biblioteca',
        'href'  => '/pwa/biblioteca',
        'icon'  => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
        'color' => 'rgba(251,191,36,0.18)',   // amber
        'iconColor' => '#fbbf24',
        'cond'  => true,
    ],
    [
        'id'    => 'chancelaria',
        'label' => 'Chancelaria',
        'href'  => '/pwa/chancelaria',
        'icon'  => 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z',
        'color' => 'rgba(201,162,39,0.18)',   // gold
        'iconColor' => '#C9A227',
        'cond'  => !empty($links['chancelaria']),
    ],
    [
        'id'    => 'comunicacao',
        'label' => 'Comunicados',
        'href'  => '/pwa/comunicacao',
        'icon'  => 'M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z',
        'color' => 'rgba(244,114,182,0.18)',  // pink
        'iconColor' => '#f472b6',
        'cond'  => !empty($links['comunicacao']),
    ],
    [
        'id'    => 'perfil',
        'label' => 'Meu CIM',
        'href'  => '/pwa/perfil',
        'icon'  => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
        'color' => 'rgba(56,189,248,0.18)',   // sky
        'iconColor' => '#38bdf8',
        'cond'  => true,
    ],
    [
        'id'    => 'obrigacoes',
        'label' => 'Obrigações',
        'href'  => '/pwa/obrigacoes',
        'icon'  => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
        'color' => 'rgba(251,146,60,0.18)',   // orange
        'iconColor' => '#fb923c',
        'cond'  => true,
    ],
    [
        'id'    => 'sistema',
        'label' => 'Sistema',
        'href'  => '/pwa/admin',
        'icon'  => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
        'color' => 'rgba(148,163,184,0.14)',  // slate
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

<div style="padding-bottom: 1.5rem;">

    <!-- ══════════════════════════════════════════════════════════════
         SECTION 1 — GREETING + STATUS FINANCEIRO
         ══════════════════════════════════════════════════════════════ -->
    <div style="padding: 1rem 1rem 0;">
        <div style="
            border-radius: 1.5rem;
            background: linear-gradient(135deg, #0f172a 0%, #1a2744 60%, #0f172a 100%);
            border: 1px solid rgba(201,162,39,0.18);
            padding: 1.125rem 1.25rem;
            position: relative;
            overflow: hidden;
        ">
            <!-- Glow decorativo -->
            <div style="
                position:absolute;top:-20px;right:-20px;
                width:120px;height:120px;
                background:radial-gradient(circle, rgba(201,162,39,0.18) 0%, transparent 70%);
                pointer-events:none;
            "></div>

            <!-- Saudação -->
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:0.5rem;">
                <div>
                    <p style="font-size:0.65rem;font-weight:700;letter-spacing:0.18em;text-transform:uppercase;color:rgba(201,162,39,0.7);margin:0 0 0.2rem;">
                        <?= $saudacao ?>
                    </p>
                    <h2 style="font-size:1.125rem;font-weight:800;color:#f8fafc;margin:0;letter-spacing:-0.02em;line-height:1.2;">
                        <?= htmlspecialchars($primeiroNome) ?>
                    </h2>
                </div>
                <?php if ($saldoAberto > 0): ?>
                    <a href="/pwa/tesouraria" style="
                        flex-shrink:0;
                        background:rgba(248,113,113,0.18);
                        border:1px solid rgba(248,113,113,0.3);
                        border-radius:0.75rem;
                        padding:0.35rem 0.75rem;
                        font-size:0.65rem;
                        font-weight:700;
                        color:#fca5a5;
                        text-decoration:none;
                        white-space:nowrap;
                    ">
                        <?= $atrasadas > 0 ? "⚠ $atrasadas em atraso" : 'Ver financeiro' ?>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Linha divisória -->
            <div style="height:1px;background:rgba(255,255,255,0.07);margin:0.875rem 0;"></div>

            <!-- Situação financeira -->
            <div style="display:flex;align-items:center;justify-content:space-between;gap:0.5rem;">
                <div>
                    <p style="font-size:0.65rem;color:#94a3b8;margin:0 0 0.15rem;font-weight:500;">
                        <?php if ($saldoAberto > 0): ?>
                            Saldo em aberto
                        <?php elseif ($proximoVenc): ?>
                            Próximo vencimento
                        <?php else: ?>
                            Situação financeira
                        <?php endif; ?>
                    </p>
                    <p style="font-size:1.25rem;font-weight:800;letter-spacing:-0.03em;margin:0;color:<?= $saldoAberto > 0 ? '#fca5a5' : '#6ee7b7' ?>;">
                        <?php if ($saldoAberto > 0): ?>
                            <?= $formatReal($saldoAberto) ?>
                        <?php else: ?>
                            Em dia ✓
                        <?php endif; ?>
                    </p>
                </div>
                <a href="/pwa/tesouraria" style="
                    background: <?= $saldoAberto > 0 ? 'rgba(248,113,113,0.25)' : 'rgba(52,211,153,0.18)' ?>;
                    border: 1px solid <?= $saldoAberto > 0 ? 'rgba(248,113,113,0.4)' : 'rgba(52,211,153,0.3)' ?>;
                    border-radius: 0.875rem;
                    padding: 0.5rem 1rem;
                    font-size: 0.75rem;
                    font-weight: 700;
                    color: <?= $saldoAberto > 0 ? '#fca5a5' : '#6ee7b7' ?>;
                    text-decoration: none;
                    flex-shrink: 0;
                ">
                    Detalhar
                </a>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         SECTION 2 — EFEMÉRIDES DO DIA (Carousel)
         ══════════════════════════════════════════════════════════════ -->
    <?php if (!empty($efemerides_reais)): ?>
    <div style="margin-top:1.25rem;">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:0 1rem;margin-bottom:0.625rem;">
            <p style="font-size:0.65rem;font-weight:700;letter-spacing:0.18em;text-transform:uppercase;color:rgba(201,162,39,0.75);margin:0;">
                Efemérides de hoje
            </p>
            <a href="/pwa/chancelaria/efemerides" style="font-size:0.7rem;font-weight:600;color:#94a3b8;text-decoration:none;">
                Gerenciar →
            </a>
        </div>

        <!-- Carousel horizontal de cards de efemérides -->
        <div class="pwa-scrollbar-none" style="
            display:flex;
            gap:0.75rem;
            overflow-x:auto;
            padding:0 1rem 0.5rem;
            scroll-snap-type:x mandatory;
            -webkit-overflow-scrolling:touch;
        ">
            <?php foreach ($efemerides_reais as $i => $card): ?>
            <div style="
                flex-shrink:0;
                width:72vw;
                max-width:280px;
                border-radius:1.25rem;
                overflow:hidden;
                position:relative;
                scroll-snap-align:start;
                aspect-ratio:3/4;
                border:1px solid rgba(255,255,255,0.10);
                background:#0f172a;
            ">
                <img src="<?= htmlspecialchars($card['url_imagem']) ?>"
                     alt="Efeméride"
                     style="width:100%;height:100%;object-fit:cover;display:block;">
                <div style="
                    position:absolute;inset:0;
                    background:linear-gradient(to top, rgba(2,6,23,0.88) 0%, transparent 55%);
                "></div>
                <div style="position:absolute;bottom:0.875rem;left:0.875rem;right:0.875rem;">
                    <p style="font-size:0.6rem;font-weight:700;letter-spacing:0.18em;text-transform:uppercase;color:#fde68a;margin:0 0 0.25rem;">
                        <?= htmlspecialchars($card['legenda_tipo']) ?>
                    </p>
                    <p style="font-size:0.9rem;font-weight:700;color:#fff;margin:0;line-height:1.3;">
                        <?= htmlspecialchars($card['titulo_homenagem']) ?>
                    </p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Dots de paginação -->
        <?php if (count($efemerides_reais) > 1): ?>
        <div style="display:flex;justify-content:center;gap:5px;margin-top:0.5rem;">
            <?php foreach ($efemerides_reais as $i => $_): ?>
            <div style="
                width:<?= $i === 0 ? '16px' : '6px' ?>;
                height:6px;
                border-radius:3px;
                background:<?= $i === 0 ? '#C9A227' : 'rgba(255,255,255,0.2)' ?>;
                transition:all 0.3s;
            "></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
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
    <div style="padding: 1rem 1rem 0;">
        <a href="/pwa/sessoes" style="
            display:flex;
            align-items:center;
            gap:0.875rem;
            background:rgba(96,165,250,0.1);
            border:1px solid rgba(96,165,250,0.22);
            border-radius:1.125rem;
            padding:0.875rem 1rem;
            text-decoration:none;
        ">
            <div style="
                width:42px;height:42px;
                border-radius:0.875rem;
                background:rgba(96,165,250,0.18);
                display:flex;align-items:center;justify-content:center;
                flex-shrink:0;
            ">
                <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="#60a5fa" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>
            <div style="flex:1;min-width:0;">
                <p style="font-size:0.65rem;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:#60a5fa;margin:0 0 0.15rem;">
                    Próxima Sessão
                </p>
                <p style="font-size:0.875rem;font-weight:700;color:#f1f5f9;margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    <?= htmlspecialchars($tituloSessao) ?>
                </p>
                <?php if ($dataHora !== ''): ?>
                <p style="font-size:0.7rem;color:#94a3b8;margin:0.1rem 0 0;font-weight:500;">
                    <?= htmlspecialchars($dataHora) ?>
                </p>
                <?php endif; ?>
            </div>
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#475569" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
        </a>
    </div>
    <?php endif; ?>

    <!-- ══════════════════════════════════════════════════════════════
         SECTION 4 — LAUNCHER GRID (App Icons)
         ══════════════════════════════════════════════════════════════ -->
    <div style="padding: 1.25rem 1rem 0;">
        <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.875rem;">
            <p style="font-size:0.65rem;font-weight:700;letter-spacing:0.18em;text-transform:uppercase;color:#475569;margin:0;">
                Escritório Digital
            </p>
            <div style="flex:1;height:1px;background:rgba(255,255,255,0.06);"></div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:0.625rem;">
            <?php foreach ($atalhosFiltrados as $a): ?>
            <a href="<?= htmlspecialchars($a['href']) ?>"
               id="atalho-<?= htmlspecialchars($a['id']) ?>"
               style="
                   display:flex;flex-direction:column;align-items:center;justify-content:center;
                   gap:0.5rem;
                   border-radius:1.125rem;
                   border:1px solid rgba(255,255,255,0.08);
                   background:<?= htmlspecialchars($a['color']) ?>;
                   padding:0.875rem 0.25rem 0.75rem;
                   text-decoration:none;
                   -webkit-tap-highlight-color:transparent;
                   transition:transform 0.12s, opacity 0.12s;
                   aspect-ratio:1/1;
               "
               onpointerdown="this.style.transform='scale(0.93)';this.style.opacity='0.8'"
               onpointerup="this.style.transform='';this.style.opacity=''"
               onpointercancel="this.style.transform='';this.style.opacity=''">
                <div style="
                    width:46px;height:46px;
                    border-radius:0.875rem;
                    background:rgba(0,0,0,0.25);
                    border:1px solid rgba(255,255,255,0.10);
                    display:flex;align-items:center;justify-content:center;
                ">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                         viewBox="0 0 24 24" stroke="<?= htmlspecialchars($a['iconColor']) ?>" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="<?= htmlspecialchars($a['icon']) ?>" />
                    </svg>
                </div>
                <span style="
                    font-size:0.6rem;font-weight:700;
                    color:#cbd5e1;
                    text-align:center;line-height:1.2;
                    word-break:break-word;
                    max-width:100%;
                "><?= htmlspecialchars($a['label']) ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         SECTION 5 — COMUNICADOS RECENTES (se houver)
         ══════════════════════════════════════════════════════════════ -->
    <?php if (!empty($ultimosComunicados)): ?>
    <div style="padding: 1.25rem 1rem 0;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.625rem;">
            <p style="font-size:0.65rem;font-weight:700;letter-spacing:0.18em;text-transform:uppercase;color:#475569;margin:0;">
                Comunicados
            </p>
            <a href="/pwa/comunicacao" style="font-size:0.7rem;font-weight:600;color:#94a3b8;text-decoration:none;">
                Ver todos →
            </a>
        </div>
        <div style="
            border:1px solid rgba(255,255,255,0.08);
            border-radius:1.125rem;
            background:rgba(255,255,255,0.04);
            overflow:hidden;
        ">
            <?php foreach (array_slice($ultimosComunicados, 0, 3) as $i => $com): ?>
            <?php $lido = !empty($com['lido_pelo_usuario']); ?>
            <a href="/pwa/comunicacao/ler?id=<?= (int)($com['id'] ?? 0) ?>" style="
                display:flex;align-items:center;gap:0.75rem;
                padding:0.75rem 1rem;
                border-bottom:<?= $i < 2 ? '1px solid rgba(255,255,255,0.06)' : 'none' ?>;
                text-decoration:none;
                background:transparent;
            ">
                <?php if (!$lido): ?>
                <div style="width:7px;height:7px;border-radius:50%;background:#C9A227;flex-shrink:0;"></div>
                <?php else: ?>
                <div style="width:7px;height:7px;border-radius:50%;background:rgba(255,255,255,0.15);flex-shrink:0;"></div>
                <?php endif; ?>
                <p style="
                    flex:1;min-width:0;
                    font-size:0.8125rem;
                    font-weight:<?= $lido ? '500' : '700' ?>;
                    color:<?= $lido ? '#94a3b8' : '#f1f5f9' ?>;
                    margin:0;
                    white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
                "><?= htmlspecialchars((string)($com['titulo'] ?? 'Comunicado')) ?></p>
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="#334155" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</div>

<?php
$pwaContent = ob_get_clean();
require __DIR__ . '/shell.php';
?>
