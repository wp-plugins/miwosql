<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

abstract class MHtmlContentLanguage {

    protected static $items = null;

    public static function existing($all = false, $translate = false) {
        if (empty(self::$items)) {
            $languages = array();
            $langs     = get_available_languages();
            if (!in_array(get_locale(), $langs)) {
                $langs[] = get_locale();
            }
            $i = 0;
            foreach ($langs as $lang) {
                $languages[$i]        = new stdClass();
                $languages[$i]->value = $lang;
                $languages[$i]->text  = $lang;
                $i++;
            }

            self::$items = $languages;
            if ($all) {
                array_unshift(self::$items, new MObject(array('value' => '*', 'text' => $translate ? MText::alt('MALL', 'language') : 'MALL_LANGUAGE')));
            }
        }

        return self::$items;
    }
}