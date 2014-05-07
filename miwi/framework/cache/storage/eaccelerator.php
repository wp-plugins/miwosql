<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MCacheStorageEaccelerator extends MCacheStorage {

	public function __construct($options = array()) {
		parent::__construct($options);
	}

	public function get($id, $group, $checkTime = true) {
		$cache_id = $this->_getCacheId($id, $group);
		$cache_content = eaccelerator_get($cache_id);
		if ($cache_content === null) {
			return false;
		}
		return $cache_content;
	}

	public function getAll() {
		parent::getAll();

		$keys = eaccelerator_list_keys();

		$secret = $this->_hash;
		$data = array();

		foreach ($keys as $key) {
		
			$name = ltrim($key['name'], ':');
			$namearr = explode('-', $name);

			if ($namearr !== false && $namearr[0] == $secret && $namearr[1] == 'cache') {
				$group = $namearr[2];

				if (!isset($data[$group])) {
					$item = new MCacheStorageHelper($group);
				}
				else {
					$item = $data[$group];
				}

				$item->updateSize($key['size'] / 1024);

				$data[$group] = $item;
			}
		}

		return $data;
	}

	public function store($id, $group, $data) {
		$cache_id = $this->_getCacheId($id, $group);
		return eaccelerator_put($cache_id, $data, $this->_lifetime);
	}

	public function remove($id, $group) {
		$cache_id = $this->_getCacheId($id, $group);
		return eaccelerator_rm($cache_id);
	}

	public function clean($group, $mode = null) {
		$keys = eaccelerator_list_keys();

		$secret = $this->_hash;

		if (is_array($keys)) {
			foreach ($keys as $key) {
				$key['name'] = ltrim($key['name'], ':');

				if (strpos($key['name'], $secret . '-cache-' . $group . '-') === 0 xor $mode != 'group') {
					eaccelerator_rm($key['name']);
				}
			}
		}
		return true;
	}

	public function gc() {
		return eaccelerator_gc();
	}

	public static function test() {
		return (extension_loaded('eaccelerator') && function_exists('eaccelerator_get'));
	}

	public function lock($id, $group, $locktime) {
		$returning = new stdClass;
		$returning->locklooped = false;

		$looptime = $locktime * 10;

		$cache_id = $this->_getCacheId($id, $group);

		$data_lock = eaccelerator_lock($cache_id);

		if ($data_lock === false) {

			$lock_counter = 0;

			while ($data_lock === false) {

				if ($lock_counter > $looptime) {
					$returning->locked = false;
					$returning->locklooped = true;
					break;
				}

				usleep(100);
				$data_lock = eaccelerator_lock($cache_id);
				$lock_counter++;
			}

		}
		$returning->locked = $data_lock;

		return $returning;
	}

	public function unlock($id, $group = null) {
		$cache_id = $this->_getCacheId($id, $group);
		return eaccelerator_unlock($cache_id);
	}
}
