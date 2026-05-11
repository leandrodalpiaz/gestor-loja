<?php
declare(strict_types=1);

$registros = is_array($registros ?? null) ? $registros : [];
$registroEditar = is_array($registroEditar ?? null) ? $registroEditar : null;
$tipos = is_array($tipos ?? null) ? $tipos : [];
$vinculos = is_array($vinculos ?? null) ? $vinculos : [];
$mensagemSucesso = $mensagemSucesso ?? null;
$mensagemErro = $mensagemErro ?? null;

$pwaPageTitle = 'Efemérides';
$pwaShowBackButton = true;
$pwaBackUrl = '/pwa/chancelaria';
$pwaActiveTab = 'cargo';

ob_start();
?>

<div class="p-4 sm:p-6 space-y-4">
    <?php if ($mensagemSucesso): ?>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800"><?= htmlspecialchars((string) $mensagemSucesso) ?></div>
    <?php endif; ?>
    <?php if ($mensagemErro): ?>
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800"><?= htmlspecialchars((string) $mensagemErro) ?></div>
    <?php endif; ?>

    <section class="pwa-hero p-5">
        <p class="pwa-eyebrow">Chancelaria</p>
        <h2 class="mt-3 text-2xl font-bold tracking-tight text-white">Gerenciar efemérides</h2>
        <p class="pwa-muted mt-2 text-sm">Aniversários, datas maçônicas, família, fatos históricos e mensagens do dia.</p>
    </section>

    <section class="rounded-2xl border border-erpBorder bg-erpSurface p-4">
        <h3 class="font-bold text-erpNavy"><?= $registroEditar ? 'Editar efemeride' : 'Nova efemeride' ?></h3>
        <form method="post" action="/pwa/chancelaria/efemerides/salvar" class="mt-3 space-y-3">
            <input type="hidden" name="id" value="<?= (int) ($registroEditar['id'] ?? 0) ?>">
            <input name="nome" required value="<?= htmlspecialchars((string) ($registroEditar['nome'] ?? '')) ?>" class="w-full rounded-lg border border-erpBorder bg-white px-3 py-2 text-sm" placeholder="Nome / titulo">
            <div class="grid grid-cols-2 gap-2">
                <select name="tipo" required class="rounded-lg border border-erpBorder bg-white px-3 py-2 text-sm">
                    <?php foreach ($tipos as $valor => $label): ?>
                        <option value="<?= htmlspecialchars((string) $valor) ?>" <?= (string) ($registroEditar['tipo'] ?? '') === (string) $valor ? 'selected' : '' ?>><?= htmlspecialchars((string) $label) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="date" name="data_evento" required value="<?= htmlspecialchars((string) ($registroEditar['data_evento'] ?? '')) ?>" class="rounded-lg border border-erpBorder bg-white px-3 py-2 text-sm">
            </div>
            <div class="grid grid-cols-2 gap-2">
                <input name="vinculo" value="<?= htmlspecialchars((string) ($registroEditar['vinculo'] ?? '')) ?>" list="vinculos-efemeride" class="rounded-lg border border-erpBorder bg-white px-3 py-2 text-sm" placeholder="Vinculo">
                <input name="parentesco" value="<?= htmlspecialchars((string) ($registroEditar['parentesco'] ?? '')) ?>" class="rounded-lg border border-erpBorder bg-white px-3 py-2 text-sm" placeholder="Parentesco / referencia">
            </div>
            <datalist id="vinculos-efemeride">
                <?php foreach ($vinculos as $vinculo): ?>
                    <option value="<?= htmlspecialchars((string) ($vinculo['nome'] ?? '')) ?>"></option>
                <?php endforeach; ?>
            </datalist>
            <input name="local" value="<?= htmlspecialchars((string) ($registroEditar['local'] ?? '')) ?>" class="w-full rounded-lg border border-erpBorder bg-white px-3 py-2 text-sm" placeholder="Local / Oriente">
            <textarea name="mensagem_custom" rows="3" class="w-full rounded-lg border border-erpBorder bg-white px-3 py-2 text-sm" placeholder="Mensagem personalizada"><?= htmlspecialchars((string) ($registroEditar['mensagem_custom'] ?? '')) ?></textarea>
            <button class="w-full rounded-lg bg-erpNavy px-4 py-3 text-sm font-semibold text-white">Salvar efemeride</button>
        </form>
    </section>

    <form method="get" action="/pwa/chancelaria/efemerides" class="grid grid-cols-3 gap-2 rounded-2xl border border-erpBorder bg-erpSurface p-4">
        <input name="q" value="<?= htmlspecialchars((string) ($_GET['q'] ?? '')) ?>" class="col-span-2 rounded-lg border border-erpBorder bg-white px-3 py-2 text-sm" placeholder="Buscar">
        <button class="rounded-lg bg-erpNavy px-3 py-2 text-sm font-semibold text-white">Filtrar</button>
    </form>

    <section class="space-y-3">
        <?php foreach ($registros as $registro): ?>
            <?php $ativo = (bool) ($registro['ativo'] ?? false); ?>
            <div class="rounded-2xl border border-erpBorder bg-erpSurface p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h3 class="font-semibold text-erpNavy"><?= htmlspecialchars((string) ($registro['nome'] ?? 'Efemeride')) ?></h3>
                        <p class="mt-1 text-xs text-erpMuted"><?= htmlspecialchars((string) ($registro['tipo'] ?? '')) ?> · <?= htmlspecialchars((string) ($registro['data_evento'] ?? '')) ?></p>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-semibold <?= $ativo ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' ?>"><?= $ativo ? 'Ativa' : 'Inativa' ?></span>
                </div>
                <?php if (!empty($registro['mensagem_custom'])): ?>
                    <p class="mt-3 rounded-lg bg-erpBg p-3 text-sm text-erpText"><?= nl2br(htmlspecialchars((string) $registro['mensagem_custom'])) ?></p>
                <?php endif; ?>
                <div class="mt-3 grid grid-cols-3 gap-2">
                    <a href="/pwa/chancelaria/efemerides?editar=<?= (int) ($registro['id'] ?? 0) ?>" class="rounded-lg border border-erpBorder bg-white px-3 py-2 text-center text-xs font-semibold text-erpNavy">Editar</a>
                    <form method="post" action="/pwa/chancelaria/efemerides/desativar">
                        <input type="hidden" name="id" value="<?= (int) ($registro['id'] ?? 0) ?>">
                        <button class="w-full rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-800">Desativar</button>
                    </form>
                    <form method="post" action="/pwa/chancelaria/efemerides/excluir" onsubmit="return confirm('Excluir esta efeméride?')">
                        <input type="hidden" name="id" value="<?= (int) ($registro['id'] ?? 0) ?>">
                        <button class="w-full rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-800">Excluir</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </section>
</div>

<?php
$pwaContent = ob_get_clean();
require __DIR__ . '/shell.php';
?>
