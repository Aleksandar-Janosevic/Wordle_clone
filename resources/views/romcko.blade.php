<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="ромчко - Погоди реч у 6 покушаја">
    <title>ромчко</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes flip {
            0% { transform: rotateX(0); }
            50% { transform: rotateX(90deg); }
            100% { transform: rotateX(0); }
        }
        .animate-flip { animation: flip 0.6s ease forwards; }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-5px); }
            40%, 80% { transform: translateX(5px); }
        }
        .shake { animation: shake 0.4s ease; }
        @keyframes pop {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        .pop { animation: pop 0.1s ease; }
        @keyframes bounce {
            0%, 20% { transform: translateY(0); }
            40% { transform: translateY(-20px); }
            50% { transform: translateY(5px); }
            60% { transform: translateY(-10px); }
            80% { transform: translateY(2px); }
            100% { transform: translateY(0); }
        }
        .bounce { animation: bounce 1s ease; }
    </style>
</head>
<body class="min-h-screen bg-zinc-900 text-white">
    <div id="app" class="min-h-screen flex flex-col">
        <header class="border-b border-zinc-700 p-4 flex justify-between items-center">
            <button onclick="showHelp()" class="text-xl">❓</button>
            <h1 class="text-3xl font-bold tracking-wider">РОМЧКО</h1>
            <button onclick="showStats()" class="text-xl">📊</button>
        </header>

        <div id="message" class="hidden absolute top-20 left-1/2 -translate-x-1/2 bg-white text-black px-4 py-2 rounded font-bold z-50"></div>

        <main class="flex-1 flex flex-col items-center justify-center gap-6 p-4">
            <div id="board" class="grid gap-1"></div>
            <div id="keyboard" class="flex flex-col gap-1"></div>
        </main>
    </div>

    <div id="stats-modal" class="hidden fixed inset-0 bg-black/80 flex items-center justify-center z-50">
        <div class="bg-zinc-800 p-6 rounded-lg max-w-sm w-full mx-4">
            <h2 class="text-xl font-bold text-center mb-4">СТАТИСТИКА</h2>
            <div class="grid grid-cols-4 gap-2 text-center mb-6">
                <div><div id="stat-played" class="text-3xl font-bold">0</div><div class="text-xs">Одиграно</div></div>
                <div><div id="stat-win" class="text-3xl font-bold">100</div><div class="text-xs">% Победа</div></div>
                <div><div id="stat-streak" class="text-3xl font-bold">0</div><div class="text-xs">Низ</div></div>
                <div><div id="stat-max" class="text-3xl font-bold">0</div><div class="text-xs">Макс Низ</div></div>
            </div>
            <h3 class="text-sm font-bold mb-2">ДИСТРИБУЦИЈА</h3>
            <div id="distribution" class="space-y-1 mb-6"></div>
            <div id="next-word" class="hidden text-center border-t border-zinc-700 pt-4">
                <div class="text-sm mb-1">СЛЕДЕЋА РЕЧ</div>
                <div id="countdown" class="text-2xl font-mono">00:00:00</div>
            </div>
            <button onclick="hideStats()" class="mt-4 w-full bg-green-600 py-2 rounded font-bold">ЗАТВОРИ</button>
        </div>
    </div>

    <div id="help-modal" class="hidden fixed inset-0 bg-black/80 flex items-center justify-center z-50">
        <div class="bg-zinc-800 p-6 rounded-lg max-w-sm w-full mx-4">
            <h2 class="text-xl font-bold text-center mb-4">КАКО СЕ ИГРА</h2>
            <p class="mb-4">Погоди реч у 6 покушаја.</p>
            <p class="mb-4">Свако погађање мора бити реч од 5 слова.</p>
            <div class="space-y-2 mb-4">
                <div class="flex items-center gap-2">
                    <div class="w-10 h-10 bg-green-600 flex items-center justify-center font-bold">А</div>
                    <span>Слово је на правом месту</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-10 h-10 bg-yellow-500 flex items-center justify-center font-bold">Б</div>
                    <span>Слово је у речи, али на погрешном месту</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-10 h-10 bg-zinc-700 flex items-center justify-center font-bold">В</div>
                    <span>Слово није у речи</span>
                </div>
            </div>
            <p class="text-sm text-zinc-400">Нова реч сваких 24 сата!</p>
            <button onclick="hideHelp()" class="mt-4 w-full bg-green-600 py-2 rounded font-bold">ИГРАЈ</button>
        </div>
    </div>

    <script>
        const THE_WORD = 'циган';
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
            const saved = JSON.parse(localStorage.getItem('romcko-game') || '{}');
            if (saved.dayKey === getDayKey()) {
                guesses = saved.guesses || [];
                gameState = saved.gameState || 'playing';
            }
            stats = JSON.parse(localStorage.getItem('romcko-stats') || JSON.stringify(stats));
        }

        function saveGame() {
            localStorage.setItem('romcko-game', JSON.stringify({
                dayKey: getDayKey(),
                guesses,
                gameState
            }));
            localStorage.setItem('romcko-stats', JSON.stringify(stats));
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
                rowDiv.className = 'flex gap-1';
                rowDiv.id = `row-${row}`;
                for (let col = 0; col < WORD_LENGTH; col++) {
                    const tile = document.createElement('div');
                    tile.className = 'w-14 h-14 border-2 border-zinc-700 flex items-center justify-center text-2xl font-bold uppercase';
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
                rowDiv.className = 'flex gap-1 justify-center';
                row.forEach(key => {
                    const btn = document.createElement('button');
                    const isWide = key === 'enter' || key === '⌫';
                    btn.className = `${isWide ? 'px-3' : 'w-8'} h-12 rounded font-bold text-sm uppercase bg-zinc-600 text-white hover:bg-zinc-500 transition-colors`;
                    btn.textContent = key === 'enter' ? 'УН' : key;
                    btn.id = `key-${key}`;
                    btn.onclick = () => handleKey(key);
                    rowDiv.appendChild(btn);
                });
                kb.appendChild(rowDiv);
            });
        }

        function updateBoard() {
            for (let row = 0; row < MAX_GUESSES; row++) {
                for (let col = 0; col < WORD_LENGTH; col++) {
                    const tile = document.getElementById(`tile-${row}-${col}`);
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
                    tile.className = `w-14 h-14 border-2 flex items-center justify-center text-2xl font-bold uppercase transition-all duration-300 ${stateClass}`;
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
                const state = keyStates[key];
                btn.className = btn.className.replace(/bg-\w+-\d+/g, '');
                if (state === 'correct') btn.className = btn.className + ' bg-green-600';
                else if (state === 'present') btn.className = btn.className + ' bg-yellow-500';
                else if (state === 'absent') btn.className = btn.className + ' bg-zinc-800 text-zinc-500';
                else btn.className = btn.className + ' bg-zinc-600';
            });
        }

        function showMessage(msg, duration = 1500) {
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
                tile.classList.add('animate-flip');
                const state = getLetterState(guess[i], i);
                setTimeout(() => {
                    tile.className = `w-14 h-14 border-2 flex items-center justify-center text-2xl font-bold uppercase ${
                        state === 'correct' ? 'bg-green-600 border-green-600' :
                        state === 'present' ? 'bg-yellow-500 border-yellow-500' :
                        'bg-zinc-700 border-zinc-700'
                    } text-white`;
                }, 250);
                i++;
                if (i >= WORD_LENGTH) {
                    clearInterval(interval);
                    setTimeout(() => {
                        updateKeyboard();
                        if (callback) callback();
                    }, 300);
                }
            }, 300);
        }

        function bounceRow(row) {
            for (let i = 0; i < WORD_LENGTH; i++) {
                const tile = document.getElementById(`tile-${row}-${i}`);
                setTimeout(() => {
                    tile.classList.add('bounce');
                }, i * 100);
            }
        }

        function handleKey(key) {
            if (gameState !== 'playing') return;
            
            if (key === 'enter') {
                if (currentGuess.length !== WORD_LENGTH) {
                    document.getElementById(`row-${guesses.length}`).classList.add('shake');
                    setTimeout(() => document.getElementById(`row-${guesses.length}`).classList.remove('shake'), 500);
                    showMessage('Недовољно слова');
                    return;
                }
                
                // THE GIMMICK: Only "циган" is a valid word!
                if (currentGuess !== THE_WORD) {
                    document.getElementById(`row-${guesses.length}`).classList.add('shake');
                    setTimeout(() => document.getElementById(`row-${guesses.length}`).classList.remove('shake'), 500);
                    showMessage('Није у речнику');
                    return;
                }
                
                guesses.push(currentGuess);
                const row = guesses.length - 1;
                currentGuess = '';
                
                revealRow(row, () => {
                    // Always wins since only valid word is the answer!
                    gameState = 'won';
                    stats.played++;
                    stats.won++;
                    stats.streak++;
                    stats.maxStreak = Math.max(stats.maxStreak, stats.streak);
                    stats.dist[row]++;
                    saveGame();
                    bounceRow(row);
                    setTimeout(() => showStats(), 1500);
                });
            } else if (key === '⌫') {
                currentGuess = currentGuess.slice(0, -1);
                updateBoard();
            } else if (currentGuess.length < WORD_LENGTH && /^[а-яљњћђџшчж]$/i.test(key)) {
                currentGuess += key.toLowerCase();
                updateBoard();
                const tile = document.getElementById(`tile-${guesses.length}-${currentGuess.length - 1}`);
                tile.classList.add('pop');
                setTimeout(() => tile.classList.remove('pop'), 100);
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
                        <span class="w-3">${i + 1}</span>
                        <div class="bg-zinc-600 h-5 flex items-center justify-end px-2 text-xs font-bold" style="width: ${Math.max(8, count / maxDist * 100)}%">${count}</div>
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

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') handleKey('enter');
            else if (e.key === 'Backspace') handleKey('⌫');
            else handleKey(e.key);
        });

        setInterval(updateCountdown, 1000);
        loadGame();
        createBoard();
        createKeyboard();
        updateBoard();
        updateKeyboard();
        
        if (!localStorage.getItem('romcko-visited')) {
            localStorage.setItem('romcko-visited', 'true');
            showHelp();
        }
    </script>
</body>
</html>