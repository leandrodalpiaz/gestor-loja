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
$podeOperarOcorrencias = (bool) ($podeOperarOcorrencias ?? false);
$podeTratarTesouraria = (bool) ($podeTratarTesouraria ?? false);

$badgeStatus = static function(string $status): string {
    return match ($status) {
        'aberta' => 'badge-warning',
        'em_acompanhamento' => 'badge-info',
        'concluida' => 'badge-success',
        'cancelada' => 'badge-danger',
        default => 'badge-secondary',
    };
};

// #############################################################################
// CONFIGURAÇÃO DO APP SHELL
// #############################################################################

$appShellEyebrow = 'Hospitalaria';
$appShellTitle = 'Painel do Mestre Hospitaleiro';
$appShellDescription = 'Ocorrências assistenciais, visitas, retornos e encaminhamentos.';
$appShellActiveHref = '/hospitaleiro';

require __DIR__ . '/../partials/erp_shell_open.php';

?>

<!-- Mensagens de Feedback -->
<?php if ($mensagemSucesso): ?><div class="alert alert-success mb-6"><?= htmlspecialchars($mensagemSucesso) ?></div><?php endif; ?>
<?php if ($mensagemErro): ?><div class="alert alert-danger mb-6"><?= htmlspecialchars($mensagemErro) ?></div><?php endif; ?>

<!-- Métricas Rápidas -->
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6 mb-8">
    <div class="card-metric"><p class="card-metric-label">Total</p><p class="card-metric-value"><?= (int) ($resumo['total'] ?? 0) ?></p></div>
    <div class="card-metric"><p class="card-metric-label">Abertas</p><p class="card-metric-value"><?= (int) ($resumo['abertas'] ?? 0) ?></p></div>
    <div class="card-metric"><p class="card-metric-label">Em Acompanhamento</p><p class="card-metric-value"><?= (int) ($resumo['em_acompanhamento'] ?? 0) ?></p></div>
    <div class="card-metric"><p class="card-metric-label">Concluídas</p><p class="card-metric-value"><?= (int) ($resumo['concluidas'] ?? 0) ?></p></div>
    <div class="card-metric"><p class="card-metric-label">Apoio Financeiro</p><p class="card-metric-value"><?= (int) ($resumo['com_apoio_financeiro'] ?? 0) ?></p></div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Coluna Principal (2/3) -->
    <div class="lg:col-span-2 space-y-8">
        <div class="card">
            <div class="card-header"><h2 class="card-title">Pendências de Visita e Retorno</h2><p class="card-description">Ocorrências que pedem presença em campo ou acompanhamento ativo.</p></div>
            <div class="card-body space-y-4">
                <?php if (empty($pendenciasVisita)): ?>
                    <p class="text-center text-gray-500 py-4">Nenhuma pendência de visita no momento.</p>
                <?php else: ?>
                    <?php foreach ($pendenciasVisita as $ocorrencia): ?>
                        <div class="list-item-report">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="font-semibold"><?= htmlspecialchars((string) ($ocorrencia['obreiro_nome'] ?? 'Sem obreiro vinculado')) ?></p>
                                    <p class="text-sm text-gray-500"><?= htmlspecialchars((string) ($ocorrencia['tipo_ocorrencia'] ?? 'assistencia_geral')) ?> &middot; Prioridade <?= htmlspecialchars((string) ($ocorrencia['prioridade'] ?? 'media')) ?></p>
                                </div>
                                <span class="badge <?= $badgeStatus((string) ($ocorrencia['status'] ?? 'aberta')) ?>"><?= htmlspecialchars((string) ($ocorrencia['status'] ?? 'aberta')) ?></span>
                            </div>
                            <p class="mt-3 text-sm"><?= nl2br(htmlspecialchars((string) ($ocorrencia['descricao'] ?? ''))) ?></p>
                            <?php if ($podeOperarOcorrencias): ?>
                                <form method="POST" action="/assistencia/ocorrencias/visita" class="mt-4 grid gap-3 md:grid-cols-[1fr_auto_auto]">
                                    <input type="hidden" name="ocorrencia_id" value="<?= (int) ($ocorrencia['id'] ?? 0) ?>">
                                    <input type="text" name="observacao_visita" placeholder="Observação da visita" class="form-input">
                                    <input type="date" name="data_proxima_acao" class="form-input">
                                    <button type="submit" class="btn btn-primary">Registrar Visita</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2 class="card-title">Ocorrências Assistenciais Recentes</h2><p class="card-description">Fluxo assistencial de saúde, nascimento, falecimento, solidariedade e apoio geral.</p></div>
            <div class="card-body space-y-4">
                <?php foreach ($ocorrencias as $ocorrencia): ?>
                    <div class="list-item-report">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold"><?= ucfirst(str_replace('_', ' ', (string) ($ocorrencia['tipo_ocorrencia'] ?? 'assistencia_geral'))) ?></p>
                                <p class="text-sm text-gray-500"><?= htmlspecialchars((string) ($ocorrencia['obreiro_nome'] ?? 'Sem obreiro vinculado')) ?><?php if (!empty($ocorrencia['nome_familiar'])): ?> &middot; Familiar: <?= htmlspecialchars((string) $ocorrencia['nome_familiar']) ?><?php endif; ?></p>
                            </div>
                            <span class="badge <?= $badgeStatus((string) ($ocorrencia['status'] ?? 'aberta')) ?>"><?= htmlspecialchars((string) ($ocorrencia['status'] ?? 'aberta')) ?></span>
                        </div>
                        <p class="mt-3 text-sm"><?= nl2br(htmlspecialchars((string) ($ocorrencia['descricao'] ?? ''))) ?></p>
                        <div class="mt-3 grid gap-2 text-xs text-gray-500 md:grid-cols-2">
                            <span>Prioridade: <strong><?= htmlspecialchars((string) ($ocorrencia['prioridade'] ?? 'media')) ?></strong></span>
                            <span>Encaminhar para: <strong><?= htmlspecialchars((string) ($ocorrencia['encaminhar_para'] ?? 'nenhum')) ?></strong></span>
                            <span>Data da ocorrência: <strong><?= htmlspecialchars((string) ($ocorrencia['data_ocorrencia'] ?? '-')) ?></strong></span>
                            <span>Próxima ação: <strong><?= htmlspecialchars((string) ($ocorrencia['data_proxima_acao'] ?? '-')) ?></strong></span>
                        </div>
                        <?php if ($podeOperarOcorrencias || $podeTratarTesouraria): ?>
                            <form method="POST" action="/assistencia/ocorrencias/status" class="mt-4 flex flex-wrap items-end gap-3">
                                <input type="hidden" name="ocorrencia_id" value="<?= (int) ($ocorrencia['id'] ?? 0) ?>">
                                <div class="flex-grow">
                                    <label for="status-<?= $ocorrencia['id'] ?>" class="form-label sr-only">Status</label>
                                    <select name="status" id="status-<?= $ocorrencia['id'] ?>" class="form-select w-full">
                                        <option value="aberta" <?= ($ocorrencia['status'] ?? '') === 'aberta' ? 'selected' : '' ?>>Aberta</option>
                                        <option value="em_acompanhamento" <?= ($ocorrencia['status'] ?? '') === 'em_acompanhamento' ? 'selected' : '' ?>>Em acompanhamento</option>
                                        <option value="concluida" <?= ($ocorrencia['status'] ?? '') === 'concluida' ? 'selected' : '' ?>>Concluída</option>
                                        <option value="cancelada" <?= ($ocorrencia['status'] ?? '') === 'cancelada' ? 'selected' : '' ?>>Cancelada</option>
                                    </select>
                                </div>
                                <div class="flex-grow-[2]">
                                    <label for="obs-<?= $ocorrencia['id'] ?>" class="form-label sr-only">Observação</label>
                                    <input type="text" name="observacao_status" id="obs-<?= $ocorrencia['id'] ?>" placeholder="Observação de status" class="form-input w-full">
                                </div>
                                <button type="submit" class="btn btn-secondary">Atualizar Status</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Coluna Lateral (1/3) -->
    <div class="space-y-8">
        <div class="card">
            <div class="card-header"><h2 class="card-title">Nova Ocorrência Assistencial</h2><p class="card-description">Registro de ocorrência, visita, apoio e encaminhamento.</p></div>
            <div class="card-body">
                <?php if ($podeOperarOcorrencias): ?>
                    <form method="POST" action="/assistencia/ocorrencias/salvar" class="space-y-4">
                        <div><label for="tipo_ocorrencia" class="form-label">Tipo de ocorrência</label><select name="tipo_ocorrencia" id="tipo_ocorrencia" class="form-select"><option value="assistencia_geral">Assistência geral</option><option value="saude">Saúde</option><option value="nascimento">Nascimento</option><option value="falecimento">Falecimento</option><option value="solidariedade">Solidariedade</option></select></div>
                        <div><label for="obreiro_id" class="form-label">Obreiro vinculado</label><select name="obreiro_id" id="obreiro_id" class="form-select"><option value="">Não vincular obreiro</option><?php foreach ($obreiros as $obreiro): ?><option value="<?= htmlspecialchars((string) ($obreiro['id'] ?? '')) ?>"><?= htmlspecialchars((string) ($obreiro['nome_historico'] ?? $obreiro['nome'] ?? '')) ?> - CIM <?= htmlspecialchars((string) ($obreiro['cim'] ?? '-')) ?></option><?php endforeach; ?></select></div>
                        <div class="grid grid-cols-2 gap-4">
                            <div><label for="nome_familiar" class="sr-only">Nome do Familiar</label><input type="text" name="nome_familiar" id="nome_familiar" placeholder="Nome do familiar" class="form-input"></div>
                            <div><label for="parentesco" class="sr-only">Parentesco</label><input type="text" name="parentesco" id="parentesco" placeholder="Parentesco" class="form-input"></div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div><label for="prioridade" class="form-label">Prioridade</label><select name="prioridade" id="prioridade" class="form-select"><option value="media">Média</option><option value="baixa">Baixa</option><option value="alta">Alta</option><option value="urgente">Urgente</option></select></div>
                            <div><label for="encaminhar_para" class="form-label">Encaminhar para</label><select name="encaminhar_para" id="encaminhar_para" class="form-select"><option value="nenhum">Nenhum</option><option value="veneravel">Venerável Mestre</option><option value="tesoureiro">Tesoureiro</option><option value="ambos">Venerável + Tesoureiro</option></select></div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div><label for="data_ocorrencia" class="form-label">Data da Ocorrência</label><input type="date" name="data_ocorrencia" id="data_ocorrencia" class="form-input"></div>
                            <div><label for="data_proxima_acao" class="form-label">Próxima Ação</label><input type="date" name="data_proxima_acao" id="data_proxima_acao" class="form-input"></div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div><label for="valor_solicitado" class="sr-only">Valor Solicitado</label><input type="text" name="valor_solicitado" id="valor_solicitado" placeholder="Valor solicitado" class="form-input"></div>
                            <div><label for="valor_aprovado" class="sr-only">Valor Aprovado</label><input type="text" name="valor_aprovado" id="valor_aprovado" placeholder="Valor aprovado" class="form-input"></div>
                        </div>
                        <div><label for="descricao" class="form-label">Descrição detalhada</label><textarea name="descricao" id="descricao" rows="4" required class="form-textarea" placeholder="Descrição detalhada da ocorrência"></textarea></div>
                        <div class="space-y-2">
                            <label class="form-check-label"><input type="checkbox" name="necessita_visita" value="1" class="form-checkbox"> Necessita visita presencial</label>
                            <label class="form-check-label"><input type="checkbox" name="necessita_apoio_financeiro" value="1" class="form-checkbox"> Necessita apoio financeiro</label>
                        </div>
                        <button type="submit" class="btn btn-primary w-full">Registrar Ocorrência</button>
                    </form>
                <?php else: ?>
                    <p class="text-center text-gray-500 py-4">Você não tem permissão para registrar novas ocorrências.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2 class="card-title">Funções do Cargo</h2></div>
            <div class="card-body">
                <ul class="list-disc space-y-2 pl-5 text-sm text-gray-500">
                    <li>Registrar ocorrências assistenciais.</li>
                    <li>Priorizar casos e marcar necessidade de visita.</li>
                    <li>Controlar retorno e próxima ação.</li>
                    <li>Encaminhar casos ao Venerável e à Tesouraria.</li>
                    <li>Acompanhar apoio financeiro e status de resolução.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../partials/erp_shell_close.php';
?>
