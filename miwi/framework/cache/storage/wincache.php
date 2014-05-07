<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MCacheStorageWincache extends MCacheStorage {

	public function __construct($options = array()) {
		parent::__construct($options);
	}

	public function get($id, $group, $checkTime = true) {
		$cache_id = $this->_getCacheId($id, $group);
		$cache_content = wincache_ucache_get($cache_id);
		return $cache_content;
	}

	public function getAll() {
		parent::getAll();

		$allinfo = wincache_ucache_info();
		$keys = $allinfo['cache_entries'];
		$secret = $this->_hash;
		$data = array();

		foreach ($keys as $key) {
			$name = $key['key_name'];
			$namearr = explode('-', $name);
			if ($namearr !== false && $namearr[0] == $secret && $namearr[1] == 'cache') {
				$group = $namearr[2];
				if (!isset($data[$group])) {
					$item = new MCacheStorageHelper($group);
				}
				else {
					$item = $data[$group];
				}
				if (isset($key['value_size'])) {
					$item->updateSize($key['value_size'] / 1024);
				}
				else {
					$item->updateSize(1);
				}
				$data[$group] = $item;
			}
		}

		return $data;
	}

	public function store($id, $group, $data) {
		$cache_id = $this->_getCacheId($id, $group);
		return wincache_ucache_set($cache_id, $data, $this->_lifetime);
	}

	public function remove($id, $group) {
		$cache_id = $this->_getCacheId($id, $group);
		return wincache_ucache_delete($cache_id);
	}

	public function clean($group, $mode = null) {
		$allinfo = wincache_ucache_info();
		$keys = $allinfo['cache_entries'];
		$secret = $this->_hash;

		foreach ($keys as $key) {
			if (strpos($key['key_name'], $secret . '-cache-' . $group . '-') === 0 xor $mode != 'group') {
				wincache_ucache_delete($key['key_name']);
			}
		}
		return true;
	}

	public function gc() {
		$allinfo = wincache_ucache_info();
		$keys = $allinfo['cache_entries'];
		$secret = $this->_hash;

		foreach ($keys as $key) {
			if (strpos($key['key_name'], $secret . '-cache-')) {
				wincache_ucache_get($key['key_name']);
			}
		}
	}

	public static function test() {
		$test = extension_loaded('wincache') && function_exists('wincache_ucache_get') && !strcmp(ini_get('wincache.ucenabled'), '1');
		return $test;
	}
}
