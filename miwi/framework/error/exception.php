<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');


class MException extends Exception {

	protected $level = null;

	protected $code = null;

	protected $message = null;

	protected $info = '';

	protected $file = null;

	protected $line = 0;

	protected $function = null;

	protected $class = null;

	protected $type = null;

	protected $args = array();

	protected $backtrace = null;

	public function __construct($msg, $code = 0, $level = null, $info = null, $backtrace = false) {
		// Deprecation warning.
		MLog::add('MException is deprecated.', MLog::WARNING, 'deprecated');

		$this->level = $level;
		$this->code = $code;
		$this->message = $msg;

		if ($info != null)
		{
			$this->info = $info;
		}

		if ($backtrace && function_exists('debug_backtrace'))
		{
			$this->backtrace = debug_backtrace();

			for ($i = count($this->backtrace) - 1; $i >= 0; --$i)
			{
				++$i;
				if (isset($this->backtrace[$i]['file']))
				{
					$this->file = $this->backtrace[$i]['file'];
				}
				if (isset($this->backtrace[$i]['line']))
				{
					$this->line = $this->backtrace[$i]['line'];
				}
				if (isset($this->backtrace[$i]['class']))
				{
					$this->class = $this->backtrace[$i]['class'];
				}
				if (isset($this->backtrace[$i]['function']))
				{
					$this->function = $this->backtrace[$i]['function'];
				}
				if (isset($this->backtrace[$i]['type']))
				{
					$this->type = $this->backtrace[$i]['type'];
				}

				$this->args = false;
				if (isset($this->backtrace[$i]['args']))
				{
					$this->args = $this->backtrace[$i]['args'];
				}
				break;
			}
		}

		// Store exception for debugging purposes!
		MError::addToStack($this);

		parent::__construct($msg, (int) $code);
	}

	public function __toString() {
		return $this->message;
	}

	public function toString() {
		return (string) $this;
	}

	public function get($property, $default = null) {
		if (isset($this->$property))
		{
			return $this->$property;
		}
		return $default;
	}

	public function getProperties($public = true) {
		$vars = get_object_vars($this);
		if ($public)
		{
			foreach ($vars as $key => $value)
			{
				if ('_' == substr($key, 0, 1))
				{
					unset($vars[$key]);
				}
			}
		}
		return $vars;
	}

	public function getError($i = null, $toString = true) {
		// Deprecation warning.
		MLog::add('MException::getError is deprecated.', MLog::WARNING, 'deprecated');

		// Find the error
		if ($i === null)
		{
			// Default, return the last message
			$error = end($this->_errors);
		}
		elseif (!array_key_exists($i, $this->_errors))
		{
			// If $i has been specified but does not exist, return false
			return false;
		}
		else
		{
			$error = $this->_errors[$i];
		}

		// Check if only the string is requested
		if ($error instanceof Exception && $toString)
		{
			return (string) $error;
		}

		return $error;
	}

	public function getErrors() {
		// Deprecation warning.
		MLog::add('MException::getErrors is deprecated.', MLog::WARNING, 'deprecated');

		return $this->_errors;
	}

	public function set($property, $value = null) {
		// Deprecation warning.
		MLog::add('MException::set is deprecated.', MLog::WARNING, 'deprecated');

		$previous = isset($this->$property) ? $this->$property : null;
		$this->$property = $value;
		return $previous;
	}

	public function setProperties($properties) {
		// Deprecation warning.
		MLog::add('MException::setProperties is deprecated.', MLog::WARNING, 'deprecated');

		// Cast to an array
		$properties = (array) $properties;

		if (is_array($properties))
		{
			foreach ($properties as $k => $v)
			{
				$this->$k = $v;
			}

			return true;
		}

		return false;
	}

	public function setError($error) {
		// Deprecation warning.
		MLog::add('MException::setErrors is deprecated.', MLog::WARNING, 'deprecated');

		array_push($this->_errors, $error);
	}
}
