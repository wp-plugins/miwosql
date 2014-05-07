<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/
defined('MIWI') or die('MIWI');

class MText {

	protected static $strings = array ();

	public static function _($string, $jsSafe = false, $interpretBackSlashes = true, $script = false) {
		$lang = MFactory::getLanguage();
		if (is_array($jsSafe)) {
			if (array_key_exists('interpretBackSlashes', $jsSafe)) {
				$interpretBackSlashes = (boolean) $jsSafe['interpretBackSlashes'];
			}
			if (array_key_exists('script', $jsSafe)) {
				$script = (boolean) $jsSafe['script'];
			}
			if (array_key_exists('jsSafe', $jsSafe)) {
				$jsSafe = (boolean) $jsSafe['jsSafe'];
			}
			else {
				$jsSafe = false;
			}
		}
		if (! (strpos($string, ',') === false)) {
			$test = substr($string, strpos($string, ','));
			if (strtoupper($test) === $test) {
				$strs = explode(',', $string);
				foreach ($strs as $i => $str) {
					$strs[$i] = $lang->_($str, $jsSafe, $interpretBackSlashes);
					if ($script) {
						self::$strings[$str] = $strs[$i];
					}
				}
				$str = array_shift($strs);
				$str = preg_replace('/\[\[%([0-9]+):[^\]]*\]\]/', '%\1$s', $str);
				$str = vsprintf($str, $strs);
				
				return $str;
			}
		}
		if ($script) {
			self::$strings[$string] = $lang->_($string, $jsSafe, $interpretBackSlashes);
			return $string;
		}
		else {
			return $lang->_($string, $jsSafe, $interpretBackSlashes);
		}
	}

	public static function alt($string, $alt, $jsSafe = false, $interpretBackSlashes = true, $script = false) {
		$lang = MFactory::getLanguage();
		if ($lang->hasKey($string . '_' . $alt)) {
			return self::_($string . '_' . $alt, $jsSafe, $interpretBackSlashes);
		}
		else {
			return self::_($string, $jsSafe, $interpretBackSlashes);
		}
	}

	public static function plural($string, $n) {
		$lang = MFactory::getLanguage();
		$args = func_get_args();
		$count = count($args);
		
		if ($count > 1) {
			$found = false;
			$suffixes = $lang->getPluralSuffixes((int) $n);
			array_unshift($suffixes, (int) $n);
			foreach ($suffixes as $suffix) {
				$key = $string . '_' . $suffix;
				if ($lang->hasKey($key)) {
					$found = true;
					break;
				}
			}
			if (! $found) {
				$key = $string;
			}
			if (is_array($args[$count - 1])) {
				$args[0] = $lang->_($key, array_key_exists('jsSafe', $args[$count - 1]) ? $args[$count - 1]['jsSafe'] : false, array_key_exists('interpretBackSlashes', $args[$count - 1]) ? $args[$count - 1]['interpretBackSlashes'] : true);
				if (array_key_exists('script', $args[$count - 1]) && $args[$count - 1]['script']) {
					self::$strings[$key] = call_user_func_array('sprintf', $args);
					return $key;
				}
			}
			else {
				$args[0] = $lang->_($key);
			}
			return call_user_func_array('sprintf', $args);
		}
		elseif ($count > 0) {
			
			// Default to the normal sprintf handling.
			$args[0] = $lang->_($string);
			return call_user_func_array('sprintf', $args);
		}
		
		return '';
	}

	public static function sprintf($string) {
		$lang = MFactory::getLanguage();
		$args = func_get_args();
		$count = count($args);
		if ($count > 0) {
			if (is_array($args[$count - 1])) {
				$args[0] = $lang->_($string, array_key_exists('jsSafe', $args[$count - 1]) ? $args[$count - 1]['jsSafe'] : false, array_key_exists('interpretBackSlashes', $args[$count - 1]) ? $args[$count - 1]['interpretBackSlashes'] : true);
				
				if (array_key_exists('script', $args[$count - 1]) && $args[$count - 1]['script']) {
					self::$strings[$string] = call_user_func_array('sprintf', $args);
					return $string;
				}
			}
			else {
				$args[0] = $lang->_($string);
			}
			$args[0] = preg_replace('/\[\[%([0-9]+):[^\]]*\]\]/', '%\1$s', $args[0]);
			return call_user_func_array('sprintf', $args);
		}
		return '';
	}

	public static function printf($string) {
		$lang = MFactory::getLanguage();
		$args = func_get_args();
		$count = count($args);
		if ($count > 0) {
			if (is_array($args[$count - 1])) {
				$args[0] = $lang->_($string, array_key_exists('jsSafe', $args[$count - 1]) ? $args[$count - 1]['jsSafe'] : false, array_key_exists('interpretBackSlashes', $args[$count - 1]) ? $args[$count - 1]['interpretBackSlashes'] : true);
			}
			else {
				$args[0] = $lang->_($string);
			}
			return call_user_func_array('printf', $args);
		}
		return '';
	}

	public static function script($string = null, $jsSafe = false, $interpretBackSlashes = true) {
		if (is_array($jsSafe)) {
			if (array_key_exists('interpretBackSlashes', $jsSafe)) {
				$interpretBackSlashes = (boolean) $jsSafe['interpretBackSlashes'];
			}
			
			if (array_key_exists('jsSafe', $jsSafe)) {
				$jsSafe = (boolean) $jsSafe['jsSafe'];
			}
			else {
				$jsSafe = false;
			}
		}
		
		if ($string !== null) {
			self::$strings[strtoupper($string)] = MFactory::getLanguage()->_($string, $jsSafe, $interpretBackSlashes);
		}
		
		return self::$strings;
	}
}