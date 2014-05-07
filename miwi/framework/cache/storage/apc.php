<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MCacheStorageApc extends MCacheStorage {

	public function get($id, $group, $checkTime = true) {
		$cache_id = $this->_getCacheId($id, $group);
		return apc_fetch($cache_id);
	}

	public function getAll() {
		parent::getAll();

		$allinfo = apc_cache_info('user');
		$keys = $allinfo['cache_list'];
		$secret = $this->_hash;

		$data = array();

		foreach ($keys as $key) {

			$name = $key['info'];
			$namearr = explode('-', $name);

			if ($namearr !== false && $namearr[0] == $secret && $namearr[1] == 'cache') {
				$group = $namearr[2];

				if (!isset($data[$group])) {
					$item = new MCacheStorageHelper($group);
				}
				else {
					$item = $data[$group];
				}

				$item->updateSize($key['mem_size'] / 1024);

				$data[$group] = $item;
			}
		}

		return $data;
	}

	public function store($id, $group, $data) {
		$cache_id = $this->_getCacheId($id, $group);
		return apc_store($cache_id, $data, $this->_lifetime);
	}

	public function remove($id, $group) {
		$cache_id = $this->_getCacheId($id, $group);
		return apc_delete($cache_id);
	}

	public function clean($group, $mode = null) {
		$allinfo = apc_cache_info('user');
		$keys = $allinfo['cache_list'];
		$secret = $this->_hash;

		foreach ($keys as $key) {
			if (strpos($key['info'], $secret . '-cache-' . $group . '-') === 0 xor $mode != 'group') {
				apc_delete($key['info']);
			}
		}
		return true;
	}

	public function gc() {
		$allinfo = apc_cache_info('user');
		$keys = $allinfo['cache_list'];
		$secret = $this->_hash;

		foreach ($keys as $key) {
			if (strpos($key['info'], $secret . '-cache-')) {
				apc_fetch($key['info']);
			}
		}
	}

	public static function test() {
		return extension_loaded('apc');
	}

	public function lock($id, $group, $locktime) {
		$returning = new stdClass;
		$returning->locklooped = false;

		$looptime = $locktime * 10;

		$cache_id = $this->_getCacheId($id, $group) . '_lock';

		$data_lock = apc_add($cache_id, 1, $locktime);

		if ($data_lock === false) {

			$lock_counter = 0;

			while ($data_lock === false) {

				if ($lock_counter > $looptime) {
					$returning->locked = false;
					$returning->locklooped = true;
					break;
				}

				usleep(100);
				$data_lock = apc_add($cache_id, 1, $locktime);
				$lock_counter++;
			}

		}
		$returning->locked = $data_lock;

		return $returning;
	}

	public function unlock($id, $group = null) {
		$unlock = false;

		$cache_id = $this->_getCacheId($id, $group) . '_lock';

		$unlock = apc_delete($cache_id);
		return $unlock;
	}
}