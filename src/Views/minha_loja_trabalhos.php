<?php
declare(strict_types=1);

$appShellEyebrow = 'Área do Irmão';
$appShellTitle = 'Meus Trabalhos';
$appShellDescription = 'Envio para validação (Vigilância) e arquivamento (Secretaria).';
$appShellActiveHref = '/minha-loja/trabalhos';
$appShellActions = [['label' => 'Voltar', 'href' => '/minha-loja']];

$submissoes = is_array($submissoes ?? null) ? $submissoes : [];
$sessoes = is_array($sessoes ?? null) ? $sessoes : [];
$grau = (string) ($grau ?? '');
$mensagemSucesso = $mensagemSucesso ?? null;
$mensagemErro = $mensagemErro ?? null;

$labelStatus = static function (string $status): string {
    return match ($status) {
        'pendente_mentor' => 'aguardando mentor',
        'pendente_secretaria' => 'aguardando secretaria',
        'rejeitado' => 'rejeitado',
        'arquivado' => 'arquivado',
        default => $status !== '' ? $status : 'rascunho',
    };
};

require __DIR__ . '/partials/erp_shell_open.php';
?>

<?php if ($mensagemSucesso): ?><div class="alert alert-success mb-6"><?= htmlspecialchars((string) $mensagemSucesso) ?></div><?php endif; ?>
<?php if ($mensagemErro): ?><div class="alert alert-danger mb-6"><?= htmlspecialchars((string) $mensagemErro) ?></div><?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Enviar novo trabalho</h2>
                <p class="card-subtitle">
                    <?php if ($grau !== ''): ?>Seu grau atual: <strong><?= htmlspecialchars($grau) ?></strong>.<?php endif; ?>
                    Aprendiz → 1º Vigilante · Companheiro → 2º Vigilante · Mestre → direto à Secretaria.
                </p>
            </div>
            <div class="card-body">
                <form method="POST" action="/minha-loja/trabalhos/enviar" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="form-label">Título</label>
                        <input class="form-input" name="titulo" required>
                    </div>
                    <div>
                        <label class="form-label">Tipo</label>
                        <select class="form-select" name="tipo_trabalho" required>
                            <option value="peca_arquitetura">Peça de Arquitetura</option>
                            <option value="trabalho_apresentado">Trabalho apresentado</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Sessão (opcional)</label>
                        <select class="form-select" name="sessao_id">
                            <option value="">Selecionar…</option>
                            <?php foreach ($sessoes as $s): ?>
                                <option value="<?= htmlspecialchars((string) ($s['id'] ?? '')) ?>">
                                    <?= htmlspecialchars((string) ($s['titulo'] ?? 'Sessão')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="form-label">PDF (link ou path, opcional)</label>
                        <input class="form-input" name="arquivo_pdf_path" placeholder="ex.: /uploads/... ou https://...">
                    </div>
                    <div class="md:col-span-2 text-right">
                        <button class="btn btn-primary" type="submit">Enviar</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2 class="card-title">Minhas submissões</h2></div>
            <div class="card-body space-y-3">
                <?php if ($submissoes === []): ?>
                    <div class="alert alert-info">Nenhum trabalho enviado.</div>
                <?php else: ?>
                    <?php foreach ($submissoes as $t): ?>
                        <div class="list-item-condensed">
                            <div class="flex items-center justify-between gap-3">
                                <div class="font-semibold"><?= htmlspecialchars((string) ($t['titulo'] ?? '')) ?></div>
                                <span class="badge-status-warning"><?= htmlspecialchars($labelStatus((string) ($t['status'] ?? ''))) ?></span>
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                                <?= htmlspecialchars((string) ($t['tipo_trabalho'] ?? '')) ?>
                                <?php if (!empty($t['mentor_observacao'])): ?> · Obs.: <?= htmlspecialchars((string) $t['mentor_observacao']) ?><?php endif; ?>
                            </div>
                            <?php if (!empty($t['arquivo_pdf_path'])): ?>
                                <div class="mt-2">
                                    <a class="btn btn-secondary btn-sm" href="<?= htmlspecialchars((string) $t['arquivo_pdf_path']) ?>" target="_blank" rel="noopener">Abrir PDF</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="space-y-6">
        <div class="card">
            <div class="card-header"><h2 class="card-title">Dica</h2></div>
            <div class="card-body text-sm text-gray-600 dark:text-gray-300">
                Após apresentação em Loja, o Secretário arquiva o PDF e o trabalho passa a ficar disponível na Biblioteca em <strong>Trabalhos & publicações</strong>.
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/partials/erp_shell_close.php'; ?>

