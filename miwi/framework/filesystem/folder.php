<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

mimport('framework.filesystem.path');

abstract class MFolder {

    public static function copy($src, $dest, $path = '', $force = false, $use_streams = false) {
        @set_time_limit(ini_get('max_execution_time'));

        // Initialise variables.
        $FTPOptions = MClientHelper::getCredentials('ftp');

        if ($path) {
            $src = MPath::clean($path . '/' . $src);
            $dest = MPath::clean($path . '/' . $dest);
        }

        // Eliminate trailing directory separators, if any
        $src = rtrim($src, DIRECTORY_SEPARATOR);
        $dest = rtrim($dest, DIRECTORY_SEPARATOR);

        if (!self::exists($src)) {
            return MError::raiseError(-1, MText::_('MLIB_FILESYSTEM_ERROR_FIND_SOURCE_FOLDER'));
        }
        if (self::exists($dest) && !$force) {
            return MError::raiseError(-1, MText::_('MLIB_FILESYSTEM_ERROR_FOLDER_EXISTS'));
        }

        // Make sure the destination exists
        if (!self::create($dest)) {
            return MError::raiseError(-1, MText::_('MLIB_FILESYSTEM_ERROR_FOLDER_CREATE'));
        }

        // If we're using ftp and don't have streams enabled
        if ($FTPOptions['enabled'] == 1 && !$use_streams) {
            // Connect the FTP client
            mimport('framework.client.ftp');
            $ftp = MFTP::getInstance($FTPOptions['host'], $FTPOptions['port'], null, $FTPOptions['user'], $FTPOptions['pass']);

            if (!($dh = @opendir($src))) {
                return MError::raiseError(-1, MText::_('MLIB_FILESYSTEM_ERROR_FOLDER_OPEN'));
            }
            // Walk through the directory copying files and recursing into folders.
            while (($file = readdir($dh)) !== false) {
                $sfid = $src . '/' . $file;
                $dfid = $dest . '/' . $file;
                switch (filetype($sfid)) {
                    case 'dir':
                        if ($file != '.' && $file != '..') {
                            $ret = self::copy($sfid, $dfid, null, $force);
                            if ($ret !== true) {
                                return $ret;
                            }
                        }
                        break;

                    case 'file':
                        // Translate path for the FTP account
                        $dfid = MPath::clean(str_replace(MPATH_ROOT, $FTPOptions['root'], $dfid), '/');
                        if (!$ftp->store($sfid, $dfid)) {
                            return MError::raiseError(-1, MText::_('MLIB_FILESYSTEM_ERROR_COPY_FAILED'));
                        }
                        break;
                }
            }
        }
        else {
            if (!($dh = @opendir($src))) {
                return MError::raiseError(-1, MText::_('MLIB_FILESYSTEM_ERROR_FOLDER_OPEN'));
            }
            // Walk through the directory copying files and recursing into folders.
            while (($file = readdir($dh)) !== false) {
                $sfid = $src . '/' . $file;
                $dfid = $dest . '/' . $file;
                switch (filetype($sfid)) {
                    case 'dir':
                        if ($file != '.' && $file != '..') {
                            $ret = self::copy($sfid, $dfid, null, $force, $use_streams);
                            if ($ret !== true) {
                                return $ret;
                            }
                        }
                        break;

                    case 'file':
                        if ($use_streams) {
                            $stream = MFactory::getStream();
                            if (!$stream->copy($sfid, $dfid)) {
                                return MError::raiseError(-1, MText::_('MLIB_FILESYSTEM_ERROR_COPY_FAILED') . ': ' . $stream->getError());
                            }
                        }
                        else {
                            if (!@copy($sfid, $dfid)) {
                                return MError::raiseError(-1, MText::_('MLIB_FILESYSTEM_ERROR_COPY_FAILED'));
                            }
                        }
                        break;
                }
            }
        }
        return true;
    }

    public static function create($path = '', $mode = 0755) {
        // Initialise variables.
        $FTPOptions = MClientHelper::getCredentials('ftp');
        static $nested = 0;

        // Check to make sure the path valid and clean
        $path = MPath::clean($path);

        // Check if parent dir exists
        $parent = dirname($path);
        if (!self::exists($parent)) {
            // Prevent infinite loops!
            $nested++;
            if (($nested > 20) || ($parent == $path)) {
                MError::raiseWarning('SOME_ERROR_CODE', __METHOD__ . ': ' . MText::_('MLIB_FILESYSTEM_ERROR_FOLDER_LOOP'));
                $nested--;
                return false;
            }

            // Create the parent directory
            if (self::create($parent, $mode) !== true) {
                // MFolder::create throws an error
                $nested--;
                return false;
            }

            // OK, parent directory has been created
            $nested--;
        }

        // Check if dir already exists
        if (self::exists($path)) {
            return true;
        }

        // Check for safe mode
        if ($FTPOptions['enabled'] == 1) {
            // Connect the FTP client
            mimport('framework.client.ftp');
            $ftp = MFTP::getInstance($FTPOptions['host'], $FTPOptions['port'], null, $FTPOptions['user'], $FTPOptions['pass']);

            // Translate path to FTP path
            $path = MPath::clean(str_replace(MPATH_ROOT, $FTPOptions['root'], $path), '/');
            $ret = $ftp->mkdir($path);
            $ftp->chmod($path, $mode);
        }
        else {
            // We need to get and explode the open_basedir paths
            $obd = ini_get('open_basedir');

            // If open_basedir is set we need to get the open_basedir that the path is in
            if ($obd != null) {
                if (MPATH_ISWIN) {
                    $obdSeparator = ";";
                }
                else {
                    $obdSeparator = ":";
                }
                // Create the array of open_basedir paths
                $obdArray = explode($obdSeparator, $obd);
                $inBaseDir = false;
                // Iterate through open_basedir paths looking for a match
                foreach ($obdArray as $test) {
                    $test = MPath::clean($test);
                    if (strpos($path, $test) === 0) {
                        $inBaseDir = true;
                        break;
                    }
                }
                if ($inBaseDir == false) {
                    // Return false for MFolder::create because the path to be created is not in open_basedir
                    MError::raiseWarning('SOME_ERROR_CODE', __METHOD__ . ': ' . MText::_('MLIB_FILESYSTEM_ERROR_FOLDER_PATH'));
                    return false;
                }
            }

            // First set umask
            $origmask = @umask(0);

            // Create the path
            if (!$ret = @mkdir($path, $mode)) {
                @umask($origmask);
                MError::raiseWarning(
                    'SOME_ERROR_CODE', __METHOD__ . ': ' . MText::_('MLIB_FILESYSTEM_ERROR_COULD_NOT_CREATE_DIRECTORY') .
                    ' Path: ' . $path
                );
                return false;
            }

            // Reset umask
            @umask($origmask);
        }
        return $ret;
    }

    public static function delete($path) {
        @set_time_limit(ini_get('max_execution_time'));

        // Sanity check
        if (!$path) {
            // Bad programmer! Bad Bad programmer!
            MError::raiseWarning(500, __METHOD__ . ': ' . MText::_('MLIB_FILESYSTEM_ERROR_DELETE_BASE_DIRECTORY'));
            return false;
        }

        // Initialise variables.
        $FTPOptions = MClientHelper::getCredentials('ftp');

        try {
            // Check to make sure the path valid and clean
            $path = MPath::clean($path);
        } catch (UnexpectedValueException $e) {
            throw new UnexpectedValueException($e);
        }

        // Is this really a folder?
        if (!is_dir($path)) {
            MError::raiseWarning(21, MText::sprintf('MLIB_FILESYSTEM_ERROR_PATH_IS_NOT_A_FOLDER', $path));
            return false;
        }

        // Remove all the files in folder if they exist; disable all filtering
        $files = self::files($path, '.', false, true, array(), array());
        if (!empty($files)) {
            mimport('framework.filesystem.file');
            if (MFile::delete($files) !== true) {
                // MFile::delete throws an error
                return false;
            }
        }

        // Remove sub-folders of folder; disable all filtering
        $folders = self::folders($path, '.', false, true, array(), array());
        foreach ($folders as $folder) {
            if (is_link($folder)) {
                // Don't descend into linked directories, just delete the link.
                mimport('framework.filesystem.file');
                if (MFile::delete($folder) !== true) {
                    // MFile::delete throws an error
                    return false;
                }
            }
            elseif (self::delete($folder) !== true) {
                // MFolder::delete throws an error
                return false;
            }
        }

        if ($FTPOptions['enabled'] == 1) {
            // Connect the FTP client
            mimport('framework.client.ftp');
            $ftp = MFTP::getInstance($FTPOptions['host'], $FTPOptions['port'], null, $FTPOptions['user'], $FTPOptions['pass']);
        }

        // In case of restricted permissions we zap it one way or the other
        // as long as the owner is either the webserver or the ftp.
        if (@rmdir($path)) {
            $ret = true;
        }
        elseif ($FTPOptions['enabled'] == 1) {
            // Translate path and delete
            $path = MPath::clean(str_replace(MPATH_ROOT, $FTPOptions['root'], $path), '/');
            // FTP connector throws an error
            $ret = $ftp->delete($path);
        }
        else {
            MError::raiseWarning('SOME_ERROR_CODE', MText::sprintf('MLIB_FILESYSTEM_ERROR_FOLDER_DELETE', $path));
            $ret = false;
        }
        return $ret;
    }

    public static function move($src, $dest, $path = '', $use_streams = false) {
        // Initialise variables.
        $FTPOptions = MClientHelper::getCredentials('ftp');

        if ($path) {
            $src = MPath::clean($path . '/' . $src);
            $dest = MPath::clean($path . '/' . $dest);
        }

        if (!self::exists($src)) {
            return MText::_('MLIB_FILESYSTEM_ERROR_FIND_SOURCE_FOLDER');
        }
        if (self::exists($dest)) {
            return MText::_('MLIB_FILESYSTEM_ERROR_FOLDER_EXISTS');
        }
        if ($use_streams) {
            $stream = MFactory::getStream();
            if (!$stream->move($src, $dest)) {
                return MText::sprintf('MLIB_FILESYSTEM_ERROR_FOLDER_RENAME', $stream->getError());
            }
            $ret = true;
        }
        else {
            if ($FTPOptions['enabled'] == 1) {
                // Connect the FTP client
                mimport('framework.client.ftp');
                $ftp = MFTP::getInstance($FTPOptions['host'], $FTPOptions['port'], null, $FTPOptions['user'], $FTPOptions['pass']);

                //Translate path for the FTP account
                $src = MPath::clean(str_replace(MPATH_ROOT, $FTPOptions['root'], $src), '/');
                $dest = MPath::clean(str_replace(MPATH_ROOT, $FTPOptions['root'], $dest), '/');

                // Use FTP rename to simulate move
                if (!$ftp->rename($src, $dest)) {
                    return MText::_('Rename failed');
                }
                $ret = true;
            }
            else {
                if (!@rename($src, $dest)) {
                    return MText::_('Rename failed');
                }
                $ret = true;
            }
        }
        return $ret;
    }

    public static function exists($path) {
        return is_dir(MPath::clean($path));
    }

    public static function files($path, $filter = '.', $recurse = false, $full = false, $exclude = array('.svn', 'CVS', '.DS_Store', '__MACOSX'), $excludefilter = array('^\..*', '.*~'), $naturalSort = false) {
        // Check to make sure the path valid and clean
        $path = MPath::clean($path);

        // Is the path a folder?
        if (!is_dir($path)) {
            MError::raiseWarning(21, MText::sprintf('MLIB_FILESYSTEM_ERROR_PATH_IS_NOT_A_FOLDER_FILES', $path));
            return false;
        }

        // Compute the excludefilter string
        if (count($excludefilter)) {
            $excludefilter_string = '/(' . implode('|', $excludefilter) . ')/';
        }
        else {
            $excludefilter_string = '';
        }

        // Get the files
        $arr = self::_items($path, $filter, $recurse, $full, $exclude, $excludefilter_string, true);

        // Sort the files based on either natural or alpha method
        if ($naturalSort) {
            natsort($arr);
        }
        else {
            asort($arr);
        }
        return array_values($arr);
    }

    public static function folders($path, $filter = '.', $recurse = false, $full = false, $exclude = array('.svn', 'CVS', '.DS_Store', '__MACOSX'), $excludefilter = array('^\..*')) {
        // Check to make sure the path valid and clean
        $path = MPath::clean($path);

        // Is the path a folder?
        if (!is_dir($path)) {
            MError::raiseWarning(21, MText::sprintf('MLIB_FILESYSTEM_ERROR_PATH_IS_NOT_A_FOLDER_FOLDER', $path));
            return false;
        }

        // Compute the excludefilter string
        if (count($excludefilter)) {
            $excludefilter_string = '/(' . implode('|', $excludefilter) . ')/';
        }
        else {
            $excludefilter_string = '';
        }

        // Get the folders
        $arr = self::_items($path, $filter, $recurse, $full, $exclude, $excludefilter_string, false);

        // Sort the folders
        asort($arr);
        return array_values($arr);
    }

    protected static function _items($path, $filter, $recurse, $full, $exclude, $excludefilter_string, $findfiles) {
        @set_time_limit(ini_get('max_execution_time'));

        // Initialise variables.
        $arr = array();

        // Read the source directory
        if (!($handle = @opendir($path))) {
            return $arr;
        }

        while (($file = readdir($handle)) !== false) {
            if ($file != '.' && $file != '..' && !in_array($file, $exclude)
                && (empty($excludefilter_string) || !preg_match($excludefilter_string, $file))
            ) {
                // Compute the fullpath
                $fullpath = $path . DIRECTORY_SEPARATOR . $file;

                // Compute the isDir flag
                $isDir = is_dir($fullpath);

                if (($isDir xor $findfiles) && preg_match("/$filter/", $file)) {
                    // (fullpath is dir and folders are searched or fullpath is not dir and files are searched) and file matches the filter
                    if ($full) {
                        // Full path is requested
                        $arr[] = $fullpath;
                    }
                    else {
                        // Filename is requested
                        $arr[] = $file;
                    }
                }
                if ($isDir && $recurse) {
                    // Search recursively
                    if (is_integer($recurse)) {
                        // Until depth 0 is reached
                        $arr = array_merge($arr, self::_items($fullpath, $filter, $recurse - 1, $full, $exclude, $excludefilter_string, $findfiles));
                    }
                    else {
                        $arr = array_merge($arr, self::_items($fullpath, $filter, $recurse, $full, $exclude, $excludefilter_string, $findfiles));
                    }
                }
            }
        }
        closedir($handle);
        return $arr;
    }

    public static function listFolderTree($path, $filter, $maxLevel = 3, $level = 0, $parent = 0) {
        $dirs = array();
        if ($level == 0) {
            $GLOBALS['_MFolder_folder_tree_index'] = 0;
        }
        if ($level < $maxLevel) {
            $folders = self::folders($path, $filter);
            // First path, index foldernames
            foreach ($folders as $name) {
                $id = ++$GLOBALS['_MFolder_folder_tree_index'];
                $fullName = MPath::clean($path . '/' . $name);
                $dirs[] = array('id' => $id, 'parent' => $parent, 'name' => $name, 'fullname' => $fullName,
                    'relname' => str_replace(MPATH_ROOT, '', $fullName));
                $dirs2 = self::listFolderTree($fullName, $filter, $maxLevel, $level + 1, $id);
                $dirs = array_merge($dirs, $dirs2);
            }
        }
        return $dirs;
    }

    public static function makeSafe($path) {
        $regex = array('#[^A-Za-z0-9:_\\\/-]#');
        return preg_replace($regex, '', $path);
    }
}
