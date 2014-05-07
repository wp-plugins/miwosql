<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

abstract class MString {
	
	protected static $incrementStyles = array(
		'dash' => array(
			'#-(\d+)$#',
			'-%d'
		),
		'default' => array(
			array('#\((\d+)\)$#', '#\(\d+\)$#'),
			array(' (%d)', '(%d)'),
		),
	);

	public static function splitCamelCase($string) {
		return preg_split('/(?<=[^A-Z_])(?=[A-Z])|(?<=[A-Z])(?=[A-Z][^A-Z_])/x', $string);
	}

	public static function increment($string, $style = 'default', $n = 0) {
		$styleSpec = isset(self::$incrementStyles[$style]) ? self::$incrementStyles[$style] : self::$incrementStyles['default'];

		if (is_array($styleSpec[0])) {
			$rxSearch = $styleSpec[0][0];
			$rxReplace = $styleSpec[0][1];
		}
		else {
			$rxSearch = $rxReplace = $styleSpec[0];
		}

		if (is_array($styleSpec[1])) {
			$newFormat = $styleSpec[1][0];
			$oldFormat = $styleSpec[1][1];
		}
		else {
			$newFormat = $oldFormat = $styleSpec[1];
		}

		if (preg_match($rxSearch, $string, $matches)) {
			$n = empty($n) ? ($matches[1] + 1) : $n;
			$string = preg_replace($rxReplace, sprintf($oldFormat, $n), $string);
		}
		else {
			$n = empty($n) ? 2 : $n;
			$string .= sprintf($newFormat, $n);
		}

		return $string;
	}

	public static function strpos($str, $search, $offset = false) {
		if ($offset === false) {
			return utf8_strpos($str, $search);
		}
		else {
			return utf8_strpos($str, $search, $offset);
		}
	}

	public static function strrpos($str, $search, $offset = 0) {
		return utf8_strrpos($str, $search, $offset);
	}

	public static function substr($str, $offset, $length = false) {
		if ($length === false) {
			return utf8_substr($str, $offset);
		}
		else {
			return utf8_substr($str, $offset, $length);
		}
	}

	public static function strtolower($str) {
		return utf8_strtolower($str);
	}

	public static function strtoupper($str) {
		return utf8_strtoupper($str);
	}

	public static function strlen($str) {
		return utf8_strlen($str);
	}

	public static function str_ireplace($search, $replace, $str, $count = null) {
		mimport('phputf8.str_ireplace');
		if ($count === false) {
			return utf8_ireplace($search, $replace, $str);
		}
		else {
			return utf8_ireplace($search, $replace, $str, $count);
		}
	}

	public static function str_split($str, $split_len = 1) {
		mimport('phputf8.str_split');
		return utf8_str_split($str, $split_len);
	}

	public static function strcasecmp($str1, $str2, $locale = false) {
		if ($locale) {
			$locale0 = setlocale(LC_COLLATE, 0);
			if (!$locale = setlocale(LC_COLLATE, $locale)) {
				$locale = $locale0;
			}

			if (!stristr($locale, 'UTF-8') && stristr($locale, '_') && preg_match('~\.(\d+)$~', $locale, $m)) {
				$encoding = 'CP' . $m[1];
			}
			elseif (stristr($locale, 'UTF-8')) {
				$encoding = 'UTF-8';
			}
			else {
				$encoding = 'nonrecodable';
			}

			if ($encoding == 'UTF-8' || $encoding == 'nonrecodable') {
				return strcoll(utf8_strtolower($str1), utf8_strtolower($str2));
			}
			else {
				return strcoll(
					self::transcode(utf8_strtolower($str1), 'UTF-8', $encoding),
					self::transcode(utf8_strtolower($str2), 'UTF-8', $encoding)
				);
			}
		}
		else {
			return utf8_strcasecmp($str1, $str2);
		}
	}

	public static function strcmp($str1, $str2, $locale = false) {
		if ($locale) {
			$locale0 = setlocale(LC_COLLATE, 0);
			if (!$locale = setlocale(LC_COLLATE, $locale)) {
				$locale = $locale0;
			}

			if (!stristr($locale, 'UTF-8') && stristr($locale, '_') && preg_match('~\.(\d+)$~', $locale, $m)) {
				$encoding = 'CP' . $m[1];
			}
			elseif (stristr($locale, 'UTF-8')) {
				$encoding = 'UTF-8';
			}
			else {
				$encoding = 'nonrecodable';
			}

			if ($encoding == 'UTF-8' || $encoding == 'nonrecodable') {
				return strcoll($str1, $str2);
			}
			else {
				return strcoll(self::transcode($str1, 'UTF-8', $encoding), self::transcode($str2, 'UTF-8', $encoding));
			}
		}
		else {
			return strcmp($str1, $str2);
		}
	}

	public static function strcspn($str, $mask, $start = null, $length = null) {
		mimport('phputf8.strcspn');
		if ($start === false && $length === false) {
			return utf8_strcspn($str, $mask);
		}
		elseif ($length === false) {
			return utf8_strcspn($str, $mask, $start);
		}
		else {
			return utf8_strcspn($str, $mask, $start, $length);
		}
	}

	public static function stristr($str, $search) {
		mimport('phputf8.stristr');
		return utf8_stristr($str, $search);
	}

	public static function strrev($str) {
		mimport('phputf8.strrev');
		return utf8_strrev($str);
	}

	public static function strspn($str, $mask, $start = null, $length = null) {
		mimport('phputf8.strspn');
		if ($start === null && $length === null) {
			return utf8_strspn($str, $mask);
		}
		elseif ($length === null) {
			return utf8_strspn($str, $mask, $start);
		}
		else {
			return utf8_strspn($str, $mask, $start, $length);
		}
	}

	public static function substr_replace($str, $repl, $start, $length = null) {
		if ($length === false) {
			return utf8_substr_replace($str, $repl, $start);
		}
		else {
			return utf8_substr_replace($str, $repl, $start, $length);
		}
	}

	public static function ltrim($str, $charlist = false) {
		if (empty($charlist) && $charlist !== false) {
			return $str;
		}

		mimport('phputf8.trim');
		if ($charlist === false) {
			return utf8_ltrim($str);
		}
		else {
			return utf8_ltrim($str, $charlist);
		}
	}

	public static function rtrim($str, $charlist = false) {
		if (empty($charlist) && $charlist !== false) {
			return $str;
		}

		mimport('phputf8.trim');
		if ($charlist === false) {
			return utf8_rtrim($str);
		}
		else {
			return utf8_rtrim($str, $charlist);
		}
	}

	public static function trim($str, $charlist = false) {
		if (empty($charlist) && $charlist !== false) {
			return $str;
		}

		mimport('phputf8.trim');
		if ($charlist === false) {
			return utf8_trim($str);
		}
		else {
			return utf8_trim($str, $charlist);
		}
	}

	public static function ucfirst($str, $delimiter = null, $newDelimiter = null) {
		mimport('phputf8.ucfirst');
		if ($delimiter === null) {
			return utf8_ucfirst($str);
		}
		else {
			if ($newDelimiter === null) {
				$newDelimiter = $delimiter;
			}
			return implode($newDelimiter, array_map('utf8_ucfirst', explode($delimiter, $str)));
		}
	}

	public static function ucwords($str) {
		mimport('phputf8.ucwords');
		return utf8_ucwords($str);
	}

	private static function _iconvErrorHandler($number, $message) {
		throw new ErrorException($message, 0, $number);
	}

	public static function transcode($source, $from_encoding, $to_encoding) {
		if (is_string($source)) {
			set_error_handler(array(__CLASS__, '_iconvErrorHandler'), E_NOTICE);
			try {
				$iconv = iconv($from_encoding, $to_encoding . '//TRANSLIT//IGNORE', $source);
			}
			catch (ErrorException $e) {
				$iconv = iconv($from_encoding, $to_encoding . '//IGNORE', $source);
			}
			restore_error_handler();
			return $iconv;
		}

		return null;
	}

	public static function valid($str) {
		$mState = 0;
		$mUcs4 = 0;
		$mBytes = 1;

		$len = strlen($str);

		for ($i = 0; $i < $len; $i++) {
			$in = ord($str{$i});

			if ($mState == 0) {
				if (0 == (0x80 & ($in))) {
					$mBytes = 1;
				}
				elseif (0xC0 == (0xE0 & ($in))) {
					$mUcs4 = ($in);
					$mUcs4 = ($mUcs4 & 0x1F) << 6;
					$mState = 1;
					$mBytes = 2;
				}
				elseif (0xE0 == (0xF0 & ($in))) {
					$mUcs4 = ($in);
					$mUcs4 = ($mUcs4 & 0x0F) << 12;
					$mState = 2;
					$mBytes = 3;
				}
				elseif (0xF0 == (0xF8 & ($in))) {
					$mUcs4 = ($in);
					$mUcs4 = ($mUcs4 & 0x07) << 18;
					$mState = 3;
					$mBytes = 4;
				}
				elseif (0xF8 == (0xFC & ($in))) {
					$mUcs4 = ($in);
					$mUcs4 = ($mUcs4 & 0x03) << 24;
					$mState = 4;
					$mBytes = 5;
				}
				elseif (0xFC == (0xFE & ($in))) {
					$mUcs4 = ($in);
					$mUcs4 = ($mUcs4 & 1) << 30;
					$mState = 5;
					$mBytes = 6;

				}
				else {
					return false;
				}
			}
			else {
				if (0x80 == (0xC0 & ($in))) {
					$shift = ($mState - 1) * 6;
					$tmp = $in;
					$tmp = ($tmp & 0x0000003F) << $shift;
					$mUcs4 |= $tmp;

					if (0 == --$mState) {
						if (((2 == $mBytes) && ($mUcs4 < 0x0080)) || ((3 == $mBytes) && ($mUcs4 < 0x0800)) || ((4 == $mBytes) && ($mUcs4 < 0x10000))
							|| (4 < $mBytes)
							|| (($mUcs4 & 0xFFFFF800) == 0xD800)
							|| ($mUcs4 > 0x10FFFF)) {
							return false;
						}

						$mState = 0;
						$mUcs4 = 0;
						$mBytes = 1;
					}
				}
				else {
					return false;
				}
			}
		}
		return true;
	}

	public static function compliant($str) {
		if (strlen($str) == 0) {
			return true;
		}
		return (preg_match('/^.{1}/us', $str, $ar) == 1);
	}

	public static function parse_url($url) {
		$result = array();
		$entities = array('%21', '%2A', '%27', '%28', '%29', '%3B', '%3A', '%40', '%26', '%3D', '%24', '%2C', '%2F', '%3F', '%25', '%23', '%5B', '%5D');
		$replacements = array('!', '*', "'", "(", ")", ";", ":", "@", "&", "=", "$", ",", "/", "?", "%", "#", "[", "]");
		$encodedURL = str_replace($entities, $replacements, urlencode($url));
		$encodedParts = parse_url($encodedURL);
		
		foreach ($encodedParts as $key => $value) {
			$result[$key] = urldecode($value);
		}
		return $result;
	}
}