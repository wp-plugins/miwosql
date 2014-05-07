<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MCacheController {

	public $cache;
	public $options;

	public function __construct($options) {
		$this->cache = new MCache($options);
		$this->options = & $this->cache->_options;

		foreach ($options as $option => $value) {
			if (isset($options[$option])) {
				$this->options[$option] = $options[$option];
			}
		}
	}

	public function __call($name, $arguments) {
		$nazaj = call_user_func_array(array($this->cache, $name), $arguments);
		return $nazaj;
	}

	public static function getInstance($type = 'output', $options = array()) {
		MCacheController::addIncludePath(MPATH_WP_CNT.'/miwi/framework/cache/controller');

		$type = strtolower(preg_replace('/[^A-Z0-9_\.-]/i', '', $type));

		$class = 'MCacheController' . ucfirst($type);

		if (!class_exists($class)) {
			mimport('framework.filesystem.path');

			if ($path = MPath::find(MCacheController::addIncludePath(), strtolower($type) . '.php')) {
				include_once $path;
			}
			else {
				MError::raiseError(500, 'Unable to load Cache Controller: ' . $type);
			}
		}

		return new $class($options);
	}

	public function setCaching($enabled) {
		$this->cache->setCaching($enabled);
	}

	public function setLifeTime($lt) {
		$this->cache->setLifeTime($lt);
	}

	public static function addIncludePath($path = '') {
		static $paths;

		if (!isset($paths)) {
			$paths = array();
		}
		if (!empty($path) && !in_array($path, $paths)) {
			mimport('framework.filesystem.path');
			array_unshift($paths, MPath::clean($path));
		}
		return $paths;
	}

	public function get($id, $group = null) {
		$data = false;
		$data = $this->cache->get($id, $group);

		if ($data === false) {
			$locktest = new stdClass;
			$locktest->locked = null;
			$locktest->locklooped = null;
			$locktest = $this->cache->lock($id, $group);
			if ($locktest->locked == true && $locktest->locklooped == true) {
				$data = $this->cache->get($id, $group);
			}
			if ($locktest->locked == true) {
				$this->cache->unlock($id, $group);
			}
		}

		if ($data !== false) {
			$data = unserialize(trim($data));
		}
		return $data;
	}

	public function store($data, $id, $group = null) {
		$locktest = new stdClass;
		$locktest->locked = null;
		$locktest->locklooped = null;

		$locktest = $this->cache->lock($id, $group);

		if ($locktest->locked == false && $locktest->locklooped == true) {
			$locktest = $this->cache->lock($id, $group);
		}

		$sucess = $this->cache->store(serialize($data), $id, $group);

		if ($locktest->locked == true) {
			$this->cache->unlock($id, $group);
		}

		return $sucess;
	}
}