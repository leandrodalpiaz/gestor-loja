document.addEventListener('DOMContentLoaded', () => {
    const payload = window.harmoniaPayload || {};
    const sessions = Array.isArray(payload.sessions) ? payload.sessions : [];
    let selectedPath = window.harmoniaSelectedPath || '';
    let currentSession = sessions.find(s => s.path === selectedPath) || sessions[0] || null;
    let tracks = currentSession && Array.isArray(currentSession.tracks) ? currentSession.tracks : [];
    let currentIndex = tracks.length > 0 ? 0 : -1;
    let autoNext = false;
    let fadeTimer = null;
    let currentOperator = window.harmoniaOperator || '';
    let preferredVolume = 1;

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
        if (type === 'transicao') return 'Transição';
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

    function setPlayerVolume(volume, remember = true) {
        const normalized = Math.max(0, Math.min(1, Number(volume)));
        el.audio.volume = normalized;
        el.volumeStatus.textContent = `${Math.round(normalized * 100)}%`;
        if (remember && normalized > 0) {
            preferredVolume = normalized;
        }
    }

    function restoreAudibleVolume() {
        if (el.audio.volume <= 0) {
            setPlayerVolume(preferredVolume > 0 ? preferredVolume : 1, false);
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
            setPlayerVolume(Math.max(0, Math.min(1, start + (diff * progress))), target > 0);
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
            el.nextStepTitle.textContent = 'Sem próxima etapa';
            el.alternativesList.innerHTML = '<div class="list-item"><span>Nenhuma alternativa.</span></div>';
            return;
        }

        const current = tracks[currentIndex];
        const next = tracks[currentIndex + 1] || null;
        el.currentStepCode.textContent = `${current.code || '--'} - ${current.phase || 'Etapa livre'}`;
        el.currentTrackTitle.textContent = current.title || current.filename;
        el.currentType.textContent = kindLabel(current.type);
        el.nextStepCode.textContent = next ? `${next.code || '--'} - ${next.phase || 'Etapa'}` : '--';
        el.nextStepTitle.textContent = next ? (next.title || next.filename) : 'Sem próxima etapa';

        if (loadOnly) {
            el.audio.src = resolveTrackUrl(current);
            el.audio.load();
            restoreAudibleVolume();
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
        el.operatorNameDisplay.textContent = currentOperator && currentOperator.trim() !== '' ? currentOperator : 'Não informado';
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
            setPlayerVolume(0, false);
            el.audio.play().catch(() => null);
            stepFade(preferredVolume > 0 ? preferredVolume : 1, 1700);
            return;
        }
        restoreAudibleVolume();
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
            el.sessionLabel.textContent = 'Sessão não carregada';
            el.globalStatus.textContent = payload.erro || 'Nenhuma informação disponível no momento';
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
        if (!el.audio) return;

        setPlayerVolume(1, true);

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
            setPlayerVolume(0, false);
            el.fadeStatus.textContent = 'Silêncio imediato';
            if (!el.audio.paused) {
                el.audio.pause();
            }
        });
        el.btnFadeIn.addEventListener('click', () => {
            if (el.audio.paused) {
                setPlayerVolume(0, false);
                startPlayback(false);
            }
            stepFade(preferredVolume > 0 ? preferredVolume : 1, 1800);
        });
        el.btnFadeOut.addEventListener('click', () => stepFade(0, 1800));
        el.btnVolDown.addEventListener('click', () => {
            clearFadeTimer();
            setPlayerVolume(Math.max(0, Number((el.audio.volume - 0.1).toFixed(2))), true);
        });
        el.btnVolUp.addEventListener('click', () => {
            clearFadeTimer();
            setPlayerVolume(Math.min(1, Number((el.audio.volume + 0.1).toFixed(2))), true);
        });
        el.btnToggleAuto.addEventListener('click', () => {
            autoNext = !autoNext;
            el.btnToggleAuto.textContent = `Próxima automática: ${autoNext ? 'ON' : 'OFF'}`;
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
            el.globalStatus.textContent = payload.erro || 'Não foi possível carregar a playlist.';
        }
    }

    init();
});
