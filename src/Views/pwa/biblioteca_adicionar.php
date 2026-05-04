<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

$erro = $_GET['erro'] ?? '';
$pwaPageTitle = 'Adicionar Título';
$pwaShowBackButton = true;
$pwaBackUrl = '/pwa/biblioteca';

ob_start();
?>

<div class="p-4 sm:p-6 space-y-4">
    <?php if ($erro === 'isbn_vazio'): ?>
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">
            Por favor, informe um ISBN válido.
        </div>
    <?php elseif ($erro): ?>
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">
            Não foi possível adicionar o livro. Verifique o ISBN e tente novamente.
        </div>
    <?php endif; ?>

    <div class="rounded-2xl border border-erpBorder bg-erpSurface p-5 shadow-sm space-y-4">
        <div>
            <h2 class="text-xl font-bold text-erpNavy leading-tight">Cadastrar por ISBN</h2>
            <p class="mt-1 text-sm text-erpMuted">
                Digite ou escaneie o código de barras do livro. O sistema buscará os dados (como Título e Autor) automaticamente.
            </p>
        </div>

        <form method="post" action="/pwa/biblioteca/adicionar" class="space-y-4 pt-2">
            <div class="space-y-3">
                <label for="isbn" class="block text-sm font-semibold text-erpNavy">Código ISBN</label>
                <input type="text" inputmode="numeric" name="isbn" id="isbn" required
                       class="w-full rounded-lg border border-erpBorder bg-erpBg px-4 py-3 text-sm placeholder-erpMuted focus:border-erpNavy focus:outline-none"
                       placeholder="Ex: 9788531206161">
            </div>

            <button type="submit" class="w-full rounded-lg bg-erpNavy px-4 py-3 text-sm font-semibold text-white transition hover:opacity-90">
                Pesquisar e Adicionar
            </button>
        </form>
    </div>

    <div class="text-center text-xs text-erpMuted">
        O livro será adicionado ao acervo da sua Loja com disponibilidade imediata.
    </div>
</div>

<?php
$pwaContent = ob_get_clean();
require __DIR__ . '/shell.php';
?>
