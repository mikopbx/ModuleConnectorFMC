<?php

declare(strict_types=1);

namespace MikoPBX\Core\Asterisk {
    class AsteriskManager
    {
        /** @var resource|false */
        public $socket = false;

        /** @var resource|false */
        public static $peerSocket = false;

        public function connect(
            ?string $server = null,
            ?string $username = null,
            ?string $secret = null,
            string $events = 'on'
        ): bool {
            $sockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, 0);
            if ($sockets === false) {
                return false;
            }
            [$this->socket, self::$peerSocket] = $sockets;
            return true;
        }
    }
}

namespace {
    require_once dirname(__DIR__) . '/Lib/Manager.php';

    $manager = new \Modules\ModuleConnectorFMC\Lib\Manager();
    $peerPid = -1;
    try {
        if (!$manager->connect()) {
            throw new RuntimeException('Legacy Core connection fixture failed.');
        }

        $peerPid = pcntl_fork();
        if ($peerPid === -1) {
            throw new RuntimeException('Unable to fork the timeout fixture.');
        }
        if ($peerPid === 0) {
            fclose($manager->socket);
            usleep(1500000);
            fclose(\MikoPBX\Core\Asterisk\AsteriskManager::$peerSocket);
            exit(0);
        }
        fclose(\MikoPBX\Core\Asterisk\AsteriskManager::$peerSocket);
        \MikoPBX\Core\Asterisk\AsteriskManager::$peerSocket = false;

        fgets($manager->socket);
        $metadata = stream_get_meta_data($manager->socket);
        if (($metadata['timed_out'] ?? false) !== true) {
            throw new RuntimeException('FMC socket timeout must remain one second on legacy Core.');
        }
    } catch (Throwable $throwable) {
        fwrite(STDERR, 'FAIL: ' . $throwable->getMessage() . PHP_EOL);
        exit(1);
    } finally {
        if ($peerPid > 0) {
            $status = 0;
            pcntl_waitpid($peerPid, $status);
        }
        if (is_resource($manager->socket)) {
            fclose($manager->socket);
        }
        if (is_resource(\MikoPBX\Core\Asterisk\AsteriskManager::$peerSocket)) {
            fclose(\MikoPBX\Core\Asterisk\AsteriskManager::$peerSocket);
        }
    }

    fwrite(STDOUT, 'PASS: FMC applies its socket timeout on legacy Core.' . PHP_EOL);
}
