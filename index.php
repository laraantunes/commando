<!DOCTYPE html>
<html>
<head>
    <title>Commando</title>
    <meta charset="utf-8" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/xterm@5.3.0/css/xterm.css" />
    <style>
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            background-color: #000;
            overflow: hidden;
        }
        #terminal {
            width: 100%;
            height: 100%;
            padding: 10px;
            box-sizing: border-box;
        }
    </style>
</head>
<body>
    <div id="terminal"></div>

    <script src="https://cdn.jsdelivr.net/npm/xterm@5.3.0/lib/xterm.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/xterm-addon-fit@0.8.0/lib/xterm-addon-fit.js"></script>

    <script>
        const term = new Terminal({
            cursorBlink: true,
            theme: {
                background: '#000000',
                foreground: '#bb51cc',
                cursor: '#bb51cc',
                cursorAccent: '#000000',
                selection: 'rgba(187, 81, 204, 0.3)'
            },
            fontFamily: "'PT Mono', monospace"
        });
        
        const fitAddon = new FitAddon.FitAddon();
        term.loadAddon(fitAddon);
        term.open(document.getElementById('terminal'));
        fitAddon.fit();

        window.addEventListener('resize', () => { fitAddon.fit(); });

        term.writeln('   ,--.');
        term.writeln('  |__**|');
        term.writeln('  |//  |');
        term.writeln('  /o|__|  [Commando Web Terminal]');
        term.writeln('');

        let currentPath = '';
        let currentInput = '';
        let isExecuting = false;
        let commandHistory = [];
        let historyIndex = -1;

        async function updatePrompt() {
            try {
                const res = await fetch('exec.php', {
                    method: 'POST',
                    body: JSON.stringify({ command: '__PWD__' }),
                    headers: { 'Content-Type': 'application/json' }
                });
                const data = await res.json();
                currentPath = data.path;
            } catch (e) {
                currentPath = '~';
            }
        }

        function printPrompt() {
            term.write('\x1b[32m[' + currentPath + ']\x1b[0m $ ');
        }

        async function executeCommand(cmd) {
            if (cmd.trim() === 'clear') {
                term.clear();
                printPrompt();
                return;
            }
            if (cmd.trim() === '') {
                printPrompt();
                return;
            }

            isExecuting = true;
            try {
                const response = await fetch('exec.php', {
                    method: 'POST',
                    body: JSON.stringify({ command: cmd }),
                    headers: { 'Content-Type': 'application/json' }
                });
                
                const reader = response.body.getReader();
                const decoder = new TextDecoder('utf-8');
                
                while (true) {
                    const { done, value } = await reader.read();
                    if (done) break;
                    term.write(decoder.decode(value));
                }
            } catch (err) {
                term.writeln('\r\n\x1b[31mError executing command.\x1b[0m');
            }
            
            await updatePrompt();
            isExecuting = false;
            printPrompt();
        }

        // Initialize
        updatePrompt().then(() => {
            printPrompt();
        });

        // Handle user input
        term.onKey(e => {
            if (isExecuting) return; // Prevent typing while command is running

            // Prevent treating arrow keys as printable characters
            const printable = !e.domEvent.altKey && !e.domEvent.altGraphKey && !e.domEvent.ctrlKey && !e.domEvent.metaKey && e.domEvent.keyCode !== 38 && e.domEvent.keyCode !== 40;
            
            if (e.domEvent.keyCode === 13) { // Enter
                term.write('\r\n');
                
                if (currentInput.trim() !== '' && currentInput.trim() !== 'clear') {
                    commandHistory.push(currentInput.trim());
                }
                historyIndex = commandHistory.length;

                executeCommand(currentInput);
                currentInput = '';
            } else if (e.domEvent.keyCode === 8) { // Backspace
                if (currentInput.length > 0) {
                    currentInput = currentInput.slice(0, -1);
                    term.write('\b \b');
                }
            } else if (e.domEvent.keyCode === 38) { // Up Arrow
                if (historyIndex > 0) {
                    historyIndex--;
                    while (currentInput.length > 0) {
                        term.write('\b \b');
                        currentInput = currentInput.slice(0, -1);
                    }
                    currentInput = commandHistory[historyIndex];
                    term.write(currentInput);
                }
            } else if (e.domEvent.keyCode === 40) { // Down Arrow
                if (historyIndex < commandHistory.length - 1) {
                    historyIndex++;
                    while (currentInput.length > 0) {
                        term.write('\b \b');
                        currentInput = currentInput.slice(0, -1);
                    }
                    currentInput = commandHistory[historyIndex];
                    term.write(currentInput);
                } else if (historyIndex === commandHistory.length - 1) {
                    historyIndex++;
                    while (currentInput.length > 0) {
                        term.write('\b \b');
                        currentInput = currentInput.slice(0, -1);
                    }
                }
            } else if (printable) {
                currentInput += e.key;
                term.write(e.key);
            }
        });
    </script>
</body>
</html>
