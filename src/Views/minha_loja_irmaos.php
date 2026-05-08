<?php
declare(strict_types=1);

$appShellEyebrow = 'Área do Irmão';
$appShellTitle = 'Irmãos da Loja';
$appShellDescription = 'Consulta fraterna (sem dados sensíveis). Clique em um nome para ver detalhes.';
$appShellActiveHref = '/minha-loja/irmaos';
$appShellActions = [['label' => 'Voltar', 'href' => '/minha-loja']];

$q = trim((string) ($_GET['q'] ?? ''));
$selecionadoId = trim((string) ($_GET['id'] ?? ''));

$obreiros = is_array($obreiros ?? null) ? $obreiros : [];
$detalhe = is_array($detalhe ?? null) ? $detalhe : null;
$familiaresDetalhe = is_array($familiaresDetalhe ?? null) ? $familiaresDetalhe : [];

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

require __DIR__ . '/partials/erp_shell_open.php';
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-1 space-y-4">
        <div class="card">
            <div class="card-body">
                <form method="GET" action="/minha-loja/irmaos" class="flex gap-2">
                    <input class="form-input" name="q" placeholder="Buscar por nome..." value="<?= htmlspecialchars($q) ?>">
                    <button class="btn btn-secondary" type="submit">Buscar</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2 class="card-title">Nomes</h2></div>
            <div class="card-body p-0">
                <?php if ($obreiros === []): ?>
                    <div class="p-4 text-sm text-gray-600 dark:text-gray-300">Nenhum irmão encontrado.</div>
                <?php else: ?>
                    <div class="divide-y divide-gray-200 dark:divide-gray-800">
                        <?php foreach ($obreiros as $o): ?>
                            <?php $oid = (string) ($o['id'] ?? ''); ?>
                            <a class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-900 <?= $selecionadoId === $oid ? 'bg-gray-50 dark:bg-gray-900' : '' ?>"
                               href="/minha-loja/irmaos?id=<?= urlencode($oid) ?>&q=<?= urlencode($q) ?>">
                                <div class="font-medium"><?= htmlspecialchars((string) ($o['nome_historico'] ?? $o['nome'] ?? '')) ?></div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="lg:col-span-2 space-y-6">
        <?php if (!$detalhe): ?>
            <div class="card">
                <div class="card-body text-sm text-gray-600 dark:text-gray-300">
                    Selecione um nome para ver os dados fraternos e familiares vinculados.
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-header"><h2 class="card-title"><?= htmlspecialchars((string) ($detalhe['nome_historico'] ?? $detalhe['nome'] ?? 'Irmão')) ?></h2></div>
                <div class="card-body grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div><span class="text-gray-500">Grau</span><div class="font-medium"><?= htmlspecialchars((string) ($detalhe['grau'] ?? '-')) ?></div></div>
                    <div><span class="text-gray-500">Telefone</span><div class="font-medium"><?= htmlspecialchars((string) ($detalhe['telefone'] ?? '-')) ?></div></div>
                    <div class="md:col-span-2"><span class="text-gray-500">E-mail</span><div class="font-medium"><?= htmlspecialchars((string) ($detalhe['email'] ?? '-')) ?></div></div>
                    <?php $dn = $fmtData($detalhe['data_nascimento_civil'] ?? null); ?>
                    <div><span class="text-gray-500">Nascimento</span><div class="font-medium"><?= htmlspecialchars((string) ($dn ?: '-')) ?></div></div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h2 class="card-title">Familiares vinculados</h2></div>
                <div class="card-body space-y-3">
                    <?php if ($familiaresDetalhe === []): ?>
                        <div class="text-sm text-gray-600 dark:text-gray-300">Nenhum familiar cadastrado.</div>
                    <?php else: ?>
                        <?php foreach ($familiaresDetalhe as $f): ?>
                            <div class="list-item-condensed">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="font-semibold"><?= htmlspecialchars((string) ($f['nome_completo'] ?? '')) ?></div>
                                    <span class="text-xs text-gray-500"><?= htmlspecialchars((string) ($f['parentesco'] ?? '')) ?></span>
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                                    <?php $fdn = $fmtData($f['data_nascimento'] ?? null); ?>
                                    <?php if ($fdn): ?>Nasc.: <?= htmlspecialchars((string) $fdn) ?><?php endif; ?>
                                    <?php if (!empty($f['falecido'])): ?> · Falecido<?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/partials/erp_shell_close.php'; ?>

