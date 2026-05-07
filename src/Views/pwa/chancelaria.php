<?php
declare(strict_types=1);

$sessoes = is_array($sessoes ?? null) ? $sessoes : [];
$sessao = is_array($sessao ?? null) ? $sessao : null;
$confirmados = is_array($confirmados ?? null) ? $confirmados : [];
$presencas = is_array($presencas ?? null) ? $presencas : [];
$visitantes = is_array($visitantes ?? null) ? $visitantes : [];
$mensagemSucesso = $mensagemSucesso ?? null;
$mensagemErro = $mensagemErro ?? null;

$sessaoId = (int) ($sessao['id'] ?? 0);
$pwaPageTitle = 'Chancelaria';
$pwaShowBackButton = true;
$pwaBackUrl = '/pwa/admin';
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
        <p class="pwa-eyebrow">Sessao em Loja</p>
        <h2 class="mt-3 text-2xl font-bold tracking-tight text-white"><?= htmlspecialchars((string) ($sessao['titulo'] ?? 'Controle de presenca')) ?></h2>
        <p class="pwa-muted mt-2 text-sm"><?= htmlspecialchars((string) ($sessao['data_hora_inicio'] ?? 'Selecione uma sessao')) ?></p>
    </section>

    <a href="/pwa/chancelaria/efemerides" class="block rounded-2xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-amber-700">Urgente</p>
                <h3 class="mt-1 text-base font-bold text-erpNavy">Gerenciar efemerides</h3>
                <p class="mt-1 text-sm text-amber-900">Cadastrar, editar, ativar/desativar e excluir conteudos da Chancelaria pelo PWA.</p>
            </div>
            <span class="rounded-full bg-amber-500 px-3 py-1 text-xs font-bold text-white">Abrir</span>
        </div>
    </a>

    <form method="get" action="/pwa/chancelaria" class="rounded-2xl border border-erpBorder bg-erpSurface p-4">
        <label class="block text-sm font-semibold text-erpNavy">Sessao</label>
        <select name="sessao_id" class="mt-2 w-full rounded-lg border border-erpBorder bg-white px-3 py-2 text-sm">
            <?php foreach ($sessoes as $opcao): ?>
                <option value="<?= (int) ($opcao['id'] ?? 0) ?>" <?= (int) ($opcao['id'] ?? 0) === $sessaoId ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string) ($opcao['titulo'] ?? 'Sessao')) ?> - <?= htmlspecialchars((string) ($opcao['data_hora_inicio'] ?? '')) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="mt-3 w-full rounded-lg bg-erpNavy px-4 py-2.5 text-sm font-semibold text-white">Carregar sessao</button>
    </form>

    <section class="grid grid-cols-3 gap-3">
        <div class="rounded-2xl border border-erpBorder bg-erpSurface p-3 text-center">
            <div class="text-2xl font-bold text-erpNavy"><?= count($confirmados) ?></div>
            <div class="text-xs text-erpMuted">Confirmados</div>
        </div>
        <div class="rounded-2xl border border-erpBorder bg-erpSurface p-3 text-center">
            <div class="text-2xl font-bold text-erpNavy"><?= count(array_filter($presencas, static fn($p): bool => !empty($p['presente']))) ?></div>
            <div class="text-xs text-erpMuted">Presentes</div>
        </div>
        <div class="rounded-2xl border border-erpBorder bg-erpSurface p-3 text-center">
            <div class="text-2xl font-bold text-erpNavy"><?= count($visitantes) ?></div>
            <div class="text-xs text-erpMuted">Visitantes</div>
        </div>
    </section>

    <?php if ($sessaoId > 0): ?>
        <section class="rounded-2xl border border-erpBorder bg-erpSurface p-4">
            <h3 class="font-bold text-erpNavy">Registrar visitante</h3>
            <form method="post" action="/pwa/chancelaria/visitante" class="mt-3 space-y-3">
                <input type="hidden" name="sessao_id" value="<?= $sessaoId ?>">
                <input name="nome" class="w-full rounded-lg border border-erpBorder bg-white px-3 py-2 text-sm" placeholder="Nome do visitante">
                <input name="loja_origem" class="w-full rounded-lg border border-erpBorder bg-white px-3 py-2 text-sm" placeholder="Loja / Oriente">
                <button type="submit" class="w-full rounded-lg bg-erpNavy px-4 py-2.5 text-sm font-semibold text-white">Registrar visitante</button>
            </form>
        </section>
    <?php endif; ?>

    <section>
        <div class="mb-3">
            <p class="pwa-eyebrow">Check-in</p>
            <h3 class="mt-1 text-lg font-bold text-white">Presenca efetiva</h3>
        </div>
        <div class="space-y-3">
            <?php foreach ($presencas as $presenca): ?>
                <?php $presente = !empty($presenca['presente']); ?>
                <div class="rounded-2xl border border-erpBorder bg-erpSurface p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="font-semibold text-erpNavy"><?= htmlspecialchars((string) ($presenca['nome'] ?? 'Obreiro')) ?></div>
                            <div class="text-xs text-erpMuted">CIM <?= htmlspecialchars((string) ($presenca['cim'] ?? '-')) ?> <?= htmlspecialchars((string) ($presenca['grau'] ?? '')) ?></div>
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-semibold <?= $presente ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' ?>">
                            <?= $presente ? 'Presente' : 'Pendente' ?>
                        </span>
                    </div>
                    <?php if ($sessaoId > 0): ?>
                        <form method="post" action="/pwa/chancelaria/presenca" class="mt-3 grid grid-cols-2 gap-2">
                            <input type="hidden" name="sessao_id" value="<?= $sessaoId ?>">
                            <input type="hidden" name="obreiro_id" value="<?= htmlspecialchars((string) ($presenca['id'] ?? '')) ?>">
                            <button name="presente" value="1" class="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white">Presente</button>
                            <button name="presente" value="0" class="rounded-lg bg-slate-200 px-3 py-2 text-sm font-semibold text-slate-800">Ausente</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<?php
$pwaContent = ob_get_clean();
require __DIR__ . '/shell.php';
?>
