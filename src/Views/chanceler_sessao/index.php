<?php
declare(strict_types=1);

// #############################################################################
// LÓGICA DE NEGÓCIO E HELPERS
// #############################################################################

$mensagemSucesso = $_SESSION['mensagem_sucesso'] ?? null;
$mensagemErro = $_SESSION['mensagem_erro'] ?? null;
unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']);

$sessoes = is_array($sessoes ?? null) ? $sessoes : [];
$confirmados = is_array($confirmados ?? null) ? $confirmados : [];
$visitantesResumo = is_array($visitantesResumo ?? null) ? $visitantesResumo : [];
$mapaPresencas = is_array($mapaPresencas ?? null) ? $mapaPresencas : [];

$sessaoEmFoco = $sessaoSelecionada ?? $proximaSessao ?? null;
$presentesEfetivos = array_values(array_filter(
    $mapaPresencas,
    static fn (array $registro): bool => !empty($registro['presente'])
));

$formatarDataHoraExibicao = static function (?string $valor): string {
    if (empty(trim((string) $valor))) return 'Data a definir';
    try {
        return (new DateTimeImmutable($valor))
            ->setTimezone(new DateTimeZone('America/Sao_Paulo'))
            ->format('d/m/Y \à\s H:i');
    } catch (\Throwable $e) {
        return (string) $valor;
    }
};

$descricaoAgape = static fn (array $sessao): string => 
    match (strtolower(trim((string) ($sessao['agape_modalidade'] ?? 'nao_havera')))) {
        'gratuito' => 'Gratuito',
        'pago' => 'Pago',
        default => 'Não haverá',
    };

$descricaoModeloTesourariaAgape = static function (array $sessao): string {
    if (strtolower(trim((string) ($sessao['agape_modalidade'] ?? 'nao_havera'))) === 'nao_havera') {
        return 'Não se aplica';
    }
    return match (strtolower(trim((string) ($sessao['agape_modelo_financeiro'] ?? 'oficial_loja')))) {
        'particular' => 'Particular',
        'misto' => 'Misto',
        default => 'Oficial da Loja',
    };
};

$paramsCertificado = [];
if ($sessaoEmFoco && !empty($sessaoEmFoco['data_hora_inicio'])) {
    $paramsCertificado = [
        'data_sessao' => substr((string) $sessaoEmFoco['data_hora_inicio'], 0, 10),
        'tipo_sessao' => (string) ($sessaoEmFoco['tipo_sessao'] ?? 'Ordinaria'),
        'grau_sessao' => (string) ($sessaoEmFoco['grau_sessao'] ?? 'Mestre Macom'),
    ];
}
$urlCertificado = '/chancelaria/certificado' . ($paramsCertificado !== [] ? '?' . http_build_query($paramsCertificado) : '');

// #############################################################################
// CONFIGURAÇÃO DO APP SHELL
// #############################################################################

$appShellEyebrow = 'Painel do Chanceler';
$appShellTitle = 'Controle de Sessão';
$appShellDescription = 'Realize o check-in, acompanhe a nominata e gerencie os visitantes para a sessão.';
$appShellActiveHref = '/chanceler/sessao';

require __DIR__ . '/../partials/erp_shell_open.php';

?>

<?php if ($mensagemSucesso): ?>
<div class="alert alert-success mb-6"><?= htmlspecialchars($mensagemSucesso) ?></div>
<?php endif; ?>
<?php if ($mensagemErro): ?>
<div class="alert alert-danger mb-6"><?= htmlspecialchars($mensagemErro) ?></div>
<?php endif; ?>

<!-- Métricas Principais -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="card-metric">
        <span class="card-metric-label">Sessão Ativa</span>
        <span class="card-metric-value truncate"><?= htmlspecialchars((string) ($sessaoEmFoco['titulo'] ?? 'N/D')) ?></span>
        <span class="card-metric-context"><?= htmlspecialchars($formatarDataHoraExibicao($sessaoEmFoco['data_hora_inicio'] ?? null)) ?></span>
    </div>
    <div class="card-metric">
        <span class="card-metric-label">Frequência Efetiva</span>
        <span class="card-metric-value"><?= count($presentesEfetivos) ?></span>
        <span class="card-metric-context">Obreiros presentes</span>
    </div>
    <div class="card-metric">
        <span class="card-metric-label">Visitantes</span>
        <span class="card-metric-value"><?= count($visitantesResumo) ?></span>
        <span class="card-metric-context">Leitura rápida para apoio</span>
    </div>
    <div class="card-metric">
        <span class="card-metric-label">Confirmados (Ágape)</span>
        <span class="card-metric-value"><?= count($confirmados) ?></span>
        <span class="card-metric-context">Presença no ágape</span>
    </div>
</div>

<!-- Seleção de Sessão e Detalhes -->
<div class="card mb-8">
    <div class="card-header">
        <h2 class="card-title">Contexto da Sessão</h2>
        <p class="card-subtitle">Troque a sessão em foco para visualizar os dados correspondentes.</p>
    </div>
    <div class="card-body">
        <form method="GET" action="/chanceler/sessao" class="flex flex-col md:flex-row md:items-end md:gap-4">
            <div class="flex-grow">
                <label for="sessao_id" class="form-label">Selecionar Sessão</label>
                <select id="sessao_id" name="sessao_id" onchange="this.form.submit()" class="form-select">
                    <?php if (empty($sessoes)): ?>
                        <option disabled selected>Nenhuma sessão disponível</option>
                    <?php else: ?>
                        <?php foreach ($sessoes as $sessaoOpcao): ?>
                            <option value="<?= (int) ($sessaoOpcao['id'] ?? 0) ?>" <?= !empty($sessaoEmFoco['id']) && (int) $sessaoEmFoco['id'] === (int) ($sessaoOpcao['id'] ?? 0) ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string) (($sessaoOpcao['titulo'] ?? '') ?: (($sessaoOpcao['tipo_sessao'] ?? 'Sessão') . ' - ' . ($sessaoOpcao['grau_sessao'] ?? '')))) ?>
                                (<?= htmlspecialchars($formatarDataHoraExibicao($sessaoOpcao['data_hora_inicio'] ?? null)) ?>)
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <a href="<?= htmlspecialchars($urlCertificado) ?>" class="btn btn-secondary mt-4 md:mt-0">Emitir Certificado de Visitante</a>
        </form>

        <?php if ($sessaoEmFoco): ?>
            <div class="mt-6 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 text-sm">
                <div class="info-badge"><span>Nominata Prevista</span><strong><?= count($mapaPresencas) ?></strong></div>
                <div class="info-badge"><span>Presentes Efetivos</span><strong><?= count($presentesEfetivos) ?></strong></div>
                <div class="info-badge"><span>Visitantes</span><strong><?= count($visitantesResumo) ?></strong></div>
                <div class="info-badge"><span>Ágape</span><strong><?= htmlspecialchars($descricaoAgape($sessaoEmFoco)) ?></strong></div>
                <div class="info-badge"><span>Modelo Financeiro</span><strong><?= htmlspecialchars($descricaoModeloTesourariaAgape($sessaoEmFoco)) ?></strong></div>
            </div>
        <?php else: ?>
            <div class="alert alert-info mt-6">Nenhuma sessão futura cadastrada.</div>
        <?php endif; ?>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-8">
    <!-- Coluna de Check-in -->
    <div class="xl:col-span-2 space-y-8">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Check-in do Quadro da Loja</h2>
                <p class="card-subtitle">Marque os presentes para compor a lista final da sessão.</p>
            </div>
            <div class="card-body grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php if (!empty($mapaPresencas)): ?>
                    <?php foreach ($mapaPresencas as $registro): ?>
                        <form method="POST" action="/chanceler/sessao/presenca" class="checkin-card">
                            <input type="hidden" name="sessao_id" value="<?= (int) ($sessaoEmFoco['id'] ?? 0) ?>">
                            <input type="hidden" name="obreiro_id" value="<?= htmlspecialchars((string) ($registro['id'] ?? '')) ?>">
                            <div>
                                <div class="font-bold text-gray-800 dark:text-gray-100"><?= htmlspecialchars((string) ($registro['nome'] ?? 'Obreiro')) ?></div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    CIM: <?= htmlspecialchars((string) ($registro['cim'] ?? '-')) ?> &middot; Grau: <?= htmlspecialchars((string) ($registro['grau'] ?? '-')) ?>
                                </div>
                            </div>
                            <div class="flex gap-2 mt-3">
                                <button type="submit" name="presente" value="1" class="btn-checkin <?= !empty($registro['presente']) ? 'presente' : '' ?>">Presente</button>
                                <button type="submit" name="presente" value="0" class="btn-checkin <?= empty($registro['presente']) ? 'ausente' : '' ?>">Ausente</button>
                            </div>
                        </form>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-info md:col-span-2">Nenhuma nominata prevista para esta sessão.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Lista Final de Presentes (<?= count($presentesEfetivos) ?>)</h2>
                <p class="card-subtitle">Conferência rápida da nominata efetiva da sessão.</p>
            </div>
            <div class="card-body grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php if (!empty($presentesEfetivos)): ?>
                    <?php foreach ($presentesEfetivos as $presente): ?>
                        <div class="list-item-condensed">
                            <div class="font-medium text-gray-800 dark:text-gray-100"><?= htmlspecialchars((string) ($presente['nome'] ?? 'Obreiro')) ?></div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                CIM: <?= htmlspecialchars((string) ($presente['cim'] ?? '-')) ?> &middot; Grau: <?= htmlspecialchars((string) ($presente['grau'] ?? '-')) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-info md:col-span-2">Ainda não há presentes efetivos marcados.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Coluna de Visitantes e Confirmados -->
    <div class="space-y-8">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Visitantes Resumidos (<?= count($visitantesResumo) ?>)</h2>
                <p class="card-subtitle">Apoio para Secretaria e Orador.</p>
            </div>
            <div class="card-body space-y-4">
                <?php if ($sessaoEmFoco): ?>
                    <form method="POST" action="/chanceler/sessao/visitante" class="rounded-2xl border border-erp-border bg-erp-surface/60 p-4 space-y-3">
                        <input type="hidden" name="sessao_id" value="<?= (int) ($sessaoEmFoco['id'] ?? 0) ?>">
                        <div class="font-semibold text-gray-800 dark:text-gray-100">Registrar visitante em Loja</div>
                        <div class="grid grid-cols-1 gap-3">
                            <input name="nome" class="form-input" placeholder="Nome do visitante" required>
                            <input name="loja" class="form-input" placeholder="Loja">
                            <input name="oriente" class="form-input" placeholder="Oriente">
                            <input name="potencia" class="form-input" placeholder="Potência">
                            <input name="grau" class="form-input" placeholder="Grau">
                            <textarea name="fala_resumida" rows="2" class="form-textarea" placeholder="Observação ou fala resumida para Orador/Balaústre"></textarea>
                        </div>
                        <button class="btn btn-primary w-full" type="submit">Adicionar visitante</button>
                    </form>
                <?php endif; ?>
                <?php if (!empty($visitantesResumo)): ?>
                    <?php foreach ($visitantesResumo as $visitante): ?>
                        <div class="list-item-condensed">
                            <div class="font-medium text-gray-800 dark:text-gray-100"><?= htmlspecialchars((string) ($visitante['nome'] ?? 'Visitante')) ?></div>
                            <div class="text-sm text-gray-600 dark:text-gray-400"><?= htmlspecialchars((string) ($visitante['linha_resumida'] ?? '')) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-info">Nenhum visitante registrado para esta sessão.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Confirmados para o Ágape (<?= count($confirmados) ?>)</h2>
                <p class="card-subtitle">Lista de presença no ágape.</p>
            </div>
            <div class="card-body space-y-3">
                <?php if (!empty($confirmados)): ?>
                    <?php foreach ($confirmados as $confirmado): ?>
                        <div class="list-item-condensed">
                            <div class="font-medium text-gray-800 dark:text-gray-100"><?= htmlspecialchars((string) ($confirmado['nome'] ?? 'Obreiro')) ?></div>
                            <div class="text-sm text-gray-600 dark:text-gray-400"><?= !empty($confirmado['participara_agape']) ? 'Confirmado com ágape' : 'Confirmado sem ágape' ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-info">Nenhum irmão confirmado para o ágape.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../partials/erp_shell_close.php'; ?>


