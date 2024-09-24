#!/usr/bin/php
<?php
/*
 * MikoPBX - free phone system for small business
 * Copyright © 2017-2023 Alexey Portnov and Nikolay Beketov
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

use MikoPBX\Core\Asterisk\AGI;
use Modules\ModuleConnectorFMC\Lib\ConnectorFMCConf;

require_once 'Globals.php';
$agi    = new AGI();
$data = [
    'SRC_CHAN'   => $agi->get_variable('SRC_CHAN',true),
    'DST_CHAN'   => $agi->get_variable('DST_CHAN',true),
    'id'         => $agi->get_variable('CHANNEL(linkedid)',true),
    'SRC_CID'    => $agi->get_variable('SRC_CID',true),
    'SRC_DST'    => $agi->get_variable('SRC_DST',true),
    'VI_CALL_ID' => $agi->get_variable('VI_CALL_ID',true),
    'TIME' => time()
];

file_put_contents(ConnectorFMCConf::getCallDir()."/{$data['VI_CALL_ID']}", json_encode($data));