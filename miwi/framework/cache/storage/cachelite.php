<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MCacheStorageCachelite extends MCacheStorage {

	protected static $CacheLiteInstance = null;
	protected $_root;

	public function __construct($options = array()) {
		parent::__construct($options);

		$this->_root = $options['cachebase'];

		$cloptions = array(
			'cacheDir' => $this->_root . '/',
			'lifeTime' => $this->_lifetime,
			'fileLocking' => $this->_locking,
			'automaticCleaningFactor' => isset($options['autoclean']) ? $options['autoclean'] : 200,
			'fileNameProtection' => false,
			'hashedDirectoryLevel' => 0,
			'caching' => $options['caching']);

		if (self::$CacheLiteInstance === null) {
			$this->initCache($cloptions);
		}
	}

	protected function initCache($cloptions) {
		require_once 'Cache/Lite.php';

		self::$CacheLiteInstance = new Cache_Lite($cloptions);

		return self::$CacheLiteInstance;
	}

	public function get($id, $group, $checkTime = true) {
		$data = false;
		self::$CacheLiteInstance->setOption('cacheDir', $this->_root . '/' . $group . '/');
		$this->_getCacheId($id, $group);
		$data = self::$CacheLiteInstance->get($this->rawname, $group);

		return $data;
	}

	public function getAll() {
		parent::getAll();

		$path = $this->_root;
		mimport('framework.filesystem.folder');
		$folders = MFolder::folders($path);
		$data = array();

		foreach ($folders as $folder) {
			$files = MFolder::files($path . '/' . $folder);
			$item = new MCacheStorageHelper($folder);

			foreach ($files as $file) {
				$item->updateSize(filesize($path . '/' . $folder . '/' . $file) / 1024);
			}

			$data[$folder] = $item;
		}

		return $data;
	}

	public function store($id, $group, $data) {
		$dir = $this->_root . '/' . $group;

		// If the folder doesn't exist try to create it
		if (!is_dir($dir)) {
			// Make sure the index file is there
			$indexFile = $dir . '/index.html';
			@mkdir($dir) && file_put_contents($indexFile, '<!DOCTYPE html><title></title>');
		}

		// Make sure the folder exists
		if (!is_dir($dir)) {
			return false;
		}

		self::$CacheLiteInstance->setOption('cacheDir', $this->_root . '/' . $group . '/');
		$this->_getCacheId($id, $group);
		$success = self::$CacheLiteInstance->save($data, $this->rawname, $group);

		if ($success == true) {
			return $success;
		}
		else {
			return false;
		}
	}

	public function remove($id, $group) {
		self::$CacheLiteInstance->setOption('cacheDir', $this->_root . '/' . $group . '/');
		$this->_getCacheId($id, $group);
		$success = self::$CacheLiteInstance->remove($this->rawname, $group);

		if ($success == true) {
			return $success;
		}
		else {
			return false;
		}
	}

	public function clean($group, $mode = null) {
		mimport('framework.filesystem.folder');

		if (trim($group) == '') {
			$clmode = 'notgroup';
		}

		if ($mode == null) {
			$clmode = 'group';
		}

		switch ($mode) {
			case 'notgroup':
				$clmode = 'notingroup';
				$success = self::$CacheLiteInstance->clean($group, $clmode);
				break;

			case 'group':
				if (is_dir($this->_root . '/' . $group)) {
					$clmode = $group;
					self::$CacheLiteInstance->setOption('cacheDir', $this->_root . '/' . $group . '/');
					$success = self::$CacheLiteInstance->clean($group, $clmode);
					MFolder::delete($this->_root . '/' . $group);
				}
				else {
					$success = true;
				}

				break;

			default:
				if (is_dir($this->_root . '/' . $group)) {
					$clmode = $group;
					self::$CacheLiteInstance->setOption('cacheDir', $this->_root . '/' . $group . '/');
					$success = self::$CacheLiteInstance->clean($group, $clmode);
				}
				else {
					$success = true;
				}

				break;
		}

		if ($success == true) {
			return $success;
		}
		else {
			return false;
		}
	}

	public function gc() {
		$result = true;
		self::$CacheLiteInstance->setOption('automaticCleaningFactor', 1);
		self::$CacheLiteInstance->setOption('hashedDirectoryLevel', 1);
		$success1 = self::$CacheLiteInstance->_cleanDir($this->_root . '/', false, 'old');

		if (!($dh = opendir($this->_root . '/'))) {
			return false;
		}

		while ($file = readdir($dh)) {
			if (($file != '.') && ($file != '..') && ($file != '.svn')) {
				$file2 = $this->_root . '/' . $file;

				if (is_dir($file2)) {
					$result = ($result and (self::$CacheLiteInstance->_cleanDir($file2 . '/', false, 'old')));
				}
			}
		}

		$success = ($success1 && $result);

		return $success;
	}

	public static function test() {
		@include_once 'Cache/Lite.php';

		if (class_exists('Cache_Lite')) {
			return true;
		}
		else {
			return false;
		}
	}
}