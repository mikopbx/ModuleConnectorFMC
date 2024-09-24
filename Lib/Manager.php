<?php
/*
 * MikoPBX - free phone system for small business
 * Copyright © 2017-2024 Alexey Portnov and Nikolay Beketov
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <https://www.gnu.org/licenses/>.
 */

namespace Modules\ModuleConnectorFMC\Lib;
use MikoPBX\Core\System\SystemMessages;
use MikoPBX\Core\Asterisk\AsteriskManager;

class Manager extends AsteriskManager
{

    /**
     * Connect to Asterisk
     *
     * @param ?string $server
     * @param ?string $username
     * @param ?string $secret
     * @param string $events
     *
     * @return bool true on success
     * @example examples/sip_show_peer.php Get information about a sip peer
     *
     */
    public function connect($server = null, $username = null, $secret = null, $events = 'on'): bool
    {
        $this->listenEvents = $events;
        // use config if not specified
        if (is_null($server)) {
            $server = $this->config['asmanager']['server'];
        }
        if (is_null($username)) {
            $username = $this->config['asmanager']['username'];
        }
        if (is_null($secret)) {
            $secret = $this->config['asmanager']['secret'];
        }

        // get port from server if specified
        if (strpos($server, ':') !== false) {
            $c            = explode(':', $server);
            $this->server = $c[0];
            $this->port   = (int)$c[1];
        } else {
            $this->server = $server;
            $this->port   = $this->config['asmanager']['port'];
        }

        // connect the socket
        $errno   = $errStr = null;
        $timeout = 2;
        try {
            $this->socket = fsockopen($this->server, $this->port, $errno, $errStr, $timeout);
        }catch (\Throwable $e){
            SystemMessages::sysLogMsg('AMI', "Exceptions, Unable to connect to manager $server ($errno): $errStr", LOG_ERR);
            return false;
        }
        if ($this->socket === false) {
            SystemMessages::sysLogMsg('AMI', "Unable to connect to manager $server ($errno): $errStr", LOG_ERR);
            return false;
        }
        stream_set_timeout($this->socket, 1, 0);

        // read the header
        $str = $this->getStringDataFromSocket();
        if ($str === '') {
            // a problem.
            SystemMessages::sysLogMsg('AMI', "Asterisk Manager header not received.", LOG_ERR);
            return false;
        }

        // login
        $res = $this->sendRequest('login', ['Username' => $username, 'Secret' => $secret, 'Events' => $events]);
        if ($res['Response'] !== 'Success') {
            $this->_loggedIn = false;
            SystemMessages::sysLogMsg('AMI', "Failed to login.", LOG_ERR);
            $this->disconnect();
            return false;
        }
        $this->_loggedIn = true;

        return true;
    }

    /**
     * Get string data from the socket response.
     *
     * @return string The string data from the socket response.
     */
    private function getStringDataFromSocket() {
        $response = $this->getDataFromSocket();
        return $response['data'] ?? '';
    }

    /**
     * Get data from the socket.
     *
     * @return array An array containing the data from the socket response or an error message.
     */
    private function getDataFromSocket() {
        $response = [];
        if(!is_resource($this->socket)){
            $response['error'] = 'Socket not init.';
            return $response;
        }
        try {
            $resultFgets = fgets($this->socket, 4096);
            if($resultFgets !== false){
                $buffer = trim($resultFgets);
                $response['data']  = $buffer;
            }else{
                $response['error'] = 'Read data error.';
            }

        }catch (\Throwable $e){
            $response['error'] = $e->getMessage();
        }

        return $response;
    }
}