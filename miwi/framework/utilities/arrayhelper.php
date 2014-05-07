<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

abstract class MArrayHelper {

	protected static $sortCase;
	protected static $sortDirection;
	protected static $sortKey;
	protected static $sortLocale;

	public static function toInteger(&$array, $default = null) {
		if (is_array($array)) {
			foreach ($array as $i => $v) {
				$array[$i] = (int) $v;
			}
		}
		else {
			if ($default === null) {
				$array = array ();
			}
			elseif (is_array($default)) {
				MArrayHelper::toInteger($default, null);
				$array = $default;
			}
			else {
				$array = array (
								(int) $default 
				);
			}
		}
	}

	public static function toObject(&$array, $class = 'stdClass') {
		$obj = null;
		if (is_array($array)) {
			$obj = new $class();
			foreach ($array as $k => $v) {
				if (is_array($v)) {
					$obj->$k = self::toObject($v, $class);
				}
				else {
					$obj->$k = $v;
				}
			}
		}
		return $obj;
	}

	public static function toString($array = null, $inner_glue = '=', $outer_glue = ' ', $keepOuterKey = false) {
		$output = array ();
		
		if (is_array($array)) {
			foreach ($array as $key => $item) {
				if (is_array($item)) {
					if ($keepOuterKey) {
						$output[] = $key;
					}
					// This is value is an array, go and do it again!
					$output[] = self::toString($item, $inner_glue, $outer_glue, $keepOuterKey);
				}
				else {
					$output[] = $key . $inner_glue . '"' . $item . '"';
				}
			}
		}
		
		return implode($outer_glue, $output);
	}

	public static function fromObject($p_obj, $recurse = true, $regex = null) {
		if (is_object($p_obj)) {
			return self::_fromObject($p_obj, $recurse, $regex);
		}
		else {
			return null;
		}
	}

	protected static function _fromObject($item, $recurse, $regex) {
		if (is_object($item)) {
			$result = array ();
			foreach (get_object_vars($item) as $k => $v) {
				if (! $regex || preg_match($regex, $k)) {
					if ($recurse) {
						$result[$k] = self::_fromObject($v, $recurse, $regex);
					}
					else {
						$result[$k] = $v;
					}
				}
			}
		}
		elseif (is_array($item)) {
			$result = array ();
			foreach ($item as $k => $v) {
				$result[$k] = self::_fromObject($v, $recurse, $regex);
			}
		}
		else {
			$result = $item;
		}
		return $result;
	}

	public static function getColumn(&$array, $index) {
		$result = array ();
		
		if (is_array($array)) {
			$n = count($array);
			
			for ($i = 0; $i < $n; $i ++) {
				$item = &$array[$i];
				
				if (is_array($item) && isset($item[$index])) {
					$result[] = $item[$index];
				}
				elseif (is_object($item) && isset($item->$index)) {
					$result[] = $item->$index;
				}
				// else ignore the entry
			}
		}
		return $result;
	}

	public static function getValue(&$array, $name, $default = null, $type = '') {
		// Initialise variables.
		$result = null;
		
		if (isset($array[$name])) {
			$result = $array[$name];
		}
		
		// Handle the default case
		if (is_null($result)) {
			$result = $default;
		}
		
		// Handle the type constraint
		switch (strtoupper($type)) {
			case 'INT' :
			case 'INTEGER' :
				// Only use the first integer value
				@preg_match('/-?[0-9]+/', $result, $matches);
				$result = @(int) $matches[0];
				break;
			
			case 'FLOAT' :
			case 'DOUBLE' :
				// Only use the first floating point value
				@preg_match('/-?[0-9]+(\.[0-9]+)?/', $result, $matches);
				$result = @(float) $matches[0];
				break;
			
			case 'BOOL' :
			case 'BOOLEAN' :
				$result = (bool) $result;
				break;
			
			case 'ARRAY' :
				if (! is_array($result)) {
					$result = array (
									$result 
					);
				}
				break;
			
			case 'STRING' :
				$result = (string) $result;
				break;
			
			case 'WORD' :
				$result = (string) preg_replace('#\W#', '', $result);
				break;
			
			case 'NONE' :
			default :
				// No casting necessary
				break;
		}
		return $result;
	}

	public static function isAssociative($array) {
		if (is_array($array)) {
			foreach (array_keys($array) as $k => $v) {
				if ($k !== $v) {
					return true;
				}
			}
		}
		
		return false;
	}

	public static function pivot($source, $key = null) {
		$result = array ();
		$counter = array ();
		
		foreach ($source as $index => $value) {
			// Determine the name of the pivot key, and its value.
			if (is_array($value)) {
				// If the key does not exist, ignore it.
				if (! isset($value[$key])) {
					continue;
				}
				
				$resultKey = $value[$key];
				$resultValue = &$source[$index];
			}
			elseif (is_object($value)) {
				// If the key does not exist, ignore it.
				if (! isset($value->$key)) {
					continue;
				}
				
				$resultKey = $value->$key;
				$resultValue = &$source[$index];
			}
			else {
				// Just a scalar value.
				$resultKey = $value;
				$resultValue = $index;
			}
			
			// The counter tracks how many times a key has been used.
			if (empty($counter[$resultKey])) {
				// The first time around we just assign the value to the key.
				$result[$resultKey] = $resultValue;
				$counter[$resultKey] = 1;
			}
			elseif ($counter[$resultKey] == 1) {
				// If there is a second time, we convert the value into an array.
				$result[$resultKey] = array (
															$result[$resultKey],
															$resultValue 
				);
				$counter[$resultKey] ++;
			}
			else {
				// After the second time, no need to track any more. Just append to the existing array.
				$result[$resultKey][] = $resultValue;
			}
		}
		
		unset($counter);
		
		return $result;
	}

	public static function sortObjects(&$a, $k, $direction = 1, $caseSensitive = true, $locale = false) {
		if (! is_array($locale) or ! is_array($locale[0])) {
			$locale = array (
							$locale 
			);
		}
		
		self::$sortCase = (array) $caseSensitive;
		self::$sortDirection = (array) $direction;
		self::$sortKey = (array) $k;
		self::$sortLocale = $locale;
		
		usort($a, array (
						__CLASS__,
						'_sortObjects' 
		));
		
		self::$sortCase = null;
		self::$sortDirection = null;
		self::$sortKey = null;
		self::$sortLocale = null;
		
		return $a;
	}

	protected static function _sortObjects(&$a, &$b) {
		$key = self::$sortKey;
		
		for ($i = 0, $count = count($key); $i < $count; $i ++) {
			if (isset(self::$sortDirection[$i])) {
				$direction = self::$sortDirection[$i];
			}
			
			if (isset(self::$sortCase[$i])) {
				$caseSensitive = self::$sortCase[$i];
			}
			
			if (isset(self::$sortLocale[$i])) {
				$locale = self::$sortLocale[$i];
			}
			
			$va = $a->$key[$i];
			$vb = $b->$key[$i];
			
			if ((is_bool($va) or is_numeric($va)) and (is_bool($vb) or is_numeric($vb))) {
				$cmp = $va - $vb;
			}
			elseif ($caseSensitive) {
				$cmp = MString::strcmp($va, $vb, $locale);
			}
			else {
				$cmp = MString::strcasecmp($va, $vb, $locale);
			}
			
			if ($cmp > 0) {
				
				return $direction;
			}
			
			if ($cmp < 0) {
				return - $direction;
			}
		}
		
		return 0;
	}

	public static function arrayUnique($myArray) {
		if (! is_array($myArray)) {
			return $myArray;
		}
		
		foreach ($myArray as &$myvalue) {
			$myvalue = serialize($myvalue);
		}
		
		$myArray = array_unique($myArray);
		
		foreach ($myArray as &$myvalue) {
			$myvalue = unserialize($myvalue);
		}
		
		return $myArray;
	}
}