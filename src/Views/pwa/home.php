<?php
declare(strict_types=1);

/**
 * PWA Home - Modelo "Mural & Atalhos"
 * Foco: Efemérides Dinâmicas (Mural) e Grid Operacional (Tiles)
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

$links = is_array($links ?? null) ? $links : [];
$usuarioNome = (string) ($_SESSION['usuario_nome'] ?? 'Irmão');
$usuarioCargo = (string) ($_SESSION['usuario_cargo'] ?? '');
$tenantSlug = (string) ($_SESSION['tenant_slug'] ?? '');
$tenantName = trim((string) ($_SESSION['tenant_name'] ?? 'Oficina Digital'));
$logoUrl = \App\Core\Tenant\TenantAssetResolver::resolveLogo($tenantSlug);

$proximaSessao = $proximaSessao ?? null;
$proximaSessaoResposta = $proximaSessaoResposta ?? null;
$ultimosComunicados = is_array($ultimosComunicados ?? null) ? $ultimosComunicados : [];

$pwaPageTitle = 'Área do Irmão';
$pwaActiveTab = 'inicio';

ob_start();
?>

<div class="pwa-stack pb-8">
    <section class="-mx-4 -mt-4 mb-2">
        <?php if (empty($efemerides_reais)): ?>
            <div class="pwa-carousel pwa-scrollbar-none snap-x snap-mandatory overflow-x-auto flex gap-0">
                <div class="relative w-full shrink-0 snap-start aspect-[9/10] overflow-hidden shadow-2xl">
                    <img src="/assets/images/templates/efemerides/card_oficial_sessao.png" class="h-full w-full object-cover" alt="Próxima Sessão">
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-transparent to-transparent"></div>
                    <div class="absolute bottom-6 left-6 right-6">
                        <p class="pwa-eyebrow text-amber-400 drop-shadow-md">Avisos Gerais</p>
                        <h2 class="text-3xl font-serif font-bold text-white leading-tight drop-shadow-lg">Aguardando Próxima Sessão</h2>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="pwa-carousel pwa-scrollbar-none snap-x snap-mandatory overflow-x-auto flex gap-0">
                <?php foreach ($efemerides_reais as $card): ?>
                    <div class="relative w-full shrink-0 snap-start aspect-[9/10] overflow-hidden shadow-2xl">
                        <img src="<?= htmlspecialchars($card['url_imagem']) ?>" class="h-full w-full object-cover" alt="Efeméride">
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-transparent to-transparent"></div>
                        <div class="absolute bottom-6 left-6 right-6">
                            <p class="pwa-eyebrow text-amber-400 drop-shadow-md"><?= htmlspecialchars($card['legenda_tipo']) ?></p>
                            <h2 class="text-3xl font-serif font-bold text-white leading-tight drop-shadow-lg"><?= htmlspecialchars($card['titulo_homenagem']) ?></h2>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="px-2">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-xs font-bold uppercase tracking-widest text-slate-400">Escritório Digital</h3>
            <span class="h-px flex-grow ml-4 bg-white/10"></span>
        </div>
        
        <div class="grid grid-cols-3 gap-3">
            <?php
            $atalhos = [
                ['id' => 'sessoes', 'label' => 'Sessões', 'href' => '/pwa/sessoes', 'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', 'cond' => !empty($links['sessoes'])],
                ['id' => 'secretaria', 'label' => 'Secretaria', 'href' => '/pwa/secretaria', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'cond' => true],
                ['id' => 'tesouraria', 'label' => 'Tesouraria', 'href' => '/pwa/tesouraria', 'icon' => 'M12 8c-1.657 0-3 .672-3 1.5S10.343 11 12 11s3 .672 3 1.5S13.657 14 12 14m0-6v6m0 0v2m8-6a8 8 0 11-16 0 8 8 0 0116 0z', 'cond' => true],
                ['id' => 'biblioteca', 'label' => 'Biblioteca', 'href' => '/pwa/biblioteca', 'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253', 'cond' => !empty($links['biblioteca'])],
                ['id' => 'chancelaria', 'label' => 'Chancelaria', 'href' => '/pwa/chancelaria', 'icon' => 'M17 20h5V4H2v16h5m10 0v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6m10 0H7', 'cond' => !empty($links['chancelaria'])],
                ['id' => 'perfil', 'label' => 'Meu CIM', 'href' => '/pwa/perfil', 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z', 'cond' => true],
                ['id' => 'ajustes', 'label' => 'Ajustes', 'href' => '/pwa/perfil', 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z', 'cond' => true],
                ['id' => 'sistema', 'label' => 'Sistema', 'href' => '/pwa/admin', 'icon' => 'M15 12a3 3 0 11-6 0 3 3 0 016 0z', 'cond' => !empty($_SESSION['is_system_admin']) && !empty($_ENV['FEATURE_PWA_ADMIN_CRUD']) && filter_var((string) $_ENV['FEATURE_PWA_ADMIN_CRUD'], FILTER_VALIDATE_BOOL)],
            ];

            foreach ($atalhos as $a):
                if (isset($a['cond']) && !$a['cond']) {
                    continue;
                }
            ?>
                <a href="<?= $a['href'] ?>" class="flex flex-col items-center justify-center p-4 rounded-3xl bg-white/5 border border-white/10 active:scale-95 transition-transform aspect-square">
                    <div class="mb-2 text-amber-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="<?= $a['icon'] ?>" />
                        </svg>
                    </div>
                    <span class="text-[0.7rem] font-bold uppercase tracking-tighter text-slate-200"><?= $a['label'] ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="pwa-card p-4 mx-2 border-amber-500/20 bg-amber-500/5">
        <p class="pwa-eyebrow text-amber-200/50">Status Financeiro</p>
        <div class="flex justify-between items-end mt-2">
            <div>
                <p class="text-xs text-slate-400 font-medium">Próximo Vencimento</p>
                <p class="text-xl font-bold text-white">R$ 150,00</p>
            </div>
            <a href="/pwa/tesouraria" class="px-4 py-2 bg-amber-500 text-slate-950 text-xs font-black rounded-xl uppercase tracking-tighter">Pagar PIX</a>
        </div>
    </section>
</div>

<?php
$pwaContent = ob_get_clean();
require __DIR__ . '/shell.php';
?>
