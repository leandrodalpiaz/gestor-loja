        </main>

        <?php if (!empty($appShellBottomNavItems)): ?>
            <nav class="fixed inset-x-0 bottom-0 z-30 border-t border-erp-border bg-erp-surface/95 backdrop-blur lg:hidden">
                <div class="grid grid-cols-<?= (int) min(5, max(1, count($appShellBottomNavItems))) ?>">
                    <?php foreach ($appShellBottomNavItems as $item): ?>
                        <?php $isActive = (string) ($item['href'] ?? '') === $appShellActiveHref; ?>
                        <a href="<?= htmlspecialchars((string) $item['href']) ?>"
                           class="min-h-[52px] px-2 py-2 text-center text-[0.68rem] font-semibold <?= $isActive ? 'text-erp-brand' : 'text-erp-muted' ?>">
                            <span class="block truncate"><?= htmlspecialchars((string) $item['label']) ?></span>
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
