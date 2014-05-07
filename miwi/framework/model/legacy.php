<?php
/**
 * @package     Moomla.Legacy
 * @subpackage  Model
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('MPATH_PLATFORM') or die;

abstract class MModelLegacy extends MObject
{
	protected $__state_set = null;
	protected $_db;
	protected $name;
	protected $option = null;
	protected $state;
	protected $event_clean_cache = null;

	public static function addIncludePath($path = '', $prefix = '')
	{
		static $paths;

		if (!isset($paths))
		{
			$paths = array();
		}

		if (!isset($paths[$prefix]))
		{
			$paths[$prefix] = array();
		}

		if (!isset($paths['']))
		{
			$paths[''] = array();
		}

		if (!empty($path))
		{
			mimport('mivi.filesystem.path');

			if (!in_array($path, $paths[$prefix]))
			{
				array_unshift($paths[$prefix], MPath::clean($path));
			}

			if (!in_array($path, $paths['']))
			{
				array_unshift($paths[''], MPath::clean($path));
			}
		}

		return $paths[$prefix];
	}

	public static function addTablePath($path)
	{
		MTable::addIncludePath($path);
	}

	protected static function _createFileName($type, $parts = array())
	{
		$filename = '';

		switch ($type)
		{
			case 'model':
				$filename = strtolower($parts['name']) . '.php';
				break;

		}
		return $filename;
	}

	public static function getInstance($type, $prefix = '', $config = array())
	{
		$type = preg_replace('/[^A-Z0-9_\.-]/i', '', $type);
		$modelClass = $prefix . ucfirst($type);

		if (!class_exists($modelClass))
		{
			mimport('mivi.filesystem.path');
			$path = MPath::find(self::addIncludePath(null, $prefix), self::_createFileName('model', array('name' => $type)));
			if (!$path)
			{
				$path = MPath::find(self::addIncludePath(null, ''), self::_createFileName('model', array('name' => $type)));
			}
			if ($path)
			{
				require_once $path;

				if (!class_exists($modelClass))
				{
					MLog::add(MText::sprintf('MLIB_APPLICATION_ERROR_MODELCLASS_NOT_FOUND', $modelClass), MLog::WARNING, 'merror');
					return false;
				}
			}
			else
			{
				return false;
			}
		}

		return new $modelClass($config);
	}

	public function __construct($config = array())
	{
		// Guess the option from the class name (Option)Model(View).
		if (empty($this->option))
		{
			$r = null;

			if (!preg_match('/(.*)Model/i', get_class($this), $r))
			{
				throw new Exception(MText::_('MLIB_APPLICATION_ERROR_MODEL_GET_NAME'), 500);
			}

			$this->option = 'com_' . strtolower($r[1]);
		}

		// Set the view name
		if (empty($this->name))
		{
			if (array_key_exists('name', $config))
			{
				$this->name = $config['name'];
			}
			else
			{
				$this->name = $this->getName();
			}
		}

		// Set the model state
		if (array_key_exists('state', $config))
		{
			$this->state = $config['state'];
		}
		else
		{
			$this->state = new MObject;
		}

		// Set the model dbo
		if (array_key_exists('dbo', $config))
		{
			$this->_db = $config['dbo'];
		}
		else
		{
			$this->_db = MFactory::getDbo();
		}

		// Set the default view search path
		if (array_key_exists('table_path', $config))
		{
			$this->addTablePath($config['table_path']);
		}
		elseif (defined('MPATH_COMPONENT_ADMINISTRATOR'))
		{
			$this->addTablePath(MPATH_COMPONENT_ADMINISTRATOR . '/tables');
		}

		// Set the internal state marker - used to ignore setting state from the request
		if (!empty($config['ignore_request']))
		{
			$this->__state_set = true;
		}

		// Set the clean cache event
		if (isset($config['event_clean_cache']))
		{
			$this->event_clean_cache = $config['event_clean_cache'];
		}
		elseif (empty($this->event_clean_cache))
		{
			$this->event_clean_cache = 'onContentCleanCache';
		}

	}

	protected function _getList($query, $limitstart = 0, $limit = 0)
	{
		$this->_db->setQuery($query, $limitstart, $limit);
		$result = $this->_db->loadObjectList();

		return $result;
	}

	protected function _getListCount($query)
	{
		$this->_db->setQuery($query);
		$this->_db->execute();

		return $this->_db->getNumRows();
	}

	protected function _createTable($name, $prefix = 'Table', $config = array())
	{
		// Clean the model name
		$name = preg_replace('/[^A-Z0-9_]/i', '', $name);
		$prefix = preg_replace('/[^A-Z0-9_]/i', '', $prefix);

		// Make sure we are returning a DBO object
		if (!array_key_exists('dbo', $config))
		{
			$config['dbo'] = $this->getDbo();
		}

		return MTable::getInstance($name, $prefix, $config);
	}

	public function getDbo()
	{
		return $this->_db;
	}

	public function getName()
	{
		if (empty($this->name))
		{
			$r = null;
			if (!preg_match('/Model(.*)/i', get_class($this), $r))
			{
				throw new Exception(MText::_('MLIB_APPLICATION_ERROR_MODEL_GET_NAME'), 500);
			}
			$this->name = strtolower($r[1]);
		}

		return $this->name;
	}

	public function getState($property = null, $default = null)
	{
		if (!$this->__state_set)
		{
			// Protected method to auto-populate the model state.
			$this->populateState();

			// Set the model state set flag to true.
			$this->__state_set = true;
		}

		return $property === null ? $this->state : $this->state->get($property, $default);
	}

	public function getTable($name = '', $prefix = 'Table', $options = array())
	{
		if (empty($name))
		{
			$name = $this->getName();
		}

		if ($table = $this->_createTable($name, $prefix, $options))
		{
			return $table;
		}

		throw new Exception(MText::sprintf('MLIB_APPLICATION_ERROR_TABLE_NAME_NOT_SUPPORTED', $name), 0);
	}

	protected function populateState()
	{
	}

	public function setDbo($db)
	{
		$this->_db = $db;
	}

	public function setState($property, $value = null)
	{
		return $this->state->set($property, $value);
	}

	protected function cleanCache($group = null, $client_id = 0)
	{
		$conf = MFactory::getConfig();
		$dispatcher = MEventDispatcher::getInstance();

		$options = array(
			'defaultgroup' => ($group) ? $group : (isset($this->option) ? $this->option : MFactory::getApplication()->input->get('option')),
			'cachebase' => ($client_id) ? MPATH_ADMINISTRATOR . '/cache' : $conf->get('cache_path', MPATH_SITE . '/cache'));

		$cache = MCache::getInstance('callback', $options);
		$cache->clean();

		// Trigger the onContentCleanCache event.
		$dispatcher->trigger($this->event_clean_cache, $options);
	}
}