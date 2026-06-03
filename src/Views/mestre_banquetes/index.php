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
    $confirmados ?? [],
    static fn (array $item): bool => empty($item['participara_agape'])
));
$participantesAgape = $participantesAgape ?? [];
$sessoes = $sessoes ?? [];
$operacaoBanquete = $operacaoBanquete ?? [];
$financeiroBanquete = $financeiroBanquete ?? [];

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
$appShellTitle = 'Gestão Logística do Ágape';
$appShellDescription = 'Acompanhamento operacional, previsão de ágape, rateios e lançamentos de caixa.';
$appShellActiveHref = '/mestre-banquetes';

require __DIR__ . '/../partials/erp_shell_open.php';
?>

<!-- Mensagens de Feedback -->
<?php if ($mensagemSucesso): ?>
    <div class="alert alert-success mb-6"><?= htmlspecialchars($mensagemSucesso) ?></div>
<?php endif; ?>
<?php if ($mensagemErro): ?>
    <div class="alert alert-danger mb-6"><?= htmlspecialchars($mensagemErro) ?></div>
<?php endif; ?>

<!-- Métricas Rápidas -->
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6 mb-8">
    <div class="card-metric">
        <p class="card-metric-label">Sessão em Foco</p>
        <p class="card-metric-value text-base font-bold truncate mt-1" title="<?= htmlspecialchars($tituloSessao($sessaoEmFoco)) ?>"><?= htmlspecialchars($tituloSessao($sessaoEmFoco)) ?></p>
    </div>
    <div class="card-metric">
        <p class="card-metric-label">Presenças Confirmadas</p>
        <p class="card-metric-value"><?= count($confirmados ?? []) ?></p>
    </div>
    <div class="card-metric">
        <p class="card-metric-label">Confirmados no Ágape</p>
        <p class="card-metric-value"><?= count($participantesAgape) ?></p>
    </div>
    <div class="card-metric">
        <p class="card-metric-label">Status Operacional</p>
        <p class="card-metric-value text-base font-bold capitalize mt-1"><?= htmlspecialchars((string) ($operacaoBanquete['status_operacional'] ?? 'planejamento'))?></p>
    </div>
    <div class="card-metric border border-emerald-500/10 bg-emerald-500/5 text-emerald-400">
        <p class="card-metric-label !text-emerald-400/80">Entrada Estimada</p>
        <p class="card-metric-value text-base font-bold mt-1"><?= htmlspecialchars($formatCurrency($financeiroBanquete['valor_estimado'] ?? 0)) ?></p>
    </div>
    <div class="card-metric">
        <p class="card-metric-label">Resultado Real</p>
        <p class="card-metric-value text-base font-bold mt-1"><?= htmlspecialchars($formatCurrency($financeiroBanquete['resultado_real'] ?? 0)) ?></p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Coluna Principal (2/3) -->
    <div class="lg:col-span-2 space-y-8">
        
        <!-- OPERAÇÃO DO BANQUETE -->
        <div class="card depth-1">
            <div class="card-header border-b border-white/5 p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="card-title text-white">Operação do Banquete</h2>
                    <p class="card-subtitle mt-1">Defina o status operacional, custos e arrecadação da sessão selecionada.</p>
                </div>
                <a href="/miniapp/mestre-banquetes" class="btn border border-white/10 text-slate-300 hover:bg-white/5 py-1.5 px-4 text-xs font-semibold">
                    Abrir Modo Mobile
                </a>
            </div>
            
            <div class="card-body p-6 space-y-6">
                <!-- Seletor de Sessão -->
                <form method="GET" action="/mestre-banquetes" class="grid grid-cols-1 sm:grid-cols-3 gap-4 bg-white/[0.01] border border-white/5 rounded-xl p-4">
                    <div class="sm:col-span-2">
                        <label for="sessao_id" class="form-label">Selecionar Sessão</label>
                        <select id="sessao_id" name="sessao_id" onchange="this.form.submit()" class="form-select w-full">
                            <?php foreach ($sessoes as $sessaoOpcao): ?>
                                <option value="<?= (int) ($sessaoOpcao['id'] ?? 0) ?>" <?= !empty($sessaoEmFoco['id']) && (int) $sessaoEmFoco['id'] === (int) ($sessaoOpcao['id'] ?? 0) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($tituloSessao($sessaoOpcao)) ?> &middot; <?= htmlspecialchars((string) ($sessaoOpcao['data_hora_inicio'] ?? '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="btn border border-white/10 text-slate-300 hover:bg-white/5 w-full py-2.5 font-semibold text-xs">Atualizar Painel</button>
                    </div>
                </form>

                <?php if ($sessaoEmFoco): ?>
                    <!-- Indicador Ritual Rápido -->
                    <div class="rounded-xl border border-white/5 bg-white/[0.02] p-4 text-xs leading-relaxed text-slate-300">
                        <p>Ref: <strong class="text-white"><?= htmlspecialchars((string) ($descricaoAgape ?? 'Ritualístico / Festivo')) ?></strong></p>
                        <p class="mt-1">Modelo: <strong class="text-white"><?= htmlspecialchars((string) ($descricaoModeloFinanceiroAgape ?? 'Rateio ou Coleta')) ?></strong></p>
                    </div>

                    <!-- Estimativas Visuais de Orçamento -->
                    <?php
                    $graficoReceita = max(0.0, (float) ($financeiroBanquete['valor_estimado'] ?? 0));
                    $graficoCusto = max(0.0, (float) ($financeiroBanquete['custo_previsto'] ?? 0));
                    $graficoMax = max($graficoReceita, $graficoCusto, 1.0);
                    ?>
                    <div class="rounded-xl border border-white/5 bg-white/[0.01] p-5 space-y-4">
                        <h3 class="text-xs font-bold text-erp-gold uppercase tracking-wider">Balanço Estimado</h3>
                        
                        <div class="space-y-3">
                            <div>
                                <div class="mb-1 flex justify-between text-xs text-slate-400">
                                    <span>Receita Estimada (Participações)</span>
                                    <strong class="text-emerald-400"><?= htmlspecialchars($formatCurrency($graficoReceita)) ?></strong>
                                </div>
                                <div class="h-2 rounded-full bg-white/5 border border-white/10">
                                    <div class="h-1.5 rounded-full bg-emerald-500" style="width: <?= min(100, round(($graficoReceita / $graficoMax) * 100)) ?>%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="mb-1 flex justify-between text-xs text-slate-400">
                                    <span>Custo Previsto (Compras)</span>
                                    <strong class="text-red-400"><?= htmlspecialchars($formatCurrency($graficoCusto)) ?></strong>
                                </div>
                                <div class="h-2 rounded-full bg-white/5 border border-white/10">
                                    <div class="h-1.5 rounded-full bg-red-500" style="width: <?= min(100, round(($graficoCusto / $graficoMax) * 100)) ?>%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Formulário Completo de Lançamento -->
                <form method="POST" action="/mestre-banquetes/operacao/salvar" class="space-y-6">
                    <input type="hidden" name="sessao_id" value="<?= (int) ($sessaoEmFoco['id'] ?? 0) ?>">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="status_operacional" class="form-label">Status Operacional</label>
                            <select name="status_operacional" id="status_operacional" class="form-select w-full">
                                <?php $statusAtual = (string) ($operacaoBanquete['status_operacional'] ?? 'planejamento'); ?>
                                <?php foreach (['planejamento' => 'Planejamento', 'preparacao' => 'Preparação (Compras)', 'abastecimento' => 'Abastecimento', 'fechado' => 'Banquete Encerrado'] as $valor => $label): ?>
                                    <option value="<?= $valor ?>" <?= $statusAtual === $valor ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="previsao_participantes" class="form-label">Previsão de Participantes (Dispensa)</label>
                            <input type="number" min="0" name="previsao_participantes" id="previsao_participantes" value="<?= htmlspecialchars((string) ($operacaoBanquete['previsao_participantes'] ?? '')) ?>" class="form-input w-full" placeholder="Quantidade de irmãos esperados">
                        </div>
                    </div>

                    <div>
                        <label for="observacoes" class="form-label">Observações Logísticas (Cardápio / Equipe)</label>
                        <textarea name="observacoes" id="observacoes" rows="3" class="form-textarea w-full" placeholder="Descreva o cardápio, necessidades especiais de compras ou equipe de apoio..."><?= htmlspecialchars((string) ($operacaoBanquete['observacoes'] ?? '')) ?></textarea>
                    </div>

                    <!-- FLUXOS FINANCEIROS E GOVERNANÇA -->
                    <div class="bg-white/[0.01] border border-white/5 rounded-xl p-5 space-y-4">
                        <div>
                            <h3 class="text-sm font-bold text-white">Modelo de Contabilização Financeira</h3>
                            <p class="text-xs text-slate-400 mt-1">Selecione uma das modalidades para definir o fluxo nos livros da Loja:</p>
                        </div>

                        <?php $fluxoAtual = (string) ($operacaoBanquete['fluxo_financeiro'] ?? 'rateio_particular'); ?>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <label class="relative flex flex-col justify-between rounded-xl border border-white/5 bg-white/[0.01] p-4 hover:bg-white/5 transition cursor-pointer text-xs">
                                <div class="flex items-center gap-2">
                                    <input type="radio" name="fluxo_financeiro" value="rateio_particular" <?= $fluxoAtual === 'rateio_particular' ? 'checked' : '' ?> class="form-radio text-primary">
                                    <span class="font-bold text-white">Rateio Particular</span>
                                </div>
                                <span class="mt-2 text-slate-400 leading-relaxed block">
                                    Um obreiro assume as compras e os participantes dividem o valor entre si. <strong>Não entra nos livros da Loja.</strong>
                                </span>
                            </label>

                            <label class="relative flex flex-col justify-between rounded-xl border border-white/5 bg-white/[0.01] p-4 hover:bg-white/5 transition cursor-pointer text-xs">
                                <div class="flex items-center gap-2">
                                    <input type="radio" name="fluxo_financeiro" value="caixa_loja" <?= $fluxoAtual === 'caixa_loja' ? 'checked' : '' ?> class="form-radio text-primary">
                                    <span class="font-bold text-white">Caixa da Loja</span>
                                </div>
                                <span class="mt-2 text-slate-400 leading-relaxed block">
                                    A arrecadação e as despesas entram no <strong>fluxo oficial de caixa da Loja</strong> (gerando lançamentos na Tesouraria).
                                </span>
                            </label>

                            <label class="relative flex flex-col justify-between rounded-xl border border-white/5 bg-white/[0.01] p-4 hover:bg-white/5 transition cursor-pointer text-xs">
                                <div class="flex items-center gap-2">
                                    <input type="radio" name="fluxo_financeiro" value="dispensa_ressarcimento" <?= $fluxoAtual === 'dispensa_ressarcimento' ? 'checked' : '' ?> class="form-radio text-primary">
                                    <span class="font-bold text-white">Dispensa/Ressarcimento</span>
                                </div>
                                <span class="mt-2 text-slate-400 leading-relaxed block">
                                    Compras feitas para o estoque/dispensa da Loja, <strong>reembolsadas de forma integral pela Tesouraria</strong> ao comprador.
                                </span>
                            </label>
                        </div>

                        <!-- VALORES E CUSTOS -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 pt-4 border-t border-white/5">
                            <div>
                                <label for="valor_unitario_previsto" class="form-label text-[11px]">Valor p/ Participante</label>
                                <input type="number" step="0.01" min="0" name="valor_unitario_previsto" id="valor_unitario_previsto" value="<?= htmlspecialchars((string) ($operacaoBanquete['valor_unitario_previsto'] ?? ($sessaoEmFoco['agape_valor'] ?? ''))) ?>" class="form-input w-full" placeholder="R$ 0,00">
                            </div>
                            <div>
                                <label for="custo_previsto" class="form-label text-[11px]">Custo Previsto (Compras)</label>
                                <input type="number" step="0.01" min="0" name="custo_previsto" id="custo_previsto" value="<?= htmlspecialchars((string) ($operacaoBanquete['custo_previsto'] ?? '')) ?>" class="form-input w-full" placeholder="R$ 0,00">
                            </div>
                            <div>
                                <label for="valor_arrecadado" class="form-label text-[11px]">Valor Real Arrecadado</label>
                                <input type="number" step="0.01" min="0" name="valor_arrecadado" id="valor_arrecadado" value="<?= htmlspecialchars((string) ($operacaoBanquete['valor_arrecadado'] ?? '')) ?>" class="form-input w-full" placeholder="R$ 0,00">
                            </div>
                            <div>
                                <label for="custo_real" class="form-label text-[11px]">Custo Real Executado</label>
                                <input type="number" step="0.01" min="0" name="custo_real" id="custo_real" value="<?= htmlspecialchars((string) ($operacaoBanquete['custo_real'] ?? '')) ?>" class="form-input w-full" placeholder="R$ 0,00">
                            </div>
                        </div>

                        <!-- DETALHES DE PAGAMENTO -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-4 border-t border-white/5">
                            <div>
                                <label for="responsavel_pagamento" class="form-label text-[11px]">Comprador / Responsável</label>
                                <input type="text" name="responsavel_pagamento" id="responsavel_pagamento" value="<?= htmlspecialchars((string) ($operacaoBanquete['responsavel_pagamento'] ?? '')) ?>" class="form-input w-full" placeholder="Ex: Mestre de Banquetes">
                            </div>
                            <div>
                                <label for="fornecedor" class="form-label text-[11px]">Fornecedor (Ex: Supermercado)</label>
                                <input type="text" name="fornecedor" id="fornecedor" value="<?= htmlspecialchars((string) ($operacaoBanquete['fornecedor'] ?? '')) ?>" class="form-input w-full" placeholder="Ex: Açougue do Irmão">
                            </div>
                            <div>
                                <label for="forma_pagamento" class="form-label text-[11px]">Meio de Pagamento</label>
                                <input type="text" name="forma_pagamento" id="forma_pagamento" value="<?= htmlspecialchars((string) ($operacaoBanquete['forma_pagamento'] ?? '')) ?>" class="form-input w-full" placeholder="PIX, Dinheiro, Cartão da Loja">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t border-white/5">
                            <div>
                                <label for="financeiro_status" class="form-label text-[11px]">Status Financeiro</label>
                                <select name="financeiro_status" id="financeiro_status" class="form-select w-full py-1.5">
                                    <?php $finStatus = (string) ($operacaoBanquete['financeiro_status'] ?? 'planejado'); ?>
                                    <?php foreach (['planejado' => 'Planejado', 'a_receber' => 'Valores a Receber', 'a_pagar' => 'Compras a Pagar', 'conciliado' => 'Fechado / Conciliado'] as $valor => $label): ?>
                                        <option value="<?= $valor ?>" <?= $finStatus === $valor ? 'selected' : '' ?>><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="financeiro_observacoes" class="form-label text-[11px]">Observações Financeiras</label>
                                <input type="text" name="financeiro_observacoes" id="financeiro_observacoes" value="<?= htmlspecialchars((string) ($operacaoBanquete['financeiro_observacoes'] ?? '')) ?>" class="form-input w-full" placeholder="Ex: cupom fiscal entregue à Tesouraria.">
                            </div>
                        </div>

                        <?php
                        $fluxoSelecionado = strtolower(trim((string) ($operacaoBanquete['fluxo_financeiro'] ?? 'rateio_particular')));
                        $impactaCaixa = in_array($fluxoSelecionado, ['caixa_loja', 'dispensa_ressarcimento'], true);
                        ?>
                        <div class="mt-4 text-xs font-semibold text-slate-300 bg-black/10 border border-white/5 rounded-lg p-3">
                            Informação Contábil: 
                            <span class="<?= $impactaCaixa ? 'text-erp-gold' : 'text-slate-400' ?>">
                                <?= $impactaCaixa ? 'Esta operação impacta e gera lançamentos de despesa/receita no Caixa Geral da Loja.' : 'Esta operação é particular e não altera os saldos oficiais da Tesouraria.' ?>
                            </span>
                        </div>

                        <?php if (!empty($operacaoBanquete['receita_lancamento_id']) || !empty($operacaoBanquete['despesa_lancamento_id'])): ?>
                            <div class="text-[10px] text-slate-500 pt-1">
                                Lançamentos Contábeis Vinculados: 
                                <?php if (!empty($operacaoBanquete['receita_lancamento_id'])): ?>Receita #<?= htmlspecialchars((string) $operacaoBanquete['receita_lancamento_id']) ?> <?php endif; ?>
                                <?php if (!empty($operacaoBanquete['despesa_lancamento_id'])): ?>&middot; Despesa #<?= htmlspecialchars((string) $operacaoBanquete['despesa_lancamento_id']) ?><?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="text-right">
                        <button type="submit" class="btn btn-primary px-8">Salvar Configurações do Banquete</button>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($sessaoEmFoco && ($sessaoEmFoco['status'] ?? '') === 'realizada' && !empty($sessaoEmFoco['agape_ativo'])): ?>
            <!-- REGISTRO DE PRESENÇA REAL NO ÁGAPE -->
            <div class="card depth-1">
                <div class="card-header border-b border-white/5 p-6">
                    <h2 class="card-title text-white">Registro de Presença Real no Ágape</h2>
                    <p class="card-subtitle mt-1">Marque os irmãos que de fato permaneceram para o banquete fraterno após a sessão ritualística.</p>
                </div>
                <div class="card-body p-6">
                    <form method="POST" action="/mestre-banquetes/presencas/salvar" class="space-y-6">
                        <input type="hidden" name="sessao_id" value="<?= (int) $sessaoEmFoco['id'] ?>">
                        
                        <?php if (empty($presencasSessao)): ?>
                            <p class="text-center text-slate-400 py-6 text-sm">
                                Nenhuma presença ritualística registrada para esta sessão ainda. O Chanceler precisa registrar as presenças da sessão antes.
                            </p>
                        <?php else: ?>
                            <!-- Grid de Presenças -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                                <?php foreach ($presencasSessao as $pres): ?>
                                    <?php $checked = ($pres['presente_agape'] === true); ?>
                                    <label class="relative flex items-center gap-3 rounded-xl border border-white/5 bg-white/[0.01] p-3 hover:bg-white/5 transition cursor-pointer text-xs">
                                        <input type="checkbox" name="presente_agape[]" value="<?= htmlspecialchars((string) $pres['obreiro_id']) ?>" <?= $checked ? 'checked' : '' ?> class="form-checkbox rounded text-primary">
                                        <div class="truncate">
                                            <span class="font-bold text-white block truncate"><?= htmlspecialchars((string) $pres['nome']) ?></span>
                                            <span class="text-[10px] text-slate-400">CIM: <?= htmlspecialchars((string) $pres['cim'] ?? '-') ?></span>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="text-right pt-4 border-t border-white/5">
                                <button type="submit" class="btn btn-success px-8">Salvar Presenças do Ágape</button>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($sessaoEmFoco): ?>
            <!-- ACOLHIMENTO E ANFITRIÕES DA NOITE -->
            <div class="card depth-1">
                <div class="card-header border-b border-white/5 p-6">
                    <h2 class="card-title text-white">Acolhimento e Integração Fraterna (Anfitriões)</h2>
                    <p class="card-subtitle mt-1">Designe "Irmãos Anfitriões" encarregados de acompanhar iniciantes, visitantes ou irmãos necessitando de reaproximação.</p>
                </div>
                <div class="card-body p-6 space-y-6">
                    
                    <!-- Lista de Designações Atuais -->
                    <div class="space-y-3">
                        <h3 class="text-xs font-bold text-erp-gold uppercase tracking-wider">Designações Ativas nesta Noite</h3>
                        
                        <?php if (empty($anfitrioesDesignados)): ?>
                            <p class="text-slate-400 text-xs bg-white/[0.01] border border-white/5 rounded-xl p-4 text-center">Nenhum anfitrião designado para esta noite.</p>
                        <?php else: ?>
                            <div class="space-y-3">
                                <?php foreach ($anfitrioesDesignados as $anf): ?>
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between p-4 rounded-xl border border-white/5 bg-white/[0.02] gap-4 text-xs">
                                        <div class="space-y-1">
                                            <p class="text-white">
                                                Anfitrião: <strong class="text-erp-gold"><?= htmlspecialchars((string)$anf['anfitriao_nome']) ?></strong>
                                            </p>
                                            <p class="text-slate-300">
                                                Foco: <span class="badge-status bg-white/5 text-slate-300 border border-white/10 uppercase text-[9px] px-1.5 py-0.5 rounded"><?= htmlspecialchars((string)$anf['foco']) ?></span>
                                                <?php if (!empty($anf['acolhido_nome'])): ?>
                                                    &middot; Acolhido: <strong class="text-white"><?= htmlspecialchars((string)$anf['acolhido_nome']) ?></strong>
                                                <?php elseif (!empty($anf['visitante_nome'])): ?>
                                                    &middot; Visitante: <strong class="text-white"><?= htmlspecialchars((string)$anf['visitante_nome']) ?></strong>
                                                <?php endif; ?>
                                            </p>
                                            <?php if (!empty($anf['observacao'])): ?>
                                                <p class="text-slate-400 italic text-[11px]">Nota: "<?= htmlspecialchars((string)$anf['observacao']) ?>"</p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-right">
                                            <form method="POST" action="/mestre-banquetes/anfitrioes/salvar" onsubmit="return confirm('Deseja remover esta designação de anfitrião?');">
                                                <input type="hidden" name="sessao_id" value="<?= (int) $sessaoEmFoco['id'] ?>">
                                                <input type="hidden" name="anfitriao_id" value="<?= (int) $anf['id'] ?>">
                                                <input type="hidden" name="action" value="remover">
                                                <button type="submit" class="btn border border-red-500/30 text-red-400 hover:bg-red-500/5 !py-1 !px-2.5 text-[10px] font-bold">
                                                    Remover
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Formulário para Adicionar Designação -->
                    <div class="border-t border-white/5 pt-6">
                        <h3 class="text-xs font-bold text-white uppercase tracking-wider mb-4">Designar Novo Anfitrião</h3>
                        <form method="POST" action="/mestre-banquetes/anfitrioes/salvar" class="space-y-4">
                            <input type="hidden" name="sessao_id" value="<?= (int) $sessaoEmFoco['id'] ?>">
                            <input type="hidden" name="action" value="adicionar">
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                                <div>
                                    <label for="anfitriao_obreiro_id" class="form-label">Irmão Anfitrião</label>
                                    <select name="anfitriao_obreiro_id" id="anfitriao_obreiro_id" required class="form-select w-full">
                                        <option value="">Selecione o Anfitrião...</option>
                                        <?php foreach ($obreirosAtivos as $ob): ?>
                                            <option value="<?= htmlspecialchars((string)$ob['id']) ?>"><?= htmlspecialchars((string)$ob['nome']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div>
                                    <label for="foco" class="form-label">Foco de Acolhimento</label>
                                    <select name="foco" id="foco" class="form-select w-full" onchange="toggleAcolhidosDisplay(this.value)">
                                        <option value="integracao">Integração Geral</option>
                                        <option value="aprendizes">Novos Aprendizes</option>
                                        <option value="companheiros">Companheiros</option>
                                        <option value="visitantes">Visitantes Externos</option>
                                        <option value="retorno">Irmão em Retorno (Afastados)</option>
                                        <option value="geral">Geral (Acolhimento da Noite)</option>
                                    </select>
                                </div>
                                
                                <div id="acolhido_obreiro_block">
                                    <label for="acolhido_obreiro_id" class="form-label">Irmão Acolhido (Do Quadro)</label>
                                    <select name="acolhido_obreiro_id" id="acolhido_obreiro_id" class="form-select w-full">
                                        <option value="">Selecione o Acolhido...</option>
                                        <?php foreach ($obreirosAtivos as $ob): ?>
                                            <option value="<?= htmlspecialchars((string)$ob['id']) ?>"><?= htmlspecialchars((string)$ob['nome']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div id="visitante_nome_block" style="display: none;">
                                    <label for="visitante_nome" class="form-label">Nome do Visitante</label>
                                    <input type="text" name="visitante_nome" id="visitante_nome" class="form-input w-full" placeholder="Ex: João da Silva">
                                </div>
                            </div>
                            
                            <div>
                                <label for="anfitriao_observacao" class="form-label">Instruções / Observações de Acolhimento</label>
                                <textarea name="observacao" id="anfitriao_observacao" rows="2" class="form-textarea w-full" placeholder="Ex: Sentar ao lado do irmão e apresentá-lo aos oficiais da Oficina..."></textarea>
                            </div>
                            
                            <div class="text-right">
                                <button type="submit" class="btn btn-primary px-8">Confirmar Designação</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <script>
            function toggleAcolhidosDisplay(focoVal) {
                const obreiroBlock = document.getElementById('acolhido_obreiro_block');
                const visitanteBlock = document.getElementById('visitante_nome_block');
                
                if (focoVal === 'visitantes') {
                    obreiroBlock.style.display = 'none';
                    document.getElementById('acolhido_obreiro_id').value = '';
                    visitanteBlock.style.display = 'block';
                } else if (focoVal === 'geral') {
                    obreiroBlock.style.display = 'none';
                    document.getElementById('acolhido_obreiro_id').value = '';
                    visitanteBlock.style.display = 'none';
                    document.getElementById('visitante_nome').value = '';
                } else {
                    obreiroBlock.style.display = 'block';
                    visitanteBlock.style.display = 'none';
                    document.getElementById('visitante_nome').value = '';
                }
            }
            </script>
        <?php endif; ?>

        <!-- PARTICIPANTES E CONFIRMADOS -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="card depth-1">
                <div class="card-header border-b border-white/5 p-6">
                    <h2 class="card-title text-white">Participantes Confirmados no Ágape</h2>
                    <p class="card-subtitle mt-1">Irmãos que confirmaram presença no jantar.</p>
                </div>
                <div class="card-body p-6 space-y-3">
                    <?php if (empty($participantesAgape)): ?>
                        <p class="text-center text-slate-400 py-6 text-sm">Nenhum participante com ágape confirmado.</p>
                    <?php else: ?>
                        <?php foreach ($participantesAgape as $part): ?>
                            <div class="flex items-center justify-between p-3 rounded-xl border border-white/5 bg-white/[0.02] text-xs">
                                <span class="font-bold text-white truncate max-w-[12rem]"><?= htmlspecialchars((string) ($part['nome'] ?? 'Obreiro')) ?></span>
                                <span class="text-slate-400 font-sans">CIM: <?= htmlspecialchars((string) ($part['cim'] ?? '-')) ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card depth-1">
                <div class="card-header border-b border-white/5 p-6">
                    <h2 class="card-title text-white">Confirmados na Sessão (Sem Ágape)</h2>
                    <p class="card-subtitle mt-1">Irmãos que participam do trabalho, mas não ficam para o jantar.</p>
                </div>
                <div class="card-body p-6 space-y-3">
                    <?php if (empty($semAgape)): ?>
                        <p class="text-center text-slate-400 py-6 text-sm">Nenhuma confirmação exclusiva de sessão.</p>
                    <?php else: ?>
                        <?php foreach ($semAgape as $sa): ?>
                            <div class="flex items-center justify-between p-3 rounded-xl border border-white/5 bg-white/[0.02] text-xs">
                                <span class="font-semibold text-slate-300 truncate max-w-[12rem]"><?= htmlspecialchars((string) ($sa['nome'] ?? 'Obreiro')) ?></span>
                                <span class="text-slate-500 font-sans">CIM: <?= htmlspecialchars((string) ($sa['cim'] ?? '-')) ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

    <!-- Coluna Lateral (1/3) -->
    <div class="space-y-6">
        <!-- AGENDA FUTURA -->
        <div class="card depth-1 p-6">
            <div class="card-header border-b border-white/5 pb-3 mb-4">
                <h2 class="card-title text-white">Agenda de Banquetes</h2>
                <p class="card-subtitle mt-1">Próximos banquetes e estimativas de público.</p>
            </div>
            <div class="card-body space-y-3">
                <?php foreach ($sessoes as $sessao): ?>
                    <a href="/mestre-banquetes?sessao_id=<?= (int) ($sessao['id'] ?? 0) ?>" class="flex flex-col p-4 rounded-xl border border-white/5 bg-white/[0.02] hover:bg-white/5 transition text-xs">
                        <p class="font-bold text-white truncate"><?= htmlspecialchars($tituloSessao($sessao))?></p>
                        <p class="text-slate-400 mt-1 font-sans"><?= htmlspecialchars((string) ($sessao['data_hora_inicio'] ?? '')) ?></p>
                        <div class="mt-2.5 flex items-center justify-between border-t border-white/5 pt-2 text-[10px] text-slate-400">
                            <span>Sessão: <strong><?= (int) ($sessao['total_confirmados'] ?? 0) ?></strong></span>
                            <span>Ágape: <strong><?= (int) ($sessao['total_agape'] ?? 0) ?></strong></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- DEVERES DO MESTRE DE BANQUETES -->
        <div class="card depth-1 p-6">
            <div class="card-header border-b border-white/5 pb-3 mb-4">
                <h2 class="card-title text-white">Deveres do Cargo</h2>
            </div>
            <div class="card-body text-xs text-slate-400 space-y-3">
                <p>Ao Mestre de Banquetes cabe a administração ritualística e logística dos banquetes de Loja (ágape):</p>
                <ul class="list-disc pl-4 space-y-2">
                    <li>Supervisiona a cozinha, dispensa e estoque de mantimentos da Loja.</li>
                    <li>Estima e programa as compras para o ágape ritual de acordo com o número de confirmados no painel.</li>
                    <li>Escolhe em conjunto com a diretoria o modelo contábil de rateio das despesas.</li>
                    <li>Entrega cupons fiscais e receitas arrecadadas à Tesouraria para fins de auditoria interna.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../partials/erp_shell_close.php';
?>
