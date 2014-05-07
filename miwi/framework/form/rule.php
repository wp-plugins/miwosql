<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

// Detect if we have full UTF-8 and unicode PCRE support.
if (!defined('MCOMPAT_UNICODE_PROPERTIES')) {
    define('MCOMPAT_UNICODE_PROPERTIES', (bool)@preg_match('/\pL/u', 'a'));
}

class MFormRule {

    protected $regex;

    protected $modifiers;

    public function test(&$element, $value, $group = null, &$input = null, &$form = null) {
        // Check for a valid regex.
        if (empty($this->regex)) {
            throw new MException(MText::sprintf('MLIB_FORM_INVALID_FORM_RULE', get_class($this)));
        }

        // Add unicode property support if available.
        if (MCOMPAT_UNICODE_PROPERTIES) {
            $this->modifiers = (strpos($this->modifiers, 'u') !== false) ? $this->modifiers : $this->modifiers . 'u';
        }

        // Test the value against the regular expression.
        if (preg_match(chr(1) . $this->regex . chr(1) . $this->modifiers, $value)) {
            return true;
        }

        return false;
    }
}