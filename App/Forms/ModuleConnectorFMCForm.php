<?php
/**
 * Copyright (C) MIKO LLC - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Nikolay Beketov, 9 2018
 *
 */
namespace Modules\ModuleConnectorFMC\App\Forms;

use Phalcon\Forms\Form;
use Phalcon\Forms\Element\Text;
use Phalcon\Forms\Element\Numeric;
use Phalcon\Forms\Element\Password;
use Phalcon\Forms\Element\Check;
use Phalcon\Forms\Element\TextArea;
use Phalcon\Forms\Element\Hidden;
use Phalcon\Forms\Element\Select;


class ModuleConnectorFMCForm extends Form
{
    public function initialize($entity = null, $options = null) :void
    {
        $this->add(new Hidden('id', ['value' => $entity->id]));
        $this->add(new Text('userAgent'));
        $this->add(new Text('sipPort'));
        $this->add(new Text('rtpPortStart'));
        $this->add(new Text('rtpPortEnd'));
        $this->add(new Text('amiPort'));
        foreach ($options as $key => $value){
            if($key === 'id'){
                continue;
            }
            if($key === 'extensions') {
                $this->add(new Hidden($key, ['value' => $value]));
            }elseif($key === 'useDelayedResponse'){
                $valuesCheck = ['value' => null];
                if ($value === '1') {
                    $valuesCheck = ['checked' => 'checked', 'value' => null];
                }
                $this->add(new Check($key, $valuesCheck));
            }elseif($key === 'incomingEndpointSecret'){
                $this->add(new Password($key, ['value' => $value]));
            }elseif(in_array($key, ['outputEndpoint','outputEndpointSecret'] , true)){
                $this->add(new Text($key, ['value' => $value, 'readonly' => '']));
            }elseif('peers' === $key){
                $peers = new Select('peers', $value, [
                    'using'    => [
                        'id',
                        'name',
                    ],
                    'value' => explode(',', $options['extensions']),
                    'useEmpty' => false,
                    'multiple' => '',
                    'class'    => 'ui fluid search dropdown',
                ]);
                $this->add($peers);
            }else{
                $this->add(new Text($key, ['value' => $value]));
            }
        }


    }
}