<?php
declare(strict_types=1);

$appShellEyebrow = 'Área do Irmão';
$appShellTitle = 'Solicitações à Secretaria';
$appShellDescription = 'Peça correções de campos controlados e acompanhe o status.';
$appShellActiveHref = '/minha-loja/solicitacoes';
$appShellActions = [['label' => 'Voltar', 'href' => '/minha-loja']];

$solicitacoes = is_array($solicitacoes ?? null) ? $solicitacoes : [];
$mensagemSucesso = $mensagemSucesso ?? null;
$mensagemErro = $mensagemErro ?? null;

require __DIR__ . '/partials/erp_shell_open.php';
?>

<?php if ($mensagemSucesso): ?><div class="alert alert-success mb-6"><?= htmlspecialchars((string) $mensagemSucesso) ?></div><?php endif; ?>
<?php if ($mensagemErro): ?><div class="alert alert-danger mb-6"><?= htmlspecialchars((string) $mensagemErro) ?></div><?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <div class="card">
            <div class="card-header"><h2 class="card-title">Nova solicitação</h2></div>
            <div class="card-body">
                <form method="POST" action="/minha-loja/solicitacoes/salvar" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label" for="tipo_solicitacao">Tipo</label>
                        <select class="form-select" id="tipo_solicitacao" name="tipo_solicitacao" required>
                            <option value="corrigir_grau">Corrigir grau</option>
                            <option value="corrigir_loja">Corrigir loja</option>
                            <option value="corrigir_numero_loja">Corrigir número da loja</option>
                            <option value="corrigir_oriente">Corrigir oriente</option>
                            <option value="corrigir_potencia">Corrigir potência</option>
                            <option value="corrigir_rito">Corrigir rito</option>
                            <option value="revisar_familiar">Revisar familiar</option>
                            <option value="enviar_trabalho">Enviar trabalho</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="valor_solicitado">Valor solicitado (opcional)</label>
                        <input class="form-input" id="valor_solicitado" name="valor_solicitado">
                    </div>
                    <div class="md:col-span-2">
                        <label class="form-label" for="justificativa">Justificativa</label>
                        <textarea class="form-textarea" id="justificativa" name="justificativa" rows="3" required></textarea>
                    </div>
                    <div class="md:col-span-2 text-right">
                        <button class="btn btn-primary" type="submit">Enviar</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2 class="card-title">Minhas solicitações</h2></div>
            <div class="card-body space-y-3">
                <?php if ($solicitacoes === []): ?>
                    <div class="alert alert-info">Nenhuma solicitação registrada.</div>
                <?php else: ?>
                    <?php foreach ($solicitacoes as $s): ?>
                        <div class="list-item-condensed">
                            <div class="flex items-center justify-between gap-3">
                                <div class="font-semibold"><?= htmlspecialchars((string) ($s['tipo_solicitacao'] ?? '')) ?></div>
                                <span class="badge-status-warning"><?= htmlspecialchars((string) ($s['status'] ?? 'pendente')) ?></span>
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                                <?= htmlspecialchars((string) ($s['justificativa'] ?? '')) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="space-y-6">
        <div class="card">
            <div class="card-header"><h2 class="card-title">Processo</h2></div>
            <div class="card-body text-sm text-gray-600 dark:text-gray-300">
                A Secretaria analisa e pode aprovar, rejeitar ou solicitar ajuste. Campos controlados permanecem somente leitura para o irmão.
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/partials/erp_shell_close.php'; ?>

