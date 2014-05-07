<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MFormRuleOptions extends MFormRule {

    public function test(&$element, $value, $group = null, &$input = null, &$form = null) {
        // Check each value and return true if we get a match
        foreach ($element->option as $option) {
            if ($value == (string)$option->attributes()->value) {
                return true;
            }
        }

        return false;
    }
}
