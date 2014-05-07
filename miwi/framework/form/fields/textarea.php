<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MFormFieldTextarea extends MFormField {

    protected $type = 'Textarea';

    protected function getInput() {
        // Initialize some field attributes.
        $class    = $this->element['class'] ? ' class="' . (string)$this->element['class'] . '"' : '';
        $disabled = ((string)$this->element['disabled'] == 'true') ? ' disabled="disabled"' : '';
        $columns  = $this->element['cols'] ? ' cols="' . (int)$this->element['cols'] . '"' : '';
        $rows     = $this->element['rows'] ? ' rows="' . (int)$this->element['rows'] . '"' : '';

        // Initialize JavaScript field attributes.
        $onchange = $this->element['onchange'] ? ' onchange="' . (string)$this->element['onchange'] . '"' : '';

        return '<textarea name="' . $this->name . '" id="' . $this->id . '"' . $columns . $rows . $class . $disabled . $onchange . '>'
        . htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8') . '</textarea>';
    }
}
