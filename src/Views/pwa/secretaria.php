<?php
declare(strict_types=1);

$sessoes = is_array($sessoes ?? null) ? $sessoes : [];
$sessao = is_array($sessao ?? null) ? $sessao : null;
$confirmados = is_array($confirmados ?? null) ? $confirmados : [];
$agape = is_array($agape ?? null) ? $agape : [];
$trabalhos = is_array($trabalhos ?? null) ? $trabalhos : [];
$publicacoes = is_array($publicacoes ?? null) ? $publicacoes : [];
$balaustres = is_array($balaustres ?? null) ? $balaustres : [];
$relatorio = is_array($relatorio ?? null) ? $relatorio : [];
$sessaoId = (int) ($sessao['id'] ?? 0);

$pwaPageTitle = 'Secretaria';
$pwaShowBackButton = true;
$pwaBackUrl = '/pwa/admin';
$pwaActiveTab = 'cargo';

ob_start();
?>

<div class="p-4 sm:p-6 space-y-4">
    <?php if (!empty($mensagemSucesso)): ?>
        <div class="pwa-alert-success"><?= htmlspecialchars((string) $mensagemSucesso) ?></div>
    <?php endif; ?>
    <?php if (!empty($mensagemErro)): ?>
        <div class="pwa-alert-error"><?= htmlspecialchars((string) $mensagemErro) ?></div>
    <?php endif; ?>

    <section class="pwa-hero p-5">
        <p class="pwa-eyebrow">Operação da Secretaria</p>
        <h2 class="mt-3 text-2xl font-bold tracking-tight text-white"><?= htmlspecialchars((string) ($sessao['titulo'] ?? 'Agenda e balaústres')) ?></h2>
        <p class="pwa-muted mt-2 text-sm">Sessões, confirmados, ágape, trabalhos, publicações, balaústre e relatório anual.</p>
    </section>

    <section class="grid grid-cols-2 gap-2">
        <a href="/pwa/secretaria/sessoes" style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.09);border-radius:0.75rem;padding:0.5rem 0.75rem;text-align:center;font-size:0.75rem;font-weight:700;color:#f1f5f9;display:block;">Sessões</a>
        <a href="/pwa/secretaria/balaustres" style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.09);border-radius:0.75rem;padding:0.5rem 0.75rem;text-align:center;font-size:0.75rem;font-weight:700;color:#f1f5f9;display:block;">Balaustres</a>
        <a href="/pwa/secretaria/trabalhos-publicacoes" style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.09);border-radius:0.75rem;padding:0.5rem 0.75rem;text-align:center;font-size:0.75rem;font-weight:700;color:#f1f5f9;display:block;">Trabalhos/Publicações</a>
        <a href="/pwa/secretaria/convites-externos" style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.09);border-radius:0.75rem;padding:0.5rem 0.75rem;text-align:center;font-size:0.75rem;font-weight:700;color:#f1f5f9;display:block;">Convites Externos</a>
        <a href="/pwa/secretaria/votacao" style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.09);border-radius:0.75rem;padding:0.5rem 0.75rem;text-align:center;font-size:0.75rem;font-weight:700;color:#f1f5f9;display:block;">Votação</a>
        <a href="/pwa/secretaria/relatorio-anual" style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.09);border-radius:0.75rem;padding:0.5rem 0.75rem;text-align:center;font-size:0.75rem;font-weight:700;color:#f1f5f9;display:block;">Relatórios</a>
        <a href="/pwa/secretaria/nominata" style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.09);border-radius:0.75rem;padding:0.5rem 0.75rem;text-align:center;font-size:0.75rem;font-weight:700;color:#f1f5f9;display:block;">Nominata</a>
        <a href="/pwa/secretaria/acessos" style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.09);border-radius:0.75rem;padding:0.5rem 0.75rem;text-align:center;font-size:0.75rem;font-weight:700;color:#f1f5f9;display:block;">Acessos</a>
        <a href="/pwa/secretaria/convites" style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.09);border-radius:0.75rem;padding:0.5rem 0.75rem;text-align:center;font-size:0.75rem;font-weight:700;color:#f1f5f9;display:block;">Convites</a>
        <a href="/pwa/secretaria/conteudo-publico" style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.09);border-radius:0.75rem;padding:0.5rem 0.75rem;text-align:center;font-size:0.75rem;font-weight:700;color:#f1f5f9;display:block;">Conteúdo Público</a>
    </section>

    <form method="get" action="/pwa/secretaria" class="pwa-card" style="padding:1rem;">
        <label class="pwa-label block text-sm font-semibold">Sessão de trabalho</label>
        <select name="sessao_id" class="pwa-select mt-2 w-full">
            <?php foreach ($sessoes as $opcao): ?>
                <option value="<?= (int) ($opcao['id'] ?? 0) ?>" <?= (int) ($opcao['id'] ?? 0) === $sessaoId ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string) ($opcao['titulo'] ?? 'Sessão')) ?> - <?= htmlspecialchars((string) ($opcao['data_hora_inicio'] ?? '')) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button class="pwa-btn-primary mt-3 w-full">Carregar</button>
    </form>

    <section class="grid grid-cols-3 gap-3">
        <div class="pwa-card p-3 text-center">
            <div class="text-2xl font-bold" style="color:#f1f5f9;"><?= count($confirmados) ?></div>
            <div class="text-xs" style="color:#94a3b8;">Confirmados</div>
        </div>
        <div class="pwa-card p-3 text-center">
            <div class="text-2xl font-bold" style="color:#f1f5f9;"><?= count($agape) ?></div>
            <div class="text-xs" style="color:#94a3b8;">Ágape</div>
        </div>
        <div class="pwa-card p-3 text-center">
            <div class="text-2xl font-bold" style="color:#f1f5f9;"><?= (int) ($relatorio['totais']['sessoes'] ?? 0) ?></div>
            <div class="text-xs" style="color:#94a3b8;">Sessões/ano</div>
        </div>
    </section>

    <?php if ($sessaoId > 0): ?>
        <section class="pwa-card" style="padding:1rem;">
            <h3 class="font-bold" style="color:#f1f5f9;">Ações da sessão</h3>
            <div class="mt-3 grid grid-cols-3 gap-2">
                <form method="post" action="/pwa/secretaria/sessoes/publicar">
                    <input type="hidden" name="sessao_id" value="<?= $sessaoId ?>">
                    <button class="w-full rounded-lg px-3 py-2 text-xs font-bold text-white" style="background:#16a34a;">Publicar</button>
                </form>
                <form method="post" action="/pwa/secretaria/sessoes/cancelar">
                    <input type="hidden" name="sessao_id" value="<?= $sessaoId ?>">
                    <button class="w-full rounded-lg px-3 py-2 text-xs font-bold text-white" style="background:#dc2626;">Cancelar</button>
                </form>
                <form method="post" action="/pwa/secretaria/sessoes/reabrir">
                    <input type="hidden" name="sessao_id" value="<?= $sessaoId ?>">
                    <button class="w-full rounded-lg px-3 py-2 text-xs font-bold text-white" style="background:#475569;">Reabrir</button>
                </form>
            </div>
            <a href="/secretaria/sessoes?editar_sessao=<?= $sessaoId ?>" class="mt-3 block rounded-lg px-3 py-2 text-center text-sm font-semibold" style="border:1px solid rgba(255,255,255,0.09);color:#f1f5f9;">Editar/criar sessão completa no Desktop</a>
        </section>
    <?php endif; ?>

    <section class="pwa-card" style="padding:1rem;">
        <h3 class="font-bold" style="color:#f1f5f9;">Registrar trabalho</h3>
        <form method="post" action="/pwa/secretaria/trabalhos/salvar" class="mt-3 space-y-3">
            <input type="hidden" name="sessao_id" value="<?= $sessaoId ?>">
            <input name="titulo" class="pwa-input w-full" placeholder="Título do trabalho">
            <input name="autor_nome_livre" class="pwa-input w-full" placeholder="Autor">
            <button class="pwa-btn-primary w-full">Salvar trabalho</button>
        </form>
    </section>

    <section class="pwa-card" style="padding:1rem;">
        <h3 class="font-bold" style="color:#f1f5f9;">Comunicação/Publicação</h3>
        <form method="post" action="/pwa/secretaria/publicacoes/salvar" class="mt-3 space-y-3">
            <input type="hidden" name="sessao_id" value="<?= $sessaoId ?>">
            <input name="titulo" class="pwa-input w-full" placeholder="Título">
            <textarea name="conteudo" rows="3" class="pwa-textarea w-full" placeholder="Conteúdo"></textarea>
            <button class="pwa-btn-primary w-full">Salvar publicação</button>
        </form>
        <a href="/pwa/comunicacao" class="mt-3 block text-center text-sm font-semibold" style="color:#f1f5f9;">Abrir Comunicação PWA</a>
    </section>

    <section class="pwa-card" style="padding:1rem;">
        <h3 class="font-bold" style="color:#f1f5f9;">Balaústre rápido</h3>
        <form method="post" action="/pwa/secretaria/balaustres/salvar" class="mt-3 space-y-3">
            <input type="hidden" name="sessao_id" value="<?= $sessaoId ?>">
            <input name="numero_balaustre" class="pwa-input w-full" placeholder="Número do balaústre">
            <textarea name="texto_final" rows="4" class="pwa-textarea w-full" placeholder="Texto ou apontamentos"></textarea>
            <button class="pwa-btn-primary w-full">Salvar rascunho</button>
        </form>
        <a href="/secretaria/balaustres?sessao_resumo=<?= $sessaoId ?>" class="mt-3 block text-center text-sm font-semibold" style="color:#f1f5f9;">Compor balaústre completo no Desktop</a>
    </section>

    <section class="space-y-3">
        <h3 class="text-lg font-bold text-white">Últimos registros</h3>
        <?php foreach ([['Trabalhos', $trabalhos], ['Publicações', $publicacoes], ['Balaustres', $balaustres]] as $grupo): ?>
            <div class="pwa-card" style="padding:1rem;">
                <h4 class="font-bold" style="color:#f1f5f9;"><?= htmlspecialchars($grupo[0]) ?></h4>
                <div class="mt-3 space-y-2">
                    <?php foreach (array_slice($grupo[1], 0, 3) as $item): ?>
                        <div style="background:rgba(255,255,255,0.03);border-radius:0.5rem;padding:0.75rem;font-size:0.875rem;">
                            <div class="font-semibold" style="color:#f1f5f9;"><?= htmlspecialchars((string) ($item['titulo'] ?? $item['numero_balaustre'] ?? 'Registro')) ?></div>
                            <div class="text-xs" style="color:#94a3b8;"><?= htmlspecialchars((string) ($item['status'] ?? $item['status_publicacao'] ?? $item['status_envio_potencia'] ?? '')) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </section>
</div>

<?php
$pwaContent = ob_get_clean();
require __DIR__ . '/shell.php';
?>
