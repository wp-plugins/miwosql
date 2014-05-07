<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MFilterOutput {

    public static function objectHTMLSafe(&$mixed, $quote_style = ENT_QUOTES, $exclude_keys = '') {
        if (is_object($mixed)) {
            foreach (get_object_vars($mixed) as $k => $v) {
                if (is_array($v) || is_object($v) || $v == null || substr($k, 1, 1) == '_') {
                    continue;
                }

                if (is_string($exclude_keys) && $k == $exclude_keys) {
                    continue;
                }
                elseif (is_array($exclude_keys) && in_array($k, $exclude_keys)) {
                    continue;
                }

                $mixed->$k = htmlspecialchars($v, $quote_style, 'UTF-8');
            }
        }
    }

    public static function linkXHTMLSafe($input) {
        $regex = 'href="([^"]*(&(amp;){0})[^"]*)*?"';
        return preg_replace_callback("#$regex#i", array('MFilterOutput', '_ampReplaceCallback'), $input);
    }

    public static function stringURLSafe($string) {
        // Remove any '-' from the string since they will be used as concatenaters
        $str = str_replace('-', ' ', $string);

        $lang = MFactory::getLanguage();
        $str = $lang->transliterate($str);

        // Trim white spaces at beginning and end of alias and make lowercase
        $str = trim(MString::strtolower($str));

        // Remove any duplicate whitespace, and ensure all characters are alphanumeric
        $str = preg_replace('/(\s|[^A-Za-z0-9\-])+/', '-', $str);

        // Trim dashes at beginning and end of alias
        $str = trim($str, '-');

        return $str;
    }

    public static function stringURLUnicodeSlug($string) {
        // Replace double byte whitespaces by single byte (East Asian languages)
        $str = preg_replace('/\xE3\x80\x80/', ' ', $string);

        // Remove any '-' from the string as they will be used as concatenator.
        // Would be great to let the spaces in but only Firefox is friendly with this

        $str = str_replace('-', ' ', $str);

        // Replace forbidden characters by whitespaces
        $str = preg_replace('#[:\#\*"@+=;!><&\.%()\]\/\'\\\\|\[]#', "\x20", $str);

        // Delete all '?'
        $str = str_replace('?', '', $str);

        // Trim white spaces at beginning and end of alias and make lowercase
        $str = trim(MString::strtolower($str));

        // Remove any duplicate whitespace and replace whitespaces by hyphens
        $str = preg_replace('#\x20+#', '-', $str);

        return $str;
    }

    public static function ampReplace($text) {
        $text = str_replace('&&', '*--*', $text);
        $text = str_replace('&#', '*-*', $text);
        $text = str_replace('&amp;', '&', $text);
        $text = preg_replace('|&(?![\w]+;)|', '&amp;', $text);
        $text = str_replace('*-*', '&#', $text);
        $text = str_replace('*--*', '&&', $text);

        return $text;
    }

    public static function _ampReplaceCallback($m) {
        $rx = '&(?!amp;)';

        return preg_replace('#' . $rx . '#', '&amp;', $m[0]);
    }

    public static function cleanText(&$text) {
        $text = preg_replace("'<script[^>]*>.*?</script>'si", '', $text);
        $text = preg_replace('/<a\s+.*?href="([^"]+)"[^>]*>([^<]+)<\/a>/is', '\2 (\1)', $text);
        $text = preg_replace('/<!--.+?-->/', '', $text);
        $text = preg_replace('/{.+?}/', '', $text);
        $text = preg_replace('/&nbsp;/', ' ', $text);
        $text = preg_replace('/&amp;/', ' ', $text);
        $text = preg_replace('/&quot;/', ' ', $text);
        $text = strip_tags($text);
        $text = htmlspecialchars($text, ENT_COMPAT, 'UTF-8');

        return $text;
    }

    public static function stripImages($string) {
        return preg_replace('#(<[/]?img.*>)#U', '', $string);
    }
}