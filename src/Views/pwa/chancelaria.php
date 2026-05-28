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

<div class="px-4 py-4 space-y-4">
    <?php if ($mensagemSucesso): ?>
        <div class="pwa-alert-success"><?= htmlspecialchars((string) $mensagemSucesso) ?></div>
    <?php endif; ?>
    <?php if ($mensagemErro): ?>
        <div class="pwa-alert-error"><?= htmlspecialchars((string) $mensagemErro) ?></div>
    <?php endif; ?>

    <section class="pwa-hero">
        <p class="pwa-eyebrow text-amber-400">Sessão em Loja</p>
        <h2 class="mt-2 text-xl font-bold tracking-tight text-white"><?= htmlspecialchars((string) ($sessao['titulo'] ?? 'Controle de presença')) ?></h2>
        <p class="pwa-muted mt-1.5 text-xs font-medium"><?= htmlspecialchars((string) ($sessao['data_hora_inicio'] ?? 'Selecione uma sessão')) ?></p>
    </section>

    <!-- Gerenciar efemérides link widget -->
    <a href="/pwa/chancelaria/efemerides" class="block pwa-card border-amber-500/20 bg-amber-500/5 active:scale-[0.98] transition-transform no-underline">
        <div class="flex items-center justify-between gap-3.5">
            <div class="min-w-0">
                <p class="pwa-eyebrow text-amber-500">Módulos</p>
                <h3 class="text-xs font-bold text-slate-100">Gerenciar Efemérides</h3>
                <p class="text-[11px] text-slate-400 mt-1 leading-snug">Cadastrar, editar, ativar/desativar e excluir efemérides do chanceler pelo PWA.</p>
            </div>
            <span class="pwa-badge bg-amber-500 text-slate-950 font-bold select-none shrink-0">Abrir</span>
        </div>
    </a>

    <!-- Selecionar Sessão Form -->
    <form method="get" action="/pwa/chancelaria" class="pwa-card">
        <label class="pwa-label">Carregar Sessão</label>
        <div class="relative mt-1">
            <select name="sessao_id" class="pwa-select pr-10">
                <?php foreach ($sessoes as $opcao): ?>
                    <option value="<?= (int) ($opcao['id'] ?? 0) ?>" <?= (int) ($opcao['id'] ?? 0) === $sessaoId ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string) ($opcao['titulo'] ?? 'Sessão')) ?> - <?= htmlspecialchars((string) ($opcao['data_hora_inicio'] ?? '')) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-slate-400">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </div>
        </div>
        <button type="submit" class="pwa-btn-secondary mt-3">Carregar Sessão</button>
    </form>

    <!-- Indicadores Rápidos -->
    <section class="grid grid-cols-3 gap-2.5">
        <div class="pwa-card p-3 text-center border border-white/5 flex flex-col items-center justify-center">
            <div class="text-xl font-bold text-slate-100"><?= count($confirmados) ?></div>
            <div class="text-[9px] text-slate-500 font-semibold uppercase mt-0.5 tracking-wider">Confirmados</div>
        </div>
        <div class="pwa-card p-3 text-center border border-white/5 flex flex-col items-center justify-center">
            <div class="text-xl font-bold text-slate-100"><?= count(array_filter($presencas, static fn($p): bool => !empty($p['presente']))) ?></div>
            <div class="text-[9px] text-slate-500 font-semibold uppercase mt-0.5 tracking-wider">Presentes</div>
        </div>
        <div class="pwa-card p-3 text-center border border-white/5 flex flex-col items-center justify-center">
            <div class="text-xl font-bold text-slate-100"><?= count($visitantes) ?></div>
            <div class="text-[9px] text-slate-500 font-semibold uppercase mt-0.5 tracking-wider">Visitantes</div>
        </div>
    </section>

    <!-- Registrar Visitante Form -->
    <?php if ($sessaoId > 0): ?>
        <section class="pwa-card space-y-3">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Registrar visitante</h3>
            <form method="post" action="/pwa/chancelaria/visitante" class="space-y-3">
                <input type="hidden" name="sessao_id" value="<?= $sessaoId ?>">
                <input name="nome" class="pwa-input" placeholder="Nome do visitante">
                <input name="loja_origem" class="pwa-input" placeholder="Loja / Oriente">
                <button type="submit" class="pwa-btn-primary">Registrar visitante</button>
            </form>
        </section>
    <?php endif; ?>

    <!-- Lista de Presenças -->
    <section class="space-y-3 pb-4">
        <div class="flex items-center gap-3">
            <p class="text-[10px] font-bold tracking-wider uppercase text-slate-500">
                Check-in / Presença efetiva
            </p>
            <div class="flex-1 h-[1px] bg-white/5"></div>
        </div>
        <div class="space-y-2.5">
            <?php foreach ($presencas as $presenca): ?>
                <?php $presente = !empty($presenca['presente']); ?>
                <div class="pwa-card border border-white/5 flex flex-col gap-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="font-bold text-slate-200 text-xs truncate"><?= htmlspecialchars((string) ($presenca['nome'] ?? 'Obreiro')) ?></div>
                            <div class="text-[10px] text-slate-400 mt-0.5">CIM <?= htmlspecialchars((string) ($presenca['cim'] ?? '-')) ?> · <?= htmlspecialchars((string) ($presenca['grau'] ?? '')) ?></div>
                        </div>
                        <span class="pwa-badge <?= $presente ? 'pwa-badge-success' : 'pwa-badge-muted' ?> shrink-0 select-none">
                            <?= $presente ? 'Presente' : 'Pendente' ?>
                        </span>
                    </div>
                    <?php if ($sessaoId > 0): ?>
                        <form method="post" action="/pwa/chancelaria/presenca" class="grid grid-cols-2 gap-2 mt-1 select-none">
                            <input type="hidden" name="sessao_id" value="<?= $sessaoId ?>">
                            <input type="hidden" name="obreiro_id" value="<?= htmlspecialchars((string) ($presenca['id'] ?? '')) ?>">
                            <button name="presente" value="1" class="py-1.5 px-3 rounded-lg text-xs font-bold text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 active:scale-[0.97] transition-transform">Presente</button>
                            <button name="presente" value="0" class="py-1.5 px-3 rounded-lg text-xs font-bold text-slate-400 bg-slate-900 border border-white/5 active:scale-[0.97] transition-transform">Ausente</button>
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
