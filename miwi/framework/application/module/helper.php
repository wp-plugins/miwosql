<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

abstract class MModuleHelper {

	protected static $modules = null;

	public static function &getModule($name, $title = null, $module_id = null) {
		$result = null;
		$modules = MModuleHelper::_load($module_id);
		$total = count($modules);

		for ($i = 0; $i < $total; $i++) {
			// Match the name of the module
			if ($modules[$i]->name == $name || $modules[$i]->module == $name) {
				// Match the title if we're looking for a specific instance of the module
				if (!$title || $modules[$i]->title == $title) {
					// Found it
					$result = &$modules[$i];
					break; // Found it
				}
			}
		}

		// If we didn't find it, and the name is mod_something, create a dummy object
		if (is_null($result) && substr($name, 0, 4) == 'mod_') {
			$result            = new stdClass;
			$result->id        = 0;
			$result->title     = '';
			$result->module    = $name;
			$result->position  = '';
			$result->content   = '';
			$result->showtitle = 0;
			$result->control   = '';
			$result->params    = '';
			$result->user      = 0;
		}

		return $result;
	}

	public static function &getModules($position = null) {
		$position = strtolower($position);
		$result = array();

		$modules = MModuleHelper::_load();

		$total = count($modules);
		for ($i = 0; $i < $total; $i++) {
			if (($position == null) or ($modules[$i]->position == $position)) {
				$result[] = $modules[$i];
			}
		}

		return $result;
	}

	public static function isEnabled($module) {
		$result = MModuleHelper::getModule($module);

		return !is_null($result);
	}

	public static function renderModule($module, $attribs = array()) {
		static $chrome;

		if (constant('MDEBUG')) {
			MProfiler::getInstance('Application')->mark('beforeRenderModule ' . $module->module . ' (' . $module->title . ')');
		}

		$app = MFactory::getApplication();

		// Record the scope.
		$scope = $app->scope;

		// Set scope to component name
		$app->scope = $module->module;

		// Get module parameters
		$params = new MRegistry;
		$params->loadString($module->params);

		// Get module path
		$module->module = preg_replace('/[^A-Z0-9_\.-]/i', '', $module->module);
		$path = MPATH_MODULES . '/' . $module->module . '/' . $module->module . '.php';

		// Load the module
		// $module->user is a check for 1.0 custom modules and is deprecated refactoring
		if (empty($module->user) && file_exists($path)) {
			$lang = MFactory::getLanguage();
			// 1.5 or Core then 1.6 3PD
				$lang->load($module->module, MPATH_BASE, null, false, true)
			||	$lang->load($module->module, dirname($path), null, false, true);

			$content = '';
			ob_start();
			include $path;
			$module->content = ob_get_contents() . $content;
			ob_end_clean();
		}

		// Load the module chrome functions
		if (!$chrome) {
			$chrome = array();
		}

		include_once MPATH_MODULES . '/modules.php';
		$chromePath = MPATH_THEMES . '/' . $app->getTemplate() . '/html/modules.php';

		if (!isset($chrome[$chromePath])) {
			if (file_exists($chromePath)) {
				include_once $chromePath;
			}

			$chrome[$chromePath] = true;
		}

		// Make sure a style is set
		if (!isset($attribs['style'])) {
			$attribs['style'] = 'none';
		}

		foreach (explode(' ', $attribs['style']) as $style) {
			$chromeMethod = 'modChrome_' . $style;

			// Apply chrome and render module
			if (function_exists($chromeMethod)) {
				$module->style = $attribs['style'];

				ob_start();
				$chromeMethod($module, $params, $attribs);
				$module->content = ob_get_contents();
				ob_end_clean();
			}
		}

		//revert the scope
		$app->scope = $scope;

		if (constant('MDEBUG')) {
			MProfiler::getInstance('Application')->mark('afterRenderModule ' . $module->module . ' (' . $module->title . ')');
		}

		return $module->content;
	}

	public static function getLayoutPath($module, $layout = 'default') {
		$template = MFactory::getApplication()->getTemplate();
		$defaultLayout = $layout;

		if (strpos($layout, ':') !== false) {
			// Get the template and file name from the string
			$temp = explode(':', $layout);
			$template = ($temp[0] == '_') ? $template : $temp[0];
			$layout = $temp[1];
			$defaultLayout = ($temp[1]) ? $temp[1] : 'default';
		}

		// Build the template and base path for the layout
		$tPath = MPATH_THEMES . '/' . $template . '/html/' . $module . '/' . $layout . '.php';
		$bPath = MPATH_MODULES . '/' . $module . '/tmpl/' . $defaultLayout . '.php';
		$dPath = MPATH_MODULES . '/' . $module . '/tmpl/default.php';

		// If the template has a layout override use it
		if (file_exists($tPath)) {
			return $tPath;
		}
		elseif (file_exists($bPath)) {
			return $bPath;
		}
		else {
			return $dPath;
		}
	}

	protected static function &_load($module_id = null) {
		/*if (self::$modules !== null) {
			return self::$modules;
		}*/
		
		mimport('framework.filesystem.folder');
		
		if (!MFolder::exists(MPATH_MODULES)) {
			self::$modules = 0;
			
			return self::$modules;
		}
		
		$folders = MFolder::folders(MPATH_MODULES);

		if (empty($folders)) {
			self::$modules = 0;
			
			return self::$modules;
		}
		
		self::$modules = array();
		
		foreach ($folders as $folder) {
            if (strpos($folder, 'quickicons')) {
                continue;
            }

			$mod = new stdClass();
			$mod->id = $folder;
			$mod->title = $folder;
			$mod->module = $folder;
			$mod->name = $folder;
			$mod->menuid = 0;
			$mod->position = $folder;
			$mod->user = 0;
			$mod->params = null;
			$mod->style = null;
			$mod->content = '';
			$mod->showtitle = 0;
			$mod->control   = '';

            $params = MFactory::getWOption('widget_'.$folder.'_widget', false, $module_id);
            if ($params != null) {
                $mod->params = json_encode($params);
            }
			
			self::$modules[] = $mod;
		}

		return self::$modules;
	}

	public static function moduleCache($module, $moduleparams, $cacheparams) {
		return array();
	}
}