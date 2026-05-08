<?php
declare(strict_types=1);

$appShellEyebrow = 'Área do Irmão';
$appShellTitle = 'Minha Loja';
$appShellDescription = 'Tudo o que é do Irmão, sem telas duplicadas.';
$appShellActiveHref = '/minha-loja';
$appShellActions = [['label' => 'Painel', 'href' => '/dashboard']];

require __DIR__ . '/partials/erp_shell_open.php';
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Ações do Irmão</h2>
                <p class="card-subtitle">Fluxos pessoais e fraternos, sem telas administrativas.</p>
            </div>
            <div class="card-body grid grid-cols-1 md:grid-cols-2 gap-3">
                <a class="list-item-action" href="/financeiro/minhas-obrigacoes"><span>Início</span><span>›</span></a>
                <a class="list-item-action" href="/meu-cadastro"><span>Meu Cadastro</span><span>›</span></a>
                <a class="list-item-action" href="/minha-loja/familiares"><span>Familiares</span><span>›</span></a>
                <a class="list-item-action" href="/minha-loja/trabalhos"><span>Meus Trabalhos</span><span>›</span></a>
                <a class="list-item-action" href="/minha-loja/irmaos"><span>Irmãos da Loja</span><span>›</span></a>
                <a class="list-item-action" href="/biblioteca"><span>Biblioteca</span><span>›</span></a>
                <a class="list-item-action" href="/minha-loja/solicitacoes"><span>Solicitações à Secretaria</span><span>›</span></a>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2 class="card-title">Como funciona</h2></div>
            <div class="card-body text-sm text-gray-600 dark:text-gray-300 space-y-2">
                <p>Você acessa a vida da Loja. Quando uma ação for de um cargo, o sistema orienta institucionalmente quem opera.</p>
                <p>Em caso de campo controlado, use <strong>Solicitações à Secretaria</strong>.</p>
            </div>
        </div>
    </div>

    <div class="space-y-6">
        <div class="card">
            <div class="card-header"><h2 class="card-title">Atalhos</h2></div>
            <div class="card-body space-y-2">
                <a class="list-item-action" href="/meu-cadastro"><span>Atualizar cadastro</span><span>›</span></a>
                <a class="list-item-action" href="/minha-loja/familiares"><span>Atualizar familiares</span><span>›</span></a>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/partials/erp_shell_close.php'; ?>
