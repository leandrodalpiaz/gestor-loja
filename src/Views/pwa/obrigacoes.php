<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

$resumoObreiro = $resumoObreiro ?? [];
$obrigacoesObreiro = $obrigacoesObreiro ?? [];
$configuracaoFinanceira = $configuracaoFinanceira ?? [];

$formatCurrency = static fn ($value): string => 'R$ ' . number_format((float) $value, 2, ',', '.');
$formatDate = static fn (?string $date): string => $date ? (new DateTimeImmutable($date))->format('d/m/Y') : '-';

$pixTipo = (string) ($configuracaoFinanceira['pix_chave_tipo'] ?? 'CNPJ');
$pixValor = (string) ($configuracaoFinanceira['pix_chave_valor'] ?? '');
$pixBeneficiario = (string) ($configuracaoFinanceira['pix_beneficiario'] ?? '');
$hoje = date('Y-m-d');
$mesAtualChave = date('Y-m');

$nomesMeses = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril', 5 => 'Maio', 6 => 'Junho',
    7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
];

$mesesTesourarias = [];

// Processar parcelas para agrupar por mês
foreach ($obrigacoesObreiro as $obrigacao) {
    foreach (($obrigacao['parcelas'] ?? []) as $parcela) {
        $parcela['obrigacao_titulo'] = (string) ($parcela['obrigacao_titulo'] ?? $obrigacao['titulo'] ?? 'Obrigação');
        $parcela['tipo_obrigacao'] = (string) ($parcela['tipo_obrigacao'] ?? $obrigacao['tipo_obrigacao'] ?? 'outra');
        $mesCompetencia = (int) ($parcela['competencia_mes'] ?? 0);
        $anoCompetencia = (int) ($parcela['competencia_ano'] ?? 0);

        if ($mesCompetencia > 0 && $anoCompetencia > 0) {
            $chaveMes = sprintf('%04d-%02d', $anoCompetencia, $mesCompetencia);
            if (!isset($mesesTesourarias[$chaveMes])) {
                $mesesTesourarias[$chaveMes] = [
                    'chave' => $chaveMes,
                    'rotulo' => ($nomesMeses[$mesCompetencia] ?? 'Mês') . ' ' . $anoCompetencia,
                    'total_pago' => 0.0,
                    'total_previsto' => 0.0,
                    'total_aberto' => 0.0,
                    'pagos' => 0,
                    'abertos' => 0,
                    'atrasados' => 0,
                    'itens' => [],
                ];
            }
            $mesesTesourarias[$chaveMes]['total_previsto'] += (float) ($parcela['valor_previsto'] ?? 0);
            if (!empty($parcela['quitado_na_exibicao'])) {
                $mesesTesourarias[$chaveMes]['total_pago'] += (float) ($parcela['valor_previsto'] ?? 0);
                $mesesTesourarias[$chaveMes]['pagos']++;
            } else {
                $mesesTesourarias[$chaveMes]['total_aberto'] += (float) ($parcela['valor_previsto'] ?? 0);
                $mesesTesourarias[$chaveMes]['abertos']++;
                if (!empty($parcela['em_atraso'])) {
                    $mesesTesourarias[$chaveMes]['atrasados']++;
                }
            }
            $mesesTesourarias[$chaveMes]['itens'][] = $parcela;
        }
    }
}

uasort($mesesTesourarias, static fn (array $a, array $b): int => strcmp((string) ($b['chave'] ?? ''), (string) ($a['chave'] ?? '')));

// Totais gerais
$totalPagoGeral = 0.0;
$totalAbertoGeral = 0.0;
$totalAtrasadoGeral = 0.0;

foreach ($mesesTesourarias as $m) {
    $totalPagoGeral += $m['total_pago'];
    $totalAbertoGeral += $m['total_aberto'];
    if ($m['atrasados'] > 0) {
        $totalAtrasadoGeral += $m['total_aberto'];
    }
}

$pwaPageTitle = 'Obrigações';
$pwaActiveTab = 'cargo';
$pwaShowBackButton = true;
$pwaBackUrl = '/pwa';

$mensagemSucesso = $_SESSION['mensagem_sucesso'] ?? null;
$mensagemErro = $_SESSION['mensagem_erro'] ?? null;
unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']);

ob_start();
?>

<div class="px-4 py-4 space-y-4" x-data="{ openUploadDrawer: false, drawerMes: '', drawerAno: '', drawerValor: '' }">

    <!-- Mensagens de Feedback -->
    <?php if ($mensagemSucesso): ?>
        <div class="pwa-alert-success"><?= htmlspecialchars($mensagemSucesso) ?></div>
    <?php endif; ?>

    <?php if ($mensagemErro): ?>
        <div class="pwa-alert-error"><?= htmlspecialchars($mensagemErro) ?></div>
    <?php endif; ?>

    <!-- Métricas Financeiras Mobile -->
    <div class="grid grid-cols-3 gap-2.5">
        <div class="pwa-card p-3 text-center border border-white/5 flex flex-col items-center justify-center bg-emerald-500/5">
            <span class="text-[9px] font-bold uppercase tracking-wider text-emerald-400">Total Pago</span>
            <div class="mt-1 text-xs font-bold text-emerald-400"><?= $formatCurrency($totalPagoGeral) ?></div>
        </div>
        <div class="pwa-card p-3 text-center border border-white/5 flex flex-col items-center justify-center bg-amber-500/5">
            <span class="text-[9px] font-bold uppercase tracking-wider text-amber-400">Em Aberto</span>
            <div class="mt-1 text-xs font-bold text-amber-400"><?= $formatCurrency($totalAbertoGeral) ?></div>
        </div>
        <div class="pwa-card p-3 text-center border border-white/5 flex flex-col items-center justify-center bg-red-500/5">
            <span class="text-[9px] font-bold uppercase tracking-wider text-red-400">Atrasado</span>
            <div class="mt-1 text-xs font-bold text-red-400"><?= $formatCurrency($totalAtrasadoGeral) ?></div>
        </div>
    </div>

    <!-- Chave PIX Rápida -->
    <div class="pwa-card flex items-center justify-between gap-4 border border-white/5">
        <div class="min-w-0">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Contribuição via PIX</span>
            <p class="mt-0.5 text-xs font-semibold truncate text-slate-100">
                Chave <?= htmlspecialchars($pixTipo) ?>: <strong class="font-mono text-amber-500"><?= htmlspecialchars($pixValor ?: 'Não configurada') ?></strong>
            </p>
            <?php if ($pixBeneficiario): ?>
                <p class="text-[10px] text-slate-400 mt-0.5 truncate"><?= htmlspecialchars($pixBeneficiario) ?></p>
            <?php endif; ?>
        </div>
        <?php if ($pixValor): ?>
            <button type="button" 
                    onclick="navigator.clipboard.writeText('<?= htmlspecialchars(addslashes($pixValor)) ?>'); alert('Chave PIX copiada!');"
                    class="pwa-btn-secondary py-1.5 px-3 w-auto text-xs shrink-0 select-none">
                Copiar
            </button>
        <?php endif; ?>
    </div>

    <!-- Pilha Vertical de Cards de Mensalidades -->
    <div class="space-y-3.5">
        <div class="flex items-center gap-3">
            <p class="text-[10px] font-bold tracking-wider uppercase text-slate-500">
                Minhas Mensalidades
            </p>
            <div class="flex-1 h-[1px] bg-white/5"></div>
        </div>

        <?php if (empty($mesesTesourarias)): ?>
            <div class="p-8 text-center text-xs text-slate-500 bg-slate-900/40 rounded-2xl border border-dashed border-white/10 select-none">
                Nenhum compromisso financeiro encontrado.
            </div>
        <?php endif; ?>

        <?php foreach ($mesesTesourarias as $mes): ?>
            <?php
            $isPago = ($mes['abertos'] === 0) && ($mes['total_pago'] > 0);
            $isAtrasado = ($mes['atrasados'] > 0);
            ?>
            <div class="pwa-card border flex flex-col gap-3.5 <?= $isPago ? 'border-emerald-500/20 bg-emerald-500/5' : ($isAtrasado ? 'border-red-500/20 bg-red-500/5' : 'border-white/5 bg-slate-900/40') ?>">
                
                <!-- Topo: Mês/Ano e Badge de Status -->
                <div class="flex items-center justify-between gap-3">
                    <h4 class="text-sm font-bold text-slate-200"><?= htmlspecialchars($mes['rotulo']) ?></h4>
                    <span class="pwa-badge <?= $isPago ? 'pwa-badge-success' : ($isAtrasado ? 'pwa-badge-danger' : 'pwa-badge-warn') ?> shrink-0 select-none">
                        <?= htmlspecialchars($isPago ? 'Pago' : ($isAtrasado ? 'Atrasado' : 'Em aberto')) ?>
                    </span>
                </div>

                <!-- Detalhes do Valor -->
                <div class="flex items-baseline justify-between gap-3">
                    <div class="text-[10px] text-slate-400">
                        <?php if ($mes['total_pago'] > 0): ?>
                            <span class="font-semibold text-emerald-400">Pago: <?= $formatCurrency($mes['total_pago']) ?></span>
                        <?php endif; ?>
                        <?php if ($mes['total_aberto'] > 0): ?>
                            <?php if ($mes['total_pago'] > 0): ?> <span class="mx-1">·</span> <?php endif; ?>
                            <span class="font-semibold text-amber-500">Aberto: <?= $formatCurrency($mes['total_aberto']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="text-base font-black text-slate-200">
                        <?= $formatCurrency($mes['total_previsto']) ?>
                    </div>
                </div>

                <!-- Sub-itens (As parcelas em si) -->
                <div class="pt-2 space-y-2 border-t border-white/5 text-[11px] text-slate-400">
                    <?php foreach (($mes['itens'] ?? []) as $item): ?>
                        <div class="flex items-center justify-between gap-2">
                            <span class="truncate text-slate-300 font-medium"><?= htmlspecialchars($item['obrigacao_titulo']) ?></span>
                            <span class="font-semibold text-slate-200"><?= $formatCurrency($item['valor_previsto']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Botão de Ação: Enviar Comprovante -->
                <?php if (!$isPago): ?>
                    <div class="pt-2 border-t border-white/5 select-none">
                        <?php
                        $mesNum = (int) substr($mes['chave'], 5, 2);
                        $anoNum = (int) substr($mes['chave'], 0, 4);
                        ?>
                        <button type="button"
                                @click="drawerMes = '<?= $mesNum ?>'; drawerAno = '<?= $anoNum ?>'; drawerValor = '<?= $mes['total_aberto'] ?>'; openUploadDrawer = true"
                                class="pwa-btn-primary py-2 text-xs font-bold bg-amber-500 text-slate-950">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                            </svg>
                            Enviar Comprovante
                        </button>
                    </div>
                <?php endif; ?>

            </div>
        <?php endforeach; ?>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════
         DRAWER DE UPLOAD
    ════════════════════════════════════════════════════════════════════ -->
    <div class="fixed inset-0 z-50 overflow-hidden" 
         x-show="openUploadDrawer" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;">
        
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="openUploadDrawer = false"></div>

        <div class="absolute inset-x-0 bottom-0 max-h-[90dvh] rounded-t-3xl pb-safe shadow-2xl flex flex-col bg-slate-900 border-t border-white/10"
             x-show="openUploadDrawer"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="translate-y-full"
             x-transition:enter-end="translate-y-0"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="translate-y-0"
             x-transition:leave-end="translate-y-full">
            
            <div class="mx-auto my-3 h-1 w-9 rounded-full bg-slate-700 select-none"></div>

            <div class="flex items-center justify-between px-5 pb-3 border-b border-white/5">
                <h3 class="text-sm font-bold text-slate-100">Enviar Comprovante</h3>
                <button type="button" @click="openUploadDrawer = false" class="rounded-full p-1.5 bg-slate-800 text-slate-400 active:scale-90 transition-transform">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Formulário -->
            <form action="/pwa/obrigacoes/enviar-comprovante" method="POST" enctype="multipart/form-data" class="p-5 overflow-y-auto space-y-4">
                <?= \App\Core\Http\WebGuards::csrfField() ?>
                <input type="hidden" name="mes" x-model="drawerMes">
                <input type="hidden" name="ano" x-model="drawerAno">

                <div>
                    <label class="pwa-label">Valor Pago (R$) *</label>
                    <input type="number" name="valor" step="0.01" min="0.01" required x-model="drawerValor" class="pwa-input">
                </div>

                <div>
                    <label class="pwa-label">Arquivo do Comprovante *</label>
                    <div class="mt-1 flex justify-center rounded-xl px-6 py-5 border-2 border-dashed border-white/10 bg-slate-900/50">
                        <div class="space-y-2 text-center flex flex-col items-center">
                            <svg class="h-8 w-8 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <div class="flex text-xs text-slate-400 select-none">
                                <label for="file-upload" class="relative cursor-pointer rounded-md font-semibold text-slate-200 hover:text-amber-500 transition-colors">
                                    <span>Tirar foto ou selecionar</span>
                                    <input id="file-upload" name="comprovante" type="file" accept="image/*,application/pdf" class="sr-only" required>
                                </label>
                            </div>
                            <p class="text-[9px] text-slate-500">Imagem (JPEG, PNG) ou PDF de até 5MB</p>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="pwa-label">Observação (Opcional)</label>
                    <input type="text" name="descricao" placeholder="Ex: Mensalidade mais biblioteca" class="pwa-input">
                </div>

                <div class="pt-2 pb-4 select-none">
                    <button type="submit" class="pwa-btn-primary">
                        Confirmar e Enviar
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<?php
$pwaContent = ob_get_clean();
require __DIR__ . '/shell.php';
?>
