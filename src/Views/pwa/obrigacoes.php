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
        <div class="rounded-2xl p-4 text-sm shadow-sm" style="background:rgba(52,211,153,0.15);color:#6ee7b7;border:1px solid rgba(52,211,153,0.25)">
            <div class="flex gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" viewBox="0 0 20 20" fill="currentColor" style="color:#6ee7b7">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <span><?= htmlspecialchars($mensagemSucesso) ?></span>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($mensagemErro): ?>
        <div class="rounded-2xl p-4 text-sm shadow-sm" style="background:rgba(248,113,113,0.12);color:#fca5a5;border:1px solid rgba(248,113,113,0.25)">
            <div class="flex gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" viewBox="0 0 20 20" fill="currentColor" style="color:#fca5a5">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
                <span><?= htmlspecialchars($mensagemErro) ?></span>
            </div>
        </div>
    <?php endif; ?>

    <!-- Métricas Financeiras Mobile -->
    <div class="grid grid-cols-3 gap-3">
        <div class="rounded-2xl p-3 text-center shadow-sm" style="background:rgba(52,211,153,0.10);border:1px solid rgba(52,211,153,0.2)">
            <span class="text-[0.62rem] font-bold uppercase tracking-wider" style="color:#6ee7b7">Total Pago</span>
            <div class="mt-1 text-sm font-extrabold" style="color:#6ee7b7"><?= $formatCurrency($totalPagoGeral) ?></div>
        </div>
        <div class="rounded-2xl p-3 text-center shadow-sm" style="background:rgba(251,191,36,0.10);border:1px solid rgba(251,191,36,0.2)">
            <span class="text-[0.62rem] font-bold uppercase tracking-wider" style="color:#fde68a">Em Aberto</span>
            <div class="mt-1 text-sm font-extrabold" style="color:#fde68a"><?= $formatCurrency($totalAbertoGeral) ?></div>
        </div>
        <div class="rounded-2xl p-3 text-center shadow-sm" style="background:rgba(248,113,113,0.10);border:1px solid rgba(248,113,113,0.2)">
            <span class="text-[0.62rem] font-bold uppercase tracking-wider" style="color:#fca5a5">Atrasado</span>
            <div class="mt-1 text-sm font-extrabold" style="color:#fca5a5"><?= $formatCurrency($totalAtrasadoGeral) ?></div>
        </div>
    </div>

    <!-- Chave PIX Rápida -->
    <div class="p-4 shadow-sm" style="background:rgba(255,255,255,0.055);border:1px solid rgba(255,255,255,0.09);border-radius:1rem;">
        <div class="flex items-center justify-between gap-4">
            <div class="min-w-0">
                <span class="text-[0.62rem] font-bold uppercase tracking-wider" style="color:#94a3b8">Contribuição via PIX</span>
                <p class="mt-0.5 text-xs font-semibold truncate" style="color:#f1f5f9">
                    Chave <?= htmlspecialchars($pixTipo) ?>: <strong class="font-mono text-erpGold"><?= htmlspecialchars($pixValor ?: 'Não configurada') ?></strong>
                </p>
                <?php if ($pixBeneficiario): ?>
                    <p class="text-[0.65rem] mt-0.5" style="color:#94a3b8"><?= htmlspecialchars($pixBeneficiario) ?></p>
                <?php endif; ?>
            </div>
            <?php if ($pixValor): ?>
                <button type="button" 
                        onclick="navigator.clipboard.writeText('<?= htmlspecialchars(addslashes($pixValor)) ?>'); alert('Chave PIX copiada!');"
                        class="shrink-0 rounded-xl px-3 py-1.5 text-xs font-bold active:scale-95 transition-transform"
                        style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.09);color:#f1f5f9">
                    Copiar
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Pilha Vertical de Cards de Mensalidades (100% de largura) -->
    <div class="space-y-4">
        <h3 class="text-sm font-bold uppercase tracking-widest" style="color:#f1f5f9">Minhas Mensalidades</h3>

        <?php if (empty($mesesTesourarias)): ?>
            <div class="p-8 text-center text-sm" style="background:rgba(255,255,255,0.055);border:1px solid rgba(255,255,255,0.09);border-style:dashed;border-radius:1rem;color:#94a3b8">
                Nenhum compromisso financeiro encontrado.
            </div>
        <?php endif; ?>

        <?php foreach ($mesesTesourarias as $mes): ?>
            <?php
            $isPago = ($mes['abertos'] === 0) && ($mes['total_pago'] > 0);
            $isAtrasado = ($mes['atrasados'] > 0);

            if ($isPago) {
                $cardBorderStyle = 'background:rgba(52,211,153,0.06);border:1px solid rgba(52,211,153,0.2);border-radius:1rem;';
                $badgeStyle = 'background:rgba(52,211,153,0.15);color:#6ee7b7;border-radius:999px;padding:0.2rem 0.55rem;font-size:0.65rem;font-weight:700;';
                $badgeLabel = 'Pago';
            } elseif ($isAtrasado) {
                $cardBorderStyle = 'background:rgba(248,113,113,0.06);border:1px solid rgba(248,113,113,0.2);border-radius:1rem;';
                $badgeStyle = 'background:rgba(248,113,113,0.12);color:#fca5a5;border-radius:999px;padding:0.2rem 0.55rem;font-size:0.65rem;font-weight:700;';
                $badgeLabel = 'Atrasado';
            } else {
                $cardBorderStyle = 'background:rgba(255,255,255,0.055);border:1px solid rgba(255,255,255,0.09);border-radius:1rem;';
                $badgeStyle = 'background:rgba(251,191,36,0.15);color:#fde68a;border-radius:999px;padding:0.2rem 0.55rem;font-size:0.65rem;font-weight:700;';
                $badgeLabel = 'Em aberto';
            }
            ?>
            <div class="p-4 shadow-sm space-y-3" style="<?= $cardBorderStyle ?>">
                
                <!-- Topo: Mês/Ano e Badge de Status -->
                <div class="flex items-center justify-between gap-3">
                    <h4 class="text-base font-bold" style="color:#f1f5f9"><?= htmlspecialchars($mes['rotulo']) ?></h4>
                    <span class="inline-flex items-center uppercase tracking-wider" style="<?= htmlspecialchars($badgeStyle) ?>">
                        <?= htmlspecialchars($badgeLabel) ?>
                    </span>
                </div>

                <!-- Detalhes do Valor -->
                <div class="flex items-baseline justify-between gap-3">
                    <div class="text-xs" style="color:#94a3b8">
                        <?php if ($mes['total_pago'] > 0): ?>
                            <span class="font-semibold" style="color:#6ee7b7">Pago: <?= $formatCurrency($mes['total_pago']) ?></span>
                        <?php endif; ?>
                        <?php if ($mes['total_aberto'] > 0): ?>
                            <?php if ($mes['total_pago'] > 0): ?> <span class="mx-1">·</span> <?php endif; ?>
                            <span class="font-semibold" style="color:#fde68a">Aberto: <?= $formatCurrency($mes['total_aberto']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="text-lg font-extrabold" style="color:#f1f5f9">
                        <?= $formatCurrency($mes['total_previsto']) ?>
                    </div>
                </div>

                <!-- Sub-itens (As parcelas em si) -->
                <div class="pt-2 space-y-1.5 text-xs" style="border-top:1px solid rgba(255,255,255,0.07);color:#94a3b8">
                    <?php foreach (($mes['itens'] ?? []) as $item): ?>
                        <div class="flex items-center justify-between gap-2">
                            <span class="truncate font-medium" style="color:#e2e8f0"><?= htmlspecialchars($item['obrigacao_titulo']) ?></span>
                            <span class="font-semibold" style="color:#e2e8f0"><?= $formatCurrency($item['valor_previsto']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Botão de Ação: Enviar Comprovante ( Progressive disclosure no card ) -->
                <?php if (!$isPago): ?>
                    <div class="pt-3" style="border-top:1px solid rgba(255,255,255,0.07)">
                        <?php
                        $mesNum = (int) substr($mes['chave'], 5, 2);
                        $anoNum = (int) substr($mes['chave'], 0, 4);
                        ?>
                        <button type="button"
                                @click="drawerMes = '<?= $mesNum ?>'; drawerAno = '<?= $anoNum ?>'; drawerValor = '<?= $mes['total_aberto'] ?>'; openUploadDrawer = true"
                                class="w-full inline-flex items-center justify-center gap-1.5 rounded-xl px-3 py-2 text-xs font-bold shadow-sm active:scale-[0.98] transition-transform"
                                style="background:#C9A227;color:#0f172a">
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
        <div class="absolute inset-x-0 bottom-0 max-h-[90dvh] rounded-t-3xl pb-safe shadow-2xl flex flex-col"
             style="background:rgba(255,255,255,0.055);border-top:1px solid rgba(255,255,255,0.09);"
             x-show="openUploadDrawer"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="translate-y-full"
             x-transition:enter-end="translate-y-0"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="translate-y-0"
             x-transition:leave-end="translate-y-full">
            
            <!-- Handle Visual -->
            <div class="mx-auto my-3 h-1.5 w-12 rounded-full" style="background:rgba(148,163,184,0.3)"></div>

            <!-- Cabeçalho -->
            <div class="flex items-center justify-between px-5 pb-3" style="border-bottom:1px solid rgba(255,255,255,0.09)">
                <h3 class="text-base font-bold" style="color:#f1f5f9">Enviar Comprovante</h3>
                <button type="button" @click="openUploadDrawer = false" class="rounded-full p-1.5 active:scale-90 transition-transform" style="background:rgba(255,255,255,0.03);color:#94a3b8">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>

            <!-- Formulário -->
            <form action="/pwa/obrigacoes/enviar-comprovante" method="POST" enctype="multipart/form-data" class="p-5 overflow-y-auto space-y-4">
                <?= \App\Core\Http\WebGuards::csrfField() ?>
                <input type="hidden" name="mes" x-model="drawerMes">
                <input type="hidden" name="ano" x-model="drawerAno">

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider" style="color:#94a3b8">Valor Pago (R$) *</label>
                    <input type="number" name="valor" step="0.01" min="0.01" required x-model="drawerValor"
                           class="mt-1 block w-full focus:outline-none focus:ring-1 focus:ring-white/20"
                           style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.10);color:#f1f5f9;border-radius:0.5rem;padding:0.6rem 0.875rem;">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider" style="color:#94a3b8">Arquivo do Comprovante *</label>
                    <div class="mt-1 flex justify-center rounded-xl px-6 py-5" style="border:2px dashed rgba(255,255,255,0.12);background:rgba(255,255,255,0.03)">
                        <div class="space-y-1 text-center">
                            <svg class="mx-auto h-10 w-10" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true" style="color:#94a3b8">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div class="flex text-xs" style="color:#94a3b8">
                                <label for="file-upload" class="relative cursor-pointer rounded-md font-semibold focus-within:outline-none hover:opacity-80" style="color:#f1f5f9;background:transparent">
                                    <span>Tirar foto ou selecionar</span>
                                    <input id="file-upload" name="comprovante" type="file" accept="image/*,application/pdf" class="sr-only" required>
                                </label>
                            </div>
                            <p class="text-[0.62rem]" style="color:#94a3b8">Imagem (JPEG, PNG) ou PDF de até 5MB</p>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider" style="color:#94a3b8">Observação (Opcional)</label>
                    <input type="text" name="descricao" placeholder="Ex: Mensalidade mais biblioteca"
                           class="mt-1 block w-full focus:outline-none focus:ring-1 focus:ring-white/20"
                           style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.10);color:#f1f5f9;border-radius:0.5rem;padding:0.6rem 0.875rem;">
                </div>

                <div class="pt-2">
                    <button type="submit"
                            class="w-full inline-flex items-center justify-center rounded-xl p-3 text-sm font-bold shadow-lg active:scale-[0.98] transition-transform"
                            style="background:#C9A227;color:#0f172a">
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
