<?php
declare(strict_types=1);

// #############################################################################
// LÓGICA DE NEGÓCIO E CONSULTA DE DADOS
// #############################################################################

// Inicialização de variáveis e helpers
$db = \App\Config\Database::getConnection();
$hoje = new DateTimeImmutable('today');
$mesAtual = (int) $hoje->format('n');
$anoAtual = (int) $hoje->format('Y');
$competenciaAtual = sprintf('%02d/%04d', $mesAtual, $anoAtual);
$meses = [1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril', 5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'];
$tituloMesAtual = ($meses[$mesAtual] ?? 'Mês') . ' ' . $anoAtual;
$formatCurrency = static fn($value): string => 'R$ ' . number_format((float) $value, 2, ',', '.');
$primeiroDiaUtilMesSeguinte = static function (int $mes, int $ano): DateTimeImmutable {
    $base = (new DateTimeImmutable(sprintf('%04d-%02d-01', $ano, $mes)))->modify('first day of next month');
    while ((int) $base->format('N') >= 6) {
        $base = $base->modify('+1 day');
    }
    return $base;
};
$classificarParcela = static function (array $item) use ($hoje, $primeiroDiaUtilMesSeguinte): string {
    if (($item['status'] ?? '') === 'pago') {
        return 'pago';
    }
    $competenciaMes = (int) ($item['competencia_mes'] ?? $hoje->format('n'));
    $competenciaAno = (int) ($item['competencia_ano'] ?? $hoje->format('Y'));
    $limiteAtraso = $primeiroDiaUtilMesSeguinte($competenciaMes, $competenciaAno);
    $competenciaInicio = new DateTimeImmutable(sprintf('%04d-%02d-01', $competenciaAno, $competenciaMes));
    if ($hoje >= $limiteAtraso) {
        return 'atrasado';
    }
    if ($competenciaInicio > $hoje) {
        return 'futuro';
    }
    return 'a_vencer';
};
$labelParcela = static fn(string $status): string => match ($status) {
    'pago' => 'Pago',
    'atrasado' => 'Em atraso',
    'futuro' => 'Futuro',
    default => 'A vencer',
};
$badgeParcela = static fn(string $status): string => match ($status) {
    'pago' => 'badge-success',
    'atrasado' => 'badge-danger',
    'futuro' => 'badge-secondary',
    default => 'badge-info',
};
$selectedObreiroId = (string) ($_GET['obreiro_id'] ?? '');
$mensagemSucesso = $_SESSION['mensagem_sucesso'] ?? null;
$mensagemErro = $_SESSION['mensagem_erro'] ?? null;
unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']);

// Carregar configura??es da loja pelo modelo oficial, sem depender de tabela legada.
$configuracaoLoja = is_array($configuracaoLoja ?? null) ? $configuracaoLoja : (new \App\Models\ConfiguracaoLoja())->obter();
$mensalidadePadrao = (float) ($configuracaoLoja['mensalidade_valor_padrao'] ?? 150);
$bibliotecaPadrao = (float) ($configuracaoLoja['contribuicao_biblioteca_valor_padrao'] ?? 40);
$salarioMinimoPadrao = (float) ($configuracaoLoja['salario_minimo_referencia'] ?? 1621);
$pixTipo = (string) ($configuracaoLoja['pix_chave_tipo'] ?? 'CNPJ');
$pixValor = (string) ($configuracaoLoja['pix_chave_valor'] ?? '');

// Carregar Obreiros e Categorias
$obreirosPainel = $db->query("SELECT id, nome FROM obreiros WHERE ativo = TRUE ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
$categoriasEntrada = $db->query("SELECT id, nome FROM categorias_financeiras WHERE tipo = 'entrada' ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

// Buscar resumo do histórico financeiro
$stmtHistorico = $db->prepare("SELECT COALESCE(SUM(valor), 0) AS total FROM lancamentos_financeiros WHERE tipo = 'entrada' AND (ano_ref < :ano OR (ano_ref = :ano AND mes_ref < :mes))");
$stmtHistorico->execute(['ano' => $anoAtual, 'mes' => $mesAtual]);
$totalPassado = (float) ($stmtHistorico->fetchColumn() ?: 0);

// Buscar todas as parcelas do mês atual para todos os obreiros ativos
$stmtMesAtual = $db->prepare("
    SELECT o.id AS obreiro_id, o.nome, p.id AS parcela_id, f.titulo, f.tipo_obrigacao, p.valor_previsto, p.vencimento, p.status, p.pago_em
    FROM obrigacao_financeira_parcelas p
    JOIN obrigacoes_financeiras f ON f.id = p.obrigacao_id
    JOIN obreiros o ON o.id = f.obreiro_id
    WHERE p.competencia_mes = :mes AND p.competencia_ano = :ano AND o.ativo = TRUE
");
$stmtMesAtual->execute(['mes' => $mesAtual, 'ano' => $anoAtual]);
$linhasMesAtual = $stmtMesAtual->fetchAll(PDO::FETCH_ASSOC);

// Processar dados para o painel geral
$painelGeral = [];
foreach ($obreirosPainel as $obreiro) {
    $painelGeral[$obreiro['id']] = ['obreiro_id' => $obreiro['id'], 'nome' => $obreiro['nome'], 'pago' => 0.0, 'aberto' => 0.0, 'atrasado' => 0.0, 'vencidos' => 0, 'itens' => []];
}

foreach ($linhasMesAtual as $linha) {
    $obreiroId = $linha['obreiro_id'];
    if (!isset($painelGeral[$obreiroId])) continue;
    $valor = (float) $linha['valor_previsto'];
    $estaPago = $linha['status'] === 'pago';
    $statusTemporal = $classificarParcela($linha);
    $estaVencido = !$estaPago && $statusTemporal === 'atrasado';
    if ($estaPago) $painelGeral[$obreiroId]['pago'] += $valor;
    else {
        $painelGeral[$obreiroId]['aberto'] += $valor;
        if ($estaVencido) {
            $painelGeral[$obreiroId]['atrasado'] += $valor;
            $painelGeral[$obreiroId]['vencidos']++;
        }
    }
    $painelGeral[$obreiroId]['itens'][] = $linha + ['esta_vencido' => $estaVencido, 'status_temporal' => $statusTemporal];
}

// Adicionar mensalidades faltantes
foreach ($painelGeral as &$registro) {
    $temMensalidade = false;
    foreach ($registro['itens'] as $item) {
        if (strtolower($item['tipo_obrigacao']) === 'mensalidade') {
            $temMensalidade = true;
            break;
        }
    }
    if (!$temMensalidade) {
        $vencimentoPadrao = sprintf('%04d-%02d-10', $anoAtual, $mesAtual);
        $statusTemporalPadrao = $classificarParcela([
            'status' => 'pendente',
            'competencia_mes' => $mesAtual,
            'competencia_ano' => $anoAtual,
        ]);
        $estaVencidoPadrao = $statusTemporalPadrao === 'atrasado';
        $registro['aberto'] += $mensalidadePadrao;
        if ($estaVencidoPadrao) {
            $registro['atrasado'] += $mensalidadePadrao;
            $registro['vencidos']++;
        }
        $registro['itens'][] = ['parcela_id' => 0, 'titulo' => 'Contribuição mensal da Loja', 'tipo_obrigacao' => 'mensalidade', 'valor_previsto' => $mensalidadePadrao, 'vencimento' => $vencimentoPadrao, 'status' => 'pendente', 'pago_em' => null, 'competencia_mes' => $mesAtual, 'competencia_ano' => $anoAtual, 'esta_vencido' => $estaVencidoPadrao, 'status_temporal' => $statusTemporalPadrao];
    }
}
unset($registro);

// Calcular totais do mês
$recebidoMes = array_sum(array_column($painelGeral, 'pago'));
$faltanteMes = array_sum(array_column($painelGeral, 'aberto'));
$irmaosAPrumo = count(array_filter($painelGeral, fn($r) => $r['aberto'] <= 0.01 && $r['pago'] > 0));

// Ordenar painel
uasort($painelGeral, function ($a, $b) {
    $cmpAtraso = ($b['vencidos'] <=> $a['vencidos']);
    if ($cmpAtraso !== 0) return $cmpAtraso;
    $cmpAberto = ($b['aberto'] <=> $a['aberto']);
    if ($cmpAberto !== 0) return $cmpAberto;
    return strcmp($a['nome'], $b['nome']);
});

// Detalhes do obreiro selecionado
$registroSelecionado = $selectedObreiroId ? ($painelGeral[$selectedObreiroId] ?? null) : null;
$selectedObreiroNome = $registroSelecionado['nome'] ?? 'Selecione um obreiro';
$itensSelecionadosPago = $registroSelecionado ? array_values(array_filter($registroSelecionado['itens'], fn($item) => $item['status'] === 'pago')) : [];
$itensSelecionadosAberto = $registroSelecionado ? array_values(array_filter($registroSelecionado['itens'], fn($item) => $item['status'] !== 'pago')) : [];
$valorProximaObrigacao = $registroSelecionado ? array_sum(array_map(
    static fn(array $item): float => in_array((string) ($item['status_temporal'] ?? ''), ['atrasado', 'a_vencer'], true) ? (float) ($item['valor_previsto'] ?? 0) : 0.0,
    $itensSelecionadosAberto
)) : 0.0;


// #############################################################################
// CONFIGURAÇÃO DO APP SHELL
// #############################################################################

$appShellEyebrow = 'Tesouraria';
$appShellTitle = 'Acompanhamento Financeiro';
$appShellDescription = 'Panorama geral do passado e do mês vigente, com leitura de quem está a prumo e quem tem pendências.';
$appShellActiveHref = '/tesouraria/obrigacoes';

require __DIR__ . '/partials/erp_shell_open.php';

?>

<?php require __DIR__ . '/partials/erp_tesouraria_topbar.php'; ?>

<?php if ($mensagemSucesso): ?>
<div class="alert alert-success mb-6"><?= htmlspecialchars($mensagemSucesso) ?></div>
<?php endif; ?>
<?php if ($mensagemErro): ?>
<div class="alert alert-danger mb-6"><?= htmlspecialchars($mensagemErro) ?></div>
<?php endif; ?>

<!-- Métricas Gerais -->
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
    <div class="card-metric"><p class="card-metric-label">Resumo do Passado</p><p class="card-metric-value"><?= $formatCurrency($totalPassado) ?></p><p class="card-metric-sublabel">Entradas antes de <?= htmlspecialchars($tituloMesAtual) ?></p></div>
    <div class="card-metric"><p class="card-metric-label">Pago no Mês</p><p class="card-metric-value text-emerald-400"><?= $formatCurrency($recebidoMes) ?></p><p class="card-metric-sublabel">Competência: <?= htmlspecialchars($competenciaAtual) ?></p></div>
    <div class="card-metric"><p class="card-metric-label">Falta no Mês</p><p class="card-metric-value text-rose-400"><?= $formatCurrency($faltanteMes) ?></p><p class="card-metric-sublabel">Obrigações em aberto do mês</p></div>
    <div class="card-metric"><p class="card-metric-label">A Prumo</p><p class="card-metric-value text-sky-400"><?= $irmaosAPrumo ?></p><p class="card-metric-sublabel">Obreiros sem pendências no mês</p></div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-[420px_minmax(0,1fr)] gap-8">
    <!-- Coluna da Esquerda: Lista de Obreiros e Parâmetros -->
    <aside class="space-y-8">
        <div class="card">
            <div class="card-header"><h2 class="card-title">Panorama dos Obreiros</h2></div>
            <div class="card-body">
                <form method="get" action="/tesouraria/obrigacoes">
                    <label for="select-obreiro" class="form-label">Selecionar obreiro</label>
                    <select name="obreiro_id" id="select-obreiro" class="form-select" onchange="this.form.submit()">
                        <option value="">Visão geral</option>
                        <?php foreach ($painelGeral as $reg): ?>
                            <option value="<?= htmlspecialchars($reg['obreiro_id']) ?>" <?= $selectedObreiroId === $reg['obreiro_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($reg['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            <div class="p-4 border-t border-erp-border/50">
                <details class="group" <?= $selectedObreiroId !== '' ? 'open' : '' ?>>
                    <summary class="flex justify-between items-center font-semibold cursor-pointer text-erp-gold hover:text-white transition-colors text-sm">
                        <span>Visualizar Lista de Obreiros</span>
                        <span class="transition-transform group-open:rotate-180">
                            <svg fill="none" height="20" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="20"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </span>
                    </summary>
                    <div class="mt-5 max-h-[500px] overflow-y-auto pr-2 space-y-3 custom-scrollbar">
                        <?php foreach ($painelGeral as $reg):
                            $isSelected = $selectedObreiroId === $reg['obreiro_id'];
                            $statusClass = 'default';
                            if (($reg['vencidos'] ?? 0) > 0) $statusClass = 'danger';
                            elseif (($reg['aberto'] ?? 0) <= 0.01 && ($reg['pago'] ?? 0) > 0) $statusClass = 'success';
                        ?>
                            <a href="/tesouraria/obrigacoes?obreiro_id=<?= urlencode($reg['obreiro_id']) ?>#detalhe-individual" 
                               class="flex items-center justify-between p-3 rounded-xl border <?= $isSelected ? 'border-erp-gold bg-erp-surface-2' : 'border-erp-border/30 bg-erp-surface/50 hover:border-erp-gold/30' ?> transition-all">
                                <div>
                                    <p class="font-semibold text-white mb-0.5"><?= htmlspecialchars($reg['nome']) ?></p>
                                    <p class="text-xs text-erp-muted">
                                        <span class="text-emerald-400"><?= $formatCurrency($reg['pago']) ?> pago</span> • <span class="<?= $statusClass === 'danger' ? 'text-rose-400 font-bold' : '' ?>"><?= $formatCurrency($reg['aberto']) ?> aberto</span>
                                    </p>
                                </div>
                                <?php if ($statusClass === 'danger'): ?><span class="badge badge-danger">Atraso</span><?php endif; ?>
                                <?php if ($statusClass === 'success'): ?><span class="badge badge-success">A Prumo</span><?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </details>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2 class="card-title">Parâmetros da Loja</h2></div>
            <div class="card-body space-y-2">
                <div class="list-item-param"><span class="text-gray-500">Mensalidade</span><span class="font-semibold"><?= $formatCurrency($mensalidadePadrao) ?></span></div>
                <div class="list-item-param"><span class="text-gray-500">Biblioteca</span><span class="font-semibold"><?= $formatCurrency($bibliotecaPadrao) ?></span></div>
                <div class="list-item-param"><span class="text-gray-500">PIX <?= htmlspecialchars($pixTipo) ?></span><span class="font-semibold"><?= htmlspecialchars($pixValor ?: 'N/D') ?></span></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Programação Biblioteca</h2>
                <p class="card-description">Escala obrigatória anual da Loja Renascença: mensalidade + <?= $formatCurrency($bibliotecaPadrao) ?> no mês designado.</p>
            </div>
            <form method="post" action="/tesouraria/obrigacoes/biblioteca/programar-renascenca" class="card-body space-y-3">
                <label for="ano_biblioteca" class="form-label">Ano de referencia</label>
                <div class="flex gap-2">
                    <input id="ano_biblioteca" name="ano_ref" type="number" min="2020" value="<?= (int) $anoAtual ?>" class="form-input">
                    <button class="btn btn-primary" type="submit">Programar ano</button>
                </div>
                <p class="text-xs text-gray-500">Ao programar, cada irmao designado passa a ver o alerta da Biblioteca 30 dias antes do vencimento.</p>
            </form>
        </div>
    </aside>

    <!-- Coluna da Direita: Detalhes e Formulários -->
    <main class="space-y-8">
        <?php if ($registroSelecionado): ?>
            <section id="detalhe-individual" class="card">
                <div class="card-header"><h2 class="card-title">Detalhe de <?= htmlspecialchars($selectedObreiroNome) ?></h2></div>
                <div class="card-body">
                    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 mb-6">
                        <div class="card-metric-simple"><p class="card-metric-label">Já Pago</p><p class="card-metric-value text-emerald-400"><?= $formatCurrency($registroSelecionado['pago']) ?></p></div>
                        <div class="card-metric-simple"><p class="card-metric-label">Falta no Mês</p><p class="card-metric-value text-rose-400"><?= $formatCurrency($registroSelecionado['aberto']) ?></p></div>
                        <div class="card-metric-simple"><p class="card-metric-label">Próxima obrigação</p><p class="card-metric-value text-sky-400"><?= $formatCurrency($valorProximaObrigacao) ?></p><p class="card-metric-sublabel">Soma atrasados + itens a vencer</p></div>
                        <?php if (($registroSelecionado['vencidos'] ?? 0) > 0): ?>
                            <div class="card-metric-simple border-rose-500/20 bg-rose-500/10"><p class="card-metric-label text-rose-400">Atenção</p><p class="card-metric-value text-rose-300"><?= (int) $registroSelecionado['vencidos'] ?> pendência(s)</p></div>
                        <?php else: ?>
                            <div class="card-metric-simple border-emerald-500/20 bg-emerald-500/10"><p class="font-semibold text-emerald-400 text-center py-4">A Prumo com a Tesouraria</p></div>
                        <?php endif; ?>
                    </div>

                    <div class="grid gap-6 xl:grid-cols-2">
                        <div class="space-y-3">
                            <h4 class="font-semibold text-gray-800 dark:text-gray-100">Pago no Mês</h4>
                            <?php if (empty($itensSelecionadosPago)): ?><p class="text-sm text-gray-500">Nenhum pagamento confirmado.</p><?php endif; ?>
                            <?php foreach ($itensSelecionadosPago as $item): ?>
                                <div class="list-item-detail">
                                    <div>
                                        <p class="font-medium"><?= htmlspecialchars($item['titulo']) ?></p>
                                        <p class="text-xs text-gray-500">Pago em <?= htmlspecialchars(date('d/m/Y', strtotime($item['pago_em']))) ?></p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-semibold text-emerald-400"><?= $formatCurrency($item['valor_previsto']) ?></p>
                                        <a href="/tesouraria/obrigacoes/parcela/recibo?id=<?= (int) $item['parcela_id'] ?>" target="_blank" class="text-xs text-gray-500 hover:underline">Recibo</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="space-y-3">
                            <h4 class="font-semibold text-gray-800 dark:text-gray-100">Aberto no Mês</h4>
                            <?php if (empty($itensSelecionadosAberto)): ?><p class="text-sm text-gray-500">Nenhuma pendência no mês.</p><?php endif; ?>
                            <?php foreach ($itensSelecionadosAberto as $item): ?>
                                <?php $statusTemporalItem = (string) ($item['status_temporal'] ?? $classificarParcela($item)); ?>
                                <div class="list-item-detail flex-col items-stretch gap-2 <?= !empty($item['esta_vencido']) ? 'border-red-300 dark:border-red-600' : '' ?>">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <p class="font-medium"><?= htmlspecialchars($item['titulo']) ?></p>
                                            <p class="text-xs text-gray-500">Vence em <?= htmlspecialchars(date('d/m/Y', strtotime($item['vencimento']))) ?> · <?= htmlspecialchars((string) ($item['tipo_obrigacao'] ?? 'obrigacao')) ?></p>
                                            <span class="badge <?= $badgeParcela($statusTemporalItem) ?> mt-2"><?= htmlspecialchars($labelParcela($statusTemporalItem)) ?></span>
                                        </div>
                                        <p class="font-semibold text-rose-400"><?= $formatCurrency($item['valor_previsto']) ?></p>
                                    </div>
                                    <?php if ((int) ($item['parcela_id'] ?? 0) > 0): ?>
                                        <form action="/tesouraria/obrigacoes/parcela/quitar" method="post" class="flex flex-wrap items-center gap-2 pt-2 border-t border-gray-200 dark:border-gray-700">
                                            <input type="hidden" name="parcela_id" value="<?= (int) $item['parcela_id'] ?>">
                                            <input type="hidden" name="obreiro_id" value="<?= htmlspecialchars($selectedObreiroId) ?>">
                                            <input type="date" name="pago_em" value="<?= date('Y-m-d') ?>" class="form-input-sm flex-grow">
                                            <input type="number" name="valor_pago" step="0.01" value="<?= htmlspecialchars(number_format((float)$item['valor_previsto'], 2, '.', '')) ?>" class="form-input-sm w-28">
                                            <button type="submit" class="btn btn-success btn-sm">Quitar</button>
                                        </form>
                                        <details class="pt-2 text-sm">
                                            <summary class="cursor-pointer font-semibold text-gray-600 dark:text-gray-300">Editar parcela</summary>
                                            <form action="/tesouraria/obrigacoes/parcela/atualizar" method="post" class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-3">
                                                <input type="hidden" name="parcela_id" value="<?= (int) $item['parcela_id'] ?>">
                                                <input type="hidden" name="obreiro_id" value="<?= htmlspecialchars($selectedObreiroId) ?>">
                                                <input type="number" name="valor_previsto" step="0.01" min="0.01" value="<?= htmlspecialchars(number_format((float)$item['valor_previsto'], 2, '.', '')) ?>" class="form-input-sm">
                                                <input type="date" name="vencimento" value="<?= htmlspecialchars((string) ($item['vencimento'] ?? '')) ?>" class="form-input-sm">
                                                <button type="submit" class="btn btn-secondary btn-sm">Salvar edição</button>
                                            </form>
                                        </details>
                                    <?php else: ?>
                                        <div class="alert alert-info text-xs">Mensalidade prevista automaticamente. Gere as mensalidades do ano para editar/quitar esta parcela.</div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <section class="card">
            <div class="card-header">
                <h2 class="card-title">Nova Obrigação Financeira</h2>
                <p class="card-description">Lançamento para registrar mensalidades, contribuições e joias.</p>
            </div>
            <form action="/tesouraria/obrigacoes/criar" method="post" class="card-body space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div><label for="obreiro_id_form" class="form-label">Obreiro *</label><select name="obreiro_id" id="obreiro_id_form" class="form-select" required><option value="">Selecione...</option><?php foreach ($obreirosPainel as $ob): ?><option value="<?= htmlspecialchars($ob['id']) ?>" <?= $selectedObreiroId === $ob['id'] ? 'selected' : '' ?>><?= htmlspecialchars($ob['nome']) ?></option><?php endforeach; ?></select></div>
                    <div><label for="tipo_obrigacao" class="form-label">Tipo *</label><select name="tipo_obrigacao" id="tipo_obrigacao" class="form-select"><option value="mensalidade">Contribuição Mensal</option><option value="biblioteca">Biblioteca</option><option value="joia">Jóia</option><option value="doacao">Doação</option><option value="outra">Outra</option></select></div>
                </div>
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div><label for="titulo_obrigacao" class="form-label">Título da Obrigação *</label><input type="text" name="titulo" id="titulo_obrigacao" class="form-input" required></div>
                        <div><label for="recorrencia" class="form-label">Forma de Cobrança</label><select name="recorrencia" id="recorrencia" class="form-select"><option value="mensal">Mensal</option><option value="anual">Anual</option><option value="parcelado">Parcelado</option><option value="avulsa">Avulsa</option></select></div>
                        <div><label for="valor_base" class="form-label">Valor Total (R$)</label><input type="number" name="valor_base" id="valor_base" step="0.01" min="0.01" class="form-input" required></div>
                        <div><label for="parcelas_total" class="form-label">Nº de Parcelas</label><input type="number" name="parcelas_total" id="parcelas_total" min="1" value="1" class="form-input"></div>
                        <div><label for="inicio_competencia" class="form-label">Início da Competência</label><input type="date" name="inicio_competencia" id="inicio_competencia" value="<?= date('Y-m-d') ?>" class="form-input"></div>
                        <div><label for="fim_competencia" class="form-label">Fim da Competência</label><input type="date" name="fim_competencia" id="fim_competencia" class="form-input"></div>
                    </div>
                </div>
                <div class="flex justify-end pt-2"><button type="submit" class="btn btn-primary">Salvar Obrigação</button></div>
            </form>
        </section>
    </main>
</div>

<?php require __DIR__ . '/partials/erp_shell_close.php'; ?>

<script>
    window.SALARIO_MINIMO_PADRAO = <?= json_encode($salarioMinimoPadrao) ?>;
    window.MENSALIDADE_PADRAO = <?= json_encode($mensalidadePadrao) ?>;
    window.BIBLIOTECA_PADRAO = <?= json_encode($bibliotecaPadrao) ?>;
</script>
<script src="/assets/js/tesouraria_obrigacoes.js"></script>


