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

    const INPUT_KEYS = new Set(['ArrowLeft', 'ArrowRight', 'ArrowUp', 'Space']);
    const FLOOR_HEIGHT = 86;
    const PHYSICS = {
        gravity: 0.72,
        maxFallSpeed: 14,
        moveSpeed: 4.6,
        jumpForce: 13.8,
    };

    const state = {
        started: false,
        animationFrameId: null,
        lastFrameAt: 0,
        activeSlide: 1,
        keys: {
            left: false,
            right: false,
            up: false,
        },
        player: createPlayer(),
        platforms: createPlatforms(),
    };

    function createPlayer() {
        return {
            x: 116,
            y: canvas.height - FLOOR_HEIGHT - 68,
            width: 36,
            height: 58,
            vx: 0,
            vy: 0,
            onGround: false,
            facing: 'right',
        };
    }

    function createPlatforms() {
        return [
            { x: 0, y: canvas.height - FLOOR_HEIGHT, width: canvas.width, height: FLOOR_HEIGHT, type: 'floor' },
            { x: 118, y: canvas.height - 186, width: 146, height: 16, type: 'platform' },
            { x: 334, y: canvas.height - 266, width: 174, height: 16, type: 'platform' },
            { x: 588, y: canvas.height - 226, width: 136, height: 16, type: 'platform' },
            { x: 786, y: canvas.height - 154, width: 108, height: 16, type: 'platform' },
        ];
    }

    function resetPlayerPosition() {
        const freshPlayer = createPlayer();
        state.player.x = freshPlayer.x;
        state.player.y = freshPlayer.y;
        state.player.width = freshPlayer.width;
        state.player.height = freshPlayer.height;
        state.player.vx = 0;
        state.player.vy = 0;
        state.player.onGround = false;
        state.player.facing = freshPlayer.facing;
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

    function preventGamePageScroll(event) {
        if (state.started && INPUT_KEYS.has(event.code)) {
            event.preventDefault();
        }
    }

    function handleKeyDown(event) {
        preventGamePageScroll(event);

        if (!state.started) {
            return;
        }

        if (event.code === 'ArrowLeft') {
            state.keys.left = true;
        }

        if (event.code === 'ArrowRight') {
            state.keys.right = true;
        }

        if (event.code === 'ArrowUp') {
            state.keys.up = true;
        }
    }

    function handleKeyUp(event) {
        if (event.code === 'ArrowLeft') {
            state.keys.left = false;
        }

        if (event.code === 'ArrowRight') {
            state.keys.right = false;
        }

        if (event.code === 'ArrowUp') {
            state.keys.up = false;
        }
    }

    function updatePlayerHorizontalMovement() {
        const { player, keys } = state;

        player.vx = 0;

        if (keys.left && !keys.right) {
            player.vx = -PHYSICS.moveSpeed;
            player.facing = 'left';
        }

        if (keys.right && !keys.left) {
            player.vx = PHYSICS.moveSpeed;
            player.facing = 'right';
        }
    }

    function applyJumpIfNeeded() {
        const { player, keys } = state;

        if (keys.up && player.onGround) {
            player.vy = -PHYSICS.jumpForce;
            player.onGround = false;
        }
    }

    function resolveHorizontalCollisions(previousX) {
        const { player, platforms } = state;

        platforms.forEach((platform) => {
            if (!isIntersecting(player, platform)) {
                return;
            }

            if (previousX + player.width <= platform.x) {
                player.x = platform.x - player.width;
            } else if (previousX >= platform.x + platform.width) {
                player.x = platform.x + platform.width;
            }
        });

        player.x = Math.max(0, Math.min(player.x, canvas.width - player.width));
    }

    function resolveVerticalCollisions(previousY) {
        const { player, platforms } = state;

        player.onGround = false;

        platforms.forEach((platform) => {
            if (!isIntersecting(player, platform)) {
                return;
            }

            if (previousY + player.height <= platform.y) {
                player.y = platform.y - player.height;
                player.vy = 0;
                player.onGround = true;
            } else if (previousY >= platform.y + platform.height) {
                player.y = platform.y + platform.height;
                player.vy = Math.max(player.vy, 0);
            }
        });

        if (player.y + player.height >= canvas.height) {
            player.y = canvas.height - player.height;
            player.vy = 0;
            player.onGround = true;
        }
    }

    function isIntersecting(rectA, rectB) {
        return (
            rectA.x < rectB.x + rectB.width &&
            rectA.x + rectA.width > rectB.x &&
            rectA.y < rectB.y + rectB.height &&
            rectA.y + rectA.height > rectB.y
        );
    }

    function update() {
        const { player } = state;

        updatePlayerHorizontalMovement();
        applyJumpIfNeeded();

        const previousX = player.x;
        player.x += player.vx;
        resolveHorizontalCollisions(previousX);

        player.vy = Math.min(player.vy + PHYSICS.gravity, PHYSICS.maxFallSpeed);

        const previousY = player.y;
        player.y += player.vy;
        resolveVerticalCollisions(previousY);

        if (player.y > canvas.height + 120) {
            resetPlayerPosition();
        }
    }

    function drawScene() {
        context.clearRect(0, 0, canvas.width, canvas.height);

        drawBackground();
        drawPlatforms();
        drawPlayer();
        drawControlsHint();
    }

    function drawBackground() {
        const skyGradient = context.createLinearGradient(0, 0, 0, canvas.height);
        skyGradient.addColorStop(0, '#ffffff');
        skyGradient.addColorStop(0.55, '#f1f5f9');
        skyGradient.addColorStop(1, '#cbd5e1');

        context.fillStyle = skyGradient;
        context.fillRect(0, 0, canvas.width, canvas.height);

        context.fillStyle = '#dbe4ef';
        context.fillRect(0, canvas.height - FLOOR_HEIGHT - 18, canvas.width, 18);
    }

    function drawPlatforms() {
        state.platforms.forEach((platform) => {
            const isFloor = platform.type === 'floor';

            context.fillStyle = isFloor ? '#111111' : '#1f2937';
            context.fillRect(platform.x, platform.y, platform.width, platform.height);

            context.fillStyle = isFloor ? '#475569' : '#64748b';
            context.fillRect(platform.x, platform.y, platform.width, 4);
        });
    }

    function drawPlayer() {
        const { player } = state;
        const bodyColor = '#0f172a';
        const accentColor = '#475569';

        context.fillStyle = bodyColor;
        context.fillRect(player.x, player.y + 14, player.width, player.height - 14);

        context.fillRect(player.x + 5, player.y + player.height - 2, 8, 12);
        context.fillRect(player.x + player.width - 13, player.y + player.height - 2, 8, 12);

        context.fillRect(player.x - 6, player.y + 18, 8, 24);
        context.fillRect(player.x + player.width - 2, player.y + 18, 8, 24);

        context.beginPath();
        context.arc(player.x + player.width / 2, player.y + 10, 12, 0, Math.PI * 2);
        context.fill();

        context.fillStyle = accentColor;
        const eyeX = player.facing === 'right' ? player.x + 23 : player.x + 11;
        context.fillRect(eyeX, player.y + 8, 4, 4);
    }

    function drawControlsHint() {
        context.fillStyle = 'rgba(15, 23, 42, 0.78)';
        context.font = '600 16px Figtree, sans-serif';
        context.fillText('Use left, right, and up arrows to move.', 28, 34);

        context.fillStyle = 'rgba(71, 85, 105, 0.92)';
        context.font = '500 13px Figtree, sans-serif';
        context.fillText('Stage 2: movement, jump, gravity, platforms, collisions.', 28, 56);
    }

    function loop(timestamp) {
        if (!state.started) {
            return;
        }

        if (timestamp - state.lastFrameAt >= 1000 / 60) {
            state.lastFrameAt = timestamp;
            update();
            drawScene();
        }

        state.animationFrameId = window.requestAnimationFrame(loop);
    }

    function startGame() {
        if (state.started) {
            return;
        }

        state.started = true;
        state.lastFrameAt = 0;
        resetPlayerPosition();
        hideStartScreen();
        syncProgressUI();
        drawScene();
        state.animationFrameId = window.requestAnimationFrame(loop);
    }

    window.addEventListener('keydown', handleKeyDown);
    window.addEventListener('keyup', handleKeyUp);

    startButton?.addEventListener('click', function () {
        startGame();
    });

    drawScene();
    syncProgressUI();
})();
