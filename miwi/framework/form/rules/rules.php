<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MFormRuleRules extends MFormRule {

    public function test(&$element, $value, $group = null, &$input = null, &$form = null) {
        // Get the possible field actions and the ones posted to validate them.
        $fieldActions = self::getFieldActions($element);
        $valueActions = self::getValueActions($value);

        // Make sure that all posted actions are in the list of possible actions for the field.
        foreach ($valueActions as $action) {
            if (!in_array($action, $fieldActions)) {
                return false;
            }
        }

        return true;
    }

    protected function getValueActions($value) {
        // Initialise variables.
        $actions = array();

        // Iterate over the asset actions and add to the actions.
        foreach ((array)$value as $name => $rules) {
            $actions[] = $name;
        }

        return $actions;
    }

    protected function getFieldActions($element) {
        // Initialise variables.
        $actions = array();

        // Initialise some field attributes.
        $section   = $element['section'] ? (string)$element['section'] : '';
        $component = $element['component'] ? (string)$element['component'] : '';

        // Get the asset actions for the element.
        $elActions = MAccess::getActions($component, $section);

        // Iterate over the asset actions and add to the actions.
        foreach ($elActions as $item) {
            $actions[] = $item->name;
        }

        // Iterate over the children and add to the actions.
        foreach ($element->children() as $el) {
            if ($el->getName() == 'action') {
                $actions[] = (string)$el['name'];
            }
        }

        return $actions;
    }
}