<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MObject {

	protected $_errors = array();

	public function __construct($properties = null) {
		if ($properties !== null) {
			$this->setProperties($properties);
		}
	}

	public function __toString() {
		return get_class($this);
	}

	public function def($property, $default = null) {
		$value = $this->get($property, $default);
		
		return $this->set($property, $value);
	}

	public function get($property, $default = null) {
		if (isset($this->$property)) {
			return $this->$property;
		}
		
		return $default;
	}

	public function getProperties($public = true) {
		$vars = get_object_vars($this);
		
		if ($public) {
			foreach ($vars as $key => $value) {
				if ('_' == substr($key, 0, 1)) {
					unset($vars[$key]);
				}
			}
		}

		return $vars;
	}

	public function getError($i = null, $toString = true) {
		// Find the error
		if ($i === null) {
			// Default, return the last message
			$error = end($this->_errors);
		}
		elseif (!array_key_exists($i, $this->_errors)) {
			// If $i has been specified but does not exist, return false
			return false;
		}
		else {
			$error = $this->_errors[$i];
		}

		// Check if only the string is requested
		if ($error instanceof Exception && $toString) {
			return (string) $error;
		}

		return $error;
	}

	public function getErrors() {
		return $this->_errors;
	}

	public function set($property, $value = null) {
		$previous = isset($this->$property) ? $this->$property : null;
		
		$this->$property = $value;
		
		return $previous;
	}

	public function setProperties($properties) {
		if (is_array($properties) || is_object($properties)) {
			foreach ((array) $properties as $k => $v) {
				// Use the set function which might be overridden.
				$this->set($k, $v);
			}
			
			return true;
		}

		return false;
	}

	public function setError($error) {
		array_push($this->_errors, $error);
	}

	public function toString() {
		// Deprecation warning.
		MLog::add('MObject::toString() is deprecated.', MLog::WARNING, 'deprecated');

		return $this->__toString();
	}
}