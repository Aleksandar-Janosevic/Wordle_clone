<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="description" content="ромчко - Погоди реч у 6 покушаја">
    <meta name="theme-color" content="#18181b">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>ромчко</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        *, *::before, *::after {
            -webkit-tap-highlight-color: transparent;
            touch-action: manipulation;
        }
        html, body {
            overflow: hidden;
            position: fixed;
            width: 100%;
            height: 100%;
            overscroll-behavior: none;
        }
        @keyframes flip {
            0% { transform: rotateX(0); }
            50% { transform: rotateX(90deg); }
            100% { transform: rotateX(0); }
        }
        .animate-flip { animation: flip 0.5s ease forwards; }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-4px); }
            40%, 80% { transform: translateX(4px); }
        }
        .shake { animation: shake 0.3s ease; }
        @keyframes pop {
            0% { transform: scale(1); }
            50% { transform: scale(1.08); }
            100% { transform: scale(1); }
        }
        .pop { animation: pop 0.08s ease; }
        @keyframes bounce {
            0%, 20% { transform: translateY(0); }
            40% { transform: translateY(-12px); }
            50% { transform: translateY(3px); }
            60% { transform: translateY(-6px); }
            80% { transform: translateY(2px); }
            100% { transform: translateY(0); }
        }
        .bounce { animation: bounce 0.7s ease; }
        .tile {
            width: clamp(48px, 14vw, 62px);
            height: clamp(48px, 14vw, 62px);
            font-size: clamp(1.25rem, 5vw, 2rem);
        }
        .key {
            height: clamp(46px, 7vh, 58px);
            min-width: clamp(24px, 7.5vw, 36px);
            font-size: clamp(0.75rem, 2.8vw, 1rem);
            transition: transform 0.1s ease, background-color 0.15s ease;
        }
        .key:active {
            transform: scale(0.92);
        }
        .key-wide {
            min-width: clamp(40px, 12vw, 60px);
        }
    </style>
</head>
<body class="bg-zinc-900 text-white select-none">
<div id="app" class="h-full flex flex-col">
    <header class="flex-none border-b border-zinc-700 px-3 py-2 flex justify-between items-center safe-area-top">
        <button onclick="showHelp()" class="w-11 h-11 flex items-center justify-center text-xl rounded-full active:bg-zinc-700 transition-colors" aria-label="Помоћ">❓</button>
        <h1 class="text-2xl sm:text-3xl font-bold tracking-wider">РОМЧКО</h1>
        <button onclick="showStats()" class="w-11 h-11 flex items-center justify-center text-xl rounded-full active:bg-zinc-700 transition-colors" aria-label="Статистика">📊</button>
    </header>

    <div id="message" class="hidden absolute top-16 left-1/2 -translate-x-1/2 bg-white text-black px-4 py-2 rounded-lg font-bold text-sm z-50 whitespace-nowrap shadow-lg"></div>

    <main class="flex-1 flex flex-col min-h-0">
        <div class="flex-1 flex items-center justify-center p-2 sm:p-4">
            <div id="board" class="grid gap-[5px]"></div>
        </div>
        <div id="keyboard" class="flex-none px-1 pb-3 sm:px-2 safe-area-bottom"></div>
    </main>
</div>

<div id="stats-modal" class="hidden fixed inset-0 bg-black/85 flex items-center justify-center z-50 p-4">
    <div class="bg-zinc-800 p-5 rounded-2xl max-w-sm w-full max-h-[85vh] overflow-y-auto">
        <h2 class="text-lg font-bold text-center mb-4">СТАТИСТИКА</h2>
        <div class="grid grid-cols-4 gap-2 text-center mb-5">
            <div>
                <div id="stat-played" class="text-2xl sm:text-3xl font-bold">0</div>
                <div class="text-[10px] sm:text-xs text-zinc-400">Одиграно</div>
            </div>
            <div>
                <div id="stat-win" class="text-2xl sm:text-3xl font-bold">100</div>
                <div class="text-[10px] sm:text-xs text-zinc-400">% Победа</div>
            </div>
            <div>
                <div id="stat-streak" class="text-2xl sm:text-3xl font-bold">0</div>
                <div class="text-[10px] sm:text-xs text-zinc-400">Низ</div>
            </div>
            <div>
                <div id="stat-max" class="text-2xl sm:text-3xl font-bold">0</div>
                <div class="text-[10px] sm:text-xs text-zinc-400">Макс Низ</div>
            </div>
        </div>
        <h3 class="text-xs font-bold mb-2 text-zinc-400">ДИСТРИБУЦИЈА</h3>
        <div id="distribution" class="space-y-1 mb-5"></div>
        <div id="next-word" class="hidden text-center border-t border-zinc-700 pt-4">
            <div class="text-xs text-zinc-400 mb-1">СЛЕДЕЋА РЕЧ</div>
            <div id="countdown" class="text-xl sm:text-2xl font-mono">00:00:00</div>
        </div>
        <button onclick="hideStats()" class="mt-4 w-full bg-green-600 active:bg-green-700 py-3 rounded-xl font-bold transition-colors">ЗАТВОРИ</button>
    </div>
</div>

<div id="help-modal" class="hidden fixed inset-0 bg-black/85 flex items-center justify-center z-50 p-4">
    <div class="bg-zinc-800 p-5 rounded-2xl max-w-sm w-full max-h-[85vh] overflow-y-auto">
        <h2 class="text-lg font-bold text-center mb-3">КАКО СЕ ИГРА</h2>
        <p class="mb-3 text-sm">Погоди реч у 6 покушаја.</p>
        <p class="mb-3 text-sm">Свако погађање мора бити реч од 5 слова.</p>
        <div class="space-y-3 mb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-600 rounded-lg flex items-center justify-center font-bold flex-shrink-0">А</div>
                <span class="text-sm">Слово је на правом месту</span>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-yellow-500 rounded-lg flex items-center justify-center font-bold flex-shrink-0">Б</div>
                <span class="text-sm">Слово је у речи, али на погрешном месту</span>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-zinc-700 rounded-lg flex items-center justify-center font-bold flex-shrink-0">В</div>
                <span class="text-sm">Слово није у речи</span>
            </div>
        </div>
        <p class="text-xs text-zinc-400">Нова реч сваких 24 сата!</p>
        <button onclick="hideHelp()" class="mt-4 w-full bg-green-600 active:bg-green-700 py-3 rounded-xl font-bold transition-colors">ИГРАЈ</button>
    </div>
</div>

<script>
    const THE_WORD = 'бобан';
    const WORD_LENGTH = 5;
    const MAX_GUESSES = 6;
    const KEYBOARD_ROWS = [
        ['љ', 'њ', 'е', 'р', 'т', 'з', 'у', 'и', 'о', 'п', 'ш'],
        ['а', 'с', 'д', 'ф', 'г', 'х', 'ј', 'к', 'л', 'ч', 'ћ'],
        ['enter', 'џ', 'ц', 'в', 'б', 'н', 'м', 'ђ', 'ж', '⌫']
    ];

    let guesses = [];
    let currentGuess = '';
    let gameState = 'playing';
    let stats = { played: 0, won: 0, streak: 0, maxStreak: 0, dist: [0,0,0,0,0,0] };

    function getDayKey() {
        const d = new Date();
        return `${d.getFullYear()}-${d.getMonth()}-${d.getDate()}`;
    }

    function loadGame() {
        try {
            const saved = JSON.parse(localStorage.getItem('romcko-game') || '{}');
            if (saved.dayKey === getDayKey()) {
                guesses = saved.guesses || [];
                gameState = saved.gameState || 'playing';
            }
            stats = JSON.parse(localStorage.getItem('romcko-stats') || JSON.stringify(stats));
        } catch (e) {
            console.warn('Could not load game state');
        }
    }

    function saveGame() {
        try {
            localStorage.setItem('romcko-game', JSON.stringify({
                dayKey: getDayKey(),
                guesses,
                gameState
            }));
            localStorage.setItem('romcko-stats', JSON.stringify(stats));
        } catch (e) {
            console.warn('Could not save game state');
        }
    }

    function getLetterState(letter, idx) {
        if (THE_WORD[idx] === letter) return 'correct';
        if (THE_WORD.includes(letter)) return 'present';
        return 'absent';
    }

    function createBoard() {
        const board = document.getElementById('board');
        board.innerHTML = '';
        for (let row = 0; row < MAX_GUESSES; row++) {
            const rowDiv = document.createElement('div');
            rowDiv.className = 'flex gap-[5px] justify-center';
            rowDiv.id = `row-${row}`;
            for (let col = 0; col < WORD_LENGTH; col++) {
                const tile = document.createElement('div');
                tile.className = 'tile border-2 border-zinc-700 rounded-md flex items-center justify-center font-bold uppercase';
                tile.id = `tile-${row}-${col}`;
                rowDiv.appendChild(tile);
            }
            board.appendChild(rowDiv);
        }
    }

    function createKeyboard() {
        const kb = document.getElementById('keyboard');
        kb.innerHTML = '';
        KEYBOARD_ROWS.forEach(row => {
            const rowDiv = document.createElement('div');
            rowDiv.className = 'flex gap-[4px] justify-center mb-[4px]';
            row.forEach(key => {
                const btn = document.createElement('button');
                const isWide = key === 'enter' || key === '⌫';
                btn.className = `key ${isWide ? 'key-wide' : ''} flex-1 max-w-[42px] rounded-md font-bold uppercase bg-zinc-600 text-white`;
                btn.textContent = key === 'enter' ? 'УН' : key;
                btn.id = `key-${key}`;

                // Touch events for mobile
                btn.addEventListener('touchstart', (e) => {
                    e.preventDefault();
                    handleKey(key);
                }, { passive: false });

                // Click for desktop
                btn.addEventListener('click', (e) => {
                    if (e.pointerType === 'touch') return;
                    handleKey(key);
                });

                rowDiv.appendChild(btn);
            });
            kb.appendChild(rowDiv);
        });
    }

    function updateBoard() {
        for (let row = 0; row < MAX_GUESSES; row++) {
            for (let col = 0; col < WORD_LENGTH; col++) {
                const tile = document.getElementById(`tile-${row}-${col}`);
                if (!tile) continue;

                let letter = '';
                let stateClass = 'border-zinc-700';

                if (row < guesses.length) {
                    letter = guesses[row][col];
                    const state = getLetterState(letter, col);
                    stateClass = state === 'correct' ? 'bg-green-600 border-green-600 text-white' :
                        state === 'present' ? 'bg-yellow-500 border-yellow-500 text-white' :
                            'bg-zinc-700 border-zinc-700 text-white';
                } else if (row === guesses.length) {
                    letter = currentGuess[col] || '';
                    if (letter) stateClass = 'border-zinc-500 text-white';
                }

                tile.textContent = letter;
                tile.className = `tile border-2 rounded-md flex items-center justify-center font-bold uppercase transition-colors duration-200 ${stateClass}`;
            }
        }
    }

    function updateKeyboard() {
        const keyStates = {};
        guesses.forEach(guess => {
            for (let i = 0; i < guess.length; i++) {
                const letter = guess[i];
                const state = getLetterState(letter, i);
                if (state === 'correct') keyStates[letter] = 'correct';
                else if (state === 'present' && keyStates[letter] !== 'correct') keyStates[letter] = 'present';
                else if (!keyStates[letter]) keyStates[letter] = 'absent';
            }
        });

        KEYBOARD_ROWS.flat().forEach(key => {
            if (key === 'enter' || key === '⌫') return;
            const btn = document.getElementById(`key-${key}`);
            if (!btn) return;

            const state = keyStates[key];
            const isWide = btn.classList.contains('key-wide');
            let bgClass = 'bg-zinc-600 text-white';

            if (state === 'correct') bgClass = 'bg-green-600 text-white';
            else if (state === 'present') bgClass = 'bg-yellow-500 text-white';
            else if (state === 'absent') bgClass = 'bg-zinc-800 text-zinc-500';

            btn.className = `key ${isWide ? 'key-wide' : ''} flex-1 max-w-[42px] rounded-md font-bold uppercase ${bgClass}`;
        });
    }

    function showMessage(msg, duration = 1200) {
        const el = document.getElementById('message');
        el.textContent = msg;
        el.classList.remove('hidden');
        setTimeout(() => el.classList.add('hidden'), duration);
    }

    function revealRow(row, callback) {
        const guess = guesses[row];
        let i = 0;
        const interval = setInterval(() => {
            const tile = document.getElementById(`tile-${row}-${i}`);
            if (!tile) return;

            tile.classList.add('animate-flip');
            const state = getLetterState(guess[i], i);
            setTimeout(() => {
                tile.className = `tile border-2 rounded-md flex items-center justify-center font-bold uppercase ${
                    state === 'correct' ? 'bg-green-600 border-green-600' :
                        state === 'present' ? 'bg-yellow-500 border-yellow-500' :
                            'bg-zinc-700 border-zinc-700'
                } text-white`;
            }, 200);
            i++;
            if (i >= WORD_LENGTH) {
                clearInterval(interval);
                setTimeout(() => {
                    updateKeyboard();
                    if (callback) callback();
                }, 250);
            }
        }, 250);
    }

    function bounceRow(row) {
        for (let i = 0; i < WORD_LENGTH; i++) {
            const tile = document.getElementById(`tile-${row}-${i}`);
            if (tile) {
                setTimeout(() => tile.classList.add('bounce'), i * 80);
            }
        }
    }

    function handleKey(key) {
        if (gameState !== 'playing') return;

        if (key === 'enter') {
            if (currentGuess.length !== WORD_LENGTH) {
                const row = document.getElementById(`row-${guesses.length}`);
                if (row) {
                    row.classList.add('shake');
                    setTimeout(() => row.classList.remove('shake'), 400);
                }
                showMessage('Недовољно слова');
                return;
            }

            if (currentGuess !== THE_WORD) {
                const row = document.getElementById(`row-${guesses.length}`);
                if (row) {
                    row.classList.add('shake');
                    setTimeout(() => row.classList.remove('shake'), 400);
                }
                showMessage('Није у речнику');
                return;
            }

            guesses.push(currentGuess);
            const row = guesses.length - 1;
            currentGuess = '';

            revealRow(row, () => {
                gameState = 'won';
                stats.played++;
                stats.won++;
                stats.streak++;
                stats.maxStreak = Math.max(stats.maxStreak, stats.streak);
                stats.dist[row]++;
                saveGame();
                bounceRow(row);
                setTimeout(() => showStats(), 1200);
            });
        } else if (key === '⌫') {
            currentGuess = currentGuess.slice(0, -1);
            updateBoard();
        } else if (currentGuess.length < WORD_LENGTH && /^[а-яљњћђџшчж]$/i.test(key)) {
            currentGuess += key.toLowerCase();
            updateBoard();
            const tile = document.getElementById(`tile-${guesses.length}-${currentGuess.length - 1}`);
            if (tile) {
                tile.classList.add('pop');
                setTimeout(() => tile.classList.remove('pop'), 80);
            }
        }
    }

    function showStats() {
        document.getElementById('stat-played').textContent = stats.played;
        document.getElementById('stat-win').textContent = stats.played ? Math.round(stats.won / stats.played * 100) : 100;
        document.getElementById('stat-streak').textContent = stats.streak;
        document.getElementById('stat-max').textContent = stats.maxStreak;

        const distEl = document.getElementById('distribution');
        distEl.innerHTML = '';
        const maxDist = Math.max(...stats.dist, 1);
        stats.dist.forEach((count, i) => {
            distEl.innerHTML += `
                    <div class="flex items-center gap-2">
                        <span class="w-3 text-xs">${i + 1}</span>
                        <div class="bg-zinc-600 h-5 flex items-center justify-end px-2 text-xs font-bold rounded" style="width: ${Math.max(10, count / maxDist * 100)}%">${count}</div>
                    </div>
                `;
        });

        if (gameState !== 'playing') {
            document.getElementById('next-word').classList.remove('hidden');
            updateCountdown();
        } else {
            document.getElementById('next-word').classList.add('hidden');
        }

        document.getElementById('stats-modal').classList.remove('hidden');
    }

    function hideStats() { document.getElementById('stats-modal').classList.add('hidden'); }
    function showHelp() { document.getElementById('help-modal').classList.remove('hidden'); }
    function hideHelp() { document.getElementById('help-modal').classList.add('hidden'); }

    function updateCountdown() {
        const now = new Date();
        const next = new Date(now);
        next.setHours(24, 0, 0, 0);
        const diff = next - now;
        const h = Math.floor(diff / 3600000).toString().padStart(2, '0');
        const m = Math.floor((diff % 3600000) / 60000).toString().padStart(2, '0');
        const s = Math.floor((diff % 60000) / 1000).toString().padStart(2, '0');
        document.getElementById('countdown').textContent = `${h}:${m}:${s}`;
    }

    // Keyboard input for desktop
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') handleKey('enter');
        else if (e.key === 'Backspace') handleKey('⌫');
        else handleKey(e.key);
    });

    // Prevent zoom on double tap
    document.addEventListener('dblclick', (e) => e.preventDefault());

    // Initialize
    setInterval(updateCountdown, 1000);
    loadGame();
    createBoard();
    createKeyboard();
    updateBoard();
    updateKeyboard();

    // Show help on first visit
    try {
        if (!localStorage.getItem('romcko-visited')) {
            localStorage.setItem('romcko-visited', 'true');
            showHelp();
        }
    } catch (e) {
        showHelp();
    }
</script>
</body>
</html>
