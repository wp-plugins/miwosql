<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MFormFieldFile extends MFormField {

    public $type = 'File';

    protected function getInput() {
        // Initialize some field attributes.
        $accept   = $this->element['accept'] ? ' accept="' . (string)$this->element['accept'] . '"' : '';
        $size     = $this->element['size'] ? ' size="' . (int)$this->element['size'] . '"' : '';
        $class    = $this->element['class'] ? ' class="' . (string)$this->element['class'] . '"' : '';
        $disabled = ((string)$this->element['disabled'] == 'true') ? ' disabled="disabled"' : '';

        // Initialize JavaScript field attributes.
        $onchange = $this->element['onchange'] ? ' onchange="' . (string)$this->element['onchange'] . '"' : '';

        return '<input type="file" name="' . $this->name . '" id="' . $this->id . '"' . ' value=""' . $accept . $disabled . $class . $size
        . $onchange . ' />';
    }
}
