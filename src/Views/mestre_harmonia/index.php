<?php
declare(strict_types=1);

// #############################################################################
// LÓGICA DE NEGÓCIO E HELPERS
// #############################################################################

$usuarioNome = (string) ($_SESSION['usuario_nome'] ?? 'Irmão');
$operadorEmExercicio = (string) ($operadorEmExercicio ?? '');
$basePathValue = (string) ($payload['base_path'] ?? '');
$selectedSessionPath = (string) (($payload['selected_session']['path'] ?? ''));

// #############################################################################
// RENDERIZAÇÃO
// #############################################################################

ob_start();
?>
<div class="min-h-screen bg-black/40 text-slate-200 font-sans p-4 lg:p-6 flex flex-col" id="app-container">

    <!-- Cabeçalho Principal -->
    <header class="bg-white/[0.01] border border-white/5 rounded-2xl p-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
        <div>
            <span class="text-[10px] font-bold uppercase tracking-wider text-erp-gold">Cargo Oficial</span>
            <h1 class="text-2xl font-black text-white tracking-tight">MESTRE DE HARMONIA</h1>
            <div class="text-xs text-slate-400 mt-1.5 flex items-center gap-2">
                <span>Responsável: <strong class="text-white"><?= htmlspecialchars($usuarioNome) ?></strong></span>
                <span class="text-white/10">&middot;</span>
                <span>Operador: <strong id="operatorNameDisplay" class="text-white">Não informado</strong></span>
                <button type="button" id="btnChangeOperator" class="text-erp-gold hover:underline text-xs font-semibold ml-1">[Alterar]</button>
            </div>
        </div>
        <div class="text-left md:text-right">
            <div id="sessionLabel" class="font-bold text-lg text-white">Sessão não carregada</div>
            <div id="globalStatus" class="text-xs text-yellow-400 font-semibold mt-1">Aguardando a escolha da sessão</div>
            <a href="/miniapp/mestre-harmonia" target="_blank" class="mt-3 inline-flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold py-2 px-4 rounded-xl transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                Abrir Painel Mobile
            </a>
        </div>
    </header>

    <!-- Configuração Pasta Base -->
    <form class="bg-white/[0.01] border border-white/5 rounded-2xl p-4 mb-6" method="get" action="/mestre-harmonia">
        <div class="flex flex-col sm:flex-row items-center gap-3">
            <div class="flex-grow w-full">
                <input type="text" name="base_path" value="<?= htmlspecialchars($basePathValue) ?>" placeholder="Pasta base das playlists no servidor..." class="form-input w-full">
            </div>
            <div class="w-full sm:w-64">
                <select name="sessao_path" id="sessionPicker" class="form-select w-full"></select>
            </div>
            <button class="btn btn-primary w-full sm:w-auto font-bold py-2.5 px-6 shrink-0" type="submit">Recarregar</button>
        </div>
    </form>

    <!-- Layout Principal -->
    <main class="flex-grow grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Playlist e Roteiro (1/3) -->
        <aside class="lg:col-span-1 bg-white/[0.01] border border-white/5 rounded-2xl p-6 flex flex-col min-h-0">
            <div class="flex justify-between items-baseline border-b border-white/5 pb-3 mb-4">
                <h2 class="text-base font-bold text-white uppercase tracking-wider">Roteiro da Sessão</h2>
                <span id="playlistCount" class="text-slate-400 text-xs">0 etapas</span>
            </div>
            <div class="flex-grow overflow-y-auto pr-2 space-y-2 max-h-[500px] lg:max-h-none" id="stepsList">
                <!-- Itens da playlist serão inseridos aqui via JS -->
            </div>
            <div id="summaryText" class="mt-4 text-[10px] text-slate-500 border-t border-white/5 pt-3">
                Principais 0 | Transição 0 | Extras 0
            </div>
        </aside>

        <!-- Player e Controles de Volume (2/3) -->
        <div class="lg:col-span-2 flex flex-col gap-6">
            <!-- Player Principal -->
            <article class="bg-white/[0.01] border border-white/5 rounded-2xl p-6 relative overflow-hidden">
                <div class="absolute top-0 left-0 h-1 bg-gradient-to-r from-blue-500 via-indigo-500 to-purple-500 w-full"></div>
                
                <div class="flex justify-between items-start mb-4">
                    <h2 id="currentStepCode" class="text-3xl font-black text-blue-400 font-mono">--</h2>
                    <div id="playerStatus" class="px-3 py-1 text-xs font-semibold rounded-full bg-yellow-500/10 text-yellow-400 border border-yellow-500/20">
                        Aguardando
                    </div>
                </div>
                
                <div id="currentTrackTitle" class="text-2xl lg:text-3xl font-bold text-white mb-6 truncate">
                    Selecione uma etapa no roteiro
                </div>
                
                <div class="flex flex-wrap items-center justify-between text-xs text-slate-400 gap-4 mb-4">
                    <span>Tipo: <strong id="currentType" class="text-white">-</strong></span>
                    <span>Fade: <strong id="fadeStatus" class="text-white">Pronto</strong></span>
                    <span>Volume: <strong id="volumeStatus" class="text-white">100%</strong></span>
                </div>
                
                <div class="flex items-center gap-4">
                    <div class="w-full bg-white/5 border border-white/10 rounded-full h-3">
                        <div id="progressBar" class="bg-blue-500 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                    </div>
                    <strong id="timeText" class="text-lg font-mono text-white tracking-widest shrink-0">00:00/00:00</strong>
                </div>
            </article>

            <!-- Controles Principais (TÁTIL AMPLIADO) -->
            <section class="bg-white/[0.01] border border-white/5 rounded-2xl p-6 grid grid-cols-2 sm:grid-cols-4 md:grid-cols-7 gap-4">
                <button type="button" class="ctrl-btn bg-emerald-600/20 hover:bg-emerald-600/30 text-emerald-400 border border-emerald-500/20 rounded-xl py-4 font-bold text-sm transition" id="btnStart">Iniciar</button>
                <button type="button" class="ctrl-btn bg-yellow-600/20 hover:bg-yellow-600/30 text-yellow-400 border border-yellow-500/20 rounded-xl py-4 font-bold text-sm transition" id="btnPause">Pausar</button>
                <button type="button" class="ctrl-btn bg-red-600/20 hover:bg-red-600/30 text-red-400 border border-red-500/20 rounded-xl py-4 font-bold text-sm transition" id="btnStop">Parar</button>
                <button type="button" class="ctrl-btn border border-white/10 text-slate-300 hover:bg-white/5 rounded-xl py-4 font-semibold text-sm transition" id="btnRestart">Reiniciar</button>
                <button type="button" class="ctrl-btn border border-white/10 text-slate-300 hover:bg-white/5 rounded-xl py-4 font-semibold text-sm transition" id="btnPrev">Anterior</button>
                <button type="button" class="ctrl-btn bg-blue-600/20 hover:bg-blue-600/30 text-blue-400 border border-blue-500/20 rounded-xl py-4 font-bold text-sm transition col-span-1" id="btnNext">Próxima</button>
                <button type="button" class="ctrl-btn bg-purple-600/20 hover:bg-purple-600/30 text-purple-400 border border-purple-500/20 rounded-xl py-4 font-bold text-sm transition" id="btnSilence">Silêncio</button>
            </section>

            <!-- Próxima Etapa e Apoio -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <article class="bg-white/[0.01] border border-white/5 rounded-2xl p-5">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Próxima Etapa</h3>
                    <div id="nextStepCode" class="text-blue-400 font-bold text-lg font-mono">--</div>
                    <div id="nextStepTitle" class="text-white font-semibold mt-1 truncate">Sem próxima etapa programada</div>
                </article>
                <article class="bg-white/[0.01] border border-white/5 rounded-2xl p-5">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Apoio Ritual (Músicas Alternativas)</h3>
                    <div id="alternativesList" class="text-xs text-slate-300 space-y-1.5"></div>
                </article>
            </div>

            <!-- Controles de Ajuste Fino de Áudio (TÁTIL AMPLIADO) -->
            <section class="bg-white/[0.01] border border-white/5 rounded-2xl p-6 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-4">
                <button type="button" class="ctrl-btn border border-white/10 text-slate-300 hover:bg-white/5 rounded-xl py-3 font-semibold text-xs transition" id="btnFadeIn">Fade In</button>
                <button type="button" class="ctrl-btn border border-white/10 text-slate-300 hover:bg-white/5 rounded-xl py-3 font-semibold text-xs transition" id="btnFadeOut">Fade Out</button>
                <button type="button" class="ctrl-btn border border-white/10 text-slate-300 hover:bg-white/5 rounded-xl py-3 font-semibold text-xs transition" id="btnVolDown">Vol -</button>
                <button type="button" class="ctrl-btn border border-white/10 text-slate-300 hover:bg-white/5 rounded-xl py-3 font-semibold text-xs transition" id="btnVolUp">Vol +</button>
                <button type="button" class="ctrl-btn border border-white/10 text-slate-300 hover:bg-white/5 rounded-xl py-3 font-semibold text-xs transition" id="btnToggleAuto">Auto: OFF</button>
                <button type="button" class="ctrl-btn bg-erp-gold/10 hover:bg-erp-gold/25 text-erp-gold border border-erp-gold/20 rounded-xl py-3 font-semibold text-xs transition" id="btnOpenCurrentAlt">Tocar Alt.</button>
            </section>
        </div>
    </main>

    <audio id="audioPlayer" preload="metadata"></audio>
</div>

<!-- Modal de Identificação -->
<div class="fixed inset-0 bg-black/80 z-50 flex items-center justify-center p-4" id="operatorOverlay">
    <div class="bg-slate-900 border border-white/5 rounded-2xl shadow-2xl p-6 max-w-md w-full">
        <h2 class="text-xl font-bold text-white mb-2">Identificação do Operador</h2>
        <p class="text-xs text-slate-400 mb-6">Por favor, registre o nome do irmão que assumirá os controles do player para a sessão.</p>
        
        <input type="text" id="operatorInput" placeholder="Nome completo do irmão em exercício..." class="form-input w-full py-2.5">
        
        <div id="operatorError" class="text-red-400 text-xs mt-2 font-medium"></div>
        <div class="mt-6">
            <button type="button" class="btn btn-primary w-full py-3 font-bold" id="btnSaveOperator">Confirmar & Abrir Harmonia</button>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Utiliza o player_shell para carregar JS e CSS base
require_once __DIR__ . '/../layouts/player_shell.php';
?>
