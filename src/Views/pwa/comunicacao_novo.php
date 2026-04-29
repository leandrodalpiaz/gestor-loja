<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

$payload = is_array($payload ?? null) ? $payload : ['titulo' => '', 'categoria' => 'geral', 'conteudo' => ''];
$erroDb = $erroDb ?? null;
$mensagemSucesso = $mensagemSucesso ?? null;
$mensagemErro = $mensagemErro ?? null;

$pwaPageTitle = 'Novo Comunicado';
$pwaShowBackButton = true;
$pwaBackUrl = '/pwa/comunicacao';

ob_start();
?>

<div class="p-4 sm:p-6 space-y-4">
    <?php if ($erroDb): ?>
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            <p class="font-semibold">Erro de Acesso</p>
            <p><?= htmlspecialchars((string) $erroDb) ?></p>
            <p class="mt-1 text-xs">Ação: Aplique a migração `database/phase2_comunicacao.sql` no schema do ambiente e tente novamente.</p>
        </div>
    <?php endif; ?>

    <?php if ($mensagemSucesso): ?>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            <?= htmlspecialchars((string) $mensagemSucesso) ?>
        </div>
    <?php endif; ?>
    <?php if ($mensagemErro): ?>
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">
            <?= htmlspecialchars((string) $mensagemErro) ?>
        </div>
    <?php endif; ?>

    <form method="post" action="/pwa/comunicacao/novo" class="rounded-2xl border border-erpBorder bg-erpSurface p-5 space-y-4 shadow-sm">
        <div>
            <label class="block text-sm font-semibold text-erpText">Título</label>
            <input name="titulo" value="<?= htmlspecialchars((string) ($payload['titulo'] ?? '')) ?>"
                   class="mt-1 block w-full rounded-xl border border-erpBorder bg-erpBg px-4 py-3 text-sm placeholder-erpMuted focus:border-erpNavy focus:outline-none"
                   placeholder="Ex.: Orientações para a próxima sessão">
        </div>

        <div>
            <label class="block text-sm font-semibold text-erpText">Categoria</label>
            <input name="categoria" value="<?= htmlspecialchars((string) ($payload['categoria'] ?? 'geral')) ?>"
                   class="mt-1 block w-full rounded-xl border border-erpBorder bg-erpBg px-4 py-3 text-sm placeholder-erpMuted focus:border-erpNavy focus:outline-none"
                   placeholder="Ex.: geral, secretaria, tesouraria">
        </div>

        <div>
            <label class="block text-sm font-semibold text-erpText">Conteúdo do Comunicado</label>
            <textarea name="conteudo" rows="8"
                      class="mt-1 block w-full rounded-xl border border-erpBorder bg-erpBg px-4 py-3 text-sm placeholder-erpMuted focus:border-erpNavy focus:outline-none"
                      placeholder="Digite aqui o texto completo do comunicado..."><?= htmlspecialchars((string) ($payload['conteudo'] ?? '')) ?></textarea>
        </div>

        <button class="w-full rounded-xl bg-erpNavy px-5 py-3 text-sm font-semibold text-white transition hover:opacity-90">
            Publicar Comunicado
        </button>
    </form>
</div>

<?php
$pwaContent = ob_get_clean();
require __DIR__ . '/shell.php';
?>

