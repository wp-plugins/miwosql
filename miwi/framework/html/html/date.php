<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

abstract class MHtmlDate {

    public static function relative($date, $unit = null, $time = null) {
        if (is_null($time)) {
            // Get now
            $time = MFactory::getDate('now');
        }

        // Get the difference in seconds between now and the time
        $diff = strtotime($time) - strtotime($date);

        // Less than a minute
        if ($diff < 60) {
            return MText::_('MLIB_HTML_DATE_RELATIVE_LESSTHANAMINUTE');
        }

        // Round to minutes
        $diff = round($diff / 60);

        // 1 to 59 minutes
        if ($diff < 60 || $unit == 'minute') {
            return MText::plural('MLIB_HTML_DATE_RELATIVE_MINUTES', $diff);
        }

        // Round to hours
        $diff = round($diff / 60);

        // 1 to 23 hours
        if ($diff < 24 || $unit == 'hour') {
            return MText::plural('MLIB_HTML_DATE_RELATIVE_HOURS', $diff);
        }

        // Round to days
        $diff = round($diff / 24);

        // 1 to 6 days
        if ($diff < 7 || $unit == 'day') {
            return MText::plural('MLIB_HTML_DATE_RELATIVE_DAYS', $diff);
        }

        // Round to weeks
        $diff = round($diff / 7);

        // 1 to 4 weeks
        if ($diff <= 4 || $unit == 'week') {
            return MText::plural('MLIB_HTML_DATE_RELATIVE_WEEKS', $diff);
        }

        // Over a month, return the absolute time
        return MHtml::_('date', $date);
    }
}