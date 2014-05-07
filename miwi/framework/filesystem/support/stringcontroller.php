<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MStringController {

    public function _getArray() {
        static $strings = array();
        return $strings;
    }

    public function createRef($reference, &$string) {
        $ref = & self::_getArray();
        $ref[$reference] = & $string;
    }

    public function getRef($reference) {
        $ref = & self::_getArray();
        if (isset($ref[$reference])) {
            return $ref[$reference];
        }
        else {
            return false;
        }
    }
}