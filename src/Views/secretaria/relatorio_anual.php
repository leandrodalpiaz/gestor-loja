<?php
declare(strict_types=1);

// #############################################################################
// LÓGICA DE NEGÓCIO E HELPERS
// #############################################################################

$formatDate = static fn(?string $val): string => !empty(trim((string) $val)) ? (new DateTimeImmutable(trim((string) $val)))->format('d/m/Y') : '-';

// #############################################################################
// CONFIGURAÇÃO DO APP SHELL
// #############################################################################

$appShellEyebrow = 'Secretaria';
$appShellTitle = 'Relatório Anual';
$appShellDescription = 'Consolidação anual da atividade da Loja sob responsabilidade da Secretaria.';
$appShellActiveHref = '/secretaria/relatorio-anual';
if (($_SERVER['REQUEST_URI'] ?? '') === '/secretaria/relatorio-gestao') {
    $appShellTitle = 'Relatório de Gestão';
    $appShellActiveHref = '/secretaria/relatorio-gestao';
}
require __DIR__ . '/_sidebar.php';

require __DIR__ . '/../partials/erp_shell_open.php';

?>

<!-- Filtro de Ano -->
<div class="card depth-1 mb-10">
    <div class="card-body p-6">
        <form method="GET" action="/secretaria/relatorio-anual" class="flex flex-col sm:flex-row sm:items-end sm:gap-6">
            <div class="flex-grow">
                <label for="ano" class="block text-[10px] font-bold text-erp-muted uppercase tracking-widest mb-3 ml-1">Ano de Referência</label>
                <select name="ano" id="ano" class="form-select shadow-sm !bg-erp-surface-2 border-erp-border/50">
                    <?php foreach ($anosDisponiveis as $anoOpcao): ?>
                        <option value="<?= (int) $anoOpcao ?>" <?= (int) $anoOpcao === (int) $relatorio['ano'] ? 'selected' : '' ?>>
                            <?= (int) $anoOpcao ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary px-8 py-3.5 shadow-lg shadow-erp-navy/10 hover-lift mt-4 sm:mt-0">Atualizar Relatório</button>
        </form>
    </div>
</div>

<!-- Identificação Institucional -->
<div class="card depth-1 overflow-hidden mb-10 border-l-4 border-l-erp-gold">
    <div class="card-body p-8">
        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-8">
            <div class="flex-grow">
                <div class="flex items-center gap-3 mb-4">
                    <span class="badge bg-erp-gold text-erp-navy px-3 py-1 text-[9px] font-black uppercase tracking-widest shadow-sm">Institucional</span>
                    <h2 class="text-2xl font-black text-erp-navy tracking-tight">
                        <?= htmlspecialchars((string) (($relatorio['loja']['nome_loja'] ?? '') ?: 'Loja não configurada')) ?>
                    </h2>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-4">
                    <div class="flex flex-col gap-1">
                        <span class="text-[10px] font-bold text-erp-muted uppercase tracking-widest">Potência</span>
                        <strong class="text-sm text-erp-navy font-black"><?= htmlspecialchars(trim((string) (($relatorio['loja']['potencia_nome'] ?? 'não informada') . (!empty($relatorio['loja']['potencia_sigla']) ? ' (' . $relatorio['loja']['potencia_sigla'] . ')' : '')))) ?></strong>
                    </div>
                    <div class="flex flex-col gap-1">
                        <span class="text-[10px] font-bold text-erp-muted uppercase tracking-widest">Oriente</span>
                        <strong class="text-sm text-erp-navy font-black"><?= htmlspecialchars((string) (($relatorio['loja']['oriente'] ?? '') ?: 'não informado')) ?></strong>
                    </div>
                    <div class="flex flex-col gap-1">
                        <span class="text-[10px] font-bold text-erp-muted uppercase tracking-widest">Localidade</span>
                        <strong class="text-sm text-erp-navy font-black"><?= htmlspecialchars(trim((string) (($relatorio['loja']['cidade'] ?? '') . ' / ' . ($relatorio['loja']['uf'] ?? '')), ' /')) ?></strong>
                    </div>
                    <div class="flex flex-col gap-1">
                        <span class="text-[10px] font-bold text-erp-muted uppercase tracking-widest">Rito</span>
                        <strong class="text-sm text-erp-navy font-black"><?= htmlspecialchars((string) (($relatorio['loja']['rito'] ?? '') ?: 'não informado')) ?></strong>
                    </div>
                    <div class="flex flex-col gap-1">
                        <span class="text-[10px] font-bold text-erp-muted uppercase tracking-widest">Fundação</span>
                        <strong class="text-sm text-erp-navy font-black"><?= $formatDate($relatorio['loja']['data_fundacao'] ?? null) ?></strong>
                    </div>
                    <div class="flex flex-col gap-1">
                        <span class="text-[10px] font-bold text-erp-muted uppercase tracking-widest">Instalação</span>
                        <strong class="text-sm text-erp-navy font-black"><?= $formatDate($relatorio['loja']['data_instalacao'] ?? null) ?></strong>
                    </div>
                </div>
            </div>
            <?php if (!empty($relatorio['loja']['observacao_relatorios'])): ?>
                <div class="md:max-w-xs bg-warning/5 rounded-2xl p-6 border border-warning/20">
                    <p class="text-[10px] font-bold text-warning uppercase tracking-widest mb-2">Observação Administrativa</p>
                    <p class="text-xs text-warning font-medium leading-relaxed"><?= htmlspecialchars((string) $relatorio['loja']['observacao_relatorios']) ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Métricas Principais -->
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6 mb-12">
    <div class="card-metric depth-1 hover-lift border-t-4 border-t-erp-navy">
        <p class="card-metric-label uppercase tracking-widest font-bold opacity-60">Visitantes</p>
        <p class="card-metric-value text-erp-navy"><?= (int) ($relatorio['visitantes']['total'] ?? 0) ?></p>
    </div>
    <div class="card-metric depth-1 hover-lift border-t-4 border-t-erp-gold">
        <p class="card-metric-label uppercase tracking-widest font-bold opacity-60">Visitas Externas</p>
        <p class="card-metric-value text-erp-navy"><?= (int) ($relatorio['visitas_externas']['total'] ?? 0) ?></p>
    </div>
    <div class="card-metric depth-1 hover-lift border-t-4 border-t-erp-brand-vibrant">
        <p class="card-metric-label uppercase tracking-widest font-bold opacity-60">Congressos</p>
        <p class="card-metric-value text-erp-navy"><?= (int) ($relatorio['congressos']['total'] ?? 0) ?></p>
    </div>
    <div class="card-metric depth-1 hover-lift border-t-4 border-t-erp-muted">
        <p class="card-metric-label uppercase tracking-widest font-bold opacity-60">Palestras</p>
        <p class="card-metric-value text-erp-navy"><?= (int) ($relatorio['palestras']['total'] ?? 0) ?></p>
    </div>
    <div class="card-metric depth-1 hover-lift border-t-4 border-t-erp-success">
        <p class="card-metric-label uppercase tracking-widest font-bold opacity-60">Total Sessões</p>
        <p class="card-metric-value text-erp-navy"><?= (int) ($relatorio['sessoes_por_grau']['total'] ?? 0) ?></p>
    </div>
</div>

<!-- Métricas do Quadro -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
    <div class="card depth-1 p-8 flex items-center justify-between hover-lift">
        <div>
            <p class="text-[10px] font-bold text-erp-muted uppercase tracking-widest mb-1">Obreiros no Quadro</p>
            <p class="text-3xl font-black text-erp-navy"><?= (int) ($relatorio['perfil_quadro']['total'] ?? 0) ?></p>
        </div>
        <div class="text-3xl opacity-20">👥</div>
    </div>
    <div class="card depth-1 p-8 flex items-center justify-between hover-lift">
        <div>
            <p class="text-[10px] font-bold text-erp-muted uppercase tracking-widest mb-1">Idade Média</p>
            <p class="text-3xl font-black text-erp-navy"><?= ($relatorio['perfil_quadro']['idade_media'] ?? null) !== null ? round((float)$relatorio['perfil_quadro']['idade_media']) . ' anos' : '-' ?></p>
        </div>
        <div class="text-3xl opacity-20">🎂</div>
    </div>
    <div class="card depth-1 p-8 flex items-center justify-between hover-lift border-l-4 border-l-erp-brand-vibrant">
        <div>
            <p class="text-[10px] font-bold text-erp-muted uppercase tracking-widest mb-1">Situação Predominante</p>
            <p class="text-2xl font-black text-erp-brand-vibrant"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', (string) ($relatorio['perfil_quadro']['situacoes'][0]['categoria'] ?? 'N/A')))) ?></p>
        </div>
        <div class="text-3xl opacity-20">🏆</div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
    <!-- Coluna Esquerda -->
    <div class="space-y-10">
        <div class="card depth-1">
            <div class="card-header border-b border-erp-border/50 p-6">
                <h3 class="text-lg font-black text-erp-navy tracking-tight">Frequência e Intercâmbio</h3>
                <p class="text-xs text-erp-muted mt-1 font-bold uppercase tracking-widest opacity-60">Visitantes recebidos e visitas realizadas</p>
            </div>
            <div class="card-body p-8 grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <h4 class="text-[11px] font-black text-erp-navy uppercase tracking-widest mb-6 flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-erp-gold"></span> Lojas Visitantes
                    </h4>
                    <div class="space-y-3">
                        <?php if (empty($relatorio['visitantes']['lojas_mais_frequentes'])): ?>
                            <p class="text-xs text-erp-muted font-medium italic py-4">Nenhum registro encontrado.</p>
                        <?php else: ?>
                            <?php foreach ($relatorio['visitantes']['lojas_mais_frequentes'] as $linha): ?>
                                <div class="flex items-center justify-between p-3 bg-erp-surface-2 rounded-xl border border-erp-border/30">
                                    <span class="text-xs font-bold text-erp-navy"><?= htmlspecialchars((string) ($linha['loja'] ?? 'N/A')) ?></span>
                                    <strong class="text-sm font-black text-erp-navy"><?= (int) ($linha['total'] ?? 0) ?></strong>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div>
                    <h4 class="text-[11px] font-black text-erp-navy uppercase tracking-widest mb-6 flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-erp-navy"></span> Lojas Visitadas
                    </h4>
                    <div class="space-y-3">
                        <?php if (empty($relatorio['visitas_externas']['lojas_mais_visitadas'])): ?>
                            <p class="text-xs text-erp-muted font-medium italic py-4">Nenhum registro encontrado.</p>
                        <?php else: ?>
                            <?php foreach ($relatorio['visitas_externas']['lojas_mais_visitadas'] as $linha): ?>
                                <div class="flex items-center justify-between p-3 bg-erp-surface-2 rounded-xl border border-erp-border/30">
                                    <span class="text-xs font-bold text-erp-navy"><?= htmlspecialchars((string) ($linha['loja'] ?? 'N/A')) ?></span>
                                    <strong class="text-sm font-black text-erp-navy"><?= (int) ($linha['total'] ?? 0) ?></strong>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="card depth-1">
            <div class="card-header border-b border-erp-border/50 p-6">
                <h3 class="text-lg font-black text-erp-navy tracking-tight">Atividade Ritualística</h3>
                <p class="text-xs text-erp-muted mt-1 font-bold uppercase tracking-widest opacity-60">Sessões ordinárias e eventos especiais</p>
            </div>
            <div class="card-body p-8 grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <h4 class="text-[11px] font-black text-erp-navy uppercase tracking-widest mb-6 flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-erp-brand-vibrant"></span> Por Grau
                    </h4>
                    <div class="space-y-3">
                        <?php if (empty($relatorio['sessoes_por_grau']['itens'])): ?>
                            <p class="text-xs text-erp-muted font-medium italic py-4">Nenhuma sessão registrada.</p>
                        <?php else: ?>
                            <?php foreach ($relatorio['sessoes_por_grau']['itens'] as $linha): ?>
                                <div class="flex items-center justify-between p-3 bg-erp-surface-2 rounded-xl border border-erp-border/30">
                                    <span class="text-xs font-bold text-erp-navy"><?= htmlspecialchars((string) ($linha['grau_sessao'] ?? 'N/A')) ?></span>
                                    <strong class="text-sm font-black text-erp-navy"><?= (int) ($linha['total'] ?? 0) ?></strong>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div>
                    <h4 class="text-[11px] font-black text-erp-navy uppercase tracking-widest mb-6 flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-erp-muted"></span> Outros Eventos
                    </h4>
                    <div class="space-y-4">
                        <div class="p-4 bg-erp-surface-2 rounded-2xl border border-erp-border/30">
                            <div class="flex justify-between mb-1">
                                <span class="text-[10px] font-bold text-erp-muted uppercase tracking-widest">Congressos</span>
                                <strong class="text-lg font-black text-erp-navy"><?= (int) ($relatorio['congressos']['total'] ?? 0) ?></strong>
                            </div>
                            <p class="text-[9px] text-erp-muted/60 font-bold uppercase tracking-widest">Fonte: <?= htmlspecialchars((string) ($relatorio['congressos']['fonte'] ?? 'N/A')) ?></p>
                        </div>
                        <div class="p-4 bg-erp-surface-2 rounded-2xl border border-erp-border/30">
                            <div class="flex justify-between mb-1">
                                <span class="text-[10px] font-bold text-erp-muted uppercase tracking-widest">Palestras</span>
                                <strong class="text-lg font-black text-erp-navy"><?= (int) ($relatorio['palestras']['total'] ?? 0) ?></strong>
                            </div>
                            <p class="text-[9px] text-erp-muted/60 font-bold uppercase tracking-widest">Fonte: <?= htmlspecialchars((string) ($relatorio['palestras']['fonte'] ?? 'N/A')) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card depth-1">
            <div class="card-header border-b border-erp-border/50 p-6">
                <h3 class="text-lg font-black text-erp-navy tracking-tight">Amostra Cadastral</h3>
                <p class="text-xs text-erp-muted mt-1 font-bold uppercase tracking-widest opacity-60">Leitura rápida para conferência de dados</p>
            </div>
            <div class="card-body p-6 space-y-4 max-h-[500px] overflow-y-auto custom-scrollbar">
                <?php if (empty($relatorio['perfil_quadro']['amostra_cadastral'])): ?>
                    <p class="text-xs text-erp-muted font-medium text-center py-10 glass-surface rounded-2xl border-dashed">Não há obreiros elegíveis no período.</p>
                <?php else: ?>
                    <?php foreach ($relatorio['perfil_quadro']['amostra_cadastral'] as $item): ?>
                        <div class="bg-erp-surface-2 rounded-2xl p-5 border border-erp-border/30 hover:border-erp-brand-vibrant transition-colors">
                            <div class="flex items-center justify-between gap-4 mb-4">
                                <p class="font-black text-erp-navy leading-tight"><?= htmlspecialchars((string) ($item['nome_exibicao'] ?? '-')) ?></p>
                                <span class="badge bg-erp-navy/5 text-erp-navy border border-erp-navy/10 px-2 py-1 text-[9px] font-black uppercase tracking-widest"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', (string) ($item['situacao_quadro'] ?? 'N/A')))) ?></span>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="flex flex-col gap-0.5">
                                    <span class="text-[9px] font-bold text-erp-muted uppercase tracking-widest">Grau</span>
                                    <strong class="text-[11px] text-erp-navy font-black"><?= htmlspecialchars((string) ($item['grau'] ?? 'N/A')) ?></strong>
                                </div>
                                <div class="flex flex-col gap-0.5">
                                    <span class="text-[9px] font-bold text-erp-muted uppercase tracking-widest">Profissão</span>
                                    <strong class="text-[11px] text-erp-navy font-black"><?= htmlspecialchars((string) ($item['profissao'] ?? 'N/A')) ?></strong>
                                </div>
                                <div class="flex flex-col gap-0.5 col-span-2">
                                    <span class="text-[9px] font-bold text-erp-muted uppercase tracking-widest">Escolaridade</span>
                                    <strong class="text-[11px] text-erp-navy font-black"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', (string) ($item['escolaridade'] ?? 'N/A')))) ?></strong>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Coluna Direita -->
    <div class="space-y-10">
        <div class="card depth-1">
            <div class="card-header border-b border-erp-border/50 p-6">
                <h3 class="text-lg font-black text-erp-navy tracking-tight">Movimentação Anual</h3>
                <p class="text-xs text-erp-muted mt-1 font-bold uppercase tracking-widest opacity-60">Evolução do quadro de obreiros</p>
            </div>
            <div class="card-body p-8">
                <div class="grid grid-cols-2 gap-6 mb-8">
                    <div class="bg-erp-navy text-white rounded-3xl p-6 shadow-xl shadow-erp-navy/10">
                        <p class="text-[10px] font-bold opacity-60 uppercase tracking-widest mb-1">Início do Ano</p>
                        <p class="text-3xl font-black"><?= ($relatorio['quadro']['inicio_ano'] ?? null) !== null ? $relatorio['quadro']['inicio_ano'] : '-' ?></p>
                    </div>
                    <div class="bg-erp-gold text-erp-navy rounded-3xl p-6 shadow-xl shadow-erp-gold/10">
                        <p class="text-[10px] font-bold opacity-60 uppercase tracking-widest mb-1">Fim do Ano</p>
                        <p class="text-3xl font-black"><?= ($relatorio['quadro']['fim_ano'] ?? null) !== null ? $relatorio['quadro']['fim_ano'] : '-' ?></p>
                    </div>
                </div>
                <?php if (!empty($relatorio['quadro']['observacao'])): ?>
                    <div class="bg-warning/5 rounded-2xl p-6 border border-warning/20 mb-8 italic text-xs text-warning font-medium">
                        <?= htmlspecialchars((string) $relatorio['quadro']['observacao']) ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($relatorio['quadro']['movimentacao'])): ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <?php foreach (['filiacoes' => 'Filiações', 'regularizacoes' => 'Regularizações', 'reintegracoes' => 'Reintegrações', 'suspensoes' => 'Suspensões', 'desligamentos' => 'Desligamentos', 'oriente_eterno' => 'Oriente Eterno'] as $chave => $label): ?>
                            <div class="flex items-center justify-between p-4 bg-erp-surface-2 rounded-2xl border border-erp-border/30">
                                <span class="text-xs font-bold text-erp-navy"><?= htmlspecialchars($label) ?></span>
                                <strong class="text-base font-black text-erp-navy"><?= (int) ($relatorio['quadro']['movimentacao'][$chave] ?? 0) ?></strong>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card depth-1">
            <div class="card-header border-b border-erp-border/50 p-6">
                <h3 class="text-lg font-black text-erp-navy tracking-tight">Perfil Demográfico</h3>
                <p class="text-xs text-erp-muted mt-1 font-bold uppercase tracking-widest opacity-60">Recorte estatístico do cadastro</p>
            </div>
            <div class="card-body p-8 space-y-10">
                <div>
                    <h4 class="text-[11px] font-black text-erp-navy uppercase tracking-widest mb-6 flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-erp-navy"></span> Escolaridade
                    </h4>
                    <div class="space-y-3">
                        <?php foreach (($relatorio['perfil_quadro']['escolaridade'] ?? []) as $linha): ?>
                            <div class="flex items-center justify-between p-3 bg-erp-surface-2 rounded-xl border border-erp-border/30">
                                <span class="text-xs font-bold text-erp-navy"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', (string) ($linha['categoria'] ?? 'N/A')))) ?></span>
                                <strong class="text-sm font-black text-erp-navy"><?= (int) ($linha['total'] ?? 0) ?></strong>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div>
                    <h4 class="text-[11px] font-black text-erp-navy uppercase tracking-widest mb-6 flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-erp-gold"></span> Situação no Quadro
                    </h4>
                    <div class="space-y-3">
                        <?php foreach (($relatorio['perfil_quadro']['situacoes'] ?? []) as $linha): ?>
                            <div class="flex items-center justify-between p-3 bg-erp-surface-2 rounded-xl border border-erp-border/30">
                                <span class="text-xs font-bold text-erp-navy"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', (string) ($linha['categoria'] ?? 'N/A')))) ?></span>
                                <strong class="text-sm font-black text-erp-navy"><?= (int) ($linha['total'] ?? 0) ?></strong>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div>
                    <h4 class="text-[11px] font-black text-erp-navy uppercase tracking-widest mb-6 flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-erp-brand-vibrant"></span> Distribuição por Grau
                    </h4>
                    <div class="space-y-3">
                        <?php foreach (($relatorio['perfil_quadro']['graus'] ?? []) as $linha): ?>
                            <div class="flex items-center justify-between p-3 bg-erp-surface-2 rounded-xl border border-erp-border/30">
                                <span class="text-xs font-bold text-erp-navy"><?= htmlspecialchars((string) ($linha['categoria'] ?? 'N/A')) ?></span>
                                <strong class="text-sm font-black text-erp-navy"><?= (int) ($linha['total'] ?? 0) ?></strong>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="card depth-1 bg-erp-navy text-white overflow-hidden">
            <div class="card-header border-b border-white/10 p-6">
                <h3 class="text-lg font-black tracking-tight">Notas Administrativas</h3>
            </div>
            <div class="card-body p-8">
                <ul class="space-y-6">
                    <li class="flex gap-4">
                        <span class="text-erp-gold font-black text-sm">01.</span>
                        <p class="text-xs text-white/70 font-medium leading-relaxed">Visitantes refletem os registros estruturados no balaústre.</p>
                    </li>
                    <li class="flex gap-4">
                        <span class="text-erp-gold font-black text-sm">02.</span>
                        <p class="text-xs text-white/70 font-medium leading-relaxed">Visitas externas refletem os registros feitos no saco de propostas.</p>
                    </li>
                    <li class="flex gap-4">
                        <span class="text-erp-gold font-black text-sm">03.</span>
                        <p class="text-xs text-white/70 font-medium leading-relaxed">Congressos e palestras são contabilizados a partir dos eventos informados no balaústre.</p>
                    </li>
                    <li class="flex gap-4">
                        <span class="text-erp-gold font-black text-sm">04.</span>
                        <p class="text-xs text-white/70 font-medium leading-relaxed">O quadro anual depende da trilha cadastral dos obreiros; a precisão melhora com a disciplina de cadastro.</p>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../partials/erp_shell_close.php';
?>
