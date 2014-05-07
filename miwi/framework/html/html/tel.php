<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

abstract class MHtmlTel {

    public static function tel($number, $displayplan) {
        $number      = explode('.', $number);
        $countrycode = $number[0];
        $number      = $number[1];

        if ($displayplan == 'ITU-T' || $displayplan == 'International' || $displayplan == 'int' || $displayplan == 'missdn' || $displayplan == null) {
            $display[0] = '+';
            $display[1] = $countrycode;
            $display[2] = ' ';
            $display[3] = implode(str_split($number, 2), ' ');
        }
        elseif ($displayplan == 'NANP' || $displayplan == 'northamerica' || $displayplan == 'US') {
            $display[0] = '(';
            $display[1] = substr($number, 0, 3);
            $display[2] = ') ';
            $display[3] = substr($number, 3, 3);
            $display[4] = '-';
            $display[5] = substr($number, 6, 4);
        }
        elseif ($displayplan == 'EPP' || $displayplan == 'IETF') {
            $display[0] = '+';
            $display[1] = $countrycode;
            $display[2] = '.';
            $display[3] = $number;

        }
        elseif ($displayplan == 'ARPA' || $displayplan == 'ENUM') {
            $number     = implode(str_split(strrev($number), 1), '.');
            $display[0] = '+';
            $display[1] = $number;
            $display[2] = '.';
            $display[3] = $countrycode;
            $display[4] = '.e164.arpa';
        }

        $display = implode($display, '');

        return $display;
    }
}
