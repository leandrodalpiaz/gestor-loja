<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso ao Gestor de Loja</title>
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
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
$logoRenascencaLogin = null;
foreach ([
    '/assets/logo-renascenca.png',
    '/assets/logo-renascenca.svg',
    '/assets/logo-loja-renascenca.png',
    '/assets/logo-loja-renascenca.svg',
] as $logoPath) {
    if (file_exists(__DIR__ . '/../../public' . $logoPath)) {
        $logoRenascencaLogin = $logoPath;
        break;
    }
}
?>
<body class="erp-readable min-h-screen bg-[radial-gradient(circle_at_top,#f9fbfd_0%,#eef3f8_55%,#e8edf4_100%)] font-sans text-erpText">
    <div class="min-h-screen px-5 py-6 sm:px-8 xl:px-10 xl:py-8">
        <div class="mx-auto flex min-h-[calc(100vh-3rem)] max-w-[1560px] items-center justify-center">
            <main class="w-full overflow-hidden rounded-[2rem] border border-erpBorder bg-erpSurface shadow-shell">
                <section class="border-b border-erpBorder bg-[linear-gradient(135deg,#173452_0%,#21466c_58%,#2d5d87_100%)] px-8 py-8 text-white sm:px-10 xl:px-12 xl:py-10">
                    <div class="flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
                        <div class="max-w-4xl">
                            <div class="inline-flex items-center rounded-full border border-white/15 bg-white/10 px-4 py-2 text-[0.7rem] font-semibold uppercase tracking-[0.28em] text-white/80">
                                Gestor de Loja
                            </div>
                            <h1 class="mt-6 text-4xl font-semibold leading-tight sm:text-[2.8rem] xl:text-[3.25rem]">
                                Acesso administrativo da Loja em uma unica entrada operacional.
                            </h1>
                            <p class="mt-4 max-w-3xl text-base leading-8 text-white/78 xl:text-lg">
                                Secretaria, Tesouraria, Chancelaria, nominata e paineis de trabalho em um ambiente web pensado para uso de mesa, com leitura ampla e contexto institucional claro.
                            </p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-3 xl:min-w-[34rem]">
                            <div class="rounded-[1.2rem] border border-white/15 bg-white/10 px-4 py-4">
                                <div class="text-[0.68rem] font-semibold uppercase tracking-[0.24em] text-white/60">Modo</div>
                                <div class="mt-2 text-lg font-semibold">Desktop-first</div>
                            </div>
                            <div class="rounded-[1.2rem] border border-white/15 bg-white/10 px-4 py-4">
                                <div class="text-[0.68rem] font-semibold uppercase tracking-[0.24em] text-white/60">Canal rapido</div>
                                <div class="mt-2 text-lg font-semibold">Telegram</div>
                            </div>
                            <div class="rounded-[1.2rem] border border-white/15 bg-white/10 px-4 py-4">
                                <div class="text-[0.68rem] font-semibold uppercase tracking-[0.24em] text-white/60">Escopo</div>
                                <div class="mt-2 text-lg font-semibold">ERP interno</div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="grid gap-0 xl:grid-cols-[minmax(0,1.25fr)_360px]">
                    <div class="px-8 py-8 sm:px-10 xl:px-12 xl:py-10">
                        <div class="max-w-[760px]">
                            <div class="flex items-center gap-4">
                                <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-erpBorder bg-slate-50">
                                    <?php if ($logoRenascencaLogin): ?>
                                        <img src="<?= htmlspecialchars($logoRenascencaLogin) ?>" alt="Logotipo da Loja Renascenca" class="h-12 w-12 object-contain">
                                    <?php else: ?>
                                        <span class="text-2xl font-semibold text-erpGold">&#8756;</span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div class="text-[0.72rem] font-semibold uppercase tracking-[0.28em] text-erpGold">Acesso administrativo</div>
                                    <h2 class="mt-2 text-[2rem] font-semibold leading-tight text-erpNavy">Entrar no Gestor de Loja</h2>
                                    <p class="mt-2 text-base leading-7 text-erpMuted">
                                        Use seu CIM e sua palavra de passe para acessar os modulos administrativos da Loja.
                                    </p>
                                </div>
                            </div>

                            <form action="/login" method="POST" class="mt-8 space-y-6">
                                <div class="grid gap-5 xl:grid-cols-2">
                                    <div class="xl:col-span-2">
                                        <label class="mb-2 block text-sm font-semibold text-erpText">CIM / Matricula</label>
                                        <div class="relative">
                                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                                <svg class="h-5 w-5 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M10 2a1 1 0 00-1 1v1a1 1 0 002 0V3a1 1 0 00-1-1zM4 6.908V16a2 2 0 002 2h8a2 2 0 002-2V6.908a2.5 2.5 0 00-1.042-2.031L11.542 2.3A2.5 2.5 0 0010 2.062V6.5h2v2h-2v2h2v2h-2v2h-2v-2h-2v-2h2v-2h-2v-2H9.99V2.062a2.5 2.5 0 00-1.542.237L5.042 4.877A2.5 2.5 0 004 6.908l.001.001z" clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                            <input
                                                type="text"
                                                name="matricula"
                                                class="block w-full rounded-xl border border-erpBorder bg-slate-50 py-4 pl-12 pr-4 text-base text-erpText placeholder-slate-400 focus:border-erpGold focus:bg-white focus:outline-none"
                                                placeholder="Digite seu CIM"
                                            >
                                        </div>
                                    </div>

                                    <div class="xl:col-span-2">
                                        <label class="mb-2 block text-sm font-semibold text-erpText">Palavra de Passe</label>
                                        <div class="relative">
                                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                                <svg class="h-5 w-5 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                            <input
                                                type="password"
                                                name="password"
                                                class="block w-full rounded-xl border border-erpBorder bg-slate-50 py-4 pl-12 pr-4 text-base text-erpText placeholder-slate-400 focus:border-erpGold focus:bg-white focus:outline-none"
                                                placeholder="Digite sua palavra de passe"
                                            >
                                        </div>
                                    </div>
                                </div>

                                <?php if (isset($erroLogin)): ?>
                                    <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-4 text-sm leading-6 text-rose-800">
                                        <?= htmlspecialchars($erroLogin) ?>
                                    </div>
                                <?php endif; ?>

                                <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                                    <div class="max-w-xl rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm leading-6 text-erpMuted">
                                        Em caso de duvida sobre acesso, procure a administracao da Loja. O painel web e o ambiente principal para operacao administrativa desktop.
                                    </div>
                                    <button type="submit" class="inline-flex min-w-[260px] items-center justify-center rounded-xl border border-transparent bg-erpNavyDeep px-6 py-4 text-base font-semibold text-white transition hover:opacity-95">
                                        <span>Entrar no sistema</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="ml-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                        </svg>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <aside class="border-t border-erpBorder bg-slate-50/80 px-8 py-8 xl:border-l xl:border-t-0 xl:px-8 xl:py-10">
                        <div class="space-y-5">
                            <div>
                                <div class="text-[0.68rem] font-semibold uppercase tracking-[0.24em] text-erpGold">Contexto</div>
                                <div class="mt-2 text-xl font-semibold text-erpNavy">Uso administrativo continuo</div>
                                <p class="mt-2 text-sm leading-7 text-erpMuted">
                                    Esta entrada concentra o acesso aos modulos que estruturam a rotina institucional da Loja.
                                </p>
                            </div>

                            <div class="space-y-3">
                                <article class="rounded-[1.2rem] border border-erpBorder bg-white px-4 py-4">
                                    <div class="text-[0.68rem] font-semibold uppercase tracking-[0.24em] text-erpMuted">Secretaria</div>
                                    <div class="mt-2 text-sm leading-6 text-erpText">Sessoes, trabalhos, publicacoes e acompanhamento cadastral.</div>
                                </article>
                                <article class="rounded-[1.2rem] border border-erpBorder bg-white px-4 py-4">
                                    <div class="text-[0.68rem] font-semibold uppercase tracking-[0.24em] text-erpMuted">Tesouraria</div>
                                    <div class="mt-2 text-sm leading-6 text-erpText">Obrigacoes, comprovantes, regularidade e fechamento mensal.</div>
                                </article>
                                <article class="rounded-[1.2rem] border border-erpBorder bg-white px-4 py-4">
                                    <div class="text-[0.68rem] font-semibold uppercase tracking-[0.24em] text-erpMuted">Chancelaria e cargos</div>
                                    <div class="mt-2 text-sm leading-6 text-erpText">Efemerides, nominata oficial e paineis por responsabilidade.</div>
                                </article>
                            </div>
                        </div>
                    </aside>
                </section>
            </main>
        </div>
    </div>
</body>
</html>
