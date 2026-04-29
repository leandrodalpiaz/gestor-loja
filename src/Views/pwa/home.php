<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

$links = is_array($links ?? null) ? $links : [];
$usuarioNome = (string) ($_SESSION['usuario_nome'] ?? 'Irmão');
$usuarioCargo = (string) ($_SESSION['usuario_cargo'] ?? '');

$pwaPageTitle = 'Acesso Rápido';

ob_start();
?>

<div class="p-4 sm:p-6">
    <div class="mb-6 rounded-2xl border border-erpBorder bg-erpSurface p-5 shadow-sm">
        <h2 class="text-xl font-bold text-erpNavy">Olá, <?= htmlspecialchars($usuarioNome) ?></h2>
        <p class="mt-1 text-sm text-erpMuted">
            Bem-vindo ao acesso rápido. Use os atalhos abaixo para as operações do dia a dia.
            <?= $usuarioCargo !== '' ? 'Seu cargo atual é ' . htmlspecialchars($usuarioCargo) . '.' : '' ?>
        </p>
        <div class="mt-4 text-xs text-erpMuted">
            Para uma experiência completa, instale este app na tela de início do seu celular.
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
        <?php
        $renderIcon = function (string $href, string $label, string $description, string $iconPath) {
            $baseClasses = 'flex flex-col items-center justify-center text-center rounded-2xl border border-erpBorder bg-erpSurface p-4 aspect-square transition-all duration-150 hover:border-erpNavy hover:shadow-lg hover:-translate-y-1';
            $textLabelClasses = 'mt-3 font-semibold text-erpNavy';
            $textDescriptionClasses = 'mt-1 text-xs text-erpMuted';

            echo "<a href='{$href}' class='{$baseClasses}'>";
            echo "<div class='flex h-12 w-12 items-center justify-center rounded-full bg-erpBg'><svg xmlns='http://www.w3.org/2000/svg' class='h-7 w-7 text-erpNavy' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='2'>{$iconPath}</svg></div>";
            echo "<div class='{$textLabelClasses}'>{$label}</div>";
            echo "<p class='{$textDescriptionClasses}'>{$description}</p>";
            echo "</a>";
        };

        if (!empty($links['sessoes'])) {
            $renderIcon(
                '/pwa/sessoes',
                'Sessões',
                'Presença e ágape',
                '<path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />'
            );
        }
        if (!empty($links['biblioteca'])) {
            $renderIcon(
                '/pwa/biblioteca',
                'Biblioteca',
                'Consultar acervo',
                '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v11.494m-9-5.747l9 5.747 9-5.747M12 6.253L3 12m9-5.747l9 5.747M3 12l9 5.747" />'
            );
        }
        if (!empty($links['comunicacao'])) {
            $renderIcon(
                '/pwa/comunicacao',
                'Comunicação',
                'Recados oficiais',
                '<path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />'
            );
        }
        if (!empty($_ENV['FEATURE_PWA_ADMIN_CRUD']) && filter_var((string) $_ENV['FEATURE_PWA_ADMIN_CRUD'], FILTER_VALIDATE_BOOL)) {
            $renderIcon(
                '/pwa/admin',
                'Admin',
                'Atalhos de gestão',
                '<path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />'
            );
        }

        if (empty(array_filter($links)) && (empty($_ENV['FEATURE_PWA_ADMIN_CRUD']) || !filter_var((string) $_ENV['FEATURE_PWA_ADMIN_CRUD'], FILTER_VALIDATE_BOOL))) {
            echo '<div class="col-span-2 sm:col-span-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-900">';
            echo 'Nenhum módulo PWA está habilitado neste ambiente. Ative as `FEATURE_PWA_*` no `.env` para ver os atalhos.';
            echo '</div>';
        }
        ?>
    </div>
</div>

<?php
$pwaContent = ob_get_clean();
require __DIR__ . '/shell.php';
?>
