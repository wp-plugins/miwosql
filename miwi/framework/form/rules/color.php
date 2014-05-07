<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MFormRuleColor extends MFormRule {

    public function test(&$element, $value, $group = null, &$input = null, &$form = null) {
        $value = trim($value);

        if (empty($value)) {
            // A color field can't be empty, we default to black. This is the same as the HTML5 spec.
            $value = '#000000';

            return true;
        }

        if ($value[0] != '#') {
            return false;
        }

        // Remove the leading # if present to validate the numeric part
        $value = ltrim($value, '#');

        // The value must be 6 or 3 characters long
        if (!((strlen($value) == 6 || strlen($value) == 3) && ctype_xdigit($value))) {
            return false;
        }

        // Prepend the # again
        $value = '#' . $value;

        return true;
    }
}