<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

define('MREQUEST_NOTRIM', 1);
define('MREQUEST_ALLOWRAW', 2);
define('MREQUEST_ALLOWHTML', 4);

class MRequest {

	public static function getURI() {
		$uri = MFactory::getURI();
		return $uri->toString(array (
									'path',
									'query' 
		));
	}

	public static function getMethod() {
		$method = strtoupper($_SERVER['REQUEST_METHOD']);
		return $method;
	}

	public static function getVar($name, $default = null, $hash = 'default', $type = 'none', $mask = 0) {
		// Ensure hash and type are uppercase
		$hash = strtoupper($hash);
		if ($hash === 'METHOD') {
			$hash = strtoupper($_SERVER['REQUEST_METHOD']);
		}
		$type = strtoupper($type);
		$sig = $hash . $type . $mask;
		
		// Get the input hash
		switch ($hash) {
			case 'GET' :
				$input = &$_GET;
				break;
			case 'POST' :
				$input = &$_POST;
				break;
			case 'FILES' :
				$input = &$_FILES;
				break;
			case 'COOKIE' :
				$input = &$_COOKIE;
				break;
			case 'ENV' :
				$input = &$_ENV;
				break;
			case 'SERVER' :
				$input = &$_SERVER;
				break;
			default :
				$input = &$_REQUEST;
				$hash = 'REQUEST';
				break;
		}
		
		if (isset($GLOBALS['_MREQUEST'][$name]['SET.' . $hash]) && ($GLOBALS['_MREQUEST'][$name]['SET.' . $hash] === true)) {
			// Get the variable from the input hash
			$var = (isset($input[$name]) && $input[$name] !== null) ? $input[$name] : $default;
			$var = self::_cleanVar($var, $mask, $type);
		}
		elseif (! isset($GLOBALS['_MREQUEST'][$name][$sig])) {
			if (isset($input[$name]) && $input[$name] !== null) {
				// Get the variable from the input hash and clean it
				$var = self::_cleanVar($input[$name], $mask, $type);
				
				// Handle magic quotes compatibility
				if (get_magic_quotes_gpc() && ($var != $default) && ($hash != 'FILES')) {
					$var = self::_stripSlashesRecursive($var);
				}
				
				$GLOBALS['_MREQUEST'][$name][$sig] = $var;
			}
			elseif ($default !== null) {
				// Clean the default value
				$var = self::_cleanVar($default, $mask, $type);
			}
			else {
				$var = $default;
			}
		}
		else {
			$var = $GLOBALS['_MREQUEST'][$name][$sig];
		}
		
		return $var;
	}

	public static function getInt($name, $default = 0, $hash = 'default') {
		return self::getVar($name, $default, $hash, 'int');
	}

	public static function getUInt($name, $default = 0, $hash = 'default') {
		return self::getVar($name, $default, $hash, 'uint');
	}

	public static function getFloat($name, $default = 0.0, $hash = 'default') {
		return self::getVar($name, $default, $hash, 'float');
	}

	public static function getBool($name, $default = false, $hash = 'default') {
		return self::getVar($name, $default, $hash, 'bool');
	}

	public static function getWord($name, $default = '', $hash = 'default') {
		return self::getVar($name, $default, $hash, 'word');
	}

	public static function getCmd($name, $default = '', $hash = 'default') {
		return self::getVar($name, $default, $hash, 'cmd');
	}

	public static function getString($name, $default = '', $hash = 'default', $mask = 0) {
		return (string) self::getVar($name, $default, $hash, 'string', $mask);
	}

	public static function setVar($name, $value = null, $hash = 'method', $overwrite = true) {
		if (! $overwrite && array_key_exists($name, $_REQUEST)) {
			return $_REQUEST[$name];
		}
		
		$GLOBALS['_MREQUEST'][$name] = array ();
		
		$hash = strtoupper($hash);
		if ($hash === 'METHOD') {
			$hash = strtoupper($_SERVER['REQUEST_METHOD']);
		}
		
		$previous = array_key_exists($name, $_REQUEST) ? $_REQUEST[$name] : null;
		
		switch ($hash) {
			case 'GET' :
				$_GET[$name] = $value;
				$_REQUEST[$name] = $value;
				break;
			case 'POST' :
				$_POST[$name] = $value;
				$_REQUEST[$name] = $value;
				break;
			case 'COOKIE' :
				$_COOKIE[$name] = $value;
				$_REQUEST[$name] = $value;
				break;
			case 'FILES' :
				$_FILES[$name] = $value;
				break;
			case 'ENV' :
				$_ENV['name'] = $value;
				break;
			case 'SERVER' :
				$_SERVER['name'] = $value;
				break;
		}
		
		$GLOBALS['_MREQUEST'][$name]['SET.' . $hash] = true;
		$GLOBALS['_MREQUEST'][$name]['SET.REQUEST'] = true;
		
		return $previous;
	}

	public static function get($hash = 'default', $mask = 0) {
		$hash = strtoupper($hash);
		
		if ($hash === 'METHOD') {
			$hash = strtoupper($_SERVER['REQUEST_METHOD']);
		}
		
		switch ($hash) {
			case 'GET' :
				$input = $_GET;
				break;
			
			case 'POST' :
				$input = $_POST;
				break;
			
			case 'FILES' :
				$input = $_FILES;
				break;
			
			case 'COOKIE' :
				$input = $_COOKIE;
				break;
			
			case 'ENV' :
				$input = &$_ENV;
				break;
			
			case 'SERVER' :
				$input = &$_SERVER;
				break;
			
			default :
				$input = $_REQUEST;
				break;
		}
		
		$result = self::_cleanVar($input, $mask);
		
		// Handle magic quotes compatibility
		if (get_magic_quotes_gpc() && ($hash != 'FILES')) {
			$result = self::_stripSlashesRecursive($result);
		}
		
		return $result;
	}

	public static function set($array, $hash = 'default', $overwrite = true) {
		foreach ($array as $key => $value) {
			self::setVar($key, $value, $hash, $overwrite);
		}
	}

	public static function checkToken($method = 'post') {
		$token = MSession::getFormToken();
		if (! self::getVar($token, '', $method, 'alnum')) {
			$session = MFactory::getSession();
			if ($session->isNew()) {
				// Redirect to login screen.
				$app = MFactory::getApplication();
				$return = MRoute::_('index.php');
				$app->redirect($return, MText::_('MLIB_ENVIRONMENT_SESSION_EXPIRED'));
				$app->close();
			}
			else {
				return false;
			}
		}
		else {
			return true;
		}
	}

	public static function clean() {
		// Only run this if register globals is on.
		// Remove this code when PHP 5.4 becomes the minimum requirement.
		if (! (bool) ini_get('register_globals')) {
			return;
		}
		
		$REQUEST = $_REQUEST;
		$GET = $_GET;
		$POST = $_POST;
		$COOKIE = $_COOKIE;
		$FILES = $_FILES;
		$ENV = $_ENV;
		$SERVER = $_SERVER;
		
		if (isset($_SESSION)) {
			$SESSION = $_SESSION;
		}
		
		foreach ($GLOBALS as $key => $value) {
			if ($key != 'GLOBALS') {
				unset($GLOBALS[$key]);
			}
		}
		$_REQUEST = $REQUEST;
		$_GET = $GET;
		$_POST = $POST;
		$_COOKIE = $COOKIE;
		$_FILES = $FILES;
		$_ENV = $ENV;
		$_SERVER = $SERVER;
		
		if (isset($SESSION)) {
			$_SESSION = $SESSION;
		}
		
		// Make sure the request hash is clean on file inclusion
		$GLOBALS['_MREQUEST'] = array ();
	}

	static function _cleanVar($var, $mask = 0, $type = null) {
		// If the no trim flag is not set, trim the variable
		if (! ($mask & 1) && is_string($var)) {
			$var = trim($var);
		}
		
		// Now we handle input filtering
		if ($mask & 2) {
			// If the allow raw flag is set, do not modify the variable
			$var = $var;
		}
		elseif ($mask & 4) {
			// If the allow HTML flag is set, apply a safe HTML filter to the variable
			$safeHtmlFilter = MFilterInput::getInstance(null, null, 1, 1);
			$var = $safeHtmlFilter->clean($var, $type);
		}
		else {
			// Since no allow flags were set, we will apply the most strict filter to the variable
			// $tags, $attr, $tag_method, $attr_method, $xss_auto use defaults.
			$noHtmlFilter = MFilterInput::getInstance();
			$var = $noHtmlFilter->clean($var, $type);
		}
		return $var;
	}

	protected static function _stripSlashesRecursive($value) {
		$value = is_array($value) ? array_map(array (
													'MRequest',
													'_stripSlashesRecursive' 
		), $value) : stripslashes($value);
		return $value;
	}
}