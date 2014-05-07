<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

abstract class MHtmlNumber {

    public static function bytes($bytes, $unit = 'auto', $precision = 2) {
        // No explicit casting $bytes to integer here, since it might overflow
        // on 32-bit systems
        $precision = (int)$precision;

        if (empty($bytes)) {
            return 0;
        }

        $unitTypes = array('b', 'kb', 'MB', 'GB', 'TB', 'PB');

        // Default automatic method.
        $i = floor(log($bytes, 1024));

        // User supplied method:
        if ($unit !== 'auto' && in_array($unit, $unitTypes)) {
            $i = array_search($unit, $unitTypes, true);
        }

        // TODO Allow conversion of units where $bytes = '32M'.

        return round($bytes / pow(1024, $i), $precision) . ' ' . $unitTypes[$i];
    }
}
