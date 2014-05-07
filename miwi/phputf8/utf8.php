<?php

if ( !defined('UTF8') ) {
    define('UTF8',dirname(__FILE__));
}

if ( extension_loaded('mbstring')) {
    if ( ini_get('mbstring.func_overload') & MB_OVERLOAD_STRING ) {
        trigger_error('String functions are overloaded by mbstring',E_USER_ERROR);
    }
    mb_internal_encoding('UTF-8');
}

$UTF8_ar = array();
if ( preg_match('/^.{1}$/u',"ñ",$UTF8_ar) != 1 ) {
    trigger_error('PCRE is not compiled with UTF-8 support',E_USER_ERROR);
}
unset($UTF8_ar);

if ( !defined('UTF8_CORE') ) {
    if ( function_exists('mb_substr') ) {
        require_once UTF8 . '/mbstring/core.php';
    } else {
        require_once UTF8 . '/utils/unicode.php';
        require_once UTF8 . '/native/core.php';
    }
}

require_once UTF8 . '/substr_replace.php';