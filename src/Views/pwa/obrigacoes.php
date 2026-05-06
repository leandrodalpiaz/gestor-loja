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

<div class="p-4 sm:p-6 space-y-5" x-data="{ openUploadDrawer: false, drawerMes: '', drawerAno: '', drawerValor: '' }">

    <!-- Mensagens de Feedback -->
    <?php if ($mensagemSucesso): ?>
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 shadow-sm">
            <div class="flex gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-emerald-600" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <span><?= htmlspecialchars($mensagemSucesso) ?></span>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($mensagemErro): ?>
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800 shadow-sm">
            <div class="flex gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-rose-600" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
                <span><?= htmlspecialchars($mensagemErro) ?></span>
            </div>
        </div>
    <?php endif; ?>

    <!-- Métricas Financeiras Mobile -->
    <div class="grid grid-cols-3 gap-3">
        <div class="rounded-2xl border border-emerald-100 bg-emerald-50/40 p-3 text-center shadow-sm">
            <span class="text-[0.62rem] font-bold uppercase tracking-wider text-emerald-800">Total Pago</span>
            <div class="mt-1 text-sm font-extrabold text-emerald-700"><?= $formatCurrency($totalPagoGeral) ?></div>
        </div>
        <div class="rounded-2xl border border-amber-100 bg-amber-50/40 p-3 text-center shadow-sm">
            <span class="text-[0.62rem] font-bold uppercase tracking-wider text-amber-800">Em Aberto</span>
            <div class="mt-1 text-sm font-extrabold text-amber-700"><?= $formatCurrency($totalAbertoGeral) ?></div>
        </div>
        <div class="rounded-2xl border border-rose-100 bg-rose-50/40 p-3 text-center shadow-sm">
            <span class="text-[0.62rem] font-bold uppercase tracking-wider text-rose-800">Atrasado</span>
            <div class="mt-1 text-sm font-extrabold text-rose-700"><?= $formatCurrency($totalAtrasadoGeral) ?></div>
        </div>
    </div>

    <!-- Chave PIX Rápida -->
    <div class="rounded-2xl border border-erpBorder bg-erpSurface p-4 shadow-sm">
        <div class="flex items-center justify-between gap-4">
            <div class="min-w-0">
                <span class="text-[0.62rem] font-bold uppercase tracking-wider text-erpMuted">Contribuição via PIX</span>
                <p class="mt-0.5 text-xs font-semibold text-erpNavy truncate">
                    Chave <?= htmlspecialchars($pixTipo) ?>: <strong class="font-mono text-erpGold"><?= htmlspecialchars($pixValor ?: 'Não configurada') ?></strong>
                </p>
                <?php if ($pixBeneficiario): ?>
                    <p class="text-[0.65rem] text-erpMuted mt-0.5"><?= htmlspecialchars($pixBeneficiario) ?></p>
                <?php endif; ?>
            </div>
            <?php if ($pixValor): ?>
                <button type="button" 
                        onclick="navigator.clipboard.writeText('<?= htmlspecialchars(addslashes($pixValor)) ?>'); alert('Chave PIX copiada!');"
                        class="shrink-0 rounded-xl bg-erpBg border border-erpBorder px-3 py-1.5 text-xs font-bold text-erpNavy active:scale-95 transition-transform">
                    Copiar
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Pilha Vertical de Cards de Mensalidades (100% de largura) -->
    <div class="space-y-4">
        <h3 class="text-sm font-bold uppercase tracking-widest text-erpNavy">Minhas Mensalidades</h3>

        <?php if (empty($mesesTesourarias)): ?>
            <div class="rounded-2xl border border-dashed border-erpBorder p-8 text-center text-sm text-erpMuted bg-erpSurface/20">
                Nenhum compromisso financeiro encontrado.
            </div>
        <?php endif; ?>

        <?php foreach ($mesesTesourarias as $mes): ?>
            <?php
            $isPago = ($mes['abertos'] === 0) && ($mes['total_pago'] > 0);
            $isAtrasado = ($mes['atrasados'] > 0);

            $statusBadge = ['Em aberto', 'bg-amber-100 text-amber-800 border-amber-300'];
            if ($isPago) {
                $statusBadge = ['Pago', 'bg-emerald-100 text-emerald-800 border-emerald-300'];
            } elseif ($isAtrasado) {
                $statusBadge = ['Atrasado', 'bg-rose-100 text-rose-800 border-rose-300'];
            }
            ?>
            <div class="rounded-2xl border bg-erpSurface p-4 shadow-sm space-y-3 <?= $isPago ? 'border-emerald-100 bg-emerald-50/5' : ($isAtrasado ? 'border-rose-100 bg-rose-50/5' : 'border-erpBorder') ?>">
                
                <!-- Topo: Mês/Ano e Badge de Status -->
                <div class="flex items-center justify-between gap-3">
                    <h4 class="text-base font-bold text-erpNavy"><?= htmlspecialchars($mes['rotulo']) ?></h4>
                    <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[0.6rem] font-bold uppercase tracking-wider <?= htmlspecialchars($statusBadge[1]) ?>">
                        <?= htmlspecialchars($statusBadge[0]) ?>
                    </span>
                </div>

                <!-- Detalhes do Valor -->
                <div class="flex items-baseline justify-between gap-3">
                    <div class="text-xs text-erpMuted">
                        <?php if ($mes['total_pago'] > 0): ?>
                            <span class="text-emerald-600 font-semibold">Pago: <?= $formatCurrency($mes['total_pago']) ?></span>
                        <?php endif; ?>
                        <?php if ($mes['total_aberto'] > 0): ?>
                            <?php if ($mes['total_pago'] > 0): ?> <span class="mx-1">·</span> <?php endif; ?>
                            <span class="text-amber-600 font-semibold">Aberto: <?= $formatCurrency($mes['total_aberto']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="text-lg font-extrabold text-erpNavy">
                        <?= $formatCurrency($mes['total_previsto']) ?>
                    </div>
                </div>

                <!-- Sub-itens (As parcelas em si) -->
                <div class="border-t border-erpBorder/50 pt-2 space-y-1.5 text-xs text-erpMuted">
                    <?php foreach (($mes['itens'] ?? []) as $item): ?>
                        <div class="flex items-center justify-between gap-2">
                            <span class="truncate text-erpNavy font-medium"><?= htmlspecialchars($item['obrigacao_titulo']) ?></span>
                            <span class="font-semibold text-erpNavy"><?= $formatCurrency($item['valor_previsto']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Botão de Ação: Enviar Comprovante ( Progressive disclosure no card ) -->
                <?php if (!$isPago): ?>
                    <div class="border-t border-erpBorder/50 pt-3">
                        <?php
                        $mesNum = (int) substr($mes['chave'], 5, 2);
                        $anoNum = (int) substr($mes['chave'], 0, 4);
                        ?>
                        <button type="button"
                                @click="drawerMes = '<?= $mesNum ?>'; drawerAno = '<?= $anoNum ?>'; drawerValor = '<?= $mes['total_aberto'] ?>'; openUploadDrawer = true"
                                class="w-full inline-flex items-center justify-center gap-1.5 rounded-xl bg-erpNavy px-3 py-2 text-xs font-bold text-white shadow-sm hover:bg-erpNavyDeep active:scale-[0.98] transition-transform">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
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
         DRAWER DE UPLOAD VIA ALPINEJS
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
        
        <!-- Overlay escuro backdrop-blur -->
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="openUploadDrawer = false"></div>

        <!-- Painel Deslizante -->
        <div class="absolute inset-x-0 bottom-0 max-h-[90dvh] rounded-t-3xl border-t border-erpBorder bg-erpSurface pb-safe shadow-2xl flex flex-col"
             x-show="openUploadDrawer"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="translate-y-full"
             x-transition:enter-end="translate-y-0"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="translate-y-0"
             x-transition:leave-end="translate-y-full">
            
            <!-- Handle Visual -->
            <div class="mx-auto my-3 h-1.5 w-12 rounded-full bg-erpMuted/30"></div>

            <!-- Cabeçalho -->
            <div class="flex items-center justify-between px-5 pb-3 border-b border-erpBorder">
                <h3 class="text-base font-bold text-erpNavy">Enviar Comprovante</h3>
                <button type="button" @click="openUploadDrawer = false" class="rounded-full bg-erpBg p-1.5 text-erpMuted hover:text-erpNavy active:scale-90 transition-transform">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>

            <!-- Formulário -->
            <form action="/pwa/obrigacoes/enviar-comprovante" method="POST" enctype="multipart/form-data" class="p-5 overflow-y-auto space-y-4">
                <input type="hidden" name="mes" x-model="drawerMes">
                <input type="hidden" name="ano" x-model="drawerAno">

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-erpMuted">Valor Pago (R$) *</label>
                    <input type="number" name="valor" step="0.01" min="0.01" required x-model="drawerValor"
                           class="mt-1 block w-full rounded-xl border border-erpBorder bg-erpBg p-3 text-sm text-erpNavy focus:border-erpNavy focus:ring-1 focus:ring-erpNavy">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-erpMuted">Arquivo do Comprovante *</label>
                    <div class="mt-1 flex justify-center rounded-xl border-2 border-dashed border-erpBorder bg-erpBg px-6 py-5">
                        <div class="space-y-1 text-center">
                            <svg class="mx-auto h-10 w-10 text-erpMuted" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div class="flex text-xs text-erpMuted">
                                <label for="file-upload" class="relative cursor-pointer rounded-md bg-white font-semibold text-erpNavy focus-within:outline-none focus-within:ring-2 focus-within:ring-erpNavy focus-within:ring-offset-2 hover:text-erpNavyDeep">
                                    <span>Tirar foto ou selecionar</span>
                                    <input id="file-upload" name="comprovante" type="file" accept="image/*,application/pdf" class="sr-only" required>
                                </label>
                            </div>
                            <p class="text-[0.62rem] text-erpMuted">Imagem (JPEG, PNG) ou PDF de até 5MB</p>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-erpMuted">Observação (Opcional)</label>
                    <input type="text" name="descricao" placeholder="Ex: Mensalidade mais biblioteca"
                           class="mt-1 block w-full rounded-xl border border-erpBorder bg-erpBg p-3 text-sm text-erpNavy focus:border-erpNavy focus:ring-1 focus:ring-erpNavy">
                </div>

                <div class="pt-2">
                    <button type="submit"
                            class="w-full inline-flex items-center justify-center rounded-xl bg-erpNavy p-3 text-sm font-bold text-white shadow-lg hover:bg-erpNavyDeep active:scale-[0.98] transition-transform">
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
