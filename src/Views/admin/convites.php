<?php
/** @var array $pendentes */
/** @var array $convites */

$mensagemSucesso = $_SESSION['mensagem_sucesso'] ?? null;
$mensagemErro = $_SESSION['mensagem_erro'] ?? null;
$conviteGeradoLink = $_SESSION['convite_gerado_link'] ?? null;
$conviteGeradoExpiraEm = $_SESSION['convite_gerado_expira_em'] ?? null;

unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro'], $_SESSION['convite_gerado_link'], $_SESSION['convite_gerado_expira_em']);

$conviteGeradoUrl = null;
if (is_string($conviteGeradoLink) && $conviteGeradoLink !== '' && filter_var($conviteGeradoLink, FILTER_VALIDATE_URL)) {
    $conviteGeradoUrl = $conviteGeradoLink;
}

$conviteModel = new \App\Models\ConviteAcesso();
$now = new DateTimeImmutable('now');

$erpPageTitle = 'Convites de acesso - Secretaria';
$appShellEyebrow = 'Secretaria';
$appShellTitle = 'Convites de acesso';
$appShellDescription = 'Gere links de ativacao para obreiros pendentes (expira em 7 dias; uso unico).';
$appShellActiveHref = '/admin/convites';
$appShellActions = [
    ['label' => 'Central de obreiros', 'href' => '/obreiros'],
    ['label' => 'Acessos', 'href' => '/admin/acessos'],
];
$appShellSidebarSections = [
    [
        'title' => 'Secretaria',
        'items' => [
            ['label' => 'Nominata oficial', 'href' => '/admin/cargos'],
            ['label' => 'Central de Obreiros', 'href' => '/obreiros'],
            ['label' => 'Convites de acesso', 'href' => '/admin/convites'],
            ['label' => 'Acessos', 'href' => '/admin/acessos'],
            ['label' => 'Sessões', 'href' => '/secretaria'],
            ['label' => 'Balaustres / votação', 'href' => '/secretaria/votacao'],
        ],
    ],
    [
        'title' => 'Geral',
        'items' => [
            ['label' => 'Painel', 'href' => '/dashboard'],
        ],
    ],
];
?>
<?php require __DIR__ . '/../partials/erp_head.php'; ?>
<?php require __DIR__ . '/../partials/erp_shell_open.php'; ?>
<div class="space-y-6">

    <?php if ($mensagemSucesso): ?>
        <div class="rounded-lg bg-emerald-50 p-3 text-sm text-emerald-800 border border-emerald-100">
            <?= htmlspecialchars((string) $mensagemSucesso) ?>
        </div>
    <?php endif; ?>
    <?php if ($mensagemErro): ?>
        <div class="rounded-lg bg-rose-50 p-3 text-sm text-rose-800 border border-rose-100">
            <?= htmlspecialchars((string) $mensagemErro) ?>
        </div>
    <?php endif; ?>

    <?php if ($conviteGeradoLink): ?>
        <div class="rounded-lg bg-white border border-slate-200 p-4 space-y-2">
            <div class="text-sm font-semibold">Link gerado</div>
            <div class="flex flex-col gap-2 md:flex-row md:items-center">
                <input id="convite-link-admin-atual" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" readonly value="<?= htmlspecialchars((string) $conviteGeradoLink) ?>">
                <button type="button" class="copy-btn rounded-md bg-slate-900 px-4 py-2 text-sm text-white" data-copy="<?= htmlspecialchars((string) $conviteGeradoLink) ?>">Copiar link</button>
                <button type="button" class="select-btn rounded-md border border-slate-300 bg-white px-4 py-2 text-sm text-slate-800" data-target="convite-link-admin-atual">Selecionar link</button>
                <?php if ($conviteGeradoUrl): ?>
                    <a href="<?= htmlspecialchars((string) $conviteGeradoUrl) ?>" target="_blank" rel="noopener" class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm text-slate-900 text-center">Abrir no Telegram</a>
                <?php endif; ?>
            </div>
            <?php if ($conviteGeradoExpiraEm): ?>
                <div class="text-xs text-slate-500">Expira em: <?= htmlspecialchars((string) $conviteGeradoExpiraEm) ?></div>
            <?php endif; ?>
            <div class="text-xs text-slate-500">Telefone não é obrigatório para gerar convite. O link pode ser encaminhado por qualquer canal.</div>
        </div>
    <?php endif; ?>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-lg bg-white border border-slate-200 p-4">
            <div class="flex items-center justify-between">
                <div class="text-sm font-semibold">Obreiros pendentes</div>
                <div class="text-xs text-slate-500"><?= count($pendentes ?? []) ?> item(ns)</div>
            </div>
            <div class="mt-4 space-y-3">
                <?php if (empty($pendentes)): ?>
                    <div class="text-sm text-slate-500">Nenhum pendente no momento.</div>
                <?php else: ?>
                    <?php foreach ($pendentes as $p): ?>
                        <form method="post" action="/admin/convites/gerar" class="rounded-lg border border-slate-200 p-3 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                            <input type="hidden" name="obreiro_id" value="<?= htmlspecialchars((string) ($p['id'] ?? '')) ?>">
                            <div>
                                <div class="text-sm font-medium"><?= htmlspecialchars((string) (($p['nome_historico'] ?? $p['nome'] ?? '') ?: 'Obreiro')) ?></div>
                                <div class="text-xs text-slate-500">CIM <?= htmlspecialchars((string) ($p['cim'] ?? '-')) ?></div>
                            </div>
                            <button type="submit" class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-medium text-white">Gerar convite</button>
                        </form>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="rounded-lg bg-white border border-slate-200 p-4">
            <div class="flex items-center justify-between">
                <div class="text-sm font-semibold">Convites recentes</div>
                <div class="text-xs text-slate-500"><?= count($convites ?? []) ?> item(ns)</div>
            </div>
            <div class="mt-4 space-y-3">
                <?php if (empty($convites)): ?>
                    <div class="text-sm text-slate-500">Nenhum convite gerado.</div>
                <?php else: ?>
                    <?php foreach ($convites as $c): ?>
                        <?php
                        $token = (string) ($c['token'] ?? '');
                        $masked = $conviteModel->maskToken($token);
                        $expira = !empty($c['expira_em']) ? new DateTimeImmutable((string) $c['expira_em']) : null;
                        $usado = !empty($c['usado_em']) ? new DateTimeImmutable((string) $c['usado_em']) : null;
                        $status = 'ativo';
                        if ($usado) $status = 'usado';
                        elseif ($expira && $expira <= $now) $status = 'expirado';
                        $deepLink = $token !== '' ? $conviteModel->deepLinkForToken($token) : '';
                        $deepUrl = ($deepLink !== '' && filter_var($deepLink, FILTER_VALIDATE_URL)) ? $deepLink : null;
                        ?>
                        <div class="rounded-lg border border-slate-200 p-3 space-y-2">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <div class="text-sm font-medium"><?= htmlspecialchars((string) (($c['nome'] ?? '') ?: 'Obreiro')) ?></div>
                                    <div class="text-xs text-slate-500">CIM <?= htmlspecialchars((string) ($c['cim'] ?? '-')) ?> · Token <?= htmlspecialchars($masked) ?></div>
                                </div>
                                <span class="text-xs px-2 py-1 rounded-full <?= $status === 'ativo' ? 'bg-emerald-50 text-emerald-700' : ($status === 'usado' ? 'bg-slate-100 text-slate-700' : 'bg-amber-50 text-amber-700') ?>">
                                    <?= htmlspecialchars($status) ?>
                                </span>
                            </div>
                            <div class="text-xs text-slate-500">
                                Expira: <?= htmlspecialchars((string) ($c['expira_em'] ?? '-')) ?>
                                <?php if ($usado): ?> · Usado: <?= htmlspecialchars((string) ($c['usado_em'] ?? '-')) ?><?php endif; ?>
                            </div>
                            <?php if ($deepLink !== ''): ?>
                                <div class="flex flex-col gap-2 md:flex-row md:items-center">
                                    <?php $inputId = 'convite-link-' . (string) ($c['id'] ?? uniqid('conv_', true)); ?>
                                    <input id="<?= htmlspecialchars($inputId) ?>" class="w-full rounded-md border border-slate-300 px-3 py-2 text-xs" readonly value="<?= htmlspecialchars((string) $deepLink) ?>">
                                    <button type="button" class="copy-btn rounded-md bg-slate-900 px-3 py-2 text-xs text-white" data-copy="<?= htmlspecialchars((string) $deepLink) ?>">Copiar link</button>
                                    <button type="button" class="select-btn rounded-md border border-slate-300 bg-white px-3 py-2 text-xs text-slate-800" data-target="<?= htmlspecialchars($inputId) ?>">Selecionar</button>
                                    <?php if ($deepUrl): ?>
                                        <a href="<?= htmlspecialchars((string) $deepUrl) ?>" target="_blank" rel="noopener" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-xs text-slate-900 text-center">Abrir no Telegram</a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script>
document.querySelectorAll('.copy-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
        const text = btn.getAttribute('data-copy') || '';
        if (!text) return;
        try {
            await navigator.clipboard.writeText(text);
            const original = btn.textContent;
            btn.textContent = 'Copiado';
            setTimeout(() => (btn.textContent = original || 'Copiar link'), 1200);
        } catch (e) {
            alert('Não foi possível copiar. Use o botão Selecionar e copie manualmente.');
        }
    });
});
document.querySelectorAll('.select-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const targetId = btn.getAttribute('data-target') || '';
        const field = targetId ? document.getElementById(targetId) : null;
        if (!field) return;
        field.focus();
        field.select();
    });
});
</script>
<?php require __DIR__ . '/../partials/erp_shell_close.php'; ?>

