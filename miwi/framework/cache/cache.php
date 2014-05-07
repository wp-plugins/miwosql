<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MCache extends MObject {

	public static $_handler = array();

	public $_options;

	public function __construct($options) {
		$conf = MFactory::getConfig();

		$this->_options = array(
			'cachebase' => $conf->get('cache_path', MPATH_CACHE),
			'lifetime' => (int) $conf->get('cachetime'),
			'language' => $conf->get('language', 'en-GB'),
			'storage' => $conf->get('cache_handler', ''),
			'defaultgroup' => 'default',
			'locking' => true,
			'locktime' => 15,
			'checkTime' => true,
			'caching' => ($conf->get('caching') >= 1) ? true : false);

		foreach ($options as $option => $value) {
			if (isset($options[$option]) && $options[$option] !== '') {
				$this->_options[$option] = $options[$option];
			}
		}

		if (empty($this->_options['storage'])) {
			$this->_options['caching'] = false;
		}
	}

	public static function getInstance($type = 'output', $options = array()) {
		return MCacheController::getInstance($type, $options);
	}

	public static function getStores() {
		mimport('framework.filesystem.folder');
		$handlers = MFolder::files(dirname(__FILE__) . '/storage', '.php');

		$names = array();
		foreach ($handlers as $handler) {
			$name = substr($handler, 0, strrpos($handler, '.'));
			$class = 'MCacheStorage' . $name;

			if (!class_exists($class)) {
				include_once dirname(__FILE__) . '/storage/' . $name . '.php';
			}

			if (call_user_func_array(array(trim($class), 'test'), array())) {
				$names[] = $name;
			}
		}

		return $names;
	}

	public function setCaching($enabled) {
		$this->_options['caching'] = $enabled;
	}

	public function getCaching() {
		return $this->_options['caching'];
	}

	public function setLifeTime($lt) {
		$this->_options['lifetime'] = $lt;
	}

	public function get($id, $group = null) {
		$group = ($group) ? $group : $this->_options['defaultgroup'];

		$handler = $this->_getStorage();
		if (!($handler instanceof Exception) && $this->_options['caching']) {
			return $handler->get($id, $group, $this->_options['checkTime']);
		}
		return false;
	}

	public function getAll() {
		$handler = $this->_getStorage();
		if (!($handler instanceof Exception) && $this->_options['caching']) {
			return $handler->getAll();
		}
		return false;
	}

	public function store($data, $id, $group = null) {
		$group = ($group) ? $group : $this->_options['defaultgroup'];

		$handler = $this->_getStorage();
		if (!($handler instanceof Exception) && $this->_options['caching']) {
			$handler->_lifetime = $this->_options['lifetime'];
			return $handler->store($id, $group, $data);
		}
		return false;
	}

	public function remove($id, $group = null) {
		$group = ($group) ? $group : $this->_options['defaultgroup'];

		$handler = $this->_getStorage();
		if (!($handler instanceof Exception)) {
			return $handler->remove($id, $group);
		}
		return false;
	}

	public function clean($group = null, $mode = 'group') {
		$group = ($group) ? $group : $this->_options['defaultgroup'];

		$handler = $this->_getStorage();
		if (!($handler instanceof Exception)) {
			return $handler->clean($group, $mode);
		}
		return false;
	}

	public function gc() {
		$handler = $this->_getStorage();
		if (!($handler instanceof Exception)) {
			return $handler->gc();
		}
		return false;
	}

	public function lock($id, $group = null, $locktime = null) {
		$returning = new stdClass;
		$returning->locklooped = false;
		$group = ($group) ? $group : $this->_options['defaultgroup'];

		$locktime = ($locktime) ? $locktime : $this->_options['locktime'];

		$handler = $this->_getStorage();
		if (!($handler instanceof Exception) && $this->_options['locking'] == true && $this->_options['caching'] == true) {
			$locked = $handler->lock($id, $group, $locktime);
			if ($locked !== false) {
				return $locked;
			}
		}

		$curentlifetime = $this->_options['lifetime'];

		$this->_options['lifetime'] = $locktime;

		$looptime = $locktime * 10;
		$id2 = $id . '_lock';

		if ($this->_options['locking'] == true && $this->_options['caching'] == true) {
			$data_lock = $this->get($id2, $group);

		}
		else {
			$data_lock = false;
			$returning->locked = false;
		}

		if ($data_lock !== false) {
			$lock_counter = 0;

			while ($data_lock !== false) {

				if ($lock_counter > $looptime) {
					$returning->locked = false;
					$returning->locklooped = true;
					break;
				}

				usleep(100);
				$data_lock = $this->get($id2, $group);
				$lock_counter++;
			}
		}

		if ($this->_options['locking'] == true && $this->_options['caching'] == true) {
			$returning->locked = $this->store(1, $id2, $group);
		}

		$this->_options['lifetime'] = $curentlifetime;

		return $returning;
	}

	public function unlock($id, $group = null) {
		$unlock = false;
		$group = ($group) ? $group : $this->_options['defaultgroup'];

		$handler = $this->_getStorage();
		if (!($handler instanceof Exception) && $this->_options['caching']) {
			$unlocked = $handler->unlock($id, $group);
			if ($unlocked !== false) {
				return $unlocked;
			}
		}

		if ($this->_options['caching']) {
			$unlock = $this->remove($id . '_lock', $group);
		}

		return $unlock;
	}

	public function &_getStorage() {
		$hash = md5(serialize($this->_options));

		if (isset(self::$_handler[$hash])) {
			return self::$_handler[$hash];
		}

		self::$_handler[$hash] = MCacheStorage::getInstance($this->_options['storage'], $this->_options);

		return self::$_handler[$hash];
	}

	public static function getWorkarounds($data, $options = array()) {
		$app = MFactory::getApplication();
		$document = MFactory::getDocument();
		$body = null;

		if (isset($options['mergehead']) && $options['mergehead'] == 1 && isset($data['head']) && !empty($data['head'])) {
			$document->mergeHeadData($data['head']);
		}
		elseif (isset($data['head']) && method_exists($document, 'setHeadData')) {
			$document->setHeadData($data['head']);
		}

		if (isset($data['pathway']) && is_array($data['pathway'])) {
			$pathway = $app->getPathWay();
			$pathway->setPathway($data['pathway']);
		}

		if (isset($data['module']) && is_array($data['module'])) {
			foreach ($data['module'] as $name => $contents) {
				$document->setBuffer($contents, 'module', $name);
			}
		}

		if (isset($data['body'])) {
			$token = MSession::getFormToken();
			$search = '#<input type="hidden" name="[0-9a-f]{32}" value="1" />#';
			$replacement = '<input type="hidden" name="' . $token . '" value="1" />';
			$data['body'] = preg_replace($search, $replacement, $data['body']);
			$body = $data['body'];
		}

		return $body;
	}

	public static function setWorkarounds($data, $options = array()) {
		$loptions = array();
		$loptions['nopathway'] = 0;
		$loptions['nohead'] = 0;
		$loptions['nomodules'] = 0;
		$loptions['modulemode'] = 0;

		if (isset($options['nopathway'])) {
			$loptions['nopathway'] = $options['nopathway'];
		}

		if (isset($options['nohead'])) {
			$loptions['nohead'] = $options['nohead'];
		}

		if (isset($options['nomodules'])) {
			$loptions['nomodules'] = $options['nomodules'];
		}

		if (isset($options['modulemode'])) {
			$loptions['modulemode'] = $options['modulemode'];
		}

		$app = MFactory::getApplication();
		$document = MFactory::getDocument();

		$buffer1 = $document->getBuffer();
		if (!is_array($buffer1)) {
			$buffer1 = array();
		}

		if (!isset($buffer1['module']) || !is_array($buffer1['module'])) {
			$buffer1['module'] = array();
		}

		$cached['body'] = $data;

		if ($loptions['nohead'] != 1 && method_exists($document, 'getHeadData')) {

			if ($loptions['modulemode'] == 1) {
				$headnow = $document->getHeadData();
				$unset = array('title', 'description', 'link', 'links', 'metaTags');

				foreach ($unset as $un) {
					unset($headnow[$un]);
					unset($options['headerbefore'][$un]);
				}

				$cached['head'] = array();

				foreach ($headnow as $now => $value) {
					if (isset($options['headerbefore'][$now])) {
						$nowvalue 		= array_map('serialize', $headnow[$now]);
						$beforevalue 	= array_map('serialize', $options['headerbefore'][$now]);
						
						$newvalue = array_diff_assoc($nowvalue, $beforevalue);
						$newvalue = array_map('unserialize', $newvalue);
						
						if (($now == 'script' || $now == 'style') && is_array($newvalue) && is_array($options['headerbefore'][$now])) {
							foreach ($newvalue as $type => $currentScriptStr) {
								if (isset($options['headerbefore'][$now][strtolower($type)])) { 
									$oldScriptStr = $options['headerbefore'][$now][strtolower($type)];
									if ($oldScriptStr != $currentScriptStr) {
										$newvalue[strtolower($type)] = MString::substr($currentScriptStr, MString::strlen($oldScriptStr));
									}
								}
							}
						}
					}
					else {
						$newvalue = $headnow[$now];
					}

					if (!empty($newvalue)) {
						$cached['head'][$now] = $newvalue;
					}
				}

			}
			else {
				$cached['head'] = $document->getHeadData();
			}
		}

		if ($app->isSite() && $loptions['nopathway'] != 1) {
			$pathway = $app->getPathWay();
			$cached['pathway'] = isset($data['pathway']) ? $data['pathway'] : $pathway->getPathway();
		}

		if ($loptions['nomodules'] != 1) {
			$buffer2 = $document->getBuffer();
			if (!is_array($buffer2)) {
				$buffer2 = array();
			}

			if (!isset($buffer2['module']) || !is_array($buffer2['module'])) {
				$buffer2['module'] = array();
			}

			$cached['module'] = array_diff_assoc($buffer2['module'], $buffer1['module']);
		}

		return $cached;
	}

	public static function makeId() {
		$app = MFactory::getApplication();

		if (!empty($app->registeredurlparams)) {
			$registeredurlparams = $app->registeredurlparams;
		}
		else {
			return md5(serialize(MRequest::getURI()));
		}
		
		$registeredurlparams->format = 'WORD';
		$registeredurlparams->option = 'WORD';
		$registeredurlparams->view = 'WORD';
		$registeredurlparams->layout = 'WORD';
		$registeredurlparams->tpl = 'CMD';
		$registeredurlparams->id = 'INT';

		$safeuriaddon = new stdClass;

		foreach ($registeredurlparams as $key => $value) {
			$safeuriaddon->$key = MRequest::getVar($key, null, 'default', $value);
		}

		return md5(serialize($safeuriaddon));
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