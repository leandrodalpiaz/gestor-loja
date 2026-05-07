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
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800"><?= htmlspecialchars((string) $mensagemSucesso) ?></div>
    <?php endif; ?>
    <?php if (!empty($mensagemErro)): ?>
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800"><?= htmlspecialchars((string) $mensagemErro) ?></div>
    <?php endif; ?>

    <section class="pwa-hero p-5">
        <p class="pwa-eyebrow">Operacao da Secretaria</p>
        <h2 class="mt-3 text-2xl font-bold tracking-tight text-white"><?= htmlspecialchars((string) ($sessao['titulo'] ?? 'Agenda e balaustres')) ?></h2>
        <p class="pwa-muted mt-2 text-sm">Sessoes, confirmados, agape, trabalhos, publicacoes, balaustre e relatorio anual.</p>
    </section>

    <form method="get" action="/pwa/secretaria" class="rounded-2xl border border-erpBorder bg-erpSurface p-4">
        <label class="block text-sm font-semibold text-erpNavy">Sessao de trabalho</label>
        <select name="sessao_id" class="mt-2 w-full rounded-lg border border-erpBorder bg-white px-3 py-2 text-sm">
            <?php foreach ($sessoes as $opcao): ?>
                <option value="<?= (int) ($opcao['id'] ?? 0) ?>" <?= (int) ($opcao['id'] ?? 0) === $sessaoId ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string) ($opcao['titulo'] ?? 'Sessao')) ?> - <?= htmlspecialchars((string) ($opcao['data_hora_inicio'] ?? '')) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button class="mt-3 w-full rounded-lg bg-erpNavy px-4 py-2.5 text-sm font-semibold text-white">Carregar</button>
    </form>

    <section class="grid grid-cols-3 gap-3">
        <div class="rounded-2xl border border-erpBorder bg-erpSurface p-3 text-center">
            <div class="text-2xl font-bold text-erpNavy"><?= count($confirmados) ?></div>
            <div class="text-xs text-erpMuted">Confirmados</div>
        </div>
        <div class="rounded-2xl border border-erpBorder bg-erpSurface p-3 text-center">
            <div class="text-2xl font-bold text-erpNavy"><?= count($agape) ?></div>
            <div class="text-xs text-erpMuted">Agape</div>
        </div>
        <div class="rounded-2xl border border-erpBorder bg-erpSurface p-3 text-center">
            <div class="text-2xl font-bold text-erpNavy"><?= (int) ($relatorio['totais']['sessoes'] ?? 0) ?></div>
            <div class="text-xs text-erpMuted">Sessoes/ano</div>
        </div>
    </section>

    <?php if ($sessaoId > 0): ?>
        <section class="rounded-2xl border border-erpBorder bg-erpSurface p-4">
            <h3 class="font-bold text-erpNavy">Acoes da sessao</h3>
            <div class="mt-3 grid grid-cols-3 gap-2">
                <form method="post" action="/pwa/secretaria/sessoes/publicar">
                    <input type="hidden" name="sessao_id" value="<?= $sessaoId ?>">
                    <button class="w-full rounded-lg bg-emerald-600 px-3 py-2 text-xs font-bold text-white">Publicar</button>
                </form>
                <form method="post" action="/pwa/secretaria/sessoes/cancelar">
                    <input type="hidden" name="sessao_id" value="<?= $sessaoId ?>">
                    <button class="w-full rounded-lg bg-rose-600 px-3 py-2 text-xs font-bold text-white">Cancelar</button>
                </form>
                <form method="post" action="/pwa/secretaria/sessoes/reabrir">
                    <input type="hidden" name="sessao_id" value="<?= $sessaoId ?>">
                    <button class="w-full rounded-lg bg-slate-700 px-3 py-2 text-xs font-bold text-white">Reabrir</button>
                </form>
            </div>
            <a href="/secretaria/sessoes?editar_sessao=<?= $sessaoId ?>" class="mt-3 block rounded-lg border border-erpBorder px-3 py-2 text-center text-sm font-semibold text-erpNavy">Editar/criar sessao completa no Desktop</a>
        </section>
    <?php endif; ?>

    <section class="rounded-2xl border border-erpBorder bg-erpSurface p-4">
        <h3 class="font-bold text-erpNavy">Registrar trabalho</h3>
        <form method="post" action="/pwa/secretaria/trabalhos/salvar" class="mt-3 space-y-3">
            <input type="hidden" name="sessao_id" value="<?= $sessaoId ?>">
            <input name="titulo" class="w-full rounded-lg border border-erpBorder px-3 py-2 text-sm" placeholder="Titulo do trabalho">
            <input name="autor_nome_livre" class="w-full rounded-lg border border-erpBorder px-3 py-2 text-sm" placeholder="Autor">
            <button class="w-full rounded-lg bg-erpNavy px-4 py-2.5 text-sm font-semibold text-white">Salvar trabalho</button>
        </form>
    </section>

    <section class="rounded-2xl border border-erpBorder bg-erpSurface p-4">
        <h3 class="font-bold text-erpNavy">Comunicacao/Publicacao</h3>
        <form method="post" action="/pwa/secretaria/publicacoes/salvar" class="mt-3 space-y-3">
            <input type="hidden" name="sessao_id" value="<?= $sessaoId ?>">
            <input name="titulo" class="w-full rounded-lg border border-erpBorder px-3 py-2 text-sm" placeholder="Titulo">
            <textarea name="conteudo" rows="3" class="w-full rounded-lg border border-erpBorder px-3 py-2 text-sm" placeholder="Conteudo"></textarea>
            <button class="w-full rounded-lg bg-erpNavy px-4 py-2.5 text-sm font-semibold text-white">Salvar publicacao</button>
        </form>
        <a href="/pwa/comunicacao" class="mt-3 block text-center text-sm font-semibold text-erpNavy">Abrir Comunicacao PWA</a>
    </section>

    <section class="rounded-2xl border border-erpBorder bg-erpSurface p-4">
        <h3 class="font-bold text-erpNavy">Balaustre rapido</h3>
        <form method="post" action="/pwa/secretaria/balaustres/salvar" class="mt-3 space-y-3">
            <input type="hidden" name="sessao_id" value="<?= $sessaoId ?>">
            <input name="numero_balaustre" class="w-full rounded-lg border border-erpBorder px-3 py-2 text-sm" placeholder="Numero do balaustre">
            <textarea name="texto_final" rows="4" class="w-full rounded-lg border border-erpBorder px-3 py-2 text-sm" placeholder="Texto ou apontamentos"></textarea>
            <button class="w-full rounded-lg bg-erpNavy px-4 py-2.5 text-sm font-semibold text-white">Salvar rascunho</button>
        </form>
        <a href="/secretaria/balaustres?sessao_resumo=<?= $sessaoId ?>" class="mt-3 block text-center text-sm font-semibold text-erpNavy">Compor balaustre completo no Desktop</a>
    </section>

    <section class="space-y-3">
        <h3 class="text-lg font-bold text-white">Ultimos registros</h3>
        <?php foreach ([['Trabalhos', $trabalhos], ['Publicacoes', $publicacoes], ['Balaustres', $balaustres]] as $grupo): ?>
            <div class="rounded-2xl border border-erpBorder bg-erpSurface p-4">
                <h4 class="font-bold text-erpNavy"><?= htmlspecialchars($grupo[0]) ?></h4>
                <div class="mt-3 space-y-2">
                    <?php foreach (array_slice($grupo[1], 0, 3) as $item): ?>
                        <div class="rounded-xl bg-slate-50 p-3 text-sm">
                            <div class="font-semibold text-erpNavy"><?= htmlspecialchars((string) ($item['titulo'] ?? $item['numero_balaustre'] ?? 'Registro')) ?></div>
                            <div class="text-xs text-erpMuted"><?= htmlspecialchars((string) ($item['status'] ?? $item['status_publicacao'] ?? $item['status_envio_potencia'] ?? '')) ?></div>
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
