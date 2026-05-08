<?php
declare(strict_types=1);

// #############################################################################
// LÓGICA DE NEGÓCIO E HELPERS
// #############################################################################

$mensagemSucesso = $_SESSION['mensagem_sucesso'] ?? null;
$mensagemErro = $_SESSION['mensagem_erro'] ?? null;
unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']);

$sessaoEmFormulario = is_array($sessaoRascunho ?? null) ? $sessaoRascunho : (is_array($sessaoEdicao ?? null) ? $sessaoEdicao : []);
$modoEdicaoSessao = !is_array($sessaoRascunho ?? null) && is_array($sessaoEdicao ?? null);

$parseDateTime = static function (?string $val): ?DateTimeImmutable {
    $val = trim((string) $val);
    if ($val === '') {
        return null;
    }

    try {
        return new DateTimeImmutable($val);
    } catch (Throwable) {
        return null;
    }
};
$formatDate = static function (?string $val) use ($parseDateTime): string {
    $dt = $parseDateTime($val);
    return $dt ? $dt->format('d/m/Y') : '-';
};
$formatDateTime = static function (?string $val) use ($parseDateTime): string {
    $dt = $parseDateTime($val);
    return $dt ? $dt->format('d/m/Y \a\s H:i') : 'Data a definir';
};
$formatInputDateTime = static function (?string $val) use ($parseDateTime): string {
    $dt = $parseDateTime($val);
    return $dt ? $dt->format('Y-m-d\TH:i') : '';
};

$badgeStatusSessao = static function (?string $status): string {
    return match (strtolower(trim((string) $status))) {
        'publicada', 'confirmada', 'ativa', 'alterada' => 'badge-success',
        'planejada' => 'badge-warning',
        'cancelada' => 'badge-danger',
        default => 'badge-secondary',
    };
};

$rascunhoEditaSessaoExistente = is_array($sessaoRascunho ?? null) && !empty($sessaoRascunho['id']);
$etapasSessao = [
    ['numero' => '1', 'titulo' => 'Preencher', 'texto' => 'Dados oficiais da agenda', 'ativo' => !$resumoRascunhoSessao],
    ['numero' => '2', 'titulo' => 'Revisar', 'texto' => 'Resumo final antes de publicar', 'ativo' => (bool) $resumoRascunhoSessao],
    ['numero' => '3', 'titulo' => 'Gerenciar', 'texto' => 'Editar, publicar, cancelar e acompanhar', 'ativo' => false],
];
$linhasVisitantesBalaustre = array_pad($visitantesBalaustre ?? [], max(3, count($visitantesBalaustre ?? []) + 1), []);
$linhasVisitasExternasBalaustre = array_pad($visitasExternasBalaustre ?? [], max(2, count($visitasExternasBalaustre ?? []) + 1), []);
$linhasObreirosPalavraBalaustre = array_pad($palavrasObreirosBalaustre ?? [], max(3, count($palavrasObreirosBalaustre ?? []) + 1), []);
$blocosBalaustre = is_array($blocosBalaustre ?? null) ? $blocosBalaustre : [];

// #############################################################################
// CONFIGURAÇÃO DO APP SHELL
// #############################################################################

$appShellEyebrow = 'Secretaria';
$appShellTitle = 'Painel da Secretaria';
$appShellDescription = 'Gestão de obreiros, sessões, balaústres, acessos e convites da Loja.';
$appShellActiveHref = '/secretaria';
$appShellActions = [
    ['label' => 'Central de Obreiros', 'href' => '/obreiros'],
    ['label' => 'Novo Obreiro', 'href' => '/obreiros/novo'],];
require __DIR__ . '/_sidebar.php';

require __DIR__ . '/../partials/erp_shell_open.php';

?>

<!-- Mensagens de Feedback -->
<?php if ($mensagemSucesso): ?><div class="alert alert-success mb-8 depth-1"><?= htmlspecialchars($mensagemSucesso) ?></div><?php endif; ?>
<?php if ($mensagemErro): ?><div class="alert alert-danger mb-8 depth-1"><?= htmlspecialchars($mensagemErro) ?></div><?php endif; ?>

<!-- Métricas Rápidas -->
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6 mb-12">
    <div class="card-metric depth-1 hover-lift border-t-4 border-t-erp-navy">
        <p class="card-metric-label uppercase tracking-widest font-bold opacity-60">Obreiros ativos</p>
        <p class="card-metric-value text-erp-navy"><?= (int) ($resumo['obreiros_ativos'] ?? 0) ?></p>
    </div>
    <div class="card-metric depth-1 hover-lift border-t-4 border-t-erp-gold">
        <p class="card-metric-label uppercase tracking-widest font-bold opacity-60">Sessões futuras</p>
        <p class="card-metric-value text-erp-navy"><?= (int) ($resumo['sessoes_futuras'] ?? 0) ?></p>
    </div>
    <div class="card-metric depth-1 hover-lift border-t-4 border-t-erp-brand-vibrant">
        <p class="card-metric-label uppercase tracking-widest font-bold opacity-60">Trabalhos pendentes</p>
        <p class="card-metric-value text-erp-navy"><?= (int) ($resumo['trabalhos_pendentes'] ?? 0) ?></p>
    </div>
    <div class="card-metric depth-1 hover-lift border-t-4 border-t-erp-muted">
        <p class="card-metric-label uppercase tracking-widest font-bold opacity-60">Rascunhos</p>
        <p class="card-metric-value text-erp-navy"><?= (int) ($resumo['publicacoes_rascunho'] ?? 0) ?></p>
    </div>
    <div class="card-metric depth-1 hover-lift border-t-4 border-t-erp-success">
        <p class="card-metric-label uppercase tracking-widest font-bold opacity-60">Balaústres aptos</p>
        <p class="card-metric-value text-erp-navy"><?= (int) ($resumo['balaustres_aptos'] ?? 0) ?></p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-12">
    <!-- Coluna Principal -->
    <div class="lg:col-span-2 space-y-10">
        <!-- Saúde Cadastral -->
        <div class="card depth-1">
            <div class="card-header flex items-center justify-between border-b border-erp-border/50 p-6">
                <div>
                    <h2 class="text-xl font-black text-erp-navy tracking-tight">Saúde Cadastral da Secretaria</h2>
                    <p class="text-sm text-erp-muted mt-1 font-medium">Saneamento do quadro e preparo de relatórios.</p>
                </div>
                <a href="/obreiros" class="btn btn-secondary shadow-sm">Central de Obreiros</a>
            </div>
            <div class="card-body p-6">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-erp-surface-2 rounded-2xl p-4 border border-erp-border/50">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-erp-muted mb-1">Total</p>
                        <p class="text-2xl font-black text-erp-navy"><?= (int) ($resumoCadastros['total'] ?? 0) ?></p>
                    </div>
                    <div class="bg-erp-surface-2 rounded-2xl p-4 border border-erp-border/50">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-erp-muted mb-1">No Quadro</p>
                        <p class="text-2xl font-black text-erp-navy"><?= (int) ($resumoCadastros['ativos'] ?? 0) ?></p>
                    </div>
                    <div class="bg-warning/5 rounded-2xl p-4 border border-warning/20">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-warning mb-1">Com Alerta</p>
                        <p class="text-2xl font-black text-warning"><?= (int) ($resumoCadastros['com_alerta'] ?? 0) ?></p>
                    </div>
                    <div class="bg-erp-surface-2 rounded-2xl p-4 border border-erp-border/50">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-erp-muted mb-1">Com Bot</p>
                        <p class="text-2xl font-black text-erp-navy"><?= (int) ($resumoCadastros['com_telegram'] ?? 0) ?></p>
                    </div>
                </div>
                <div class="mt-8 flex flex-wrap gap-4">
                    <a href="/obreiros?alerta=cadastro" class="btn btn-warning hover-lift">Ver Alertas Cadastrais</a>
                    <a href="/obreiros/novo" class="btn btn-primary hover-lift">Adicionar Obreiro</a>
                </div>
            </div>
        </div>

        <!-- Identidade e História da Loja -->
        <div class="card depth-1 overflow-hidden">
            <div class="card-header flex items-center justify-between bg-erp-navy p-6">
                <div>
                    <h2 class="text-xl font-black text-white tracking-tight">
                        <?= htmlspecialchars(trim((string) (($configuracaoLoja['nome_loja'] ?? '') . ' nº ' . ($configuracaoLoja['numero_loja'] ?? '')), " nº")) ?>
                    </h2>
                    <p class="text-xs text-white/60 mt-1 font-bold uppercase tracking-widest">Base institucional e histórica da oficina</p>
                </div>
                <?php if (!empty($configuracaoLoja['potencia_sigla']) || !empty($configuracaoLoja['potencia_nome'])): ?>
                    <span class="badge bg-erp-gold text-erp-navy px-4 py-2 text-[10px] font-black uppercase tracking-widest shadow-lg">
                        <?= htmlspecialchars((string) (($configuracaoLoja['potencia_sigla'] ?? '') !== '' ? $configuracaoLoja['potencia_sigla'] : ($configuracaoLoja['potencia_nome'] ?? ''))) ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="card-body p-8">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
                    <div class="flex flex-col gap-1">
                        <span class="text-[10px] font-bold text-erp-muted uppercase tracking-widest">Oriente</span>
                        <strong class="text-erp-navy font-black"><?= htmlspecialchars((string) ($configuracaoLoja['oriente'] ?? '-')) ?></strong>
                    </div>
                    <div class="flex flex-col gap-1">
                        <span class="text-[10px] font-bold text-erp-muted uppercase tracking-widest">Rito</span>
                        <strong class="text-erp-navy font-black"><?= htmlspecialchars((string) ($configuracaoLoja['rito'] ?? '-')) ?></strong>
                    </div>
                    <div class="flex flex-col gap-1">
                        <span class="text-[10px] font-bold text-erp-muted uppercase tracking-widest">Fundação</span>
                        <strong class="text-erp-navy font-black"><?= $formatDate($configuracaoLoja['data_fundacao'] ?? null) ?></strong>
                    </div>
                    <div class="flex flex-col gap-1">
                        <span class="text-[10px] font-bold text-erp-muted uppercase tracking-widest">Instalação</span>
                        <strong class="text-erp-navy font-black"><?= $formatDate($configuracaoLoja['data_instalacao'] ?? null) ?></strong>
                    </div>
                    <div class="flex flex-col gap-1">
                        <span class="text-[10px] font-bold text-erp-muted uppercase tracking-widest">Templo</span>
                        <strong class="text-erp-navy font-black"><?= htmlspecialchars((string) ($configuracaoLoja['nome_templo'] ?? '-')) ?></strong>
                    </div>
                    <div class="flex flex-col gap-1">
                        <span class="text-[10px] font-bold text-erp-muted uppercase tracking-widest">Reuniões</span>
                        <strong class="text-erp-navy font-black"><?= htmlspecialchars(trim((string) (($configuracaoLoja['dia_semana_reuniao'] ?? '') . ' • ' . ($configuracaoLoja['horario_reuniao'] ?? '') . ' • ' . ($configuracaoLoja['periodicidade_reuniao'] ?? '')), ' •')) ?></strong>
                    </div>
                </div>
                <div class="prose dark:prose-invert max-w-none pt-8 border-t border-erp-border/30">
                    <h3 class="text-sm font-black text-erp-navy uppercase tracking-widest mb-4">Breve História da Loja</h3>
                    <p class="text-sm leading-relaxed text-erp-text/80 whitespace-pre-line font-medium italic"><?= htmlspecialchars(mb_strimwidth(trim((string) ($configuracaoLoja['historia_loja'] ?? '')), 0, 2200, '...')) ?></p>
                </div>
            </div>    <!-- Coluna Lateral -->
    <div class="lg:col-start-3 lg:row-start-1 lg:row-span-2 space-y-10">
        <div class="card depth-1">
            <div class="card-header border-b border-erp-border/50 p-6">
                <h2 class="text-lg font-black text-erp-navy tracking-tight">Sessão em Foco</h2>
                <p class="text-xs text-erp-muted mt-1 font-bold uppercase tracking-widest opacity-60">Resumo operacional selecionado</p>
            </div>
            <div class="card-body p-6">
                <form method="GET" action="/secretaria" class="mb-8">
                    <label for="sessao_resumo" class="block text-[10px] font-bold text-erp-muted uppercase tracking-widest mb-3 ml-1">Sessão para acompanhamento</label>
                    <div class="flex gap-2">
                        <select name="sessao_resumo" id="sessao_resumo" class="form-select flex-grow !bg-erp-surface-2 border-erp-border/50 text-sm">
                            <?php foreach ($sessoes as $sessaoOpcao): ?>
                                <option value="<?= (int) ($sessaoOpcao['id'] ?? 0) ?>" <?= (int) ($sessaoResumo['id'] ?? 0) === (int) ($sessaoOpcao['id'] ?? 0) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string) ($sessaoOpcao['titulo'] ?: (($sessaoOpcao['tipo_sessao'] ?? 'Sessão') . ' - ' . ($sessaoOpcao['grau_sessao'] ?? '')))) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-primary px-4 shadow-sm">Ver</button>
                    </div>
                </form>

                <?php if (!empty($sessaoResumo)): ?>
                    <div class="bg-erp-navy/[0.03] rounded-2xl p-6 border border-erp-navy/10">
                        <p class="font-black text-erp-navy leading-tight"><?= htmlspecialchars((string) ($sessaoResumo['titulo'] ?: (($sessaoResumo['tipo_sessao'] ?? 'Sessão') . ' - ' . ($sessaoResumo['grau_sessao'] ?? '')))) ?></p>
                        <div class="flex items-center gap-2 mt-3">
                            <span class="text-xs font-bold text-erp-muted uppercase tracking-widest"><?= $formatDateTime((string) ($sessaoResumo['data_hora_inicio'] ?? '')) ?></span>
                            <span class="w-1.5 h-1.5 rounded-full bg-erp-border/50"></span>
                            <span class="badge badge-success text-[9px] font-black uppercase tracking-widest"><?= htmlspecialchars((string) ($sessaoResumo['status'] ?? '')) ?></span>
                        </div>
                    </div>
                    
                    <div class="mt-8 grid grid-cols-3 gap-3">
                        <div class="bg-white rounded-xl p-3 border border-erp-border/40 shadow-sm text-center">
                            <p class="text-[9px] font-bold text-erp-muted uppercase tracking-widest mb-1">Conf.</p>
                            <p class="text-lg font-black text-erp-navy"><?= (int) ($sessaoResumo['total_confirmados'] ?? 0) ?></p>
                        </div>
                        <div class="bg-white rounded-xl p-3 border border-erp-border/40 shadow-sm text-center">
                            <p class="text-[9px] font-bold text-erp-muted uppercase tracking-widest mb-1">Ausentes</p>
                            <p class="text-lg font-black text-erp-navy"><?= (int) ($sessaoResumo['total_ausentes'] ?? 0) ?></p>
                        </div>
                        <div class="bg-white rounded-xl p-3 border border-erp-border/40 shadow-sm text-center">
                            <p class="text-[9px] font-bold text-erp-muted uppercase tracking-widest mb-1">Ágape</p>
                            <p class="text-lg font-black text-erp-navy"><?= (int) ($sessaoResumo['total_agape'] ?? 0) ?></p>
                        </div>
                    </div>

                    <div class="mt-10 space-y-8">
                        <div>
                            <div class="flex items-center gap-3 mb-4">
                                <div class="h-4 w-1 bg-erp-success rounded-full"></div>
                                <h3 class="text-[11px] font-black text-erp-navy uppercase tracking-widest">Confirmados</h3>
                            </div>
                            <ul class="space-y-2 max-h-60 overflow-y-auto pr-2 custom-scrollbar">
                                <?php foreach ($confirmadosSessaoResumo as $confirmado): ?>
                                    <li class="flex items-center justify-between p-3 bg-erp-surface-2 rounded-xl border border-erp-border/30">
                                        <span class="text-sm font-bold text-erp-navy"><?= htmlspecialchars((string) ($confirmado['nome'] ?? 'Irmão')) ?></span>
                                        <span class="text-[10px] font-mono text-erp-muted bg-white px-2 py-0.5 rounded border border-erp-border/50"><?= htmlspecialchars((string) ($confirmado['cim'] ?? '-')) ?></span>
                                    </li>
                                <?php endforeach; ?>
                                <?php if (empty($confirmadosSessaoResumo)): ?>
                                    <li class="text-center text-xs text-erp-muted py-6 font-medium italic opacity-60">Nenhuma confirmação registrada.</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        <div>
                            <div class="flex items-center gap-3 mb-4">
                                <div class="h-4 w-1 bg-erp-gold rounded-full"></div>
                                <h3 class="text-[11px] font-black text-erp-navy uppercase tracking-widest">No Ágape</h3>
                            </div>
                            <ul class="space-y-2 max-h-60 overflow-y-auto pr-2 custom-scrollbar">
                                <?php foreach ($participantesAgapeResumo as $participanteAgape): ?>
                                    <li class="flex items-center justify-between p-3 bg-erp-surface-2 rounded-xl border border-erp-border/30">
                                        <span class="text-sm font-bold text-erp-navy"><?= htmlspecialchars((string) ($participanteAgape['nome'] ?? 'Irmão')) ?></span>
                                        <span class="text-[10px] font-mono text-erp-muted bg-white px-2 py-0.5 rounded border border-erp-border/50"><?= htmlspecialchars((string) ($participanteAgape['cim'] ?? '-')) ?></span>
                                    </li>
                                <?php endforeach; ?>
                                <?php if (empty($participantesAgapeResumo)): ?>
                                    <li class="text-center text-xs text-erp-muted py-6 font-medium italic opacity-60">Sem participantes no ágape.</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="mt-10 text-center py-12 glass-surface rounded-3xl border-dashed">
                        <div class="text-3xl mb-4 opacity-20">📅</div>
                        <p class="text-xs text-erp-muted font-bold uppercase tracking-widest opacity-60">Selecione uma sessão para ver o resumo</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Gerenciamento de Sessões -->
    <div class="card depth-1 mt-10">
        <div class="card-header border-b border-erp-border/50 p-6 flex items-center justify-between">
            <div>
                <h2 class="text-xl font-black text-erp-navy tracking-tight">Gerenciamento de Sessões</h2>
                <p class="text-sm text-erp-muted mt-1 font-medium">Fluxo operacional: preencher, revisar, publicar e acompanhar.</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-erp-brand-vibrant animate-pulse"></span>
                <span class="text-[10px] font-black text-erp-navy uppercase tracking-widest">Sistema Ativo</span>
            </div>
        </div>
        <div class="card-body p-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
                <?php foreach ($etapasSessao as $etapaSessao): ?>
                    <div class="relative p-5 rounded-2xl border transition-all <?= !empty($etapaSessao['ativo']) ? 'bg-erp-navy text-white border-erp-navy shadow-lg' : 'bg-erp-surface-2 border-erp-border/50 opacity-60' ?>">
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full text-xs font-black mb-4 <?= !empty($etapaSessao['ativo']) ? 'bg-erp-gold text-erp-navy shadow-inner shadow-black/20' : 'bg-erp-border/50 text-erp-muted' ?>">
                            <?= htmlspecialchars($etapaSessao['numero']) ?>
                        </span>
                        <h4 class="text-sm font-black uppercase tracking-widest mb-1"><?= htmlspecialchars($etapaSessao['titulo']) ?></h4>
                        <p class="text-[11px] leading-tight font-medium opacity-80"><?= htmlspecialchars($etapaSessao['texto']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if ($resumoRascunhoSessao): ?>
                <div class="bg-warning/5 rounded-[32px] border border-warning/20 p-8 mb-12 animate-in fade-in slide-in-from-top-4">
                    <div class="flex flex-wrap items-start justify-between gap-6 mb-8">
                        <div>
                            <h3 class="text-lg font-black text-warning tracking-tight"><?= $rascunhoEditaSessaoExistente ? 'Revisão final da atualização' : 'Revisão final da nova Sessão' ?></h3>
                            <p class="text-sm text-warning/80 mt-1 font-medium"><?= $rascunhoEditaSessaoExistente ? 'Confira o resumo antes de atualizar a Sessão já existente.' : 'Confira o texto final antes de publicar a nova Sessão.' ?></p>
                        </div>
                        <?php if ($sessaoDuplicada): ?>
                            <span class="badge bg-danger text-white px-4 py-2 text-[10px] font-black uppercase tracking-widest animate-bounce">Sessão semelhante encontrada</span>
                        <?php endif; ?>
                    </div>
                    <div class="bg-white rounded-2xl p-6 border border-warning/30 shadow-inner max-h-96 overflow-y-auto custom-scrollbar">
                        <pre class="whitespace-pre-wrap text-sm font-mono text-erp-navy leading-relaxed"><?= htmlspecialchars($resumoRascunhoSessao) ?></pre>
                    </div>
                    <div class="mt-6 flex flex-wrap gap-2">
                        <?php foreach ($acoesConfirmacaoRascunho as $acaoRascunho): ?>
                            <span class="badge bg-erp-navy/5 text-erp-navy border border-erp-navy/10 px-3 py-1.5 text-[9px] font-black uppercase tracking-widest">
                                <?= htmlspecialchars((string) ($acaoRascunho['label'] ?? '')) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-10 flex flex-wrap gap-4">
                        <form method="POST" action="/secretaria/sessoes/publicar-rascunho">
                            <button type="submit" class="btn btn-primary px-8 py-3 shadow-xl shadow-erp-navy/10 hover-lift">
                                <?= $rascunhoEditaSessaoExistente ? 'Confirmar Atualização' : 'Confirmar Publicação' ?>
                            </button>
                        </form>
                        <form method="POST" action="/secretaria/sessoes/cancelar-rascunho">
                            <button type="submit" class="btn btn-secondary px-8 py-3 hover-lift">Descartar Revisão</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="/secretaria/sessoes/salvar" class="space-y-8">
                <?php if ($modoEdicaoSessao): ?><input type="hidden" name="sessao_id" value="<?= (int) ($sessaoEmFormulario['id'] ?? 0) ?>"><?php endif; ?>
                <div class="bg-erp-surface-2 rounded-2xl p-6 border border-erp-border/30 flex flex-col md:flex-row items-center justify-between gap-6">
                    <div>
                        <h3 class="text-lg font-black text-erp-navy tracking-tight"><?= $modoEdicaoSessao ? 'Editar sessão existente' : 'Nova sessão' ?></h3>
                        <p class="text-sm text-erp-muted mt-1 font-medium"><?= $modoEdicaoSessao ? 'Revise os dados da sessão e confirme a atualização.' : 'Preencha os dados da nova sessão para revisão.' ?></p>
                    </div>
                    <?php if ($modoEdicaoSessao): ?>
                        <a href="/secretaria" class="btn btn-secondary px-6 hover-lift shadow-sm">Cancelar Edição</a>
                    <?php endif; ?>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="md:col-span-2">
                        <label for="titulo" class="block text-[10px] font-bold text-erp-muted uppercase tracking-widest mb-3 ml-1">Título da sessão</label>
                        <input type="text" id="titulo" name="titulo" required value="<?= htmlspecialchars((string) ($sessaoEmFormulario['titulo'] ?? '')) ?>" class="form-input shadow-sm !bg-white focus:ring-4 focus:ring-erp-navy/5">
                    </div>
                    <div>
                        <label for="data_hora_inicio" class="block text-[10px] font-bold text-erp-muted uppercase tracking-widest mb-3 ml-1">Data e Hora de Início</label>
                        <input type="datetime-local" id="data_hora_inicio" name="data_hora_inicio" required value="<?= $formatInputDateTime($sessaoEmFormulario['data_hora_inicio'] ?? null) ?>" class="form-input shadow-sm !bg-white">
                    </div>
                    <div>
                        <label for="data_hora_fim" class="block text-[10px] font-bold text-erp-muted uppercase tracking-widest mb-3 ml-1">Encerramento Previsto</label>
                        <input type="datetime-local" id="data_hora_fim" name="data_hora_fim" value="<?= $formatInputDateTime($sessaoEmFormulario['data_hora_fim'] ?? null) ?>" class="form-input shadow-sm !bg-white">
                    </div>
                    <div>
                        <label for="grau_sessao" class="block text-[10px] font-bold text-erp-muted uppercase tracking-widest mb-3 ml-1">Grau da Sessão</label>
                        <select id="grau_sessao" name="grau_sessao" class="form-select shadow-sm !bg-white">
                            <?php foreach (['Aprendiz', 'Companheiro', 'Mestre', 'Outro'] as $g): ?>
                                <option value="<?= $g ?>" <?= (($sessaoEmFormulario['grau_sessao'] ?? '') === $g) ? 'selected' : '' ?>><?= $g ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="grau_personalizado" class="block text-[10px] font-bold text-erp-muted uppercase tracking-widest mb-3 ml-1">Grau (livre)</label>
                        <input type="text" id="grau_personalizado" name="grau_personalizado" value="<?= htmlspecialchars((string) ($sessaoEmFormulario['grau_personalizado'] ?? '')) ?>" placeholder="Se grau for 'Outro'" class="form-input shadow-sm !bg-white">
                    </div>
                    <div>
                        <label for="tipo_sessao_principal" class="block text-[10px] font-bold text-erp-muted uppercase tracking-widest mb-3 ml-1">Tipo Principal</label>
                        <select id="tipo_sessao_principal" name="tipo_sessao_principal" class="form-select shadow-sm !bg-white">
                            <option value="economica" <?= (($sessaoEmFormulario['tipo_sessao_principal'] ?? 'economica') === 'economica') ? 'selected' : '' ?>>Econômica</option>
                            <option value="magna" <?= (($sessaoEmFormulario['tipo_sessao_principal'] ?? '') === 'magna') ? 'selected' : '' ?>>Magna</option>
                            <option value="outra" <?= (($sessaoEmFormulario['tipo_sessao_principal'] ?? '') === 'outra') ? 'selected' : '' ?>>Outra</option>
                        </select>
                    </div>
                    <div>
                        <label for="tipo_sessao_subtipo" class="block text-[10px] font-bold text-erp-muted uppercase tracking-widest mb-3 ml-1">Subtipo</label>
                        <select id="tipo_sessao_subtipo" name="tipo_sessao_subtipo" class="form-select shadow-sm !bg-white">
                            <?php foreach (['economica_1' => 'Econômica de 1º Grau', 'economica_2' => 'Econômica de 2º Grau', 'economica_3' => 'Econômica de 3º Grau', 'magna_iniciacao' => 'Magna de Iniciação', 'magna_elevacao' => 'Magna de Elevação', 'magna_exaltacao' => 'Magna de Exaltação', 'magna_instalacao' => 'Magna de Instalação', 'outra' => 'Outra'] as $v => $l): ?>
                                <option value="<?= $v ?>" <?= (($sessaoEmFormulario['tipo_sessao_subtipo'] ?? '') === $v) ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label for="tipo_sessao_personalizado" class="block text-[10px] font-bold text-erp-muted uppercase tracking-widest mb-3 ml-1">Tipo (livre)</label>
                        <input type="text" id="tipo_sessao_personalizado" name="tipo_sessao_personalizado" value="<?= htmlspecialchars((string) ($sessaoEmFormulario['tipo_sessao_personalizado'] ?? '')) ?>" placeholder="Se tipo ou subtipo não estiver na lista" class="form-input shadow-sm !bg-white">
                    </div>
                    <div>
                        <label for="traje_tipo" class="block text-[10px] font-bold text-erp-muted uppercase tracking-widest mb-3 ml-1">Traje</label>
                        <select id="traje_tipo" name="traje_tipo" class="form-select shadow-sm !bg-white">
                            <option value="maconico" <?= (($sessaoEmFormulario['traje_tipo'] ?? 'maconico') === 'maconico') ? 'selected' : '' ?>>Maçônico</option>
                            <option value="livre" <?= (($sessaoEmFormulario['traje_tipo'] ?? '') === 'livre') ? 'selected' : '' ?>>Livre</option>
                            <option value="outro" <?= (($sessaoEmFormulario['traje_tipo'] ?? '') === 'outro') ? 'selected' : '' ?>>Outro</option>
                        </select>
                    </div>
                    <div>
                        <label for="traje_personalizado" class="block text-[10px] font-bold text-erp-muted uppercase tracking-widest mb-3 ml-1">Traje (livre)</label>
                        <input type="text" id="traje_personalizado" name="traje_personalizado" value="<?= htmlspecialchars((string) ($sessaoEmFormulario['traje_personalizado'] ?? '')) ?>" placeholder="Se traje for 'Outro'" class="form-input shadow-sm !bg-white">
                    </div>
                    <div>
                        <label for="agape_modalidade" class="block text-[10px] font-bold text-erp-muted uppercase tracking-widest mb-3 ml-1">Ágape</label>
                        <select id="agape_modalidade" name="agape_modalidade" class="form-select shadow-sm !bg-white">
                            <option value="nao_havera" <?= (($sessaoEmFormulario['agape_modalidade'] ?? 'nao_havera') === 'nao_havera') ? 'selected' : '' ?>>Não haverá</option>
                            <option value="gratuito" <?= (($sessaoEmFormulario['agape_modalidade'] ?? '') === 'gratuito') ? 'selected' : '' ?>>Sim (gratuito)</option>
                            <option value="pago" <?= (($sessaoEmFormulario['agape_modalidade'] ?? '') === 'pago') ? 'selected' : '' ?>>Sim (pago)</option>
                        </select>
                    </div>
                    <div>
                        <label for="agape_modelo_financeiro" class="block text-[10px] font-bold text-erp-muted uppercase tracking-widest mb-3 ml-1">Modelo Financeiro do Ágape</label>
                        <select id="agape_modelo_financeiro" name="agape_modelo_financeiro" class="form-select shadow-sm !bg-white">
                            <option value="oficial_loja" <?= (($sessaoEmFormulario['agape_modelo_financeiro'] ?? 'oficial_loja') === 'oficial_loja') ? 'selected' : '' ?>>Oficial da Loja</option>
                            <option value="particular" <?= (($sessaoEmFormulario['agape_modelo_financeiro'] ?? '') === 'particular') ? 'selected' : '' ?>>Particular</option>
                            <option value="misto" <?= (($sessaoEmFormulario['caret_modelo_financeiro'] ?? '') === 'misto') ? 'selected' : '' ?>>Misto (Loja + particular)</option>
                        </select>
                    </div>
                    <div>
                        <label for="agape_valor" class="block text-[10px] font-bold text-erp-muted uppercase tracking-widest mb-3 ml-1">Valor do Ágape (opcional)</label>
                        <input type="text" id="agape_valor" name="agape_valor" value="<?= htmlspecialchars((string) ($sessaoEmFormulario['agape_valor'] ?? '')) ?>" placeholder="Definido pelo M. de Banquetes" class="form-input shadow-sm !bg-white">
                    </div>
                    <div>
                        <label for="gestao_referencia" class="block text-[10px] font-bold text-erp-muted uppercase tracking-widest mb-3 ml-1">Gestão de Referência</label>
                        <input type="text" id="gestao_referencia" name="gestao_referencia" value="<?= htmlspecialchars((string) ($sessaoEmFormulario['gestao_referencia'] ?? '')) ?>" placeholder="Ex: 2026/2027" class="form-input shadow-sm !bg-white">
                    </div>
                    <div class="md:col-span-2">
                        <label for="ordem_dia" class="block text-[10px] font-bold text-erp-muted uppercase tracking-widest mb-3 ml-1">Ordem do Dia / Observações</label>
                        <textarea id="ordem_dia" name="ordem_dia" rows="3" class="form-textarea shadow-sm !bg-white"><?= htmlspecialchars((string) ($sessaoEmFormulario['ordem_dia'] ?? '')) ?></textarea>
                    </div>
                    <div class="md:col-span-2 flex items-center gap-3 bg-erp-surface-2 p-4 rounded-xl border border-erp-border/30">
                        <input type="checkbox" id="conta_relatorio_potencia" name="conta_relatorio_potencia" value="1" class="form-checkbox h-5 w-5 rounded text-erp-navy" <?= !array_key_exists('conta_relatorio_potencia', $sessaoEmFormulario) || !empty($sessaoEmFormulario['conta_relatorio_potencia']) ? 'checked' : '' ?>>
                        <label for="conta_relatorio_potencia" class="text-sm font-bold text-erp-navy cursor-pointer">Contabilizar no relatório oficial da potência</label>
                    </div>
                </div>
                <div class="pt-10 border-t border-erp-border/50">
                    <button type="submit" class="btn btn-primary px-10 py-4 text-base shadow-xl shadow-erp-navy/10 hover-lift">
                        <?= $modoEdicaoSessao ? 'Revisar Atualização da Sessão' : 'Continuar para Revisão' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista de Sessões -->
    <div class="mt-12">
        <div class="flex items-center gap-4 mb-8">
            <div class="h-8 w-1 bg-erp-gold rounded-full"></div>
            <h2 class="text-2xl font-black tracking-tight text-erp-navy">Agenda de Sessões</h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php if (empty($sessoes)): ?>
                <div class="md:col-span-2 lg:col-span-3 text-center py-20 glass-surface rounded-[40px] border-dashed">
                    <div class="text-5xl mb-6 opacity-20">📅</div>
                    <p class="text-sm text-erp-muted font-bold uppercase tracking-widest opacity-60">Nenhuma sessão futura cadastrada</p>
                </div>
            <?php else: ?>
                <?php foreach ($sessoes as $sessao): ?>
                <div class="card depth-1 flex flex-col hover-lift group overflow-hidden">
                    <div class="card-body p-6 flex-grow">
                        <div class="flex items-start justify-between gap-4 mb-6">
                            <h3 class="font-black text-erp-navy leading-tight group-hover:text-erp-brand-vibrant transition-colors">
                                <?= htmlspecialchars((string) ($sessao['titulo'] ?: (($sessao['tipo_sessao'] ?? 'Sessão') . ' - ' . ($sessao['grau_sessao'] ?? '')))) ?>
                            </h3>
                            <span class="badge <?= $badgeStatusSessao($sessao['status'] ?? null) ?> text-[9px] font-black uppercase tracking-widest shrink-0">
                                <?= htmlspecialchars((string) ($sessao['status'] ?: 'planejada')) ?>
                            </span>
                        </div>
                        <div class="space-y-4">
                            <div class="bg-erp-surface-2 rounded-xl p-4 border border-erp-border/30 flex flex-col gap-1">
                                <span class="text-[9px] font-bold text-erp-muted uppercase tracking-widest">Cronograma</span>
                                <strong class="text-erp-navy text-sm"><?= $formatDateTime((string) ($sessao['data_hora_inicio'] ?? '')) ?></strong>
                            </div>
                            <div class="bg-erp-surface-2 rounded-xl p-4 border border-erp-border/30 flex flex-col gap-1">
                                <span class="text-[9px] font-bold text-erp-muted uppercase tracking-widest">Presenças Confirmadas</span>
                                <div class="flex items-center gap-2">
                                    <strong class="text-erp-navy text-sm"><?= (int) ($sessao['total_confirmados'] ?? 0) ?></strong>
                                    <?php if ((int) ($sessao['total_agape'] ?? 0) > 0): ?>
                                        <span class="text-[10px] font-bold text-erp-muted bg-white px-2 py-0.5 rounded border border-erp-border/50">
                                            + <?= (int) ($sessao['total_agape'] ?? 0) ?> no ágape
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-erp-surface-2 p-4 border-t border-erp-border/30">
                        <div class="flex flex-wrap gap-2">
                            <a href="/secretaria?editar_sessao=<?= (int) ($sessao['id'] ?? 0) ?>" class="btn btn-sm bg-white hover:bg-erp-navy hover:text-white border-erp-border/50 text-[10px] font-black uppercase tracking-widest px-4 py-2 transition-all">Editar</a>
                            <?php if (in_array($sessao['status'] ?? '', ['planejada', 'alterada'], true)): ?>
                                <form method="POST" action="/secretaria/sessoes/publicar" onsubmit="return confirm('Deseja publicar esta sessão agora?');">
                                    <input type="hidden" name="sessao_id" value="<?= (int) ($sessao['id'] ?? 0) ?>">
                                    <button type="submit" class="btn btn-sm bg-erp-gold hover:bg-erp-gold/80 text-erp-navy border-none text-[10px] font-black uppercase tracking-widest px-4 py-2 transition-all">Publicar</button>
                                </form>
                            <?php endif; ?>
                            <?php if (($sessao['status'] ?? '') !== 'cancelada'): ?>
                                <form method="POST" action="/secretaria/sessoes/cancelar" onsubmit="return confirm('Deseja cancelar esta sessão?');">
                                    <input type="hidden" name="sessao_id" value="<?= (int) ($sessao['id'] ?? 0) ?>">
                                    <button type="submit" class="btn btn-sm bg-danger/10 hover:bg-danger hover:text-white text-danger border-none text-[10px] font-black uppercase tracking-widest px-4 py-2 transition-all">Cancelar</button>
                                </form>
                            <?php else: ?>
                                <form method="POST" action="/secretaria/sessoes/reabrir" onsubmit="return confirm('Deseja reabrir esta sessão?');">
                                    <input type="hidden" name="sessao_id" value="<?= (int) ($sessao['id'] ?? 0) ?>">
                                    <button type="submit" class="btn btn-sm bg-erp-success/10 hover:bg-erp-success hover:text-white text-erp-success border-none text-[10px] font-black uppercase tracking-widest px-4 py-2 transition-all">Reabrir</button>
                                </form>
                            <?php endif; ?>
                            <a href="/secretaria?historico_sessao=<?= (int) ($sessao['id'] ?? 0) ?>" class="btn btn-sm bg-erp-navy/5 hover:bg-erp-navy/10 text-erp-navy border-none text-[10px] font-black uppercase tracking-widest px-4 py-2 transition-all">Log</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($sessaoHistorico)): ?>
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" x-data="{ open: true }" x-show="open">
            <div class="card max-w-2xl w-full m-4" @click.away="open = false">
                <div class="card-header">
                    <div><h3 class="card-title">Histórico Operacional</h3><p class="card-description"><?= htmlspecialchars((string) ($sessaoHistorico['titulo'] ?: (($sessaoHistorico['tipo_sessao'] ?? 'Sessão') . ' - ' . ($sessaoHistorico['grau_sessao'] ?? '')))) ?></p></div>
                    <a href="/secretaria" class="text-gray-400 hover:text-gray-600">&times;</a>
                </div>
                <div class="card-body max-h-96 overflow-y-auto">
                    <div class="space-y-4">
                        <?php if (empty($historicoSessao)): ?>
                            <p class="text-center text-gray-500 py-8">Ainda não há histórico registrado para esta sessão.</p>
                        <?php else: ?>
                            <?php foreach ($historicoSessao as $itemHistorico): ?>
                                <div class="list-item-report">
                                    <div class="flex flex-wrap items-center justify-between text-sm">
                                        <p class="font-semibold"><?= htmlspecialchars((string) ($itemHistorico['acao'] ?? 'Ação')) ?></p>
                                        <p class="text-xs text-gray-500"><?= htmlspecialchars((string) ($itemHistorico['autor_nome'] ?? 'Sistema')) ?> &bull; <?= htmlspecialchars((string) ($itemHistorico['created_at'] ?? '')) ?></p>
                                    </div>
                                    <?php if (!empty($itemHistorico['observacao'])): ?><p class="mt-2 text-sm text-gray-600"><?= htmlspecialchars((string) $itemHistorico['observacao']) ?></p><?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-footer text-right"><a href="/secretaria" class="btn btn-secondary">Fechar</a></div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Trabalhos e Balaustre -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 mt-12">
        <div class="card depth-1">
            <div class="card-header border-b border-erp-border/50 p-6">
                <h2 class="text-xl font-black text-erp-navy tracking-tight">Trabalhos e Peças de Arquitetura</h2>
                <p class="text-sm text-erp-muted mt-1 font-medium">Controle de trabalhos para a Ordem do Dia.</p>
            </div>
            <div class="card-body p-8 text-center">
                <div class="text-4xl mb-4 opacity-20">✍️</div>
                <p class="text-xs text-erp-muted font-bold uppercase tracking-widest opacity-60">Em breve: Módulo de trabalhos refatorado</p>
            </div>
        </div>

        <div class="card depth-1">
            <div class="card-header border-b border-erp-border/50 p-6 flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-black text-erp-navy tracking-tight">Balaústre da Sessão</h2>
                    <p class="text-sm text-erp-muted mt-1 font-medium">Rascunho inteligente baseado na agenda oficial.</p>
                </div>
                <?php if (!empty($balaustreSessao)): ?>
                    <span class="badge <?= $badgeStatusSessao($balaustreSessao['status'] ?? null) ?> text-[10px] font-black uppercase tracking-widest">
                        <?= htmlspecialchars((string) ($balaustreSessao['status'] ?? 'rascunho')) ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="card-body p-8">
                <?php if (empty($sessaoResumo) && empty($modoBalaustreIndependente)): ?>
                    <div class="space-y-6">
                        <div class="bg-erp-surface-2 rounded-2xl p-6 border border-erp-border/30">
                            <p class="text-sm text-erp-navy font-medium leading-relaxed">Você pode preparar o balaústre manualmente a qualquer momento. Selecione uma sessão abaixo para carregar os dados base.</p>
                        </div>
                        <form method="GET" action="/secretaria" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="md:col-span-2">
                                <label for="sessao_resumo_balaustre" class="block text-[10px] font-bold text-erp-muted uppercase tracking-widest mb-3 ml-1">Sessão de Referência</label>
                                <select name="sessao_resumo" id="sessao_resumo_balaustre" class="form-select shadow-sm !bg-white">
                                    <?php foreach ($sessoes as $sessaoOpcao): ?>
                                        <option value="<?= (int) ($sessaoOpcao['id'] ?? 0) ?>">
                                            <?= htmlspecialchars((string) ($sessaoOpcao['titulo'] ?: (($sessaoOpcao['tipo_sessao'] ?? 'Sessão') . ' - ' . ($sessaoOpcao['grau_sessao'] ?? '')))) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="md:col-span-1 flex items-end">
                                <button type="submit" class="btn btn-primary w-full py-3.5 shadow-lg shadow-erp-navy/10 hover-lift">Carregar Dados</button>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <form method="POST" action="/secretaria/balaustres/salvar" class="space-y-10">
                        <input type="hidden" name="sessao_id" value="<?= (int) ($sessaoResumo['id'] ?? 0) ?>">
                        <input type="hidden" name="balaustre_id" value="<?= (int) ($balaustreSessao['id'] ?? 0) ?>">
                        <?php if (!empty($modoBalaustreIndependente)): ?><input type="hidden" name="balaustre_independente" value="1"><?php endif; ?>
                        
                        <div class="bg-erp-navy text-white rounded-3xl p-6 shadow-xl shadow-erp-navy/20">
                            <p class="font-black text-lg leading-tight mb-2"><?= htmlspecialchars((string) ($sessaoResumo['titulo'] ?: (($sessaoResumo['tipo_sessao'] ?? 'Sessão') . ' - ' . ($sessaoResumo['grau_sessao'] ?? '')))) ?></p>
                            <div class="flex items-center gap-4 text-xs font-bold text-white/60 uppercase tracking-widest">
                                <span><?= $formatDateTime((string) ($sessaoResumo['data_hora_inicio'] ?? '')) ?></span>
                                <span class="w-1 h-1 rounded-full bg-white/30"></span>
                                <span><?= (int) ($sessaoResumo['total_presentes'] ?? 0) ?> Presentes</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="numero_balaustre" class="block text-[10px] font-bold text-erp-muted uppercase tracking-widest mb-3 ml-1">Número do Balaústre</label>
                                <input id="numero_balaustre" name="numero_balaustre" value="<?= htmlspecialchars((string) ($balaustreSessao['numero_balaustre'] ?? '')) ?>" class="form-input shadow-sm !bg-white" placeholder="Ex: 012/2026">
                            </div>
                            <div>
                                <label for="template_versao" class="block text-[10px] font-bold text-erp-muted uppercase tracking-widest mb-3 ml-1">Modelo de Ata</label>
                                <input id="template_versao" name="template_versao" value="<?= htmlspecialchars((string) ($balaustreSessao['template_versao'] ?? 'oficial-v1')) ?>" class="form-input shadow-sm !bg-white">
                            </div>
                        </div>

                        <div x-data="{ step: 0, total: 5 }" class="space-y-10">
                            <div class="flex items-center justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-6 w-1 bg-erp-gold rounded-full"></div>
                                    <h3 class="text-sm font-black text-erp-navy uppercase tracking-widest">Redação Oficial por Blocos</h3>
                                </div>
                                <div class="flex gap-2">
                                    <template x-for="i in Array.from({length: total})">
                                        <div :class="step >= (i-1) ? 'bg-erp-gold' : 'bg-erp-border'" class="w-8 h-1 rounded-full transition-all duration-500"></div>
                                    </template>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-8 bg-white/50 p-6 rounded-3xl border border-erp-border/20 shadow-inner">
                                <!-- Passo 0: Início -->
                                <div x-show="step === 0" class="space-y-6 animate-in fade-in slide-in-from-right-4">
                                    <div>
                                        <label class="block text-[10px] font-bold text-erp-muted uppercase tracking-widest mb-3 ml-1">Abertura</label>
                                        <textarea name="bloco_abertura" rows="4" class="form-textarea shadow-sm !bg-white"><?= htmlspecialchars((string) ($blocosBalaustre['abertura'] ?? '')) ?></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-erp-muted uppercase tracking-widest mb-3 ml-1">Balaústre Anterior</label>
                                        <textarea name="bloco_balaustre" rows="4" class="form-textarea shadow-sm !bg-white"><?= htmlspecialchars((string) ($blocosBalaustre['balaustre'] ?? '')) ?></textarea>
                                    </div>
                                </div>

                                <!-- Passo 1: Expediente -->
                                <div x-show="step === 1" class="space-y-6 animate-in fade-in slide-in-from-right-4">
                                    <div>
                                        <label class="block text-[10px] font-bold text-erp-muted uppercase tracking-widest mb-3 ml-1">Expediente</label>
                                        <textarea name="bloco_expediente" rows="4" class="form-textarea shadow-sm !bg-white"><?= htmlspecialchars((string) ($blocosBalaustre['expediente'] ?? '')) ?></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-erp-muted uppercase tracking-widest mb-3 ml-1">Saco de Propostas</label>
                                        <textarea name="bloco_saco_propostas" rows="4" class="form-textarea shadow-sm !bg-white"><?= htmlspecialchars((string) ($blocosBalaustre['saco_propostas'] ?? '')) ?></textarea>
                                    </div>
                                </div>

                                <!-- Passo 2: Ordem do Dia -->
                                <div x-show="step === 2" class="space-y-6 animate-in fade-in slide-in-from-right-4">
                                    <div>
                                        <label class="block text-[10px] font-bold text-erp-muted uppercase tracking-widest mb-3 ml-1">Ordem do Dia</label>
                                        <textarea name="bloco_ordem_dia" rows="10" class="form-textarea shadow-sm !bg-white"><?= htmlspecialchars((string) ($blocosBalaustre['ordem_dia'] ?? '')) ?></textarea>
                                    </div>
                                </div>

                                <!-- Passo 3: Conclusões -->
                                <div x-show="step === 3" class="space-y-6 animate-in fade-in slide-in-from-right-4">
                                    <div>
                                        <label class="block text-[10px] font-bold text-erp-muted uppercase tracking-widest mb-3 ml-1">Tronco de Solidariedade</label>
                                        <textarea name="bloco_tronco_solidariedade" rows="4" class="form-textarea shadow-sm !bg-white"><?= htmlspecialchars((string) ($blocosBalaustre['tronco_solidariedade'] ?? '')) ?></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-erp-muted uppercase tracking-widest mb-3 ml-1">Conclusões do Orador</label>
                                        <textarea name="bloco_conclusoes_orador" rows="4" class="form-textarea shadow-sm !bg-white"><?= htmlspecialchars((string) ($blocosBalaustre['conclusoes_orador'] ?? '')) ?></textarea>
                                    </div>
                                </div>

                                <!-- Passo 4: Encerramento -->
                                <div x-show="step === 4" class="space-y-6 animate-in fade-in slide-in-from-right-4">
                                    <div>
                                        <label class="block text-[10px] font-bold text-erp-muted uppercase tracking-widest mb-3 ml-1">Encerramento</label>
                                        <textarea name="bloco_encerramento" rows="4" class="form-textarea shadow-sm !bg-white"><?= htmlspecialchars((string) ($blocosBalaustre['encerramento'] ?? '')) ?></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-erp-muted uppercase tracking-widest mb-3 ml-1">Assinaturas</label>
                                        <textarea name="bloco_assinaturas" rows="4" class="form-textarea shadow-sm !bg-white"><?= htmlspecialchars((string) ($blocosBalaustre['assinaturas'] ?? 'Secretário              Guarda da Lei              Venerável Mestre')) ?></textarea>
                                    </div>
                                </div>

                                <!-- Navegação do Wizard -->
                                <div class="flex items-center justify-between pt-6 border-t border-erp-border/20">
                                    <button type="button" @click="step--" :disabled="step === 0" class="btn btn-secondary !py-2 !px-4 text-[10px] font-black uppercase tracking-widest disabled:opacity-30">Anterior</button>
                                    <span class="text-[10px] font-black text-erp-muted uppercase tracking-widest">Etapa <span x-text="step + 1"></span> de <span x-text="total"></span></span>
                                    <button type="button" @click="step++" :disabled="step === (total - 1)" class="btn btn-secondary !py-2 !px-4 text-[10px] font-black uppercase tracking-widest disabled:opacity-30">Próxima</button>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-8">
                            <div class="flex items-center gap-3">
                                <div class="h-6 w-1 bg-erp-navy rounded-full"></div>
                                <h3 class="text-sm font-black text-erp-navy uppercase tracking-widest">Cargos e Ocupantes</h3>
                            </div>
                            <div class="space-y-4 max-h-[500px] overflow-y-auto pr-4 custom-scrollbar">
                                <?php foreach ($cargosBalaustreSessao as $cargoSessao): ?>
                                    <div class="bg-erp-surface-2 rounded-2xl p-6 border border-erp-border/30 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                        <input type="hidden" name="cargo_sessao_codigo[]" value="<?= htmlspecialchars((string) ($cargoSessao['codigo'] ?? '')) ?>">
                                        <input type="hidden" name="cargo_sessao_nome[]" value="<?= htmlspecialchars((string) ($cargoSessao['cargo_nome'] ?? $cargoSessao['label'] ?? '')) ?>">
                                        <input type="hidden" name="cargo_sessao_titular_oficial[]" value="<?= htmlspecialchars((string) ($cargoSessao['titular_oficial'] ?? '')) ?>">
                                        <div class="flex flex-col gap-1">
                                            <span class="text-[9px] font-bold text-erp-muted uppercase tracking-widest">Cargo</span>
                                            <strong class="text-xs text-erp-navy uppercase font-black"><?= htmlspecialchars((string) ($cargoSessao['cargo_nome'] ?? $cargoSessao['label'] ?? '-')) ?></strong>
                                        </div>
                                        <div class="flex flex-col gap-1">
                                            <span class="text-[9px] font-bold text-erp-muted uppercase tracking-widest">Titular</span>
                                            <strong class="text-xs text-erp-navy font-black"><?= htmlspecialchars((string) (($cargoSessao['titular_oficial'] ?? '') !== '' ? $cargoSessao['titular_oficial'] : '-')) ?></strong>
                                        </div>
                                        <div>
                                            <label class="text-[9px] font-bold text-erp-muted uppercase tracking-widest mb-1 block">Ocupante</label>
                                            <input name="cargo_sessao_ocupante_nome[]" value="<?= htmlspecialchars((string) ($cargoSessao['ocupante_nome'] ?? $cargoSessao['titular_oficial'] ?? '')) ?>" class="form-input !py-1.5 !text-xs !bg-white">
                                        </div>
                                        <div>
                                            <label class="text-[9px] font-bold text-erp-muted uppercase tracking-widest mb-1 block">Obs.</label>
                                            <input name="cargo_sessao_observacao[]" value="<?= htmlspecialchars((string) ($cargoSessao['observacao'] ?? '')) ?>" class="form-input !py-1.5 !text-xs !bg-white" placeholder="...">
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="space-y-8">
                            <div class="flex items-center gap-3">
                                <div class="h-6 w-1 bg-erp-gold rounded-full"></div>
                                <h3 class="text-sm font-black text-erp-navy uppercase tracking-widest">Palavra a bem da Ordem</h3>
                            </div>
                            
                            <div class="space-y-6">
                                <h4 class="text-[10px] font-bold text-erp-muted uppercase tracking-widest ml-1">Quadro da Loja</h4>
                                <div class="space-y-4">
                                    <?php foreach ($linhasObreirosPalavraBalaustre as $palavraObreiro): ?>
                                        <div class="bg-erp-surface-2 rounded-2xl p-6 border border-erp-border/30 grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div class="md:col-span-1">
                                                <label class="text-[9px] font-bold text-erp-muted uppercase tracking-widest mb-1 block">Obreiro</label>
                                                <select name="palavra_obreiro_id[]" class="form-select !py-1.5 !text-xs !bg-white">
                                                    <option value="">Selecionar...</option>
                                                    <?php foreach ($obreiros as $obreiroPalavraOpcao): ?>
                                                        <option value="<?= htmlspecialchars((string) ($obreiroPalavraOpcao['id'] ?? '')) ?>" <?= (string) ($palavraObreiro['obreiro_id'] ?? '') === (string) ($obreiroPalavraOpcao['id'] ?? '') ? 'selected' : '' ?>><?= htmlspecialchars((string) ($obreiroPalavraOpcao['nome'] ?? 'Obreiro')) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="text-[9px] font-bold text-erp-muted uppercase tracking-widest mb-1 block">Nome Manual</label>
                                                <input name="palavra_obreiro_nome[]" value="<?= htmlspecialchars((string) ($palavraObreiro['nome'] ?? '')) ?>" class="form-input !py-1.5 !text-xs !bg-white">
                                            </div>
                                            <div>
                                                <label class="text-[9px] font-bold text-erp-muted uppercase tracking-widest mb-1 block">Cargo</label>
                                                <input name="palavra_obreiro_cargo[]" value="<?= htmlspecialchars((string) ($palavraObreiro['cargo_no_momento'] ?? '')) ?>" class="form-input !py-1.5 !text-xs !bg-white">
                                            </div>
                                            <div class="md:col-span-3">
                                                <label class="text-[9px] font-bold text-erp-muted uppercase tracking-widest mb-1 block">Resumo da Fala</label>
                                                <input name="palavra_obreiro_fala[]" value="<?= htmlspecialchars((string) ($palavraObreiro['fala_resumida'] ?? '')) ?>" class="form-input !py-1.5 !text-xs !bg-white">
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <h4 class="text-[10px] font-bold text-erp-muted uppercase tracking-widest mt-10 ml-1">Visitantes</h4>
                                <div class="space-y-4">
                                    <?php foreach ($linhasVisitantesBalaustre as $visitante): ?>
                                        <div class="bg-erp-surface-2 rounded-2xl p-6 border border-erp-border/30 grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
                                            <div class="lg:col-span-2">
                                                <label class="text-[9px] font-bold text-erp-muted uppercase tracking-widest mb-1 block">Nome do Visitante</label>
                                                <input name="palavra_visitante_nome[]" value="<?= htmlspecialchars((string) ($visitante['nome'] ?? '')) ?>" class="form-input !py-1.5 !text-xs !bg-white">
                                            </div>
                                            <div>
                                                <label class="text-[9px] font-bold text-erp-muted uppercase tracking-widest mb-1 block">Loja / Oriente</label>
                                                <input name="palavra_visitante_loja[]" value="<?= htmlspecialchars((string) ($visitante['loja'] ?? '')) ?>" class="form-input !py-1.5 !text-xs !bg-white" placeholder="Loja">
                                            </div>
                                            <div>
                                                <label class="text-[9px] font-bold text-erp-muted uppercase tracking-widest mb-1 block">Potência / Grau</label>
                                                <input name="palavra_visitante_potencia[]" value="<?= htmlspecialchars((string) ($visitante['potencia'] ?? '')) ?>" class="form-input !py-1.5 !text-xs !bg-white" placeholder="Potência">
                                            </div>
                                            <div class="lg:col-span-5">
                                                <label class="text-[9px] font-bold text-erp-muted uppercase tracking-widest mb-1 block">Fala Resumida</label>
                                                <input name="palavra_visitante_fala[]" value="<?= htmlspecialchars((string) ($visitante['fala_resumida'] ?? '')) ?>" class="form-input !py-1.5 !text-xs !bg-white">
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-6 pt-10 border-t border-erp-border/50">
                            <?php if (!empty($previewTextoOficialBalaustre)): ?>
                                <div class="bg-erp-surface-2 rounded-[32px] border border-erp-border/30 p-8 mb-8 animate-in fade-in zoom-in-95">
                                    <div class="flex items-center gap-3 mb-6">
                                        <div class="w-2 h-2 rounded-full bg-erp-gold animate-pulse"></div>
                                        <h3 class="text-[10px] font-black text-erp-navy uppercase tracking-widest">Prévia Oficial (Tempo Real)</h3>
                                    </div>
                                    <div class="bg-white rounded-2xl p-8 border border-erp-border/20 shadow-inner max-h-[400px] overflow-y-auto custom-scrollbar">
                                        <div class="prose prose-slate max-w-none font-serif text-sm leading-relaxed text-erp-navy/80 whitespace-pre-wrap"><?= htmlspecialchars($previewTextoOficialBalaustre) ?></div>
                                    </div>
                                    <p class="text-[9px] text-erp-muted mt-4 font-bold uppercase tracking-widest text-center">Salve o balaústre para atualizar esta prévia com os novos dados.</p>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($balaustreSessao['texto_final'])): ?>
                                <div>
                                    <label for="texto_final" class="block text-[10px] font-bold text-erp-muted uppercase tracking-widest mb-3 ml-1">Snapshot Oficial (Leitura)</label>
                                    <textarea id="texto_final" name="texto_final" rows="5" class="form-textarea !bg-erp-surface-2 font-mono text-xs opacity-80" readonly><?= htmlspecialchars((string) ($balaustreSessao['texto_final'] ?? '')) ?></textarea>
                                </div>
                            <?php endif; ?>

                            <div>
                                <label for="observacoes_secretaria" class="block text-[10px] font-bold text-erp-muted uppercase tracking-widest mb-3 ml-1">Observações Privadas da Secretaria</label>
                                <textarea id="observacoes_secretaria" name="observacoes_secretaria" rows="3" class="form-textarea !bg-white shadow-sm"><?= htmlspecialchars($observacoesBalaustre) ?></textarea>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-4 pt-10 border-t border-erp-border/50">
                            <button type="submit" class="btn btn-primary px-10 py-4 shadow-xl shadow-erp-navy/10 hover-lift">Salvar Balaústre</button>
                            <?php if (!empty($sessaoResumo['id']) && !empty($balaustreSessao['id']) && !in_array($balaustreSessao['status'] ?? '', ['apto_votacao', 'em_votacao', 'aprovado'], true)): ?>
                                <button type="submit" form="marcar-balaustre-apto" class="btn btn-warning px-8 py-4 hover-lift">Marcar Apto para Votação</button>
                            <?php endif; ?>
                            <a href="/secretaria/votacao" class="btn btn-secondary px-8 py-4 hover-lift shadow-sm">Acompanhar Votações</a>
                        </div>
                    </form>
                    <?php if (!empty($sessaoResumo['id']) && !empty($balaustreSessao['id']) && !in_array($balaustreSessao['status'] ?? '', ['apto_votacao', 'em_votacao', 'aprovado'], true)): ?>
                        <form id="marcar-balaustre-apto" method="POST" action="/secretaria/balaustres/apto" onsubmit="return confirm('Marcar este balaústre como apto para votação?');">
                            <input type="hidden" name="balaustre_id" value="<?= (int) ($balaustreSessao['id'] ?? 0) ?>">
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../partials/erp_shell_close.php'; ?>


