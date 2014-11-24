<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

// Define a boolean constant as true if a Windows based host
define('MPATH_ISWIN', (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'));

// Define a boolean constant as true if a Mac based host
define('MPATH_ISMAC', (strtoupper(substr(PHP_OS, 0, 3)) === 'MAC'));

if (!defined('DS')) {
	// Define a string constant shortcut for the DIRECTORY_SEPARATOR define
	define('DS', DIRECTORY_SEPARATOR);
}

if (!defined('MPATH_ROOT')) {
	// Define a string constant for the root directory of the file system in native format
	define('MPATH_ROOT', MPath::clean(MPATH_SITE));
}

class MPath {

	public static function canChmod($path) {
		$perms = fileperms($path);
		
		if ($perms !== false) {
			if (@chmod($path, $perms ^ 0001)) {
				@chmod($path, $perms);
				return true;
			}
		}

		return false;
	}

	public static function setPermissions($path, $filemode = '0644', $foldermode = '0755') {
		// Initialise return value
		$ret = true;

		if (is_dir($path)) {
			$dh = opendir($path);

			while ($file = readdir($dh)) {
				if ($file != '.' && $file != '..') {
					$fullpath = $path . '/' . $file;
					
					if (is_dir($fullpath)) {
						if (!MPath::setPermissions($fullpath, $filemode, $foldermode)) {
							$ret = false;
						}
					}
					else {
						if (isset($filemode)) {
							if (!@ chmod($fullpath, octdec($filemode))) {
								$ret = false;
							}
						}
					}
				}
			}
			
			closedir($dh);
			
			if (isset($foldermode)) {
				if (!@ chmod($path, octdec($foldermode))) {
					$ret = false;
				}
			}
		}
		else {
			if (isset($filemode)) {
				$ret = @ chmod($path, octdec($filemode));
			}
		}

		return $ret;
	}

	public static function getPermissions($path) {
		$path = MPath::clean($path);
		$mode = @ decoct(@ fileperms($path) & 0777);

		if (strlen($mode) < 3) {
			return '---------';
		}

		$parsed_mode = '';
		for ($i = 0; $i < 3; $i++) {
			// read
			$parsed_mode .= ($mode{$i} & 04) ? "r" : "-";
			// write
			$parsed_mode .= ($mode{$i} & 02) ? "w" : "-";
			// execute
			$parsed_mode .= ($mode{$i} & 01) ? "x" : "-";
		}

		return $parsed_mode;
	}

	public static function check($path, $ds = DIRECTORY_SEPARATOR) {
		if (strpos($path, '..') !== false) {
			MError::raiseError(20, 'MPath::check Use of relative paths not permitted');
			mexit();
		}

		$path = MPath::clean($path);
		if ((MPATH_ROOT != '') && strpos($path, MPath::clean(MPATH_ROOT)) !== 0) {
			// Don't translate
			//MError::raiseError(20, 'MPath::check Snooping out of bounds @ ' . $path);
			//mexit();
		}

		return $path;
	}

	public static function clean($path, $ds = DIRECTORY_SEPARATOR) {
		if (!is_string($path) && !empty($path)) {
			throw new UnexpectedValueException('MPath::clean: $path is not a string.');
		}

		$path = trim($path);

		if (empty($path)) {
			$path = MPATH_ROOT;
		}
		// Remove double slashes and backslashes and convert all slashes and backslashes to DIRECTORY_SEPARATOR
		// If dealing with a UNC path don't forget to prepend the path with a backslash.
		elseif (($ds == '\\') && ($path[0] == '\\' ) && ( $path[1] == '\\' )) {
			$path = "\\" . preg_replace('#[/\\\\]+#', $ds, $path);
		}
		else {
			$path = preg_replace('#[/\\\\]+#', $ds, $path);
		}

		return $path;
	}

	public static function isOwner($path) {
		mimport('framework.filesystem.file');

		$tmp = md5(MUserHelper::genRandomPassword(16));
		$ssp = ini_get('session.save_path');
		$jtp = MPATH_SITE . '/tmp';

		// Try to find a writable directory
		$dir = is_writable('/tmp') ? '/tmp' : false;
		$dir = (!$dir && is_writable($ssp)) ? $ssp : false;
		$dir = (!$dir && is_writable($jtp)) ? $jtp : false;

		if ($dir) {
			$test = $dir . '/' . $tmp;

			// Create the test file
			$blank = '';
			MFile::write($test, $blank, false);

			// Test ownership
			$return = (fileowner($test) == fileowner($path));

			// Delete the test file
			MFile::delete($test);

			return $return;
		}

		return false;
	}

	public static function find($paths, $file) {
		settype($paths, 'array'); //force to array

		// Start looping through the path set
		foreach ($paths as $path) {
			// Get the path to the file
			$fullname = $path . '/' . $file;

			// Is the path based on a stream?
			if (strpos($path, '://') === false) {
				// Not a stream, so do a realpath() to avoid directory
				// traversal attempts on the local file system.
				$path = realpath($path); // needed for substr() later
				$fullname = realpath($fullname);
			}

			if (file_exists($fullname) && substr($fullname, 0, strlen($path)) == $path) {
				return $fullname;
			}
		}

		// Could not find the file in the set of paths
		return false;
	}
}