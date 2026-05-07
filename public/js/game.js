(function () {
    const page = document.querySelector('[data-game-page]');

    if (!page) {
        return;
    }

    const canvas = document.getElementById('platformer-canvas');
    const startScreen = page.querySelector('[data-game-start-screen]');
    const startButton = page.querySelector('[data-game-start-button]');
    const progressPreview = page.querySelector('[data-game-progress-preview]');
    const progressCaption = page.querySelector('[data-game-progress-caption]');
    const progressSteps = Array.from(page.querySelectorAll('[data-game-progress-step]'));

    if (!(canvas instanceof HTMLCanvasElement)) {
        return;
    }

    const context = canvas.getContext('2d');

    if (!context) {
        return;
    }

    const state = {
        started: false,
        activeSlide: 1,
    };

    function drawPlaceholderScene() {
        context.clearRect(0, 0, canvas.width, canvas.height);

        context.fillStyle = '#ffffff';
        context.fillRect(0, 0, canvas.width, canvas.height);

        context.fillStyle = '#111111';
        context.fillRect(0, canvas.height - 88, canvas.width, 88);

        context.fillRect(80, canvas.height - 180, 140, 18);
        context.fillRect(280, canvas.height - 250, 160, 18);
        context.fillRect(520, canvas.height - 210, 130, 18);

        context.fillRect(170, canvas.height - 132, 32, 44);
        context.fillRect(177, canvas.height - 158, 18, 20);

        context.fillRect(canvas.width - 150, canvas.height - 168, 12, 80);
        context.fillRect(canvas.width - 150, canvas.height - 168, 46, 18);

        context.fillStyle = '#444444';
        context.font = '700 20px Figtree, sans-serif';
        context.fillText('Gameplay will appear in the next step', 46, 54);

        context.font = '500 16px Figtree, sans-serif';
        context.fillText('Canvas, progress panel, and start screen are ready.', 46, 84);
    }

    function syncProgressUI() {
        progressSteps.forEach((step) => {
            const isActive = Number(step.getAttribute('data-slide-number')) === state.activeSlide;
            step.classList.toggle('is-active', isActive);
        });

        if (progressPreview) {
            const previewCard = progressPreview.querySelector('.game-progress-panel__preview-image span');

            if (previewCard) {
                previewCard.textContent = `Slide ${state.activeSlide}`;
            }
        }

        if (progressCaption) {
            progressCaption.textContent = `Slide ${state.activeSlide} of 10`;
        }
    }

    function hideStartScreen() {
        if (!startScreen) {
            return;
        }

        startScreen.classList.add('is-hidden');
        startScreen.setAttribute('aria-hidden', 'true');
    }

    function startGameShell() {
        state.started = true;
        hideStartScreen();
        drawPlaceholderScene();
        syncProgressUI();
    }

    startButton?.addEventListener('click', function () {
        startGameShell();
    });

    drawPlaceholderScene();
    syncProgressUI();
})();
