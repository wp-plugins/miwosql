<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MFormFieldUsergroup extends MFormField {

    protected $type = 'Usergroup';

    protected function getInput() {
        // Initialize variables.
        $options = array();
        $attr    = '';

        // Initialize some field attributes.
        $attr .= $this->element['class'] ? ' class="' . (string)$this->element['class'] . '"' : '';
        $attr .= ((string)$this->element['disabled'] == 'true') ? ' disabled="disabled"' : '';
        $attr .= $this->element['size'] ? ' size="' . (int)$this->element['size'] . '"' : '';
        $attr .= $this->multiple ? ' multiple="multiple"' : '';

        // Initialize JavaScript field attributes.
        $attr .= $this->element['onchange'] ? ' onchange="' . (string)$this->element['onchange'] . '"' : '';

        // Iterate through the children and build an array of options.
        foreach ($this->element->children() as $option) {

            // Only add <option /> elements.
            if ($option->getName() != 'option') {
                continue;
            }

            // Create a new option object based on the <option /> element.
            $tmp = MHtml::_(
                'select.option', (string)$option['value'], trim((string)$option), 'value', 'text',
                ((string)$option['disabled'] == 'true')
            );

            // Set some option attributes.
            $tmp->class = (string)$option['class'];

            // Set some JavaScript option attributes.
            $tmp->onclick = (string)$option['onclick'];

            // Add the option object to the result set.
            $options[] = $tmp;
        }

        return MHtml::_('access.usergroup', $this->name, $this->value, $attr, $options, $this->id);
    }
}