<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MCacheStorageXcache extends MCacheStorage {

	public function get($id, $group, $checkTime = true) {
		$cache_id = $this->_getCacheId($id, $group);
		$cache_content = xcache_get($cache_id);

		if ($cache_content === null) {
			return false;
		}

		return $cache_content;
	}

	public function getAll() {
		parent::getAll();

		$allinfo = xcache_list(XC_TYPE_VAR, 0);
		$keys = $allinfo['cache_list'];
		$secret = $this->_hash;

		$data = array();

		foreach ($keys as $key) {

			$namearr = explode('-', $key['name']);

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
		$store = xcache_set($cache_id, $data, $this->_lifetime);
		return $store;
	}

	public function remove($id, $group) {
		$cache_id = $this->_getCacheId($id, $group);

		if (!xcache_isset($cache_id)) {
			return true;
		}

		return xcache_unset($cache_id);
	}

	public function clean($group, $mode = null) {
		$allinfo = xcache_list(XC_TYPE_VAR, 0);
		$keys = $allinfo['cache_list'];

		$secret = $this->_hash;
		foreach ($keys as $key) {
			if (strpos($key['name'], $secret . '-cache-' . $group . '-') === 0 xor $mode != 'group') {
				xcache_unset($key['name']);
			}
		}
		return true;
	}

	public function gc() {
		return true;
	}

	public static function test() {
		return (extension_loaded('xcache'));
	}
}