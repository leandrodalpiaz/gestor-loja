<?php
declare(strict_types=1);

// #############################################################################
// LÓGICA DE NEGÓCIO E HELPERS
// #############################################################################

if (!isset($obreiroTesouraria) || !$obreiroTesouraria) {
    http_response_code(401);
    echo 'Acesso não autorizado.';
    exit;
}

$resumoObreiro = $resumoObreiro ?? [];
$obrigacoesObreiro = $obrigacoesObreiro ?? [];
$configuracaoFinanceira = (new \App\Models\ConfiguracaoLoja())->obter();

$formatCurrency = static fn ($value): string => 'R$ ' . number_format((float) $value, 2, ',', '.');
$formatDate = static fn (?string $date): string => $date ? (new DateTimeImmutable($date))->format('d/m/Y') : '-';

$mensalidadePadrao = (float) ($configuracaoFinanceira['mensalidade_valor_padrao'] ?? 150);
$pixTipo = (string) ($configuracaoFinanceira['pix_chave_tipo'] ?? 'CNPJ');
$pixValor = (string) ($configuracaoFinanceira['pix_chave_valor'] ?? '');
$pixBeneficiario = (string) ($configuracaoFinanceira['pix_beneficiario'] ?? '');
$hoje = date('Y-m-d');
$mesAtualChave = date('Y-m');
$anoPainel = 2026; // Ano fixo para o painel de ajuste

// Simulação de dados da biblioteca
$bibliotecaPorMes = [];
try {
    $dbTesouraria = \App\Config\Database::getConnection();
    $stmtBiblioteca = $dbTesouraria->prepare("
        SELECT mes_ref, valor_previsto
        FROM public.biblioteca_contribuintes_mensal
        WHERE ano_ref = :ano_ref AND obreiro_id = :obreiro_id
    ");
    $stmtBiblioteca->execute([
        'ano_ref' => $anoPainel,
        'obreiro_id' => (string) ($obreiroTesouraria['id'] ?? ''),
    ]);
    foreach ($stmtBiblioteca->fetchAll(PDO::FETCH_ASSOC) as $linhaBiblioteca) {
        $bibliotecaPorMes[(int) ($linhaBiblioteca['mes_ref'] ?? 0)] = (float) ($linhaBiblioteca['valor_previsto'] ?? 0);
    }
} catch (\Throwable $e) {
    // Silencia o erro se a tabela não existir ou houver outro problema
    $bibliotecaPorMes = [];
}

$nomesMeses = [1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril', 5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'];
$mesesTesourarias = [];

// Processamento de obrigações
foreach ($obrigacoesObreiro as $obrigacao) {
    foreach (($obrigacao['parcelas'] ?? []) as $parcela) {
        $parcela['obrigacao_titulo'] = (string) ($parcela['obrigacao_titulo'] ?? $obrigacao['titulo'] ?? 'Obrigacao');
        $parcela['tipo_obrigacao'] = (string) ($parcela['tipo_obrigacao'] ?? $obrigacao['tipo_obrigacao'] ?? 'outra');
        $mesCompetencia = (int) ($parcela['competencia_mes'] ?? 0);
        $anoCompetencia = (int) ($parcela['competencia_ano'] ?? 0);

        if ($mesCompetencia > 0 && $anoCompetencia > 0) {
            $chaveMes = sprintf('%04d-%02d', $anoCompetencia, $mesCompetencia);
            if (!isset($mesesTesourarias[$chaveMes])) {
                $mesesTesourarias[$chaveMes] = [
                    'chave' => $chaveMes,
                    'rotulo' => ($nomesMeses[$mesCompetencia] ?? 'Mês') . ' ' . $anoCompetencia,
                    'total_pago' => 0.0, 'total_previsto' => 0.0, 'total_aberto' => 0.0,
                    'pagos' => 0, 'abertos' => 0, 'atrasados' => 0, 'itens' => [],
                ];
            }
            $mesesTesourarias[$chaveMes]['total_previsto'] += (float) ($parcela['valor_previsto'] ?? 0);
            if (!empty($parcela['quitado_na_exibicao'])) {
                $mesesTesourarias[$chaveMes]['total_pago'] += (float) ($parcela['valor_previsto'] ?? 0);
                $mesesTesourarias[$chaveMes]['pagos']++;
            } else {
                $mesesTesourarias[$chaveMes]['total_aberto'] += (float) ($parcela['valor_previsto'] ?? 0);
                $mesesTesourarias[$chaveMes]['abertos']++;
                if (!empty($parcela['em_atraso'])) $mesesTesourarias[$chaveMes]['atrasados']++;
            }
            $mesesTesourarias[$chaveMes]['itens'][] = $parcela;
        }
    }
}

uasort($mesesTesourarias, static fn (array $a, array $b): int => strcmp((string) ($a['chave'] ?? ''), (string) ($b['chave'] ?? '')));

$parcelasPagas = [];
$parcelasAguardandoConfirmacao = [];
$parcelasProgramadas = [];
$parcelasAtrasadas = [];

foreach ($mesesTesourarias as $mes) {
    foreach (($mes['itens'] ?? []) as $p) {
        if (!empty($p['quitado_na_exibicao'])) $parcelasPagas[] = $p;
        elseif (!empty($p['em_atraso'])) $parcelasAtrasadas[] = $p;
        elseif (!empty($p['vencimento']) && $p['vencimento'] <= $hoje) $parcelasAguardandoConfirmacao[] = $p;
        else $parcelasProgramadas[] = $p;
    }
}

$sortFn = static fn (array $a, array $b): int => strcmp((string) ($a['vencimento'] ?? ''), (string) ($b['vencimento'] ?? ''));
usort($parcelasPagas, static fn (array $a, array $b): int => strcmp((string) ($b['pago_em'] ?? $b['vencimento']), (string) ($a['pago_em'] ?? $a['vencimento'])));
usort($parcelasAguardandoConfirmacao, $sortFn);
usort($parcelasProgramadas, $sortFn);
usort($parcelasAtrasadas, $sortFn);

$nomeObreiro = (string) ($obreiroTesouraria['nome_historico'] ?? $obreiroTesouraria['nome'] ?? 'Irmão');
$totalPago = array_reduce($parcelasPagas, static fn ($c, $p) => $c + ($p['valor_previsto'] ?? 0), 0.0);
$totalAberto = array_reduce(array_merge($parcelasAguardandoConfirmacao, $parcelasProgramadas, $parcelasAtrasadas), static fn ($c, $p) => $c + ($p['valor_previsto'] ?? 0), 0.0);
$totalAtrasado = array_reduce($parcelasAtrasadas, static fn ($c, $p) => $c + ($p['valor_previsto'] ?? 0), 0.0);
$proximaObrigacao = $parcelasAguardandoConfirmacao[0] ?? $parcelasProgramadas[0] ?? null;
$alertaBiblioteca = null;
$limiteAlertaBiblioteca = (new DateTimeImmutable('today'))->modify('+30 days')->format('Y-m-d');
foreach (array_merge($parcelasAguardandoConfirmacao, $parcelasProgramadas) as $parcelaAlerta) {
    $tipoAlerta = strtolower((string) ($parcelaAlerta['tipo_obrigacao'] ?? ''));
    $tituloAlerta = strtolower((string) ($parcelaAlerta['obrigacao_titulo'] ?? ''));
    $vencimentoAlerta = (string) ($parcelaAlerta['vencimento'] ?? '');
    if (($tipoAlerta === 'biblioteca' || str_contains($tituloAlerta, 'biblioteca')) && $vencimentoAlerta !== '' && $vencimentoAlerta <= $limiteAlertaBiblioteca) {
        $alertaBiblioteca = $parcelaAlerta;
        break;
    }
}
$totalEsperadoBiblioteca = $alertaBiblioteca ? $mensalidadePadrao + (float) ($alertaBiblioteca['valor_previsto'] ?? 0) : 0.0;

// #############################################################################
// CONFIGURAÇÃO DO APP SHELL
// #############################################################################

$appShellEyebrow = 'Tesouraria';
$appShellTitle = 'Minhas Obrigações';
$appShellDescription = 'Painel financeiro pessoal do obreiro ' . htmlspecialchars($nomeObreiro);
$appShellActiveHref = '/minhas-obrigacoes';
$appShellActions = [['label' => 'Voltar ao Painel', 'href' => '/dashboard']];

require __DIR__ . '/partials/erp_shell_open.php';

?>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-6 xl:gap-8">
    <!-- Métricas Principais -->
    <div class="metric-card bg-green-50 border-green-200 dark:bg-green-900/20 dark:border-green-800">
        <div class="metric-label">Total Pago</div>
        <div class="metric-value text-green-700 dark:text-green-300"><?= $formatCurrency($totalPago) ?></div>
        <div class="metric-meta"><?= count($parcelasPagas) ?> compromissos quitados</div>
    </div>
    <div class="metric-card bg-yellow-50 border-yellow-200 dark:bg-yellow-900/20 dark:border-yellow-800">
        <div class="metric-label">Próxima Obrigação</div>
        <div class="metric-value text-yellow-700 dark:text-yellow-300"><?= $formatCurrency($proximaObrigacao['valor_previsto'] ?? 0) ?></div>
        <div class="metric-meta"><?= $proximaObrigacao ? htmlspecialchars($proximaObrigacao['obrigacao_titulo']) . ' em ' . $formatDate($proximaObrigacao['vencimento']) : 'Nenhuma obrigação futura' ?></div>
    </div>
    <div class="metric-card bg-blue-50 border-blue-200 dark:bg-blue-900/20 dark:border-blue-800">
        <div class="metric-label">Total em Aberto</div>
        <div class="metric-value text-blue-700 dark:text-blue-300"><?= $formatCurrency($totalAberto) ?></div>
        <div class="metric-meta"><?= count($parcelasProgramadas) + count($parcelasAguardandoConfirmacao) ?> compromissos programados</div>
    </div>
    <div class="metric-card bg-red-50 border-red-200 dark:bg-red-900/20 dark:border-red-800">
        <div class="metric-label">Em Atraso</div>
        <div class="metric-value text-red-700 dark:text-red-300"><?= $formatCurrency($totalAtrasado) ?></div>
        <div class="metric-meta"><?= count($parcelasAtrasadas) ?> pendências</div>
    </div>
</div>

<?php if ($alertaBiblioteca): ?>
<div class="card mt-6 xl:mt-8 border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-900/20">
    <div class="card-body flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h3 class="font-semibold text-amber-900 dark:text-amber-100">Alerta da Biblioteca</h3>
            <p class="text-sm text-amber-800 dark:text-amber-200">
                No vencimento de <?= $formatDate($alertaBiblioteca['vencimento'] ?? null) ?>, acrescente <?= $formatCurrency($alertaBiblioteca['valor_previsto'] ?? 0) ?> &agrave; mensalidade.
                Total esperado: <?= $formatCurrency($totalEsperadoBiblioteca) ?>.
            </p>
        </div>
        <span class="badge-status warning">Aviso 30 dias</span>
    </div>
</div>
<?php endif; ?>

<!-- Chave PIX -->
<div class="card mt-6 xl:mt-8">
    <div class="card-body flex flex-wrap items-center justify-between gap-4">
        <div>
            <h3 class="font-semibold text-gray-800 dark:text-gray-200">Pagamento via PIX</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Chave <?= htmlspecialchars($pixTipo) ?>: <strong class="font-mono"><?= htmlspecialchars($pixValor ?: 'Não informada') ?></strong>
                (<?= htmlspecialchars($pixBeneficiario) ?>)
            </p>
        </div>
        <button type="button" class="btn btn-secondary" onclick="navigator.clipboard.writeText('<?= htmlspecialchars(addslashes($pixValor)) ?>')">
            Copiar Chave PIX
        </button>
    </div>
</div>

<!-- Painel de Obrigações Mensais -->
<div class="card mt-6 xl:mt-8">
    <div class="card-header">
        <h2 class="card-title">Painel Mensal de Obrigações</h2>
        <p class="card-subtitle">Detalhes de cada competência, incluindo o que foi pago e o que está pendente.</p>
    </div>
    <div class="card-body space-y-4">
        <?php if (empty($mesesTesourarias)): ?>
            <div class="text-center py-10 text-gray-500">Nenhuma obrigação encontrada para este período.</div>
        <?php endif; ?>

        <?php foreach ($mesesTesourarias as $mes): ?>
            <?php
            $mesPago = ($mes['abertos'] ?? 0) === 0 && ($mes['total_pago'] ?? 0) > 0;
            $mesAtrasado = ($mes['atrasados'] ?? 0) > 0;
            $cardMesClass = 'bg-gray-50 dark:bg-gray-800/50 border-gray-200 dark:border-gray-700';
            if ($mesPago) $cardMesClass = 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800';
            elseif ($mesAtrasado) $cardMesClass = 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800';
            ?>
            <details class="rounded-lg border <?= $cardMesClass ?>" <?= $mes['chave'] === $mesAtualChave ? 'open' : '' ?>>
                <summary class="p-4 cursor-pointer flex justify-between items-center">
                    <div>
                        <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-200"><?= htmlspecialchars($mes['rotulo']) ?></h3>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            <span class="text-green-600 dark:text-green-400">Pago: <?= $formatCurrency($mes['total_pago']) ?></span> |
                            <span class="text-yellow-600 dark:text-yellow-400">Aberto: <?= $formatCurrency($mes['total_aberto']) ?></span>
                        </div>
                    </div>
                    <div class="text-sm font-semibold"><?= $mes['pagos'] ?> pagos | <?= $mes['abertos'] ?> abertos</div>
                </summary>
                <div class="px-4 pb-4 border-t border-[inherit]">
                    <div class="divide-y divide-[inherit]">
                        <?php foreach (($mes['itens'] ?? []) as $parcela): ?>
                            <div class="py-3">
                                <div class="flex flex-wrap justify-between items-start gap-2">
                                    <div>
                                        <div class="font-semibold text-gray-800 dark:text-gray-100"><?= htmlspecialchars($parcela['obrigacao_titulo']) ?></div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            Vencimento: <?= $formatDate($parcela['vencimento']) ?>
                                            <?php if (!empty($parcela['pago_em'])): ?>
                                                | Pago em: <?= $formatDate($parcela['pago_em']) ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-semibold text-lg"><?= $formatCurrency($parcela['valor_previsto'] ?? 0) ?></div>
                                        <?php
                                        $statusClass = 'badge-status-warning';
                                        $statusLabel = 'Programada';
                                        if (!empty($parcela['quitado_na_exibicao'])) {
                                            $statusClass = 'badge-status-success';
                                            $statusLabel = 'Pago';
                                        } elseif (!empty($parcela['em_atraso'])) {
                                            $statusClass = 'badge-status-danger';
                                            $statusLabel = 'Atrasado';
                                        }
                                        ?>
                                        <?php
                                        $label = $statusLabel;
                                        // Mapeando a classe de status para o type do componente (ex: badge-status-warning -> warning)
                                        $type = str_replace('badge-status-', '', $statusClass);
                                        require __DIR__ . '/components/badge-status.php';
                                        ?>
                                    </div>
                                </div>
                                <?php if (!empty($parcela['descricao_status'])): ?>
                                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 italic">"<?= htmlspecialchars($parcela['descricao_status']) ?>"</p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </details>
        <?php endforeach; ?>
    </div>
</div>

<?php require __DIR__ . '/partials/erp_shell_close.php'; ?>



