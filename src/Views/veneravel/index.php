<?php
declare(strict_types=1);

// #############################################################################
// LÓGICA DE NEGÓCIO E HELPERS
// #############################################################################

$mensagemSucesso = $_SESSION['mensagem_sucesso'] ?? null;
$mensagemErro = $_SESSION['mensagem_erro'] ?? null;
unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']);

$mes = (int) ($view['mes'] ?? (int) date('n'));
$ano = (int) ($view['ano'] ?? (int) date('Y'));
$inicioMes = $view['inicio_mes'] ?? null;
$fimMes = $view['fim_mes'] ?? null;
$sessoesMes = $view['sessoes_mes'] ?? [];
$convitesMes = $view['convites_mes'] ?? [];
$tesourariaResumo = $view['tesouraria_resumo'] ?? [];
$tesourariaSerie = $view['tesouraria_serie'] ?? [];
$tesourariaSomatorios = $view['tesouraria_somatorios'] ?? [];
$balaustresPendentes = $view['balaustres_pendentes_decisao'] ?? [];
$obreirosAtrasoFraterno = $view['obreiros_atraso_fraterno'] ?? [];
$auxiliosPendentes = $view['auxilios_pendentes'] ?? [];

$formatCurrency = static fn (float $value): string => 'R$ ' . number_format($value, 2, ',', '.');
$formatMesAno = static fn (int $m, int $a): string => str_pad((string) $m, 2, '0', STR_PAD_LEFT) . '/' . $a;

// App shell
$appShellEyebrow = 'Painel Executivo';
$appShellTitle = 'Venerável Mestre';
$appShellDescription = 'Acompanhamento executivo, homologação de balaústres, agenda de sessões e governança de auxílios da Hospitalaria.';
$appShellActiveHref = '/veneravel';
$appShellActions = [
    ['label' => 'Painel da Loja', 'href' => '/dashboard'],
    ['label' => 'Secretaria', 'href' => '/secretaria'],
    ['label' => 'Tesouraria', 'href' => '/tesouraria/caixa'],
];

require __DIR__ . '/../partials/erp_shell_open.php';
?>

<!-- Mensagens de Feedback -->
<?php if ($mensagemSucesso): ?>
    <div class="alert alert-success mb-6"><?= htmlspecialchars((string) $mensagemSucesso) ?></div>
<?php endif; ?>
<?php if ($mensagemErro): ?>
    <div class="alert alert-danger mb-6"><?= htmlspecialchars((string) $mensagemErro) ?></div>
<?php endif; ?>

<!-- ALERTA DE AUXÍLIOS DA HOSPITALARIA PENDENTES -->
<?php if (!empty($auxiliosPendentes)): ?>
    <div class="card depth-1 border border-warning/30 bg-warning/5 p-6 mb-8">
        <div class="card-header border-b border-warning/10 pb-3 mb-4 flex items-center justify-between">
            <div>
                <h2 class="card-title text-warning flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    Auxílios Assistenciais Pendentes de Aprovação
                </h2>
                <p class="card-subtitle text-warning/75">Solicitações de apoio financeiro registradas pelo Hospitaleiro.</p>
            </div>
            <span class="badge-status badge-status-warning uppercase text-[10px]"><?= count($auxiliosPendentes) ?> pendente(s)</span>
        </div>
        <div class="card-body space-y-6">
            <?php foreach ($auxiliosPendentes as $ap): ?>
                <div class="bg-black/25 border border-warning/10 rounded-xl p-5 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-white/5 pb-2">
                        <div>
                            <span class="font-bold text-white text-base">
                                <?= htmlspecialchars((string) ($ap['obreiro_nome'] ?? 'Obreiro não especificado')) ?>
                            </span>
                            <?php if (!empty($ap['nome_familiar'])): ?>
                                <span class="text-xs text-slate-400"> (Familiar: <?= htmlspecialchars((string) $ap['nome_familiar']) ?> - <?= htmlspecialchars((string) ($ap['parentesco'] ?? '')) ?>)</span>
                            <?php endif; ?>
                            <p class="text-xs text-erp-gold mt-0.5 uppercase tracking-wider font-semibold">Hospitalaria &middot; Tipo: <?= str_replace('_', ' ', (string) ($ap['tipo_ocorrencia'] ?? 'assistencia_geral')) ?></p>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] text-slate-400">Solicitado em</p>
                            <p class="text-xs text-white font-medium"><?= !empty($ap['data_ocorrencia']) ? date('d/m/Y', strtotime((string) $ap['data_ocorrencia'])) : '-' ?></p>
                        </div>
                    </div>

                    <div class="text-sm text-slate-300">
                        <p class="font-semibold text-slate-400 mb-1">Descrição / Justificativa:</p>
                        <p class="leading-relaxed bg-black/10 p-3 rounded-lg border border-white/5">"<?= nl2br(htmlspecialchars((string) ($ap['descricao'] ?? ''))) ?>"</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-white/[0.01] p-3 rounded-xl border border-white/5">
                        <div>
                            <p class="text-xs text-slate-400">Valor Solicitado:</p>
                            <p class="text-xl font-bold text-white mt-1">R$ <?= number_format((float) ($ap['valor_solicitado'] ?? 0), 2, ',', '.') ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400">Encaminhado Para:</p>
                            <p class="text-xs text-slate-200 mt-1.5 capitalize font-medium"><?= htmlspecialchars((string) ($ap['encaminhar_para'] ?? 'veneravel')) ?></p>
                        </div>
                    </div>

                    <!-- Formulário de Decisão -->
                    <form method="POST" action="/veneravel/assistencia/decidir" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end pt-2 border-t border-white/5">
                        <input type="hidden" name="ocorrencia_id" value="<?= (int) ($ap['id'] ?? 0) ?>">
                        
                        <div>
                            <label class="form-label text-slate-300">Valor Aprovado (R$)</label>
                            <input type="text" name="valor_aprovado" value="<?= number_format((float) ($ap['valor_solicitado'] ?? 0), 2, ',', '') ?>" required class="form-input w-full">
                        </div>
                        
                        <div>
                            <label class="form-label text-slate-300">Justificativa / Observação</label>
                            <input type="text" name="justificativa" placeholder="Ex: Aprovado conforme limites do Tronco." class="form-input w-full">
                        </div>

                        <div class="flex gap-2">
                            <button type="submit" name="acao" value="aprovar" class="btn btn-success flex-grow text-xs font-bold py-2 px-3">
                                Aprovar Auxílio
                            </button>
                            <button type="submit" name="acao" value="recusar" class="btn btn-danger text-xs font-bold py-2 px-3" onclick="return confirm('Deseja realmente recusar esta solicitação assistencial?');">
                                Recusar
                            </button>
                        </div>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Navegação por Abas -->
<?php $activeTab = $_GET['tab'] ?? 'executiva'; ?>
<div class="flex border-b border-white/10 mb-6 gap-4">
    <button onclick="switchTab('executiva')" id="tab-btn-executiva" class="py-2.5 px-4 text-sm font-bold <?= $activeTab === 'executiva' ? 'text-erp-gold border-erp-gold' : 'text-slate-400 border-transparent hover:text-white' ?> border-b-2 transition">
        Resumo Executivo
    </button>
    <button onclick="switchTab('preventivo')" id="tab-btn-preventivo" class="py-2.5 px-4 text-sm font-bold <?= $activeTab === 'preventivo' ? 'text-erp-gold border-erp-gold' : 'text-slate-400 border-transparent hover:text-white' ?> border-b-2 transition">
        Atenção Preventiva por Ausência
    </button>
</div>

<div id="secao-executiva" class="<?= $activeTab === 'executiva' ? '' : 'hidden' ?>">
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Coluna Principal (2/3) -->
    <div class="lg:col-span-2 space-y-8">
        
        <!-- AGENDA DO MÊS -->
        <div class="card depth-1">
            <div class="card-header border-b border-white/5 p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="card-title text-white">Agenda do Mês</h2>
                    <p class="card-subtitle mt-1">Sessões e compromissos ritualísticos (<?= htmlspecialchars($formatMesAno($mes, $ano)) ?>).</p>
                </div>
                <form method="GET" action="/veneravel" class="flex items-center gap-2">
                    <select name="mes" class="form-select py-1 text-xs w-20">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>" <?= $m === $mes ? 'selected' : '' ?>><?= str_pad((string) $m, 2, '0', STR_PAD_LEFT) ?></option>
                        <?php endfor; ?>
                    </select>
                    <input name="ano" type="number" min="2000" max="2100" class="form-input py-1 text-xs w-20" value="<?= (int) $ano ?>">
                    <button type="submit" class="btn border border-white/10 text-slate-300 hover:bg-white/5 py-1 px-3 text-xs font-semibold">Ok</button>
                </form>
            </div>
            
            <div class="card-body p-6 space-y-6">
                <div class="grid grid-cols-2 gap-4">
                    <div class="rounded-xl border border-white/5 bg-white/[0.01] p-4">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Sessões Programadas</p>
                        <p class="mt-2 text-3xl font-black text-white"><?= count($sessoesMes) ?></p>
                        <p class="mt-1 text-[10px] text-slate-400">Definidas pelo calendário oficial</p>
                    </div>
                    <div class="rounded-xl border border-white/5 bg-white/[0.01] p-4">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Convites Externos</p>
                        <p class="mt-2 text-3xl font-black text-white"><?= count($convitesMes) ?></p>
                        <p class="mt-1 text-[10px] text-slate-400">Intercâmbio com Lojas Coirmãs</p>
                    </div>
                </div>

                <div class="space-y-3">
                    <?php if (empty($sessoesMes)): ?>
                        <p class="text-center text-slate-400 py-6 text-sm">Nenhuma sessão registrada para este período.</p>
                    <?php else: ?>
                        <?php foreach (array_slice($sessoesMes, 0, 8) as $sessao): ?>
                            <div class="rounded-xl border border-white/5 bg-white/[0.01] p-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
                                <div>
                                    <p class="font-bold text-white"><?= htmlspecialchars((string) ($sessao['titulo'] ?? 'Sessão')) ?></p>
                                    <p class="text-xs text-slate-400 mt-1"><?= htmlspecialchars((string) ($sessao['data_hora_inicio'] ?? '')) ?></p>
                                </div>
                                <div class="flex flex-wrap items-center gap-2 justify-end">
                                    <span class="badge-status badge-status-secondary text-xs capitalize"><?= htmlspecialchars((string) ($sessao['status'] ?? 'planejada')) ?></span>
                                    <div class="flex flex-wrap gap-1.5">
                                        <form method="POST" action="/veneravel/sessoes/publicar">
                                            <input type="hidden" name="sessao_id" value="<?= (int) ($sessao['id'] ?? 0) ?>">
                                            <button type="submit" class="btn border border-white/10 text-slate-300 hover:bg-white/5 py-1 px-2.5 text-[10px] font-semibold uppercase tracking-wider">Publicar</button>
                                        </form>
                                        <form method="POST" action="/veneravel/sessoes/realizar">
                                            <input type="hidden" name="sessao_id" value="<?= (int) ($sessao['id'] ?? 0) ?>">
                                            <button type="submit" class="btn border border-white/10 text-slate-300 hover:bg-white/5 py-1 px-2.5 text-[10px] font-semibold uppercase tracking-wider">Realizar</button>
                                        </form>
                                        <form method="POST" action="/veneravel/sessoes/reabrir">
                                            <input type="hidden" name="sessao_id" value="<?= (int) ($sessao['id'] ?? 0) ?>">
                                            <button type="submit" class="btn border border-white/10 text-slate-300 hover:bg-white/5 py-1 px-2.5 text-[10px] font-semibold uppercase tracking-wider">Reabrir</button>
                                        </form>
                                        <form method="POST" action="/veneravel/sessoes/cancelar" onsubmit="return confirm('Confirmar cancelamento desta sessão?');">
                                            <input type="hidden" name="sessao_id" value="<?= (int) ($sessao['id'] ?? 0) ?>">
                                            <button type="submit" class="btn btn-danger py-1 px-2.5 text-[10px] font-semibold uppercase tracking-wider">Cancelar</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <div class="pt-2">
                            <a class="btn border border-white/10 text-slate-300 hover:bg-white/5 w-full text-center text-xs font-semibold py-2.5 block" href="/secretaria">
                                Ver Agenda Completa na Secretaria
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- TESOURARIA (RESUMO EXECUTIVO COM GRÁFICO DARK) -->
        <div class="card depth-1">
            <div class="card-header border-b border-white/5 p-6">
                <h2 class="card-title text-white">Tesouraria & Finanças (Resumo Executivo)</h2>
                <p class="card-subtitle mt-1">Visão geral macro do caixa, sem exposição de transações individuais.</p>
            </div>
            <div class="card-body p-6 space-y-6">
                <?php
                $fluxo = $tesourariaResumo['fluxo_atual'] ?? ['entradas' => 0, 'saidas' => 0, 'resultado' => 0];
                $saldoAtual = (float) ($tesourariaResumo['saldo_atual'] ?? 0);
                $previsao = (float) ($tesourariaResumo['previsao_fim_mes'] ?? 0);
                ?>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="card-metric border border-white/5 bg-white/[0.01]">
                        <p class="card-metric-label">Fluxo Atual (Mês)</p>
                        <p class="card-metric-value text-2xl font-bold text-white"><?= htmlspecialchars($formatCurrency((float) ($fluxo['resultado'] ?? 0))) ?></p>
                        <p class="text-[10px] text-slate-400 mt-1.5">Entradas: <?= htmlspecialchars($formatCurrency((float) ($fluxo['entradas'] ?? 0))) ?> · Saídas: <?= htmlspecialchars($formatCurrency((float) ($fluxo['saidas'] ?? 0))) ?></p>
                    </div>
                    <div class="card-metric border border-white/5 bg-white/[0.01]">
                        <p class="card-metric-label">Saldo em Caixa</p>
                        <p class="card-metric-value text-2xl font-bold text-white"><?= htmlspecialchars($formatCurrency($saldoAtual)) ?></p>
                        <p class="text-[10px] text-slate-400 mt-1.5">Total consolidado das contas da Loja</p>
                    </div>
                    <div class="card-metric border border-white/5 bg-white/[0.01]">
                        <p class="card-metric-label">Previsão Fim do Mês</p>
                        <p class="card-metric-value text-2xl font-bold text-white"><?= htmlspecialchars($formatCurrency($previsao)) ?></p>
                        <p class="text-[10px] text-slate-400 mt-1.5">Projeção estimada de adimplência</p>
                    </div>
                </div>

                <div class="rounded-xl border border-white/5 bg-white/[0.01] p-5 space-y-4">
                    <p class="text-xs font-bold text-erp-gold uppercase tracking-wider">Somatórios do Ano por Categoria</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                        <div class="flex items-center justify-between border-b border-white/5 pb-2">
                            <span class="text-slate-300 font-semibold">Despesas Fixas</span>
                            <span class="text-white"><?= htmlspecialchars($formatCurrency((float) ($tesourariaSomatorios['despesas_fixas'] ?? 0))) ?></span>
                        </div>
                        <div class="flex items-center justify-between border-b border-white/5 pb-2">
                            <span class="text-slate-300 font-semibold">Ágape</span>
                            <span class="text-white"><?= htmlspecialchars($formatCurrency((float) ($tesourariaSomatorios['despesas_agape'] ?? 0))) ?></span>
                        </div>
                        <div class="flex items-center justify-between border-b border-white/5 pb-2">
                            <span class="text-slate-300 font-semibold">Material Expediente</span>
                            <span class="text-white"><?= htmlspecialchars($formatCurrency((float) ($tesourariaSomatorios['despesas_expediente'] ?? 0))) ?></span>
                        </div>
                        <div class="flex items-center justify-between border-b border-white/5 pb-2">
                            <span class="text-slate-300 font-semibold">Cozinha e Dispensa</span>
                            <span class="text-white"><?= htmlspecialchars($formatCurrency((float) ($tesourariaSomatorios['despesas_cozinha_mercado'] ?? 0))) ?></span>
                        </div>
                        <div class="flex items-center justify-between border-b border-white/5 pb-2">
                            <span class="text-slate-300 font-semibold">Entradas Fixas</span>
                            <span class="text-emerald-400"><?= htmlspecialchars($formatCurrency((float) ($tesourariaSomatorios['entradas_fixas'] ?? 0))) ?></span>
                        </div>
                        <div class="flex items-center justify-between border-b border-white/5 pb-2">
                            <span class="text-slate-300 font-semibold">Mensalidades</span>
                            <span class="text-emerald-400"><?= htmlspecialchars($formatCurrency((float) ($tesourariaSomatorios['entradas_mensalidades'] ?? 0))) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Comparativo em Barras -->
                <div class="rounded-xl border border-white/5 bg-white/[0.01] p-5 space-y-4">
                    <p class="text-xs font-bold text-erp-gold uppercase tracking-wider">Comparativo Mensal (Resultado Financeiro)</p>
                    <div class="grid grid-cols-12 gap-2 items-end pt-6" style="min-height: 140px;">
                        <?php
                        $serieAnoBase = array_values(array_filter($tesourariaSerie, static fn (array $p): bool => (int) ($p['ano'] ?? 0) === $ano));
                        $valores = array_map(static fn (array $p): float => (float) ($p['resultado'] ?? 0), $serieAnoBase);
                        $maxAbs = max(1.0, max(array_map('abs', $valores ?: [0.0])));
                        for ($m = 1; $m <= 12; $m++):
                            $ponto = null;
                            foreach ($serieAnoBase as $linha) {
                                if ((int) ($linha['mes'] ?? 0) === $m) { $ponto = $linha; break; }
                            }
                            $resultado = (float) ($ponto['resultado'] ?? 0);
                            $altura = (int) round((abs($resultado) / $maxAbs) * 100);
                            $classe = $resultado >= 0 
                                ? 'bg-emerald-500/60 border border-emerald-500/40 shadow-md shadow-emerald-500/5' 
                                : 'bg-red-500/60 border border-red-500/40 shadow-md shadow-red-500/5';
                        ?>
                            <div class="flex flex-col items-center justify-end gap-1.5 h-full" title="<?= htmlspecialchars($formatMesAno($m, $ano)) ?>: <?= htmlspecialchars($formatCurrency($resultado)) ?>">
                                <div class="w-full rounded-md <?= $classe ?>" style="height: <?= max(8, $altura) ?>px;"></div>
                                <div class="text-[9px] font-bold text-slate-400"><?= str_pad((string) $m, 2, '0', STR_PAD_LEFT) ?></div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Coluna Lateral (1/3) -->
    <div class="space-y-6">
        
        <!-- BALAÚSTRES PARA DECISÃO -->
        <div class="card depth-1 p-6">
            <div class="card-header border-b border-white/5 pb-3 mb-4">
                <h2 class="card-title text-white">Balaústres para Homologação</h2>
                <p class="card-subtitle mt-1">Revisão, sugestão de edição e liberação para votação.</p>
            </div>
            <div class="card-body space-y-3">
                <?php if (empty($balaustresPendentes)): ?>
                    <p class="text-xs text-slate-400 py-4 text-center">Nenhum balaústre aguardando revisão.</p>
                <?php else: ?>
                    <?php foreach (array_slice($balaustresPendentes, 0, 6) as $balaustre): ?>
                        <a href="/veneravel/balaustre/visualizar?id=<?= (int) ($balaustre['id'] ?? 0) ?>" class="flex items-center justify-between p-4 rounded-xl border border-white/5 bg-white/[0.02] hover:bg-white/5 transition text-sm">
                            <div class="min-w-0 flex-grow pr-3">
                                <p class="font-bold text-white truncate"><?= htmlspecialchars((string) ($balaustre['sessao_titulo'] ?? $balaustre['numero_balaustre'] ?? 'Balaústre')) ?></p>
                                <p class="text-xs text-slate-400 mt-1 truncate">Status: <span class="capitalize text-slate-300"><?= htmlspecialchars((string) ($balaustre['status'] ?? 'rascunho')) ?></span></p>
                            </div>
                            <span class="badge-status badge-status-warning text-xs shrink-0">Revisar</span>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
                <div class="pt-2">
                    <a class="btn border border-white/10 text-slate-300 hover:bg-white/5 w-full text-center text-xs font-semibold py-2.5 block" href="/secretaria/balaustres">
                        Ver Todos na Secretaria
                    </a>
                </div>
            </div>
        </div>

        <!-- ACOMPANHAMENTO FRATERNO (INADIMPLÊNCIA MACRO) -->
        <div class="card depth-1 p-6">
            <div class="card-header border-b border-white/5 pb-3 mb-4">
                <h2 class="card-title text-white">Acompanhamento Fraterno</h2>
                <p class="card-subtitle mt-1">Alerta discreto de obreiros afastados ou com débitos de mensalidade.</p>
            </div>
            <div class="card-body space-y-4">
                <?php if (empty($obreirosAtrasoFraterno)): ?>
                    <p class="text-xs text-slate-400 py-4 text-center">Nenhum atraso fraterno considerado pendente.</p>
                <?php else: ?>
                    <div class="rounded-xl border border-white/5 bg-white/[0.01] p-4 text-center">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Contatos recomendados</p>
                        <p class="mt-2 text-3xl font-black text-white"><?= count($obreirosAtrasoFraterno) ?></p>
                    </div>
                    
                    <div class="space-y-2">
                        <?php foreach ($obreirosAtrasoFraterno as $ob): ?>
                            <div class="flex items-center justify-between p-3 rounded-xl border border-white/5 bg-white/[0.02]">
                                <span class="font-semibold text-white text-xs truncate max-w-[12rem]"><?= htmlspecialchars((string) ($ob['nome'] ?? 'Obreiro')) ?></span>
                                <span class="inline-flex items-center rounded-md bg-red-500/10 px-2 py-0.5 text-[10px] font-bold text-red-400 border border-red-500/20 uppercase">Atraso</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="pt-2">
                        <a class="btn border border-white/10 text-slate-300 hover:bg-white/5 w-full text-center text-xs font-semibold py-2.5 block" href="/tesouraria/obrigacoes">
                            Ver Obrigações na Tesouraria
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- MONITORAMENTO SECRETARIA -->
        <div class="card depth-1 p-6">
            <div class="card-header border-b border-white/5 pb-3 mb-4">
                <h2 class="card-title text-white">Monitoramento de Secretaria</h2>
                <p class="card-subtitle mt-1">Controle de fluxos operacionais.</p>
            </div>
            <div class="card-body space-y-3">
                <a href="/secretaria/balaustres" class="flex items-center justify-between rounded-xl border border-white/5 bg-white/[0.02] p-4 hover:bg-white/5 transition text-xs">
                    <span class="text-slate-300">Balaústres Pendentes: <strong><?= count($balaustresPendentes) ?></strong></span>
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </a>
                <a href="/secretaria/convites-externos" class="flex items-center justify-between rounded-xl border border-white/5 bg-white/[0.02] p-4 hover:bg-white/5 transition text-xs">
                    <span class="text-slate-300">Convites no Mês: <strong><?= count($convitesMes) ?></strong></span>
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </a>
                <a href="/secretaria" class="flex items-center justify-between rounded-xl border border-white/5 bg-white/[0.02] p-4 hover:bg-white/5 transition text-xs">
                    <span class="text-slate-300">Agenda de Sessões: <strong><?= count($sessoesMes) ?></strong></span>
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </a>
            </div>
        </div>

    </div>
</div>
</div>

<div id="secao-preventivo" class="<?= $activeTab === 'preventivo' ? '' : 'hidden' ?>">
    <?php include __DIR__ . '/../vida_loja/partial_sinais_e_contatos.php'; ?>
</div>

<script>
function switchTab(tabId) {
    if (tabId === 'executiva') {
        document.getElementById('secao-executiva').classList.remove('hidden');
        document.getElementById('secao-preventivo').classList.add('hidden');
        
        document.getElementById('tab-btn-executiva').classList.add('text-erp-gold', 'border-erp-gold');
        document.getElementById('tab-btn-executiva').classList.remove('text-slate-400', 'border-transparent');
        
        document.getElementById('tab-btn-preventivo').classList.remove('text-erp-gold', 'border-erp-gold');
        document.getElementById('tab-btn-preventivo').classList.add('text-slate-400', 'border-transparent');
    } else {
        document.getElementById('secao-executiva').classList.add('hidden');
        document.getElementById('secao-preventivo').classList.remove('hidden');
        
        document.getElementById('tab-btn-preventivo').classList.add('text-erp-gold', 'border-erp-gold');
        document.getElementById('tab-btn-preventivo').classList.remove('text-slate-400', 'border-transparent');
        
        document.getElementById('tab-btn-executiva').classList.remove('text-erp-gold', 'border-erp-gold');
        document.getElementById('tab-btn-executiva').classList.add('text-slate-400', 'border-transparent');
    }
}
</script>

<?php require __DIR__ . '/../partials/erp_shell_close.php'; ?>
