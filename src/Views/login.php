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
                        erpNavyDeep: '#17314F',
                        erpGold: '#B8960C',
                        erpBg: '#F4F7FB',
                        erpSurface: '#FFFFFF',
                        erpBorder: '#D9E0EA',
                        erpText: '#243447',
                        erpMuted: '#5B6B7D',
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    },
                    boxShadow: {
                        shell: '0 24px 60px rgba(30, 58, 95, 0.10)',
                        panel: '0 24px 80px rgba(23, 49, 79, 0.18)',
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
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
<body class="min-h-screen bg-[linear-gradient(180deg,#f4f7fb_0%,#eef2f7_100%)] font-sans text-erpText">
    <div class="min-h-screen px-5 py-6 sm:px-8 xl:px-10 xl:py-8">
        <div class="mx-auto flex min-h-[calc(100vh-3rem)] max-w-[1700px] flex-col overflow-hidden rounded-[2rem] border border-erpBorder bg-white shadow-shell xl:grid xl:grid-cols-[minmax(720px,1.25fr)_minmax(540px,0.75fr)]">
            <section class="relative overflow-hidden bg-[linear-gradient(135deg,#17314f_0%,#21466c_44%,#2f5f89_100%)] text-white">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(184,150,12,0.22),transparent_26%),radial-gradient(circle_at_14%_84%,rgba(255,255,255,0.08),transparent_22%)]"></div>
                <div class="relative flex h-full flex-col justify-between px-10 py-10 sm:px-12 xl:px-16 xl:py-14">
                    <div>
                        <div class="inline-flex items-center rounded-full border border-white/15 bg-white/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.28em] text-white/80">
                            Gestor de Loja
                        </div>
                        <h1 class="mt-8 max-w-3xl text-4xl font-semibold leading-[1.02] sm:text-5xl xl:text-[3.9rem]">
                            Operacao administrativa clara, continua e preparada para desktop.
                        </h1>
                        <p class="mt-6 max-w-2xl text-base leading-8 text-white/78 xl:text-lg">
                            O painel web concentra Secretaria, Tesouraria, Chancelaria, nominata e parametros institucionais com leitura ampla, contexto visivel e navegação de trabalho.
                        </p>

                        <div class="mt-10 grid gap-4 xl:grid-cols-3">
                            <article class="rounded-[1.5rem] border border-white/15 bg-white/10 p-5">
                                <div class="text-[0.68rem] font-semibold uppercase tracking-[0.28em] text-white/60">Estrutura</div>
                                <div class="mt-3 text-xl font-semibold">Desktop-first</div>
                                <p class="mt-2 text-sm leading-7 text-white/72">Sidebar, leitura ampla e superfícies pensadas para gestão contínua.</p>
                            </article>
                            <article class="rounded-[1.5rem] border border-white/15 bg-white/10 p-5">
                                <div class="text-[0.68rem] font-semibold uppercase tracking-[0.28em] text-white/60">Fluxo móvel</div>
                                <div class="mt-3 text-xl font-semibold">Telegram</div>
                                <p class="mt-2 text-sm leading-7 text-white/72">O bot continua como canal rápido para uso frequente fora da mesa.</p>
                            </article>
                            <article class="rounded-[1.5rem] border border-white/15 bg-white/10 p-5">
                                <div class="text-[0.68rem] font-semibold uppercase tracking-[0.28em] text-white/60">Escopo</div>
                                <div class="mt-3 text-xl font-semibold">ERP interno</div>
                                <p class="mt-2 text-sm leading-7 text-white/72">Uma unica entrada para modulos administrativos e memoria institucional.</p>
                            </article>
                        </div>
                    </div>

                    <div class="mt-10 grid gap-4 border-t border-white/10 pt-8 sm:grid-cols-3">
                        <div>
                            <div class="text-[0.68rem] font-semibold uppercase tracking-[0.28em] text-white/55">Loja</div>
                            <div class="mt-2 text-lg font-semibold">Renascenca n 270</div>
                        </div>
                        <div>
                            <div class="text-[0.68rem] font-semibold uppercase tracking-[0.28em] text-white/55">Canal</div>
                            <div class="mt-2 text-lg font-semibold">Web administrativo</div>
                        </div>
                        <div>
                            <div class="text-[0.68rem] font-semibold uppercase tracking-[0.28em] text-white/55">Acesso</div>
                            <div class="mt-2 text-lg font-semibold">Autenticado por CIM</div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="flex items-center bg-[#fbfcfe] px-6 py-8 sm:px-10 xl:px-14 xl:py-12">
                <div class="mx-auto w-full max-w-[560px]">
                    <div class="overflow-hidden rounded-[1.8rem] border border-erpBorder bg-white shadow-[0_18px_50px_rgba(30,58,95,0.08)]">
                        <div class="border-b border-erpBorder px-8 py-8">
                            <div class="flex items-center gap-4">
                                <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-erpBorder bg-slate-50">
                                    <?php if ($logoRenascencaLogin): ?>
                                        <img src="<?= htmlspecialchars($logoRenascencaLogin) ?>" alt="Logotipo da Loja Renascenca" class="h-12 w-12 object-contain">
                                    <?php else: ?>
                                        <span class="text-2xl font-semibold text-erpGold">&#8756;</span>
                                    <?php endif; ?>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-[0.68rem] font-semibold uppercase tracking-[0.28em] text-erpGold">Acesso administrativo</div>
                                    <h1 class="mt-2 text-[2rem] font-semibold leading-tight text-erpNavy">Entrar no Gestor de Loja</h1>
                                    <p class="mt-2 text-sm leading-6 text-erpMuted">
                                        Acesse os modulos de gestão com seu CIM e sua palavra de passe.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="px-8 py-8">
                            <form action="/login" method="POST" class="space-y-6">
                                <div class="grid gap-5">
                                    <div>
                                        <label class="mb-2 block text-sm font-semibold text-erpText">CIM / Matrícula</label>
                                        <div class="relative">
                                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                                <svg class="h-5 w-5 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M10 2a1 1 0 00-1 1v1a1 1 0 002 0V3a1 1 0 00-1-1zM4 6.908V16a2 2 0 002 2h8a2 2 0 002-2V6.908a2.5 2.5 0 00-1.042-2.031L11.542 2.3A2.5 2.5 0 0010 2.062V6.5h2v2h-2v2h2v2h-2v2h-2v-2h-2v-2h2v-2h-2v-2H9.99V2.062a2.5 2.5 0 00-1.542.237L5.042 4.877A2.5 2.5 0 004 6.908l.001.001z" clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                            <input
                                                type="text"
                                                name="matricula"
                                                class="block w-full rounded-xl border border-erpBorder bg-slate-50 py-3.5 pl-12 pr-4 text-base text-erpText placeholder-slate-400 focus:border-erpGold focus:bg-white focus:outline-none"
                                                placeholder="Digite seu CIM"
                                            >
                                        </div>
                                    </div>

                                    <div>
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
                                                class="block w-full rounded-xl border border-erpBorder bg-slate-50 py-3.5 pl-12 pr-4 text-base text-erpText placeholder-slate-400 focus:border-erpGold focus:bg-white focus:outline-none"
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

                                <div class="grid gap-4 pt-2">
                                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl border border-transparent bg-erpNavyDeep px-5 py-4 text-base font-semibold text-white transition hover:opacity-95">
                                        <span>Entrar no sistema</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="ml-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                        </svg>
                                    </button>
                                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm leading-6 text-erpMuted">
                                        Em caso de dúvida sobre acesso, procure a administração da Loja.
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</body>
</html>
