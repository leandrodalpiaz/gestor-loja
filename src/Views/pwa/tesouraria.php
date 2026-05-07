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

    <form method="get" action="/pwa/tesouraria" class="grid grid-cols-3 gap-2 rounded-2xl border border-erpBorder bg-erpSurface p-4">
        <input name="mes" value="<?= $mes ?>" type="number" min="1" max="12" class="rounded-lg border border-erpBorder px-3 py-2 text-sm">
        <input name="ano" value="<?= $ano ?>" type="number" min="2020" class="rounded-lg border border-erpBorder px-3 py-2 text-sm">
        <button class="rounded-lg bg-erpNavy px-3 py-2 text-sm font-semibold text-white">Filtrar</button>
    </form>

    <section class="grid grid-cols-3 gap-3">
        <div class="rounded-2xl border border-erpBorder bg-erpSurface p-3">
            <div class="text-xs text-erpMuted">Entradas</div>
            <div class="mt-1 text-lg font-bold text-emerald-700"><?= $formatCurrency($totais['entrada'] ?? 0) ?></div>
        </div>
        <div class="rounded-2xl border border-erpBorder bg-erpSurface p-3">
            <div class="text-xs text-erpMuted">Saídas</div>
            <div class="mt-1 text-lg font-bold text-rose-700"><?= $formatCurrency($totais['saida'] ?? 0) ?></div>
        </div>
        <div class="rounded-2xl border border-erpBorder bg-erpSurface p-3">
            <div class="text-xs text-erpMuted">Saldo</div>
            <div class="mt-1 text-lg font-bold text-erpNavy"><?= $formatCurrency($saldo) ?></div>
        </div>
    </section>

    <section class="grid grid-cols-2 gap-3">
        <a href="/pwa/comprovantes" class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
            <div class="text-2xl font-bold text-amber-800"><?= count($comprovantesPendentes) ?></div>
            <div class="text-sm font-semibold text-amber-900">Comprovantes pendentes</div>
        </a>
        <a href="/tesouraria/fechamento" class="rounded-2xl border border-erpBorder bg-erpSurface p-4">
            <div class="text-sm font-semibold text-erpNavy">Fechamento</div>
            <div class="mt-1 text-xs text-erpMuted"><?= $fechamento ? 'Competência aberta/registrada' : 'Sem fechamento criado' ?></div>
        </a>
    </section>

    <section class="rounded-2xl border border-erpBorder bg-erpSurface p-4">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h3 class="font-bold text-erpNavy">Regularidade</h3>
                <p class="text-xs text-erpMuted">Regulares: <?= (int) ($regularidadeResumo['regular'] ?? 0) ?> · Irregulares: <?= (int) ($regularidadeResumo['irregular'] ?? 0) ?></p>
            </div>
            <a href="/tesouraria/regularidade" class="rounded-lg bg-erpNavy px-3 py-2 text-xs font-semibold text-white">Gerir</a>
        </div>
        <div class="mt-3 space-y-2">
            <?php foreach ($regularidadeLista as $registro): ?>
                <?php $status = (string) ($registro['status'] ?? 'pendente'); ?>
                <div class="flex items-center justify-between gap-3 rounded-lg bg-erpBg px-3 py-2">
                    <span class="truncate text-sm font-medium text-erpNavy"><?= htmlspecialchars((string) ($registro['obreiro_nome'] ?? 'Obreiro')) ?></span>
                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold <?= $status === 'regular' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' ?>"><?= htmlspecialchars($status) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="rounded-2xl border border-erpBorder bg-erpSurface p-4">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h3 class="font-bold text-erpNavy">Obrigações em aberto</h3>
                <p class="text-xs text-erpMuted">Principais obreiros com pendência financeira.</p>
            </div>
            <a href="/tesouraria/obrigacoes" class="rounded-lg bg-erpNavy px-3 py-2 text-xs font-semibold text-white">Gerir</a>
        </div>
        <div class="mt-3 space-y-2">
            <?php foreach ($obreirosPainel as $item): ?>
                <div class="rounded-lg bg-erpBg px-3 py-2">
                    <div class="text-sm font-semibold text-erpNavy"><?= htmlspecialchars((string) ($item['nome'] ?? $item['nome_historico'] ?? 'Obreiro')) ?></div>
                    <div class="text-xs text-erpMuted">Aberto: <?= $formatCurrency($item['total_em_aberto'] ?? $item['saldo_aberto'] ?? 0) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="rounded-2xl border border-erpBorder bg-erpSurface p-4">
        <h3 class="font-bold text-erpNavy">Últimos lançamentos</h3>
        <div class="mt-3 space-y-2">
            <?php foreach ($lancamentos as $lancamento): ?>
                <div class="flex items-center justify-between gap-3 rounded-lg bg-erpBg px-3 py-2">
                    <div class="min-w-0">
                        <div class="truncate text-sm font-medium text-erpNavy"><?= htmlspecialchars((string) ($lancamento['descricao'] ?? 'Lançamento')) ?></div>
                        <div class="text-xs text-erpMuted"><?= htmlspecialchars((string) ($lancamento['data_lancamento'] ?? '')) ?></div>
                    </div>
                    <span class="text-sm font-bold <?= ($lancamento['tipo'] ?? '') === 'saida' ? 'text-rose-700' : 'text-emerald-700' ?>"><?= $formatCurrency($lancamento['valor'] ?? 0) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <a href="/tesouraria/caixa" class="mt-3 block rounded-lg border border-erpBorder px-3 py-2 text-center text-sm font-semibold text-erpNavy">Abrir livro-caixa completo</a>
    </section>

    <section class="rounded-2xl border border-erpBorder bg-erpSurface p-4">
        <h3 class="font-bold text-erpNavy">Sessões financeiras</h3>
        <div class="mt-3 space-y-2">
            <?php foreach ($sessoesFinanceiras as $sessao): ?>
                <div class="rounded-lg bg-erpBg px-3 py-2">
                    <div class="text-sm font-semibold text-erpNavy"><?= htmlspecialchars((string) ($sessao['titulo'] ?? 'Sessão')) ?></div>
                    <div class="text-xs text-erpMuted">Ágape: <?= htmlspecialchars((string) ($sessao['agape_modalidade'] ?? '')) ?> · Confirmados: <?= (int) ($sessao['total_confirmados'] ?? 0) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        <a href="/tesouraria/sessoes" class="mt-3 block rounded-lg border border-erpBorder px-3 py-2 text-center text-sm font-semibold text-erpNavy">Abrir sessões financeiras</a>
    </section>
</div>

<?php
$pwaContent = ob_get_clean();
require __DIR__ . '/shell.php';
?>
