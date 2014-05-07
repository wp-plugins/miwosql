<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MArchive {

	public static function extract($archivename, $extractdir) {
		mimport('framework.filesystem.file');
		mimport('framework.filesystem.folder');

		$untar = false;
		$result = false;
		$ext = MFile::getExt(strtolower($archivename));

		// Check if a tar is embedded...gzip/bzip2 can just be plain files!
		if (MFile::getExt(MFile::stripExt(strtolower($archivename))) == 'tar') {
			$untar = true;
		}

		switch ($ext) {
			case 'zip':
				$adapter = MArchive::getAdapter('zip');

				if ($adapter) {
					$result = $adapter->extract($archivename, $extractdir);
				}
				break;
			case 'tar':
				$adapter = MArchive::getAdapter('tar');

				if ($adapter) {
					$result = $adapter->extract($archivename, $extractdir);
				}
				break;
			case 'tgz':
				// This format is a tarball gzip'd
				$untar = true;
			case 'gz':
			case 'gzip':
				// This may just be an individual file (e.g. sql script)
				$adapter = MArchive::getAdapter('gzip');

				if ($adapter) {
					$config = MFactory::getConfig();
					$tmpfname = $config->get('tmp_path') . '/' . uniqid('gzip');
					$gzresult = $adapter->extract($archivename, $tmpfname);

					if ($gzresult instanceof Exception) {
						@unlink($tmpfname);

						return false;
					}

					if ($untar) {
						// Try to untar the file
						$tadapter = MArchive::getAdapter('tar');

						if ($tadapter) {
							$result = $tadapter->extract($tmpfname, $extractdir);
						}
					}
					else {
						$path = MPath::clean($extractdir);
						MFolder::create($path);
						$result = MFile::copy($tmpfname, $path . '/' . MFile::stripExt(MFile::getName(strtolower($archivename))), null, 1);
					}

					@unlink($tmpfname);
				}
				break;
			case 'tbz2':
				// This format is a tarball bzip2'd
				$untar = true;
			case 'bz2':
			case 'bzip2':
				// This may just be an individual file (e.g. sql script)
				$adapter = MArchive::getAdapter('bzip2');

				if ($adapter) {
					$config = MFactory::getConfig();
					$tmpfname = $config->get('tmp_path') . '/' . uniqid('bzip2');
					$bzresult = $adapter->extract($archivename, $tmpfname);

					if ($bzresult instanceof Exception) {
						@unlink($tmpfname);
						return false;
					}

					if ($untar) {
						// Try to untar the file
						$tadapter = MArchive::getAdapter('tar');

						if ($tadapter) {
							$result = $tadapter->extract($tmpfname, $extractdir);
						}
					}
					else {
						$path = MPath::clean($extractdir);
						MFolder::create($path);
						$result = MFile::copy($tmpfname, $path . '/' . MFile::stripExt(MFile::getName(strtolower($archivename))), null, 1);
					}

					@unlink($tmpfname);
				}
				break;
			default:
				MError::raiseWarning(10, MText::_('MLIB_FILESYSTEM_UNKNOWNARCHIVETYPE'));
				return false;
				break;
		}

		if (!$result || $result instanceof Exception) {
			return false;
		}

		return true;
	}

	public static function getAdapter($type) {
		static $adapters;

		if (!isset($adapters)) {
			$adapters = array();
		}

		if (!isset($adapters[$type])) {
			// Try to load the adapter object
			$class = 'MArchive' . ucfirst($type);

			if (!class_exists($class)) {
				$path = dirname(__FILE__) . '/archive/' . strtolower($type) . '.php';
				if (file_exists($path)) {
					require_once $path;
				}
				else {
					MError::raiseError(500, MText::_('MLIB_FILESYSTEM_UNABLE_TO_LOAD_ARCHIVE'));
				}
			}

			$adapters[$type] = new $class;
		}

		return $adapters[$type];
	}
}