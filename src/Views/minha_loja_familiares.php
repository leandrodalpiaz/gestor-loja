<?php
declare(strict_types=1);

$appShellEyebrow = 'Área do Irmão';
$appShellTitle = 'Familiares';
$appShellDescription = 'Cadastre esposa/esposo/filho/filha para efemérides (sujeito à revisão).';
$appShellActiveHref = '/minha-loja/familiares';
$appShellActions = [['label' => 'Voltar', 'href' => '/minha-loja']];

$familiares = is_array($familiares ?? null) ? $familiares : [];
$mensagemSucesso = $mensagemSucesso ?? null;
$mensagemErro = $mensagemErro ?? null;

$fmtData = static function (?string $data): ?string {
    $data = trim((string) $data);
    if ($data === '') {
        return null;
    }
    try {
        return (new DateTimeImmutable($data))->format('d-m-Y');
    } catch (Throwable $e) {
        return $data;
    }
};

$statusLabel = static function (string $status): string {
    return match ($status) {
        'revisado' => 'revisado',
        'corrigir' => 'corrigir',
        default => 'pendente',
    };
};

require __DIR__ . '/partials/erp_shell_open.php';
?>

<?php if ($mensagemSucesso): ?><div class="alert alert-success mb-6"><?= htmlspecialchars((string) $mensagemSucesso) ?></div><?php endif; ?>
<?php if ($mensagemErro): ?><div class="alert alert-danger mb-6"><?= htmlspecialchars((string) $mensagemErro) ?></div><?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <div class="card">
            <div class="card-header"><h2 class="card-title">Novo familiar</h2></div>
            <div class="card-body">
                <form method="POST" action="/minha-loja/familiares/salvar" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="form-label" for="nome_completo">Nome completo</label>
                        <input class="form-input" id="nome_completo" name="nome_completo" required>
                    </div>
                    <div>
                        <label class="form-label" for="parentesco">Parentesco</label>
                        <select class="form-select" id="parentesco" name="parentesco" required>
                            <option value="esposa">Esposa</option>
                            <option value="esposo">Esposo</option>
                            <option value="filho">Filho</option>
                            <option value="filha">Filha</option>
                            <option value="enteado">Enteado</option>
                            <option value="enteada">Enteada</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="data_nascimento">Data de nascimento</label>
                        <input class="form-input" id="data_nascimento" type="date" name="data_nascimento">
                    </div>
                    <div>
                        <label class="form-label" for="data_casamento">Data de casamento (se aplicável)</label>
                        <input class="form-input" id="data_casamento" type="date" name="data_casamento">
                    </div>
                    <div class="flex items-center gap-2">
                        <input id="falecido" type="checkbox" name="falecido" value="1">
                        <label for="falecido" class="text-sm">Falecido</label>
                    </div>
                    <div class="md:col-span-2 text-right">
                        <button class="btn btn-primary" type="submit">Salvar</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2 class="card-title">Meus familiares</h2></div>
            <div class="card-body space-y-3">
                <?php if ($familiares === []): ?>
                    <div class="alert alert-info">Nenhum familiar cadastrado.</div>
                <?php else: ?>
                    <?php foreach ($familiares as $f): ?>
                        <div class="list-item-condensed" data-fam-item="1">
                            <div class="flex items-center justify-between gap-3">
                                <div class="font-semibold"><?= htmlspecialchars((string) ($f['nome_completo'] ?? '')) ?></div>
                                <span class="badge-status-warning"><?= htmlspecialchars($statusLabel((string) ($f['status_revisao'] ?? 'pendente'))) ?></span>
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                                <?= htmlspecialchars((string) ($f['parentesco'] ?? '')) ?>
                                <?php $dn = $fmtData($f['data_nascimento'] ?? null); ?>
                                <?php if ($dn): ?> · Nasc.: <?= htmlspecialchars((string) $dn) ?><?php endif; ?>
                                <?php if (!empty($f['falecido'])): ?> · Falecido<?php endif; ?>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <button type="button" class="btn btn-secondary btn-sm" data-fam-action="toggle">Editar</button>
                                <button type="button" class="btn btn-secondary btn-sm hidden" data-fam-action="cancel">Cancelar</button>
                                <button type="submit" form="fam-form-<?= htmlspecialchars((string) ($f['id'] ?? '')) ?>" class="btn btn-primary btn-sm hidden" data-fam-action="confirm">Confirmar</button>
                            </div>

                            <form id="fam-form-<?= htmlspecialchars((string) ($f['id'] ?? '')) ?>" method="POST" action="/minha-loja/familiares/atualizar" class="mt-4 hidden" data-fam-form="1">
                                <input type="hidden" name="id" value="<?= htmlspecialchars((string) ($f['id'] ?? '')) ?>">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="md:col-span-2">
                                        <label class="form-label">Nome completo</label>
                                        <input class="form-input" name="nome_completo" value="<?= htmlspecialchars((string) ($f['nome_completo'] ?? '')) ?>" required>
                                    </div>
                                    <div>
                                        <label class="form-label">Parentesco</label>
                                        <select class="form-select" name="parentesco" required>
                                            <?php $p = strtolower((string) ($f['parentesco'] ?? '')); ?>
                                            <option value="esposa" <?= $p === 'esposa' ? 'selected' : '' ?>>Esposa</option>
                                            <option value="esposo" <?= $p === 'esposo' ? 'selected' : '' ?>>Esposo</option>
                                            <option value="filho" <?= $p === 'filho' ? 'selected' : '' ?>>Filho</option>
                                            <option value="filha" <?= $p === 'filha' ? 'selected' : '' ?>>Filha</option>
                                            <option value="enteado">Enteado</option>
                                            <option value="enteada">Enteada</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="form-label">Data de nascimento</label>
                                        <input class="form-input" type="date" name="data_nascimento" value="<?= htmlspecialchars((string) ($f['data_nascimento'] ?? '')) ?>">
                                    </div>
                                    <div>
                                        <label class="form-label">Data de casamento (se aplicável)</label>
                                        <input class="form-input" type="date" name="data_casamento" value="<?= htmlspecialchars((string) ($f['data_casamento'] ?? '')) ?>">
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <input id="falecido-<?= htmlspecialchars((string) ($f['id'] ?? '')) ?>" type="checkbox" name="falecido" value="1" <?= !empty($f['falecido']) ? 'checked' : '' ?>>
                                        <label for="falecido-<?= htmlspecialchars((string) ($f['id'] ?? '')) ?>" class="text-sm">Falecido</label>
                                    </div>
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
            <div class="card-header"><h2 class="card-title">Revisão</h2></div>
            <div class="card-body text-sm text-gray-600 dark:text-gray-300">
                Cadastros novos entram como <strong>pendente</strong> para revisão da Secretaria, evitando publicação indevida em efemérides.
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/partials/erp_shell_close.php'; ?>

<script>
(() => {
  document.querySelectorAll('[data-fam-item=\"1\"]').forEach(item => {
    const btnToggle = item.querySelector('[data-fam-action=\"toggle\"]');
    const btnCancel = item.querySelector('[data-fam-action=\"cancel\"]');
    const btnConfirm = item.querySelector('[data-fam-action=\"confirm\"]');
    const form = item.querySelector('[data-fam-form=\"1\"]');
    if (!btnToggle || !btnCancel || !btnConfirm || !form) return;

    const setOpen = (open) => {
      form.classList.toggle('hidden', !open);
      btnCancel.classList.toggle('hidden', !open);
      btnConfirm.classList.toggle('hidden', !open);
      btnToggle.classList.toggle('hidden', open);
    };

    setOpen(false);
    btnToggle.addEventListener('click', () => setOpen(true));
    btnCancel.addEventListener('click', () => setOpen(false));
  });
})();
</script>
