<?php
/**
 * Componente Drawer (Painel Inferior Deslizante) — PWA Mobile Dark
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
 * Dependências: AlpineJS (já carregado no shell via CDN).
 */

$drawerId      = (string) ($drawerId ?? 'pwa-drawer');
$drawerTitle   = (string) ($drawerTitle ?? '');
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
         class="fixed inset-0 bg-black/60 backdrop-blur-sm"
         style="display: none;"></div>

    <!-- Painel inferior deslizante — dark -->
    <div x-show="open"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="translate-y-full"
         x-transition:enter-end="translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-y-0"
         x-transition:leave-end="translate-y-full"
         class="fixed inset-x-0 bottom-0 z-50 transform"
         style="display: none; padding-bottom: env(safe-area-inset-bottom, 0px);">

        <div style="
            max-width: 32rem;
            margin: 0 auto;
            max-height: 85vh;
            border-radius: 1.25rem 1.25rem 0 0;
            background: #0f172a;
            border: 1px solid rgba(255,255,255,0.10);
            border-bottom: none;
            box-shadow: 0 -20px 60px rgba(2,6,23,0.65);
            display: flex;
            flex-direction: column;
        ">

            <!-- Handle de arrasto -->
            <div style="display:flex;justify-content:center;padding:0.75rem 0 0.5rem;">
                <div style="width:2.5rem;height:4px;border-radius:999px;background:rgba(255,255,255,0.20);"></div>
            </div>

            <!-- Cabeçalho do Drawer -->
            <div style="
                display:flex;align-items:center;justify-content:space-between;
                padding:0 1.25rem 0.75rem;
                border-bottom:1px solid rgba(255,255,255,0.08);
            ">
                <?php if ($drawerTitle !== ''): ?>
                    <h2 style="font-size:0.9375rem;font-weight:700;color:#f1f5f9;margin:0;"><?= htmlspecialchars($drawerTitle) ?></h2>
                <?php else: ?>
                    <div></div>
                <?php endif; ?>
                <button @click="open = false"
                        style="
                            width:32px;height:32px;border-radius:50%;
                            background:rgba(255,255,255,0.08);
                            border:1px solid rgba(255,255,255,0.10);
                            display:flex;align-items:center;justify-content:center;
                            color:#94a3b8;cursor:pointer;
                        "
                        aria-label="Fechar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Conteúdo scrollável -->
            <div style="overflow-y:auto;overscroll-behavior:contain;padding:1rem 1.25rem 1.5rem;flex:1;-webkit-overflow-scrolling:touch;">
                <?= $drawerContent ?>
            </div>
        </div>
    </div>
</div>
