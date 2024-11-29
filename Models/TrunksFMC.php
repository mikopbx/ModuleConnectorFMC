<?php
/**
 * Copyright © MIKO LLC - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Alexey Portnov, 2 2019
 */

namespace Modules\ModuleConnectorFMC\Models;

use MikoPBX\Modules\Models\ModulesModelsBase;

class TrunksFMC extends ModulesModelsBase
{
    public const PROVIDER_TYPE_B24 = 0;
    public const PROVIDER_TYPE_MCN = 1;

    /**
     * @Primary
     * @Identity
     * @Column(type="integer", nullable=false)
     */
    public $id;

    /**
     * @Column(type="string", nullable=true)
     */
    public $outputEndpoint;

    /**
     * @Column(type="string", nullable=true)
     */
    public $outputEndpointSecret;
    /**
     * @Column(type="string", nullable=true)
     */
    public $incomingEndpointHost;
    /**
     * @Column(type="string", nullable=true)
     */
    public $incomingEndpointPort;
    /**
     * @Column(type="string", nullable=true)
     */
    public $incomingEndpointSecret;

    /**
     * @Column(type="string", nullable=true)
     */
    public $incomingEndpointLogin;

    /**
     * @Column(type="string", nullable=true)
     */
    public $extensions;

    /**
     * @Column(type="integer", default="1", nullable=true)
     */
    public $useDelayedResponse = '1';

    /**
     * @Column(type="integer", default="0", nullable=true)
     */
    public $providerType = 0;

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
        $this->setSource('m_TrunksFMC');
        parent::initialize();
    }
}