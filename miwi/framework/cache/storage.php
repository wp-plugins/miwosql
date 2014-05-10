<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MCacheStorage {

	protected $rawname;
	public $_now;
	public $_lifetime;
	public $_locking;
	public $_language;
	public $_application;
	public $_hash;

	public function __construct($options = array()) {
		$config = MFactory::getConfig();
		$this->_hash = md5($config->get('secret'));
		$this->_application = (isset($options['application'])) ? $options['application'] : null;
		$this->_language = (isset($options['language'])) ? $options['language'] : 'en-GB';
		$this->_locking = (isset($options['locking'])) ? $options['locking'] : true;
		$this->_lifetime = (isset($options['lifetime'])) ? $options['lifetime'] * 60 : $config->get('cachetime') * 60;
		$this->_now = (isset($options['now'])) ? $options['now'] : time();

		if (empty($this->_lifetime)) {
			$this->_threshold = $this->_now - 60;
			$this->_lifetime = 60;
		}
		else {
			$this->_threshold = $this->_now - $this->_lifetime;
		}

	}

	public static function getInstance($handler = null, $options = array()) {
		static $now = null;

		MCacheStorage::addIncludePath(MPATH_WP_CNT.'/miwi/framework/cache/storage');

		if (!isset($handler)) {
			$conf = MFactory::getConfig();
			$handler = $conf->get('cache_handler');
			if (empty($handler)) {
				return MError::raiseWarning(500, MText::_('MLIB_CACHE_ERROR_CACHE_HANDLER_NOT_SET'));
			}
		}

		if (is_null($now)) {
			$now = time();
		}

		$options['now'] = $now;
		$handler = strtolower(preg_replace('/[^A-Z0-9_\.-]/i', '', $handler));

		$class = 'MCacheStorage' . ucfirst($handler);
		if (!class_exists($class)) {
			mimport('joomla.filesystem.path');
			if ($path = MPath::find(MCacheStorage::addIncludePath(), strtolower($handler) . '.php')) {
				include_once $path;
			}
			else {
				return MError::raiseWarning(500, MText::sprintf('MLIB_CACHE_ERROR_CACHE_STORAGE_LOAD', $handler));
			}
		}

		return new $class($options);
	}

	public function get($id, $group, $checkTime = true) {
		return false;
	}

	public function getAll() {
		if (!class_exists('MCacheStorageHelper', false)) {
			include_once(MPATH_WP_CNT.'/miwi/framework/cache/storage/helpers/helper.php');
		}
		return;
	}

	public function store($id, $group, $data) {
		return true;
	}

	public function remove($id, $group) {
		return true;
	}

	public function clean($group, $mode = null) {
		return true;
	}

	public function gc() {
		return true;
	}

	public static function test() {
		return true;
	}

	public function lock($id, $group, $locktime) {
		return false;
	}

	public function unlock($id, $group = null) {
		return false;
	}

	protected function _getCacheId($id, $group) {
		$name = md5($this->_application . '-' . $id . '-' . $this->_language);
		$this->rawname = $this->_hash . '-' . $name;
		return $this->_hash . '-cache-' . $group . '-' . $name;
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
}