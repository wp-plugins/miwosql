<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

abstract class MHtmlJquery {

    protected static $loaded = array();

    public static function framework($noConflict = true, $debug = null, $migrate = true) {
        return;
    }

    public static function ui(array $components = array('core'), $debug = null) {
        return;
    }
}