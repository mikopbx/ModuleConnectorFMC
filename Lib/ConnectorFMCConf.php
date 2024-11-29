<?php
/**
 * Copyright © MIKO LLC - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Alexey Portnov, 12 2019
 */

namespace Modules\ModuleConnectorFMC\Lib;

use MikoPBX\Common\Models\PbxSettings;
use MikoPBX\Common\Models\PbxSettingsConstants;
use MikoPBX\Common\Models\Sip;
use MikoPBX\Core\System\Directories;
use MikoPBX\Core\System\PBX;
use MikoPBX\Core\System\Processes;
use MikoPBX\Core\System\Util;
use MikoPBX\Modules\Config\ConfigClass;
use Modules\ModuleConnectorFMC\Lib\RestAPI\Controllers\ApiController;
use Modules\ModuleConnectorFMC\Models\ModuleConnectorFMC;
use Modules\ModuleConnectorFMC\Models\TrunksFMC;


class ConnectorFMCConf extends ConfigClass
{

    /**
     * Receive information about mikopbx main database changes
     *
     * @param $data
     */
    public function modelsEventChangeData($data): void
    {
        $scriptPath = $this->moduleDir.'/bin/init-asterisk.php';
        $phpPath    = Util::which('php');
        if ( $data['model'] === PbxSettings::class && $data['recordId'] === PbxSettingsConstants::SIP_PORT ) {
            shell_exec("$phpPath -f $scriptPath");
        }elseif ( $data['model'] === Sip::class ) {
            shell_exec("$phpPath -f $scriptPath");
        }elseif ( $data['model'] === ModuleConnectorFMC::class && in_array($data['recordId'],['rtpPortStart', 'sipPort', 'rtpPortEnd'], true)) {
            shell_exec("$phpPath -f $scriptPath restart");
            PBX::sipReload();
            PBX::dialplanReload();
        }elseif ($data['model'] === ModuleConnectorFMC::class || $data['model'] === TrunksFMC::class){
            shell_exec("$phpPath -f $scriptPath");
            PBX::sipReload();
            PBX::dialplanReload();
        }
    }

    /**
     * Prepares additional contexts sections in the extensions.conf file
     * @see https://docs.mikopbx.com/mikopbx-development/module-developement/module-class#extensiongencontexts
     *
     * @return string
     */
    public function extensionGenContexts(): string
    {
        $conf = '';
        $settings = ModuleConnectorFMC::findFirst(['columns' => 'sipPort']);
        if(!$settings){
            return '';
        }
        $trunks = TrunksFMC::find(['columns' => 'outputEndpoint AS id,providerType'])->toArray();
        foreach ($trunks as $trunk){
            if(intval($trunk['providerType']) === TrunksFMC::PROVIDER_TYPE_B24){
                $conf.= '['.$trunk['id'].'-incoming]'.PHP_EOL.
                        'exten => _.!,1,NoOp()'.PHP_EOL.
                        '    same => n,Set(callId=${PJSIP_HEADER(read,X-Extension-Number)})'.PHP_EOL.
                        '    same => n,Set(CALLERID(num)=${callId})'.PHP_EOL.
                        '    same => n,Set(CALLERID(name)=${callId})'.PHP_EOL.
                        '    same => n,Dial(PJSIP/'.$trunk['id'].'/sip:${EXTEN}@127.0.0.1:'.$settings->sipPort.',,f(${callId} <${callId}>))'.PHP_EOL.
                        'exten => _[hit],1,Hangup()';
            }elseif (intval($trunk['providerType']) === TrunksFMC::PROVIDER_TYPE_MCN) {
                $conf .= '['.$trunk['id'].'-incoming]'.PHP_EOL;
                $conf .= 'exten => _X!,1,NoOp(--- Incoming call ---)'.PHP_EOL;
                $conf .= '    same => n,Set(CHANNEL(language)=ru-ru)'.PHP_EOL;
                $conf .= '    same => n,Set(CHANNEL(hangup_handler_wipe)=hangup_handler,s,1)'.PHP_EOL;
                $conf .= '    same => n,Set(__FROM_DID=${EXTEN})'.PHP_EOL;
                $conf .= '    same => n,Set(__FROM_CHAN=${CHANNEL})'.PHP_EOL;
                $conf .= '    same => n,Set(__M_CALLID=${CHANNEL(callid)})'.PHP_EOL;
                $conf .= '    same => n,Set(__TRANSFER_OPTIONS=t)'.PHP_EOL;
                $conf .= '    same => n,Set(M_TIMEOUT=600)'.PHP_EOL;
                $conf .= '    same => n,Progress()'.PHP_EOL;
                $conf .= '    same => n,Playback(silence/1,noanswer)'.PHP_EOL;
                $conf .= '    same => n,Dial(Local/did2user@internal-incoming,600,${TRANSFER_OPTIONS}Kg)'.PHP_EOL;
                $conf .= '    same => n,Hangup()'.PHP_EOL;
            }
        }
        return $conf;
    }

    /**
     * Prepares additional peers data in the pjsip.conf file
     * @see https://docs.mikopbx.com/mikopbx-development/module-developement/module-class#generatepeerspj
     *
     * @return string
     */
    public function generatePeersPj(): string
    {
        $settings = ModuleConnectorFMC::findFirst(['columns' => 'sipPort']);
        if(!$settings){
            return '';
        }
        $trunks = TrunksFMC::find(['columns' => 'outputEndpoint AS id,outputEndpointSecret AS pass,providerType'])->toArray();
        $config = '';
        foreach ($trunks as $trunk) {
            if(intval($trunk['providerType']) === TrunksFMC::PROVIDER_TYPE_B24){

                $config.= "[".$trunk['id']."-AUTH]" . PHP_EOL .
                    "type = auth" . PHP_EOL .
                    "username = ".$trunk['id'] . PHP_EOL .
                    "password = ".$trunk['pass'] . PHP_EOL . PHP_EOL .

                    "[".$trunk['id']."]" . PHP_EOL .
                    "type = aor" . PHP_EOL .
                    "max_contacts = 5" . PHP_EOL .
                    "maximum_expiration = 3600" . PHP_EOL .
                    "minimum_expiration = 60" . PHP_EOL .
                    "default_expiration = 120" . PHP_EOL .
                    "qualify_frequency = 60" . PHP_EOL .
                    "qualify_timeout = 3.0" . PHP_EOL . PHP_EOL .

                    "[".$trunk['id']."]" . PHP_EOL .
                    "type = endpoint" . PHP_EOL .
                    "100rel = no" . PHP_EOL .
                    "context = ".$trunk['id']."-incoming" . PHP_EOL .
                    "dtmf_mode = auto" . PHP_EOL .
                    "disallow = all" . PHP_EOL .
                    "allow = opus" . PHP_EOL .
                    "allow = alaw" . PHP_EOL .
                    "allow = h264" . PHP_EOL .
                    "rtp_symmetric = yes" . PHP_EOL .
                    "force_rport = yes" . PHP_EOL .
                    "rewrite_contact = yes" . PHP_EOL .
                    "ice_support = no" . PHP_EOL .
                    "direct_media = no" . PHP_EOL .
                    "contact_user = ".$trunk['id']. PHP_EOL .
                    "sdp_session = mikopbx" . PHP_EOL .
                    "language = ru-ru" . PHP_EOL .
                    "aors = ".$trunk['id'] . PHP_EOL .
                    "timers = no" . PHP_EOL .
                    "rtp_timeout = 30" . PHP_EOL .
                    "rtp_timeout_hold = 30" . PHP_EOL .
                    "auth = ".$trunk['id']."-AUTH" . PHP_EOL .
                    "inband_progress = yes" . PHP_EOL .
                    "tone_zone = ru" . PHP_EOL;
            }elseif (intval($trunk['providerType']) === TrunksFMC::PROVIDER_TYPE_MCN) {
                $config .= "[".$trunk['id']."]" . PHP_EOL;
                $config .= 'type = identify' . PHP_EOL;
                $config .= 'endpoint = '.$trunk['id'] . PHP_EOL;
                $config .= 'match = 127.0.0.1:'.$settings->sipPort . PHP_EOL;
                $config .= PHP_EOL;

                $config .= "[".$trunk['id']."]" . PHP_EOL;
                $config .= 'type = aor' . PHP_EOL;
                $config .= 'max_contacts = 1' . PHP_EOL;
                $config .= 'maximum_expiration = 3600' . PHP_EOL;
                $config .= 'minimum_expiration = 60' . PHP_EOL;
                $config .= 'default_expiration = 120' . PHP_EOL;
                $config .= 'contact = sip:127.0.0.1:'.$settings->sipPort . PHP_EOL;
                $config .= 'qualify_frequency = 60' . PHP_EOL;
                $config .= 'qualify_timeout = 3.0' . PHP_EOL;
                $config .= PHP_EOL;

                $config .= "[".$trunk['id']."]" . PHP_EOL;
                $config .= 'type = endpoint' . PHP_EOL;
                $config .= '100rel = no' . PHP_EOL;
                $config .= "context = ".$trunk['id']."-incoming" . PHP_EOL;
                $config .= 'dtmf_mode = auto' . PHP_EOL;
                $config .= 'disallow = all' . PHP_EOL;
                $config .= 'allow = alaw' . PHP_EOL;
                $config .= 'rtp_symmetric = yes' . PHP_EOL;
                $config .= 'force_rport = yes' . PHP_EOL;
                $config .= 'rewrite_contact = yes' . PHP_EOL;
                $config .= 'ice_support = no' . PHP_EOL;
                $config .= 'direct_media = no' . PHP_EOL;
                $config .= 'from_user = ; username' . PHP_EOL;
                $config .= 'from_domain = 127.0.0.1' . PHP_EOL;
                $config .= 'contact_user = ; username' . PHP_EOL;
                $config .= 'sdp_session = mikopbx' . PHP_EOL;
                $config .= 'language = ru-ru' . PHP_EOL;
                $config .= "aors = ".$trunk['id'] . PHP_EOL;
                $config .= 'timers = no' . PHP_EOL;
                $config .= 'rtp_timeout = 30' . PHP_EOL;
                $config .= 'rtp_timeout_hold = 30' . PHP_EOL;
                $config .= 'inband_progress = yes' . PHP_EOL;
                $config .= 'tone_zone = ru' . PHP_EOL;
            }
        }
        return $config;
    }

    public function onAfterModuleEnable(): void
    {
        parent::onAfterModuleEnable();
        $scriptPath = $this->moduleDir.'/bin/init-asterisk.php';
        $phpPath    = Util::which('php');
        shell_exec("$phpPath -f $scriptPath start");
        PBX::sipReload();
        PBX::dialplanReload();
    }

    public function onAfterModuleDisable(): void
    {
        parent::onAfterModuleDisable();
        $scriptPath = $this->moduleDir.'/bin/init-asterisk.php';
        $phpPath    = Util::which('php');
        shell_exec("$phpPath -f $scriptPath stop");
        PBX::sipReload();
        PBX::dialplanReload();
    }

    public static function getLogDir():string
    {
        return Directories::getDir(Directories::CORE_LOGS_DIR) . '/ModuleConnectorFMC';
    }

    public static function getCallDir():string
    {
        return dirname(__DIR__).'/db/CALL_DATA';
    }

    public static function getB24UsersDir():string
    {
        return dirname(__DIR__).'/db/B24_USERS';
    }

    public static function getAmiPort():string
    {
        return ModuleConnectorFMC::getValueByKey('amiPort');
    }

    /**
     * Rotates the specified PBX log file.
     * @param string $fileName The name of the log file to rotate.
     */
    public static function rotatePbxLog(string $fileName = 'full'): void
    {
        $di           = MikoPBXVersion::getDefaultDi();
        $asteriskPath = Util::which('asterisk');
        if ($di === null) {
            return;
        }
        $max_size    = 10;
        $log_dir     = self::getLogDir();
        $text_config = "$log_dir$fileName {
    nocreate
    nocopytruncate
    delaycompress
    nomissingok
    start 0
    rotate 3
    size {$max_size}M
    missingok
    noolddir
    postrotate
        $asteriskPath -rx 'logger reload' > /dev/null 2> /dev/null
    endscript
}";
        $varEtcDir  = $di->getShared('config')->path('core.varEtcDir');
        $path_conf   = $varEtcDir . '/asterisk_logrotate_' . $fileName . '.conf';
        file_put_contents($path_conf, $text_config);
        $mb10 = $max_size * 1024 * 1024;

        $options = '';
        if (Util::mFileSize("$log_dir/$fileName") > $mb10) {
            $options = '-f';
        }
        $logrotatePath = Util::which('logrotate');
        Processes::mwExecBg("$logrotatePath $options '$path_conf' > /dev/null 2> /dev/null");
    }

    /**
     * @param array $tasks
     */
    public function createCronTasks(array &$tasks): void
    {
        $phpPath    = Util::which('php');
        $tasks[]    = "0 1 * * * $phpPath $this->moduleDir/bin/rotate-logs.php > /dev/null 2>&1".PHP_EOL;
    }

    /**
     * REST API модуля.
     * @return array[]
     */
    public function getPBXCoreRESTAdditionalRoutes(): array
    {
        return [
            [ApiController::class, 'onCallAnswer','/pbxcore/api/module-connector-fmc/on-call-answer', 'post', '/', true],
        ];
    }
}