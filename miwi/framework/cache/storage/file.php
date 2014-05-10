<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

mimport('framework.filesystem.file');

class MCacheStorageFile extends MCacheStorage {

	protected $_root;

	public function __construct($options = array()) {
		parent::__construct($options);
		$this->_root = $options['cachebase'];
	}

	public function get($id, $group, $checkTime = true) {
		$data = false;

		$path = $this->_getFilePath($id, $group);

		if ($checkTime == false || ($checkTime == true && $this->_checkExpire($id, $group) === true)) {
			if (file_exists($path)) {
				$data = file_get_contents($path);
				if ($data) {
					// Remove the initial die() statement
					$data = str_replace('<?php die("Access Denied"); ?>#x#', '', $data);
				}
			}

			return $data;
		}
		else {
			return false;
		}
	}

	public function getAll() {
		parent::getAll();

		$path = $this->_root;
		$folders = $this->_folders($path);
		$data = array();

		foreach ($folders as $folder) {
			$files = array();
			$files = $this->_filesInFolder($path . '/' . $folder);
			$item = new MCacheStorageHelper($folder);

			foreach ($files as $file) {
				$item->updateSize(filesize($path . '/' . $folder . '/' . $file) / 1024);
			}
			$data[$folder] = $item;
		}

		return $data;
	}

	public function store($id, $group, $data) {
		$written = false;
		$path = $this->_getFilePath($id, $group);
		$die = '<?php die("Access Denied"); ?>#x#';

		// Prepend a die string
		$data = $die . $data;

		$_fileopen = @fopen($path, "wb");

		if ($_fileopen) {
			$len = strlen($data);
			@fwrite($_fileopen, $data, $len);
			$written = true;
		}

		// Data integrity check
		if ($written && ($data == file_get_contents($path))) {
			return true;
		}
		else {
			return false;
		}
	}

	public function remove($id, $group) {
		$path = $this->_getFilePath($id, $group);
		if (!@unlink($path)) {
			return false;
		}
		return true;
	}

	public function clean($group, $mode = null) {
		$return = true;
		$folder = $group;

		if (trim($folder) == '') {
			$mode = 'notgroup';
		}

		switch ($mode) {
			case 'notgroup':
				$folders = $this->_folders($this->_root);
				for ($i = 0, $n = count($folders); $i < $n; $i++) {
					if ($folders[$i] != $folder) {
						$return |= $this->_deleteFolder($this->_root . '/' . $folders[$i]);
					}
				}
				break;
			case 'group':
			default:
				if (is_dir($this->_root . '/' . $folder)) {
					$return = $this->_deleteFolder($this->_root . '/' . $folder);
				}
				break;
		}
		return $return;
	}

	public function gc() {
		$result = true;
		// files older than lifeTime get deleted from cache
		$files = $this->_filesInFolder($this->_root, '', true, true, array('.svn', 'CVS', '.DS_Store', '__MACOSX', 'index.html'));
		foreach ($files as $file) {
			$time = @filemtime($file);
			if (($time + $this->_lifetime) < $this->_now || empty($time)) {
				$result |= @unlink($file);
			}
		}
		return $result;
	}

	public static function test() {
		$conf = MFactory::getConfig();
		return is_writable($conf->get('cache_path', MPATH_CACHE));
	}

	public function lock($id, $group, $locktime) {
		$returning = new stdClass;
		$returning->locklooped = false;

		$looptime = $locktime * 10;
		$path = $this->_getFilePath($id, $group);

		$_fileopen = @fopen($path, "r+b");

		if ($_fileopen) {
			$data_lock = @flock($_fileopen, LOCK_EX);
		}
		else {
			$data_lock = false;
		}

		if ($data_lock === false) {

			$lock_counter = 0;

			// loop until you find that the lock has been released.  that implies that data get from other thread has finished
			while ($data_lock === false) {

				if ($lock_counter > $looptime) {
					$returning->locked = false;
					$returning->locklooped = true;
					break;
				}

				usleep(100);
				$data_lock = @flock($_fileopen, LOCK_EX);
				$lock_counter++;
			}

		}
		$returning->locked = $data_lock;

		return $returning;
	}

	public function unlock($id, $group = null) {
		$path = $this->_getFilePath($id, $group);

		$_fileopen = @fopen($path, "r+b");

		if ($_fileopen) {
			$ret = @flock($_fileopen, LOCK_UN);
			@fclose($_fileopen);
		}

		return $ret;
	}

	protected function _checkExpire($id, $group) {
		$path = $this->_getFilePath($id, $group);

		// check prune period
		if (file_exists($path)) {
			$time = @filemtime($path);
			if (($time + $this->_lifetime) < $this->_now || empty($time)) {
				@unlink($path);
				return false;
			}
			return true;
		}
		return false;
	}

	protected function _getFilePath($id, $group) {
		$name = $this->_getCacheId($id, $group);
		$dir = $this->_root . '/' . $group;

		// If the folder doesn't exist try to create it
		if (!is_dir($dir)) {

			// Make sure the index file is there
			$indexFile = $dir . '/index.html';
			@ mkdir($dir) && file_put_contents($indexFile, '<!DOCTYPE html><title></title>');
		}

		// Make sure the folder exists
		if (!is_dir($dir)) {
			return false;
		}
		return $dir . '/' . $name . '.php';
	}

	protected function _deleteFolder($path) {
		if (!$path || !is_dir($path) || empty($this->_root)) {
			MError::raiseWarning(500, 'MCacheStorageFile::_deleteFolder ' . MText::_('MLIB_FILESYSTEM_ERROR_DELETE_BASE_DIRECTORY'));
			return false;
		}

		$path = $this->_cleanPath($path);

		$pos = strpos($path, $this->_cleanPath($this->_root));

		if ($pos === false || $pos > 0) {
			MError::raiseWarning(500, 'MCacheStorageFile::_deleteFolder' . MText::sprintf('MLIB_FILESYSTEM_ERROR_PATH_IS_NOT_A_FOLDER', $path));
			return false;
		}

		$files = $this->_filesInFolder($path, '.', false, true, array(), array());

		if (!empty($files) && !is_array($files)) {
			if (@unlink($files) !== true) {
				return false;
			}
		}
		elseif (!empty($files) && is_array($files)) {

			foreach ($files as $file) {
				$file = $this->_cleanPath($file);

				if (@unlink($file)) {
					// Do nothing
				}
				else {
					$filename = basename($file);
					MError::raiseWarning('SOME_ERROR_CODE', 'MCacheStorageFile::_deleteFolder' . MText::sprintf('MLIB_FILESYSTEM_DELETE_FAILED', $filename));
					return false;
				}
			}
		}

		$folders = $this->_folders($path, '.', false, true, array(), array());

		foreach ($folders as $folder) {
			if (is_link($folder)) {
				if (@unlink($folder) !== true) {
					return false;
				}
			}
			elseif ($this->_deleteFolder($folder) !== true) {
				return false;
			}
		}

		if (@rmdir($path)) {
			$ret = true;
		}
		else {
			MError::raiseWarning('SOME_ERROR_CODE', 'MCacheStorageFile::_deleteFolder' . MText::sprintf('MLIB_FILESYSTEM_ERROR_FOLDER_DELETE', $path));
			$ret = false;
		}
		return $ret;
	}

	protected function _cleanPath($path, $ds = DIRECTORY_SEPARATOR) {
		$path = trim($path);

		if (empty($path)) {
			$path = $this->_root;
		}
		else {
			$path = preg_replace('#[/\\\\]+#', $ds, $path);
		}

		return $path;
	}

	protected function _filesInFolder($path, $filter = '.', $recurse = false, $fullpath = false
		, $exclude = array('.svn', 'CVS', '.DS_Store', '__MACOSX'), $excludefilter = array('^\..*', '.*~')) {
		$arr = array();

		$path = $this->_cleanPath($path);

		if (!is_dir($path)) {
			MError::raiseWarning(21, 'MCacheStorageFile::_filesInFolder' . MText::sprintf('MLIB_FILESYSTEM_ERROR_PATH_IS_NOT_A_FOLDER', $path));
			return false;
		}

		if (!($handle = @opendir($path))) {
			return $arr;
		}

		if (count($excludefilter)) {
			$excludefilter = '/(' . implode('|', $excludefilter) . ')/';
		}
		else {
			$excludefilter = '';
		}
		while (($file = readdir($handle)) !== false) {
			if (($file != '.') && ($file != '..') && (!in_array($file, $exclude)) && (!$excludefilter || !preg_match($excludefilter, $file))) {
				$dir = $path . '/' . $file;
				$isDir = is_dir($dir);
				if ($isDir) {
					if ($recurse) {
						if (is_integer($recurse)) {
							$arr2 = $this->_filesInFolder($dir, $filter, $recurse - 1, $fullpath);
						}
						else {
							$arr2 = $this->_filesInFolder($dir, $filter, $recurse, $fullpath);
						}

						$arr = array_merge($arr, $arr2);
					}
				}
				else {
					if (preg_match("/$filter/", $file)) {
						if ($fullpath) {
							$arr[] = $path . '/' . $file;
						}
						else {
							$arr[] = $file;
						}
					}
				}
			}
		}
		closedir($handle);

		return $arr;
	}

	protected function _folders($path, $filter = '.', $recurse = false, $fullpath = false
		, $exclude = array('.svn', 'CVS', '.DS_Store', '__MACOSX'), $excludefilter = array('^\..*')) {
		$arr = array();

		$path = $this->_cleanPath($path);

		if (!is_dir($path)) {
			MError::raiseWarning(21, 'MCacheStorageFile::_folders' . MText::sprintf('MLIB_FILESYSTEM_ERROR_PATH_IS_NOT_A_FOLDER', $path));
			return false;
		}

		if (!($handle = @opendir($path))) {
			return $arr;
		}

		if (count($excludefilter)) {
			$excludefilter_string = '/(' . implode('|', $excludefilter) . ')/';
		}
		else {
			$excludefilter_string = '';
		}
		while (($file = readdir($handle)) !== false) {
			if (($file != '.') && ($file != '..')
				&& (!in_array($file, $exclude))
				&& (empty($excludefilter_string) || !preg_match($excludefilter_string, $file))) {
				
				$dir = $path . '/' . $file;
				$isDir = is_dir($dir);
				if ($isDir) {
					if (preg_match("/$filter/", $file)) {
						if ($fullpath) {
							$arr[] = $dir;
						}
						else {
							$arr[] = $file;
						}
					}
					if ($recurse) {
						if (is_integer($recurse)) {
							$arr2 = $this->_folders($dir, $filter, $recurse - 1, $fullpath, $exclude, $excludefilter);
						}
						else {
							$arr2 = $this->_folders($dir, $filter, $recurse, $fullpath, $exclude, $excludefilter);
						}

						$arr = array_merge($arr, $arr2);
					}
				}
			}
		}
		closedir($handle);

		return $arr;
	}
}