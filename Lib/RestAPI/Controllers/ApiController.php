<?php
/**
 * Copyright (C) MIKO LLC - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Nikolay Beketov, 4 2020
 *
 */

namespace Modules\ModuleConnectorFMC\Lib\RestAPI\Controllers;

use MikoPBX\Core\System\SystemMessages;
use MikoPBX\PBXCoreREST\Controllers\Modules\ModulesControllerBase;
use Modules\ModuleConnectorFMC\Lib\ConnectorFMCConf;
use Modules\ModuleConnectorFMC\Lib\Manager;

class ApiController extends ModulesControllerBase
{
    /**
     * curl -X POST -d '{"crmId":80001}' http://127.0.0.1/pbxcore/api/module-connector-fmc/on-call-answer
     * // Задача с параметрами:
     *
     */
    public function onCallAnswer():void
    {
        $postData   = $this->request->getPost();
        SystemMessages::sysLogMsg("B24_REST", json_encode($_SERVER['REMOTE_ADDR']));
        if($postData['event'] === 'ONVOXIMPLANTCALLSTART'){
            $cid = $postData['data']['CALL_ID']??'';
            $callDataFile = ConnectorFMCConf::getCallDir()."/$cid";
            if(file_exists($callDataFile)){
                $call = json_decode(file_get_contents($callDataFile), true);
                if(!empty($call['SRC_CHAN']) && !empty($call['DST_CHAN'])){
                    $am = new Manager();
                    $am->connect('127.0.0.1:'.ConnectorFMCConf::getAmiPort());
                    $commandParams = [
                        'Channel'      => $call['SRC_CHAN'],
                        'ExtraChannel' => $call['DST_CHAN'],
                        'Exten'        => 's',
                        'Context'      => "orig-src-bridge",
                        'Priority'     => '1',
                        'ExtraExten'   => 'h',
                        'ExtraContext' => "orig-src-bridge",
                        'ExtraPriority'=> '1',
                    ];
                    $am->sendRequest('Redirect',$commandParams);
                }
                unlink($call);
            }
        }
        $this->response->sendRaw();
    }
}