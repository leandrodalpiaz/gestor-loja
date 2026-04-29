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
<div class="min-h-screen bg-gray-900 text-gray-200 font-sans p-4 lg:p-6 flex flex-col" id="app-container">

    <!-- Cabeçalho -->
    <header class="bg-gray-800/50 rounded-xl p-4 lg:p-5 flex flex-wrap justify-between items-center gap-4 border border-gray-700/50">
        <div>
            <h1 class="text-xl lg:text-2xl font-bold text-white">MESTRE DE HARMONIA</h1>
            <p class="text-sm text-gray-400">Responsável: <?= htmlspecialchars($usuarioNome) ?></p>
            <div class="text-sm text-gray-400 mt-1">Irmão em exercício: <strong id="operatorNameDisplay" class="text-white">Não informado</strong> <button type="button" id="btnChangeOperator" class="ml-2 text-blue-400 hover:text-blue-300 text-xs font-semibold">[Alterar]</button></div>
        </div>
        <div class="text-right">
            <div id="sessionLabel" class="font-semibold text-lg text-white">Sessão não carregada</div>
            <div id="globalStatus" class="text-sm text-yellow-400">Aguardando a escolha da sessão</div>
            <a href="/miniapp/mestre-harmonia" target="_blank" class="mt-2 inline-block bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold py-2 px-3 rounded-lg">
                Abrir MiniApp
            </a>
        </div>
    </header>

    <!-- Configuração -->
    <form class="bg-gray-800/50 rounded-xl p-3 my-4 border border-gray-700/50" method="get" action="/mestre-harmonia">
        <div class="flex flex-wrap items-center gap-3">
            <input type="text" name="base_path" value="<?= htmlspecialchars($basePathValue) ?>" placeholder="Pasta base das playlists" class="flex-grow bg-gray-900 border border-gray-600 rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
            <select name="sessao_path" id="sessionPicker" class="bg-gray-900 border border-gray-600 rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500"></select>
            <button class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg" type="submit">Recarregar</button>
        </div>
    </form>

    <!-- Layout Principal -->
    <main class="flex-grow grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Playlist -->
        <aside class="lg:col-span-1 bg-gray-800/50 rounded-xl p-4 flex flex-col border border-gray-700/50">
            <div class="flex justify-between items-center mb-3">
                <h2 class="text-lg font-semibold text-white">Roteiro da sessão</h2>
                <span id="playlistCount" class="text-gray-400 text-sm">0 etapas</span>
            </div>
            <div class="flex-grow overflow-y-auto pr-2" id="stepsList">
                <!-- Itens da playlist serão inseridos aqui via JS -->
            </div>
            <div id="summaryText" class="mt-3 text-xs text-gray-500 border-t border-gray-700 pt-2">Principais 0 | Transicao 0 | Extras 0</div>
        </aside>

        <!-- Painel Central -->
        <div class="lg:col-span-2 flex flex-col gap-4">
            <!-- Player Principal -->
            <article class="bg-gray-800/50 rounded-xl p-5 border border-gray-700/50">
                <div class="flex justify-between items-start">
                    <h2 id="currentStepCode" class="text-2xl font-bold text-blue-400">--</h2>
                    <div id="playerStatus" class="px-3 py-1 text-sm font-semibold rounded-full bg-yellow-500/20 text-yellow-300">Aguardando</div>
                </div>
                <div id="currentTrackTitle" class="text-2xl lg:text-4xl font-semibold text-white mt-2 mb-4 truncate">Selecione uma etapa para comecar</div>
                <div class="flex items-center justify-between text-gray-400 text-sm mb-3">
                    <span>Tipo: <strong id="currentType" class="text-gray-200">-</strong></span>
                    <span>Fade: <strong id="fadeStatus" class="text-gray-200">Pronto</strong></span>
                    <span>Volume: <strong id="volumeStatus" class="text-gray-200">100%</strong></span>
                </div>
                <div class="flex items-center gap-4">
                    <div class="w-full bg-gray-700 rounded-full h-2.5">
                        <div id="progressBar" class="bg-blue-500 h-2.5 rounded-full" style="width: 0%"></div>
                    </div>
                    <strong id="timeText" class="text-xl font-mono text-white">00:00/00:00</strong>
                </div>
            </article>

            <!-- Controles Principais -->
            <section class="bg-gray-800/50 rounded-xl p-4 grid grid-cols-3 sm:grid-cols-4 md:grid-cols-7 gap-3 border border-gray-700/50">
                <button type="button" class="ctrl-btn bg-green-600 hover:bg-green-700 text-white" id="btnStart">Iniciar</button>
                <button type="button" class="ctrl-btn bg-yellow-600 hover:bg-yellow-700 text-white" id="btnPause">Pausar</button>
                <button type="button" class="ctrl-btn bg-red-700 hover:bg-red-800 text-white" id="btnStop">Parar</button>
                <button type="button" class="ctrl-btn" id="btnRestart">Reiniciar</button>
                <button type="button" class="ctrl-btn" id="btnPrev">Anterior</button>
                <button type="button" class="ctrl-btn bg-blue-600 hover:bg-blue-700 text-white col-span-1 md:col-span-1" id="btnNext">Próxima</button>
                <button type="button" class="ctrl-btn bg-purple-600 hover:bg-purple-700 text-white" id="btnSilence">Silêncio</button>
            </section>

            <!-- Próxima Etapa e Apoio -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <article class="bg-gray-800/50 rounded-xl p-4 border border-gray-700/50">
                    <h3 class="font-semibold text-white">Próxima etapa</h3>
                    <div id="nextStepCode" class="text-blue-400 font-bold text-xl mt-1">--</div>
                    <div id="nextStepTitle" class="text-gray-300 mt-1 truncate">Sem próxima etapa</div>
                </article>
                <article class="bg-gray-800/50 rounded-xl p-4 border border-gray-700/50">
                    <h3 class="font-semibold text-white">Apoio Ritual (Alternativas)</h3>
                    <div id="alternativesList" class="text-sm text-gray-400 mt-2 space-y-1"></div>
                </article>
            </div>

            <!-- Controles de Apoio -->
            <section class="bg-gray-800/50 rounded-xl p-4 grid grid-cols-3 sm:grid-cols-6 gap-3 border border-gray-700/50">
                <button type="button" class="ctrl-btn" id="btnFadeIn">Fade In</button>
                <button type="button" class="ctrl-btn" id="btnFadeOut">Fade Out</button>
                <button type="button" class="ctrl-btn" id="btnVolDown">Vol -</button>
                <button type="button" class="ctrl-btn" id="btnVolUp">Vol +</button>
                <button type="button" class="ctrl-btn" id="btnToggleAuto">Auto: OFF</button>
                <button type="button" class="ctrl-btn" id="btnOpenCurrentAlt">Tocar Alt.</button>
            </section>
        </div>
    </main>

    <audio id="audioPlayer" preload="metadata"></audio>
</div>

<!-- Modal de Identificação -->
<div class="fixed inset-0 bg-black/80 z-50 flex items-center justify-center p-4" id="operatorOverlay">
    <div class="bg-gray-800 rounded-2xl shadow-2xl p-8 max-w-md w-full border border-gray-700">
        <h2 class="text-2xl font-bold text-white mb-2">Identificação da Sessão</h2>
        <p class="text-gray-400 mb-6">Informe o nome do irmão que está exercendo a função. Este dado é salvo para referência futura.</p>
        <input type="text" id="operatorInput" placeholder="Nome do irmão em exercício" class="w-full bg-gray-900 border border-gray-600 rounded-lg px-4 py-3 text-white focus:ring-blue-500 focus:border-blue-500">
        <div id="operatorError" class="text-red-400 text-sm mt-2"></div>
        <div class="mt-6">
            <button type="button" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg" id="btnSaveOperator">Confirmar e Abrir Player</button>
        </div>
    </div>
</div>

<style>
    .ctrl-btn {
        @apply w-full h-full px-3 py-3 rounded-lg text-xs font-bold text-gray-200 bg-gray-700/60 hover:bg-gray-600/80 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-gray-900;
    }
    #stepsList .step-item {
        @apply p-3 rounded-lg cursor-pointer border-2 border-transparent transition-all;
    }
    #stepsList .step-item:hover {
        @apply bg-gray-700/50;
    }
    #stepsList .step-item.active {
        @apply bg-blue-600/30 border-blue-500 text-white;
    }
    #stepsList .step-item.played {
        @apply opacity-50;
    }
    #stepsList .step-code { @apply font-bold text-blue-400 text-sm; }
    #stepsList .step-title { @apply font-semibold; }
    #stepsList .step-type { @apply text-xs text-gray-400; }

    #alternativesList .alt-item {
        @apply p-2 rounded-md hover:bg-gray-700/50 cursor-pointer flex justify-between items-center;
    }
</style>

<?php
$content = ob_get_clean();

// Utiliza o player_shell, mas agora o estilo principal vem do Tailwind embutido.
require_once __DIR__ . '/../layouts/player_shell.php';
?>
