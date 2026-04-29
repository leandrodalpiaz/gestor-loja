<?php

$mensagem = $_SESSION['mensagem_sucesso'] ?? null;
$mensagemErro = $_SESSION['mensagem_erro'] ?? null;
unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']);

$erpPageTitle = 'Aprovacao de acesso';
$appShellEyebrow = 'Secretaria';
$appShellTitle = 'Acessos';
$appShellDescription = 'Aprovação e bloqueio de acessos ao sistema (pendente/ativo/inativo).';
$appShellActiveHref = '/admin/acessos';
$appShellActions = [
    ['label' => 'Voltar ao painel', 'href' => '/dashboard', 'primary' => true],
];
$appShellSidebarSections = [
    [
        'title' => 'Secretaria',
        'items' => [
            ['label' => 'Nominata oficial', 'href' => '/admin/cargos'],
            ['label' => 'Central de Obreiros', 'href' => '/obreiros'],
            ['label' => 'Convites de acesso', 'href' => '/admin/convites'],
            ['label' => 'Aprovacao de acessos', 'href' => '/admin/acessos'],
            ['label' => 'Sessões', 'href' => '/secretaria'],
            ['label' => 'Balaustres / votação', 'href' => '/secretaria/votacao'],
            ['label' => 'Painel', 'href' => '/dashboard'],
        ],
    ],
];

require __DIR__ . '/../partials/erp_head.php';
require __DIR__ . '/../partials/erp_shell_open.php';
?>

<section class="rounded-2xl border border-erpBorder bg-white">
    <div class="border-b border-erpBorder px-6 py-5">
                <div class="text-sm font-semibold text-erpNavy">Em análises de aprovação</div>
        <div class="mt-1 text-sm text-erpMuted">Somente secretario/admin devem concluir esta etapa.</div>
    </div>

    <?php if ($mensagem): ?>
        <div class="mx-6 mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-4 text-sm text-emerald-800">
            <?= htmlspecialchars($mensagem) ?>
        </div>
    <?php endif; ?>
    <?php if ($mensagemErro): ?>
        <div class="mx-6 mt-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-4 text-sm text-rose-800">
            <?= htmlspecialchars($mensagemErro) ?>
        </div>
    <?php endif; ?>

    <?php if (empty($itens)): ?>
        <div class="px-6 py-8 text-sm text-erpMuted">Nenhuma solicitacao pendente no momento.</div>
    <?php else: ?>
        <div class="divide-y divide-erpBorder">
            <?php foreach ($itens as $item): ?>
                <div class="px-6 py-5">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <div class="text-sm font-semibold text-erpText"><?= htmlspecialchars((string) ($item['nome_historico'] ?? $item['nome'] ?? 'Sem nome')) ?></div>
                            <div class="mt-1 text-sm text-erpMuted">CIM: <?= htmlspecialchars((string) ($item['cim'] ?? '-')) ?></div>
                            <div class="mt-1 text-xs text-erpMuted">Telegram: <?= htmlspecialchars((string) ($item['telegram_id'] ?? 'não informado')) ?></div>
                        </div>
                        <div class="flex flex-col gap-2 sm:flex-row">
                            <form method="POST" action="/admin/acessos/atualizar">
                                <input type="hidden" name="id" value="<?= htmlspecialchars((string) ($item['id'] ?? '')) ?>">
                                <input type="hidden" name="status" value="ativo">
                                <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-erpNavyDeep px-5 py-2.5 text-sm font-semibold text-white hover:opacity-95">
                                    Aprovar
                                </button>
                            </form>
                            <form method="POST" action="/admin/acessos/atualizar">
                                <input type="hidden" name="id" value="<?= htmlspecialchars((string) ($item['id'] ?? '')) ?>">
                                <input type="hidden" name="status" value="inativo">
                                <button type="submit" class="inline-flex items-center justify-center rounded-xl border border-rose-200 bg-rose-50 px-5 py-2.5 text-sm font-semibold text-rose-700 hover:bg-rose-100">
                                    Inativar
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../partials/erp_shell_close.php'; ?>

