<?php
/**
 * Copyright © MIKO LLC - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Alexey Portnov, 11 2018
 */
namespace Modules\ModuleConnectorFMC\App\Controllers;
use MikoPBX\AdminCabinet\Controllers\BaseController;
use MikoPBX\Common\Models\Extensions;
use MikoPBX\Common\Models\Sip;
use MikoPBX\Modules\PbxExtensionUtils;
use Modules\ModuleConnectorFMC\App\Forms\ModuleConnectorFMCForm;
use Modules\ModuleConnectorFMC\Models\ModuleConnectorFMC;
use Modules\ModuleConnectorFMC\Models\TrunksFMC;

class ModuleConnectorFMCController extends BaseController
{
    private $moduleUniqueID = 'ModuleConnectorFMC';
    private $moduleDir;

    /**
     * Basic initial class
     */
    public function initialize(): void
    {
        $this->moduleDir = PbxExtensionUtils::getModuleDir($this->moduleUniqueID);
        $this->view->logoImagePath = "{$this->url->get()}assets/img/cache/{$this->moduleUniqueID}/logo.svg";
        $this->view->submitMode = null;
        parent::initialize();
    }
    /**
     * Index page controller
     */
    public function indexAction(): void
    {
        $footerCollection = $this->assets->collection('footerJS');
        $footerCollection->addJs('js/pbx/main/form.js', true);
        $footerCollection->addJs('js/vendor/datatable/dataTables.semanticui.js', true);
        $footerCollection->addJs("js/cache/{$this->moduleUniqueID}/module-connectorfmc-index.js", true);
        $footerCollection->addJs('js/vendor/jquery.tablednd.min.js', true);
        $headerCollectionCSS = $this->assets->collection('headerCSS');
        $headerCollectionCSS->addCss("css/cache/{$this->moduleUniqueID}/module-connectorfmc.css", true);
        $headerCollectionCSS->addCss('css/vendor/datatable/dataTables.semanticui.min.css', true);

        $settings = ModuleConnectorFMC::findFirst();
        if ($settings === null) {
            $settings = new ModuleConnectorFMC();
        }
        $options = TrunksFMC::findFirst();
        if ($options === null) {
            $options = new TrunksFMC();
        }

        $filterSip = [
            "type = '".Extensions::TYPE_SIP."'",
            'columns' => 'concat(callerid, " <", number, ">") as name, number as id',
        ];
        $peers = Extensions::find($filterSip);

        $options = $options->toArray();
        $options['peers'] = $peers;
        $this->view->form = new ModuleConnectorFMCForm($settings, $options);
        $this->view->pick("$this->moduleDir/App/Views/index");
    }

    /**
     * Save settings AJAX action
     */
    public function saveAction() :void
    {
        $data       = $this->request->getPost();
        $settings = ModuleConnectorFMC::findFirst();
        if ($settings === null) {
            $settings = new ModuleConnectorFMC();
        }
        $trunk = TrunksFMC::findFirst();
        if ($trunk === null) {
            $trunk = new TrunksFMC();
            $trunk->outputEndpoint       = Sip::generateUniqueID('SIP-FMC-');
            $trunk->outputEndpointSecret = md5($trunk->outputEndpoint.time());
        }
        $this->db->begin();
        foreach ($settings as $key => $value) {
            if(isset($data[$key])){
                $settings->$key = trim($data[$key]);
            }
        }
        foreach ($trunk as $key => $value) {
            if(isset($data[$key])){
                if($key === 'useDelayedResponse'){
                    $trunk->$key  = ($data[$key] === 'on') ? '1' : '0';
                }else{
                    $trunk->$key = trim($data[$key]);
                }
            }
        }
        if ($settings->save() === FALSE || $trunk->save() === FALSE) {
            $this->view->success = false;
            $this->db->rollback();
            return;
        }
        $this->flash->success($this->translation->_('ms_SuccessfulSaved'));
        $this->view->success = true;
        $this->db->commit();
    }
}