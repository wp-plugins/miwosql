<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MApplicationHelper {

	protected static $_clients = null;

	public static function getComponentName($default = null) {
		static $option;

		if ($option) {
			return $option;
		}

		$option = strtolower(MRequest::getCmd('option'));

		if (empty($option))	{
			$option = $default;
		}

		MRequest::setVar('option', $option);
		return $option;
	}

	public static function getClientInfo($id = null, $byName = false) {
		// Only create the array if it does not exist
		if (self::$_clients === null) {
			$obj = new stdClass;

			// Site Client
			$obj->id = 0;
			$obj->name = 'site';
			$obj->path = MPATH_SITE;
			self::$_clients[0] = clone $obj;

			// Administrator Client
			$obj->id = 1;
			$obj->name = 'administrator';
			$obj->path = MPATH_ADMINISTRATOR;
			self::$_clients[1] = clone $obj;

			// Installation Client
			$obj->id = 2;
			$obj->name = 'installation';
			$obj->path = MPATH_INSTALLATION;
			self::$_clients[2] = clone $obj;
		}

		// If no client id has been passed return the whole array
		if (is_null($id)) {
			return self::$_clients;
		}

		// Are we looking for client information by id or by name?
		if (!$byName) {
			if (isset(self::$_clients[$id]))
			{
				return self::$_clients[$id];
			}
		}
		else {
			foreach (self::$_clients as $client)
			{
				if ($client->name == strtolower($id))
				{
					return $client;
				}
			}
		}

		return null;
	}

	public static function addClientInfo($client) {
		if (is_array($client)) {
			$client = (object) $client;
		}

		if (!is_object($client)) {
			return false;
		}

		$info = self::getClientInfo();

		if (!isset($client->id)) {
			$client->id = count($info);
		}

		self::$_clients[$client->id] = clone $client;

		return true;
	}

	public static function getPath($varname, $user_option = null) {
		// Check needed for handling of custom/new module XML file loading
		$check = (($varname == 'mod0_xml') || ($varname == 'mod1_xml'));

		if (!$user_option && !$check) {
			$user_option = MRequest::getCmd('option');
		}
		else {
			$user_option = MFilterInput::getInstance()->clean($user_option, 'path');
		}

		$result = null;
		$name = substr($user_option, 4);

		switch ($varname) {
			case 'front':
				$result = self::_checkPath('/components/' . $user_option . '/' . $name . '.php', 0);
				break;
			case 'html':
			case 'front_html':
				if (!($result = self::_checkPath('/templates/' . MApplication::getTemplate() . '/components/' . $name . '.html.php', 0))) {
					$result = self::_checkPath('/components/' . $user_option . '/' . $name . '.html.php', 0);
				}
				break;
			case 'toolbar':
				$result = self::_checkPath('/components/' . $user_option . '/toolbar.' . $name . '.php', -1);
				break;
			case 'toolbar_html':
				$result = self::_checkPath('/components/' . $user_option . '/toolbar.' . $name . '.html.php', -1);
				break;
			case 'toolbar_default':
			case 'toolbar_front':
				$result = self::_checkPath('/includes/HTML_toolbar.php', 0);
				break;
			case 'admin':
				$path = '/components/' . $user_option . '/admin.' . $name . '.php';
				$result = self::_checkPath($path, -1);
				if ($result == null) {
					$path = '/components/' . $user_option . '/' . $name . '.php';
					$result = self::_checkPath($path, -1);
				}
				break;
			case 'admin_html':
				$path = '/components/' . $user_option . '/admin.' . $name . '.html.php';
				$result = self::_checkPath($path, -1);
				break;
			case 'admin_functions':
				$path = '/components/' . $user_option . '/' . $name . '.functions.php';
				$result = self::_checkPath($path, -1);
				break;
			case 'class':
				if (!($result = self::_checkPath('/components/' . $user_option . '/' . $name . '.class.php'))) {
					$result = self::_checkPath('/includes/' . $name . '.php');
				}
				break;
			case 'helper':
				$path = '/components/' . $user_option . '/' . $name . '.helper.php';
				$result = self::_checkPath($path);
				break;
			case 'com_xml':
				$path = '/components/' . $user_option . '/' . $name . '.xml';
				$result = self::_checkPath($path, 1);
				break;
			case 'mod0_xml':
				$path = '/modules/' . $user_option . '/' . $user_option . '.xml';
				$result = self::_checkPath($path);
				break;
			case 'mod1_xml':
				// Admin modules
				$path = '/modules/' . $user_option . '/' . $user_option . '.xml';
				$result = self::_checkPath($path, -1);
				break;
			case 'plg_xml':
				// Site plugins
				$j15path = '/plugins/' . $user_option . '.xml';
				$parts = explode(DIRECTORY_SEPARATOR, $user_option);
				$j16path = '/plugins/' . $user_option . '/' . $parts[1] . '.xml';
				$j15 = self::_checkPath($j15path, 0);
				$j16 = self::_checkPath($j16path, 0);
				// Return 1.6 if working otherwise default to whatever 1.5 gives us
				$result = $j16 ? $j16 : $j15;
				break;
			case 'menu_xml':
				$path = '/components/com_menus/' . $user_option . '/' . $user_option . '.xml';
				$result = self::_checkPath($path, -1);
				break;
		}

		return $result;
	}

	public static function parseXMLInstallFile($path) {
		MLog::add('MApplicationHelper::parseXMLInstallFile is deprecated. Use MInstaller::parseXMLInstallFile instead.', MLog::WARNING, 'deprecated');

		// Read the file to see if it's a valid component XML file
		if (!$xml = MFactory::getXML($path)) {
			return false;
		}

		// Check for a valid XML root tag.

		// Should be 'install', but for backward compatibility we will accept 'extension'.
		// Languages use 'metafile' instead

		if ($xml->getName() != 'install' && $xml->getName() != 'extension' && $xml->getName() != 'metafile') {
			unset($xml);
			return false;
		}

		$data = array();

		$data['legacy'] = ($xml->getName() == 'mosinstall' || $xml->getName() == 'install');

		$data['name'] = (string) $xml->name;

		// Check if we're a language. If so use metafile.
		$data['type'] = $xml->getName() == 'metafile' ? 'language' : (string) $xml->attributes()->type;

		$data['creationDate'] = ((string) $xml->creationDate) ? (string) $xml->creationDate : MText::_('Unknown');
		$data['author'] = ((string) $xml->author) ? (string) $xml->author : MText::_('Unknown');

		$data['copyright'] = (string) $xml->copyright;
		$data['authorEmail'] = (string) $xml->authorEmail;
		$data['authorUrl'] = (string) $xml->authorUrl;
		$data['version'] = (string) $xml->version;
		$data['description'] = (string) $xml->description;
		$data['group'] = (string) $xml->group;

		return $data;
	}

	public static function parseXMLLangMetaFile($path) {
		// Read the file to see if it's a valid component XML file
		$xml = MFactory::getXML($path);

		if (!$xml) {
			return false;
		}

		/*
		 * Check for a valid XML root tag.
		 *
		 * Should be 'langMetaData'.
		 */
		if ($xml->getName() != 'metafile') {
			unset($xml);
			return false;
		}

		$data = array();

		$data['name'] = (string) $xml->name;
		$data['type'] = $xml->attributes()->type;

		$data['creationDate'] = ((string) $xml->creationDate) ? (string) $xml->creationDate : MText::_('MLIB_UNKNOWN');
		$data['author'] = ((string) $xml->author) ? (string) $xml->author : MText::_('MLIB_UNKNOWN');

		$data['copyright'] = (string) $xml->copyright;
		$data['authorEmail'] = (string) $xml->authorEmail;
		$data['authorUrl'] = (string) $xml->authorUrl;
		$data['version'] = (string) $xml->version;
		$data['description'] = (string) $xml->description;
		$data['group'] = (string) $xml->group;

		return $data;
	}

	protected static function _checkPath($path, $checkAdmin = 1) {
		$file = MPATH_SITE . $path;
		if ($checkAdmin > -1 && file_exists($file))	{
			return $file;
		}
		elseif ($checkAdmin != 0) {
			$file = MPATH_ADMINISTRATOR . $path;
			if (file_exists($file))	{
				return $file;
			}
		}

		return null;
	}
}