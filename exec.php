<?php
/**
 * Commando - Apache Streaming Backend
 */
session_start();

if (empty($_SESSION['path'])) {
    $_SESSION['path'] = getcwd();
}
chdir($_SESSION['path']);

$params = json_decode(file_get_contents('php://input'));
$commandRaw = $params->command ?? '';

// If it's just a path request
if ($commandRaw === '__PWD__') {
    header('Content-Type: application/json');
    echo json_encode(['path' => $_SESSION['path']]);
    exit;
}

header('Content-Type: application/octet-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
ob_implicit_flush(1);

if (!empty($commandRaw)) {
    $command = explode(' ', $commandRaw);
    
    // Handle CD internally since it only affects the PHP process
    if ($command[0] == 'cd') {
        if (!empty($command[1])) {
            $target = $command[1];
            if (chdir($target)) {
                $_SESSION['path'] = getcwd();
            } else {
                echo "cd: no such file or directory: $target\r\n";
            }
        }
        exit;
    }

    $descriptorspec = [
       0 => ["pipe", "r"],  // stdin
       1 => ["pipe", "w"],  // stdout
       2 => ["pipe", "w"]   // stderr
    ];

    // Windows uses bypass shell, Linux uses standard execution
    $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    if ($isWindows && strpos($commandRaw, 'cmd') === false) {
        // Force UTF-8 code page before running the command
        $commandRaw = 'cmd /c "chcp 65001 >nul & ' . $commandRaw . '"';
    }

    $process = proc_open($commandRaw, $descriptorspec, $pipes, $_SESSION['path']);

    if (is_resource($process)) {
        fclose($pipes[0]); // We aren't writing to it
        
        stream_set_blocking($pipes[1], 0);
        stream_set_blocking($pipes[2], 0);

        while (true) {
            $read = [$pipes[1], $pipes[2]];
            $write = null;
            $except = null;
            
            // Wait up to 1 second for stream data
            $num_changed_streams = @stream_select($read, $write, $except, 1);
            
            if ($num_changed_streams === false) {
                break; // Error
            } elseif ($num_changed_streams > 0) {
                foreach ($read as $stream) {
                    $chunk = fread($stream, 4096);
                    if ($chunk !== false && strlen($chunk) > 0) {
                        // Normalize newlines for xterm.js
                        $chunk = str_replace("\r\n", "\n", $chunk);
                        $chunk = str_replace("\n", "\r\n", $chunk);
                        echo $chunk;
                        @ob_flush();
                        @flush();
                    }
                }
            }
            
            $status = proc_get_status($process);
            // If process ended and no more data in pipes
            if (!$status['running'] && feof($pipes[1]) && feof($pipes[2])) {
                break;
            }
        }
        
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);
    } else {
        echo "Failed to execute command.\r\n";
    }
}
