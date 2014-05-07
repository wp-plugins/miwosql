<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MFormRuleEquals extends MFormRule {

    public function test(&$element, $value, $group = null, &$input = null, &$form = null) {
        // Initialize variables.
        $field = (string)$element['field'];

        // Check that a validation field is set.
        if (!$field) {
            return new MException(MText::sprintf('MLIB_FORM_INVALID_FORM_RULE', get_class($this)));
        }

        // Check that a valid MForm object is given for retrieving the validation field value.
        if (!($form instanceof MForm)) {
            return new MException(MText::sprintf('MLIB_FORM_INVALID_FORM_OBJECT', get_class($this)));
        }

        // Test the two values against each other.
        if ($value == $input->get($field)) {
            return true;
        }

        return false;
    }
}
