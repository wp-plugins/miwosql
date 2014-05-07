<?php
/**
* @version $Id$
* @package utf8
* @subpackage strings
*/

/**
* Define UTF8_CORE as required
*/
if ( !defined('UTF8_CORE') ) {
    define('UTF8_CORE',TRUE);
}

function utf8_strlen($str){
    return mb_strlen($str);
}

function utf8_strpos($str, $search, $offset = FALSE){
    if ( $offset === FALSE ) {
        return mb_strpos($str, $search);
    } else {
        return mb_strpos($str, $search, $offset);
    }
}

function utf8_strrpos($str, $search, $offset = FALSE){
    if ( $offset === FALSE ) {
        # Emulate behaviour of strrpos rather than raising warning
        if ( empty($str) ) {
            return FALSE;
        }
        return mb_strrpos($str, $search);
    } else {
        if ( !is_int($offset) ) {
            trigger_error('utf8_strrpos expects parameter 3 to be long',E_USER_WARNING);
            return FALSE;
        }

        $str = mb_substr($str, $offset);

        if ( FALSE !== ( $pos = mb_strrpos($str, $search) ) ) {
            return $pos + $offset;
        }

        return FALSE;
    }
}

function utf8_substr($str, $offset, $length = FALSE){
    if ( $length === FALSE ) {
        return mb_substr($str, $offset);
    } else {
        return mb_substr($str, $offset, $length);
    }
}

function utf8_strtolower($str){
    return mb_strtolower($str);
}

function utf8_strtoupper($str){
    return mb_strtoupper($str);
}
