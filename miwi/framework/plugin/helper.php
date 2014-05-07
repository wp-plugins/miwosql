<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

abstract class MPluginHelper {

	protected static $plugins = null;

	public static function getPlugin($type, $plugin = null) {
		$result = array();
		$plugins = self::_load();

		// Find the correct plugin(s) to return.
		if (!$plugin) {
			foreach ($plugins as $p) {
				// Is this the right plugin?
				if ($p->type == $type)
				{
					$result[] = $p;
				}
			}
		}
		else {
			foreach ($plugins as $p) {
				// Is this plugin in the right group?
				if ($p->type == $type && $p->name == $plugin) {
					$result = $p;
					break;
				}
			}
		}

		return $result;
	}

	public static function isEnabled($type, $plugin = null) {
		$result = self::getPlugin($type, $plugin);
		
		return (!empty($result));
	}

	public static function importPlugin($type, $plugin = null, $autocreate = true, $dispatcher = null) {
		static $loaded = array();

		// check for the default args, if so we can optimise cheaply
		$defaults = false;
		if (is_null($plugin) && $autocreate == true && is_null($dispatcher)) {
			$defaults = true;
		}

		if (!isset($loaded[$type]) || !$defaults) {
			$results = null;

			// Load the plugins from the database.
			$plugins = self::_load();

			// Get the specified plugin(s).
			for ($i = 0, $t = count($plugins); $i < $t; $i++) {
				if ($plugins[$i]->type == $type && ($plugin === null || $plugins[$i]->name == $plugin)) {
					self::_import($plugins[$i], $autocreate, $dispatcher);
					$results = true;
				}
			}

			// Bail out early if we're not using default args
			if (!$defaults) {
				return $results;
			}
			$loaded[$type] = $results;
		}

		return $loaded[$type];
	}

	protected static function _import(&$plugin, $autocreate = true, $dispatcher = null) {
		static $paths = array();

		$plugin->type = preg_replace('/[^A-Z0-9_\.-]/i', '', $plugin->type);
		$plugin->name = preg_replace('/[^A-Z0-9_\.-]/i', '', $plugin->name);

		$path = MPATH_PLUGINS . '/plg_' . $plugin->type . '_' . $plugin->name . '/'. $plugin->name . '.php';

		if (!isset($paths[$path])) {
			if (file_exists($path)) {
				if (!isset($paths[$path])) {
					require_once($path);
				}
				
				$paths[$path] = true;

				if ($autocreate) {
					// Makes sure we have an event dispatcher
					if (!is_object($dispatcher)) {
						$dispatcher = MDispatcher::getInstance();
					}

					$className = 'plg' . $plugin->type . $plugin->name;
					if (class_exists($className)) {
						// Load the plugin from the database.
						if (!isset($plugin->params)) {
							// Seems like this could just go bye bye completely
							$plugin = self::getPlugin($plugin->type, $plugin->name);
						}

						// Instantiate and register the plugin.
						new $className($dispatcher, (array) ($plugin));
					}
				}
			}
			else {
				$paths[$path] = false;
			}
		}
	}

	protected static function _load() {
		if (self::$plugins !== null) {
			return self::$plugins;
		}

		mimport('framework.filesystem.folder');
		
		if (!MFolder::exists(MPATH_PLUGINS)) {
			self::$plugins = array();
			
			return self::$plugins;
		}
		
		$folders = MFolder::folders(MPATH_PLUGINS);

		if (empty($folders)) {
			self::$plugins = array();
			
			return self::$plugins;
		}
		
		self::$plugins = array();
		
		foreach ($folders as $folder) {
			$folder = str_replace('plg_', '', $folder);
			
			list($type, $name) = explode('_', $folder);	
			
			$plg = new stdClass();
			$plg->type = $type;
			$plg->name = $name;
			$plg->params = null;

            $xml_file = MPATH_MIWI.'/plugins/plg_'.$type.'_'.$name.'/'.$name.'.xml';
            if (file_exists($xml_file)) {
                $form = MForm::getInstance($folder.'_form', $xml_file, array(), false, 'config');

                $field_sets = $form->getFieldsets();
                if (!empty($field_sets)) {
                    $params = array();

                    foreach ($field_sets as $name => $field_set) {
                        foreach ($form->getFieldset($name) as $field) {
                            $field_name = $field->get('fieldname');
                            $field_value = $field->get('value');

                            $params[$field_name] = $field_value;
                        }
                    }

                    $plg->params = json_encode($params);
                }
            }

			self::$plugins[] = $plg;
		}

		return self::$plugins;
	}
}