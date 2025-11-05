<?php
/**
 * Copyright © MIKO LLC - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Alexey Portnov, 2 2019
 */

namespace Modules\ModuleConnectorFMC\Models;

use MikoPBX\Common\Handlers\CriticalErrorsHandler;
use MikoPBX\Modules\Models\ModulesModelsBase;

class ModuleConnectorFMC extends ModulesModelsBase
{
    /**
     * @Primary
     * @Identity
     * @Column(type="integer", nullable=false)
     */
    public $id;

    /**
     * @Column(type="string", default="miko-b24-fmc", nullable=true)
     */
    public $userAgent = 'miko-b24-fmc';

    /**
     * @Column(type="integer", default="5168", nullable=true)
     */
    public $sipPort = 5168;

    /**
     * @Column(type="integer", default="40000", nullable=true)
     */
    public $rtpPortStart = 40000;

    /**
     * @Column(type="integer", default="41000", nullable=true)
     */
    public $rtpPortEnd = 41000;

    /**
     * @Column(type="integer", default="55039", nullable=true)
     */
    public $amiPort = 55039;

    /**
     * @param $calledModelObject
     *
     * @return void
     */
    public static function getDynamicRelations(&$calledModelObject): void
    {
    }

    public function initialize(): void
    {
        $this->setSource('m_ModuleConnectorFMC');
        parent::initialize();
    }

    /**
     * Returns default or saved value for key if it exists on DB
     *
     * @param $key string value key
     *
     * @return string
     */
    public static function getValueByKey(string $key): string
    {
        try {
            $currentSettings = parent::findFirst()->toArray();
            return trim($currentSettings[$key]??'');
        } catch (\Throwable $e) {
            CriticalErrorsHandler::handleException($e);
        }

        return '';
    }
}