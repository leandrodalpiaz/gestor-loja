<?php
declare(strict_types=1);

// #############################################################################
// LÃ“GICA DE NEGÃ“CIO E HELPERS
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
        'nao_iniciado' => 'badge-secondary',
        'em_andamento' => 'badge-info',
        'concluido' => 'badge-success',
        'aguardando_devolutiva' => 'badge-warning',
        'certificado_solicitado' => 'badge-primary',
        default => 'badge-secondary',
    };
};

// #############################################################################
// CONFIGURAÃ‡ÃƒO DO APP SHELL
// #############################################################################

$appShellEyebrow = $somenteProprio ? 'Autoacompanhamento' : 'Acompanhamento Formativo';
$appShellTitle = htmlspecialchars($nomeAprendiz);
$appShellDescription = $somenteProprio ? 'Acompanhe sua trilha, sua leitura orientada e a situaÃ§Ã£o do certificado formativo.' : 'Linha do tempo individual do Aprendiz com trilha, leitura sugerida, devolutivas e pedido formal de certificado.';
$appShellActiveHref = $somenteProprio ? '/dashboard' : '/primeiro-vigilante';

require __DIR__ . '/../partials/erp_shell_open.php';
?>

<!-- BotÃµes de NavegaÃ§Ã£o -->
<div class="mb-6 flex flex-wrap gap-3">
    <a href="<?= $somenteProprio ? '/dashboard' : '/primeiro-vigilante' ?>" class="btn btn-secondary">Voltar</a>
    <?php if (!$somenteProprio): ?>
        <a href="/obreiros" class="btn btn-outline-secondary">Lista de Obreiros</a>
        <a href="/biblioteca" class="btn btn-outline-secondary">Biblioteca</a>
    <?php endif; ?>
</div>

<!-- Mensagens de Feedback -->
<?php if ($mensagemSucesso): ?><div class="alert alert-success mb-6"><?= htmlspecialchars($mensagemSucesso) ?></div><?php endif; ?>
<?php if ($mensagemErro): ?><div class="alert alert-danger mb-6"><?= htmlspecialchars($mensagemErro) ?></div><?php endif; ?>
<?php if (!empty($avisoInfra)): ?><div class="alert alert-warning mb-6"><?= htmlspecialchars((string) $avisoInfra) ?></div><?php endif; ?>

<!-- MÃ©tricas RÃ¡pidas -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-8">
    <div class="card-metric"><p class="card-metric-label">Etapa Atual</p><p class="card-metric-value"><?= $etapaAtualOrdem ?></p><p class="card-metric-context"><?= htmlspecialchars($etapaAtualTitulo) ?></p></div>
    <div class="card-metric"><p class="card-metric-label">Status Atual</p><p class="card-metric-value text-2xl capitalize"><?= str_replace('_', ' ', htmlspecialchars($etapaAtualStatus)) ?></p></div>
    <div class="card-metric"><p class="card-metric-label">Etapas ConcluÃ­das</p><p class="card-metric-value"><?= (int) ($resumoTrilha['total_concluidas'] ?? 0) ?> / <?= (int) ($resumoTrilha['total_etapas'] ?? 0) ?></p></div>
    <div class="card-metric"><p class="card-metric-label">ConclusÃ£o da Trilha</p><p class="card-metric-value"><?= $percentual ?>%</p></div>
</div>

<!-- FormulÃ¡rios de GestÃ£o (Apenas para 1Âº Vigilante) -->
<?php if (!$somenteProprio): ?>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <div class="card">
            <div class="card-header"><h2 class="card-title">Atualizar Etapa da Trilha</h2><p class="card-description">Registre andamento, recebimento, revisÃ£o e devolutiva.</p></div>
            <div class="card-body">
                <form action="/primeiro-vigilante/trilha/atualizar" method="POST" class="space-y-4">
                    <input type="hidden" name="aprendiz_id" value="<?= htmlspecialchars((string) ($aprendiz['id'] ?? '')) ?>">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div><label for="etapa_ordem" class="form-label">Etapa</label><select name="etapa_ordem" id="etapa_ordem" class="form-select"><?php foreach ($etapas as $etapa): ?><option value="<?= (int) ($etapa['etapa_ordem'] ?? 0) ?>" <?= (int) ($etapa['etapa_ordem'] ?? 0) === $etapaAtualOrdem ? 'selected' : '' ?>>Etapa <?= (int) ($etapa['etapa_ordem'] ?? 0) ?> - <?= htmlspecialchars((string) ($etapa['titulo_etapa'] ?? '')) ?></option><?php endforeach; ?></select></div>
                        <div><label for="status" class="form-label">Status</label><select name="status" id="status" class="form-select"><?php foreach ($statusDisponiveis as $codigo => $rotulo): ?><option value="<?= htmlspecialchars($codigo) ?>" <?= $codigo === $etapaAtualStatus ? 'selected' : '' ?>><?= htmlspecialchars($rotulo) ?></option><?php endforeach; ?></select></div>
                    </div>
                    <div><label for="observacao_vigilante" class="form-label">ObservaÃ§Ã£o do 1Âº Vigilante</label><textarea name="observacao_vigilante" id="observacao_vigilante" rows="3" class="form-textarea" placeholder="Registre orientaÃ§Ãµes, devolutivas e o prÃ³ximo encaminhamento."><?= htmlspecialchars((string) ($etapaAtual['observacao_vigilante'] ?? '')) ?></textarea></div>
                    <div class="text-right"><button type="submit" class="btn btn-primary">Salvar Andamento</button></div>
                </form>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h2 class="card-title">Leitura Sugerida</h2><p class="card-description">Conecte o acompanhamento com a biblioteca da Loja.</p></div>
            <div class="card-body">
                <form action="/primeiro-vigilante/leitura/salvar" method="POST" class="space-y-4">
                    <input type="hidden" name="aprendiz_id" value="<?= htmlspecialchars((string) ($aprendiz['id'] ?? '')) ?>">
                    <div><label for="acervo_id" class="form-label">Item do Acervo</label><select name="acervo_id" id="acervo_id" class="form-select"><option value="">Sem vincular livro especÃ­fico</option><?php foreach ($leiturasDisponiveis as $livro): ?><option value="<?= (int) ($livro['id'] ?? 0) ?>" <?= ((int) ($acompanhamento['leitura_acervo_id'] ?? 0) === (int) ($livro['id'] ?? 0)) ? 'selected' : '' ?>><?= htmlspecialchars((string) ($livro['titulo'] ?? 'Livro') . ' - ' . (string) ($livro['autor'] ?? '')) ?></option><?php endforeach; ?></select></div>
                    <div><label for="observacao_leitura" class="form-label">OrientaÃ§Ã£o de Leitura</label><textarea name="observacao_leitura" id="observacao_leitura" rows="3" class="form-textarea" placeholder="Explique o motivo da leitura, o foco da instruÃ§Ã£o ou o capÃ­tulo recomendado."><?= htmlspecialchars((string) ($acompanhamento['leitura_observacao'] ?? '')) ?></textarea></div>
                    <div class="text-right"><button type="submit" class="btn btn-primary">Salvar Leitura</button></div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <div class="lg:col-span-2">
        <div class="card">
            <div class="card-header">
                <div><h2 class="card-title">Linha do Tempo da Trilha</h2><p class="card-description">Cada etapa mostra o estado real da jornada formativa.</p></div>
                <div class="w-32"><div class="h-2.5 rounded-full bg-gray-200 dark:bg-gray-700"><div class="h-2.5 rounded-full bg-blue-600" style="width: <?= max(0, min(100, $percentual)) ?>%"></div></div></div>
            </div>
            <div class="card-body space-y-4">
                <?php foreach ($etapas as $etapa):
                    $status = (string) ($etapa['status'] ?? 'nao_iniciado');
                    $ordem = (int) ($etapa['etapa_ordem'] ?? 0);
                    $ativo = $ordem === $etapaAtualOrdem;
                    $concluido = in_array($status, ['concluido', 'certificado_solicitado'], true);
                    $baseClass = 'p-4 rounded-lg border';
                    $colorClass = $ativo ? 'bg-blue-50 border-blue-200 dark:bg-blue-900/20 dark:border-blue-700' : ($concluido ? 'bg-green-50 border-green-200 dark:bg-green-900/20 dark:border-green-700' : 'bg-gray-50 border-gray-200 dark:bg-gray-800/50 dark:border-gray-700');
                ?>
                    <div id="etapa-<?= $ordem ?>" class="<?= $baseClass ?> <?= $colorClass ?>">
                        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="badge <?= $badgeStatus($status) ?>">Etapa <?= $ordem ?></span>
                                    <span class="badge badge-secondary capitalize"><?= str_replace('_', ' ', htmlspecialchars($status)) ?></span>
                                </div>
                                <h3 class="mt-2 text-lg font-semibold"><?= htmlspecialchars((string) ($etapa['titulo_etapa'] ?? '')) ?></h3>
                                <?php if (!empty($etapa['observacao_vigilante'])): ?><p class="mt-2 text-sm text-gray-600 dark:text-gray-400"><?= nl2br(htmlspecialchars((string) $etapa['observacao_vigilante'])) ?></p><?php endif; ?>
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 md:text-right space-y-1 flex-shrink-0">
                                <p>DisponibilizaÃ§Ã£o: <?= !empty($etapa['data_disponibilizacao']) ? htmlspecialchars(date('d/m/Y', strtotime((string) $etapa['data_disponibilizacao']))) : '-' ?></p>
                                <p>Entrega: <?= !empty($etapa['data_entrega']) ? htmlspecialchars(date('d/m/Y', strtotime((string) $etapa['data_entrega']))) : '-' ?></p>
                                <p>RevisÃ£o: <?= !empty($etapa['data_revisao']) ? htmlspecialchars(date('d/m/Y', strtotime((string) $etapa['data_revisao']))) : '-' ?></p>
                            </div>
                        </div>
                         <?php if (!$somenteProprio && !empty($acoesRapidasPorEtapa[$ordem])): ?>
                            <div class="mt-3 flex flex-wrap gap-2 border-t border-gray-200 dark:border-gray-700 pt-3">
                                <?php foreach ($acoesRapidasPorEtapa[$ordem] as $acao): ?>
                                    <form action="/primeiro-vigilante/trilha/acao-rapida" method="POST">
                                        <input type="hidden" name="aprendiz_id" value="<?= htmlspecialchars((string) ($aprendiz['id'] ?? '')) ?>"><input type="hidden" name="etapa_ordem" value="<?= $ordem ?>"><input type="hidden" name="status" value="<?= htmlspecialchars((string) ($acao['status'] ?? '')) ?>">
                                        <button type="submit" class="btn btn-sm btn-dark"><?= htmlspecialchars((string) ($acao['label'] ?? 'AvanÃ§ar')) ?></button>
                                    </form>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="space-y-8">
        <div class="card">
            <div class="card-header"><h2 class="card-title">Certificado Formativo</h2><p class="card-description">SolicitaÃ§Ã£o formal da conclusÃ£o da docÃªncia.</p></div>
            <div class="card-body">
                <div class="list-item-report mb-4">
                    <p class="text-xs font-bold uppercase text-gray-500">Status</p>
                    <p class="mt-1 text-lg font-semibold capitalize"><?= str_replace('_', ' ', htmlspecialchars((string) ($acompanhamento['certificado_status'] ?? 'nao_solicitado'))) ?></p>
                    <?php if (!empty($acompanhamento['certificado_solicitado_em'])): ?><p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Solicitado em <?= htmlspecialchars(date('d/m/Y H:i', strtotime((string) $acompanhamento['certificado_solicitado_em']))) ?></p><?php endif; ?>
                </div>
                <?php if (!$somenteProprio): ?>
                    <form action="/primeiro-vigilante/certificado/solicitar" method="POST" class="space-y-4">
                        <input type="hidden" name="aprendiz_id" value="<?= htmlspecialchars((string) ($aprendiz['id'] ?? '')) ?>">
                        <div><label for="observacao_certificado" class="form-label">ObservaÃ§Ã£o da SolicitaÃ§Ã£o</label><textarea name="observacao_certificado" id="observacao_certificado" rows="3" class="form-textarea" placeholder="Registre as condiÃ§Ãµes para conclusÃ£o, avaliaÃ§Ã£o final ou pendÃªncias."><?= htmlspecialchars((string) ($acompanhamento['certificado_observacao'] ?? '')) ?></textarea></div>
                        <div class="text-right"><button type="submit" class="btn btn-primary">Solicitar Certificado</button></div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h2 class="card-title">HistÃ³rico Formativo</h2><p class="card-description">Marcos da trilha, leitura e certificado.</p></div>
            <div class="card-body space-y-3">
                <?php if (empty($historicoFormativo)): ?>
                    <p class="text-center text-gray-500 py-4">Ainda nÃ£o hÃ¡ marcos registrados.</p>
                <?php else: ?>
                    <?php foreach ($historicoFormativo as $evento): ?>
                        <div class="list-item-report">
                            <p class="text-xs font-bold uppercase text-gray-500"><?= htmlspecialchars((string) ($evento['tipo'] ?? 'evento')) ?></p>
                            <p class="font-semibold mt-1"><?= htmlspecialchars((string) ($evento['titulo'] ?? 'Marco formativo')) ?></p>
                            <p class="text-xs text-gray-500 mt-1"><?= !empty($evento['momento']) ? htmlspecialchars(date('d/m/Y H:i', strtotime((string) $evento['momento']))) : '-' ?></p>
                            <?php if (!empty($evento['descricao'])): ?><p class="text-sm mt-2"><?= htmlspecialchars((string) $evento['descricao']) ?></p><?php endif; ?>
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


