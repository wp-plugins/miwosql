<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

abstract class MComponentHelper {

	protected static $components = array();

	public static function getComponent($option, $strict = false) {
		if (!isset(self::$components[$option])) {
			if (self::_load($option)) {
				$result = self::$components[$option];
			}
			else {
				$result = new stdClass;
				$result->enabled = $strict ? false : true;
				$result->params = new MRegistry();
			}
		}
		else {
			$result = self::$components[$option];
		}

		return $result;
	}

	public static function isEnabled($option, $strict = false) {
		$result = self::getComponent($option, $strict);

		return $result->enabled;
	}

	public static function getParams($option, $strict = false) {
		$component = self::getComponent($option, $strict);

		return $component->params;
	}

	public static function filterText($text) {
		return $text;
	}

	public static function renderComponent($option, $params = array()) {
		$app = MFactory::getApplication();
		$lang = MFactory::getLanguage();

		if (empty($option)) {
			throw new Exception(MText::_('MLIB_APPLICATION_ERROR_COMPONENT_NOT_FOUND'), 404);
		}

		// Record the scope
		$scope = $app->scope;

		// Set scope to component name
		$app->scope = $option;

		// Build the component path.
		$option = preg_replace('/[^A-Z0-9_\.-]/i', '', $option);
		$file = substr($option, 4);

		// Define component path.
		define('MPATH_COMPONENT_SITE', MPATH_WP_PLG.'/' . $file . '/site');
		define('MPATH_COMPONENT_ADMINISTRATOR', MPATH_WP_PLG.'/' . $file . '/admin');
		
		if (MFactory::getApplication()->isAdmin()) {
			define('MPATH_COMPONENT', MPATH_COMPONENT_ADMINISTRATOR);
		}
		else {
			define('MPATH_COMPONENT', MPATH_COMPONENT_SITE);
		}

		$path = MPATH_COMPONENT . '/' . $file . '.php';

		// If component is disabled throw error
		if (!self::isEnabled($option) || !file_exists($path)) {
			throw new Exception(MText::_('MLIB_APPLICATION_ERROR_COMPONENT_NOT_FOUND'), 404);
		}

		// Load common and local language files.
        $lang->load($option, MPATH_COMPONENT, null, false, true);

		// Handle template preview outlining.
		$contents = null;

		// Execute the component.
		$contents = self::executeComponent($path);

		// Revert the scope
		$app->scope = $scope;

		return $contents;
	}

	protected static function executeComponent($path) {
		ob_start();
		
		require_once $path;
		$contents = ob_get_contents();
		
		ob_end_clean();
		
		return $contents;
	}

	protected static function _load($option) {
		if (isset(self::$components[$option]) and (self::$components[$option] !== null)) {
			return true;
		}
		
		mimport('framework.filesystem.folder');
		
		$folders = MFolder::folders(MPATH_WP_PLG);

		if (empty($folders)) {
			self::$components[$option] = new stdClass();
			
			return false;
		}
		
		self::$components = array();
		
		$n = count($folders);
		for ($i = 0; $i < $n; $i++) {
			$folder = @$folders[$i];
			
			if (empty($folder)) {
				continue;
			}

			if (substr($folder, 0, 4) != 'miwo') {
				continue;
			}
			
			$com = new stdClass();
			$com->id = $i;
			$com->option = 'com_'.$folder;
			$com->params = MFactory::getWOption($folder);
			$com->enabled = 1;
			
			// Convert the params to an object.
			if (is_string($com->params)) {
				$temp = new MRegistry();
				$temp->loadString($com->params);
				$com->params = $temp;
			}
			
			self::$components[$com->option] = $com;
		}
		
		return true;
	}
}