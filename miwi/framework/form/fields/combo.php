<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

MFormHelper::loadFieldClass('list');

class MFormFieldCombo extends MFormFieldList {

    public $type = 'Combo';

    protected function getInput() {
        // Initialize variables.
        $html = array();
        $attr = '';

        // Initialize some field attributes.
        $attr .= $this->element['class'] ? ' class="combobox ' . (string)$this->element['class'] . '"' : ' class="combobox"';
        $attr .= ((string)$this->element['readonly'] == 'true') ? ' readonly="readonly"' : '';
        $attr .= ((string)$this->element['disabled'] == 'true') ? ' disabled="disabled"' : '';
        $attr .= $this->element['size'] ? ' size="' . (int)$this->element['size'] . '"' : '';

        // Initialize JavaScript field attributes.
        $attr .= $this->element['onchange'] ? ' onchange="' . (string)$this->element['onchange'] . '"' : '';

        // Get the field options.
        $options = $this->getOptions();

        // Load the combobox behavior.
        MHtml::_('behavior.combobox');

        // Build the input for the combo box.
        $html[] = '<input type="text" name="' . $this->name . '" id="' . $this->id . '"' . ' value="'
            . htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8') . '"' . $attr . '/>';

        // Build the list for the combo box.
        $html[] = '<ul id="combobox-' . $this->id . '" style="display:none;">';
        foreach ($options as $option) {
            $html[] = '<li>' . $option->text . '</li>';
        }
        $html[] = '</ul>';

        return implode($html);
    }
}
