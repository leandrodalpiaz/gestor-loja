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
        <div class="rounded-xl px-4 py-3 text-sm font-medium" style="background:rgba(52,211,153,0.15);color:#6ee7b7;border:1px solid rgba(52,211,153,0.25);"><?= htmlspecialchars((string) $mensagemSucesso) ?></div>
    <?php endif; ?>
    <?php if ($mensagemErro): ?>
        <div class="rounded-xl px-4 py-3 text-sm font-medium" style="background:rgba(248,113,113,0.12);color:#fca5a5;border:1px solid rgba(248,113,113,0.25);"><?= htmlspecialchars((string) $mensagemErro) ?></div>
    <?php endif; ?>

    <section class="pwa-hero p-5">
        <p class="pwa-eyebrow">Sessão em Loja</p>
        <h2 class="mt-3 text-2xl font-bold tracking-tight text-white"><?= htmlspecialchars((string) ($sessao['titulo'] ?? 'Controle de presença')) ?></h2>
        <p class="pwa-muted mt-2 text-sm"><?= htmlspecialchars((string) ($sessao['data_hora_inicio'] ?? 'Selecione uma sessão')) ?></p>
    </section>

    <a href="/pwa/chancelaria/efemerides" class="block rounded-2xl p-4 shadow-sm" style="background:rgba(251,191,36,0.15);border:1px solid rgba(251,191,36,0.25);">
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide" style="color:#fde68a;">Urgente</p>
                <h3 class="mt-1 text-base font-bold" style="color:#f1f5f9;">Gerenciar efemérides</h3>
                <p class="mt-1 text-sm" style="color:#fde68a;">Cadastrar, editar, ativar/desativar e excluir conteúdos da Chancelaria pelo PWA.</p>
            </div>
            <span class="rounded-full px-3 py-1 text-xs font-bold" style="background:#C9A227;color:#0f172a;">Abrir</span>
        </div>
    </a>

    <form method="get" action="/pwa/chancelaria" class="rounded-2xl p-4" style="background:rgba(255,255,255,0.055);border:1px solid rgba(255,255,255,0.09);">
        <label class="block text-sm font-semibold" style="color:#f1f5f9;">Sessão</label>
        <select name="sessao_id" class="mt-2 w-full rounded-lg px-3 py-2 text-sm" style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.10);color:#f1f5f9;">
            <?php foreach ($sessoes as $opcao): ?>
                <option value="<?= (int) ($opcao['id'] ?? 0) ?>" <?= (int) ($opcao['id'] ?? 0) === $sessaoId ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string) ($opcao['titulo'] ?? 'Sessão')) ?> - <?= htmlspecialchars((string) ($opcao['data_hora_inicio'] ?? '')) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="mt-3 w-full px-4 py-2.5 text-sm font-semibold" style="background:#1e3a5f;color:#f1f5f9;border-radius:0.625rem;">Carregar sessão</button>
    </form>

    <section class="grid grid-cols-3 gap-3">
        <div class="rounded-2xl p-3 text-center" style="background:rgba(255,255,255,0.055);border:1px solid rgba(255,255,255,0.09);">
            <div class="text-2xl font-bold" style="color:#f1f5f9;"><?= count($confirmados) ?></div>
            <div class="text-xs" style="color:#94a3b8;">Confirmados</div>
        </div>
        <div class="rounded-2xl p-3 text-center" style="background:rgba(255,255,255,0.055);border:1px solid rgba(255,255,255,0.09);">
            <div class="text-2xl font-bold" style="color:#f1f5f9;"><?= count(array_filter($presencas, static fn($p): bool => !empty($p['presente']))) ?></div>
            <div class="text-xs" style="color:#94a3b8;">Presentes</div>
        </div>
        <div class="rounded-2xl p-3 text-center" style="background:rgba(255,255,255,0.055);border:1px solid rgba(255,255,255,0.09);">
            <div class="text-2xl font-bold" style="color:#f1f5f9;"><?= count($visitantes) ?></div>
            <div class="text-xs" style="color:#94a3b8;">Visitantes</div>
        </div>
    </section>

    <?php if ($sessaoId > 0): ?>
        <section class="rounded-2xl p-4" style="background:rgba(255,255,255,0.055);border:1px solid rgba(255,255,255,0.09);">
            <h3 class="font-bold" style="color:#f1f5f9;">Registrar visitante</h3>
            <form method="post" action="/pwa/chancelaria/visitante" class="mt-3 space-y-3">
                <input type="hidden" name="sessao_id" value="<?= $sessaoId ?>">
                <input name="nome" class="w-full rounded-lg px-3 py-2 text-sm" style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.10);color:#f1f5f9;" placeholder="Nome do visitante">
                <input name="loja_origem" class="w-full rounded-lg px-3 py-2 text-sm" style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.10);color:#f1f5f9;" placeholder="Loja / Oriente">
                <button type="submit" class="w-full px-4 py-2.5 text-sm font-semibold" style="background:#1e3a5f;color:#f1f5f9;border-radius:0.625rem;">Registrar visitante</button>
            </form>
        </section>
    <?php endif; ?>

    <section>
        <div class="mb-3">
            <p class="pwa-eyebrow">Check-in</p>
            <h3 class="mt-1 text-lg font-bold text-white">Presença efetiva</h3>
        </div>
        <div class="space-y-3">
            <?php foreach ($presencas as $presenca): ?>
                <?php $presente = !empty($presenca['presente']); ?>
                <div class="rounded-2xl p-4" style="background:rgba(255,255,255,0.055);border:1px solid rgba(255,255,255,0.09);">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="font-semibold" style="color:#f1f5f9;"><?= htmlspecialchars((string) ($presenca['nome'] ?? 'Obreiro')) ?></div>
                            <div class="text-xs" style="color:#94a3b8;">CIM <?= htmlspecialchars((string) ($presenca['cim'] ?? '-')) ?> <?= htmlspecialchars((string) ($presenca['grau'] ?? '')) ?></div>
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-semibold" style="<?= $presente ? 'background:rgba(52,211,153,0.15);color:#6ee7b7;' : 'background:rgba(255,255,255,0.04);color:#94a3b8;' ?>">
                            <?= $presente ? 'Presente' : 'Pendente' ?>
                        </span>
                    </div>
                    <?php if ($sessaoId > 0): ?>
                        <form method="post" action="/pwa/chancelaria/presenca" class="mt-3 grid grid-cols-2 gap-2">
                            <input type="hidden" name="sessao_id" value="<?= $sessaoId ?>">
                            <input type="hidden" name="obreiro_id" value="<?= htmlspecialchars((string) ($presenca['id'] ?? '')) ?>">
                            <button name="presente" value="1" class="rounded-lg px-3 py-2 text-sm font-semibold" style="background:rgba(52,211,153,0.2);color:#6ee7b7;border:1px solid rgba(52,211,153,0.3);">Presente</button>
                            <button name="presente" value="0" class="rounded-lg px-3 py-2 text-sm font-semibold" style="background:rgba(255,255,255,0.04);color:#94a3b8;border:1px solid rgba(255,255,255,0.09);">Ausente</button>
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
