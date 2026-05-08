<?php
declare(strict_types=1);

$appShellEyebrow = 'Secretaria';
$appShellTitle = 'Trabalhos & Publicações';
$appShellDescription = 'Arquive e publique na Biblioteca após a apresentação em Loja.';
$appShellActiveHref = '/secretaria/trabalhos-publicacoes';
$appShellActions = [['label' => 'Voltar', 'href' => '/secretaria']];

$pendentes = is_array($pendentes ?? null) ? $pendentes : [];
$trabalhosRecentes = is_array($trabalhosRecentes ?? null) ? $trabalhosRecentes : [];
$sessoes = is_array($sessoes ?? null) ? $sessoes : [];

require __DIR__ . '/_sidebar.php';
require __DIR__ . '/../partials/erp_shell_open.php';
?>

<?php if (!empty($_SESSION['mensagem_sucesso'])): ?><div class="alert alert-success mb-6"><?= htmlspecialchars((string) $_SESSION['mensagem_sucesso']) ?></div><?php unset($_SESSION['mensagem_sucesso']); endif; ?>
<?php if (!empty($_SESSION['mensagem_erro'])): ?><div class="alert alert-danger mb-6"><?= htmlspecialchars((string) $_SESSION['mensagem_erro']) ?></div><?php unset($_SESSION['mensagem_erro']); endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Pendentes para arquivamento</h2>
                <p class="card-subtitle">Após arquivar, o trabalho fica disponível na Biblioteca em “Trabalhos & publicações”.</p>
            </div>
            <div class="card-body space-y-3">
                <?php if ($pendentes === []): ?>
                    <div class="alert alert-info">Nenhuma submissão pendente.</div>
                <?php else: ?>
                    <?php foreach ($pendentes as $p): ?>
                        <div class="list-item-condensed">
                            <div class="flex items-center justify-between gap-3">
                                <div class="font-semibold"><?= htmlspecialchars((string) ($p['titulo'] ?? '')) ?></div>
                                <span class="text-xs text-gray-500"><?= htmlspecialchars((string) ($p['obreiro_nome'] ?? '')) ?></span>
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                                <?= htmlspecialchars((string) ($p['tipo_trabalho'] ?? '')) ?>
                                <?php if (!empty($p['mentor_decisao'])): ?> · Mentor: <?= htmlspecialchars((string) $p['mentor_decisao']) ?><?php endif; ?>
                            </div>
                            <form method="POST" action="/secretaria/trabalhos-publicacoes/arquivar" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                                <input type="hidden" name="id" value="<?= htmlspecialchars((string) ($p['id'] ?? '')) ?>">
                                <input type="hidden" name="autor_id" value="<?= htmlspecialchars((string) ($p['obreiro_id'] ?? '')) ?>">

                                <div class="md:col-span-2">
                                    <label class="form-label">Título</label>
                                    <input class="form-input" name="titulo" value="<?= htmlspecialchars((string) ($p['titulo'] ?? '')) ?>" required>
                                </div>
                                <div>
                                    <label class="form-label">Tipo</label>
                                    <?php $tipo = (string) ($p['tipo_trabalho'] ?? 'peca_arquitetura'); ?>
                                    <select class="form-select" name="tipo_trabalho" required>
                                        <option value="peca_arquitetura" <?= $tipo === 'peca_arquitetura' ? 'selected' : '' ?>>Peça de Arquitetura</option>
                                        <option value="trabalho_apresentado" <?= $tipo === 'trabalho_apresentado' ? 'selected' : '' ?>>Trabalho apresentado</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label">Sessão (obrigatório)</label>
                                    <select class="form-select" name="sessao_id" required>
                                        <option value="">Selecionar…</option>
                                        <?php foreach ($sessoes as $s): ?>
                                            <?php $sid = (string) ($s['id'] ?? ''); ?>
                                            <option value="<?= htmlspecialchars($sid) ?>" <?= (string) ($p['sessao_id'] ?? '') === $sid ? 'selected' : '' ?>>
                                                <?= htmlspecialchars((string) ($s['titulo'] ?? 'Sessão')) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="form-label">PDF (link ou path)</label>
                                    <input class="form-input" name="arquivo_pdf_path" value="<?= htmlspecialchars((string) ($p['arquivo_pdf_path'] ?? '')) ?>" required>
                                </div>
                                <div>
                                    <label class="form-label">Registro na Potência</label>
                                    <select class="form-select" name="status_envio_potencia">
                                        <option value="pendente">Pendente</option>
                                        <option value="enviado">Enviado</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label">Observação (opcional)</label>
                                    <input class="form-input" name="observacao">
                                </div>
                                <div class="md:col-span-2 text-right">
                                    <button class="btn btn-primary" type="submit">Arquivar e publicar</button>
                                </div>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="space-y-6">
        <div class="card">
            <div class="card-header"><h2 class="card-title">Arquivos recentes</h2></div>
            <div class="card-body space-y-2">
                <?php foreach (array_slice($trabalhosRecentes, 0, 10) as $t): ?>
                    <div class="text-sm">
                        <div class="font-medium"><?= htmlspecialchars((string) ($t['titulo'] ?? '')) ?></div>
                        <div class="text-gray-600 dark:text-gray-300">
                            <?= htmlspecialchars((string) ($t['tipo_trabalho'] ?? '')) ?>
                            <?php if (!empty($t['autor_nome'])): ?> · <?= htmlspecialchars((string) $t['autor_nome']) ?><?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if ($trabalhosRecentes === []): ?>
                    <div class="text-sm text-gray-600 dark:text-gray-300">Nenhum arquivo ainda.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../partials/erp_shell_close.php'; ?>
