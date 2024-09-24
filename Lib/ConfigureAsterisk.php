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
use MikoPBX\Common\Models\PbxSettings;
use MikoPBX\Common\Models\PbxSettingsConstants;
use MikoPBX\Common\Models\Sip;
use MikoPBX\Core\System\Util;
use MikoPBX\Core\System\Processes;
use Modules\ModuleConnectorFMC\Models\ModuleConnectorFMC;
use Modules\ModuleConnectorFMC\Models\TrunksFMC;
use MikoPBX\Modules\PbxExtensionUtils;
use Phalcon\Mvc\Model\Row;

class ConfigureAsterisk
{
    public const EXTENSIONS_CONF_NAME = 'extensions';
    public const ASTERISK_CONF_NAME = 'asterisk';
    public const MANAGER_CONF_NAME = 'manager';
    public const RTP_CONF_NAME = 'rtp';
    public const PJSIP_CONF_NAME = 'pjsip';

    private Row $settings;
    private string $sipPort;
    private array $providers;
    private string $dirName;
    private string $agiDir;

    private array $configFileData = [];
    private string $asteriskConfPath;
    private string $cmdAsterisk;

    public function __construct()
    {
        $settings = ModuleConnectorFMC::findFirst(['columns' => 'sipPort,userAgent,rtpPortStart,rtpPortEnd']);
        if(!$settings){
            exit(2);
        }
        $this->settings = $settings;

        $this->agiDir = dirname(__DIR__).'/agi-bin';
        $this->dirName = dirname(__DIR__).'/Calls';
        $this->asteriskConfPath    = "$this->dirName/asterisk/asterisk.conf";
        $this->cmdAsterisk = Util::which('asterisk');
        $this->sipPort  = PbxSettings::getValueByKey(PbxSettingsConstants::SIP_PORT);
        $this->providers= TrunksFMC::find(['columns' => 'incomingEndpointHost AS host,incomingEndpointPort AS port,incomingEndpointSecret AS secret,incomingEndpointLogin AS endpoint,extensions,useDelayedResponse'])->toArray();
    }

    public function makeConfig($action):void
    {
        $pid = Processes::getPidOfProcess($this->asteriskConfPath);
        if($action === 'stop' || PbxExtensionUtils::isEnabled('ModuleConnectorFMC') !== true){
            shell_exec(Util::which('kill')." $pid");
            exit(0);
        }

        $this->makeAstConf();
        $this->makeRtp();
        $this->makePjSip();
        $this->makeExtensions();
        $this->makeManagerConf();

        foreach ($this->configFileData as $name => $config){
            $asteriskRtpConfPath    = "$this->dirName/asterisk/$name.conf";
            $oldConfig = file_get_contents($asteriskRtpConfPath);
            if($oldConfig !== $config){
                file_put_contents($asteriskRtpConfPath, $config);
                if(in_array($action, ['stop', 'restart'], true)){
                    continue;
                }
                switch ($name) {
                    case self::ASTERISK_CONF_NAME:
                        // рестарт астера
                        break;
                    case self::EXTENSIONS_CONF_NAME:
                        shell_exec("$this->cmdAsterisk -C '$this->asteriskConfPath' -rx 'dialplan reload'");
                        break;
                    case self::MANAGER_CONF_NAME:
                        shell_exec("$this->cmdAsterisk -C '$this->asteriskConfPath' -rx 'manager reload'");
                        break;
                    case self::RTP_CONF_NAME:
                        shell_exec("$this->cmdAsterisk -C '$this->asteriskConfPath' -rx 'module reload res_rtp_asterisk.so");
                        break;
                    case self::PJSIP_CONF_NAME:
                        shell_exec("$this->cmdAsterisk -C '$this->asteriskConfPath' -rx 'module reload res_pjsip.so'");
                        shell_exec("$this->cmdAsterisk -C '$this->asteriskConfPath' -rx 'pjsip send register *all'");
                        break;
                    default:
                        break;
                }
            }
        }
        if(!empty($pid) && 'restart' === $action){
            shell_exec(Util::which('kill')." $pid");
            $pid = '';
        }
        if(empty($pid)){
            shell_exec("$this->cmdAsterisk -C '$this->asteriskConfPath'");
        }
    }

    private function makeExtensions():void
    {
        $extensionsConfAdditional = '';
        $extensionsConf = "[globals]".PHP_EOL;
        $extensionsConf.= "[general]".PHP_EOL;
        $extensionsConf.= "[incoming]".PHP_EOL;
        $extensionsConf.='exten => _.!,1,Dial(PJSIP/${EXTEN}@${CALLERID(num)},600,Tt)'.PHP_EOL;
        $extensionsConf.='exten => _[hit],1,Hangup()'.PHP_EOL;
        foreach ($this->providers as $provider){
            $filterSip = [
                "type = 'peer' AND disabled <> '1' AND extension IN ({extension:array}) ",
                'columns' => 'extension,secret',
                'order' => 'extension',
                'bind' => [
                    'extension' => explode(',',$provider['extensions'])
                ]
            ];
            $peers = Sip::find($filterSip);
            foreach ($peers as $peer){
                if($provider['useDelayedResponse'] === '1'){
                    $extensionsConf.= 'exten => '.$peer->extension.',1,Goto(incoming-to-'.$provider['endpoint'].',${EXTEN},1)'.PHP_EOL;
                }else{
                     $extensionsConf.= 'exten => '.$peer->extension.',1,Dial(PJSIP/${EXTEN}@'.$provider['endpoint'].',,f(${CALLERID(num)} <'.$provider['endpoint'].'>)Tt)'.PHP_EOL;
                }
            }
            $extensionsConfAdditional.= PHP_EOL.'[incoming-to-'.$provider['endpoint'].']' . PHP_EOL .
                'exten => _X!,1,Ringing()' . PHP_EOL .
                '    same => n,Set(_SRC_CHAN=${CHANNEL})' . PHP_EOL .
                '    same => n,Set(_SRC_CID=${CALLERID(num)})' . PHP_EOL .
                '    same => n,Set(_SRC_DST=${EXTEN})' . PHP_EOL .
                '    same => n,Dial(PJSIP/${EXTEN}@'.$provider['endpoint'].',,f(${SRC_CID} <'.$provider['endpoint'].'>)Ttb(create-dst-chan,${SRC_DST},1)G(orig-call^${SRC_CID}^1))' . PHP_EOL;
        }

        $extensionsConf.=$extensionsConfAdditional;
        $extensionsConf.=
            '[orig-call]' . PHP_EOL .
            'exten => _X!,1,Goto(orig-src-chan,${EXTEN},1)' . PHP_EOL .
            'exten => _X!,2,Goto(orig-dst-chan,${EXTEN},1)' . PHP_EOL .
            '[create-dst-chan]' . PHP_EOL .
            'exten => _X!,1,Set(EXPORT(${SRC_CHAN},DST_CHAN)=${CHANNEL})' . PHP_EOL .
            '    same => n,Set(DST_CHAN=${CHANNEL})' . PHP_EOL .
            '    same => n,return' . PHP_EOL .
            '[orig-src-chan]' . PHP_EOL .
            'exten => _X!,1,NoOp(is SRC chan, DST_CHAN: ${DST_CHAN}, id=${CHANNEL(linkedid)}, SRC_CID=${SRC_CID})' . PHP_EOL .
            '    same => n,Ringing()' . PHP_EOL .
            '    same => n,Wait(600)' . PHP_EOL .
            'exten => h,1,AGI('.$this->agiDir.'/hangup.php,${DST_CHAN})' . PHP_EOL .
            PHP_EOL .
            '[orig-dst-chan]' . PHP_EOL .
            'exten => _X!,1,NoOp(is DST chan, SRC_CHAN: ${SRC_CHAN}, id=${CHANNEL(linkedid)}, SRC_CID=${SRC_CID}, SRC_DST=${SRC_DST})' . PHP_EOL .
            '    same => n,Set(VI_CALL_ID=${PJSIP_RESPONSE_HEADER(read,VI-Call-ID)})' . PHP_EOL .
            '    same => n,Set(EXPORT(${SRC_CHAN},VI_CALL_ID)=${VI_CALL_ID})' . PHP_EOL .
            '    same => n,AGI('.$this->agiDir.'/save-state.php)' . PHP_EOL .
            '    same => n,Wait(600)' . PHP_EOL .
            'exten => h,1,AGI('.$this->agiDir.'/hangup.php,${SRC_CHAN})' . PHP_EOL .
            PHP_EOL .
            '[orig-src-bridge]' . PHP_EOL .
            'exten => s,1,Answer()' . PHP_EOL .
            '    same => n,Bridge(${IF($["${CHANNEL}" == "${DST_CHAN}"]?${SRC_CHAN}:${DST_CHAN})},Tt)'.PHP_EOL.
            'exten => h,1,AGI('.$this->agiDir.'/hangup.php)' . PHP_EOL ;

        $this->configFileData[self::EXTENSIONS_CONF_NAME] = $extensionsConf;
    }

    private function makeAstConf():void
    {
        $asteriskConfPattern = file_get_contents($this->dirName.'/templates/asterisk-pattern.conf');
        $logDir = ConnectorFMCConf::getLogDir();
        Util::mwMkdir($logDir);
        $asteriskConf        = str_replace(array('LOG_DIR', 'PATH'), array($logDir, $this->dirName), $asteriskConfPattern);
        $this->configFileData[self::ASTERISK_CONF_NAME] = $asteriskConf;

    }

    private function makeManagerConf():void
    {
        $pattern = file_get_contents($this->dirName.'/templates/manager.conf');
        $conf        = str_replace('<PORT>', ConnectorFMCConf::getAmiPort(), $pattern);
        $this->configFileData[self::MANAGER_CONF_NAME] = $conf;
    }

    private function makeRtp():void
    {
        $rtpConf =  "[general]".PHP_EOL.
            "rtpstart={$this->settings->rtpPortStart}".PHP_EOL.
            "rtpend={$this->settings->rtpPortEnd}".PHP_EOL;

        $this->configFileData[self::RTP_CONF_NAME] = $rtpConf;
    }

    private function makePjSip():void
    {
        $endpointPattern = file_get_contents($this->dirName.'/templates/pjsip-pattern-endpoint.conf');
        $configPjSip     = str_replace(
            ['<USER-AGENT>', '<SIP_PORT_MODULE>'],
            [$this->settings->userAgent, $this->settings->sipPort],
            file_get_contents($this->dirName . '/templates/pjsip-pattern.conf')
        );

        foreach ($this->providers as $provider){
            if(empty($provider['host'])){
                continue;
            }
            $conf = str_replace(
                ['<ENDPOINT>', '<PASSWORD>', '<SIP_PORT>', '<SIP_HOST>', 'from_user'],
                [
                    $provider['endpoint'],
                    $provider['secret'],
                    $provider['port'],
                    $provider['host'],
                    ';from_user',
                ],
                $endpointPattern
            );
            $configPjSip .= PHP_EOL.$conf.PHP_EOL;

            $filterSip = [
                "type = 'peer' AND disabled <> '1' AND extension IN ({extension:array}) ",
                'columns' => 'extension,secret',
                'order' => 'extension',
                'bind' => [
                    'extension' => explode(',',$provider['extensions'])
                ]
            ];
            $peers = Sip::find($filterSip);
            foreach ($peers as $peer){
                $conf = str_replace(
                    ['<ENDPOINT>', '<PASSWORD>', '<SIP_PORT>', '<SIP_HOST>'],
                    [$peer->extension, $peer->secret, $this->sipPort, '127.0.0.1'],
                    $endpointPattern
                );
                $configPjSip .= PHP_EOL.$conf.PHP_EOL;
            }
        }
        $this->configFileData[self::PJSIP_CONF_NAME] = $configPjSip;
    }

}