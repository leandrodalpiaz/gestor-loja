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

<div class="p-4 sm:p-6 space-y-4">
    <section class="pwa-hero p-5">
        <p class="pwa-eyebrow">Competência <?= str_pad((string) $mes, 2, '0', STR_PAD_LEFT) ?>/<?= $ano ?></p>
        <h2 class="mt-3 text-2xl font-bold tracking-tight text-white">Painel financeiro</h2>
        <p class="pwa-muted mt-2 text-sm">Caixa, comprovantes, regularidade, obrigações, fechamento e sessões financeiras.</p>
    </section>

    <form method="get" action="/pwa/tesouraria" class="pwa-card grid grid-cols-3 gap-2" style="padding:1rem;">
        <input name="mes" value="<?= $mes ?>" type="number" min="1" max="12" class="pwa-input">
        <input name="ano" value="<?= $ano ?>" type="number" min="2020" class="pwa-input">
        <button class="pwa-btn-primary">Filtrar</button>
    </form>

    <section class="grid grid-cols-3 gap-3">
        <div class="pwa-card p-3">
            <div class="text-xs" style="color:#94a3b8;">Entradas</div>
            <div class="mt-1 text-lg font-bold" style="color:#34d399;"><?= $formatCurrency($totais['entrada'] ?? 0) ?></div>
        </div>
        <div class="pwa-card p-3">
            <div class="text-xs" style="color:#94a3b8;">Saídas</div>
            <div class="mt-1 text-lg font-bold" style="color:#f87171;"><?= $formatCurrency($totais['saida'] ?? 0) ?></div>
        </div>
        <div class="pwa-card p-3">
            <div class="text-xs" style="color:#94a3b8;">Saldo</div>
            <div class="mt-1 text-lg font-bold" style="color:#f1f5f9;"><?= $formatCurrency($saldo) ?></div>
        </div>
    </section>

    <section class="grid grid-cols-2 gap-3">
        <a href="/pwa/comprovantes" class="pwa-card p-4" style="display:block;">
            <div class="text-2xl font-bold pwa-badge pwa-badge-warn" style="display:inline-block;margin-bottom:0.25rem;"><?= count($comprovantesPendentes) ?></div>
            <div class="text-sm font-semibold" style="color:#f1f5f9;">Comprovantes pendentes</div>
        </a>
        <a href="/tesouraria/fechamento" class="pwa-card p-4" style="display:block;">
            <div class="text-sm font-semibold" style="color:#f1f5f9;">Fechamento</div>
            <div class="mt-1 text-xs" style="color:#94a3b8;"><?= $fechamento ? 'Competência aberta/registrada' : 'Sem fechamento criado' ?></div>
        </a>
    </section>

    <section class="pwa-card" style="padding:1rem;">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h3 class="font-bold" style="color:#f1f5f9;">Regularidade</h3>
                <p class="text-xs" style="color:#94a3b8;">Regulares: <?= (int) ($regularidadeResumo['regular'] ?? 0) ?> · Irregulares: <?= (int) ($regularidadeResumo['irregular'] ?? 0) ?></p>
            </div>
            <a href="/tesouraria/regularidade" class="pwa-btn-primary" style="padding:0.5rem 0.75rem;font-size:0.75rem;">Gerir</a>
        </div>
        <div class="mt-3 space-y-2">
            <?php foreach ($regularidadeLista as $registro): ?>
                <?php $status = (string) ($registro['status'] ?? 'pendente'); ?>
                <div class="flex items-center justify-between gap-3 rounded-lg px-3 py-2" style="background:rgba(255,255,255,0.03);">
                    <span class="truncate text-sm font-medium" style="color:#f1f5f9;"><?= htmlspecialchars((string) ($registro['obreiro_nome'] ?? 'Obreiro')) ?></span>
                    <span class="pwa-badge <?= $status === 'regular' ? 'pwa-badge-success' : 'pwa-badge-warn' ?>"><?= htmlspecialchars($status) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="pwa-card" style="padding:1rem;">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h3 class="font-bold" style="color:#f1f5f9;">Obrigações em aberto</h3>
                <p class="text-xs" style="color:#94a3b8;">Principais obreiros com pendência financeira.</p>
            </div>
            <a href="/tesouraria/obrigacoes" class="pwa-btn-primary" style="padding:0.5rem 0.75rem;font-size:0.75rem;">Gerir</a>
        </div>
        <div class="mt-3 space-y-2">
            <?php foreach ($obreirosPainel as $item): ?>
                <div class="rounded-lg px-3 py-2" style="background:rgba(255,255,255,0.03);">
                    <div class="text-sm font-semibold" style="color:#f1f5f9;"><?= htmlspecialchars((string) ($item['nome'] ?? $item['nome_historico'] ?? 'Obreiro')) ?></div>
                    <div class="text-xs" style="color:#94a3b8;">Aberto: <?= $formatCurrency($item['total_em_aberto'] ?? $item['saldo_aberto'] ?? 0) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="pwa-card" style="padding:1rem;">
        <h3 class="font-bold" style="color:#f1f5f9;">Últimos lançamentos</h3>
        <div class="mt-3 space-y-2">
            <?php foreach ($lancamentos as $lancamento): ?>
                <div class="flex items-center justify-between gap-3 rounded-lg px-3 py-2" style="background:rgba(255,255,255,0.03);">
                    <div class="min-w-0">
                        <div class="truncate text-sm font-medium" style="color:#f1f5f9;"><?= htmlspecialchars((string) ($lancamento['descricao'] ?? 'Lançamento')) ?></div>
                        <div class="text-xs" style="color:#94a3b8;"><?= htmlspecialchars((string) ($lancamento['data_lancamento'] ?? '')) ?></div>
                    </div>
                    <span class="text-sm font-bold" style="color:<?= ($lancamento['tipo'] ?? '') === 'saida' ? '#f87171' : '#34d399' ?>;"><?= $formatCurrency($lancamento['valor'] ?? 0) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <a href="/tesouraria/caixa" class="mt-3 block rounded-lg px-3 py-2 text-center text-sm font-semibold" style="border:1px solid rgba(255,255,255,0.09);color:#f1f5f9;">Abrir livro-caixa completo</a>
    </section>

    <section class="pwa-card" style="padding:1rem;">
        <h3 class="font-bold" style="color:#f1f5f9;">Sessões financeiras</h3>
        <div class="mt-3 space-y-2">
            <?php foreach ($sessoesFinanceiras as $sessao): ?>
                <div class="rounded-lg px-3 py-2" style="background:rgba(255,255,255,0.03);">
                    <div class="text-sm font-semibold" style="color:#f1f5f9;"><?= htmlspecialchars((string) ($sessao['titulo'] ?? 'Sessão')) ?></div>
                    <div class="text-xs" style="color:#94a3b8;">Ágape: <?= htmlspecialchars((string) ($sessao['agape_modalidade'] ?? '')) ?> · Confirmados: <?= (int) ($sessao['total_confirmados'] ?? 0) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        <a href="/tesouraria/sessoes" class="mt-3 block rounded-lg px-3 py-2 text-center text-sm font-semibold" style="border:1px solid rgba(255,255,255,0.09);color:#f1f5f9;">Abrir sessões financeiras</a>
    </section>
</div>

<?php
$pwaContent = ob_get_clean();
require __DIR__ . '/shell.php';
?>
