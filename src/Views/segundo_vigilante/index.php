<?php
declare(strict_types=1);

// #############################################################################
// LÓGICA DE NEGÓCIO E HELPERS
// #############################################################################

$mensagemSucesso = $_SESSION['mensagem_sucesso'] ?? null;
$mensagemErro = $_SESSION['mensagem_erro'] ?? null;
unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']);

$badgeStatus = static function(string $status): string {
    return match ($status) {
        'nao_iniciado' => 'badge-secondary',
        'em_andamento' => 'badge-info',
        'concluido' => 'badge-success',
        'aguardando_devolutiva' => 'badge-warning',
        default => 'badge-secondary',
    };
};

// #############################################################################
// CONFIGURAÇÃO DO APP SHELL
// #############################################################################

$appShellEyebrow = 'Segundo Vigilante';
$appShellTitle = 'Painel de Acompanhamento';
$appShellDescription = 'Acompanhamento formativo dos Companheiros, trilha, docência e recomendação de exaltação.';
$appShellActiveHref = '/segundo-vigilante';

require __DIR__ . '/../partials/erp_shell_open.php';

?>

<!-- Mensagens de Feedback -->
<?php if ($mensagemSucesso): ?><div class="alert alert-success mb-6"><?= htmlspecialchars($mensagemSucesso) ?></div><?php endif; ?>
<?php if ($mensagemErro): ?><div class="alert alert-danger mb-6"><?= htmlspecialchars($mensagemErro) ?></div><?php endif; ?>
<?php if (!empty($avisoInfra)): ?><div class="alert alert-warning mb-6"><?= htmlspecialchars((string) $avisoInfra) ?></div><?php endif; ?>

<!-- Métricas Rápidas -->
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6 mb-8">
    <div class="card-metric"><p class="card-metric-label">Companheiros Ativos</p><p class="card-metric-value"><?= (int) ($resumo['companheiros_ativos'] ?? 0) ?></p></div>
    <div class="card-metric"><p class="card-metric-label">Etapa Inicial</p><p class="card-metric-value"><?= (int) ($resumo['etapa_inicial'] ?? 0) ?></p></div>
    <div class="card-metric"><p class="card-metric-label">Aguardando Recebimento</p><p class="card-metric-value"><?= (int) ($resumo['trabalhos_aguardando_recebimento'] ?? 0) ?></p></div>
    <div class="card-metric"><p class="card-metric-label">Aptos para Docência</p><p class="card-metric-value"><?= (int) ($resumo['aptos_docencia'] ?? 0) ?></p></div>
    <div class="card-metric"><p class="card-metric-label">Aptos para Exaltação</p><p class="card-metric-value"><?= (int) ($resumo['aptos_exaltacao'] ?? 0) ?></p></div>
    <div class="card-metric"><p class="card-metric-label">Leituras Sugeridas</p><p class="card-metric-value"><?= (int) ($resumo['leituras_sugeridas'] ?? 0) ?></p></div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Coluna Principal (2/3) -->
    <div class="lg:col-span-2">
        <div class="card">
            <div class="card-header"><h2 class="card-title">Companheiros em Acompanhamento</h2><p class="card-description">Painel central com trilha, docência, certificado e indicação de exaltação.</p></div>
            <div class="card-body divide-y divide-gray-200 dark:divide-gray-700">
                <?php if (empty($companheiros)): ?>
                    <p class="text-center text-gray-500 py-10">Nenhum Companheiro ativo encontrado.</p>
                <?php else: ?>
                    <?php foreach ($companheiros as $companheiro): ?>
                        <div class="list-item-action flex-col sm:flex-row items-start sm:items-center !py-4">
                            <div class="flex-grow">
                                <p class="font-semibold"><?= htmlspecialchars((string) ($companheiro['nome_historico'] ?? $companheiro['nome'] ?? 'Companheiro')) ?></p>
                                <p class="text-sm text-gray-500">CIM <?= htmlspecialchars((string) ($companheiro['cim'] ?? '-')) ?> &middot; Elevação: <?= !empty($companheiro['data_elevacao']) ? htmlspecialchars(date('d/m/Y', strtotime((string) $companheiro['data_elevacao']))) : 'Não informada' ?></p>
                                <div class="mt-2 flex items-center gap-3 text-sm">
                                    <span class="font-semibold">Etapa <?= (int) ($companheiro['trilha_etapa_atual'] ?? 1) ?>:</span>
                                    <span class="text-gray-600 dark:text-gray-400"><?= htmlspecialchars((string) ($companheiro['trilha_titulo_atual'] ?? '')) ?></span>
                                    <span class="badge <?= $badgeStatus((string) ($companheiro['trilha_status_atual'] ?? 'nao_iniciado')) ?>"><?= str_replace('_', ' ', (string) ($companheiro['trilha_status_atual'] ?? 'nao_iniciado')) ?></span>
                                </div>
                            </div>
                            <a href="/segundo-vigilante/companheiro?id=<?= urlencode((string) ($companheiro['id'] ?? '')) ?>" class="btn btn-sm btn-outline-primary mt-3 sm:mt-0">
                                Ver Linha do Tempo
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Coluna Lateral (1/3) -->
    <div class="space-y-8">
        <div class="card">
            <div class="card-header"><h2 class="card-title">Titular do Cargo</h2></div>
            <div class="card-body">
                <div class="list-item-report">
                    <p class="text-xs font-bold uppercase text-gray-500">SEGUNDO VIGILANTE</p>
                    <p class="mt-1 text-lg font-semibold"><?= htmlspecialchars(trim((string) ($titularCargo['titular_nome'] ?? '')) ?: 'A definir') ?></p>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Cargo orientado à instrução dos Companheiros, revisão de trabalhos e preparo para exaltação.</p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2 class="card-title">Trilha de Estudo</h2></div>
            <div class="card-body space-y-3">
                <?php foreach ($trilhaEstudo as $ordem => $titulo): ?>
                    <div class="list-item-report">
                        <p class="text-xs font-bold uppercase text-gray-500">Etapa <?= (int) $ordem ?></p>
                        <p class="mt-1 font-semibold"><?= htmlspecialchars($titulo) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../partials/erp_shell_close.php';
?>
