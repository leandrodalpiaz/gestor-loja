<?php
if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}

$usuarioNome = (string) ($_SESSION['usuario_nome'] ?? 'Irmao');
$operadorEmExercicio = (string) ($operadorEmExercicio ?? '');
$payloadSafe = json_encode($payload ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$basePathValue = (string) ($payload['base_path'] ?? '');
$selectedSessionPath = (string) (($payload['selected_session']['path'] ?? ''));
$operadorSafe = json_encode($operadorEmExercicio, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_QUOT);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mestre de Harmonia - Player Ritual</title>
    <style>
        :root {
            --bg: #0b1218;
            --panel: #122130;
            --panel-soft: #1b3042;
            --line: #28445a;
            --text: #f2ede4;
            --muted: #9cb0c2;
            --gold: #d8b067;
            --accent: #6ea9d7;
            --ok: #79c58b;
            --warn: #8d5f41;
            --danger: #b85b5c;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Segoe UI", "Helvetica Neue", Arial, sans-serif;
            background:
                radial-gradient(circle at 10% 5%, rgba(216, 176, 103, 0.15), transparent 40%),
                radial-gradient(circle at 90% 0%, rgba(110, 169, 215, 0.2), transparent 42%),
                var(--bg);
            color: var(--text);
            min-height: 100vh;
        }

        @media (min-width: 1440px) {
            body {
                font-size: 1.06rem;
            }
        }

        .app {
            height: 100vh;
            display: grid;
            grid-template-rows: auto 1fr auto auto;
            gap: 14px;
            padding: 14px;
        }

        .panel {
            border: 1px solid var(--line);
            background: linear-gradient(180deg, rgba(18, 33, 48, 0.96), rgba(15, 28, 41, 0.96));
            border-radius: 16px;
        }

        .topbar {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 14px;
            align-items: center;
            padding: 14px 18px;
        }

        .title {
            font-size: 44px;
            font-weight: 800;
            letter-spacing: 0.01em;
            margin: 0;
            line-height: 1;
        }

        .subtitle {
            margin: 6px 0 0;
            color: var(--muted);
            font-size: 18px;
        }

        .status-box {
            border: 1px solid var(--line);
            background: var(--panel-soft);
            border-radius: 12px;
            padding: 10px 14px;
            min-width: 360px;
        }

        .status-box strong {
            color: var(--gold);
            display: block;
            font-size: 22px;
            margin-bottom: 4px;
        }

        .config {
            display: grid;
            grid-template-columns: 1.4fr 1fr auto;
            gap: 10px;
            margin-top: 12px;
        }

        .config input,
        .config select {
            border: 1px solid var(--line);
            border-radius: 12px;
            background: #0f1c28;
            color: var(--text);
            padding: 12px 14px;
            font-size: 16px;
        }

        .layout {
            display: grid;
            grid-template-columns: 420px 1fr;
            gap: 14px;
            min-height: 0;
        }

        .playlist-panel {
            display: grid;
            grid-template-rows: auto 1fr auto;
            min-height: 0;
            padding: 12px;
        }

        .playlist-header {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 8px;
        }

        .playlist-header h2 {
            margin: 0;
            font-size: 26px;
        }

        .playlist-scroll {
            overflow: auto;
            padding-right: 4px;
        }

        .step-btn {
            width: 100%;
            text-align: left;
            border: 1px solid transparent;
            border-radius: 12px;
            padding: 10px 12px;
            background: transparent;
            color: var(--text);
            margin-bottom: 6px;
            cursor: pointer;
            display: grid;
            grid-template-columns: 64px 1fr auto;
            gap: 10px;
            align-items: center;
            font-size: 20px;
        }

        .step-btn .code {
            color: var(--gold);
            font-weight: 700;
        }

        .step-btn .kind {
            border-radius: 999px;
            font-size: 12px;
            padding: 3px 9px;
            border: 1px solid var(--line);
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .step-btn.transition {
            background: rgba(67, 110, 141, 0.35);
        }

        .step-btn.extra {
            background: rgba(132, 88, 60, 0.25);
        }

        .step-btn.active {
            background: var(--gold);
            color: #1d1d1d;
            border-color: rgba(255, 255, 255, 0.2);
        }

        .step-btn.active .code,
        .step-btn.active .kind {
            color: #1d1d1d;
            border-color: rgba(0, 0, 0, 0.18);
        }

        .playlist-summary {
            border: 1px solid var(--line);
            background: rgba(66, 97, 124, 0.35);
            border-radius: 10px;
            font-size: 16px;
            color: var(--muted);
            padding: 10px 12px;
            margin-top: 8px;
        }

        .focus-panel {
            display: grid;
            grid-template-rows: auto auto 1fr;
            gap: 14px;
            min-height: 0;
        }

        .current {
            padding: 16px 18px;
        }

        .current h2 {
            margin: 0;
            font-size: 58px;
            line-height: 1.02;
            letter-spacing: 0.01em;
        }

        .track-title {
            margin: 8px 0;
            font-size: 42px;
            font-weight: 700;
        }

        .meta-line {
            display: flex;
            gap: 28px;
            flex-wrap: wrap;
            color: var(--muted);
            font-size: 24px;
            margin-top: 8px;
        }

        .meta-line strong { color: var(--gold); }

        .progress-wrap {
            margin-top: 12px;
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 10px;
            align-items: center;
        }

        .progress {
            height: 20px;
            background: #28465e;
            border-radius: 999px;
            overflow: hidden;
            border: 1px solid var(--line);
        }

        .bar {
            height: 100%;
            background: linear-gradient(90deg, #d6ab5f, #efcc83);
            width: 0%;
        }

        .mini-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            min-height: 0;
        }

        .mini-box {
            padding: 14px 16px;
            min-height: 200px;
            display: grid;
            grid-template-rows: auto auto 1fr auto;
            gap: 8px;
        }

        .mini-box h3 {
            margin: 0;
            font-size: 30px;
        }

        .mini-primary {
            font-size: 38px;
            font-weight: 800;
        }

        .list {
            overflow: auto;
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 8px;
            background: rgba(14, 26, 38, 0.65);
        }

        .list-item {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 8px 10px;
            border-radius: 9px;
            font-size: 18px;
            color: var(--muted);
        }

        .list-item + .list-item { margin-top: 5px; }
        .list-item strong { color: var(--text); }

        .control-row {
            display: grid;
            gap: 10px;
            padding: 10px;
            align-items: center;
        }

        .control-row.main {
            grid-template-columns: repeat(7, minmax(120px, 1fr));
        }

        .control-row.support {
            grid-template-columns: repeat(6, minmax(120px, 1fr));
        }

        .ctrl-btn {
            border: 1px solid var(--line);
            border-radius: 14px;
            background: #23364a;
            color: var(--text);
            font-size: 28px;
            font-weight: 700;
            padding: 14px 8px;
            cursor: pointer;
            min-height: 76px;
        }

        .ctrl-btn:hover { filter: brightness(1.08); }
        .ctrl-btn:disabled { opacity: 0.45; cursor: not-allowed; }
        .ctrl-btn.primary { background: #2f4c39; }
        .ctrl-btn.danger { background: #5b2f34; }
        .ctrl-btn.warn { background: #573d2d; }

        .footer {
            font-size: 18px;
            color: var(--muted);
            padding: 6px 4px;
        }

        .operator-line {
            margin-top: 8px;
            font-size: 18px;
            color: var(--muted);
        }

        .operator-line strong {
            color: var(--gold);
        }

        .operator-line button {
            margin-left: 12px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: #223447;
            color: var(--text);
            padding: 6px 10px;
            cursor: pointer;
            font-size: 14px;
        }

        .operator-overlay {
            position: fixed;
            inset: 0;
            background: rgba(5, 10, 14, 0.82);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .operator-modal {
            width: min(640px, calc(100vw - 40px));
            border: 1px solid var(--line);
            background: #122130;
            border-radius: 16px;
            padding: 20px;
        }

        .operator-modal h2 {
            margin: 0 0 8px;
            font-size: 34px;
        }

        .operator-modal p {
            margin: 0 0 16px;
            color: var(--muted);
            font-size: 18px;
            line-height: 1.5;
        }

        .operator-modal input {
            width: 100%;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: #0f1c28;
            color: var(--text);
            padding: 12px 14px;
            font-size: 20px;
        }

        .operator-modal .actions {
            margin-top: 14px;
            display: flex;
            gap: 10px;
        }

        .operator-modal .actions button {
            border: 1px solid var(--line);
            border-radius: 12px;
            font-size: 20px;
            padding: 10px 16px;
            color: var(--text);
            background: #223447;
            cursor: pointer;
        }

        .operator-modal .actions .primary {
            background: #2f4c39;
        }

        .operator-modal .error {
            min-height: 20px;
            margin-top: 10px;
            color: #f1a6a6;
            font-size: 15px;
        }

        @media (max-width: 1360px) {
            .layout { grid-template-columns: 360px 1fr; }
            .title { font-size: 36px; }
            .current h2 { font-size: 46px; }
            .track-title { font-size: 34px; }
            .ctrl-btn { font-size: 22px; min-height: 68px; }
        }
    </style>
</head>
<body>
<div class="app">
    <header class="panel topbar">
        <div>
            <h1 class="title">MESTRE DE HARMONIA</h1>
            <p class="subtitle">Responsavel: <?= htmlspecialchars($usuarioNome) ?> | Conducao musical da sessao em tela cheia</p>
            <div class="operator-line">Irmao em exercicio: <strong id="operatorNameDisplay">Nao informado</strong><button type="button" id="btnChangeOperator">Alterar</button></div>
        </div>
        <div class="status-box">
            <strong id="sessionLabel">Sessao nao carregada</strong>
            <span id="globalStatus">Aguardando a escolha da sessao</span>
            <div style="margin-top:8px;">
                <a href="/miniapp/mestre-harmonia" style="display:inline-block;border:1px solid var(--line);border-radius:8px;padding:6px 10px;color:var(--text);text-decoration:none;background:#223447;font-size:14px;">Abrir miniapp do cargo</a>
            </div>
        </div>
    </header>

    <form class="panel" method="get" action="/mestre-harmonia" style="padding: 10px 12px;">
        <div class="config">
            <input type="text" name="base_path" value="<?= htmlspecialchars($basePathValue) ?>" placeholder="Pasta base das playlists">
            <select name="sessao_path" id="sessionPicker"></select>
            <button class="ctrl-btn primary" type="submit" style="font-size: 20px; min-height: 0; padding: 10px 16px;">Recarregar</button>
        </div>
    </form>

    <section class="layout">
        <aside class="panel playlist-panel">
            <div class="playlist-header">
                <h2>Roteiro da sessao</h2>
                <span id="playlistCount" style="color: var(--muted); font-size: 18px;">0 etapas</span>
            </div>
            <div class="playlist-scroll" id="stepsList"></div>
            <div class="playlist-summary" id="summaryText">Principais 0 | Transicao 0 | Extras 0</div>
        </aside>

        <div class="focus-panel">
            <article class="panel current">
                <h2 id="currentStepCode">--</h2>
                <div class="track-title" id="currentTrackTitle">Selecione uma etapa para comecar</div>
                <div class="meta-line">
                    <span>Tipo: <strong id="currentType">-</strong></span>
                    <span>Status: <strong id="playerStatus">Aguardando</strong></span>
                    <span>Fade: <strong id="fadeStatus">Pronto</strong></span>
                    <span>Volume: <strong id="volumeStatus">100%</strong></span>
                </div>
                <div class="progress-wrap">
                    <div class="progress"><div class="bar" id="progressBar"></div></div>
                    <strong id="timeText" style="font-size: 30px;">00:00 / 00:00</strong>
                </div>
            </article>

            <section class="mini-grid">
                <article class="panel mini-box">
                    <h3>Proxima etapa</h3>
                    <div class="mini-primary" id="nextStepCode">--</div>
                    <div id="nextStepTitle" style="font-size: 28px; color: var(--muted);">Sem proxima etapa</div>
                    <div class="footer">Use "Proxima etapa" para seguir a sessao com tranquilidade.</div>
                </article>

                <article class="panel mini-box">
                    <h3>Apoio Ritual</h3>
                    <div style="font-size: 24px; color: var(--muted);">Alternativas da etapa atual</div>
                    <div class="list" id="alternativesList"></div>
                    <div class="footer">"Apos", "Conferencia" e "Espera" entram como transicao reconhecida.</div>
                </article>
            </section>
        </div>
    </section>

    <section class="panel control-row main">
        <button type="button" class="ctrl-btn primary" id="btnStart">Iniciar</button>
        <button type="button" class="ctrl-btn" id="btnPause">Pausar</button>
        <button type="button" class="ctrl-btn danger" id="btnStop">Parar</button>
        <button type="button" class="ctrl-btn" id="btnRestart">Reiniciar</button>
        <button type="button" class="ctrl-btn" id="btnPrev">Etapa Anterior</button>
        <button type="button" class="ctrl-btn primary" id="btnNext">Proxima Etapa</button>
        <button type="button" class="ctrl-btn warn" id="btnSilence">Silencio</button>
    </section>

    <section class="panel control-row support">
        <button type="button" class="ctrl-btn" id="btnFadeIn">Fade In</button>
        <button type="button" class="ctrl-btn" id="btnFadeOut">Fade Out</button>
        <button type="button" class="ctrl-btn" id="btnVolDown">Volume -</button>
        <button type="button" class="ctrl-btn" id="btnVolUp">Volume +</button>
        <button type="button" class="ctrl-btn" id="btnToggleAuto">Proxima automatica: OFF</button>
        <button type="button" class="ctrl-btn" id="btnOpenCurrentAlt">Tocar Alternativa</button>
    </section>

    <audio id="audioPlayer" preload="metadata"></audio>
</div>

<div class="operator-overlay" id="operatorOverlay">
    <div class="operator-modal">
        <h2>Identificacao da sessao</h2>
        <p>Informe o nome do irmao que esta exercendo a funcao nesta sessao. Esse dado fica registrado para apoio posterior.</p>
        <input type="text" id="operatorInput" placeholder="Nome do irmao em exercicio">
        <div class="actions">
            <button type="button" class="primary" id="btnSaveOperator">Confirmar e abrir player</button>
        </div>
        <div class="error" id="operatorError"></div>
    </div>
</div>

<script>
    const payload = <?= $payloadSafe ?: '{}' ?>;
    const sessions = Array.isArray(payload.sessions) ? payload.sessions : [];
    let selectedPath = <?= json_encode($selectedSessionPath, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_QUOT) ?> || '';
    let currentSession = sessions.find(s => s.path === selectedPath) || sessions[0] || null;
    let tracks = currentSession && Array.isArray(currentSession.tracks) ? currentSession.tracks : [];
    let currentIndex = tracks.length > 0 ? 0 : -1;
    let autoNext = false;
    let fadeTimer = null;
    let currentOperator = <?= $operadorSafe ?: '""' ?> || '';

    const el = {
        sessionLabel: document.getElementById('sessionLabel'),
        globalStatus: document.getElementById('globalStatus'),
        sessionPicker: document.getElementById('sessionPicker'),
        playlistCount: document.getElementById('playlistCount'),
        stepsList: document.getElementById('stepsList'),
        summaryText: document.getElementById('summaryText'),
        currentStepCode: document.getElementById('currentStepCode'),
        currentTrackTitle: document.getElementById('currentTrackTitle'),
        currentType: document.getElementById('currentType'),
        playerStatus: document.getElementById('playerStatus'),
        fadeStatus: document.getElementById('fadeStatus'),
        volumeStatus: document.getElementById('volumeStatus'),
        progressBar: document.getElementById('progressBar'),
        timeText: document.getElementById('timeText'),
        nextStepCode: document.getElementById('nextStepCode'),
        nextStepTitle: document.getElementById('nextStepTitle'),
        alternativesList: document.getElementById('alternativesList'),
        audio: document.getElementById('audioPlayer'),
        btnStart: document.getElementById('btnStart'),
        btnPause: document.getElementById('btnPause'),
        btnStop: document.getElementById('btnStop'),
        btnRestart: document.getElementById('btnRestart'),
        btnPrev: document.getElementById('btnPrev'),
        btnNext: document.getElementById('btnNext'),
        btnSilence: document.getElementById('btnSilence'),
        btnFadeIn: document.getElementById('btnFadeIn'),
        btnFadeOut: document.getElementById('btnFadeOut'),
        btnVolDown: document.getElementById('btnVolDown'),
        btnVolUp: document.getElementById('btnVolUp'),
        btnToggleAuto: document.getElementById('btnToggleAuto'),
        btnOpenCurrentAlt: document.getElementById('btnOpenCurrentAlt'),
        operatorNameDisplay: document.getElementById('operatorNameDisplay'),
        btnChangeOperator: document.getElementById('btnChangeOperator'),
        operatorOverlay: document.getElementById('operatorOverlay'),
        operatorInput: document.getElementById('operatorInput'),
        btnSaveOperator: document.getElementById('btnSaveOperator'),
        operatorError: document.getElementById('operatorError'),
    };

    function formatTime(totalSeconds) {
        if (!Number.isFinite(totalSeconds) || totalSeconds < 0) return '00:00';
        const min = Math.floor(totalSeconds / 60);
        const sec = Math.floor(totalSeconds % 60);
        return `${String(min).padStart(2, '0')}:${String(sec).padStart(2, '0')}`;
    }

    function kindLabel(type) {
        if (type === 'transicao') return 'Transicao';
        if (type === 'extra') return 'Extra';
        return 'Principal';
    }

    function resolveTrackUrl(track) {
        return `/api/mestre-harmonia/audio?id=${encodeURIComponent(track.id)}`;
    }

    function clearFadeTimer() {
        if (fadeTimer) {
            clearInterval(fadeTimer);
            fadeTimer = null;
        }
    }

    function stepFade(target, durationMs) {
        clearFadeTimer();
        const audio = el.audio;
        const start = audio.volume;
        const diff = target - start;
        const steps = Math.max(1, Math.floor(durationMs / 60));
        let tick = 0;
        el.fadeStatus.textContent = target > start ? 'Fade In em curso' : 'Fade Out em curso';

        fadeTimer = setInterval(() => {
            tick++;
            const progress = Math.min(1, tick / steps);
            audio.volume = Math.max(0, Math.min(1, start + (diff * progress)));
            el.volumeStatus.textContent = `${Math.round(audio.volume * 100)}%`;
            if (progress >= 1) {
                clearFadeTimer();
                el.fadeStatus.textContent = 'Pronto';
                if (target === 0 && !audio.paused) {
                    audio.pause();
                }
            }
        }, 60);
    }

    function applyCurrentTrack(loadOnly = true) {
        if (currentIndex < 0 || !tracks[currentIndex]) {
            el.currentStepCode.textContent = '--';
            el.currentTrackTitle.textContent = 'Nenhuma faixa carregada';
            el.currentType.textContent = '-';
            el.nextStepCode.textContent = '--';
            el.nextStepTitle.textContent = 'Sem proxima etapa';
            el.alternativesList.innerHTML = '<div class="list-item"><span>Nenhuma alternativa.</span></div>';
            return;
        }

        const current = tracks[currentIndex];
        const next = tracks[currentIndex + 1] || null;
        el.currentStepCode.textContent = `${current.code || '--'} - ${current.phase || 'Etapa livre'}`;
        el.currentTrackTitle.textContent = current.title || current.filename;
        el.currentType.textContent = kindLabel(current.type);
        el.nextStepCode.textContent = next ? `${next.code || '--'} - ${next.phase || 'Etapa'}` : '--';
        el.nextStepTitle.textContent = next ? (next.title || next.filename) : 'Sem proxima etapa';

        if (loadOnly) {
            el.audio.src = resolveTrackUrl(current);
            el.audio.load();
            el.playerStatus.textContent = 'Pronto';
        }

        renderAlternatives();
        renderSteps();
    }

    function setControlsEnabled(enabled) {
        const controls = [
            el.btnStart, el.btnPause, el.btnStop, el.btnRestart, el.btnPrev, el.btnNext,
            el.btnSilence, el.btnFadeIn, el.btnFadeOut, el.btnVolDown, el.btnVolUp,
            el.btnToggleAuto, el.btnOpenCurrentAlt
        ];
        controls.forEach((button) => {
            if (button) button.disabled = !enabled;
        });
    }

    function updateOperatorDisplay() {
        el.operatorNameDisplay.textContent = currentOperator && currentOperator.trim() !== '' ? currentOperator : 'Nao informado';
    }

    async function saveOperator(name) {
        const response = await fetch('/api/mestre-harmonia/operador', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nome: name })
        });

        const json = await response.json().catch(() => ({ ok: false, erro: 'Falha ao salvar operador.' }));
        if (!response.ok || json.ok !== true) {
            throw new Error(json.erro || 'Falha ao salvar operador.');
        }

        currentOperator = String(json.operador || name).trim();
        updateOperatorDisplay();
        setControlsEnabled(true);
        el.operatorOverlay.style.display = 'none';
    }

    function openOperatorModal() {
        el.operatorError.textContent = '';
        el.operatorInput.value = currentOperator || '';
        el.operatorOverlay.style.display = 'flex';
        setControlsEnabled(false);
        setTimeout(() => el.operatorInput.focus(), 20);
    }

    function renderAlternatives() {
        if (currentIndex < 0 || !tracks[currentIndex]) {
            el.alternativesList.innerHTML = '<div class="list-item"><span>Nenhuma alternativa.</span></div>';
            return;
        }

        const current = tracks[currentIndex];
        const options = tracks.filter((item, index) =>
            index !== currentIndex &&
            item.stage_key === current.stage_key
        );

        if (options.length === 0) {
            el.alternativesList.innerHTML = '<div class="list-item"><span>Nenhuma alternativa nesta etapa.</span></div>';
            return;
        }

        el.alternativesList.innerHTML = '';
        options.slice(0, 12).forEach((item) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'list-item';
            btn.style.width = '100%';
            btn.style.textAlign = 'left';
            btn.style.border = '0';
            btn.style.background = 'rgba(35,57,75,0.48)';
            btn.style.cursor = 'pointer';
            btn.innerHTML = `<strong>${item.code || '--'}</strong><span>${item.title}</span>`;
            btn.addEventListener('click', () => {
                const idx = tracks.findIndex(track => track.id === item.id);
                if (idx >= 0) {
                    currentIndex = idx;
                    applyCurrentTrack(true);
                    startPlayback();
                }
            });
            el.alternativesList.appendChild(btn);
        });
    }

    function renderSteps() {
        el.stepsList.innerHTML = '';
        tracks.forEach((step, index) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = `step-btn ${step.type === 'transicao' ? 'transition' : ''} ${step.type === 'extra' ? 'extra' : ''} ${index === currentIndex ? 'active' : ''}`;
            btn.innerHTML = `
                <span class="code">${step.code || '--'}</span>
                <span>${step.phase || 'Etapa livre'}</span>
                <span class="kind">${kindLabel(step.type)}</span>
            `;
            btn.addEventListener('click', () => {
                currentIndex = index;
                applyCurrentTrack(true);
            });
            el.stepsList.appendChild(btn);
        });
    }

    function startPlayback(withFade = false) {
        if (currentIndex < 0 || !tracks[currentIndex]) return;
        if (withFade) {
            el.audio.volume = 0;
            el.volumeStatus.textContent = '0%';
            el.audio.play().catch(() => null);
            stepFade(1, 1700);
            return;
        }
        el.audio.play().catch(() => null);
    }

    function pauseOrResume() {
        if (el.audio.paused) {
            el.audio.play().catch(() => null);
        } else {
            el.audio.pause();
        }
    }

    function stopPlayback() {
        clearFadeTimer();
        el.audio.pause();
        el.audio.currentTime = 0;
        el.playerStatus.textContent = 'Parado';
        el.fadeStatus.textContent = 'Pronto';
    }

    function moveStep(offset, autoPlay = false) {
        const target = currentIndex + offset;
        if (target < 0 || target >= tracks.length) return;
        currentIndex = target;
        applyCurrentTrack(true);
        if (autoPlay) startPlayback();
    }

    function renderSessionPicker() {
        el.sessionPicker.innerHTML = '';
        sessions.forEach((session) => {
            const option = document.createElement('option');
            option.value = session.path;
            option.textContent = `${session.name} (${session.total_tracks} faixas)`;
            if (currentSession && session.path === currentSession.path) {
                option.selected = true;
            }
            el.sessionPicker.appendChild(option);
        });
    }

    function updateHeader() {
        if (!currentSession) {
            el.sessionLabel.textContent = 'Sessao nao carregada';
            el.globalStatus.textContent = payload.erro || 'Nenhuma informacao disponivel no momento';
            return;
        }
        el.sessionLabel.textContent = currentSession.name;
        el.globalStatus.textContent = `${tracks.length} faixas disponiveis | pasta: ${payload.base_path || '-'}`;
    }

    function updateSummary() {
        const summary = currentSession && currentSession.summary ? currentSession.summary : { principal: 0, transicao: 0, extra: 0 };
        el.summaryText.textContent = `Principais ${summary.principal || 0} | Transicao ${summary.transicao || 0} | Extras ${summary.extra || 0}`;
        el.playlistCount.textContent = `${tracks.length} etapas`;
    }

    function bindEvents() {
        el.audio.volume = 1;
        el.volumeStatus.textContent = '100%';

        el.audio.addEventListener('loadedmetadata', () => {
            el.timeText.textContent = `00:00 / ${formatTime(el.audio.duration)}`;
        });

        el.audio.addEventListener('timeupdate', () => {
            const duration = Number.isFinite(el.audio.duration) ? el.audio.duration : 0;
            const current = Number.isFinite(el.audio.currentTime) ? el.audio.currentTime : 0;
            const percent = duration > 0 ? (current / duration) * 100 : 0;
            el.progressBar.style.width = `${Math.max(0, Math.min(100, percent))}%`;
            el.timeText.textContent = `${formatTime(current)} / ${formatTime(duration)}`;
        });

        el.audio.addEventListener('play', () => { el.playerStatus.textContent = 'Tocando'; });
        el.audio.addEventListener('pause', () => {
            if (el.audio.currentTime > 0 && el.audio.currentTime < (el.audio.duration || Infinity)) {
                el.playerStatus.textContent = 'Pausado';
            }
        });
        el.audio.addEventListener('ended', () => {
            el.playerStatus.textContent = 'Concluido';
            if (autoNext) {
                moveStep(1, true);
            }
        });

        el.btnStart.addEventListener('click', () => startPlayback(false));
        el.btnPause.addEventListener('click', pauseOrResume);
        el.btnStop.addEventListener('click', stopPlayback);
        el.btnRestart.addEventListener('click', () => {
            el.audio.currentTime = 0;
            startPlayback(false);
        });
        el.btnPrev.addEventListener('click', () => moveStep(-1, false));
        el.btnNext.addEventListener('click', () => moveStep(1, false));
        el.btnSilence.addEventListener('click', () => {
            clearFadeTimer();
            el.audio.volume = 0;
            el.volumeStatus.textContent = '0%';
            el.fadeStatus.textContent = 'Silencio imediato';
            if (!el.audio.paused) {
                el.audio.pause();
            }
        });
        el.btnFadeIn.addEventListener('click', () => {
            if (el.audio.paused) {
                el.audio.volume = 0;
                startPlayback(false);
            }
            stepFade(1, 1800);
        });
        el.btnFadeOut.addEventListener('click', () => stepFade(0, 1800));
        el.btnVolDown.addEventListener('click', () => {
            clearFadeTimer();
            el.audio.volume = Math.max(0, Number((el.audio.volume - 0.1).toFixed(2)));
            el.volumeStatus.textContent = `${Math.round(el.audio.volume * 100)}%`;
        });
        el.btnVolUp.addEventListener('click', () => {
            clearFadeTimer();
            el.audio.volume = Math.min(1, Number((el.audio.volume + 0.1).toFixed(2)));
            el.volumeStatus.textContent = `${Math.round(el.audio.volume * 100)}%`;
        });
        el.btnToggleAuto.addEventListener('click', () => {
            autoNext = !autoNext;
            el.btnToggleAuto.textContent = `Proxima automatica: ${autoNext ? 'ON' : 'OFF'}`;
        });
        el.btnOpenCurrentAlt.addEventListener('click', () => {
            const current = tracks[currentIndex];
            if (!current) return;
            const idx = tracks.findIndex((item, index) =>
                index !== currentIndex &&
                item.stage_key === current.stage_key
            );
            if (idx >= 0) {
                currentIndex = idx;
                applyCurrentTrack(true);
                startPlayback(false);
            }
        });

        el.btnChangeOperator.addEventListener('click', openOperatorModal);
        el.btnSaveOperator.addEventListener('click', async () => {
            const name = (el.operatorInput.value || '').trim();
            if (name === '') {
                el.operatorError.textContent = 'Informe o nome para continuar.';
                return;
            }
            el.operatorError.textContent = '';
            el.btnSaveOperator.disabled = true;
            try {
                await saveOperator(name);
            } catch (error) {
                el.operatorError.textContent = error instanceof Error ? error.message : 'Falha ao salvar.';
            } finally {
                el.btnSaveOperator.disabled = false;
            }
        });
    }

    function init() {
        renderSessionPicker();
        updateHeader();
        updateSummary();
        applyCurrentTrack(true);
        bindEvents();
        updateOperatorDisplay();

        if (!currentOperator || currentOperator.trim() === '') {
            openOperatorModal();
        } else {
            setControlsEnabled(true);
        }

        if (payload.ok !== true) {
            el.globalStatus.textContent = payload.erro || 'Nao foi possivel carregar a playlist.';
        }
    }

    init();
</script>
</body>
</html>
