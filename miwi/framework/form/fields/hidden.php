<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MFormFieldHidden extends MFormField {

    protected $type = 'Hidden';

    protected function getInput() {
        // Initialize some field attributes.
        $class    = $this->element['class'] ? ' class="' . (string)$this->element['class'] . '"' : '';
        $disabled = ((string)$this->element['disabled'] == 'true') ? ' disabled="disabled"' : '';

        // Initialize JavaScript field attributes.
        $onchange = $this->element['onchange'] ? ' onchange="' . (string)$this->element['onchange'] . '"' : '';

        return '<input type="hidden" name="' . $this->name . '" id="' . $this->id . '"' . ' value="'
        . htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8') . '"' . $class . $disabled . $onchange . ' />';
    }
}
