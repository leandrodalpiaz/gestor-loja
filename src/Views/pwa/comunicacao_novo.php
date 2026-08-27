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
        <div class="rounded-xl p-4 text-sm" style="background:rgba(251,191,36,0.15);color:#fde68a;border:1px solid rgba(251,191,36,0.25);">
            <p class="font-semibold">Erro de Acesso</p>
            <p><?= htmlspecialchars((string) $erroDb) ?></p>
            <p class="mt-1 text-xs">Ação: Aplique a migração `database/phase2_comunicacao.sql` no schema do ambiente e tente novamente.</p>
        </div>
    <?php endif; ?>

    <?php if ($mensagemSucesso): ?>
        <div class="rounded-xl px-4 py-3 text-sm font-medium" style="background:rgba(52,211,153,0.15);color:#6ee7b7;border:1px solid rgba(52,211,153,0.25);">
            <?= htmlspecialchars((string) $mensagemSucesso) ?>
        </div>
    <?php endif; ?>
    <?php if ($mensagemErro): ?>
        <div class="rounded-xl px-4 py-3 text-sm font-medium" style="background:rgba(248,113,113,0.12);color:#fca5a5;border:1px solid rgba(248,113,113,0.25);">
            <?= htmlspecialchars((string) $mensagemErro) ?>
        </div>
    <?php endif; ?>

    <form method="post" action="/pwa/comunicacao/novo" class="rounded-2xl p-5 space-y-4 shadow-sm" style="background:rgba(255,255,255,0.055);border:1px solid rgba(255,255,255,0.09);">
        <div>
            <label class="block text-sm font-semibold" style="color:#e2e8f0;">Título</label>
            <input name="titulo" value="<?= htmlspecialchars((string) ($payload['titulo'] ?? '')) ?>"
                   class="mt-1 block w-full rounded-xl px-4 py-3 text-sm placeholder:color:#94a3b8 focus:outline-none"
                   style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.10);color:#f1f5f9;"
                   placeholder="Ex.: Orientações para a próxima sessão">
        </div>

        <div>
            <label class="block text-sm font-semibold" style="color:#e2e8f0;">Categoria</label>
            <input name="categoria" value="<?= htmlspecialchars((string) ($payload['categoria'] ?? 'geral')) ?>"
                   class="mt-1 block w-full rounded-xl px-4 py-3 text-sm placeholder:color:#94a3b8 focus:outline-none"
                   style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.10);color:#f1f5f9;"
                   placeholder="Ex.: geral, secretaria, tesouraria">
        </div>

        <div>
            <label class="block text-sm font-semibold" style="color:#e2e8f0;">Conteúdo do Comunicado</label>
            <textarea name="conteudo" rows="8"
                      class="mt-1 block w-full rounded-xl px-4 py-3 text-sm placeholder:color:#94a3b8 focus:outline-none"
                      style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.10);color:#f1f5f9;"
                      placeholder="Digite aqui o texto completo do comunicado..."><?= htmlspecialchars((string) ($payload['conteudo'] ?? '')) ?></textarea>
        </div>

        <button class="w-full rounded-xl px-5 py-3 text-sm font-semibold transition hover:opacity-90" style="background:#C9A227;color:#0f172a;">
            Publicar Comunicado
        </button>
    </form>
</div>

<?php
$pwaContent = ob_get_clean();
require __DIR__ . '/shell.php';
?>

