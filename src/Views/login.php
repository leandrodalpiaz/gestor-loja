<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Publico e Acesso Restrito - Gestor de Loja</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        erpNavy: '#1E3A5F',
                        erpNavyDeep: '#162E4A',
                        erpGold: '#B8960C',
                        erpBg: '#F4F7FB',
                        erpSurface: '#FFFFFF',
                        erpBorder: '#D9E0EA',
                        erpText: '#1F2937',
                        erpMuted: '#526173',
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    },
                    boxShadow: {
                        shell: '0 28px 70px rgba(22, 46, 74, 0.12)',
                    }
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        @media (min-width: 1440px) {
            .erp-readable {
                font-size: 1.08rem;
            }
            .erp-readable .text-xs,
            .erp-readable .text-\[0\.68rem\],
            .erp-readable .text-\[0\.7rem\],
            .erp-readable .text-\[0\.72rem\] {
                font-size: 0.92rem !important;
                line-height: 1.4rem !important;
            }
            .erp-readable .text-sm {
                font-size: 1.04rem !important;
                line-height: 1.6rem !important;
            }
        }
        @media (min-width: 1800px) {
            .erp-readable {
                font-size: 1.14rem;
            }
            .erp-readable .text-xs,
            .erp-readable .text-\[0\.68rem\],
            .erp-readable .text-\[0\.7rem\],
            .erp-readable .text-\[0\.72rem\] {
                font-size: 0.98rem !important;
                line-height: 1.5rem !important;
            }
        }
    </style>
</head>
<?php
$tenantSlug = trim((string) ($tenantSlug ?? ($_SESSION['tenant_slug'] ?? '')));
$tenantName = trim((string) ($tenantName ?? ($_SESSION['tenant_name'] ?? '')));
$tenantResolved = !empty($tenantResolved) && $tenantSlug !== '';
$tenantUnavailableMessage = trim((string) ($tenantUnavailableMessage ?? ''));
$logoLogin = \App\Core\Tenant\TenantAssetResolver::resolveLogo($tenantSlug);

$heroPortalImage = \App\Core\Tenant\TenantAssetResolver::resolve(
    $tenantSlug,
    'portal/hero/capa-institucional.svg',
    '/assets/portal/hero/capa-institucional.svg'
);
if ($heroPortalImage === '') {
    $heroPortalImage = null;
}

$publicConteudos = is_array($publicConteudos ?? null) ? $publicConteudos : [];
$publicAds = is_array($publicAds ?? null) ? $publicAds : [];

$publicMedia = [
    [
        'tipo' => 'foto',
        'titulo' => 'Galeria de eventos publicos',
        'arquivo' => \App\Core\Tenant\TenantAssetResolver::resolve(
            $tenantSlug,
            'portal/galeria/fotos/foto-placeholder-01.svg',
            '/assets/portal/galeria/fotos/foto-placeholder-01.svg'
        ),
    ],
    [
        'tipo' => 'video',
        'titulo' => 'Video institucional da proposta',
        'arquivo' => \App\Core\Tenant\TenantAssetResolver::resolve(
            $tenantSlug,
            'portal/galeria/videos/video-placeholder-01.svg',
            '/assets/portal/galeria/videos/video-placeholder-01.svg'
        ),
    ],
];
?>
<body class="erp-readable min-h-screen bg-[radial-gradient(circle_at_top,#f9fbfd_0%,#eef3f8_55%,#e8edf4_100%)] font-sans text-erpText">
    <div class="min-h-screen px-4 py-5 sm:px-8 xl:px-10 xl:py-8">
        <div class="mx-auto flex min-h-[calc(100vh-2.5rem)] max-w-[1560px] items-center justify-center">
            <main class="w-full overflow-hidden rounded-[2rem] border border-erpBorder bg-erpSurface shadow-shell">
                <section class="relative overflow-hidden border-b border-erpBorder bg-[linear-gradient(135deg,#173452_0%,#21466c_58%,#2d5d87_100%)] px-6 py-7 text-white sm:px-10 xl:px-12 xl:py-10">
                    <?php if ($heroPortalImage): ?>
                        <img src="<?= htmlspecialchars($heroPortalImage) ?>" alt="Capa institucional" class="pointer-events-none absolute inset-0 h-full w-full object-cover opacity-20">
                    <?php endif; ?>
                    <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
                        <span class="absolute -left-4 top-20 text-[2.4rem] font-bold tracking-tight text-white/10 sm:text-[3.4rem] xl:text-[4.2rem]">Portal Publico da Loja</span>
                        <span class="absolute left-1/3 top-36 text-[2rem] font-semibold tracking-tight text-white/10 sm:text-[2.8rem] xl:text-[3.4rem]">Agenda, Noticias e Eventos</span>
                        <span class="absolute right-10 bottom-8 text-[1.5rem] font-semibold uppercase tracking-[0.26em] text-white/10 sm:text-[2rem]">Acesso Restrito Seguro</span>
                    </div>
                    <div class="relative z-10">
                        <div class="mb-6 flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-full border border-white/25 bg-white/10">
                                    <?php if ($logoLogin): ?>
                                        <img src="<?= htmlspecialchars($logoLogin) ?>" alt="Brasao da Loja" class="h-10 w-10 object-contain">
                                    <?php else: ?>
                                        <span class="text-lg font-semibold text-white">&#8756;</span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-[0.72rem] font-semibold uppercase tracking-[0.28em] text-white/75">
                                    <?= htmlspecialchars($tenantResolved && $tenantName !== '' ? $tenantName : 'Portal Publico da Loja') ?>
                                </div>
                            </div>
                            <a href="#acesso-restrito" class="inline-flex items-center rounded-xl border border-white/25 bg-white/10 px-4 py-2 text-sm font-semibold text-white hover:bg-white/20">
                                Entrar
                            </a>
                        </div>

                        <div class="grid gap-6 xl:grid-cols-[1.25fr_0.75fr] xl:items-end">
                            <div class="max-w-4xl">
                                <h1 class="text-3xl font-semibold leading-tight sm:text-[2.45rem] xl:text-[3rem]">
                                    Presenca institucional moderna com conteudo publico e acesso restrito seguro.
                                </h1>
                                <p class="mt-4 max-w-3xl text-base leading-8 text-white/82 xl:text-lg">
                                    Agenda, noticias e acoes da Loja para a comunidade, preservando o sistema interno fechado para operacao administrativa.
                                </p>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-3 xl:grid-cols-1">
                                <div class="rounded-[1.2rem] border border-white/15 bg-white/10 px-4 py-4">
                                    <div class="text-[0.68rem] font-semibold uppercase tracking-[0.24em] text-white/65">Publico</div>
                                    <div class="mt-2 text-lg font-semibold">Agenda e noticias</div>
                                </div>
                                <div class="rounded-[1.2rem] border border-white/15 bg-white/10 px-4 py-4">
                                    <div class="text-[0.68rem] font-semibold uppercase tracking-[0.24em] text-white/65">Acesso</div>
                                    <div class="mt-2 text-lg font-semibold">Area restrita</div>
                                </div>
                                <div class="rounded-[1.2rem] border border-white/15 bg-white/10 px-4 py-4">
                                    <div class="text-[0.68rem] font-semibold uppercase tracking-[0.24em] text-white/65">Modelo</div>
                                    <div class="mt-2 text-lg font-semibold">Parcerias locais</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="grid gap-0 xl:grid-cols-[minmax(0,1.4fr)_390px]">
                    <div class="order-1 px-6 py-7 sm:px-10 xl:px-12 xl:py-10">
                        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <div class="text-[0.72rem] font-semibold uppercase tracking-[0.26em] text-erpGold">Apresentacao da proposta</div>
                                <h2 class="mt-2 text-2xl font-semibold text-erpNavy">Portal Publico da Loja</h2>
                            </div>
                            <a href="#acesso-restrito" class="inline-flex items-center rounded-xl border border-erpBorder bg-white px-4 py-2 text-sm font-semibold text-erpNavy hover:bg-erpBg xl:hidden">
                                Entrar na area restrita
                            </a>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <?php foreach (array_slice($publicConteudos, 0, 4) as $item): ?>
                                <?php
                                $tipo = trim((string) ($item['tipo'] ?? 'info'));
                                $titulo = trim((string) ($item['titulo'] ?? 'Publicacao oficial'));
                                $resumo = trim((string) ($item['resumo'] ?? 'Atualizacao institucional da Loja para visitantes e comunidade.'));
                                $inicioEm = trim((string) ($item['inicio_em'] ?? ''));
                                $link = trim((string) ($item['link_url'] ?? ''));
                                ?>
                                <article class="rounded-2xl border border-erpBorder bg-white px-5 py-5 shadow-sm">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="inline-flex items-center rounded-full border border-erpBorder bg-slate-50 px-3 py-1 text-[0.68rem] font-semibold uppercase tracking-[0.18em] text-erpMuted">
                                            <?= htmlspecialchars($tipo) ?>
                                        </span>
                                        <?php if ($inicioEm !== ''): ?>
                                            <span class="text-xs text-erpMuted"><?= htmlspecialchars($inicioEm) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <h3 class="mt-3 text-base font-semibold leading-6 text-erpText"><?= htmlspecialchars($titulo) ?></h3>
                                    <p class="mt-2 text-sm leading-6 text-erpMuted"><?= htmlspecialchars($resumo) ?></p>
                                    <?php if ($link !== '' && $link !== '#'): ?>
                                        <a class="mt-3 inline-flex items-center text-sm font-semibold text-erpNavy underline decoration-erpBorder underline-offset-4 hover:decoration-erpGold" href="<?= htmlspecialchars($link) ?>" target="_blank" rel="noopener noreferrer">
                                            Ver fonte
                                        </a>
                                    <?php endif; ?>
                                </article>
                            <?php endforeach; ?>
                        </div>

                        <div class="mt-8 rounded-2xl border border-erpBorder bg-white px-5 py-5 shadow-sm">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <div class="text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-erpMuted">Galeria publica</div>
                                    <h3 class="mt-1 text-lg font-semibold text-erpNavy">Fotos e videos da proposta</h3>
                                </div>
                                <div class="text-xs font-semibold uppercase tracking-[0.2em] text-erpGold">Carrossel horizontal</div>
                            </div>
                            <div class="mt-4 flex gap-4 overflow-x-auto pb-2">
                                <?php foreach ($publicMedia as $media): ?>
                                    <?php
                                    $mediaFile = (string) ($media['arquivo'] ?? '');
                                    $mediaTitle = (string) ($media['titulo'] ?? 'Midia publica');
                                    ?>
                                    <article class="min-w-[280px] max-w-[300px] rounded-2xl border border-erpBorder bg-slate-50 p-3">
                                        <div class="aspect-[3/2] overflow-hidden rounded-xl border border-erpBorder bg-white p-2">
                                            <img src="<?= htmlspecialchars($mediaFile) ?>" alt="<?= htmlspecialchars($mediaTitle) ?>" class="h-full w-full object-contain" loading="lazy">
                                        </div>
                                        <div class="mt-3 text-sm font-semibold text-erpText"><?= htmlspecialchars($mediaTitle) ?></div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="mt-8 rounded-2xl border border-erpBorder bg-white px-5 py-5 shadow-sm">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <div class="text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-erpMuted">Parcerias</div>
                                    <h3 class="mt-1 text-lg font-semibold text-erpNavy">Espacos de publicidade institucional</h3>
                                </div>
                                <div class="text-xs font-semibold uppercase tracking-[0.2em] text-erpGold">Sem trackers</div>
                            </div>
                            <div class="mt-4 grid gap-4 md:grid-cols-3">
                                <?php foreach (array_slice($publicAds, 0, 3) as $ad): ?>
                                    <?php
                                    $adTitle = trim((string) ($ad['titulo'] ?? 'Espaco reservado para publicidade'));
                                    $adResumo = trim((string) ($ad['resumo'] ?? 'Sua marca pode apoiar projetos da Loja.'));
                                    $adLink = trim((string) ($ad['link_url'] ?? '#'));
                                    $adImage = trim((string) ($ad['imagem_url'] ?? ''));
                                    if ($adImage === '') {
                                        $adImage = \App\Core\Tenant\TenantAssetResolver::resolve(
                                            $tenantSlug,
                                            'portal/publicidade/cards/card-reservado-01.svg',
                                            '/assets/portal/publicidade/cards/card-reservado-01.svg'
                                        );
                                    }
                                    ?>
                                    <article class="rounded-2xl border border-erpBorder bg-slate-50 p-3">
                                        <div class="aspect-[4/3] overflow-hidden rounded-xl border border-erpBorder bg-white p-2">
                                            <img src="<?= htmlspecialchars($adImage) ?>" alt="<?= htmlspecialchars($adTitle) ?>" class="h-full w-full object-contain" loading="lazy">
                                        </div>
                                        <div class="mt-3 text-sm font-semibold text-erpText"><?= htmlspecialchars($adTitle) ?></div>
                                        <p class="mt-1 text-sm leading-6 text-erpMuted"><?= htmlspecialchars($adResumo) ?></p>
                                        <?php if ($adLink !== '' && $adLink !== '#'): ?>
                                            <a class="mt-2 inline-flex items-center text-sm font-semibold text-erpNavy underline decoration-erpBorder underline-offset-4 hover:decoration-erpGold" href="<?= htmlspecialchars($adLink) ?>" target="_blank" rel="noopener noreferrer">
                                                Saiba mais
                                            </a>
                                        <?php else: ?>
                                            <span class="mt-2 inline-flex items-center text-xs font-semibold uppercase tracking-[0.18em] text-erpMuted">
                                                Espaco reservado para publicidade
                                            </span>
                                        <?php endif; ?>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <aside id="acesso-restrito" class="order-2 border-t border-erpBorder bg-slate-50/80 px-6 py-7 sm:px-8 xl:border-l xl:border-t-0 xl:px-8 xl:py-10">
                        <div class="xl:sticky xl:top-6">
                            <div class="rounded-[1.7rem] border border-erpBorder bg-white px-5 py-6 shadow-sm">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-erpBorder bg-slate-50">
                                        <?php if ($logoLogin): ?>
                                            <img src="<?= htmlspecialchars($logoLogin) ?>" alt="Brasao da Loja" class="h-10 w-10 object-contain">
                                        <?php else: ?>
                                            <span class="text-lg font-semibold text-erpGold">&#8756;</span>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div class="text-[0.68rem] font-semibold uppercase tracking-[0.22em] text-erpGold">Acesso restrito</div>
                                        <h3 class="mt-1 text-xl font-semibold text-erpNavy">Entrar no sistema</h3>
                                    </div>
                                </div>
                                <p class="mt-3 text-sm leading-6 text-erpMuted">
                                    Esta area e exclusiva para membros autorizados. Nenhum modulo interno e exibido antes do login.
                                </p>

                                <?php if (!$tenantResolved): ?>
                                    <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-900">
                                        <?= htmlspecialchars($tenantUnavailableMessage !== '' ? $tenantUnavailableMessage : 'Loja nao identificada. Verifique a configuracao do ambiente.') ?>
                                    </div>
                                <?php endif; ?>

                                <form action="/login" method="POST" class="mt-5 space-y-4">
                                    <div>
                                        <label class="mb-2 block text-sm font-semibold text-erpText">CIM / Matricula</label>
                                        <input
                                            type="text"
                                            name="matricula"
                                            <?= $tenantResolved ? '' : 'disabled' ?>
                                            class="block w-full rounded-xl border border-erpBorder bg-slate-50 px-4 py-3 text-base text-erpText placeholder-slate-400 focus:border-erpGold focus:bg-white focus:outline-none"
                                            placeholder="Digite seu CIM"
                                        >
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-sm font-semibold text-erpText">Senha de acesso</label>
                                        <input
                                            type="password"
                                            name="password"
                                            <?= $tenantResolved ? '' : 'disabled' ?>
                                            class="block w-full rounded-xl border border-erpBorder bg-slate-50 px-4 py-3 text-base text-erpText placeholder-slate-400 focus:border-erpGold focus:bg-white focus:outline-none"
                                            placeholder="Digite sua senha"
                                        >
                                    </div>

                                    <?php if (isset($erroLogin)): ?>
                                        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm leading-6 text-rose-800">
                                            <?= htmlspecialchars($erroLogin) ?>
                                        </div>
                                    <?php endif; ?>

                                    <button type="submit" name="acao" value="login" <?= $tenantResolved ? '' : 'disabled' ?> class="inline-flex w-full items-center justify-center rounded-xl border border-transparent bg-erpNavyDeep px-6 py-3 text-base font-semibold text-white transition hover:opacity-95 disabled:cursor-not-allowed disabled:opacity-60">
                                        Entrar no sistema
                                    </button>
                                    <button type="submit" name="acao" value="solicitar" <?= $tenantResolved ? '' : 'disabled' ?> class="inline-flex w-full items-center justify-center rounded-xl border border-erpBorder bg-white px-6 py-3 text-base font-semibold text-erpNavy transition hover:bg-erpBg disabled:cursor-not-allowed disabled:opacity-60">
                                        Solicitar acesso
                                    </button>
                                </form>

                                <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm leading-6 text-erpMuted">
                                    Em caso de duvida de acesso, procure a Secretaria para validacao cadastral.
                                </div>
                            </div>
                        </div>
                    </aside>
                </section>
            </main>
        </div>
    </div>
</body>
</html>
