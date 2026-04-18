<?php
$mensagemSucesso = $_SESSION['mensagem_sucesso'] ?? null;
$mensagemErro = $_SESSION['mensagem_erro'] ?? null;
unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']);

$erpPageTitle = 'Votacao de Balaustre - Gestor de Loja';
$appShellEyebrow = 'Secretaria';
$appShellTitle = 'Votacao de Balaustre';
$appShellDescription = 'Painel de votação aberto para os presentes aptos na sessão.';
$appShellActiveHref = '/secretaria/votacao';
$appShellActions = [
    ['label' => 'Voltar para Secretaria', 'href' => '/secretaria'],
];
$appShellSidebarSections = [
    [
        'title' => 'Secretaria',
        'items' => [
            ['label' => 'Painel da Secretaria', 'href' => '/secretaria'],
            ['label' => 'Votacao de balaustre', 'href' => '/secretaria/votacao'],
            ['label' => 'Relatorio anual', 'href' => '/secretaria/relatorio-anual'],
            ['label' => 'Central de Obreiros', 'href' => '/obreiros'],
        ],
    ],
    [
        'title' => 'Navegacao',
        'items' => [
            ['label' => 'Dashboard', 'href' => '/dashboard'],
            ['label' => 'Nominata oficial', 'href' => '/admin/cargos'],
        ],
    ],
];
require __DIR__ . '/../partials/erp_head.php';
?>
<?php require __DIR__ . '/../partials/erp_shell_open.php'; ?>

        <?php if ($mensagemSucesso): ?>
            <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-700"><?= htmlspecialchars($mensagemSucesso) ?></div>
        <?php endif; ?>
        <?php if ($mensagemErro): ?>
            <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-rose-700"><?= htmlspecialchars($mensagemErro) ?></div>
        <?php endif; ?>

        <?php if ($votacoesAbertas === []): ?>
            <div class="rounded-xl border border-slate-200 bg-white p-6 text-sm text-slate-700">
                No momento, nao ha votacoes abertas para o seu perfil.
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($votacoesAbertas as $balaustre): ?>
                    <?php
                    $balaustreId = (int) ($balaustre['id'] ?? 0);
                    $elegivel = (bool) ($elegibilidadeVoto[$balaustreId] ?? false);
                    ?>
                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                            <div>
                                <div class="text-lg font-semibold text-erp-navy">
                                    <?= htmlspecialchars($balaustre['numero_balaustre'] ?: 'Balaustre sem numero') ?>
                                </div>
                                <div class="text-sm text-slate-700">
                                    <?= htmlspecialchars($balaustre['sessao_titulo'] ?: (($balaustre['tipo_sessao'] ?? 'Sessao') . ' - ' . ($balaustre['grau_sessao'] ?? ''))) ?>
                                </div>
                                <div class="text-xs text-slate-700 mt-1">
                                    Data da sessao: <?= htmlspecialchars((string) ($balaustre['data_hora_inicio'] ?? '')) ?>
                                </div>
                            </div>
                            <div class="text-sm">
                                <?php if ($elegivel): ?>
                                    <span class="inline-flex rounded-full bg-emerald-100 text-emerald-700 px-3 py-1">Apto a votar</span>
                                <?php else: ?>
                                    <span class="inline-flex rounded-full bg-amber-100 text-amber-800 px-3 py-1">Somente para acompanhamento</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($elegivel): ?>
                            <form method="POST" action="/secretaria/balaustres/votar" class="mt-4 flex flex-col md:flex-row gap-2 md:items-center">
                                <input type="hidden" name="balaustre_id" value="<?= $balaustreId ?>">
                                <input type="hidden" name="return_to" value="/secretaria/votacao">
                                <select name="voto" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                    <option value="aprovar">aprovar</option>
                                    <option value="pedir_correcao">pedir correcao</option>
                                    <option value="rejeitar">rejeitar</option>
                                </select>
                                <input type="text" name="justificativa" placeholder="Justificativa (opcional)" class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                <button type="submit" class="rounded-lg bg-erp-navy px-4 py-2 text-sm text-white">Registrar voto</button>
                            </form>
                        <?php else: ?>
                            <p class="mt-4 text-xs text-slate-700">Seu nome nao consta na base congelada de votantes desta sessao.</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
<?php require __DIR__ . '/../partials/erp_shell_close.php'; ?>
