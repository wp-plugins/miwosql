<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MRegistryFormatJSON extends MRegistryFormat {

	public function objectToString($object, $options = array()) {
		return json_encode($object);
	}

	public function stringToObject($data, $options = array('processSections' => false)) {
		// Fix legacy API.
		if (is_bool($options)) {
			$options = array (
							'processSections' => $options 
			);
			
			// Deprecation warning.
			MLog::add('MRegistryFormatJSON::stringToObject() second argument should not be a boolean.', MLog::WARNING, 'deprecated');
		}
		
		$data = trim($data);
		if ((substr($data, 0, 1) != '{') && (substr($data, - 1, 1) != '}')) {
			$ini = MRegistryFormat::getInstance('INI');
			$obj = $ini->stringToObject($data, $options);
		}
		else {
			$obj = json_decode($data);
		}
		return $obj;
	}
}