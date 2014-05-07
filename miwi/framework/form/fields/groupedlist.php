<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MFormFieldGroupedList extends MFormField {

    protected $type = 'GroupedList';

    protected function getGroups() {
        // Initialize variables.
        $groups = array();
        $label  = 0;

        foreach ($this->element->children() as $element) {
            switch ($element->getName()) {
                // The element is an <option />
                case 'option':
                    // Initialize the group if necessary.
                    if (!isset($groups[$label])) {
                        $groups[$label] = array();
                    }

                    // Create a new option object based on the <option /> element.
                    $tmp = MHtml::_(
                        'select.option', ($element['value']) ? (string)$element['value'] : trim((string)$element),
                        MText::alt(trim((string)$element), preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->fieldname)), 'value', 'text',
                        ((string)$element['disabled'] == 'true')
                    );

                    // Set some option attributes.
                    $tmp->class = (string)$element['class'];

                    // Set some JavaScript option attributes.
                    $tmp->onclick = (string)$element['onclick'];

                    // Add the option.
                    $groups[$label][] = $tmp;
                    break;

                // The element is a <group />
                case 'group':
                    // Get the group label.
                    if ($groupLabel = (string)$element['label']) {
                        $label = MText::_($groupLabel);
                    }

                    // Initialize the group if necessary.
                    if (!isset($groups[$label])) {
                        $groups[$label] = array();
                    }

                    // Iterate through the children and build an array of options.
                    foreach ($element->children() as $option) {
                        // Only add <option /> elements.
                        if ($option->getName() != 'option') {
                            continue;
                        }

                        // Create a new option object based on the <option /> element.
                        $tmp = MHtml::_(
                            'select.option', ($option['value']) ? (string)$option['value'] : MText::_(trim((string)$option)),
                            MText::_(trim((string)$option)), 'value', 'text', ((string)$option['disabled'] == 'true')
                        );

                        // Set some option attributes.
                        $tmp->class = (string)$option['class'];

                        // Set some JavaScript option attributes.
                        $tmp->onclick = (string)$option['onclick'];

                        // Add the option.
                        $groups[$label][] = $tmp;
                    }

                    if ($groupLabel) {
                        $label = count($groups);
                    }
                    break;

                // Unknown element type.
                default:
                    MError::raiseError(500, MText::sprintf('MLIB_FORM_ERROR_FIELDS_GROUPEDLIST_ELEMENT_NAME', $element->getName()));
                    break;
            }
        }

        reset($groups);

        return $groups;
    }

    protected function getInput() {
        // Initialize variables.
        $html = array();
        $attr = '';

        // Initialize some field attributes.
        $attr .= $this->element['class'] ? ' class="' . (string)$this->element['class'] . '"' : '';
        $attr .= ((string)$this->element['disabled'] == 'true') ? ' disabled="disabled"' : '';
        $attr .= $this->element['size'] ? ' size="' . (int)$this->element['size'] . '"' : '';
        $attr .= $this->multiple ? ' multiple="multiple"' : '';

        // Initialize JavaScript field attributes.
        $attr .= $this->element['onchange'] ? ' onchange="' . (string)$this->element['onchange'] . '"' : '';

        // Get the field groups.
        $groups = (array)$this->getGroups();

        // Create a read-only list (no name) with a hidden input to store the value.
        if ((string)$this->element['readonly'] == 'true') {
            $html[] = MHtml::_(
                'select.groupedlist', $groups, null,
                array(
                    'list.attr'          => $attr, 'id' => $this->id, 'list.select' => $this->value, 'group.items' => null, 'option.key.toHtml' => false,
                    'option.text.toHtml' => false
                )
            );
            $html[] = '<input type="hidden" name="' . $this->name . '" value="' . $this->value . '"/>';
        }
        // Create a regular list.
        else {
            $html[] = MHtml::_(
                'select.groupedlist', $groups, $this->name,
                array(
                    'list.attr'          => $attr, 'id' => $this->id, 'list.select' => $this->value, 'group.items' => null, 'option.key.toHtml' => false,
                    'option.text.toHtml' => false
                )
            );
        }

        return implode($html);
    }
}