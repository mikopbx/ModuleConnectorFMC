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

namespace Modules\ModuleConnectorFMC\App\Forms;

use Phalcon\Forms\Form;
use Phalcon\Forms\Element\Text;
use Phalcon\Forms\Element\Password;
use Phalcon\Forms\Element\Check;
use Phalcon\Forms\Element\Hidden;
use Phalcon\Forms\Element\Select;

class ModuleConnectorFMCForm extends Form
{
    public function initialize($entity = null, $options = null): void
    {
        $this->add(new Hidden('id', ['value' => $entity->id]));
        $this->add(new Text('userAgent'));
        $this->add(new Text('sipPort'));
        $this->add(new Text('rtpPortStart'));
        $this->add(new Text('rtpPortEnd'));
        $this->add(new Text('amiPort'));
        foreach ($options as $key => $value) {
            if ($key === 'id') {
                continue;
            }
            if ($key === 'extensions') {
                $this->add(new Hidden($key, ['value' => $value]));
            } elseif ($key === 'useDelayedResponse') {
                $this->addCheckBox($key, intval($value) === 1);
            } elseif ($key === 'incomingEndpointSecret') {
                $this->add(new Password($key, ['value' => $value]));
            } elseif (in_array($key, ['outputEndpoint','outputEndpointSecret'], true)) {
                $this->add(new Text($key, ['value' => $value, 'readonly' => '']));
            } elseif ('peers' === $key) {
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
            } else {
                $this->add(new Text($key, ['value' => $value]));
            }
        }
    }

    /**
     * Adds a checkbox to the form field with the given name.
     * Can be deleted if the module depends on MikoPBX later than 2024.3.0
     *
     * @param string $fieldName The name of the form field.
     * @param bool $checked Indicates whether the checkbox is checked by default.
     * @param string $checkedValue The value assigned to the checkbox when it is checked.
     * @return void
     */
    public function addCheckBox(string $fieldName, bool $checked, string $checkedValue = 'on'): void
    {
        $checkAr = ['value' => null];
        if ($checked) {
            $checkAr = ['checked' => $checkedValue,'value' => $checkedValue];
        }
        $this->add(new Check($fieldName, $checkAr));
    }
}
