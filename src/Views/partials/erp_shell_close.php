        </main>

        <?php if (!empty($appShellBottomNavItems)): ?>
            <?php
            // Função simples para mapear ícones dinâmicos de acordo com o título do menu no ERP
            $getBottomIcon = function($label) {
                $l = strtolower($label);
                if (str_contains($l, 'painel') || str_contains($l, 'início')) {
                    // Dashboard/Home
                    return '<path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />';
                }
                if (str_contains($l, 'sessões') || str_contains($l, 'reuniões') || str_contains($l, 'agenda')) {
                    // Calendar
                    return '<path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />';
                }
                if (str_contains($l, 'balaústre') || str_contains($l, 'ata') || str_contains($l, 'trabalho')) {
                    // Document
                    return '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />';
                }
                if (str_contains($l, 'obreiro') || str_contains($l, 'membro') || str_contains($l, 'quadro')) {
                    // Users
                    return '<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />';
                }
                if (str_contains($l, 'caixa') || str_contains($l, 'finanç') || str_contains($l, 'tesouraria') || str_contains($l, 'obrigação')) {
                    // Money/Coin
                    return '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />';
                }
                if (str_contains($l, 'convite') || str_contains($l, 'visitante')) {
                    // Ticket/Mail
                    return '<path stroke-linecap="round" stroke-linejoin="round" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />';
                }
                // Default: Menu/Dots
                return '<path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />';
            };
            ?>
            <nav class="fixed inset-x-0 bottom-0 z-50 border-t border-white/10 bg-[#0E2640]/95 backdrop-blur-xl lg:hidden shadow-[0_-10px_40px_rgba(0,0,0,0.5)]" style="padding-bottom: max(env(safe-area-inset-bottom), 0.25rem);">
                <div class="grid grid-cols-<?= (int) min(5, max(1, count($appShellBottomNavItems))) ?> items-center justify-items-center pt-2">
                    <?php foreach ($appShellBottomNavItems as $item): ?>
                        <?php $isActive = (string) ($item['href'] ?? '') === $appShellActiveHref; ?>
                        <a href="<?= htmlspecialchars((string) $item['href']) ?>"
                           class="flex w-full flex-col items-center justify-center rounded-2xl px-1 py-1.5 transition-colors <?= $isActive ? 'text-[#C9A227]' : 'text-white/50 hover:text-white/90' ?>">
                            
                            <div class="relative mb-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 transition-transform <?= $isActive ? 'scale-110 drop-shadow-[0_0_8px_rgba(201,162,39,0.5)]' : '' ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="<?= $isActive ? '2.5' : '1.8' ?>">
                                    <?= $getBottomIcon($item['label']) ?>
                                </svg>
                                <?php if ($isActive): ?>
                                    <div class="absolute -bottom-1.5 left-1/2 h-1 w-1 -translate-x-1/2 rounded-full bg-[#C9A227] opacity-100 shadow-[0_0_6px_#C9A227]"></div>
                                <?php endif; ?>
                            </div>
                            
                            <span class="text-[0.62rem] font-bold leading-tight tracking-tight <?= $isActive ? 'opacity-100' : 'opacity-80' ?>"><?= htmlspecialchars((string) $item['label']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </nav>
        <?php endif; ?>
    </div>
</div>
<script>
    (function () {
        var navTargets = document.querySelectorAll('a[href]:not([href^="#"]):not([target="_blank"])');
        var main = document.querySelector('main');
        if (!main || !navTargets.length) return;

        function showSkeleton() {
            if (main.querySelector('[data-pwa-skeleton="1"]')) return;
            var wrap = document.createElement('div');
            wrap.setAttribute('data-pwa-skeleton', '1');
            wrap.className = 'grid gap-3 mt-2';
            wrap.innerHTML =
                '<div class="erp-skeleton h-12"></div>' +
                '<div class="erp-skeleton h-20"></div>' +
                '<div class="erp-skeleton h-20"></div>';
            main.appendChild(wrap);
        }

        navTargets.forEach(function (el) {
            el.addEventListener('click', function () {
                showSkeleton();
            });
        });
    })();
</script>
</body>
</html>
