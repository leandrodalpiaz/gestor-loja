<?php
/**
 * Componente Drawer (Painel Inferior Deslizante) — PWA Mobile
 *
 * Uso:
 *   $drawerId      = 'meu-drawer';         // ID único no DOM
 *   $drawerTitle   = 'Detalhes do Obreiro'; // Título exibido no cabeçalho
 *   $drawerContent = '<p>Conteúdo aqui</p>';// HTML injetado no corpo
 *   require __DIR__ . '/components/drawer.php';
 *
 * O drawer é controlado via AlpineJS. Para abri-lo externamente:
 *   <button @click="$dispatch('open-drawer', { id: 'meu-drawer' })">Abrir</button>
 *
 * Dependências: AlpineJS (já carregado no shell via CDN ou via erp_head.php).
 */

$drawerId = (string) ($drawerId ?? 'pwa-drawer');
$drawerTitle = (string) ($drawerTitle ?? '');
$drawerContent = (string) ($drawerContent ?? '');
?>

<div x-data="{ open: false }"
     x-on:open-drawer.window="if ($event.detail.id === '<?= htmlspecialchars($drawerId) ?>') open = true"
     x-on:keydown.escape.window="open = false"
     class="relative z-50"
     id="<?= htmlspecialchars($drawerId) ?>">

    <!-- Overlay escuro -->
    <div x-show="open"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="open = false"
         class="fixed inset-0 bg-black/50 backdrop-blur-sm"
         style="display: none;"></div>

    <!-- Painel inferior deslizante -->
    <div x-show="open"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="translate-y-full"
         x-transition:enter-end="translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-y-0"
         x-transition:leave-end="translate-y-full"
         class="fixed inset-x-0 bottom-0 z-50 transform"
         style="display: none;">

        <div class="mx-auto max-w-lg rounded-t-2xl bg-white shadow-[0_-8px_30px_rgba(0,0,0,0.15)]"
             style="max-height: 85vh; padding-bottom: env(safe-area-inset-bottom, 0px);">

            <!-- Indicador de arrasto (handle) -->
            <div class="flex justify-center pt-3 pb-1">
                <div class="h-1 w-10 rounded-full bg-gray-300"></div>
            </div>

            <!-- Cabeçalho do Drawer -->
            <div class="flex items-center justify-between border-b border-gray-100 px-5 pb-3">
                <?php if ($drawerTitle !== ''): ?>
                    <h2 class="text-base font-bold text-erpNavy"><?= htmlspecialchars($drawerTitle) ?></h2>
                <?php else: ?>
                    <div></div>
                <?php endif; ?>
                <button @click="open = false"
                        class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors"
                        aria-label="Fechar">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Conteúdo do Drawer (scroll interno) -->
            <div class="overflow-y-auto overscroll-contain px-5 py-4" style="max-height: calc(85vh - 80px);">
                <?= $drawerContent ?>
            </div>
        </div>
    </div>
</div>
