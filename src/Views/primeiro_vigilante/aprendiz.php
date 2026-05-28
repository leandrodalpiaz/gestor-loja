<?php
declare(strict_types=1);

// #############################################################################
// LÓGICA DE NEGÓCIO E HELPERS
// #############################################################################

$mensagemSucesso = $_SESSION['mensagem_sucesso'] ?? null;
$mensagemErro = $_SESSION['mensagem_erro'] ?? null;
unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']);

$nomeAprendiz = (string) ($aprendiz['nome_historico'] ?? $aprendiz['nome'] ?? 'Aprendiz');
$etapaAtualOrdem = (int) ($etapaAtual['etapa_ordem'] ?? 1);
$etapaAtualTitulo = (string) ($etapaAtual['titulo_etapa'] ?? '');
$etapaAtualStatus = (string) ($etapaAtual['status'] ?? 'nao_iniciado');
$percentual = (int) ($resumoTrilha['percentual_conclusao'] ?? 0);

$badgeStatus = static function(string $status): string {
    return match ($status) {
        'nao_iniciado' => 'badge-status-secondary',
        'em_andamento' => 'badge-status-info',
        'concluido' => 'badge-status-success',
        'aguardando_devolutiva' => 'badge-status-warning',
        'certificado_solicitado' => 'badge-status-primary',
        default => 'badge-status-secondary',
    };
};

// #############################################################################
// CONFIGURAÇÃO DO APP SHELL
// #############################################################################

$appShellEyebrow = $somenteProprio ? 'Autoacompanhamento' : 'Acompanhamento Formativo';
$appShellTitle = htmlspecialchars($nomeAprendiz);
$appShellDescription = $somenteProprio ? 'Acompanhe sua trilha, sua leitura orientada e a situação do certificado formativo.' : 'Linha de mentoria individual do Aprendiz com trilha, leitura sugerida, orientações e pedido formal de certificado.';
$appShellActiveHref = $somenteProprio ? '/dashboard' : '/primeiro-vigilante';

require __DIR__ . '/../partials/erp_shell_open.php';
?>

<!-- Botões de Navegação -->
<div class="mb-6 flex flex-wrap gap-3">
    <a href="<?= $somenteProprio ? '/dashboard' : '/primeiro-vigilante' ?>" class="btn border border-white/10 text-slate-300 hover:bg-white/5 py-2 px-4 text-sm font-semibold">Voltar</a>
    <?php if (!$somenteProprio): ?>
        <a href="/obreiros" class="btn border border-white/10 text-slate-300 hover:bg-white/5 py-2 px-4 text-sm font-semibold">Lista de Obreiros</a>
        <a href="/biblioteca" class="btn border border-white/10 text-slate-300 hover:bg-white/5 py-2 px-4 text-sm font-semibold">Biblioteca</a>
    <?php endif; ?>
</div>

<!-- Mensagens de Feedback -->
<?php if ($mensagemSucesso): ?>
    <div class="alert alert-success mb-6"><?= htmlspecialchars($mensagemSucesso) ?></div>
<?php endif; ?>
<?php if ($mensagemErro): ?>
    <div class="alert alert-danger mb-6"><?= htmlspecialchars($mensagemErro) ?></div>
<?php endif; ?>
<?php if (!empty($avisoInfra)): ?>
    <div class="alert alert-warning mb-6"><?= htmlspecialchars((string) $avisoInfra) ?></div>
<?php endif; ?>

<!-- Métricas Rápidas -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-8">
    <div class="card-metric">
        <p class="card-metric-label">Etapa Atual</p>
        <p class="card-metric-value"><?= $etapaAtualOrdem ?></p>
        <p class="card-metric-context font-sans text-xs mt-1 text-slate-400 max-w-full truncate" title="<?= htmlspecialchars($etapaAtualTitulo) ?>"><?= htmlspecialchars($etapaAtualTitulo) ?></p>
    </div>
    <div class="card-metric">
        <p class="card-metric-label">Status Atual</p>
        <p class="card-metric-value text-2xl capitalize mt-1"><?= str_replace('_', ' ', htmlspecialchars($etapaAtualStatus)) ?></p>
    </div>
    <div class="card-metric">
        <p class="card-metric-label">Etapas Concluídas</p>
        <p class="card-metric-value"><?= (int) ($resumoTrilha['total_concluidas'] ?? 0) ?> / <?= (int) ($resumoTrilha['total_etapas'] ?? 0) ?></p>
    </div>
    <div class="card-metric">
        <p class="card-metric-label">Conclusão da Trilha</p>
        <p class="card-metric-value"><?= $percentual ?>%</p>
    </div>
</div>

<!-- Formulários de Gestão (Apenas para 1º Vigilante em Modo Mentoria) -->
<?php if (!$somenteProprio): ?>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <div class="card depth-1 p-6">
            <div class="card-header border-b border-white/5 pb-3 mb-4">
                <h2 class="card-title">Atualizar Etapa da Trilha</h2>
                <p class="card-subtitle">Registre o andamento, recebimento de peça e envie orientações de estudo.</p>
            </div>
            <div class="card-body">
                <form action="/primeiro-vigilante/trilha/atualizar" method="POST" class="space-y-4">
                    <input type="hidden" name="aprendiz_id" value="<?= htmlspecialchars((string) ($aprendiz['id'] ?? '')) ?>">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="etapa_ordem" class="form-label">Etapa</label>
                            <select name="etapa_ordem" id="etapa_ordem" class="form-select w-full">
                                <?php foreach ($etapas as $etapa): ?>
                                    <option value="<?= (int) ($etapa['etapa_ordem'] ?? 0) ?>" <?= (int) ($etapa['etapa_ordem'] ?? 0) === $etapaAtualOrdem ? 'selected' : '' ?>>
                                        Etapa <?= (int) ($etapa['etapa_ordem'] ?? 0) ?> - <?= htmlspecialchars((string) ($etapa['titulo_etapa'] ?? '')) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="form-select w-full">
                                <?php foreach ($statusDisponiveis as $codigo => $rotulo): ?>
                                    <option value="<?= htmlspecialchars($codigo) ?>" <?= $codigo === $etapaAtualStatus ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($rotulo) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label for="observacao_vigilante" class="form-label">Orientação do 1º Vigilante</label>
                        <textarea name="observacao_vigilante" id="observacao_vigilante" rows="3" class="form-textarea w-full" placeholder="Registre orientações, devolutivas e o próximo encaminhamento."><?= htmlspecialchars((string) ($etapaAtual['observacao_vigilante'] ?? '')) ?></textarea>
                    </div>
                    <div class="text-right pt-2">
                        <button type="submit" class="btn btn-primary">Salvar Andamento</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card depth-1 p-6">
            <div class="card-header border-b border-white/5 pb-3 mb-4">
                <h2 class="card-title">Leitura Sugerida</h2>
                <p class="card-subtitle">Conecte o acompanhamento com o acervo da Biblioteca.</p>
            </div>
            <div class="card-body">
                <form action="/primeiro-vigilante/leitura/salvar" method="POST" class="space-y-4">
                    <input type="hidden" name="aprendiz_id" value="<?= htmlspecialchars((string) ($aprendiz['id'] ?? '')) ?>">
                    <div>
                        <label for="acervo_id" class="form-label">Item do Acervo</label>
                        <select name="acervo_id" id="acervo_id" class="form-select w-full">
                            <option value="">Sem vincular livro específico</option>
                            <?php foreach ($leiturasDisponiveis as $livro): ?>
                                <option value="<?= (int) ($livro['id'] ?? 0) ?>" <?= ((int) ($acompanhamento['leitura_acervo_id'] ?? 0) === (int) ($livro['id'] ?? 0)) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string) ($livro['titulo'] ?? 'Livro') . ' - ' . (string) ($livro['autor'] ?? '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="observacao_leitura" class="form-label">Orientação de Leitura</label>
                        <textarea name="observacao_leitura" id="observacao_leitura" rows="3" class="form-textarea w-full" placeholder="Explique o motivo da leitura, o foco da instrução ou o capítulo recomendado."><?= htmlspecialchars((string) ($acompanhamento['leitura_observacao'] ?? '')) ?></textarea>
                    </div>
                    <div class="text-right pt-2">
                        <button type="submit" class="btn btn-primary">Salvar Leitura</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <div class="lg:col-span-2">
        <div class="card depth-1 p-6">
            <div class="card-header border-b border-white/5 pb-3 mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="card-title">Linha do Tempo da Trilha</h2>
                    <p class="card-subtitle">Cada etapa mostra o estado real da jornada formativa do irmão.</p>
                </div>
                <div class="w-32 flex-shrink-0">
                    <div class="h-2.5 rounded-full bg-white/5 border border-white/10">
                        <div class="h-2 rounded-full bg-blue-500" style="width: <?= max(0, min(100, $percentual)) ?>%"></div>
                    </div>
                </div>
            </div>
            <div class="card-body space-y-4">
                <?php foreach ($etapas as $etapa):
                    $status = (string) ($etapa['status'] ?? 'nao_iniciado');
                    $ordem = (int) ($etapa['etapa_ordem'] ?? 0);
                    $ativo = $ordem === $etapaAtualOrdem;
                    $concluido = in_array($status, ['concluido', 'certificado_solicitado'], true);
                    
                    $baseClass = 'p-4 rounded-xl border transition';
                    $colorClass = $ativo 
                        ? 'bg-blue-500/5 border-blue-500/30 shadow-md shadow-blue-500/5' 
                        : ($concluido 
                            ? 'bg-emerald-500/5 border-emerald-500/20' 
                            : 'bg-white/[0.01] border-white/5 opacity-85');
                ?>
                    <div id="etapa-<?= $ordem ?>" class="<?= $baseClass ?> <?= $colorClass ?>">
                        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                            <div class="min-w-0 flex-grow">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="badge-status <?= $badgeStatus($status) ?>">Etapa <?= $ordem ?></span>
                                    <span class="badge-status badge-status-secondary capitalize"><?= str_replace('_', ' ', htmlspecialchars($status)) ?></span>
                                </div>
                                <h3 class="mt-3 text-lg font-bold text-white"><?= htmlspecialchars((string) ($etapa['titulo_etapa'] ?? '')) ?></h3>
                                <?php if (!empty($etapa['observacao_vigilante'])): ?>
                                    <div class="mt-3 text-sm text-slate-300 leading-relaxed bg-black/10 border border-white/5 rounded-lg p-3">
                                        <?= nl2br(htmlspecialchars((string) $etapa['observacao_vigilante'])) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="text-xs text-slate-400 md:text-right space-y-1.5 flex-shrink-0 md:min-w-[12rem] bg-white/[0.02] border border-white/5 rounded-lg p-3">
                                <p>Disponibilização: <span class="text-white font-medium"><?= !empty($etapa['data_disponibilizacao']) ? htmlspecialchars(date('d/m/Y', strtotime((string) $etapa['data_disponibilizacao']))) : '-' ?></span></p>
                                <p>Entrega de peça: <span class="text-white font-medium"><?= !empty($etapa['data_entrega']) ? htmlspecialchars(date('d/m/Y', strtotime((string) $etapa['data_entrega']))) : '-' ?></span></p>
                                <p>Orientação/Ajuste: <span class="text-white font-medium"><?= !empty($etapa['data_revisao']) ? htmlspecialchars(date('d/m/Y', strtotime((string) $etapa['data_revisao']))) : '-' ?></span></p>
                            </div>
                        </div>
                        <?php if (!$somenteProprio && !empty($acoesRapidasPorEtapa[$ordem])): ?>
                            <div class="mt-4 flex flex-wrap gap-2 border-t border-white/5 pt-3">
                                <?php foreach ($acoesRapidasPorEtapa[$ordem] as $acao): ?>
                                    <form action="/primeiro-vigilante/trilha/acao-rapida" method="POST">
                                        <input type="hidden" name="aprendiz_id" value="<?= htmlspecialchars((string) ($aprendiz['id'] ?? '')) ?>">
                                        <input type="hidden" name="etapa_ordem" value="<?= $ordem ?>">
                                        <input type="hidden" name="status" value="<?= htmlspecialchars((string) ($acao['status'] ?? '')) ?>">
                                        <button type="submit" class="btn border border-white/10 text-slate-300 hover:bg-white/5 py-1 px-3 text-xs font-semibold"><?= htmlspecialchars((string) ($acao['label'] ?? 'Avançar')) ?></button>
                                    </form>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="space-y-6">
        <div class="card depth-1 p-6">
            <div class="card-header border-b border-white/5 pb-3 mb-4">
                <h2 class="card-title">Certificado Formativo</h2>
                <p class="card-subtitle">Solicitação formal ao Venerável da conclusão da mentoria do irmão.</p>
            </div>
            <div class="card-body">
                <div class="rounded-xl border border-white/5 bg-white/[0.02] p-4 mb-4">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Status</p>
                    <p class="mt-1 text-lg font-semibold text-white capitalize"><?= str_replace('_', ' ', htmlspecialchars((string) ($acompanhamento['certificado_status'] ?? 'nao_solicitado'))) ?></p>
                    <?php if (!empty($acompanhamento['certificado_solicitado_em'])): ?>
                        <p class="mt-1.5 text-xs text-slate-400">Solicitado em <?= htmlspecialchars(date('d/m/Y H:i', strtotime((string) $acompanhamento['certificado_solicitado_em']))) ?></p>
                    <?php endif; ?>
                </div>
                <?php if (!$somenteProprio): ?>
                    <form action="/primeiro-vigilante/certificado/solicitar" method="POST" class="space-y-4">
                        <input type="hidden" name="aprendiz_id" value="<?= htmlspecialchars((string) ($aprendiz['id'] ?? '')) ?>">
                        <div>
                            <label for="observacao_certificado" class="form-label">Observação da Solicitação</label>
                            <textarea name="observacao_certificado" id="observacao_certificado" rows="3" class="form-textarea w-full" placeholder="Registre as observações para conclusão, orientação final ou justificativa de aptidão."><?= htmlspecialchars((string) ($acompanhamento['certificado_observacao'] ?? '')) ?></textarea>
                        </div>
                        <div class="text-right pt-1">
                            <button type="submit" class="btn btn-primary w-full sm:w-auto">Solicitar Certificado</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="card depth-1 p-6">
            <div class="card-header border-b border-white/5 pb-3 mb-4">
                <h2 class="card-title">Histórico Formativo</h2>
                <p class="card-subtitle">Marcos importantes registrados ao longo da jornada.</p>
            </div>
            <div class="card-body space-y-3">
                <?php if (empty($historicoFormativo)): ?>
                    <p class="text-center text-slate-400 py-4 text-sm">Ainda não há marcos registrados.</p>
                <?php else: ?>
                    <?php foreach ($historicoFormativo as $evento): ?>
                        <div class="rounded-xl border border-white/5 bg-white/[0.02] p-4 text-xs">
                            <p class="text-[9px] font-bold uppercase tracking-wider text-erp-gold"><?= htmlspecialchars((string) ($evento['tipo'] ?? 'evento')) ?></p>
                            <p class="font-semibold text-white mt-1 text-sm"><?= htmlspecialchars((string) ($evento['titulo'] ?? 'Marco formativo')) ?></p>
                            <p class="text-slate-400 mt-1"><?= !empty($evento['momento']) ? htmlspecialchars(date('d/m/Y H:i', strtotime((string) $evento['momento']))) : '-' ?></p>
                            <?php if (!empty($evento['descricao'])): ?>
                                <p class="text-slate-300 mt-2 border-t border-white/5 pt-2 leading-relaxed"><?= htmlspecialchars((string) $evento['descricao']) ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../partials/erp_shell_close.php';
?>
