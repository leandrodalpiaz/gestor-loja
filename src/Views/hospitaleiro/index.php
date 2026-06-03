<?php
declare(strict_types=1);

// #############################################################################
// LÓGICA DE NEGÓCIO E HELPERS
// #############################################################################

$mensagemSucesso = $_SESSION['mensagem_sucesso'] ?? null;
$mensagemErro = $_SESSION['mensagem_erro'] ?? null;
unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']);

$resumo = isset($resumo) && is_array($resumo) ? $resumo : [];
$pendenciasVisita = isset($pendenciasVisita) && is_array($pendenciasVisita) ? $pendenciasVisita : [];
$ocorrencias = isset($ocorrencias) && is_array($ocorrencias) ? $ocorrencias : [];
$obreiros = isset($obreiros) && is_array($obreiros) ? $obreiros : [];
$movimentosTronco = isset($movimentosTronco) && is_array($movimentosTronco) ? $movimentosTronco : [];
$saldoTronco = (float) ($saldoTronco ?? 0.0);
$podeOperarOcorrencias = (bool) ($podeOperarOcorrencias ?? false);
$podeTratarFinanceiro = (bool) ($podeTratarFinanceiro ?? false);

$badgeStatus = static function(string $status): string {
    return match ($status) {
        'aberta' => 'badge-status-warning',
        'em_acompanhamento' => 'badge-status-info',
        'concluida' => 'badge-status-success',
        'cancelada' => 'badge-status-danger',
        default => 'badge-status-secondary',
    };
};

$badgePrioridade = static function(string $prioridade): string {
    return match ($prioridade) {
        'urgente' => 'bg-red-500/10 text-red-400 border border-red-500/20',
        'alta' => 'bg-orange-500/10 text-orange-400 border border-orange-500/20',
        'media' => 'bg-blue-500/10 text-blue-400 border border-blue-500/20',
        default => 'bg-slate-500/10 text-slate-400 border border-slate-500/10',
    };
};

// Formata a descrição para a prestação de contas pública anônima
$anonimizarMovimento = static function(array $m): string {
    $descricao = trim((string) ($m['descricao'] ?? ''));
    $tipo = trim((string) ($m['tipo'] ?? 'entrada'));
    $dataStr = !empty($m['data_mov']) ? date('d/m/Y', strtotime((string) $m['data_mov'])) : '';

    if ($tipo === 'entrada') {
        if ($descricao !== '') {
            return "Entrada no Tronco: " . htmlspecialchars($descricao);
        }
        return "Coleta do Tronco de Solidariedade realizada em sessão ritual.";
    }

    // Para saídas (Auxílios Assistenciais)
    if (preg_match('/Ocorrência\s*#(\d+)/i', $descricao, $matches)) {
        $idOcorr = $matches[1];
        if (str_contains(strtolower($descricao), 'venerável')) {
            return "Valor solicitado pelo Hospitaleiro ao Venerável Mestre no dia {$dataStr} para atender à solicitação #{$idOcorr} (sigilo preservado), devidamente registrada em balaústre.";
        }
        return "Valor solicitado pelo Hospitaleiro em Loja no dia {$dataStr} para atender à solicitação #{$idOcorr} (sigilo preservado), registrada no balaústre da sessão.";
    }

    return "Auxílio Assistencial Solidário aprovado e repassado para atendimento de demandas fraternas (sigilo preservado).";
};

// #############################################################################
// CONFIGURAÇÃO DO APP SHELL
// #############################################################################

$appShellEyebrow = 'Hospitalaria';
$appShellTitle = 'Hospitalaria & Tronco de Solidariedade';
$appShellDescription = $podeOperarOcorrencias 
    ? 'Painel de gestão assistencial, governança do Tronco de Beneficência, visitas e repasses de apoio.'
    : 'Prestação de contas do Tronco de Solidariedade da Loja (sigilo e transparência fraterna).';
$appShellActiveHref = '/assistencia';

require __DIR__ . '/../partials/erp_shell_open.php';
?>

<!-- Mensagens de Feedback -->
<?php if ($mensagemSucesso): ?>
    <div class="alert alert-success mb-6"><?= htmlspecialchars($mensagemSucesso) ?></div>
<?php endif; ?>
<?php if ($mensagemErro): ?>
    <div class="alert alert-danger mb-6"><?= htmlspecialchars($mensagemErro) ?></div>
<?php endif; ?>

<!-- Métricas Rápidas / Painel Financeiro do Tronco -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="card-metric border border-warning/20 bg-warning/5 text-warning">
        <p class="card-metric-label !text-warning/80">Saldo do Tronco</p>
        <p class="card-metric-value text-3xl font-bold">R$ <?= number_format($saldoTronco, 2, ',', '.') ?></p>
        <p class="card-metric-context text-[10px] text-slate-400 mt-1">Valores sob guarda da Tesouraria</p>
    </div>
    <div class="card-metric">
        <p class="card-metric-label">Ocorrências Ativas</p>
        <p class="card-metric-value"><?= (int) (($resumo['abertas'] ?? 0) + ($resumo['em_acompanhamento'] ?? 0)) ?></p>
        <p class="card-metric-context text-[10px] text-slate-400 mt-1">Necessitando atenção e acompanhamento</p>
    </div>
    <div class="card-metric">
        <p class="card-metric-label">Visitas Pendentes</p>
        <p class="card-metric-value"><?= count($pendenciasVisita) ?></p>
        <p class="card-metric-context text-[10px] text-slate-400 mt-1">Irmãos ou familiares aguardando visita</p>
    </div>
    <div class="card-metric">
        <p class="card-metric-label">Total Assistências</p>
        <p class="card-metric-value"><?= (int) ($resumo['total'] ?? 0) ?></p>
        <p class="card-metric-context text-[10px] text-slate-400 mt-1">Registros totais na gestão atual</p>
    </div>
</div>

<!-- Navegação por Abas -->
<?php $activeTab = $_GET['tab'] ?? 'assistencial'; ?>
<div class="flex border-b border-white/10 mb-6 gap-4">
    <button onclick="switchTab('assistencial')" id="tab-btn-assistencial" class="py-2.5 px-4 text-sm font-bold <?= $activeTab === 'assistencial' ? 'text-erp-gold border-erp-gold' : 'text-slate-400 border-transparent hover:text-white' ?> border-b-2 transition">
        Gestão Assistencial & Tronco
    </button>
    <button onclick="switchTab('preventivo')" id="tab-btn-preventivo" class="py-2.5 px-4 text-sm font-bold <?= $activeTab === 'preventivo' ? 'text-erp-gold border-erp-gold' : 'text-slate-400 border-transparent hover:text-white' ?> border-b-2 transition">
        Atenção Preventiva por Ausência
    </button>
</div>

<div id="secao-assistencial" class="<?= $activeTab === 'assistencial' ? '' : 'hidden' ?>">
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Coluna Principal (2/3) -->
    <div class="lg:col-span-2 space-y-8">
        
        <?php if ($podeOperarOcorrencias): ?>
            <!-- SEÇÃO DE GESTÃO - EXCLUSIVA PARA HOSPITALEIRO, SECRETÁRIO, TESOUREIRO E VENERÁVEL -->

            <!-- Painel Privado de Ocorrências Recentes e Solicitações de Apoio -->
            <div class="card depth-1">
                <div class="card-header border-b border-white/5 p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h2 class="card-title text-white">Ocorrências Assistenciais & Governança</h2>
                        <p class="card-subtitle mt-1">Controle de solicitações de auxílio financeiro e visitas.</p>
                    </div>
                    <span class="badge-status badge-status-primary text-xs uppercase">Painel de Governança Privado</span>
                </div>
                <div class="card-body p-6 divide-y divide-white/5 space-y-6">
                    <?php if (empty($ocorrencias)): ?>
                        <p class="text-center text-slate-400 py-10">Nenhuma ocorrência assistencial registrada.</p>
                    <?php else: ?>
                        <?php foreach ($ocorrencias as $oc): ?>
                            <div class="py-4 first:pt-0 last:pb-0 space-y-3">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                    <div>
                                        <span class="badge-status <?= $badgeStatus((string) ($oc['status'] ?? 'aberta')) ?> capitalize">
                                            <?= str_replace('_', ' ', (string) ($oc['status'] ?? 'aberta')) ?>
                                        </span>
                                        <span class="ml-2 inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium <?= $badgePrioridade((string) ($oc['prioridade'] ?? 'media')) ?> capitalize">
                                            <?= htmlspecialchars((string) ($oc['prioridade'] ?? 'media')) ?>
                                        </span>
                                        <span class="ml-2 text-xs text-slate-400">Ref: #<?= (int) ($oc['id'] ?? 0) ?></span>
                                    </div>
                                    <span class="text-xs text-slate-400"><?= !empty($oc['data_ocorrencia']) ? date('d/m/Y', strtotime((string) $oc['data_ocorrencia'])) : '-' ?></span>
                                </div>

                                <div class="bg-white/[0.01] border border-white/5 rounded-xl p-4">
                                    <p class="font-bold text-white text-base">
                                        <?= htmlspecialchars((string) ($oc['obreiro_nome'] ?? 'Sem obreiro vinculado')) ?>
                                        <?php if (!empty($oc['nome_familiar'])): ?>
                                            <span class="text-xs text-slate-400 font-normal"> &middot; Familiar: <?= htmlspecialchars((string) $oc['nome_familiar']) ?> (<?= htmlspecialchars((string) ($oc['parentesco'] ?? '')) ?>)</span>
                                        <?php endif; ?>
                                    </p>
                                    <p class="text-xs text-erp-gold mt-1 uppercase tracking-wider font-semibold">Tipo: <?= str_replace('_', ' ', (string) ($oc['tipo_ocorrencia'] ?? 'assistencia_geral')) ?></p>
                                    <p class="text-sm text-slate-300 mt-2 leading-relaxed"><?= nl2br(htmlspecialchars((string) ($oc['descricao'] ?? ''))) ?></p>

                                    <!-- Área de Apoio Financeiro e Governança -->
                                    <?php if (!empty($oc['necessita_apoio_financeiro']) || (float) ($oc['valor_solicitado'] ?? 0) > 0): ?>
                                        <div class="mt-3 pt-3 border-t border-white/5 grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                                            <div>
                                                <p class="text-slate-400">Valor Solicitado:</p>
                                                <p class="text-white font-semibold text-sm">R$ <?= number_format((float) ($oc['valor_solicitado'] ?? 0), 2, ',', '.') ?></p>
                                                <p class="text-slate-500 mt-0.5">Encaminhado para: <span class="capitalize text-slate-400"><?= htmlspecialchars((string) ($oc['encaminhar_para'] ?? 'nenhum')) ?></span></p>
                                            </div>
                                            <div>
                                                <p class="text-slate-400">Valor Aprovado (Venerável):</p>
                                                <p class="text-erp-gold font-bold text-sm">
                                                    <?= (float) ($oc['valor_aprovado'] ?? 0) > 0 ? 'R$ ' . number_format((float) $oc['valor_aprovado'], 2, ',', '.') : 'Aguardando decisão' ?>
                                                </p>
                                                <?php if (!empty($oc['observacao_status'])): ?>
                                                    <p class="text-slate-400 mt-1 italic">"<?= htmlspecialchars((string) $oc['observacao_status']) ?>"</p>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- Botão de Registrar Repasse (Hospitaleiro ou Tesoureiro dão baixa no saldo do Tronco) -->
                                        <?php if ((float) ($oc['valor_aprovado'] ?? 0) > 0 && (string) ($oc['status'] ?? '') !== 'concluida'): ?>
                                            <div class="mt-3 pt-2 flex justify-end">
                                                <form method="POST" action="/assistencia/ocorrencias/repasse" onsubmit="return confirm('Deseja registrar o repasse deste auxílio? O saldo do Tronco será deduzido em R$ <?= number_format((float) $oc['valor_aprovado'], 2, ',', '.') ?>.');">
                                                    <input type="hidden" name="ocorrencia_id" value="<?= (int) ($oc['id'] ?? 0) ?>">
                                                    <button type="submit" class="btn btn-success text-xs py-1.5 px-4 font-bold flex items-center gap-1.5">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                        Registrar Repasse (Saída do Tronco)
                                                    </button>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>

                                <!-- Ações de Mudança de Status -->
                                <form method="POST" action="/assistencia/ocorrencias/status" class="flex flex-col sm:flex-row items-end gap-3 mt-3">
                                    <input type="hidden" name="ocorrencia_id" value="<?= (int) ($oc['id'] ?? 0) ?>">
                                    <div class="w-full sm:w-44">
                                        <select name="status" class="form-select w-full py-1 text-xs">
                                            <option value="aberta" <?= ($oc['status'] ?? '') === 'aberta' ? 'selected' : '' ?>>Aberta</option>
                                            <option value="em_acompanhamento" <?= ($oc['status'] ?? '') === 'em_acompanhamento' ? 'selected' : '' ?>>Em acompanhamento</option>
                                            <option value="concluida" <?= ($oc['status'] ?? '') === 'concluida' ? 'selected' : '' ?>>Concluída</option>
                                            <option value="cancelada" <?= ($oc['status'] ?? '') === 'cancelada' ? 'selected' : '' ?>>Cancelada</option>
                                        </select>
                                    </div>
                                    <div class="flex-grow w-full">
                                        <input type="text" name="observacao_status" placeholder="Observações da alteração/motivo..." class="form-input w-full py-1 text-xs">
                                    </div>
                                    <button type="submit" class="btn border border-white/10 text-slate-300 hover:bg-white/5 py-1 px-4 text-xs font-semibold w-full sm:w-auto">Atualizar Status</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Pendências de Visita -->
            <div class="card depth-1">
                <div class="card-header border-b border-white/5 p-6">
                    <h2 class="card-title text-white">Pendências de Visita e Presença em Campo</h2>
                    <p class="card-subtitle mt-1">Encaminhamentos que pedem assistência ou presença física no lar.</p>
                </div>
                <div class="card-body p-6 space-y-4">
                    <?php if (empty($pendenciasVisita)): ?>
                        <p class="text-center text-slate-400 py-6 text-sm">Nenhuma visita pendente registrada no momento.</p>
                    <?php else: ?>
                        <?php foreach ($pendenciasVisita as $pv): ?>
                            <div class="rounded-xl border border-white/5 bg-white/[0.02] p-5">
                                <div class="flex flex-wrap items-start justify-between gap-3 border-b border-white/5 pb-3">
                                    <div>
                                        <p class="font-bold text-white"><?= htmlspecialchars((string) ($pv['obreiro_nome'] ?? 'Sem obreiro')) ?></p>
                                        <p class="text-xs text-slate-400 mt-0.5">Tipo: <?= htmlspecialchars((string) ($pv['tipo_ocorrencia'] ?? 'assistencia_geral')) ?> &middot; Prioridade: <span class="capitalize text-slate-300"><?= htmlspecialchars((string) ($pv['prioridade'] ?? 'media')) ?></span></p>
                                    </div>
                                    <span class="badge-status <?= $badgeStatus((string) ($pv['status'] ?? 'aberta')) ?> capitalize"><?= htmlspecialchars((string) ($pv['status'] ?? 'aberta')) ?></span>
                                </div>
                                <p class="mt-3 text-sm text-slate-300 leading-relaxed"><?= nl2br(htmlspecialchars((string) ($pv['descricao'] ?? ''))) ?></p>
                                
                                <form method="POST" action="/assistencia/ocorrencias/visita" class="mt-4 grid gap-3 md:grid-cols-[1fr_auto_auto] items-end">
                                    <input type="hidden" name="ocorrencia_id" value="<?= (int) ($pv['id'] ?? 0) ?>">
                                    <div>
                                        <label class="form-label text-[10px]">Observação da Visita</label>
                                        <input type="text" name="observacao_visita" placeholder="Ex: Visitado ontem, necessita de novos remédios." required class="form-input w-full py-1 text-xs">
                                    </div>
                                    <div>
                                        <label class="form-label text-[10px]">Agendar Retorno</label>
                                        <input type="date" name="data_proxima_acao" class="form-input py-1 text-xs">
                                    </div>
                                    <button type="submit" class="btn btn-primary text-xs py-1.5 px-4 font-bold h-9">Registrar Visita Realizada</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- PRESTAÇÃO DE CONTAS DO TRONCO (PÚBLICA E ANÔNIMA) -->
        <div class="card depth-1">
            <div class="card-header border-b border-white/5 p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="card-title text-white">Extrato e Prestação de Contas do Tronco</h2>
                    <p class="card-subtitle mt-1">Transparência financeira e sigilo dos irmãos atendidos.</p>
                </div>
                <span class="badge-status badge-status-secondary text-xs uppercase">Visível a Todos os Obreiros</span>
            </div>
            <div class="card-body p-6 space-y-4">
                <div class="overflow-x-auto">
                    <table class="table-base w-full">
                        <thead>
                            <tr class="border-b border-white/10 text-left text-xs uppercase tracking-wider text-slate-400">
                                <th class="pb-3 font-semibold">Data</th>
                                <th class="pb-3 font-semibold">Tipo</th>
                                <th class="pb-3 font-semibold">Descrição / Registro Ritual</th>
                                <th class="pb-3 font-semibold text-right">Valor</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5 text-sm">
                            <?php if (empty($movimentosTronco)): ?>
                                <tr>
                                    <td colspan="4" class="py-8 text-center text-slate-400">Nenhum movimento registrado no Tronco de Solidariedade.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($movimentosTronco as $mov): 
                                    $isEntrada = ($mov['tipo'] ?? 'entrada') === 'entrada';
                                ?>
                                    <tr class="hover:bg-white/[0.01] transition">
                                        <td class="py-3 text-slate-300"><?= !empty($mov['data_mov']) ? date('d/m/Y', strtotime((string) $mov['data_mov'])) : '-' ?></td>
                                        <td class="py-3">
                                            <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold <?= $isEntrada ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-red-500/10 text-red-400 border border-red-500/20' ?>">
                                                <?= $isEntrada ? 'Entrada' : 'Saída' ?>
                                            </span>
                                        </td>
                                        <td class="py-3 text-slate-300 max-w-md truncate" title="<?= $anonimizarMovimento($mov) ?>">
                                            <?= $anonimizarMovimento($mov) ?>
                                        </td>
                                        <td class="py-3 text-right font-semibold <?= $isEntrada ? 'text-emerald-400' : 'text-red-400' ?>">
                                            <?= $isEntrada ? '+' : '-' ?> R$ <?= number_format((float) ($mov['valor'] ?? 0.0), 2, ',', '.') ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- Coluna Lateral (1/3) -->
    <div class="space-y-6">
        
        <?php if ($podeOperarOcorrencias): ?>
            <!-- FORMULÁRIO DE NOVA OCORRÊNCIA -->
            <div class="card depth-1 p-6">
                <div class="card-header border-b border-white/5 pb-3 mb-4">
                    <h2 class="card-title text-white">Nova Ocorrência Assistencial</h2>
                    <p class="card-subtitle mt-1">Cadastrar demandas, apoio financeiro ou visitas.</p>
                </div>
                <div class="card-body">
                    <form method="POST" action="/assistencia/ocorrencias/salvar" class="space-y-4">
                        <div>
                            <label for="tipo_ocorrencia" class="form-label">Tipo de Ocorrência</label>
                            <select name="tipo_ocorrencia" id="tipo_ocorrencia" class="form-select w-full">
                                <option value="assistencia_geral">Assistência Geral</option>
                                <option value="saude">Saúde / Medicamento</option>
                                <option value="nascimento">Nascimento / Familiar</option>
                                <option value="falecimento">Falecimento / Funerário</option>
                                <option value="solidariedade">Solidariedade Geral</option>
                            </select>
                        </div>

                        <div>
                            <label for="obreiro_id" class="form-label">Obreiro Beneficiado</label>
                            <select name="obreiro_id" id="obreiro_id" class="form-select w-full">
                                <option value="">Não vincular obreiro diretamente</option>
                                <?php foreach ($obreiros as $ob): ?>
                                    <option value="<?= htmlspecialchars((string) ($ob['id'] ?? '')) ?>">
                                        <?= htmlspecialchars((string) ($ob['nome_historico'] ?? $ob['nome'] ?? '')) ?> - CIM <?= htmlspecialchars((string) ($ob['cim'] ?? '-')) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="nome_familiar" class="form-label">Familiar (Se aplicável)</label>
                                <input type="text" name="nome_familiar" id="nome_familiar" placeholder="Nome do familiar" class="form-input w-full">
                            </div>
                            <div>
                                <label for="parentesco" class="form-label">Parentesco</label>
                                <input type="text" name="parentesco" id="parentesco" placeholder="Ex: Esposa, Filho" class="form-input w-full">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="prioridade" class="form-label">Prioridade</label>
                                <select name="prioridade" id="prioridade" class="form-select w-full">
                                    <option value="baixa">Baixa</option>
                                    <option value="media" selected>Média</option>
                                    <option value="alta">Alta</option>
                                    <option value="urgente">Urgente</option>
                                </select>
                            </div>
                            <div>
                                <label for="encaminhar_para" class="form-label">Encaminhar Para</label>
                                <select name="encaminhar_para" id="encaminhar_para" class="form-select w-full">
                                    <option value="nenhum">Apenas Hospitalaria</option>
                                    <option value="veneravel">Venerável Mestre</option>
                                    <option value="tesoureiro">Tesoureiro</option>
                                    <option value="ambos">Venerável + Tesoureiro</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="data_ocorrencia" class="form-label">Data Ocorrência</label>
                                <input type="date" name="data_ocorrencia" id="data_ocorrencia" value="<?= date('Y-m-d') ?>" class="form-input w-full">
                            </div>
                            <div>
                                <label for="data_proxima_acao" class="form-label">Próximo Retorno</label>
                                <input type="date" name="data_proxima_acao" id="data_proxima_acao" class="form-input w-full">
                            </div>
                        </div>

                        <div class="bg-white/[0.02] border border-white/5 rounded-xl p-4 space-y-3">
                            <p class="text-xs font-bold text-erp-gold uppercase tracking-wider">Governança Financeira (Apoio)</p>
                            <label class="form-check-label flex items-center gap-2 text-xs">
                                <input type="checkbox" name="necessita_apoio_financeiro" value="1" class="form-checkbox" onchange="document.getElementById('solicitacao_valores').style.display = this.checked ? 'block' : 'none';">
                                Necessita Auxílio do Tronco
                            </label>
                            
                            <div id="solicitacao_valores" style="display: none;" class="space-y-2">
                                <label for="valor_solicitado" class="form-label text-[10px]">Valor Solicitado (R$)</label>
                                <input type="text" name="valor_solicitado" id="valor_solicitado" placeholder="Ex: 500,00" class="form-input w-full">
                                <p class="text-[10px] text-slate-400">O pedido passará pela aprovação do Venerável antes do repasse.</p>
                            </div>
                        </div>

                        <div>
                            <label for="descricao" class="form-label">Descrição da Demanda / Justificativa</label>
                            <textarea name="descricao" id="descricao" rows="3" required class="form-textarea w-full" placeholder="Detalhes da ocorrência e justificativa da necessidade de visita ou apoio."></textarea>
                        </div>

                        <div class="space-y-2 pt-2">
                            <label class="form-check-label flex items-center gap-2 text-xs">
                                <input type="checkbox" name="necessita_visita" value="1" class="form-checkbox" checked>
                                Necessita Visita Presencial do Hospitaleiro
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary w-full">Cadastrar Ocorrência</button>
                    </form>
                </div>
            </div>

            <!-- REGISTRAR MOVIMENTAÇÃO MANUAL DO TRONCO -->
            <?php if ($podeTratarFinanceiro || in_array('hospitaleiro', $roles, true)): ?>
                <div class="card depth-1 p-6">
                    <div class="card-header border-b border-white/5 pb-3 mb-4">
                        <h2 class="card-title text-white">Movimentação Manual do Tronco</h2>
                        <p class="card-subtitle mt-1">Registrar depósitos de sessões ou doações diretas.</p>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="/assistencia/tronco/registrar" class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="tipo_mov" class="form-label">Tipo</label>
                                    <select name="tipo" id="tipo_mov" class="form-select w-full">
                                        <option value="entrada">Entrada (Depósito)</option>
                                        <option value="saida">Saída (Retirada)</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="valor_mov" class="form-label">Valor (R$)</label>
                                    <input type="text" name="valor" id="valor_mov" required placeholder="0,00" class="form-input w-full">
                                </div>
                            </div>
                            <div>
                                <label for="desc_mov" class="form-label">Descrição / Motivo</label>
                                <input type="text" name="descricao" id="desc_mov" placeholder="Ex: Tronco coletado na sessão do dia..." class="form-input w-full">
                            </div>
                            <button type="submit" class="btn border border-white/10 text-slate-300 hover:bg-white/5 py-2 px-4 text-sm font-semibold w-full">
                                Registrar Movimento
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- FUNÇÕES DO CARGO -->
        <div class="card depth-1 p-6">
            <div class="card-header border-b border-white/5 pb-3 mb-4">
                <h2 class="card-title text-white">Hospitalaria & Governança</h2>
            </div>
            <div class="card-body text-xs text-slate-400 space-y-3">
                <p>O Hospitaleiro exerce a caridade e a solidariedade de forma sigilosa e organizada:</p>
                <ul class="list-disc pl-4 space-y-2">
                    <li>Coleta as contribuições em sessão ritual (Tronco de Solidariedade) e entrega à Tesouraria.</li>
                    <li>Mantém o saldo atualizado e zela pelo patrimônio de beneficência da Loja.</li>
                    <li>Visita obreiros enfermos ou em dificuldade, e estende a mão às viúvas e familiares.</li>
                    <li>Solicita verbas extraordinárias ao Venerável Mestre com justificativas sigilosas.</li>
                </ul>
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
    if (tabId === 'assistencial') {
        document.getElementById('secao-assistencial').classList.remove('hidden');
        document.getElementById('secao-preventivo').classList.add('hidden');
        
        document.getElementById('tab-btn-assistencial').classList.add('text-erp-gold', 'border-erp-gold');
        document.getElementById('tab-btn-assistencial').classList.remove('text-slate-400', 'border-transparent');
        
        document.getElementById('tab-btn-preventivo').classList.remove('text-erp-gold', 'border-erp-gold');
        document.getElementById('tab-btn-preventivo').classList.add('text-slate-400', 'border-transparent');
    } else {
        document.getElementById('secao-assistencial').classList.add('hidden');
        document.getElementById('secao-preventivo').classList.remove('hidden');
        
        document.getElementById('tab-btn-preventivo').classList.add('text-erp-gold', 'border-erp-gold');
        document.getElementById('tab-btn-preventivo').classList.remove('text-slate-400', 'border-transparent');
        
        document.getElementById('tab-btn-assistencial').classList.remove('text-erp-gold', 'border-erp-gold');
        document.getElementById('tab-btn-assistencial').classList.add('text-slate-400', 'border-transparent');
    }
}
</script>

<?php
require_once __DIR__ . '/../partials/erp_shell_close.php';
?>
