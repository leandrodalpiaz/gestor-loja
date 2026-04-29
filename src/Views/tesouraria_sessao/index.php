<?php
declare(strict_types=1);

// #############################################################################
// LÃ“GICA DE NEGÃ“CIO E HELPERS
// #############################################################################

$mensagemSucesso = $_SESSION['mensagem_sucesso'] ?? null;
$mensagemErro = $_SESSION['mensagem_erro'] ?? null;
unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']);

$formatCurrency = static fn(?float $valor): string => 'R$ ' . number_format($valor ?? 0, 2, ',', '.');
$sessaoFormatter = new \App\Models\Sessao();

// #############################################################################
// CONFIGURAÃ‡ÃƒO DO APP SHELL
// #############################################################################

$appShellEyebrow = 'Tesouraria';
$appShellTitle = 'Tesouraria e SessÃµes';
$appShellDescription = 'Acompanhe o reflexo financeiro dos Ã¡gapes e eventos das sessÃµes.';
$appShellActiveHref = '/tesouraria/sessoes';

require __DIR__ . '/../partials/erp_shell_open.php';
?>

<!-- Mensagens de Feedback -->
<?php if ($mensagemSucesso): ?>
    <div class="alert alert-success mb-6"><?= htmlspecialchars($mensagemSucesso) ?></div>
<?php endif; ?>
<?php if ($mensagemErro): ?>
    <div class="alert alert-danger mb-6"><?= htmlspecialchars($mensagemErro) ?></div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Coluna Principal -->
    <div class="lg:col-span-2 space-y-8">
        <!-- PrÃ³xima SessÃ£o -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">PrÃ³xima SessÃ£o com Leitura Financeira</h2>
            </div>
            <?php if ($proximaSessao): ?>
                <div class="card-body">
                    <div class="mb-6">
                        <p class="text-2xl font-bold text-blue-600 dark:text-blue-400"><?= htmlspecialchars($proximaSessao['titulo'] ?: (($proximaSessao['tipo_sessao'] ?? 'SessÃ£o') . ' - ' . ($proximaSessao['grau_sessao'] ?? ''))) ?></p>
                        <p class="text-sm text-gray-500 dark:text-gray-400"><?= htmlspecialchars((string) ($proximaSessao['data_hora_inicio'] ?? '')) ?></p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="list-item-param"><span>Loja</span><strong><?= htmlspecialchars(trim((string) (($configuracaoLoja['nome_loja'] ?? '') . ((string) ($configuracaoLoja['numero_loja'] ?? '') !== '' ? ' nÂº ' . $configuracaoLoja['numero_loja'] : '')))) ?></strong></div>
                        <div class="list-item-param"><span>Ãgape</span><strong><?= htmlspecialchars($sessaoFormatter->obterDescricaoAgape($proximaSessao)) ?></strong></div>
                        <div class="list-item-param"><span>Modelo Financeiro</span><strong><?= htmlspecialchars($sessaoFormatter->obterDescricaoModeloTesourariaAgape($proximaSessao)) ?></strong></div>
                        <div class="card-metric-simple"><p class="card-metric-label">Confirmados com Ãgape</p><p class="card-metric-value text-xl"><?= count($participantesAgape) ?></p></div>
                        <div class="card-metric-simple md:col-span-2"><p class="card-metric-label">Estimativa de ArrecadaÃ§Ã£o</p><p class="card-metric-value text-xl text-green-600 dark:text-green-400"><?= $formatCurrency($estimativaArrecadacao) ?></p></div>
                    </div>
                    <?php if (empty($proximaSessao['reflete_financeiro_oficial'])): ?>
                        <div class="alert alert-warning mt-6">Esta sessÃ£o nÃ£o gera reflexo automÃ¡tico no financeiro oficial da Loja.</div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="card-body text-center text-gray-500 dark:text-gray-400 py-10">
                    <p>Nenhuma sessÃ£o futura cadastrada.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Participantes do Ãgape -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Participantes do Ãgape</h2>
                <p class="card-description">ConferÃªncia financeira da prÃ³xima sessÃ£o.</p>
            </div>
            <div class="card-body">
                <?php if (!empty($participantesAgape)): ?>
                    <ul class="space-y-3 max-h-96 overflow-y-auto">
                        <?php foreach ($participantesAgape as $participante): ?>
                            <li class="list-item-detail">
                                <span><?= htmlspecialchars((string) ($participante['nome'] ?? 'Obreiro')) ?></span>
                                <span class="text-xs text-gray-500">CIM: <?= htmlspecialchars((string) ($participante['cim'] ?? '-')) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <p>Ainda nÃ£o hÃ¡ confirmaÃ§Ãµes com Ã¡gape.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Coluna Lateral -->
    <div class="space-y-8">
        <!-- Agenda Futura -->
        <div class="card">
            <div class="card-header"><h2 class="card-title">Agenda Futura</h2></div>
            <div class="card-body space-y-4">
                <?php if (!empty($sessoesFinanceiras)): ?>
                    <?php foreach ($sessoesFinanceiras as $sessao): ?>
                        <div class="list-item-report">
                            <p class="font-semibold"><?= htmlspecialchars($sessao['titulo'] ?: ($sessao['descricao_tipo'] ?: 'SessÃ£o')) ?></p>
                            <p class="text-sm text-gray-500 mb-2"><?= htmlspecialchars((string) ($sessao['data_hora_inicio'] ?? '')) ?></p>
                            <div class="text-xs space-y-1 text-gray-600 dark:text-gray-300">
                                <p><strong>Ãgape:</strong> <?= htmlspecialchars((string) ($sessao['descricao_agape'] ?? '-')) ?></p>
                                <p><strong>Modelo:</strong> <?= htmlspecialchars((string) ($sessao['descricao_modelo_financeiro_agape'] ?? '-')) ?></p>
                                <p><strong>Confirmados:</strong> <?= (int) ($sessao['total_agape'] ?? 0) ?></p>
                                <p><strong>Estimativa:</strong> <span class="font-bold"><?= $formatCurrency((float) ($sessao['estimativa_arrecadacao'] ?? 0)) ?></span></p>
                            </div>
                            <?php if (empty($sessao['reflete_financeiro_oficial'])): ?>
                                <div class="alert alert-warning text-xs mt-2">Sem reflexo automÃ¡tico.</div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <p>Nenhuma sessÃ£o futura disponÃ­vel.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Fluxo de Trabalho -->
        <div class="card">
            <div class="card-header"><h2 class="card-title">Fluxo de Trabalho</h2></div>
            <ul class="card-body space-y-3 text-sm text-gray-700 dark:text-gray-300">
                <li class="flex items-start gap-3"><span class="font-bold text-blue-500">1.</span> <span>O SecretÃ¡rio publica a sessÃ£o e define o modelo financeiro do Ã¡gape.</span></li>
                <li class="flex items-start gap-3"><span class="font-bold text-blue-500">2.</span> <span>Os membros confirmam presenÃ§a, com ou sem Ã¡gape.</span></li>
                <li class="flex items-start gap-3"><span class="font-bold text-blue-500">3.</span> <span>A Tesouraria consome automaticamente os valores com reflexo oficial.</span></li>
                <li class="flex items-start gap-3"><span class="font-bold text-blue-500">4.</span> <span>O lanÃ§amento detalhado Ã© feito no Livro-Caixa, mantendo a rastreabilidade.</span></li>
            </ul>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../partials/erp_shell_close.php'; ?>



