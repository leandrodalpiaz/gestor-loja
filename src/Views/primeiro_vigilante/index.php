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
        'nao_iniciado' => 'badge-status-secondary',
        'em_andamento' => 'badge-status-info',
        'concluido' => 'badge-status-success',
        'aguardando_devolutiva' => 'badge-status-warning',
        default => 'badge-status-secondary',
    };
};

// #############################################################################
// CONFIGURAÇÃO DO APP SHELL
// #############################################################################

$appShellEyebrow = 'Primeiro Vigilante';
$appShellTitle = 'Painel de Acompanhamento';
$appShellDescription = 'Acompanhamento formativo dos Aprendizes, trilha de estudos, orientações e leituras.';
$appShellActiveHref = '/primeiro-vigilante';

require __DIR__ . '/../partials/erp_shell_open.php';
?>

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
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6 mb-8">
    <div class="card-metric">
        <p class="card-metric-label">Aprendizes Ativos</p>
        <p class="card-metric-value"><?= (int) ($resumo['aprendizes_ativos'] ?? 0) ?></p>
    </div>
    <div class="card-metric">
        <p class="card-metric-label">Etapa Inicial</p>
        <p class="card-metric-value"><?= (int) ($resumo['etapa_inicial'] ?? 0) ?></p>
    </div>
    <div class="card-metric">
        <p class="card-metric-label">Sob Mentoria</p>
        <p class="card-metric-value"><?= (int) ($resumo['trabalhos_aguardando_recebimento'] ?? 0) ?></p>
    </div>
    <div class="card-metric">
        <p class="card-metric-label">Aptos para Certificado</p>
        <p class="card-metric-value"><?= (int) ($resumo['aptos_certificado'] ?? 0) ?></p>
    </div>
    <div class="card-metric">
        <p class="card-metric-label">Leituras Sugeridas</p>
        <p class="card-metric-value"><?= (int) ($resumo['leituras_sugeridas'] ?? 0) ?></p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Coluna Principal (2/3) -->
    <div class="lg:col-span-2">
        <div class="card depth-1">
            <div class="card-header border-b border-white/5 p-6">
                <h2 class="card-title">Aprendizes em Acompanhamento</h2>
                <p class="card-subtitle">Painel de mentoria formativa com trilha, leitura orientada e preparo do certificado.</p>
            </div>
            <div class="card-body p-6 divide-y divide-white/5">
                <?php if (empty($aprendizes)): ?>
                    <p class="text-center text-slate-400 py-10">Nenhum Aprendiz ativo encontrado.</p>
                <?php else: ?>
                    <?php foreach ($aprendizes as $aprendiz): ?>
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between py-4 first:pt-0 last:pb-0 gap-3">
                            <div class="flex-grow">
                                <p class="font-semibold text-white"><?= htmlspecialchars((string) ($aprendiz['nome_historico'] ?? $aprendiz['nome'] ?? 'Aprendiz')) ?></p>
                                <p class="text-xs text-slate-400 mt-1">CIM <?= htmlspecialchars((string) ($aprendiz['cim'] ?? '-')) ?> &middot; Iniciação: <?= !empty($aprendiz['data_iniciacao']) ? htmlspecialchars(date('d/m/Y', strtotime((string) $aprendiz['data_iniciacao']))) : 'Não informada' ?></p>
                                <div class="mt-2 flex items-center gap-3 text-xs">
                                    <span class="font-semibold text-slate-300">Etapa <?= (int) ($aprendiz['trilha_etapa_atual'] ?? 1) ?>:</span>
                                    <span class="text-slate-400"><?= htmlspecialchars((string) ($aprendiz['trilha_titulo_atual'] ?? '')) ?></span>
                                    <span class="badge-status <?= $badgeStatus((string) ($aprendiz['trilha_status_atual'] ?? 'nao_iniciado')) ?>"><?= str_replace('_', ' ', (string) ($aprendiz['trilha_status_atual'] ?? 'nao_iniciado')) ?></span>
                                </div>
                            </div>
                            <a href="/primeiro-vigilante/aprendiz?id=<?= urlencode((string) ($aprendiz['id'] ?? '')) ?>" class="btn border border-white/10 text-slate-300 hover:bg-white/5 py-1.5 px-4 text-xs font-semibold shrink-0">
                                Ver Linha de Mentoria
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Coluna Lateral (1/3) -->
    <div class="space-y-6">
        <div class="card depth-1 p-6">
            <div class="card-header border-b border-white/5 pb-3 mb-4">
                <h2 class="card-title">Titular do Cargo</h2>
            </div>
            <div class="card-body">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-erp-gold">PRIMEIRO VIGILANTE</p>
                    <p class="mt-1 text-lg font-semibold text-white"><?= htmlspecialchars(trim((string) ($titularCargo['titular_nome'] ?? '')) ?: 'A definir') ?></p>
                    <p class="mt-2 text-xs leading-5 text-slate-400">Cargo orientado à instrução, mentoria de trabalhos e incentivo ao estudo dos Aprendizes.</p>
                </div>
            </div>
        </div>

        <div class="card depth-1 p-6">
            <div class="card-header border-b border-white/5 pb-3 mb-4">
                <h2 class="card-title">Biblioteca</h2>
            </div>
            <div class="card-body">
                <a href="/biblioteca" class="flex items-center justify-between rounded-xl border border-white/5 bg-white/[0.02] p-4 hover:bg-white/5 transition text-sm">
                    <span class="text-slate-200">Recomendar livros do acervo por grau</span>
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </a>
                <p class="mt-3 text-[11px] text-slate-400">A recomendação orienta a formação dos Aprendizes e não restringe o acesso ao acervo.</p>
            </div>
        </div>

        <div class="card depth-1 p-6">
            <div class="card-header border-b border-white/5 pb-3 mb-4">
                <h2 class="card-title">Trilha de Estudo</h2>
            </div>
            <div class="card-body space-y-3">
                <?php foreach ($trilhaEstudo as $ordem => $titulo): ?>
                    <div class="rounded-xl border border-white/5 bg-white/[0.02] p-4">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Etapa <?= (int) $ordem ?></p>
                        <p class="mt-1 text-sm font-semibold text-white"><?= htmlspecialchars($titulo) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../partials/erp_shell_close.php';
?>
