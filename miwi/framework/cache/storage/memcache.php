<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MCacheStorageMemcache extends MCacheStorage {

	protected static $_db = null;
	protected $_persistent = false;
	protected $_compress = 0;

	public function __construct($options = array()) {
		parent::__construct($options);
		if (self::$_db === null) {
			$this->getConnection();
		}
	}

	protected function getConnection() {
		if ((extension_loaded('memcache') && class_exists('Memcache')) != true) {
			return false;
		}

		$config = MFactory::getConfig();
		$this->_persistent = $config->get('memcache_persist', true);
		$this->_compress = $config->get('memcache_compress', false) == false ? 0 : MEMCACHE_COMPRESSED;

		$server = array();
		$server['host'] = $config->get('memcache_server_host', 'localhost');
		$server['port'] = $config->get('memcache_server_port', 11211);
		// Create the memcache connection
		self::$_db = new Memcache;
		self::$_db->addServer($server['host'], $server['port'], $this->_persistent);

		$memcachetest = @self::$_db->connect($server['host'], $server['port']);
		if ($memcachetest == false) {
			return MError::raiseError(404, "Could not connect to memcache server");
		}

		if (self::$_db->get($this->_hash . '-index') === false) {
			$empty = array();
			self::$_db->set($this->_hash . '-index', $empty, $this->_compress, 0);
		}

		return;
	}

	public function get($id, $group, $checkTime = true) {
		$cache_id = $this->_getCacheId($id, $group);
		$back = self::$_db->get($cache_id);
		return $back;
	}

	public function getAll() {
		parent::getAll();

		$keys = self::$_db->get($this->_hash . '-index');
		$secret = $this->_hash;

		$data = array();

		if (!empty($keys)) {
			foreach ($keys as $key) {
				if (empty($key)) {
					continue;
				}
				$namearr = explode('-', $key->name);

				if ($namearr !== false && $namearr[0] == $secret && $namearr[1] == 'cache') {

					$group = $namearr[2];

					if (!isset($data[$group])) {
						$item = new MCacheStorageHelper($group);
					}
					else {
						$item = $data[$group];
					}

					$item->updateSize($key->size / 1024);

					$data[$group] = $item;
				}
			}
		}

		return $data;
	}

	public function store($id, $group, $data) {
		$cache_id = $this->_getCacheId($id, $group);

		if (!$this->lockindex()) {
			return false;
		}

		$index = self::$_db->get($this->_hash . '-index');
		if ($index === false) {
			$index = array();
		}

		$tmparr = new stdClass;
		$tmparr->name = $cache_id;
		$tmparr->size = strlen($data);
		$index[] = $tmparr;
		self::$_db->replace($this->_hash . '-index', $index, 0, 0);
		$this->unlockindex();

		$config = MFactory::getConfig();
		$lifetime = (int) $config->get('cachetime', 15);
		if ($this->_lifetime == $lifetime) {
			$this->_lifetime = $lifetime * 60;
		}

		if (!self::$_db->replace($cache_id, $data, $this->_compress, $this->_lifetime)) {
			self::$_db->set($cache_id, $data, $this->_compress, $this->_lifetime);
		}

		return true;
	}

	public function remove($id, $group) {
		$cache_id = $this->_getCacheId($id, $group);

		if (!$this->lockindex()) {
			return false;
		}

		$index = self::$_db->get($this->_hash . '-index');
		if ($index === false) {
			$index = array();
		}

		foreach ($index as $key => $value) {
			if ($value->name == $cache_id) {
				unset($index[$key]);
			}
			break;
		}
		self::$_db->replace($this->_hash . '-index', $index, 0, 0);
		$this->unlockindex();

		return self::$_db->delete($cache_id);
	}

	public function clean($group, $mode = null) {
		if (!$this->lockindex()) {
			return false;
		}

		$index = self::$_db->get($this->_hash . '-index');
		if ($index === false) {
			$index = array();
		}

		$secret = $this->_hash;
		foreach ($index as $key => $value) {

			if (strpos($value->name, $secret . '-cache-' . $group . '-') === 0 xor $mode != 'group') {
				self::$_db->delete($value->name, 0);
				unset($index[$key]);
			}
		}
		self::$_db->replace($this->_hash . '-index', $index, 0, 0);
		$this->unlockindex();
		return true;
	}

	public static function test() {
		if ((extension_loaded('memcache') && class_exists('Memcache')) != true) {
			return false;
		}

		$config = MFactory::getConfig();
		$host = $config->get('memcache_server_host', 'localhost');
		$port = $config->get('memcache_server_port', 11211);

		$memcache = new Memcache;
		$memcachetest = @$memcache->connect($host, $port);

		if (!$memcachetest) {
			return false;
		}
		else {
			return true;
		}
	}

	public function lock($id, $group, $locktime) {
		$returning = new stdClass;
		$returning->locklooped = false;

		$looptime = $locktime * 10;

		$cache_id = $this->_getCacheId($id, $group);

		if (!$this->lockindex()) {
			return false;
		}

		$index = self::$_db->get($this->_hash . '-index');
		if ($index === false) {
			$index = array();
		}

		$tmparr = new stdClass;
		$tmparr->name = $cache_id;
		$tmparr->size = 1;
		$index[] = $tmparr;
		self::$_db->replace($this->_hash . '-index', $index, 0, 0);
		$this->unlockindex();

		$data_lock = self::$_db->add($cache_id . '_lock', 1, false, $locktime);

		if ($data_lock === false) {

			$lock_counter = 0;

			while ($data_lock === false) {

				if ($lock_counter > $looptime) {
					$returning->locked = false;
					$returning->locklooped = true;
					break;
				}

				usleep(100);
				$data_lock = self::$_db->add($cache_id . '_lock', 1, false, $locktime);
				$lock_counter++;
			}

		}
		$returning->locked = $data_lock;

		return $returning;
	}

	public function unlock($id, $group = null) {
		$cache_id = $this->_getCacheId($id, $group) . '_lock';

		if (!$this->lockindex()) {
			return false;
		}

		$index = self::$_db->get($this->_hash . '-index');
		if ($index === false) {
			$index = array();
		}

		foreach ($index as $key => $value) {
			if ($value->name == $cache_id) {
				unset($index[$key]);
			}
			break;
		}
		self::$_db->replace($this->_hash . '-index', $index, 0, 0);
		$this->unlockindex();

		return self::$_db->delete($cache_id);
	}

	protected function lockindex() {
		$looptime = 300;
		$data_lock = self::$_db->add($this->_hash . '-index_lock', 1, false, 30);

		if ($data_lock === false) {

			$lock_counter = 0;

			while ($data_lock === false) {
				if ($lock_counter > $looptime) {
					return false;
					break;
				}

				usleep(100);
				$data_lock = self::$_db->add($this->_hash . '-index_lock', 1, false, 30);
				$lock_counter++;
			}
		}

		return true;
	}

	protected function unlockindex() {
		return self::$_db->delete($this->_hash . '-index_lock');
	}
}
