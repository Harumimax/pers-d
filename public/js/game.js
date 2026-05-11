(function () {
    const page = document.querySelector('[data-game-page]');

    if (!page) {
        return;
    }

    const canvas = document.getElementById('platformer-canvas');
    const startScreen = page.querySelector('[data-game-start-screen]');
    const startButton = page.querySelector('[data-game-start-button]');
    const winScreen = page.querySelector('[data-game-win-screen]');
    const loseScreen = page.querySelector('[data-game-lose-screen]');
    const winRestartButton = page.querySelector('[data-game-win-restart-button]');
    const loseRestartButton = page.querySelector('[data-game-lose-restart-button]');
    const livesBadge = page.querySelector('[data-game-lives]');
    const progressPreview = page.querySelector('[data-game-progress-preview]');

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
    const WEAPON = {
        bulletSpeed: 9.5,
        cooldownMs: 220,
    };

    const level = createLevel();

    const state = {
        started: false,
        gameStatus: 'idle',
        animationFrameId: null,
        lastFrameAt: 0,
        activeSlide: 1,
        maxLives: 3,
        lives: 3,
        keys: {
            left: false,
            right: false,
            up: false,
        },
        level,
        camera: {
            x: 0,
            y: 0,
        },
        player: createPlayer(level),
        bullets: [],
        enemies: createEnemyRuntime(level.enemyConfigs),
        breakableObstacles: createBreakableRuntime(level.breakableConfigs),
        hazards: createHazardRuntime(level.hazardConfigs),
        lastShotAt: -WEAPON.cooldownMs,
    };

    function createLevel() {
        return {
            width: 2480,
            height: canvas.height,
            spawn: {
                x: 116,
                y: canvas.height - FLOOR_HEIGHT - 68,
            },
            finishZone: {
                x: 2320,
                y: canvas.height - FLOOR_HEIGHT - 168,
                width: 80,
                height: 168,
            },
            platforms: [
                { x: 0, y: canvas.height - FLOOR_HEIGHT, width: 2480, height: FLOOR_HEIGHT, type: 'floor' },
                { x: 124, y: canvas.height - 188, width: 150, height: 16, type: 'platform' },
                { x: 346, y: canvas.height - 272, width: 188, height: 16, type: 'platform' },
                { x: 626, y: canvas.height - 220, width: 140, height: 16, type: 'platform' },
                { x: 856, y: canvas.height - 154, width: 126, height: 16, type: 'platform' },
                { x: 1022, y: canvas.height - 254, width: 174, height: 16, type: 'platform' },
                { x: 1288, y: canvas.height - 322, width: 122, height: 16, type: 'platform' },
                { x: 1468, y: canvas.height - 232, width: 168, height: 16, type: 'platform' },
                { x: 1738, y: canvas.height - 286, width: 156, height: 16, type: 'platform' },
                { x: 1988, y: canvas.height - 204, width: 134, height: 16, type: 'platform' },
                { x: 2166, y: canvas.height - 146, width: 120, height: 16, type: 'platform' },
            ],
            enemyConfigs: [
                { type: 'static', x: 694, y: canvas.height - FLOOR_HEIGHT - 42, width: 34, height: 42 },
                { type: 'patrol', x: 1086, y: canvas.height - 254 - 42, width: 34, height: 42, patrolMinX: 1036, patrolMaxX: 1158, speed: 1.25 },
                { type: 'static', x: 1526, y: canvas.height - 232 - 42, width: 34, height: 42 },
                { type: 'patrol', x: 2018, y: canvas.height - 204 - 42, width: 34, height: 42, patrolMinX: 1996, patrolMaxX: 2078, speed: 1.1 },
            ],
            breakableConfigs: [
                { x: 540, y: canvas.height - FLOOR_HEIGHT - 42, width: 34, height: 34 },
                { x: 931, y: canvas.height - FLOOR_HEIGHT - 42, width: 34, height: 34 },
                { x: 1838, y: canvas.height - FLOOR_HEIGHT - 42, width: 34, height: 34 },
            ],
            hazardConfigs: [
                { x: 780, y: canvas.height - FLOOR_HEIGHT - 18, width: 58, height: 18 },
                { x: 1378, y: canvas.height - FLOOR_HEIGHT - 18, width: 64, height: 18 },
                { x: 2092, y: canvas.height - FLOOR_HEIGHT - 18, width: 64, height: 18 },
            ],
        };
    }

    function createPlayer(levelConfig) {
        return {
            x: levelConfig.spawn.x,
            y: levelConfig.spawn.y,
            width: 36,
            height: 58,
            vx: 0,
            vy: 0,
            onGround: false,
            facing: 'right',
        };
    }

    function createEnemyRuntime(configs) {
        return configs.map((config, index) => ({
            id: `enemy-${index + 1}`,
            ...config,
            alive: true,
            direction: config.type === 'patrol' ? 1 : 0,
        }));
    }

    function createBreakableRuntime(configs) {
        return configs.map((config, index) => ({
            id: `breakable-${index + 1}`,
            ...config,
            active: true,
        }));
    }

    function createHazardRuntime(configs) {
        return configs.map((config, index) => ({
            id: `hazard-${index + 1}`,
            ...config,
        }));
    }

    function setGameStatus(status) {
        state.gameStatus = status;
    }

    function hideScreen(screen) {
        if (!screen) {
            return;
        }

        screen.classList.add('is-hidden');
        screen.setAttribute('aria-hidden', 'true');
    }

    function showScreen(screen) {
        if (!screen) {
            return;
        }

        screen.classList.remove('is-hidden');
        screen.setAttribute('aria-hidden', 'false');
    }

    function hideEndScreens() {
        hideScreen(winScreen);
        hideScreen(loseScreen);
    }

    function syncLivesUI() {
        if (!livesBadge) {
            return;
        }

        const label = livesBadge.getAttribute('data-label') || 'Lives';
        livesBadge.textContent = `${label}: ${state.lives}`;
    }

    function syncProgressUI() {
        if (progressPreview) {
            const previewCard = progressPreview.querySelector('.game-progress-panel__preview-image span');

            if (previewCard) {
                previewCard.textContent = `Slide ${state.activeSlide}`;
            }
        }
    }

    function hideStartScreen() {
        if (!startScreen) {
            return;
        }

        startScreen.classList.add('is-hidden');
        startScreen.setAttribute('aria-hidden', 'true');
    }

    function resetPlayerPosition() {
        state.player.x = state.level.spawn.x;
        state.player.y = state.level.spawn.y;
        state.player.width = 36;
        state.player.height = 58;
        state.player.vx = 0;
        state.player.vy = 0;
        state.player.onGround = false;
        state.player.facing = 'right';
        state.camera.x = 0;
    }

    function resetRuntimeWorld() {
        resetPlayerPosition();
        state.bullets = [];
        state.enemies = createEnemyRuntime(state.level.enemyConfigs);
        state.breakableObstacles = createBreakableRuntime(state.level.breakableConfigs);
        state.hazards = createHazardRuntime(state.level.hazardConfigs);
        state.lastShotAt = -WEAPON.cooldownMs;
        state.activeSlide = 1;
        syncProgressUI();
    }

    function respawnAfterHit() {
        resetRuntimeWorld();
        drawScene();
    }

    function restartGame() {
        state.started = true;
        state.lives = state.maxLives;
        setGameStatus('running');
        hideStartScreen();
        hideEndScreens();
        resetRuntimeWorld();
        syncLivesUI();
        updateActiveSlide();
        syncProgressUI();
        drawScene();

        if (state.animationFrameId === null) {
            state.animationFrameId = window.requestAnimationFrame(loop);
        }
    }

    function showWinScreen() {
        hideScreen(loseScreen);
        showScreen(winScreen);
    }

    function showLoseScreen() {
        hideScreen(winScreen);
        showScreen(loseScreen);
    }

    function completeLevel() {
        setGameStatus('won');
        state.keys.left = false;
        state.keys.right = false;
        state.keys.up = false;
        state.player.vx = 0;
        state.player.vy = 0;
        state.activeSlide = 10;
        syncProgressUI();
        showWinScreen();
    }

    function loseLife() {
        if (state.gameStatus !== 'running') {
            return;
        }

        state.lives = Math.max(0, state.lives - 1);
        syncLivesUI();

        if (state.lives === 0) {
            setGameStatus('lost');
            state.keys.left = false;
            state.keys.right = false;
            state.keys.up = false;
            state.player.vx = 0;
            state.player.vy = 0;
            showLoseScreen();
            return;
        }

        respawnAfterHit();
    }

    function preventGamePageScroll(event) {
        if (state.started && INPUT_KEYS.has(event.code)) {
            event.preventDefault();
        }
    }

    function handleKeyDown(event) {
        preventGamePageScroll(event);

        if (state.gameStatus !== 'running') {
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

        if (event.code === 'Space') {
            tryShoot(event.timeStamp);
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

    function tryShoot(timestamp) {
        if (timestamp - state.lastShotAt < WEAPON.cooldownMs) {
            return;
        }

        state.lastShotAt = timestamp;

        const direction = state.player.facing === 'right' ? 1 : -1;
        const bulletWidth = 12;
        const bulletHeight = 6;
        const muzzleOffsetX = direction === 1 ? state.player.width + 2 : -bulletWidth - 2;

        state.bullets.push({
            x: state.player.x + muzzleOffsetX,
            y: state.player.y + 24,
            width: bulletWidth,
            height: bulletHeight,
            vx: direction * WEAPON.bulletSpeed,
            active: true,
        });
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

    function getSolidBodies() {
        return state.level.platforms.concat(state.breakableObstacles.filter((obstacle) => obstacle.active));
    }

    function resolveHorizontalCollisions(previousX) {
        const { player, level } = state;

        getSolidBodies().forEach((platform) => {
            if (!isIntersecting(player, platform)) {
                return;
            }

            if (previousX + player.width <= platform.x) {
                player.x = platform.x - player.width;
            } else if (previousX >= platform.x + platform.width) {
                player.x = platform.x + platform.width;
            }
        });

        player.x = Math.max(0, Math.min(player.x, level.width - player.width));
    }

    function resolveVerticalCollisions(previousY) {
        const { player, level } = state;

        player.onGround = false;

        getSolidBodies().forEach((platform) => {
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

        if (player.y + player.height >= level.height) {
            player.y = level.height - player.height;
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

    function updateCamera() {
        const { camera, player, level } = state;
        const targetX = player.x + player.width / 2 - canvas.width / 2;
        camera.x = clamp(targetX, 0, level.width - canvas.width);
    }

    function clamp(value, min, max) {
        return Math.max(min, Math.min(value, max));
    }

    function updateActiveSlide() {
        const { player, level } = state;
        const progress = clamp(player.x / (level.finishZone.x + level.finishZone.width), 0, 1);
        state.activeSlide = Math.min(10, Math.max(1, Math.floor(progress * 10) + 1));
    }

    function checkFinishReached() {
        if (state.gameStatus !== 'running') {
            return;
        }

        if (isIntersecting(state.player, state.level.finishZone)) {
            completeLevel();
        }
    }

    function updateEnemies() {
        state.enemies.forEach((enemy) => {
            if (!enemy.alive || enemy.type !== 'patrol') {
                return;
            }

            enemy.x += enemy.speed * enemy.direction;

            if (enemy.x <= enemy.patrolMinX) {
                enemy.x = enemy.patrolMinX;
                enemy.direction = 1;
            }

            if (enemy.x + enemy.width >= enemy.patrolMaxX) {
                enemy.x = enemy.patrolMaxX - enemy.width;
                enemy.direction = -1;
            }
        });
    }

    function updateBullets() {
        const solidBodies = getSolidBodies();

        state.bullets.forEach((bullet) => {
            if (!bullet.active) {
                return;
            }

            bullet.x += bullet.vx;

            if (bullet.x > state.level.width + 40 || bullet.x + bullet.width < -40) {
                bullet.active = false;
                return;
            }

            const enemyHit = state.enemies.find((enemy) => enemy.alive && isIntersecting(bullet, enemy));

            if (enemyHit) {
                enemyHit.alive = false;
                bullet.active = false;
                return;
            }

            const obstacleHit = state.breakableObstacles.find((obstacle) => obstacle.active && isIntersecting(bullet, obstacle));

            if (obstacleHit) {
                obstacleHit.active = false;
                bullet.active = false;
                return;
            }

            const solidHit = solidBodies.some((body) => isIntersecting(bullet, body));

            if (solidHit) {
                bullet.active = false;
            }
        });

        state.bullets = state.bullets.filter((bullet) => bullet.active);
    }

    function checkPlayerDangerCollisions() {
        const enemyCollision = state.enemies.some((enemy) => enemy.alive && isIntersecting(state.player, enemy));
        const hazardCollision = state.hazards.some((hazard) => isIntersecting(state.player, hazard));

        return enemyCollision || hazardCollision;
    }

    function update() {
        if (state.gameStatus !== 'running') {
            syncProgressUI();
            syncLivesUI();
            return;
        }

        const { player, level } = state;

        updatePlayerHorizontalMovement();
        applyJumpIfNeeded();

        const previousX = player.x;
        player.x += player.vx;
        resolveHorizontalCollisions(previousX);

        player.vy = Math.min(player.vy + PHYSICS.gravity, PHYSICS.maxFallSpeed);

        const previousY = player.y;
        player.y += player.vy;
        resolveVerticalCollisions(previousY);

        if (player.y > level.height + 120) {
            loseLife();
            return;
        }

        updateEnemies();
        updateBullets();

        if (checkPlayerDangerCollisions()) {
            loseLife();
            return;
        }

        updateCamera();
        updateActiveSlide();
        checkFinishReached();
        syncProgressUI();
        syncLivesUI();
    }

    function worldToScreenX(worldX) {
        return worldX - state.camera.x;
    }

    function drawScene() {
        context.clearRect(0, 0, canvas.width, canvas.height);

        drawBackground();
        drawPlatforms();
        drawBreakableObstacles();
        drawHazards();
        drawFinishZone();
        drawEnemies();
        drawBullets();
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

        context.fillStyle = '#e2e8f0';

        for (let index = 0; index < state.level.width; index += 220) {
            const stripeX = worldToScreenX(index);
            context.fillRect(stripeX, canvas.height - FLOOR_HEIGHT - 18, 120, 18);
        }
    }

    function drawPlatforms() {
        state.level.platforms.forEach((platform) => {
            const screenX = worldToScreenX(platform.x);

            if (screenX + platform.width < -40 || screenX > canvas.width + 40) {
                return;
            }

            const isFloor = platform.type === 'floor';

            context.fillStyle = isFloor ? '#111111' : '#1f2937';
            context.fillRect(screenX, platform.y, platform.width, platform.height);

            context.fillStyle = isFloor ? '#475569' : '#94a3b8';
            context.fillRect(screenX, platform.y, platform.width, 4);
        });
    }

    function drawBreakableObstacles() {
        state.breakableObstacles.forEach((obstacle) => {
            if (!obstacle.active) {
                return;
            }

            const screenX = worldToScreenX(obstacle.x);

            if (screenX + obstacle.width < -40 || screenX > canvas.width + 40) {
                return;
            }

            context.fillStyle = '#334155';
            context.fillRect(screenX, obstacle.y, obstacle.width, obstacle.height);

            context.strokeStyle = '#cbd5e1';
            context.lineWidth = 2;
            context.strokeRect(screenX + 1, obstacle.y + 1, obstacle.width - 2, obstacle.height - 2);

            context.beginPath();
            context.moveTo(screenX + 7, obstacle.y + 7);
            context.lineTo(screenX + obstacle.width - 7, obstacle.y + obstacle.height - 7);
            context.moveTo(screenX + obstacle.width - 7, obstacle.y + 7);
            context.lineTo(screenX + 7, obstacle.y + obstacle.height - 7);
            context.stroke();
        });
    }

    function drawHazards() {
        state.hazards.forEach((hazard) => {
            const screenX = worldToScreenX(hazard.x);

            if (screenX + hazard.width < -40 || screenX > canvas.width + 40) {
                return;
            }

            const spikeCount = Math.max(3, Math.floor(hazard.width / 12));
            const spikeWidth = hazard.width / spikeCount;

            context.fillStyle = '#0f172a';

            for (let index = 0; index < spikeCount; index += 1) {
                const spikeX = screenX + index * spikeWidth;

                context.beginPath();
                context.moveTo(spikeX, hazard.y + hazard.height);
                context.lineTo(spikeX + spikeWidth / 2, hazard.y);
                context.lineTo(spikeX + spikeWidth, hazard.y + hazard.height);
                context.closePath();
                context.fill();
            }
        });
    }

    function drawFinishZone() {
        const finish = state.level.finishZone;
        const flagX = worldToScreenX(finish.x);
        const poleTopY = finish.y;
        const poleHeight = finish.height;

        if (flagX + finish.width < -80 || flagX > canvas.width + 80) {
            return;
        }

        context.fillStyle = '#0f172a';
        context.fillRect(flagX + 18, poleTopY, 8, poleHeight);

        const boardX = flagX + 26;
        const boardY = poleTopY + 8;
        const boardWidth = 44;
        const boardHeight = 28;

        context.fillStyle = '#f8fafc';
        context.fillRect(boardX, boardY, boardWidth, boardHeight);

        context.strokeStyle = '#94a3b8';
        context.lineWidth = 3;
        context.strokeRect(boardX, boardY, boardWidth, boardHeight);

        context.fillStyle = '#cbd5e1';
        context.beginPath();
        context.moveTo(boardX + 6, boardY + 6);
        context.lineTo(boardX + 34, boardY + 14);
        context.lineTo(boardX + 6, boardY + 22);
        context.closePath();
        context.fill();

        context.fillStyle = 'rgba(15, 23, 42, 0.6)';
        context.fillRect(flagX - 6, poleTopY + poleHeight, 52, 10);
    }

    function drawEnemies() {
        state.enemies.forEach((enemy) => {
            if (!enemy.alive) {
                return;
            }

            const screenX = worldToScreenX(enemy.x);

            if (screenX + enemy.width < -40 || screenX > canvas.width + 40) {
                return;
            }

            context.fillStyle = enemy.type === 'patrol' ? '#111827' : '#1f2937';
            context.fillRect(screenX, enemy.y + 10, enemy.width, enemy.height - 10);

            context.beginPath();
            context.arc(screenX + enemy.width / 2, enemy.y + 10, 11, 0, Math.PI * 2);
            context.fill();

            context.fillStyle = '#f8fafc';
            const eyeBaseX = enemy.type === 'patrol' && enemy.direction === -1 ? screenX + 8 : screenX + enemy.width - 14;
            context.fillRect(eyeBaseX, enemy.y + 8, 4, 4);
        });
    }

    function drawBullets() {
        state.bullets.forEach((bullet) => {
            const screenX = worldToScreenX(bullet.x);

            if (screenX + bullet.width < -20 || screenX > canvas.width + 20) {
                return;
            }

            context.fillStyle = '#0f172a';
            context.fillRect(screenX, bullet.y, bullet.width, bullet.height);

            context.fillStyle = '#94a3b8';
            context.fillRect(screenX + 2, bullet.y + 1, bullet.width - 4, bullet.height - 2);
        });
    }

    function drawPlayer() {
        const { player } = state;
        const screenX = worldToScreenX(player.x);
        const bodyColor = '#0f172a';
        const accentColor = '#475569';

        context.fillStyle = bodyColor;
        context.fillRect(screenX, player.y + 14, player.width, player.height - 14);

        context.fillRect(screenX + 5, player.y + player.height - 2, 8, 12);
        context.fillRect(screenX + player.width - 13, player.y + player.height - 2, 8, 12);

        context.fillRect(screenX - 6, player.y + 18, 8, 24);
        context.fillRect(screenX + player.width - 2, player.y + 18, 8, 24);

        context.beginPath();
        context.arc(screenX + player.width / 2, player.y + 10, 12, 0, Math.PI * 2);
        context.fill();

        context.fillStyle = accentColor;
        const eyeX = player.facing === 'right' ? screenX + 23 : screenX + 11;
        context.fillRect(eyeX, player.y + 8, 4, 4);
    }

    function drawControlsHint() {
        context.fillStyle = 'rgba(15, 23, 42, 0.78)';
        context.font = '600 16px Figtree, sans-serif';
        context.fillText('Arrows move. Space shoots. Reach the finish on the far right.', 28, 34);

        context.fillStyle = 'rgba(71, 85, 105, 0.92)';
        context.font = '500 13px Figtree, sans-serif';
        context.fillText('You have three lives. Restart from the overlays after win or loss.', 28, 56);
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

        restartGame();
    }

    window.addEventListener('keydown', handleKeyDown);
    window.addEventListener('keyup', handleKeyUp);

    startButton?.addEventListener('click', function () {
        startGame();
    });

    winRestartButton?.addEventListener('click', function () {
        restartGame();
    });

    loseRestartButton?.addEventListener('click', function () {
        restartGame();
    });

    syncLivesUI();
    syncProgressUI();
    drawScene();
})();
