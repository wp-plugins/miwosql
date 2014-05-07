<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MFormFieldCheckbox extends MFormField {

    public $type = 'Checkbox';

    protected function getInput() {
        // Initialize some field attributes.
        $class    = $this->element['class'] ? ' class="' . (string)$this->element['class'] . '"' : '';
        $disabled = ((string)$this->element['disabled'] == 'true') ? ' disabled="disabled"' : '';
        $checked  = ((string)$this->element['value'] == $this->value) ? ' checked="checked"' : '';

        // Initialize JavaScript field attributes.
        $onclick = $this->element['onclick'] ? ' onclick="' . (string)$this->element['onclick'] . '"' : '';

        return '<input type="checkbox" name="' . $this->name . '" id="' . $this->id . '"' . ' value="'
        . htmlspecialchars((string)$this->element['value'], ENT_COMPAT, 'UTF-8') . '"' . $class . $checked . $disabled . $onclick . '/>';
    }
}