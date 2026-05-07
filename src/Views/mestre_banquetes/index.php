<?php
declare(strict_types=1);

// #############################################################################
// LÓGICA DE NEGÓCIO E HELPERS
// #############################################################################

$mensagemSucesso = $_SESSION['mensagem_sucesso'] ?? null;
$mensagemErro = $_SESSION['mensagem_erro'] ?? null;
unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']);

$sessaoEmFoco = $sessaoEmFoco ?? $proximaSessao ?? null;
$semAgape = array_values(array_filter(
    $confirmados,
    static fn (array $item): bool => empty($item['participara_agape'])
));

$tituloSessao = static function (?array $sessao): string {
    if (!$sessao) return 'N/A';
    $titulo = trim((string) ($sessao['titulo'] ?? ''));
    return $titulo !== '' ? $titulo : trim(((string) ($sessao['tipo_sessao'] ?? 'Sessão')) . ' - ' . ((string) ($sessao['grau_sessao'] ?? '')));
};

$formatCurrency = static fn($value): string => 'R$ ' . number_format((float) ($value ?? 0), 2, ',', '.');

// #############################################################################
// CONFIGURAÇÃO DO APP SHELL
// #############################################################################

$appShellEyebrow = 'Mestre de Banquetes';
$appShellTitle = 'Painel de Controle do Ágape';
$appShellDescription = 'Controle do ágape, previsão de participantes e observações logísticas por sessão.';
$appShellActiveHref = '/mestre-banquetes';

require __DIR__ . '/../partials/erp_shell_open.php';

?>

<!-- Mensagens de Feedback -->
<?php if ($mensagemSucesso): ?><div class="alert alert-success mb-6"><?= htmlspecialchars($mensagemSucesso) ?></div><?php endif; ?>
<?php if ($mensagemErro): ?><div class="alert alert-danger mb-6"><?= htmlspecialchars($mensagemErro) ?></div><?php endif; ?>

<!-- Métricas Rápidas -->
<div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-6 mb-8">
    <div class="card-metric"><p class="card-metric-label">Sessão em Foco</p><p class="card-metric-value text-lg truncate" title="<?= htmlspecialchars($tituloSessao($sessaoEmFoco)) ?>"><?= htmlspecialchars($tituloSessao($sessaoEmFoco)) ?></p></div>
    <div class="card-metric"><p class="card-metric-label">Confirmados na Sessão</p><p class="card-metric-value"><?= count($confirmados) ?></p></div>
    <div class="card-metric"><p class="card-metric-label">Participantes do Ágape</p><p class="card-metric-value"><?= count($participantesAgape) ?></p></div>
    <div class="card-metric"><p class="card-metric-label">Status Operacional</p><p class="card-metric-value text-lg capitalize"><?= htmlspecialchars((string) ($operacaoBanquete['status_operacional'] ?? 'planejamento'))?></p></div>
    <div class="card-metric"><p class="card-metric-label">Entrada Estimada</p><p class="card-metric-value text-lg text-green-600"><?= htmlspecialchars($formatCurrency($financeiroBanquete['valor_estimado'] ?? 0)) ?></p></div>
    <div class="card-metric"><p class="card-metric-label">Resultado Real</p><p class="card-metric-value text-lg"><?= htmlspecialchars($formatCurrency($financeiroBanquete['resultado_real'] ?? 0)) ?></p></div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Coluna Principal (2/3) -->
    <div class="lg:col-span-2 space-y-8">
        <div class="card">
            <div class="card-header"><h2 class="card-title">Operação do Banquete</h2><p class="card-description">Ajuste a sessão de trabalho e registre a previsão e o status logístico do ágape.</p></div>
            <div class="card-body space-y-6">
                <form method="GET" action="/mestre-banquetes" class="flex flex-col sm:flex-row sm:items-end sm:gap-4">
                    <div class="flex-grow">
                        <label for="sessao_id" class="form-label">Selecionar Sessão</label>
                        <select id="sessao_id" name="sessao_id" onchange="this.form.submit()" class="form-select">
                            <?php foreach ($sessoes as $sessaoOpcao): ?>
                                <option value="<?= (int) ($sessaoOpcao['id'] ?? 0) ?>" <?= !empty($sessaoEmFoco['id']) && (int) $sessaoEmFoco['id'] === (int) ($sessaoOpcao['id'] ?? 0) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($tituloSessao($sessaoOpcao)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <a href="/miniapp/mestre-banquetes" class="btn btn-outline-secondary mt-4 sm:mt-0">Abrir Mobile</a>
                </form>

                <?php if ($sessaoEmFoco): ?>
                    <div class="list-item-report">
                        <p class="text-sm">Configuração do ágape: <strong><?= htmlspecialchars((string) ($descricaoAgape ?? 'Não informado')) ?></strong></p>
                        <p class="text-sm mt-1">Modelo financeiro: <strong><?= htmlspecialchars((string) ($descricaoModeloFinanceiroAgape ?? 'Não informado')) ?></strong></p>
                    </div>
                    <?php
                    $graficoReceita = max(0.0, (float) ($financeiroBanquete['valor_estimado'] ?? 0));
                    $graficoCusto = max(0.0, (float) ($financeiroBanquete['custo_previsto'] ?? 0));
                    $graficoMax = max($graficoReceita, $graficoCusto, 1.0);
                    ?>
                    <div class="rounded-2xl border border-erp-border bg-white/70 p-4 dark:bg-gray-900/40">
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <div>
                                <h3 class="font-semibold text-gray-800 dark:text-gray-100">Leitura financeira rápida</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Estimativa baseada nos participantes do ágape e custos previstos.</p>
                            </div>
                            <span class="text-3xl" aria-hidden="true">🍽️</span>
                        </div>
                        <div class="space-y-3">
                            <div>
                                <div class="mb-1 flex justify-between text-xs"><span>Receita estimada</span><strong><?= htmlspecialchars($formatCurrency($graficoReceita)) ?></strong></div>
                                <div class="h-3 rounded-full bg-gray-100"><div class="h-3 rounded-full bg-green-500" style="width: <?= min(100, round(($graficoReceita / $graficoMax) * 100)) ?>%"></div></div>
                            </div>
                            <div>
                                <div class="mb-1 flex justify-between text-xs"><span>Custo previsto</span><strong><?= htmlspecialchars($formatCurrency($graficoCusto)) ?></strong></div>
                                <div class="h-3 rounded-full bg-gray-100"><div class="h-3 rounded-full bg-red-500" style="width: <?= min(100, round(($graficoCusto / $graficoMax) * 100)) ?>%"></div></div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/mestre-banquetes/operacao/salvar" class="space-y-4">
                    <input type="hidden" name="sessao_id" value="<?= (int) ($sessaoEmFoco['id'] ?? 0) ?>">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="status_operacional" class="form-label">Status Operacional</label>
                            <select name="status_operacional" id="status_operacional" class="form-select">
                                <?php $statusAtual = (string) ($operacaoBanquete['status_operacional'] ?? 'planejamento'); ?>
                                <?php foreach (['planejamento' => 'Planejamento', 'preparacao' => 'Preparação', 'abastecimento' => 'Abastecimento', 'fechado' => 'Fechado'] as $valor => $label): ?>
                                    <option value="<?= $valor ?>" <?= $statusAtual === $valor ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div><label for="previsao_participantes" class="form-label">Previsão de Participantes</label><input type="number" min="0" name="previsao_participantes" id="previsao_participantes" value="<?= htmlspecialchars((string) ($operacaoBanquete['previsao_participantes'] ?? '')) ?>" class="form-input"></div>
                    </div>
                    <div><label for="observacoes" class="form-label">Observações Logísticas</label><textarea name="observacoes" id="observacoes" rows="3" class="form-textarea" placeholder="Ex: cardápio, restrições, equipe de apoio..."><?= htmlspecialchars((string) ($operacaoBanquete['observacoes'] ?? '')) ?></textarea></div>
                    <div class="rounded-2xl border border-erp-border bg-erp-surface/60 p-4">
                        <div class="mb-4">
                            <h3 class="font-semibold text-gray-800 dark:text-gray-100">Controle financeiro do ágape</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Escolha se o controle é particular, se afeta o caixa da Loja ou se é ressarcimento de compras para dispensa.</p>
                        </div>
                        <?php $fluxoAtual = (string) ($operacaoBanquete['fluxo_financeiro'] ?? 'rateio_particular'); ?>
                        <div class="mb-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                            <label class="rounded-xl border border-erp-border bg-white p-4 text-sm">
                                <input type="radio" name="fluxo_financeiro" value="rateio_particular" <?= $fluxoAtual === 'rateio_particular' ? 'checked' : '' ?>>
                                <span class="ml-2 font-semibold">Rateio particular</span>
                                <span class="mt-2 block text-xs text-gray-500">Um membro paga, o valor é dividido entre participantes e não entra no caixa da Loja.</span>
                            </label>
                            <label class="rounded-xl border border-erp-border bg-white p-4 text-sm">
                                <input type="radio" name="fluxo_financeiro" value="caixa_loja" <?= $fluxoAtual === 'caixa_loja' ? 'checked' : '' ?>>
                                <span class="ml-2 font-semibold">Caixa da Loja</span>
                                <span class="mt-2 block text-xs text-gray-500">A Loja paga ou arrecada; ao registrar no caixa, gera lançamento oficial.</span>
                            </label>
                            <label class="rounded-xl border border-erp-border bg-white p-4 text-sm">
                                <input type="radio" name="fluxo_financeiro" value="dispensa_ressarcimento" <?= $fluxoAtual === 'dispensa_ressarcimento' ? 'checked' : '' ?>>
                                <span class="ml-2 font-semibold">Dispensa/ressarcimento</span>
                                <span class="mt-2 block text-xs text-gray-500">Compra para abastecimento, com ressarcimento integral pela Tesouraria.</span>
                            </label>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div><label for="valor_unitario_previsto" class="form-label">Valor por participante</label><input type="number" step="0.01" min="0" name="valor_unitario_previsto" id="valor_unitario_previsto" value="<?= htmlspecialchars((string) ($operacaoBanquete['valor_unitario_previsto'] ?? ($sessaoEmFoco['agape_valor'] ?? ''))) ?>" class="form-input"></div>
                            <div><label for="custo_previsto" class="form-label">Custo previsto</label><input type="number" step="0.01" min="0" name="custo_previsto" id="custo_previsto" value="<?= htmlspecialchars((string) ($operacaoBanquete['custo_previsto'] ?? '')) ?>" class="form-input"></div>
                            <div><label for="valor_arrecadado" class="form-label">Valor arrecadado</label><input type="number" step="0.01" min="0" name="valor_arrecadado" id="valor_arrecadado" value="<?= htmlspecialchars((string) ($operacaoBanquete['valor_arrecadado'] ?? '')) ?>" class="form-input"></div>
                            <div><label for="custo_real" class="form-label">Custo real</label><input type="number" step="0.01" min="0" name="custo_real" id="custo_real" value="<?= htmlspecialchars((string) ($operacaoBanquete['custo_real'] ?? '')) ?>" class="form-input"></div>
                        </div>
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div><label for="responsavel_pagamento" class="form-label">Responsável pelo pagamento</label><input type="text" name="responsavel_pagamento" id="responsavel_pagamento" value="<?= htmlspecialchars((string) ($operacaoBanquete['responsavel_pagamento'] ?? '')) ?>" class="form-input" placeholder="Nome do membro ou Loja"></div>
                            <div><label for="fornecedor" class="form-label">Fornecedor</label><input type="text" name="fornecedor" id="fornecedor" value="<?= htmlspecialchars((string) ($operacaoBanquete['fornecedor'] ?? '')) ?>" class="form-input"></div>
                            <div><label for="forma_pagamento" class="form-label">Forma de pagamento</label><input type="text" name="forma_pagamento" id="forma_pagamento" value="<?= htmlspecialchars((string) ($operacaoBanquete['forma_pagamento'] ?? '')) ?>" class="form-input" placeholder="PIX, dinheiro, cartão..."></div>
                            <div><label for="financeiro_status" class="form-label">Status financeiro</label><select name="financeiro_status" id="financeiro_status" class="form-select"><?php $finStatus = (string) ($operacaoBanquete['financeiro_status'] ?? 'planejado'); foreach (['planejado' => 'Planejado', 'a_receber' => 'A receber', 'a_pagar' => 'A pagar', 'conciliado' => 'Conciliado'] as $valor => $label): ?><option value="<?= $valor ?>" <?= $finStatus === $valor ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></div>
                        </div>
                        <div class="mt-4"><label for="financeiro_observacoes" class="form-label">Observações financeiras</label><textarea name="financeiro_observacoes" id="financeiro_observacoes" rows="2" class="form-textarea"><?= htmlspecialchars((string) ($operacaoBanquete['financeiro_observacoes'] ?? '')) ?></textarea></div>
                        <?php
                        $fluxoSelecionado = strtolower(trim((string) ($operacaoBanquete['fluxo_financeiro'] ?? 'rateio_particular')));
                        $impactaCaixa = in_array($fluxoSelecionado, ['caixa_loja', 'dispensa_ressarcimento'], true);
                        ?>
                        <div class="mt-4 text-sm text-gray-700 dark:text-gray-300">
                            <span class="font-semibold">Caixa da Loja:</span>
                            <?= $impactaCaixa ? 'Esta operação impacta o Caixa da Loja.' : 'Esta operação não impacta o Caixa da Loja.' ?>
                        </div>
                        <?php if (!empty($operacaoBanquete['receita_lancamento_id']) || !empty($operacaoBanquete['despesa_lancamento_id'])): ?>
                            <div class="mt-3 text-xs text-gray-500">Lançamentos vinculados: receita #<?= htmlspecialchars((string) ($operacaoBanquete['receita_lancamento_id'] ?? '-')) ?> · despesa #<?= htmlspecialchars((string) ($operacaoBanquete['despesa_lancamento_id'] ?? '-')) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="text-right"><button type="submit" class="btn btn-primary">Salvar Operação</button></div>
                </form>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="card">
                <div class="card-header"><h2 class="card-title">Participantes do Ágape</h2></div>
                <div class="card-body space-y-3">
                    <?php if (empty($participantesAgape)): ?>
                        <p class="text-center text-gray-500 py-4">Ainda não há participantes confirmados com ágape.</p>
                    <?php else: ?>
                        <?php foreach ($participantesAgape as $participante): ?>
                            <div class="list-item">
                                <p class="font-semibold"><?= htmlspecialchars((string) ($participante['nome'] ?? 'Obreiro')) ?></p>
                                <p class="text-sm text-gray-500">CIM: <?= htmlspecialchars((string) ($participante['cim'] ?? '-')) ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h2 class="card-title">Confirmados sem Ágape</h2></div>
                <div class="card-body space-y-3">
                    <?php if (empty($semAgape)): ?>
                        <p class="text-center text-gray-500 py-4">Não há confirmados sem ágape.</p>
                    <?php else: ?>
                        <?php foreach ($semAgape as $confirmado): ?>
                            <div class="list-item">
                                <p class="font-semibold"><?= htmlspecialchars((string) ($confirmado['nome'] ?? 'Obreiro')) ?></p>
                                <p class="text-sm text-gray-500">CIM: <?= htmlspecialchars((string) ($confirmado['cim'] ?? '-')) ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Coluna Lateral (1/3) -->
    <div class="space-y-8">
        <div class="card">
            <div class="card-header"><h2 class="card-title">Agenda Futura</h2></div>
            <div class="card-body space-y-3">
                <?php foreach ($sessoes as $sessao): ?>
                    <a href="/mestre-banquetes?sessao_id=<?= (int) ($sessao['id'] ?? 0) ?>" class="list-item-action">
                        <p class="font-semibold"><?= htmlspecialchars($tituloSessao($sessao))?></p>
                        <p class="mt-1 text-sm text-gray-500"><?= htmlspecialchars((string) ($sessao['data_hora_inicio'] ?? '')) ?></p>
                        <div class="mt-2 flex gap-4 text-xs text-gray-500">
                            <span>Confirmados: <strong><?= (int) ($sessao['total_confirmados'] ?? 0) ?></strong></span>
                            <span>Ágape: <strong><?= (int) ($sessao['total_agape'] ?? 0) ?></strong></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../partials/erp_shell_close.php';
?>

