<?php
declare(strict_types=1);

$appShellEyebrow = 'Área do Irmão';
$appShellTitle = 'Minha Loja';
$appShellDescription = 'Tudo o que é do Irmão, sem telas duplicadas.';
$appShellActiveHref = '/minha-loja';
$appShellActions = [['label' => 'Painel', 'href' => '/dashboard']];

require __DIR__ . '/partials/erp_shell_open.php';
?>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <!-- Início -->
    <div class="card flex flex-col h-full">
        <div class="card-body flex-1 flex flex-col text-center p-6 items-center">
            <div class="w-12 h-12 rounded-full bg-erp-surface-2 flex items-center justify-center mb-4 text-erp-gold">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
            <h3 class="font-cinzel text-lg font-bold text-white mb-2">Financeiro</h3>
            <p class="text-xs text-erp-muted mb-6 flex-1">Consulte suas obrigações financeiras e mensalidades.</p>
            <a href="/financeiro/minhas-obrigacoes" class="btn btn-primary w-full">Acessar</a>
        </div>
    </div>

    <!-- Meu Cadastro -->
    <div class="card flex flex-col h-full">
        <div class="card-body flex-1 flex flex-col text-center p-6 items-center">
            <div class="w-12 h-12 rounded-full bg-erp-surface-2 flex items-center justify-center mb-4 text-erp-gold">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
            </div>
            <h3 class="font-cinzel text-lg font-bold text-white mb-2">Meu Cadastro</h3>
            <p class="text-xs text-erp-muted mb-6 flex-1">Mantenha seus dados de contato e maçônicos atualizados.</p>
            <a href="/meu-cadastro" class="btn btn-primary w-full">Atualizar</a>
        </div>
    </div>

    <!-- Familiares -->
    <div class="card flex flex-col h-full">
        <div class="card-body flex-1 flex flex-col text-center p-6 items-center">
            <div class="w-12 h-12 rounded-full bg-erp-surface-2 flex items-center justify-center mb-4 text-erp-gold">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
            </div>
            <h3 class="font-cinzel text-lg font-bold text-white mb-2">Familiares</h3>
            <p class="text-xs text-erp-muted mb-6 flex-1">Cadastre cunhadas, sobrinhos e informações do núcleo familiar.</p>
            <a href="/minha-loja/familiares" class="btn btn-primary w-full">Gerenciar</a>
        </div>
    </div>

    <!-- Meus Trabalhos -->
    <div class="card flex flex-col h-full">
        <div class="card-body flex-1 flex flex-col text-center p-6 items-center">
            <div class="w-12 h-12 rounded-full bg-erp-surface-2 flex items-center justify-center mb-4 text-erp-gold">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9.5a2.5 2.5 0 00-2.5-2.5H15M9 11l3 3L22 4" /></svg>
            </div>
            <h3 class="font-cinzel text-lg font-bold text-white mb-2">Meus Trabalhos</h3>
            <p class="text-xs text-erp-muted mb-6 flex-1">Consulte as peças de arquitetura e trabalhos entregues por você.</p>
            <a href="/minha-loja/trabalhos" class="btn btn-primary w-full">Consultar</a>
        </div>
    </div>

    <!-- Irmãos da Loja -->
    <div class="card flex flex-col h-full">
        <div class="card-body flex-1 flex flex-col text-center p-6 items-center">
            <div class="w-12 h-12 rounded-full bg-erp-surface-2 flex items-center justify-center mb-4 text-erp-gold">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
            </div>
            <h3 class="font-cinzel text-lg font-bold text-white mb-2">Irmãos da Loja</h3>
            <p class="text-xs text-erp-muted mb-6 flex-1">Lista de contatos e informações públicas dos Irmãos do Quadro.</p>
            <a href="/minha-loja/irmaos" class="btn btn-primary w-full">Ver Quadro</a>
        </div>
    </div>

    <!-- Biblioteca -->
    <div class="card flex flex-col h-full">
        <div class="card-body flex-1 flex flex-col text-center p-6 items-center">
            <div class="w-12 h-12 rounded-full bg-erp-surface-2 flex items-center justify-center mb-4 text-erp-gold">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
            </div>
            <h3 class="font-cinzel text-lg font-bold text-white mb-2">Biblioteca</h3>
            <p class="text-xs text-erp-muted mb-6 flex-1">Acervo de peças de arquitetura, rituais e documentos de estudo.</p>
            <a href="/biblioteca" class="btn btn-primary w-full">Acessar Acervo</a>
        </div>
    </div>

    <!-- Solicitações à Secretaria -->
    <div class="card flex flex-col h-full md:col-span-2 lg:col-span-3">
        <div class="card-body flex-1 flex flex-col sm:flex-row items-center p-6 gap-6">
            <div class="w-16 h-16 rounded-full bg-erp-surface-2 flex items-center justify-center text-erp-gold shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" /></svg>
            </div>
            <div class="flex-1 text-center sm:text-left">
                <h3 class="font-cinzel text-lg font-bold text-white mb-1">Solicitações à Secretaria</h3>
                <p class="text-xs text-erp-muted">Você não pode alterar alguns dados diretamente. Para ajustar um cargo, grau ou documento, envie uma solicitação para a Secretaria da Loja.</p>
            </div>
            <a href="/minha-loja/solicitacoes" class="btn btn-secondary w-full sm:w-auto px-8">Nova Solicitação</a>
        </div>
    </div>
</div>

<?php require __DIR__ . '/partials/erp_shell_close.php'; ?>
