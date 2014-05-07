<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MFormFieldList extends MFormField {

    protected $type = 'List';

    protected function getInput() {
        // Initialize variables.
        $html = array();
        $attr = '';

        // Initialize some field attributes.
        $attr .= $this->element['class'] ? ' class="' . (string)$this->element['class'] . '"' : '';

        // To avoid user's confusion, readonly="true" should imply disabled="true".
        if ((string)$this->element['readonly'] == 'true' || (string)$this->element['disabled'] == 'true') {
            $attr .= ' disabled="disabled"';
        }

        $attr .= $this->element['size'] ? ' size="' . (int)$this->element['size'] . '"' : '';
        $attr .= $this->multiple ? ' multiple="multiple"' : '';

        // Initialize JavaScript field attributes.
        $attr .= $this->element['onchange'] ? ' onchange="' . (string)$this->element['onchange'] . '"' : '';

        // Get the field options.
        $options = (array)$this->getOptions();

        // Create a read-only list (no name) with a hidden input to store the value.
        if ((string)$this->element['readonly'] == 'true') {
            $html[] = MHtml::_('select.genericlist', $options, '', trim($attr), 'value', 'text', $this->value, $this->id);
            $html[] = '<input type="hidden" name="' . $this->name . '" value="' . $this->value . '"/>';
        }
        // Create a regular list.
        else {
            $html[] = MHtml::_('select.genericlist', $options, $this->name, trim($attr), 'value', 'text', $this->value, $this->id);
        }

        return implode($html);
    }

    protected function getOptions() {
        // Initialize variables.
        $options = array();

        foreach ($this->element->children() as $option) {

            // Only add <option /> elements.
            if ($option->getName() != 'option') {
                continue;
            }

            // Create a new option object based on the <option /> element.
            $tmp = MHtml::_(
                'select.option', (string)$option['value'],
                MText::alt(trim((string)$option), preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->fieldname)), 'value', 'text',
                ((string)$option['disabled'] == 'true')
            );

            // Set some option attributes.
            $tmp->class = (string)$option['class'];

            // Set some JavaScript option attributes.
            $tmp->onclick = (string)$option['onclick'];

            // Add the option object to the result set.
            $options[] = $tmp;
        }

        reset($options);

        return $options;
    }
}
