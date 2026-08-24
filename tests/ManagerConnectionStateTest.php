<?php

declare(strict_types=1);

$coreRoot = getenv('MIKOPBX_TEST_CORE_DIR');
if (!is_string($coreRoot) || $coreRoot === '') {
    $coreRoot = dirname(__DIR__, 3) . '/mikopbx/Core';
}
require_once $coreRoot . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/Lib/Manager.php';

use Modules\ModuleConnectorFMC\Lib\Manager;

function readAmiRequest($client): array
{
    $request = [];
    while (($line = fgets($client)) !== false) {
        $line = rtrim($line, "\r\n");
        if ($line === '') {
            break;
        }
        $separator = strpos($line, ':');
        if ($separator !== false) {
            $request[substr($line, 0, $separator)] = ltrim(substr($line, $separator + 1));
        }
    }
    return $request;
}

$server = stream_socket_server('tcp://127.0.0.1:0', $errorCode, $errorMessage);
if (!is_resource($server)) {
    throw new RuntimeException($errorMessage, $errorCode);
}
$address = (string)stream_socket_get_name($server, false);
$port = (int)substr(strrchr($address, ':'), 1);

$serverPid = pcntl_fork();
if ($serverPid === -1) {
    throw new RuntimeException('Unable to fork AMI fixture.');
}
if ($serverPid === 0) {
    $client = stream_socket_accept($server, 5);
    if (!is_resource($client)) {
        exit(2);
    }
    stream_set_timeout($client, 5);
    fwrite($client, "Asterisk Call Manager/5.0\r\n");
    $login = readAmiRequest($client);
    if (($login['Action'] ?? '') !== 'login') {
        exit(3);
    }
    fwrite($client, "Response: Success\r\nMessage: Authentication accepted\r\n\r\n");
    readAmiRequest($client);
    fclose($client);
    fclose($server);
    exit(0);
}

fclose($server);
$manager = new class (null, [
    'server' => "127.0.0.1:$port",
    'username' => 'fixture',
    'secret' => 'fixture-secret',
]) extends Manager {
    protected function isAsteriskListening(): bool
    {
        return true;
    }
};

$failure = null;
try {
    if (!$manager->connect(null, null, null, 'off')) {
        throw new RuntimeException('AMI fixture login failed.');
    }
    if (!$manager->loggedIn()) {
        throw new RuntimeException('Inherited loggedIn() lost the successful child connection state.');
    }
    if (!$manager->isConnected()) {
        throw new RuntimeException('Inherited isConnected() lost the successful child connection state.');
    }
} catch (Throwable $throwable) {
    $failure = $throwable;
} finally {
    $manager->disconnect();
    $status = 0;
    pcntl_waitpid($serverPid, $status);
}

if ($failure !== null) {
    fwrite(STDERR, 'FAIL: ' . $failure->getMessage() . PHP_EOL);
    exit(1);
}
if (!pcntl_wifexited($status) || pcntl_wexitstatus($status) !== 0) {
    fwrite(STDERR, 'FAIL: AMI fixture exited abnormally.' . PHP_EOL);
    exit(1);
}

fwrite(STDOUT, "PASS: Manager preserves the authenticated Core connection state.\n");
