<?php

/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MLanguageHelper {

    public static function createLanguageList($actualLanguage, $basePath = MPATH_BASE, $caching = false, $installed = false) {
        $list = array();

        // Cache activation
        $langs = get_available_languages();

        foreach ($langs as $lang) {
            $option = array();

            $option['text']  = $lang;
            $option['value'] = $lang;
            if ($lang == $actualLanguage) {
                $option['selected'] = 'selected="selected"';
            }
            $list[] = $option;
        }

        return $list;
    }

    public static function detectLanguage() {
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $browserLangs = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
            $systemLangs  = self::getLanguages();
            foreach ($browserLangs as $browserLang) {
                // Slice out the part before ; on first step, the part before - on second, place into array
                $browserLang         = substr($browserLang, 0, strcspn($browserLang, ';'));
                $primary_browserLang = substr($browserLang, 0, 2);
                foreach ($systemLangs as $systemLang) {
                    // Take off 3 letters iso code languages as they can't match browsers' languages and default them to en
                    $Jinstall_lang = $systemLang->lang_code;

                    if (strlen($Jinstall_lang) < 6) {
                        if (strtolower($browserLang) == strtolower(substr($systemLang->lang_code, 0, strlen($browserLang)))) {
                            return $systemLang->lang_code;
                        }
                        elseif ($primary_browserLang == substr($systemLang->lang_code, 0, 2)) {
                            $primaryDetectedLang = $systemLang->lang_code;
                        }
                    }
                }

                if (isset($primaryDetectedLang)) {
                    return $primaryDetectedLang;
                }
            }
        }

        return null;
    }

    public static function getLanguages($key = 'default') {
        static $languages;

        if (empty($languages)) {
            // Installation uses available languages
            if (MFactory::getApplication()->getClientId() == 2) {
                $languages[$key] = array();
                $knownLangs      = MLanguage::getKnownLanguages(MPATH_BASE);
                foreach ($knownLangs as $metadata) {
                    // Take off 3 letters iso code languages as they can't match browsers' languages and default them to en
                    $languages[$key][] = new MObject(array('lang_code' => $metadata['tag']));
                }
            }
            else {
                $cache = MFactory::getCache('com_languages', '');
                if (!$languages = $cache->get('languages')) {
                    $langs = get_available_languages();
                    $i = 0;
                    $lngs = array();
                    foreach ($langs as $lang) {
                        $lngs[$i] = new stdClass();
                        $lngs[$i]->title = $lang;
                        $i++;
                    }
                    $languages['default']   = $lngs;
                    $languages['lang_code'] = array();
                    if (isset($languages['default'][0])) {
                        foreach ($languages['default'] as $lang) {
                            $languages['lang_code'][$lang->title] = $lang;
                        }
                    }
                    $cache->store($languages, 'languages');
                }
            }
        }

        return $languages[$key];
    }
}