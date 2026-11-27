(function () {
    'use strict';

    var root = document.getElementById('local-studytimer-root');
    if (!root) {
        return;
    }

    var userid = root.getAttribute('data-userid') || '0';
    var STORAGE_KEY = 'local_studytimer_state_u' + userid;
    var PACING_SETTINGS_KEY = 'local_studytimer_pacing_settings_u' + userid;
    var PACING_SESSION_KEY = 'local_studytimer_pacing_session_u' + userid;

    var MIN_WIDTH = 290;
    var MAX_WIDTH = 460;
    var DEFAULT_WIDTH = 330;

    var strings = {
        start: root.getAttribute('data-str-start') || 'Start',
        pause: root.getAttribute('data-str-pause') || 'Pause',
        resume: root.getAttribute('data-str-resume') || 'Resume',
        ready: root.getAttribute('data-str-ready') || 'Ready',
        studying: root.getAttribute('data-str-studying') || 'Studying',
        paused: root.getAttribute('data-str-paused') || 'Paused',
        onBreak: root.getAttribute('data-str-break') || 'On break',
        cyclePrefix: root.getAttribute('data-str-cycleprefix') || 'Cycle',
        questionLabel: root.getAttribute('data-str-questionlabel') || 'Q'
    };

    var navToggleBtn = document.getElementById('local-studytimer-nav-toggle');
    var navBadge = document.getElementById('local-studytimer-nav-badge');
    var popup = document.getElementById('local-studytimer-popup');
    var dragHandle = document.getElementById('local-studytimer-drag-handle');
    var resizeHandle = document.getElementById('local-studytimer-resize-handle');
    var collapseBtn = document.getElementById('local-studytimer-collapse-btn');
    var cycleBadge = document.getElementById('local-studytimer-cycle-badge');
    var statusPill = document.getElementById('local-studytimer-status-pill');
    var presetBtns = root.querySelectorAll('.local-studytimer-preset-btn');
    var customInputsWrap = document.getElementById('local-studytimer-custom-inputs');
    var studyInput = document.getElementById('local-studytimer-study-input');
    var breakInput = document.getElementById('local-studytimer-break-input');
    var displayEl = document.getElementById('local-studytimer-display');
    var transitionBox = document.getElementById('local-studytimer-transition-box');
    var takeBreakBtn = document.getElementById('local-studytimer-take-break-btn');
    var continueBtn = document.getElementById('local-studytimer-continue-btn');
    var controlsWrap = document.getElementById('local-studytimer-controls');
    var primaryBtn = document.getElementById('local-studytimer-primary-btn');
    var resetBtn = document.getElementById('local-studytimer-reset-btn');
    var autoLoopToggle = document.getElementById('local-studytimer-autoloop-toggle');
    var breakOverlay = document.getElementById('local-studytimer-break-overlay');
    var breakDisplayEl = document.getElementById('local-studytimer-break-display');
    var endBreakBtn = document.getElementById('local-studytimer-end-break-btn');

    function clampNumber(val, min, max, fallback) {
        var n = parseInt(val, 10);
        if (isNaN(n)) {
            return fallback;
        }
        return Math.max(min, Math.min(max, n));
    }

    function defaultState() {
        return {
            isOpen: false,
            position: null,
            width: DEFAULT_WIDTH,
            preset: '25-5',
            studyMinutes: 25,
            breakMinutes: 5,
            autoLoop: true,
            cycleCount: 1,
            mode: 'idle', // 'idle' | 'study' | 'paused_study' | 'awaiting_break' | 'break'
            endTime: null,
            remainingMs: 25 * 60 * 1000
        };
    }

    function loadState() {
        try {
            var raw = window.localStorage.getItem(STORAGE_KEY);
            if (!raw) {
                return defaultState();
            }
            var parsed = JSON.parse(raw);
            var def = defaultState();
            return {
                isOpen: Boolean(parsed.isOpen),
                position: parsed.position && typeof parsed.position.left === 'number' && typeof parsed.position.top === 'number'
                    ? parsed.position
                    : null,
                width: clampNumber(parsed.width, MIN_WIDTH, MAX_WIDTH, DEFAULT_WIDTH),
                preset: parsed.preset || def.preset,
                studyMinutes: clampNumber(parsed.studyMinutes, 1, 180, def.studyMinutes),
                breakMinutes: clampNumber(parsed.breakMinutes, 1, 60, def.breakMinutes),
                autoLoop: parsed.autoLoop !== undefined ? Boolean(parsed.autoLoop) : true,
                cycleCount: clampNumber(parsed.cycleCount, 1, 999, 1),
                mode: parsed.mode || def.mode,
                endTime: typeof parsed.endTime === 'number' ? parsed.endTime : null,
                remainingMs: typeof parsed.remainingMs === 'number' ? parsed.remainingMs : def.remainingMs
            };
        } catch (e) {
            return defaultState();
        }
    }

    var state = loadState();

    function saveState() {
        try {
            window.localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
        } catch (e) {
            // Ignore storage quota errors.
        }
    }

    function formatMs(ms) {
        var totalSeconds = Math.max(0, Math.ceil(ms / 1000));
        var minutes = Math.floor(totalSeconds / 60);
        var seconds = totalSeconds % 60;
        return (minutes < 10 ? '0' : '') + minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
    }

    function formatElapsedSeconds(totalSeconds) {
        var safeSec = Math.max(0, Math.floor(totalSeconds));
        var minutes = Math.floor(safeSec / 60);
        var seconds = safeSec % 60;
        return (minutes < 10 ? '0' : '') + minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
    }

    function playGentleBell() {
        try {
            var AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtx) {
                return;
            }
            var ctx = new AudioCtx();
            var notes = [523.25, 659.25, 783.99];
            notes.forEach(function (freq, idx) {
                var osc = ctx.createOscillator();
                var gain = ctx.createGain();
                var startAt = ctx.currentTime + idx * 0.16;
                osc.type = 'sine';
                osc.frequency.setValueAtTime(freq, startAt);
                gain.gain.setValueAtTime(0.001, startAt);
                gain.gain.exponentialRampToValueAtTime(0.14, startAt + 0.03);
                gain.gain.exponentialRampToValueAtTime(0.0001, startAt + 0.65);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start(startAt);
                osc.stop(startAt + 0.7);
            });
        } catch (e) {
            // Ignore audio errors if blocked by browser policy.
        }
    }

    function clampPopupPosition(left, top) {
        var width = popup.offsetWidth || state.width || DEFAULT_WIDTH;
        var height = popup.offsetHeight || 260;
        var maxLeft = Math.max(8, window.innerWidth - width - 8);
        var maxTop = Math.max(8, window.innerHeight - height - 8);
        return {
            left: Math.max(8, Math.min(maxLeft, left)),
            top: Math.max(8, Math.min(maxTop, top))
        };
    }

    function applySizeAndPosition() {
        var maxAllowed = Math.min(MAX_WIDTH, Math.max(MIN_WIDTH, window.innerWidth - 16));
        var clampedWidth = Math.max(MIN_WIDTH, Math.min(maxAllowed, state.width || DEFAULT_WIDTH));
        popup.style.width = clampedWidth + 'px';

        if (state.position && state.isOpen) {
            var clamped = clampPopupPosition(state.position.left, state.position.top);
            popup.style.left = clamped.left + 'px';
            popup.style.top = clamped.top + 'px';
            popup.style.right = 'auto';
        }
    }

    function getCurrentRemainingMs() {
        if ((state.mode === 'study' || state.mode === 'break') && state.endTime !== null) {
            return Math.max(0, state.endTime - Date.now());
        }
        return Math.max(0, state.remainingMs);
    }

    function startStudySession() {
        var studyMs = state.studyMinutes * 60 * 1000;
        state.mode = 'study';
        state.remainingMs = studyMs;
        state.endTime = Date.now() + studyMs;
        saveState();
    }

    function startBreakSession() {
        var breakMs = state.breakMinutes * 60 * 1000;
        state.mode = 'break';
        state.remainingMs = breakMs;
        state.endTime = Date.now() + breakMs;
        saveState();
    }

    function checkTransitions() {
        var now = Date.now();
        if (state.mode === 'study' && state.endTime !== null && now >= state.endTime) {
            state.mode = 'awaiting_break';
            state.endTime = null;
            state.remainingMs = 0;
            state.isOpen = true;
            saveState();
            playGentleBell();
        } else if (state.mode === 'break' && state.endTime !== null && now >= state.endTime) {
            playGentleBell();
            if (state.autoLoop) {
                state.cycleCount = (state.cycleCount || 1) + 1;
                startStudySession();
            } else {
                state.mode = 'idle';
                state.endTime = null;
                state.remainingMs = state.studyMinutes * 60 * 1000;
                saveState();
            }
        }
    }

    function renderPomodoro() {
        checkTransitions();

        if (state.isOpen) {
            popup.classList.remove('d-none');
            applySizeAndPosition();
        } else {
            popup.classList.add('d-none');
        }
        if (navToggleBtn) {
            navToggleBtn.setAttribute('aria-expanded', state.isOpen ? 'true' : 'false');
        }

        if (cycleBadge) {
            cycleBadge.textContent = strings.cyclePrefix + ' #' + (state.cycleCount || 1);
        }

        if (autoLoopToggle) {
            autoLoopToggle.checked = Boolean(state.autoLoop);
        }

        presetBtns.forEach(function (btn) {
            if (btn.getAttribute('data-preset') === state.preset) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });

        if (state.preset === 'custom') {
            customInputsWrap.classList.remove('d-none');
        } else {
            customInputsWrap.classList.add('d-none');
        }

        if (document.activeElement !== studyInput) {
            studyInput.value = state.studyMinutes;
        }
        if (document.activeElement !== breakInput) {
            breakInput.value = state.breakMinutes;
        }

        var inputsDisabled = state.mode !== 'idle';
        studyInput.disabled = inputsDisabled;
        breakInput.disabled = inputsDisabled;
        presetBtns.forEach(function (btn) {
            btn.disabled = inputsDisabled;
        });

        var remaining = getCurrentRemainingMs();
        var formatted = formatMs(remaining);
        displayEl.textContent = formatted;

        statusPill.classList.remove('is-studying', 'is-paused', 'is-break');
        if (state.mode === 'study') {
            statusPill.textContent = strings.studying;
            statusPill.classList.add('is-studying');
            primaryBtn.textContent = strings.pause;
        } else if (state.mode === 'paused_study') {
            statusPill.textContent = strings.paused;
            statusPill.classList.add('is-paused');
            primaryBtn.textContent = strings.resume;
        } else if (state.mode === 'break') {
            statusPill.textContent = strings.onBreak;
            statusPill.classList.add('is-break');
            primaryBtn.textContent = strings.start;
        } else if (state.mode === 'awaiting_break') {
            statusPill.textContent = strings.studying;
            statusPill.classList.add('is-studying');
        } else {
            statusPill.textContent = strings.ready;
            primaryBtn.textContent = strings.start;
        }

        if (state.mode === 'awaiting_break') {
            transitionBox.classList.remove('d-none');
            controlsWrap.classList.add('d-none');
        } else {
            transitionBox.classList.add('d-none');
            controlsWrap.classList.remove('d-none');
        }

        if (navBadge) {
            if (state.mode === 'study' || state.mode === 'break' || state.mode === 'paused_study') {
                navBadge.textContent = formatted;
                navBadge.classList.remove('d-none');
            } else {
                navBadge.classList.add('d-none');
            }
        }

        if (state.mode === 'break') {
            breakDisplayEl.textContent = formatted;
            breakOverlay.classList.remove('d-none');
        } else {
            breakOverlay.classList.add('d-none');
        }
    }

    function resetTimer() {
        state.mode = 'idle';
        state.endTime = null;
        state.cycleCount = 1;
        state.remainingMs = state.studyMinutes * 60 * 1000;
        saveState();
        renderPomodoro();
    }

    // Navbar toggle & collapse.
    if (navToggleBtn) {
        navToggleBtn.addEventListener('click', function () {
            state.isOpen = !state.isOpen;
            saveState();
            renderPomodoro();
        });
    }

    collapseBtn.addEventListener('click', function () {
        state.isOpen = false;
        saveState();
        renderPomodoro();
    });

    // Auto-loop toggle.
    if (autoLoopToggle) {
        autoLoopToggle.addEventListener('change', function () {
            state.autoLoop = Boolean(autoLoopToggle.checked);
            saveState();
        });
    }

    // Presets & custom inputs.
    presetBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (state.mode !== 'idle') {
                return;
            }
            var preset = btn.getAttribute('data-preset');
            state.preset = preset;
            if (preset !== 'custom') {
                state.studyMinutes = clampNumber(btn.getAttribute('data-study'), 1, 180, 25);
                state.breakMinutes = clampNumber(btn.getAttribute('data-break'), 1, 60, 5);
            }
            state.remainingMs = state.studyMinutes * 60 * 1000;
            saveState();
            renderPomodoro();
        });
    });

    function onCustomInputChange() {
        if (state.mode !== 'idle') {
            return;
        }
        state.studyMinutes = clampNumber(studyInput.value, 1, 180, 25);
        state.breakMinutes = clampNumber(breakInput.value, 1, 60, 5);
        state.remainingMs = state.studyMinutes * 60 * 1000;
        saveState();
        renderPomodoro();
    }

    studyInput.addEventListener('change', onCustomInputChange);
    breakInput.addEventListener('change', onCustomInputChange);

    // Primary Start / Pause / Resume button.
    primaryBtn.addEventListener('click', function () {
        if (state.mode === 'idle') {
            startStudySession();
            renderPomodoro();
        } else if (state.mode === 'study') {
            state.remainingMs = getCurrentRemainingMs();
            state.endTime = null;
            state.mode = 'paused_study';
            saveState();
            renderPomodoro();
        } else if (state.mode === 'paused_study') {
            state.mode = 'study';
            state.endTime = Date.now() + state.remainingMs;
            saveState();
            renderPomodoro();
        }
    });

    resetBtn.addEventListener('click', resetTimer);

    takeBreakBtn.addEventListener('click', function () {
        startBreakSession();
        renderPomodoro();
    });

    continueBtn.addEventListener('click', function () {
        state.cycleCount = (state.cycleCount || 1) + 1;
        startStudySession();
        renderPomodoro();
    });

    endBreakBtn.addEventListener('click', function () {
        state.mode = 'idle';
        state.endTime = null;
        state.remainingMs = state.studyMinutes * 60 * 1000;
        saveState();
        renderPomodoro();
    });

    // Drag and drop for floating popup.
    var dragState = null;

    dragHandle.addEventListener('pointerdown', function (e) {
        if (e.target.closest('button')) {
            return;
        }
        var rect = popup.getBoundingClientRect();
        dragState = {
            pointerId: e.pointerId,
            offsetX: e.clientX - rect.left,
            offsetY: e.clientY - rect.top
        };
        dragHandle.setPointerCapture(e.pointerId);
        e.preventDefault();
    });

    dragHandle.addEventListener('pointermove', function (e) {
        if (!dragState || e.pointerId !== dragState.pointerId) {
            return;
        }
        var pos = clampPopupPosition(e.clientX - dragState.offsetX, e.clientY - dragState.offsetY);
        popup.style.left = pos.left + 'px';
        popup.style.top = pos.top + 'px';
        popup.style.right = 'auto';
        state.position = pos;
    });

    function endDrag(e) {
        if (!dragState || e.pointerId !== dragState.pointerId) {
            return;
        }
        dragState = null;
        saveState();
    }

    dragHandle.addEventListener('pointerup', endDrag);
    dragHandle.addEventListener('pointercancel', endDrag);

    // Bounded horizontal resize handle.
    var resizeState = null;
    if (resizeHandle) {
        resizeHandle.addEventListener('pointerdown', function (e) {
            var rect = popup.getBoundingClientRect();
            resizeState = {
                pointerId: e.pointerId,
                startX: e.clientX,
                startWidth: rect.width
            };
            resizeHandle.setPointerCapture(e.pointerId);
            e.preventDefault();
            e.stopPropagation();
        });

        resizeHandle.addEventListener('pointermove', function (e) {
            if (!resizeState || e.pointerId !== resizeState.pointerId) {
                return;
            }
            var maxAllowed = Math.min(MAX_WIDTH, window.innerWidth - 16);
            var nextWidth = Math.max(MIN_WIDTH, Math.min(maxAllowed, resizeState.startWidth + (e.clientX - resizeState.startX)));
            state.width = Math.round(nextWidth);
            applySizeAndPosition();
        });

        function endResize(e) {
            if (!resizeState || e.pointerId !== resizeState.pointerId) {
                return;
            }
            resizeState = null;
            saveState();
        }

        resizeHandle.addEventListener('pointerup', endResize);
        resizeHandle.addEventListener('pointercancel', endResize);
    }

    window.addEventListener('resize', function () {
        if (state.isOpen) {
            applySizeAndPosition();
            saveState();
        }
    });

    // =========================================================================
    // Feature 2: Quiz Pacing (only active on mod-quiz-attempt pages)
    // =========================================================================
    var pacingSection = document.getElementById('local-studytimer-pacing-section');
    var pacingToggle = document.getElementById('local-studytimer-pacing-toggle');
    var pacingContent = document.getElementById('local-studytimer-pacing-content');
    var pacingQNumEl = document.getElementById('local-studytimer-pacing-qnum');
    var pacingDisplayEl = document.getElementById('local-studytimer-pacing-display');
    var pacingSecondsInput = document.getElementById('local-studytimer-pacing-seconds');
    var pacingToastEl = document.getElementById('local-studytimer-pacing-toast');

    var pacingSettings = null;
    var pacingSession = null;
    var toastTimerId = null;

    function loadPacingSettings() {
        try {
            var raw = window.localStorage.getItem(PACING_SETTINGS_KEY);
            if (!raw) {
                return { enabled: true, targetSeconds: 60 };
            }
            var parsed = JSON.parse(raw);
            return {
                enabled: parsed.enabled !== undefined ? Boolean(parsed.enabled) : true,
                targetSeconds: clampNumber(parsed.targetSeconds, 10, 600, 60)
            };
        } catch (e) {
            return { enabled: true, targetSeconds: 60 };
        }
    }

    function savePacingSettings() {
        try {
            window.localStorage.setItem(PACING_SETTINGS_KEY, JSON.stringify(pacingSettings));
        } catch (e) {
            // Ignore storage errors.
        }
    }

    function loadPacingSession() {
        try {
            var raw = window.sessionStorage.getItem(PACING_SESSION_KEY);
            return raw ? JSON.parse(raw) : null;
        } catch (e) {
            return null;
        }
    }

    function savePacingSession() {
        try {
            window.sessionStorage.setItem(PACING_SESSION_KEY, JSON.stringify(pacingSession));
        } catch (e) {
            // Ignore storage errors.
        }
    }

    function getAttemptIdFromUrl() {
        try {
            var params = new URLSearchParams(window.location.search);
            return params.get('attempt') || '0';
        } catch (e) {
            return '0';
        }
    }

    function extractQuestionInfo(queEl, indexFallback) {
        var qId = queEl.getAttribute('id') || ('idx-' + indexFallback);
        var qnoEl = queEl.querySelector('.info .qno');
        var qNum = qnoEl ? qnoEl.textContent.trim() : String(indexFallback + 1);
        return {
            key: getAttemptIdFromUrl() + ':' + qId,
            label: strings.questionLabel + ' ' + qNum
        };
    }

    function showPacingToast() {
        if (!pacingToastEl) {
            return;
        }
        pacingToastEl.classList.remove('d-none');
        if (toastTimerId) {
            window.clearTimeout(toastTimerId);
        }
        toastTimerId = window.setTimeout(function () {
            pacingToastEl.classList.add('d-none');
            toastTimerId = null;
        }, 5000);
    }

    function hidePacingToast() {
        if (toastTimerId) {
            window.clearTimeout(toastTimerId);
            toastTimerId = null;
        }
        if (pacingToastEl) {
            pacingToastEl.classList.add('d-none');
        }
    }

    function switchToQuestion(info) {
        if (pacingSession && pacingSession.key === info.key) {
            return;
        }
        pacingSession = {
            key: info.key,
            label: info.label,
            startedAt: Date.now(),
            overtimeNotified: false
        };
        savePacingSession();
        hidePacingToast();
    }

    function initQuizPacing() {
        if (!pacingSection) {
            return;
        }

        pacingSettings = loadPacingSettings();
        pacingSession = loadPacingSession();

        var queEls = document.querySelectorAll('.que');
        if (queEls.length > 0) {
            var initialQue = queEls[0];
            var initialIdx = 0;
            if (window.location.hash) {
                var hashed = document.querySelector(window.location.hash);
                if (hashed && hashed.classList.contains('que')) {
                    initialQue = hashed;
                    initialIdx = Array.prototype.indexOf.call(queEls, hashed);
                }
            }
            var initialInfo = extractQuestionInfo(initialQue, Math.max(0, initialIdx));
            if (!pacingSession || pacingSession.key !== initialInfo.key) {
                switchToQuestion(initialInfo);
            }
        }

        // Detect switching between questions on multi-question quiz pages.
        function onQuestionInteract(e) {
            var queEl = e.target && e.target.closest ? e.target.closest('.que') : null;
            if (!queEl) {
                return;
            }
            var idx = Array.prototype.indexOf.call(queEls, queEl);
            switchToQuestion(extractQuestionInfo(queEl, Math.max(0, idx)));
            renderQuizPacing();
        }

        document.addEventListener('click', onQuestionInteract);
        document.addEventListener('focusin', onQuestionInteract);

        if (pacingToggle) {
            pacingToggle.checked = pacingSettings.enabled;
            pacingToggle.addEventListener('change', function () {
                pacingSettings.enabled = Boolean(pacingToggle.checked);
                savePacingSettings();
                if (!pacingSettings.enabled) {
                    hidePacingToast();
                } else if (pacingSession) {
                    pacingSession.startedAt = Date.now();
                    pacingSession.overtimeNotified = false;
                    savePacingSession();
                }
                renderQuizPacing();
            });
        }

        if (pacingSecondsInput) {
            pacingSecondsInput.value = pacingSettings.targetSeconds;
            pacingSecondsInput.addEventListener('change', function () {
                pacingSettings.targetSeconds = clampNumber(pacingSecondsInput.value, 10, 600, 60);
                pacingSecondsInput.value = pacingSettings.targetSeconds;
                savePacingSettings();
                renderQuizPacing();
            });
        }
    }

    function renderQuizPacing() {
        if (!pacingSection || !pacingSettings) {
            return;
        }

        if (!pacingSettings.enabled) {
            pacingContent.classList.add('opacity-50');
            pacingDisplayEl.textContent = '--:--';
            pacingDisplayEl.classList.remove('is-warning', 'is-danger');
            return;
        }

        pacingContent.classList.remove('opacity-50');

        if (!pacingSession) {
            pacingDisplayEl.textContent = '00:00';
            pacingDisplayEl.classList.remove('is-warning', 'is-danger');
            return;
        }

        if (pacingQNumEl) {
            pacingQNumEl.textContent = pacingSession.label;
        }

        var elapsedSeconds = Math.max(0, Math.floor((Date.now() - pacingSession.startedAt) / 1000));
        pacingDisplayEl.textContent = formatElapsedSeconds(elapsedSeconds);

        var ratio = elapsedSeconds / Math.max(1, pacingSettings.targetSeconds);
        pacingDisplayEl.classList.remove('is-warning', 'is-danger');

        if (ratio >= 1.0) {
            pacingDisplayEl.classList.add('is-danger');
            if (!pacingSession.overtimeNotified) {
                pacingSession.overtimeNotified = true;
                savePacingSession();
                showPacingToast();
            }
        } else if (ratio >= 0.8) {
            pacingDisplayEl.classList.add('is-warning');
        }
    }

    function tick() {
        renderPomodoro();
        renderQuizPacing();
    }

    // Sync state across open Moodle tabs.
    window.addEventListener('storage', function (e) {
        if (e.key === STORAGE_KEY) {
            state = loadState();
            renderPomodoro();
        } else if (e.key === PACING_SETTINGS_KEY && pacingSection) {
            pacingSettings = loadPacingSettings();
            if (pacingToggle) {
                pacingToggle.checked = pacingSettings.enabled;
            }
            if (pacingSecondsInput) {
                pacingSecondsInput.value = pacingSettings.targetSeconds;
            }
            renderQuizPacing();
        }
    });

    initQuizPacing();
    tick();
    window.setInterval(tick, 250);
})();
