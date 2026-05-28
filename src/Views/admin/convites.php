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

$erpPageTitle = 'Convites de acesso';
$appShellEyebrow = 'Secretaria';
$appShellTitle = 'Convites de acesso';
$appShellDescription = 'Gere links de ativação para obreiros pendentes (expira em 7 dias; uso único).';
$appShellActiveHref = '/admin/convites';
$appShellActions = [
    ['label' => 'Central de obreiros', 'href' => '/obreiros'],
    ['label' => 'Acessos', 'href' => '/admin/acessos'],
];

$appShellSidebarSections = [
    [
        'title' => 'Secretaria',
        'items' => [
            ['label' => 'Nominata Oficial', 'href' => '/admin/cargos'],
            ['label' => 'Central de Obreiros', 'href' => '/obreiros'],
            ['label' => 'Convites de acesso', 'href' => '/admin/convites'],
            ['label' => 'Acessos', 'href' => '/admin/acessos'],
            ['label' => 'Sessões', 'href' => '/secretaria'],
            ['label' => 'Balaústres / votação', 'href' => '/secretaria/votacao'],
            ['label' => 'Relatório Anual', 'href' => '/secretaria/relatorio-anual'],
            ['label' => 'Painel', 'href' => '/dashboard'],
        ],
    ],
];

if (!empty($_SESSION['is_system_admin'])) {
    $appShellSidebarSections[] = [
        'title' => 'Sistema',
        'items' => [
            ['label' => 'Painel do sistema', 'href' => '/sistema'],
            ['label' => 'Parâmetros da Loja', 'href' => '/admin/loja'],
        ],
    ];
}
?>
<?php require __DIR__ . '/../partials/erp_head.php'; ?>
<?php require __DIR__ . '/../partials/erp_shell_open.php'; ?>

<div class="space-y-6">

    <?php if ($mensagemSucesso): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars((string) $mensagemSucesso) ?>
        </div>
    <?php endif; ?>
    <?php if ($mensagemErro): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars((string) $mensagemErro) ?>
        </div>
    <?php endif; ?>

    <?php if ($conviteGeradoLink): ?>
        <div class="card depth-1 p-4 space-y-2 mb-6">
            <div class="text-sm font-semibold text-white">Link gerado</div>
            <div class="flex flex-col gap-2 md:flex-row md:items-center">
                <input id="convite-link-admin-atual" class="form-input w-full" readonly value="<?= htmlspecialchars((string) $conviteGeradoLink) ?>">
                <button type="button" class="copy-btn btn btn-primary" data-copy="<?= htmlspecialchars((string) $conviteGeradoLink) ?>">Copiar link</button>
                <button type="button" class="select-btn btn border border-white/10 text-slate-300 hover:bg-white/5" data-target="convite-link-admin-atual">Selecionar link</button>
                <?php if ($conviteGeradoUrl): ?>
                    <a href="<?= htmlspecialchars((string) $conviteGeradoUrl) ?>" target="_blank" rel="noopener" class="btn border border-white/10 text-slate-300 hover:bg-white/5 text-center">Abrir no Telegram</a>
                <?php endif; ?>
            </div>
            <?php if ($conviteGeradoExpiraEm): ?>
                <div class="text-xs text-slate-400">Expira em: <span class="text-white font-semibold"><?= htmlspecialchars((string) $conviteGeradoExpiraEm) ?></span></div>
            <?php endif; ?>
            <div class="form-hint">Telefone não é obrigatório para gerar convite. O link pode ser encaminhado por qualquer canal.</div>
        </div>
    <?php endif; ?>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="card depth-1 p-5">
            <div class="flex items-center justify-between border-b border-white/5 pb-3">
                <div class="text-sm font-semibold text-white">Obreiros pendentes</div>
                <div class="text-xs text-slate-400 font-medium"><?= count($pendentes ?? []) ?> item(ns)</div>
            </div>
            <div class="mt-4 space-y-3">
                <?php if (empty($pendentes)): ?>
                    <div class="text-sm text-slate-400 py-2">Nenhum obreiro pendente no momento.</div>
                <?php else: ?>
                    <?php foreach ($pendentes as $p): ?>
                        <form method="post" action="/admin/convites/gerar" class="rounded-lg border border-white/5 bg-white/[0.02] p-3 flex flex-col gap-3 md:flex-row md:items-center md:justify-between hover:border-white/10 transition">
                            <input type="hidden" name="obreiro_id" value="<?= htmlspecialchars((string) ($p['id'] ?? '')) ?>">
                            <div>
                                <div class="text-sm font-medium text-white"><?= htmlspecialchars((string) (($p['nome_historico'] ?? $p['nome'] ?? '') ?: 'Obreiro')) ?></div>
                                <div class="text-xs text-slate-400 mt-0.5">CIM <?= htmlspecialchars((string) ($p['cim'] ?? '-')) ?></div>
                            </div>
                            <button type="submit" class="btn btn-success py-1.5 px-3 text-xs">Gerar convite</button>
                        </form>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="card depth-1 p-5">
            <div class="flex items-center justify-between border-b border-white/5 pb-3">
                <div class="text-sm font-semibold text-white">Convites recentes</div>
                <div class="text-xs text-slate-400 font-medium"><?= count($convites ?? []) ?> item(ns)</div>
            </div>
            <div class="mt-4 space-y-3">
                <?php if (empty($convites)): ?>
                    <div class="text-sm text-slate-400 py-2">Nenhum convite gerado.</div>
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
                        <div class="rounded-lg border border-white/5 bg-white/[0.01] p-4 space-y-3">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <div class="text-sm font-medium text-white"><?= htmlspecialchars((string) (($c['nome'] ?? '') ?: 'Obreiro')) ?></div>
                                    <div class="text-xs text-slate-400 mt-0.5">CIM <?= htmlspecialchars((string) ($c['cim'] ?? '-')) ?> · Token <?= htmlspecialchars($masked) ?></div>
                                </div>
                                <span class="badge-status <?= $status === 'ativo' ? 'badge-status-success' : ($status === 'usado' ? 'badge-status-secondary' : 'badge-status-warning') ?>">
                                    <?= htmlspecialchars($status) ?>
                                </span>
                            </div>
                            <div class="text-xs text-slate-400">
                                Expira: <span class="text-slate-300"><?= htmlspecialchars((string) ($c['expira_em'] ?? '-')) ?></span>
                                <?php if ($usado): ?> · Usado: <span class="text-slate-300"><?= htmlspecialchars((string) ($c['usado_em'] ?? '-')) ?></span><?php endif; ?>
                            </div>
                            <?php if ($deepLink !== ''): ?>
                                <div class="flex flex-col gap-2 md:flex-row md:items-center pt-1">
                                    <?php $inputId = 'convite-link-' . (string) ($c['id'] ?? uniqid('conv_', true)); ?>
                                    <input id="<?= htmlspecialchars($inputId) ?>" class="form-input w-full text-xs" readonly value="<?= htmlspecialchars((string) $deepLink) ?>">
                                    <div class="flex gap-2">
                                        <button type="button" class="copy-btn btn btn-primary py-1 px-3 text-xs" data-copy="<?= htmlspecialchars((string) $deepLink) ?>">Copiar</button>
                                        <button type="button" class="select-btn btn border border-white/10 text-slate-300 hover:bg-white/5 py-1 px-3 text-xs" data-target="<?= htmlspecialchars($inputId) ?>">Selecionar</button>
                                        <?php if ($deepUrl): ?>
                                            <a href="<?= htmlspecialchars((string) $deepUrl) ?>" target="_blank" rel="noopener" class="btn border border-white/10 text-slate-300 hover:bg-white/5 py-1 px-3 text-xs text-center">Telegram</a>
                                        <?php endif; ?>
                                    </div>
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
