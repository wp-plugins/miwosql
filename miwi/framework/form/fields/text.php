<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MFormFieldText extends MFormField {

    protected $type = 'Text';

    protected function getInput() {
        // Initialize some field attributes.
        $size      = $this->element['size'] ? ' size="' . (int)$this->element['size'] . '"' : '';
        $maxLength = $this->element['maxlength'] ? ' maxlength="' . (int)$this->element['maxlength'] . '"' : '';
        $class     = $this->element['class'] ? ' class="' . (string)$this->element['class'] . '"' : '';
        $readonly  = ((string)$this->element['readonly'] == 'true') ? ' readonly="readonly"' : '';
        $disabled  = ((string)$this->element['disabled'] == 'true') ? ' disabled="disabled"' : '';

        // Initialize JavaScript field attributes.
        $onchange = $this->element['onchange'] ? ' onchange="' . (string)$this->element['onchange'] . '"' : '';

        return '<input type="text" name="' . $this->name . '" id="' . $this->id . '"' . ' value="'
        . htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8') . '"' . $class . $size . $disabled . $readonly . $onchange . $maxLength . '/>';
    }
}
