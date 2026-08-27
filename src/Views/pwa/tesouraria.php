<?php
declare(strict_types=1);

$mes = (int) ($mes ?? date('n'));
$ano = (int) ($ano ?? date('Y'));
$totais = is_array($totais ?? null) ? $totais : ['entrada' => 0, 'saida' => 0];
$lancamentos = is_array($lancamentos ?? null) ? $lancamentos : [];
$comprovantesPendentes = is_array($comprovantesPendentes ?? null) ? $comprovantesPendentes : [];
$regularidadeResumo = is_array($regularidadeResumo ?? null) ? $regularidadeResumo : ['regular' => 0, 'irregular' => 0];
$regularidadeLista = is_array($regularidadeLista ?? null) ? $regularidadeLista : [];
$fechamento = is_array($fechamento ?? null) ? $fechamento : null;
$obreirosPainel = is_array($obreirosPainel ?? null) ? $obreirosPainel : [];
$sessoesFinanceiras = is_array($sessoesFinanceiras ?? null) ? $sessoesFinanceiras : [];

$formatCurrency = static fn ($value): string => 'R$ ' . number_format((float) $value, 2, ',', '.');
$saldo = (float) ($totais['entrada'] ?? 0) - (float) ($totais['saida'] ?? 0);

$pwaPageTitle = 'Tesouraria';
$pwaShowBackButton = true;
$pwaBackUrl = '/pwa/admin';
$pwaActiveTab = 'cargo';

ob_start();
?>

<div class="px-4 py-4 space-y-4">
    <section class="pwa-hero">
        <p class="pwa-eyebrow">Competência <?= str_pad((string) $mes, 2, '0', STR_PAD_LEFT) ?>/<?= $ano ?></p>
        <h2 class="mt-2 text-xl font-bold tracking-tight text-white">Painel Financeiro</h2>
        <p class="pwa-muted mt-1.5 text-xs">Acompanhamento de caixa, comprovantes, regularidade e sessões.</p>
    </section>

    <!-- Filtro Form -->
    <form method="get" action="/pwa/tesouraria" class="pwa-card grid grid-cols-3 gap-2 select-none">
        <input name="mes" value="<?= $mes ?>" type="number" min="1" max="12" class="pwa-input" placeholder="Mês">
        <input name="ano" value="<?= $ano ?>" type="number" min="2020" class="pwa-input" placeholder="Ano">
        <button class="pwa-btn-primary py-0 h-full text-xs">Filtrar</button>
    </form>

    <!-- Indicadores Financeiros -->
    <section class="grid grid-cols-3 gap-2.5">
        <div class="pwa-card p-3 text-center border border-white/5 flex flex-col items-center justify-center">
            <div class="text-[9px] text-slate-500 font-semibold uppercase tracking-wider">Entradas</div>
            <div class="mt-1 text-xs font-bold text-emerald-400 truncate w-full"><?= $formatCurrency($totais['entrada'] ?? 0) ?></div>
        </div>
        <div class="pwa-card p-3 text-center border border-white/5 flex flex-col items-center justify-center">
            <div class="text-[9px] text-slate-500 font-semibold uppercase tracking-wider">Saídas</div>
            <div class="mt-1 text-xs font-bold text-red-400 truncate w-full"><?= $formatCurrency($totais['saida'] ?? 0) ?></div>
        </div>
        <div class="pwa-card p-3 text-center border border-white/5 flex flex-col items-center justify-center">
            <div class="text-[9px] text-slate-500 font-semibold uppercase tracking-wider">Saldo</div>
            <div class="mt-1 text-xs font-bold text-slate-100 truncate w-full"><?= $formatCurrency($saldo) ?></div>
        </div>
    </section>

    <!-- Widgets rápidos -->
    <section class="grid grid-cols-2 gap-2.5 select-none">
        <a href="/pwa/comprovantes" class="pwa-card flex flex-col justify-between border border-white/5 active:scale-[0.97] transition-transform no-underline">
            <span class="pwa-badge pwa-badge-warn self-start font-bold"><?= count($comprovantesPendentes) ?></span>
            <div class="text-xs font-bold text-slate-200 mt-2">Comprovantes pendentes</div>
        </a>
        <a href="/tesouraria/fechamento" class="pwa-card flex flex-col justify-between border border-white/5 active:scale-[0.97] transition-transform no-underline">
            <span class="pwa-badge pwa-badge-muted self-start font-bold"><?= $fechamento ? 'Ativo' : 'Pendente' ?></span>
            <div class="text-xs font-bold text-slate-200 mt-2 truncate">Fechamento Competência</div>
        </a>
    </section>

    <!-- Regularidade Section -->
    <section class="pwa-card space-y-3">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Regularidade</h3>
                <p class="text-[10px] text-slate-500 font-medium mt-0.5">Regulares: <?= (int) ($regularidadeResumo['regular'] ?? 0) ?> · Irregulares: <?= (int) ($regularidadeResumo['irregular'] ?? 0) ?></p>
            </div>
            <a href="/tesouraria/regularidade" class="pwa-btn-secondary py-1.5 px-3.5 w-auto text-[11px] font-bold select-none">Gerir</a>
        </div>
        <div class="pwa-list-group">
            <?php foreach ($regularidadeLista as $registro): ?>
                <?php $status = (string) ($registro['status'] ?? 'pendente'); ?>
                <div class="pwa-list-item flex items-center justify-between gap-3">
                    <span class="truncate text-xs font-medium text-slate-200"><?= htmlspecialchars((string) ($registro['obreiro_nome'] ?? 'Obreiro')) ?></span>
                    <span class="pwa-badge <?= $status === 'regular' ? 'pwa-badge-success' : 'pwa-badge-warn' ?> select-none"><?= htmlspecialchars($status) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Obrigações em aberto Section -->
    <section class="pwa-card space-y-3">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Obrigações em aberto</h3>
                <p class="text-[10px] text-slate-500 font-medium mt-0.5">Principais obreiros com pendência financeira.</p>
            </div>
            <a href="/tesouraria/obrigacoes" class="pwa-btn-secondary py-1.5 px-3.5 w-auto text-[11px] font-bold select-none">Gerir</a>
        </div>
        <div class="pwa-list-group">
            <?php foreach ($obreirosPainel as $item): ?>
                <div class="pwa-list-item flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <div class="text-xs font-bold text-slate-200 truncate"><?= htmlspecialchars((string) ($item['nome'] ?? $item['nome_historico'] ?? 'Obreiro')) ?></div>
                        <div class="text-[10px] text-slate-400 mt-0.5">Aberto: <?= $formatCurrency($item['total_em_aberto'] ?? $item['saldo_aberto'] ?? 0) ?></div>
                    </div>
                    <svg class="w-3.5 h-3.5 text-slate-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Últimos Lançamentos Section -->
    <section class="pwa-card space-y-3">
        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Últimos lançamentos</h3>
        <div class="pwa-list-group">
            <?php foreach ($lancamentos as $lancamento): ?>
                <div class="pwa-list-item flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <div class="truncate text-xs font-semibold text-slate-200"><?= htmlspecialchars((string) ($lancamento['descricao'] ?? 'Lançamento')) ?></div>
                        <div class="text-[10px] text-slate-400 mt-0.5"><?= htmlspecialchars((string) ($lancamento['data_lancamento'] ?? '')) ?></div>
                    </div>
                    <span class="text-xs font-bold shrink-0 <?= ($lancamento['tipo'] ?? '') === 'saida' ? 'text-red-400' : 'text-emerald-400' ?>"><?= $formatCurrency($lancamento['valor'] ?? 0) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <a href="/tesouraria/caixa" class="pwa-btn-secondary mt-2 w-full text-xs font-bold select-none">Abrir livro-caixa completo</a>
    </section>

    <!-- Sessões Financeiras Section -->
    <section class="pwa-card space-y-3 pb-4">
        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Sessões financeiras</h3>
        <div class="pwa-list-group">
            <?php foreach ($sessoesFinanceiras as $sessao): ?>
                <div class="pwa-list-item flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <div class="text-xs font-bold text-slate-200 truncate"><?= htmlspecialchars((string) ($sessao['titulo'] ?? 'Sessão')) ?></div>
                        <div class="text-[10px] text-slate-400 mt-0.5">Ágape: <?= htmlspecialchars((string) ($sessao['agape_modalidade'] ?? '')) ?> · Confirmados: <?= (int) ($sessao['total_confirmados'] ?? 0) ?></div>
                    </div>
                    <svg class="w-3.5 h-3.5 text-slate-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </div>
            <?php endforeach; ?>
        </div>
        <a href="/tesouraria/sessoes" class="pwa-btn-secondary mt-2 w-full text-xs font-bold select-none">Abrir sessões financeiras</a>
    </section>
</div>

<?php
$pwaContent = ob_get_clean();
require __DIR__ . '/shell.php';
?>
