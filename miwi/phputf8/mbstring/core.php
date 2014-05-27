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
	if(function_exists('mb_strlen')) {
		return mb_strlen($str);
	}
	else {
		return strlen($str);
	}
}

function utf8_strpos($str, $search, $offset = FALSE){
    if ( $offset === FALSE ) {
		if(function_exists('mb_strpos')) {
			return mb_strpos($str, $search);
		}
		else {
			return strpos($str, $search);
		}
    } else {
		if(function_exists('mb_strpos')) {
			return mb_strpos($str, $search, $offset);
		}
		else {
			return strpos($str, $search, $offset);
		}
    }
}

function utf8_strrpos($str, $search, $offset = FALSE){
    if ( $offset === FALSE ) {
        # Emulate behaviour of strrpos rather than raising warning
        if ( empty($str) ) {
            return FALSE;
        }
		
		if(function_exists('mb_strrpos')) {
			return mb_strrpos($str, $search);
		}
		else {
			return strrpos($str, $search);
		}
		
    } else {
        if ( !is_int($offset) ) {
            trigger_error('utf8_strrpos expects parameter 3 to be long',E_USER_WARNING);
            return FALSE;
        }

		if(function_exists('mb_substr')) {
			$str = mb_substr($str, $offset);
		}
		else {
			$str = substr($str, $offset);
		}
		
		if(function_exists('mb_strrpos')) {
			$pos = mb_strrpos($str, $search);
		}
		else {
			$pos = strrpos($str, $search);
		}

        if ( FALSE !== $pos ) {
            return $pos + $offset;
        }

        return FALSE;
    }
}

function utf8_substr($str, $offset, $length = FALSE){
    if ( $length === FALSE ) {
		if(function_exists('mb_substr')) {
			return mb_substr($str, $offset);
		}
		else {
			return substr($str, $offset);
		}
    } else {
		if(function_exists('mb_substr')) {
			return mb_substr($str, $offset, $length);
		}
		else {
			return substr($str, $offset, $length);
		}		
    }
}

function utf8_strtolower($str){
	if(function_exists('mb_strtolower')) {
		return mb_strtolower($str);
	}
	else {
		return strtolower($str);
	}
}

function utf8_strtoupper($str){
	if(function_exists('mb_strtoupper')) {
		return mb_strtoupper($str);
	}
	else {
		return strtoupper($str);
	}
}
