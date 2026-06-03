<?php
declare(strict_types=1);

$mensagemSucesso = $_SESSION['mensagem_sucesso'] ?? null;
$mensagemErro = $_SESSION['mensagem_erro'] ?? null;
unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']);

// Variáveis vindas do Controller
$totalAbertos = (int) ($totalAbertos ?? 0);
$totalEmObservacao = (int) ($totalEmObservacao ?? 0);
$cuidadoAlto = (int) ($cuidadoAlto ?? 0);
$cuidadoMedio = (int) ($cuidadoMedio ?? 0);
$contagemAcompanhamentosMes = (int) ($contagemAcompanhamentosMes ?? 0);
$proximosAgendados = isset($proximosAgendados) && is_array($proximosAgendados) ? $proximosAgendados : [];

$appShellEyebrow = 'Vida da Loja';
$appShellTitle = 'Painel Vida da Loja';
$appShellDescription = 'Resumo dos sinais preventivos de cuidado e agendamentos de acompanhamento fraterno.';
$appShellActiveHref = '/vida-loja';

require __DIR__ . '/../partials/erp_shell_open.php';
?>

<!-- Mensagens de Feedback -->
<?php if ($mensagemSucesso): ?>
    <div class="alert alert-success mb-6"><?= htmlspecialchars($mensagemSucesso) ?></div>
<?php endif; ?>
<?php if ($mensagemErro): ?>
    <div class="alert alert-danger mb-6"><?= htmlspecialchars($mensagemErro) ?></div>
<?php endif; ?>

<!-- Métricas Rápidas -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="card-metric border border-red-500/20 bg-red-500/5 text-red-400">
        <p class="card-metric-label !text-red-400/80">Cuidado Prioritário</p>
        <p class="card-metric-value text-3xl font-bold"><?= $cuidadoAlto ?></p>
        <p class="card-metric-context text-[10px] text-slate-400 mt-1">Sinais preventivos de nível alto</p>
    </div>
    
    <div class="card-metric border border-orange-500/20 bg-orange-500/5 text-orange-400">
        <p class="card-metric-label !text-orange-400/80">Atenção Sugerida</p>
        <p class="card-metric-value text-3xl font-bold"><?= $cuidadoMedio ?></p>
        <p class="card-metric-context text-[10px] text-slate-400 mt-1">Sinais preventivos de nível médio</p>
    </div>

    <div class="card-metric">
        <p class="card-metric-label">Em Observação</p>
        <p class="card-metric-value text-3xl font-bold"><?= $totalEmObservacao ?></p>
        <p class="card-metric-context text-[10px] text-slate-400 mt-1">Irmãos que retornaram recentemente</p>
    </div>

    <div class="card-metric border border-emerald-500/20 bg-emerald-500/5 text-emerald-400">
        <p class="card-metric-label !text-emerald-400/80">Acompanhamentos (Mês)</p>
        <p class="card-metric-value text-3xl font-bold"><?= $contagemAcompanhamentosMes ?></p>
        <p class="card-metric-context text-[10px] text-slate-400 mt-1">Contatos fraternos registrados este mês</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Coluna Principal (2/3) -->
    <div class="lg:col-span-2 space-y-8">
        
        <!-- Próximos Contatos Agendados -->
        <div class="card depth-1">
            <div class="card-header border-b border-white/5 p-6">
                <h2 class="card-title text-white">Próximos Acompanhamentos Agendados</h2>
                <p class="card-subtitle mt-1">Planejamento de contatos e visitas da Hospitalaria para os próximos dias.</p>
            </div>
            <div class="card-body p-6">
                <?php if (empty($proximosAgendados)): ?>
                    <p class="text-center text-slate-400 py-10">Nenhum acompanhamento agendado para o futuro.</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="table-base w-full">
                            <thead>
                                <tr class="border-b border-white/10 text-left text-xs uppercase tracking-wider text-slate-400">
                                    <th class="pb-3 font-semibold">Irmão</th>
                                    <th class="pb-3 font-semibold">CIM</th>
                                    <th class="pb-3 font-semibold text-center">Data Agendada</th>
                                    <th class="pb-3 font-semibold text-center">Meio Sugerido</th>
                                    <th class="pb-3 font-semibold">Responsável</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5 text-sm">
                                <?php foreach ($proximosAgendados as $ac): ?>
                                    <tr class="hover:bg-white/[0.01] transition">
                                        <td class="py-3 text-white font-medium">
                                            <?= htmlspecialchars((string) ($ac['obreiro_nome'] ?? 'Sem obreiro')) ?>
                                        </td>
                                        <td class="py-3 text-slate-300">
                                            <?= htmlspecialchars((string) ($ac['obreiro_cim'] ?? '-')) ?>
                                        </td>
                                        <td class="py-3 text-center text-erp-gold font-semibold">
                                            <?= date('d/m/Y', strtotime((string)$ac['proximo_acompanhamento'])) ?>
                                        </td>
                                        <td class="py-3 text-center">
                                            <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold bg-slate-500/10 text-slate-300 border border-white/5 capitalize">
                                                <?= htmlspecialchars((string) ($ac['meio_contato'] ?? 'whatsapp')) ?>
                                            </span>
                                        </td>
                                        <td class="py-3 text-slate-400 text-xs">
                                            <?= htmlspecialchars((string) ($ac['responsavel_nome'] ?? '-')) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Princípios da Hospitalaria / Vida da Loja -->
        <div class="card depth-1">
            <div class="card-header border-b border-white/5 p-6">
                <h2 class="card-title text-white">Manual Sintrópico do Cuidado</h2>
                <p class="card-subtitle mt-1">Como utilizar os sinais preventivos de apoio fraterno.</p>
            </div>
            <div class="card-body p-6 space-y-4 text-sm text-slate-300 leading-relaxed">
                <p>
                    A base da estabilidade de uma Oficina repousa sobre a coesão de suas colunas. Afastamentos silenciosos raramente ocorrem por desinteresse voluntário; quase sempre decorrem de cansaço intelectual, problemas de saúde pessoal ou familiar, ou pressões econômicas discretas.
                </p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
                    <div class="bg-white/[0.02] border border-white/5 rounded-xl p-4">
                        <p class="font-bold text-erp-gold text-xs uppercase tracking-wider mb-2">Ação Sintrópica</p>
                        <p class="text-xs text-slate-400">
                            A hospitalaria atua preventivamente. Ao identificar um sinal de cuidado, o Hospitaleiro ou o Venerável realizam contato sutil para saber do bem-estar do irmão, agindo antes que se inicie o processo de evasão.
                        </p>
                    </div>
                    <div class="bg-white/[0.02] border border-white/5 rounded-xl p-4">
                        <p class="font-bold text-erp-gold text-xs uppercase tracking-wider mb-2">Sigilo Absoluto</p>
                        <p class="text-xs text-slate-400">
                            Registros detalhados de caráter pessoal ou familiar são protegidos e ocultados de telas públicas. Apenas o Venerável e o Hospitaleiro possuem acesso aos logs mais profundos.
                        </p>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Coluna Lateral (1/3) -->
    <div class="space-y-6">
        
        <!-- Ações Rápidas -->
        <div class="card depth-1 p-6">
            <div class="card-header border-b border-white/5 pb-3 mb-4">
                <h2 class="card-title text-white">Ações de Gestão</h2>
            </div>
            <div class="card-body space-y-3">
                <a href="/vida-loja/sinais" class="btn btn-primary w-full text-center flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                    Gerenciar Sinais e Contatos
                </a>
            </div>
        </div>

        <!-- Estatísticas Rápidas -->
        <div class="card depth-1 p-6">
            <div class="card-header border-b border-white/5 pb-3 mb-4">
                <h2 class="card-title text-white">Resumo Operacional</h2>
            </div>
            <div class="card-body space-y-4 text-xs text-slate-300">
                <div class="flex justify-between items-center pb-2 border-b border-white/5">
                    <span class="text-slate-400">Sinais em aberto:</span>
                    <span class="font-bold text-white"><?= $totalAbertos ?></span>
                </div>
                <div class="flex justify-between items-center pb-2 border-b border-white/5">
                    <span class="text-slate-400">Sinais em observação:</span>
                    <span class="font-bold text-white"><?= $totalEmObservacao ?></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-400">Agendamentos futuros:</span>
                    <span class="font-bold text-white"><?= count($proximosAgendados) ?></span>
                </div>
            </div>
        </div>

    </div>
</div>

<?php
require_once __DIR__ . '/../partials/erp_shell_close.php';
?>
