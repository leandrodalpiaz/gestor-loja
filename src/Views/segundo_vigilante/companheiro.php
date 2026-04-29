<?php
require_once __DIR__ . '/../partials/erp_shell_open.php';

$mensagemSucesso = $_SESSION['mensagem_sucesso'] ?? null;
$mensagemErro = $_SESSION['mensagem_erro'] ?? null;
unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']);

$nomeCompanheiro = (string) ($companheiro['nome_historico'] ?? $companheiro['nome'] ?? 'Companheiro');
$etapaAtualOrdem = (int) ($etapaAtual['etapa_ordem'] ?? 1);
$etapaAtualTitulo = (string) ($etapaAtual['titulo_etapa'] ?? '');
$etapaAtualStatus = (string) ($etapaAtual['status'] ?? 'nao_iniciado');
$percentual = (int) ($resumoTrilha['percentual_conclusao'] ?? 0);
?>

<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <!-- Cabeçalho do Companheiro -->
    <div class="bg-gradient-to-r from-gray-800 to-blue-900 text-white rounded-lg shadow-lg p-6 mb-8">
        <p class="text-sm font-semibold uppercase tracking-wider text-blue-300"><?= $somenteProprio ? 'Autoacompanhamento' : 'Acompanhamento Formativo' ?></p>
        <h1 class="text-4xl font-bold mt-2"><?= htmlspecialchars($nomeCompanheiro) ?></h1>
        <p class="mt-2 text-blue-200 max-w-3xl">
            <?= $somenteProprio ? 'Acompanhe sua trilha, leitura, certificado e recomendação de exaltação.' : 'Linha do tempo individual do Companheiro com trilha, leitura, docência e recomendação de exaltação.' ?>
        </p>
        <div class="mt-5 flex flex-wrap gap-3">
            <a href="<?= $somenteProprio ? '/dashboard' : '/segundo-vigilante' ?>" class="btn btn-secondary">Voltar</a>
            <?php if (!$somenteProprio): ?>
                <a href="/obreiros" class="btn btn-primary">Lista de Obreiros</a>
                <a href="/biblioteca" class="btn btn-secondary">Biblioteca</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Mensagens de Feedback -->
    <?php if ($mensagemSucesso): ?><div class="alert alert-success mb-6"><?= htmlspecialchars($mensagemSucesso) ?></div><?php endif; ?>
    <?php if ($mensagemErro): ?><div class="alert alert-danger mb-6"><?= htmlspecialchars($mensagemErro) ?></div><?php endif; ?>
    <?php if (!empty($avisoInfra)): ?><div class="alert alert-warning mb-6"><?= htmlspecialchars((string) $avisoInfra) ?></div><?php endif; ?>

    <!-- Métricas Rápidas -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="card-metric"><p class="card-metric-label">Etapa Atual</p><p class="card-metric-value"><?= $etapaAtualOrdem ?></p><p class="text-sm text-gray-500 dark:text-gray-400 mt-1"><?= htmlspecialchars($etapaAtualTitulo) ?></p></div>
        <div class="card-metric"><p class="card-metric-label">Status Atual</p><p class="card-metric-value text-2xl"><?= htmlspecialchars($etapaAtualStatus) ?></p></div>
        <div class="card-metric"><p class="card-metric-label">Etapas Concluídas</p><p class="card-metric-value"><?= (int) ($resumoTrilha['total_concluidas'] ?? 0) ?> / <?= (int) ($resumoTrilha['total_etapas'] ?? 0) ?></p></div>
        <div class="card-metric"><p class="card-metric-label">Conclusão da Trilha</p><p class="card-metric-value"><?= $percentual ?>%</p></div>
    </div>

    <!-- Formulários de Gestão (Apenas para 2º Vigilante) -->
    <?php if (!$somenteProprio): ?>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <div class="card">
                <div class="card-header"><h2 class="card-title">Atualizar Etapa da Trilha</h2><p class="card-description">Registre andamento, recebimento, revisão e devolutiva.</p></div>
                <div class="card-body">
                    <form action="/segundo-vigilante/trilha/atualizar" method="POST" class="space-y-4">
                        <input type="hidden" name="companheiro_id" value="<?= htmlspecialchars((string) ($companheiro['id'] ?? '')) ?>">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="etapa_ordem" class="form-label">Etapa</label>
                                <select name="etapa_ordem" id="etapa_ordem" class="form-select">
                                    <?php foreach ($etapas as $etapa): ?>
                                        <option value="<?= (int) ($etapa['etapa_ordem'] ?? 0) ?>" <?= (int) ($etapa['etapa_ordem'] ?? 0) === $etapaAtualOrdem ? 'selected' : '' ?>>
                                            Etapa <?= (int) ($etapa['etapa_ordem'] ?? 0) ?> - <?= htmlspecialchars((string) ($etapa['titulo_etapa'] ?? '')) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="status" class="form-label">Status</label>
                                <select name="status" id="status" class="form-select">
                                    <?php foreach ($statusDisponiveis as $codigo => $rotulo): ?>
                                        <option value="<?= htmlspecialchars($codigo) ?>" <?= $codigo === $etapaAtualStatus ? 'selected' : '' ?>><?= htmlspecialchars($rotulo) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label for="observacao_vigilante" class="form-label">Observação do 2º Vigilante</label>
                            <textarea name="observacao_vigilante" id="observacao_vigilante" rows="3" class="form-textarea" placeholder="Registre orientações, devolutivas e temas sugeridos."><?= htmlspecialchars((string) ($etapaAtual['observacao_vigilante'] ?? '')) ?></textarea>
                        </div>
                        <div class="text-right"><button type="submit" class="btn btn-primary">Salvar Andamento</button></div>
                    </form>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h2 class="card-title">Leitura Sugerida</h2><p class="card-description">Conecte o acompanhamento com a biblioteca da Loja.</p></div>
                <div class="card-body">
                    <form action="/segundo-vigilante/leitura/salvar" method="POST" class="space-y-4">
                        <input type="hidden" name="companheiro_id" value="<?= htmlspecialchars((string) ($companheiro['id'] ?? '')) ?>">
                        <div>
                            <label for="acervo_id" class="form-label">Item do Acervo</label>
                            <select name="acervo_id" id="acervo_id" class="form-select">
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
                            <textarea name="observacao_leitura" id="observacao_leitura" rows="3" class="form-textarea" placeholder="Explique o foco do estudo ou capítulo recomendado."><?= htmlspecialchars((string) ($acompanhamento['leitura_observacao'] ?? '')) ?></textarea>
                        </div>
                        <div class="text-right"><button type="submit" class="btn btn-primary">Salvar Leitura</button></div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Linha do Tempo e Histórico -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2">
            <div class="card">
                <div class="card-header flex items-center justify-between">
                    <div>
                        <h2 class="card-title">Linha do Tempo da Trilha</h2>
                        <p class="card-description">Jornada formativa do Companheiro.</p>
                    </div>
                    <div class="w-32">
                        <div class="h-2.5 rounded-full bg-gray-200 dark:bg-gray-700">
                            <div class="h-2.5 rounded-full bg-blue-600" style="width: <?= max(0, min(100, $percentual)) ?>%"></div>
                        </div>
                    </div>
                </div>
                <div class="card-body space-y-4">
                    <?php foreach ($etapas as $etapa):
                        $status = (string) ($etapa['status'] ?? 'nao_iniciado');
                        $ordem = (int) ($etapa['etapa_ordem'] ?? 0);
                        $ativo = $ordem === $etapaAtualOrdem;
                        $concluido = in_array($status, ['concluido', 'certificado_solicitado', 'exaltacao_recomendada'], true);
                        $baseClass = 'p-4 rounded-lg border';
                        $colorClass = $ativo ? 'bg-blue-50 border-blue-200 dark:bg-blue-900/20 dark:border-blue-700' : ($concluido ? 'bg-green-50 border-green-200 dark:bg-green-900/20 dark:border-green-700' : 'bg-gray-50 border-gray-200 dark:bg-gray-800/50 dark:border-gray-700');
                    ?>
                        <div id="etapa-<?= $ordem ?>" class="<?= $baseClass ?> <?= $colorClass ?>">
                            <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="badge <?= $ativo ? 'badge-primary' : ($concluido ? 'badge-success' : 'badge-secondary') ?>">Etapa <?= $ordem ?></span>
                                        <span class="badge badge-secondary"><?= htmlspecialchars($status) ?></span>
                                    </div>
                                    <h3 class="mt-2 text-lg font-semibold"><?= htmlspecialchars((string) ($etapa['titulo_etapa'] ?? '')) ?></h3>
                                    <?php if (!empty($etapa['observacao_vigilante'])): ?>
                                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400"><?= nl2br(htmlspecialchars((string) $etapa['observacao_vigilante'])) ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 md:text-right space-y-1 flex-shrink-0">
                                    <p>Disponibilização: <?= !empty($etapa['data_disponibilizacao']) ? htmlspecialchars(date('d/m/Y', strtotime((string) $etapa['data_disponibilizacao']))) : '-' ?></p>
                                    <p>Entrega: <?= !empty($etapa['data_entrega']) ? htmlspecialchars(date('d/m/Y', strtotime((string) $etapa['data_entrega']))) : '-' ?></p>
                                    <p>Revisão: <?= !empty($etapa['data_revisao']) ? htmlspecialchars(date('d/m/Y', strtotime((string) $etapa['data_revisao']))) : '-' ?></p>
                                </div>
                            </div>
                             <?php if (!$somenteProprio && !empty($acoesRapidasPorEtapa[$ordem])): ?>
                                <div class="mt-3 flex flex-wrap gap-2 border-t border-gray-200 dark:border-gray-700 pt-3">
                                    <?php foreach ($acoesRapidasPorEtapa[$ordem] as $acao): ?>
                                        <form action="/segundo-vigilante/trilha/acao-rapida" method="POST">
                                            <input type="hidden" name="companheiro_id" value="<?= htmlspecialchars((string) ($companheiro['id'] ?? '')) ?>">
                                            <input type="hidden" name="etapa_ordem" value="<?= $ordem ?>">
                                            <input type="hidden" name="status" value="<?= htmlspecialchars((string) ($acao['status'] ?? '')) ?>">
                                            <button type="submit" class="btn btn-sm btn-dark"><?= htmlspecialchars((string) ($acao['label'] ?? 'Avançar')) ?></button>
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
                <div class="card-header"><h2 class="card-title">Certificado de Docência</h2><p class="card-description">Conclusão da docência do Companheiro.</p></div>
                <div class="card-body">
                    <div class="bg-gray-100 dark:bg-gray-700/50 p-4 rounded-lg mb-4">
                        <p class="text-xs font-bold uppercase text-gray-500 dark:text-gray-400">Status</p>
                        <p class="mt-1 text-lg font-semibold"><?= htmlspecialchars((string) ($acompanhamento['certificado_status'] ?? 'nao_solicitado')) ?></p>
                        <?php if (!empty($acompanhamento['certificado_solicitado_em'])): ?>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Solicitado em <?= htmlspecialchars(date('d/m/Y H:i', strtotime((string) $acompanhamento['certificado_solicitado_em']))) ?></p>
                        <?php endif; ?>
                    </div>
                    <?php if (!$somenteProprio): ?>
                        <form action="/segundo-vigilante/certificado/solicitar" method="POST" class="space-y-4">
                            <input type="hidden" name="companheiro_id" value="<?= htmlspecialchars((string) ($companheiro['id'] ?? '')) ?>">
                            <div>
                                <label for="observacao_certificado" class="form-label">Observação da Solicitação</label>
                                <textarea name="observacao_certificado" id="observacao_certificado" rows="3" class="form-textarea" placeholder="Registre a avaliação final da docência."><?= htmlspecialchars((string) ($acompanhamento['certificado_observacao'] ?? '')) ?></textarea>
                            </div>
                            <div class="text-right"><button type="submit" class="btn btn-primary">Solicitar Certificado</button></div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
             <div class="card">
                <div class="card-header"><h2 class="card-title">Recomendação de Exaltação</h2><p class="card-description">Encaminhamento para apreciação da exaltação.</p></div>
                <div class="card-body">
                    <div class="bg-gray-100 dark:bg-gray-700/50 p-4 rounded-lg mb-4">
                        <p class="text-xs font-bold uppercase text-gray-500 dark:text-gray-400">Status</p>
                        <p class="mt-1 text-lg font-semibold"><?= htmlspecialchars((string) ($acompanhamento['exaltacao_status'] ?? 'nao_recomendada')) ?></p>
                        <?php if (!empty($acompanhamento['exaltacao_recomendada_em'])): ?>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Recomendada em <?= htmlspecialchars(date('d/m/Y H:i', strtotime((string) $acompanhamento['exaltacao_recomendada_em']))) ?></p>
                        <?php endif; ?>
                    </div>
                    <?php if (!$somenteProprio): ?>
                        <form action="/segundo-vigilante/exaltacao/recomendar" method="POST" class="space-y-4">
                            <input type="hidden" name="companheiro_id" value="<?= htmlspecialchars((string) ($companheiro['id'] ?? '')) ?>">
                            <div>
                                <label for="observacao_exaltacao" class="form-label">Observação da Recomendação</label>
                                <textarea name="observacao_exaltacao" id="observacao_exaltacao" rows="3" class="form-textarea" placeholder="Registre os fundamentos para a exaltação."><?= htmlspecialchars((string) ($acompanhamento['exaltacao_observacao'] ?? '')) ?></textarea>
                            </div>
                            <div class="text-right"><button type="submit" class="btn btn-dark">Recomendar Exaltação</button></div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h2 class="card-title">Histórico Formativo</h2><p class="card-description">Marcos da trilha, leitura, certificado e exaltação.</p></div>
                <div class="card-body space-y-3">
                    <?php if (empty($historicoFormativo)): ?>
                        <p class="text-center text-gray-500 dark:text-gray-400 py-4">Ainda não há marcos registrados.</p>
                    <?php else: ?>
                        <?php foreach ($historicoFormativo as $evento): ?>
                            <div class="bg-gray-50 dark:bg-gray-800/50 p-3 rounded-lg">
                                <p class="text-xs font-bold uppercase text-gray-500 dark:text-gray-400"><?= htmlspecialchars((string) ($evento['tipo'] ?? 'evento')) ?></p>
                                <p class="font-semibold mt-1"><?= htmlspecialchars((string) ($evento['titulo'] ?? 'Marco formativo')) ?></p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1"><?= !empty($evento['momento']) ? htmlspecialchars(date('d/m/Y H:i', strtotime((string) $evento['momento']))) : '-' ?></p>
                                <?php if (!empty($evento['descricao'])): ?><p class="text-sm mt-2"><?= htmlspecialchars((string) $evento['descricao']) ?></p><?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .card { @apply bg-white dark:bg-gray-800 rounded-lg shadow-md; }
    .card-header { @apply p-6 border-b border-gray-200 dark:border-gray-700; }
    .card-title { @apply text-xl font-bold text-gray-800 dark:text-gray-100; }
    .card-description { @apply mt-1 text-sm text-gray-600 dark:text-gray-400; }
    .card-body { @apply p-6; }
    .card-metric { @apply bg-white dark:bg-gray-800 rounded-lg shadow-md p-5; }
    .card-metric-label { @apply text-sm font-medium text-gray-500 dark:text-gray-400; }
    .card-metric-value { @apply mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100; }
</style>

<?php
require_once __DIR__ . '/../partials/erp_shell_close.php';
?>

