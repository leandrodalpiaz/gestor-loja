<?php
declare(strict_types=1);

// #############################################################################
// LÓGICA DE NEGÓCIO E HELPERS
// #############################################################################

$usuarioNome = $_SESSION['usuario_nome'] ?? 'Irmão';
$usuarioCargo = (string) ($_SESSION['usuario_cargo'] ?? '');
$usuarioLogado = is_array($_SESSION['usuario_logado'] ?? null) ? $_SESSION['usuario_logado'] : [];
$usuarioNomeCompleto = trim((string) ($usuarioLogado['nome_completo'] ?? ''));
$usuarioNomeHistorico = trim((string) ($usuarioLogado['nome_historico'] ?? ''));
if (in_array(strtolower(trim((string) $usuarioNome)), ['admin', 'administrador'], true)) {
    $usuarioNome = ($usuarioNomeCompleto !== '' && stripos($usuarioNomeCompleto, 'acesso temporario') === false)
        ? $usuarioNomeCompleto
        : (($usuarioNomeHistorico !== '' && !in_array(strtolower($usuarioNomeHistorico), ['admin', 'administrador'], true)) ? $usuarioNomeHistorico : 'Irmão');
}

$isSystemAdmin = (bool) ($_SESSION['is_system_admin'] ?? false);
$isTestSession = isset($_SESSION['usuario_id']) && (string) $_SESSION['usuario_id'] === '0';
$allowAllPanels = filter_var($_ENV['APP_TEST_ALLOW_ALL_PANELS'] ?? 'false', FILTER_VALIDATE_BOOL);
$showAllPanels = filter_var($_ENV['APP_TEST_OPEN_ACCESS'] ?? 'false', FILTER_VALIDATE_BOOL) || $isTestSession || $allowAllPanels;

$dashboardPermissions = isset($dashboardPermissions) && is_array($dashboardPermissions) ? $dashboardPermissions : [];
$dashboardCan = static fn (string $p): bool => $showAllPanels || (bool) ($dashboardPermissions[$p] ?? false);

$secoes = \App\Core\DashboardSections::build($dashboardCan, $isSystemAdmin);
$atalhosOperacionais = array_slice(array_merge([], ...array_map(static fn(array $secao): array => $secao['itens'] ?? [], $secoes)), 0, 6);

$dashboardConfiguracaoLoja = isset($dashboardConfiguracaoLoja) && is_array($dashboardConfiguracaoLoja) ? $dashboardConfiguracaoLoja : [];
$dashboardNomeLoja = trim((string) ($dashboardConfiguracaoLoja['nome_loja'] ?? ($_SESSION['tenant_name'] ?? 'Loja Maçônica')));
if (!empty($dashboardConfiguracaoLoja['numero_loja'])) {
    $dashboardNomeLoja .= ' nº ' . $dashboardConfiguracaoLoja['numero_loja'];
}

$dashboardSessoes = isset($dashboardSessoes) && is_array($dashboardSessoes) ? $dashboardSessoes : [];
$dashboardRecados = isset($dashboardRecados) && is_array($dashboardRecados) ? $dashboardRecados : [];
$dashboardPalavraIrmao = trim((string) ($dashboardPalavraIrmao ?? ''));
$dashboardOutrasLojas = isset($dashboardOutrasLojas) && is_array($dashboardOutrasLojas) ? $dashboardOutrasLojas : [];
$dashboardEfemeridesCards = isset($dashboardEfemeridesCards) && is_array($dashboardEfemeridesCards) ? $dashboardEfemeridesCards : [];

$formatarDataHoraPainel = static function (?string $valor): string {
    if (empty(trim((string) $valor))) return 'Data a definir';
    try {
        return (new DateTimeImmutable($valor))->setTimezone(new DateTimeZone('America/Sao_Paulo'))->format('d/m/Y \à\s H:i');
    } catch (\Throwable) {
        return (string) $valor;
    }
};

$resumirTexto = static fn (?string $texto, int $limite = 180): string => function_exists('mb_strimwidth')
    ? mb_strimwidth(trim(strip_tags((string) $texto)), 0, $limite, '...')
    : substr(trim(strip_tags((string) $texto)), 0, $limite - 3) . '...';

$statusClasses = static fn (?string $status): string => match (strtolower(trim((string) $status))) {
    'publicada', 'confirmada', 'confirmado', 'ativa', 'agendada', 'programada' => 'badge-status-success',
    'cancelada', 'cancelado', 'encerrada' => 'badge-status-danger',
    'rascunho', 'pendente' => 'badge-status-warning',
    default => 'badge-status-neutral',
};

// #############################################################################
// CONFIGURAÇÃO DO APP SHELL
// #############################################################################

$appShellEyebrow = 'Painel de Comando';
$appShellTitle = $dashboardNomeLoja;
$appShellDescription = 'Abertura operacional da Loja com agenda, recados e acessos prioritários.';
$appShellActiveHref = '/dashboard';
$appShellUserLabel = $usuarioNome;
$appShellActions = [['label' => 'Sair', 'href' => '/logout']];
$appShellSidebarSections = array_merge(
    [['title' => 'Geral', 'items' => [['label' => 'Painel', 'href' => '/dashboard']]]],
    array_map(
        static fn(array $secao): array => ['title' => (string) ($secao['titulo'] ?? 'Secao'), 'items' => $secao['itens'] ?? []],
        $secoes
    )
);

require __DIR__ . '/partials/erp_shell_open.php';
?>

<style>
.art-card {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 16px;
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
}
.art-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.4);
    border-color: rgba(201, 162, 39, 0.2);
}
</style>

<div class="space-y-6">
    <!-- Flash Messages -->
    <?php if (isset($dashboardMensagemSucesso)): ?>
        <div class="mb-8 p-4 rounded-xl border border-emerald-500/20 bg-emerald-500/10 text-emerald-400 font-bold text-sm"><?= htmlspecialchars($dashboardMensagemSucesso) ?></div>
    <?php endif; ?>
    <?php if (isset($dashboardMensagemErro)): ?>
        <div class="mb-8 p-4 rounded-xl border border-red-500/20 bg-red-500/10 text-red-400 font-bold text-sm"><?= htmlspecialchars($dashboardMensagemErro) ?></div>
    <?php endif; ?>

    <!-- Welcome Card -->
    <div class="card p-6 bg-gradient-to-r from-erp-navy-deep to-erp-navy relative overflow-hidden border border-white/5 shadow-lg">
        <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-erp-gold/5 rounded-full blur-3xl"></div>
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 relative z-10">
            <div>
                <p class="text-[10px] font-bold text-erp-gold uppercase tracking-widest mb-1">
                    <?= htmlspecialchars($dashboardNomeLoja) ?>
                </p>
                <h2 class="text-2xl font-cinzel font-bold text-white leading-tight">
                    Saudações, <?= htmlspecialchars($usuarioNome) ?>!
                </h2>
                <p class="text-xs text-erp-muted mt-1 leading-relaxed">
                    A Oficina está operacional. Reuniões ordinárias: <strong class="text-slate-300"><?= htmlspecialchars($dashboardConfiguracaoLoja['dia_semana_reuniao'] ?? 'A definir') ?></strong>.
                </p>
            </div>
            <?php if ($usuarioCargo !== ''): ?>
                <div class="shrink-0 flex items-center gap-3 bg-white/5 border border-white/10 px-4 py-2.5 rounded-xl">
                    <div class="w-8 h-8 rounded-lg bg-erp-gold/15 text-erp-gold flex items-center justify-center text-xs font-bold font-cinzel">
                        ★
                    </div>
                    <div>
                        <p class="text-[9px] text-erp-muted uppercase font-bold tracking-wider">Cargo / Função</p>
                        <p class="text-xs font-semibold text-white"><?= htmlspecialchars($usuarioCargo) ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Shortcuts Grid -->
    <?php if (!empty($atalhosOperacionais)): ?>
        <section class="mb-2">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-1 h-5 bg-erp-gold rounded-full"></div>
                <h3 class="font-cinzel text-sm text-white tracking-widest uppercase">Atalhos Operacionais</h3>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                <?php foreach ($atalhosOperacionais as $atalho): ?>
                    <a href="<?= htmlspecialchars((string) ($atalho['href'] ?? '#')) ?>" 
                       class="glass-surface hover-lift flex flex-col items-center justify-center p-4 rounded-2xl border border-white/5 hover:border-erp-gold/40 transition-all text-center group">
                        <div class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center text-erp-gold mb-3 group-hover:scale-110 transition-transform shadow-inner">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <?= $getSidebarIcon((string) ($atalho['label'] ?? '')) ?>
                            </svg>
                        </div>
                        <span class="text-xs font-bold text-white line-clamp-1"><?= htmlspecialchars((string) ($atalho['label'] ?? 'Item')) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- Main Grid -->
    <div class="grid grid-cols-1 xl:grid-cols-12 gap-8">
        
        <!-- Center / Left: Próximos Trabalhos (Sessão Própria) & Convites -->
        <div class="xl:col-span-8 space-y-10">
            
            <section>
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-1 h-6 bg-[#C9A227] rounded-full"></div>
                    <h2 class="font-cinzel text-xl text-white tracking-widest uppercase">Próximos Trabalhos</h2>
                </div>
                
                <?php if (!empty($dashboardSessoes)): ?>
                    <?php foreach (array_slice($dashboardSessoes, 0, 1) as $sessao): ?>
                        <div class="art-card p-0 overflow-hidden flex flex-col md:flex-row bg-[#0f1c2e] shadow-[0_20px_50px_rgba(0,0,0,0.5)]">
                            
                            <!-- "Image" Area (Overlay Artístico sobre a tela em branco) -->
                            <div class="w-full md:w-5/12 relative bg-[#0A1628] border-r border-white/5 overflow-hidden flex items-center justify-center group">
                                <div class="absolute inset-0 flex items-center justify-center text-slate-600 z-0">
                                    <svg class="w-8 h-8 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                </div>
                                <img src="/assets/images/templates/efemerides/card_oficial_sessao.png" alt="Card Oficial da Sessão" class="relative z-10 w-full h-auto object-contain transition-transform duration-700 group-hover:scale-[1.02]">
                                
                                <!-- Text Overlay (Dados impressos via HTML sobre o pergaminho) -->
                                <div class="absolute inset-0 z-20 flex flex-col justify-center items-center text-center px-6 sm:px-10 transition-transform duration-700 group-hover:scale-[1.02] mt-12 md:mt-16">
                                    <p class="text-[9px] sm:text-[11px] font-bold uppercase tracking-[0.2em] text-[#6b5133] mb-3">
                                        <?= htmlspecialchars($dashboardNomeLoja) ?>
                                    </p>
                                    <h3 class="font-cinzel text-xl sm:text-2xl lg:text-3xl font-extrabold text-[#3e2e1c] leading-tight mb-2 text-center drop-shadow-sm">
                                        <?= htmlspecialchars(trim((string) ($sessao['titulo'] ?? '')) !== '' ? $sessao['titulo'] : ($sessao['tipo_sessao'] ?? 'Sessão Oficial')) ?>
                                    </h3>
                                    <p class="text-[12px] sm:text-[14px] font-serif font-bold text-[#8a7342] mb-1 uppercase tracking-widest">
                                        <?= (new DateTimeImmutable($sessao['data_hora_inicio'] ?? 'now'))->format('d/m/Y') ?>
                                    </p>
                                    <p class="text-[11px] sm:text-[13px] font-serif italic text-[#5c472b] mb-6">
                                        <?= htmlspecialchars($formatarDataHoraPainel($sessao['data_hora_inicio'] ?? null)) ?>
                                    </p>
                                    <div class="mt-2 inline-block px-4 py-1.5 border-2 border-[#8a7342] text-[#6b5133] text-[9px] font-bold uppercase tracking-widest rounded-full bg-[#e8dcc4]/20 backdrop-blur-sm shadow-sm">
                                        GRAU <?= htmlspecialchars($sessao['grau_sessao'] ?? 'N/D') ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Details & Actions Area -->
                            <div class="w-full md:w-7/12 p-8 flex flex-col justify-between">
                                <div>
                                    <div class="flex justify-between items-start mb-4">
                                        <h4 class="font-cinzel text-lg font-bold text-white tracking-wide">Registro de Presença</h4>
                                        <span class="px-2 py-1 bg-white/5 border border-white/10 rounded text-[10px] uppercase tracking-widest text-[#C9A227]">
                                            <?= htmlspecialchars($sessao['status'] ?? 'Agendada') ?>
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-400 mb-6 leading-relaxed">Sua confirmação prévia é essencial para a organização da Loja e logística do Ágape fraterno.</p>
                                    
                                    <div class="space-y-4 mb-8 bg-white/5 rounded-xl p-4 border border-white/5">
                                        <div class="flex justify-between items-center text-sm">
                                            <span class="text-slate-400">Modalidade:</span>
                                            <strong class="text-white"><?= htmlspecialchars($sessao['tipo_sessao'] ?? 'N/D') ?></strong>
                                        </div>
                                        <div class="flex justify-between items-center text-sm">
                                            <span class="text-slate-400">Irmãos Confirmados:</span>
                                            <div class="flex items-center gap-2">
                                                <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                                                <strong class="text-white"><?= (int)($sessao['total_confirmados'] ?? 0) ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- POST Form Preserved -->
                                <form method="POST" action="/dashboard#sessoes-loja" class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-auto">
                                    <input type="hidden" name="dashboard_action" value="sessao_confirmacao">
                                    <input type="hidden" name="sessao_id" value="<?= (int) ($sessao['id'] ?? 0) ?>">
                                    <button type="submit" name="acao" value="confirmar" class="btn btn-primary">Confirmar</button>
                                    <button type="submit" name="acao" value="cancelar" class="btn btn-secondary">Ausência</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h3 class="empty-state-title">Nenhum trabalho programado</h3>
                        <p class="empty-state-description">A Oficina está com a agenda em dia. Novas sessões e convocações aparecerão aqui.</p>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Convites Externos (Carrossel / Lojas Co-irmãs) -->
            <section>
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <div class="w-1 h-6 bg-slate-600 rounded-full"></div>
                        <h2 class="font-cinzel text-lg text-slate-300 tracking-widest uppercase">Lojas Co-Irmãs</h2>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php if (!empty($dashboardOutrasLojas)): ?>
                        <?php foreach ($dashboardOutrasLojas as $sessaoExterna): ?>
                            <div class="art-card p-0 overflow-hidden group cursor-pointer relative aspect-[3/4] flex flex-col justify-end border border-white/10 hover:border-[#C9A227]/50 shadow-lg">
                                <img src="/assets/images/templates/efemerides/card_oficial_convite.png" alt="Fundo Convite" class="absolute inset-0 w-full h-full object-cover opacity-60 group-hover:opacity-90 transition-all duration-500 group-hover:scale-110">
                                <div class="absolute inset-0 bg-gradient-to-t from-[#0A1628] via-[#0A1628]/80 to-transparent"></div>
                                
                                <div class="relative z-10 p-5 pt-12 mt-auto">
                                    <div class="text-[9px] font-bold text-[#C9A227] uppercase tracking-widest mb-2 opacity-90">
                                        <?= htmlspecialchars($sessaoExterna['loja'] ?? 'Outra Loja') ?>
                                    </div>
                                    <h3 class="font-cinzel text-base font-bold text-white mb-2 leading-tight group-hover:text-[#C9A227] transition-colors drop-shadow-md">
                                        <?= htmlspecialchars($sessaoExterna['titulo'] ?? 'Sessão') ?>
                                    </h3>
                                    <p class="text-[11px] text-slate-300 font-medium font-serif italic">
                                        <?= htmlspecialchars($formatarDataHoraPainel($sessaoExterna['data_hora_inicio'] ?? null)) ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-span-full">
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                                    </svg>
                                </div>
                                <h3 class="empty-state-title">Sem convites ativos</h3>
                                <p class="empty-state-description">Não há convocações ou convites de outras Lojas Co-Irmãs registrados no momento.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

        </div>

        <!-- Right: Efemérides & Recados -->
        <div class="xl:col-span-4 space-y-8">
            
            <!-- Mural & Efemérides -->
            <section>
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-1 h-6 bg-white/20 rounded-full"></div>
                    <h2 class="font-cinzel text-lg text-slate-300 tracking-widest uppercase">Efemérides</h2>
                </div>
                <div class="art-card p-6 bg-gradient-to-b from-[#162a42] to-[#0f1c2e] relative overflow-hidden group">
                    <div class="absolute -right-4 -top-4 w-24 h-24 bg-[#C9A227] opacity-5 rounded-full blur-xl group-hover:opacity-10 transition-opacity"></div>
                    <?php if ($dashboardPalavraIrmao): ?>
                        <div class="mb-4 flex items-center justify-center">
                            <span class="text-2xl opacity-20">❝</span>
                        </div>
                        <p class="text-sm text-slate-300 leading-relaxed italic text-center px-4">
                            <?= nl2br(htmlspecialchars($resumirTexto($dashboardPalavraIrmao, 220))) ?>
                        </p>
                        <div class="mt-6 flex justify-center">
                            <a href="/chancelaria/efemerides" class="text-[10px] font-bold text-[#C9A227] uppercase tracking-widest hover:text-white transition-colors border border-[#C9A227]/30 px-4 py-2 rounded-full hover:bg-[#C9A227]/10">
                                Ver Artes do Dia
                            </a>
                        </div>
                    <?php else: ?>
                        <p class="text-sm text-slate-500 text-center py-6">Nenhuma data histórica para hoje.</p>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Recados Rápidos -->
            <section>
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-1 h-6 bg-white/20 rounded-full"></div>
                    <h2 class="font-cinzel text-lg text-slate-300 tracking-widest uppercase">Avisos da Loja</h2>
                </div>
                <?php if (!empty($dashboardRecados)): ?>
                    <div class="space-y-3">
                        <?php foreach (array_slice($dashboardRecados, 0, 3) as $recado): ?>
                            <div class="art-card p-4 border-l-2 border-l-[#C9A227] hover:bg-white/5 transition-colors">
                                <h3 class="font-bold text-xs text-white mb-1 tracking-wide"><?= htmlspecialchars($recado['titulo'] ?? 'Recado') ?></h3>
                                <p class="text-[11px] text-slate-400 leading-relaxed"><?= htmlspecialchars($resumirTexto($recado['conteudo'] ?? '', 100)) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="art-card p-6 text-center text-sm text-slate-500 border border-dashed border-white/10">Nenhum aviso afixado.</div>
                <?php endif; ?>
            </section>
            
            <!-- Mini-Métricas Ocultas no Fundo -->
            <div class="grid grid-cols-2 gap-3 pt-4">
                <div class="art-card p-3 text-center bg-white/5">
                    <div class="text-[9px] text-slate-500 uppercase tracking-widest mb-1">Seu Perfil</div>
                    <div class="text-xs font-bold text-white truncate px-2"><?= htmlspecialchars($usuarioCargo ?: 'Geral') ?></div>
                </div>
                <div class="art-card p-3 text-center bg-white/5">
                    <div class="text-[9px] text-slate-500 uppercase tracking-widest mb-1">Dia Fixo</div>
                    <div class="text-xs font-bold text-white"><?= htmlspecialchars($dashboardConfiguracaoLoja['dia_semana_reuniao'] ?? 'A definir') ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Espaço de Publicação: Efemérides Visuais / Nossa História (FULL CARDS) -->
    <?php
    $cardsPorCategoria = [];
    foreach ($dashboardEfemeridesCards as $c) {
        $cat = trim((string) ($c['categoria'] ?? 'Geral'));
        if ($cat === '') $cat = 'Geral';
        $catNorm = strtolower($cat);
        $label = match(true) {
            str_contains($catNorm, 'história') || str_contains($catNorm, 'historia') => 'Nossa História',
            str_contains($catNorm, 'aniversário') || str_contains($catNorm, 'aniversario') ||
            in_array($catNorm, ['esposa', 'cunhada', 'filho', 'filha', 'sobrinho', 'sobrinha', 'membro', 'irmao', 'irmão', 'familiar'], true) => 'Aniversariantes do Dia',
            default => mb_convert_case($cat, MB_CASE_TITLE, "UTF-8")
        };
        $cardsPorCategoria[$label][] = $c;
    }
    ?>

    <?php if (!empty($cardsPorCategoria)): ?>
        <div class="mt-12 pt-10 border-t border-white/10">
            <?php foreach ($cardsPorCategoria as $label => $grupoCards): ?>
                <div class="mb-12 last:mb-0">
                    <div class="flex items-center gap-3 mb-8">
                        <div class="w-1 h-8 bg-[#C9A227] rounded-full shadow-[0_0_10px_rgba(201,162,39,0.4)]"></div>
                        <h2 class="font-cinzel text-xl md:text-2xl text-white tracking-[0.15em] uppercase"><?= htmlspecialchars($label) ?></h2>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                        <?php foreach ($grupoCards as $c): ?>
                            <?php if (!empty($c['image_url'])): ?>
                                <div class="relative art-card p-0 bg-[#0D1117] shadow-[0_25px_50px_-12px_rgba(0,0,0,0.7)] overflow-hidden aspect-[9/16] w-full max-w-[340px] mx-auto border border-white/10 hover:border-[#C9A227]/40 transition-all duration-500 hover:-translate-y-2">
                                    <img src="<?= htmlspecialchars($c['image_url']) ?>" 
                                         alt="<?= htmlspecialchars($c['titulo'] ?? 'Card') ?>" 
                                         loading="lazy"
                                         class="w-full h-full object-cover cursor-pointer" 
                                         onclick="window.open(this.src, '_blank')">
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Transparência & Arquivos -->
    <div class="mt-16 pt-10 border-t border-white/10">
        <h2 class="font-cinzel text-2xl text-white tracking-widest uppercase mb-2">Transparência & Arquivos</h2>
        <p class="text-sm text-slate-400 mb-8 font-light">Acesso rápido aos documentos oficiais, prestações de contas e memórias fotográficas.</p>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            
            <!-- Prestação de Contas -->
            <a href="/tesouraria/obrigacoes" class="group art-card p-6 flex flex-col items-center text-center hover:bg-white/5 transition-colors cursor-pointer">
                <div class="w-12 h-12 rounded-full bg-emerald-500/10 text-emerald-400 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h3 class="font-bold text-sm text-white uppercase tracking-widest mb-2">Tesouraria</h3>
                <p class="text-xs text-slate-400 mb-4">Balancetes e prestação de contas mensal.</p>
                <div class="mt-auto text-[10px] font-bold text-[#C9A227] uppercase tracking-widest">Ver Relatórios</div>
            </a>

            <!-- Resumos do Secretário -->
            <a href="/secretaria/balaustres" class="group art-card p-6 flex flex-col items-center text-center hover:bg-white/5 transition-colors cursor-pointer">
                <div class="w-12 h-12 rounded-full bg-blue-500/10 text-blue-400 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                </div>
                <h3 class="font-bold text-sm text-white uppercase tracking-widest mb-2">Secretaria</h3>
                <p class="text-xs text-slate-400 mb-4">Resumos das últimas pranchas e decretos.</p>
                <div class="mt-auto text-[10px] font-bold text-[#C9A227] uppercase tracking-widest">Ler Resumos</div>
            </a>

            <!-- Publicações Oficiais (PDFs) -->
            <a href="/biblioteca" class="group art-card p-6 flex flex-col items-center text-center hover:bg-white/5 transition-colors cursor-pointer">
                <div class="w-12 h-12 rounded-full bg-red-500/10 text-red-400 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                </div>
                <h3 class="font-bold text-sm text-white uppercase tracking-widest mb-2">Biblioteca</h3>
                <p class="text-xs text-slate-400 mb-4">Boletins oficiais e normativas em PDF.</p>
                <div class="mt-auto text-[10px] font-bold text-[#C9A227] uppercase tracking-widest">Acessar Mural</div>
            </a>

            <!-- Galeria de Imagens -->
            <a href="#" class="group art-card p-6 flex flex-col items-center text-center hover:bg-white/5 transition-colors cursor-pointer" onclick="alert('Funcionalidade em desenvolvimento'); return false;">
                <div class="w-12 h-12 rounded-full bg-purple-500/10 text-purple-400 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </div>
                <h3 class="font-bold text-sm text-white uppercase tracking-widest mb-2">Galeria</h3>
                <p class="text-xs text-slate-400 mb-4">Fotos e memórias das últimas sessões.</p>
                <div class="mt-auto text-[10px] font-bold text-[#C9A227] uppercase tracking-widest">Ver Acervo</div>
            </a>

        </div>
    </div>

</div>

<?php require __DIR__ . '/partials/erp_shell_close.php'; ?>

