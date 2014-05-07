<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU/GPL based on Moomla www.joomla.org
*/

defined('MIWI') or die('MIWI');

mimport('framework.filesystem.path');

class MFile {

    public static function getExt($file) {
        $dot = strrpos($file, '.') + 1;

        return substr($file, $dot);
    }

    public static function stripExt($file) {
        return preg_replace('#\.[^.]*$#', '', $file);
    }

    public static function makeSafe($file) {
        // Remove any trailing dots, as those aren't ever valid file names.
        $file = rtrim($file, '.');

        $regex = array('#(\.){2,}#', '#[^A-Za-z0-9\.\_\- ]#', '#^\.#');

        return preg_replace($regex, '', $file);
    }

    public static function copy($src, $dest, $path = null, $use_streams = false) {
        // Prepend a base path if it exists
        if ($path) {
            $src = MPath::clean($path . '/' . $src);
            $dest = MPath::clean($path . '/' . $dest);
        }

        // Check src path
        if (!is_readable($src)) {
            MError::raiseWarning(21, MText::sprintf('MLIB_FILESYSTEM_ERROR_MFile_FIND_COPY', $src));

            return false;
        }

        if ($use_streams) {
            $stream = MFactory::getStream();

            if (!$stream->copy($src, $dest)) {
                MError::raiseWarning(21, MText::sprintf('MLIB_FILESYSTEM_ERROR_MFile_STREAMS', $src, $dest, $stream->getError()));

                return false;
            }

            return true;
        }
        else {
            // Initialise variables.
            $FTPOptions = MClientHelper::getCredentials('ftp');

            if ($FTPOptions['enabled'] == 1) {
                // Connect the FTP client
                mimport('framework.client.ftp');
                $ftp = MFTP::getInstance($FTPOptions['host'], $FTPOptions['port'], null, $FTPOptions['user'], $FTPOptions['pass']);

                // If the parent folder doesn't exist we must create it
                if (!file_exists(dirname($dest))) {
                    mimport('framework.filesystem.folder');
                    MFolder::create(dirname($dest));
                }

                // Translate the destination path for the FTP account
                $dest = MPath::clean(str_replace(MPATH_ROOT, $FTPOptions['root'], $dest), '/');
                if (!$ftp->store($src, $dest)) {

                    // FTP connector throws an error
                    return false;
                }
                $ret = true;
            }
            else {
                if (!@ copy($src, $dest)) {
                    MError::raiseWarning(21, MText::_('MLIB_FILESYSTEM_ERROR_COPY_FAILED'));

                    return false;
                }
                $ret = true;
            }

            return $ret;
        }
    }

    public static function delete($file) {
        // Initialise variables.
        mimport('framework.client.helper');
        $FTPOptions = MClientHelper::getCredentials('ftp');

        if (is_array($file)) {
            $files = $file;
        }
        else {
            $files[] = $file;
        }

        // Do NOT use ftp if it is not enabled
        if ($FTPOptions['enabled'] == 1) {
            // Connect the FTP client
            mimport('framework.client.ftp');
            $ftp = MFTP::getInstance($FTPOptions['host'], $FTPOptions['port'], null, $FTPOptions['user'], $FTPOptions['pass']);
        }

        foreach ($files as $file) {
            $file = MPath::clean($file);

            // Try making the file writable first. If it's read-only, it can't be deleted
            // on Windows, even if the parent folder is writable
            @chmod($file, 0777);

            // In case of restricted permissions we zap it one way or the other
            // as long as the owner is either the webserver or the ftp
            if (@unlink($file)) {
                // Do nothing
            }
            elseif ($FTPOptions['enabled'] == 1) {
                $file = MPath::clean(str_replace(MPATH_ROOT, $FTPOptions['root'], $file), '/');
                if (!$ftp->delete($file)) {
                    // FTP connector throws an error

                    return false;
                }
            }
            else {
                $filename = basename($file);
                MError::raiseWarning('SOME_ERROR_CODE', MText::sprintf('MLIB_FILESYSTEM_DELETE_FAILED', $filename));

                return false;
            }
        }

        return true;
    }

    public static function move($src, $dest, $path = '', $use_streams = false) {
        if ($path) {
            $src = MPath::clean($path . '/' . $src);
            $dest = MPath::clean($path . '/' . $dest);
        }

        // Check src path
        if (!is_readable($src)) {

            return MText::_('MLIB_FILESYSTEM_CANNOT_FIND_SOURCE_FILE');
        }

        if ($use_streams) {
            $stream = MFactory::getStream();

            if (!$stream->move($src, $dest)) {
                MError::raiseWarning(21, MText::sprintf('MLIB_FILESYSTEM_ERROR_MFile_MOVE_STREAMS', $stream->getError()));

                return false;
            }

            return true;
        }
        else {
            // Initialise variables.
            mimport('framework.client.helper');
            $FTPOptions = MClientHelper::getCredentials('ftp');

            if ($FTPOptions['enabled'] == 1) {
                // Connect the FTP client
                mimport('framework.client.ftp');
                $ftp = MFTP::getInstance($FTPOptions['host'], $FTPOptions['port'], null, $FTPOptions['user'], $FTPOptions['pass']);

                // Translate path for the FTP account
                $src = MPath::clean(str_replace(MPATH_ROOT, $FTPOptions['root'], $src), '/');
                $dest = MPath::clean(str_replace(MPATH_ROOT, $FTPOptions['root'], $dest), '/');

                // Use FTP rename to simulate move
                if (!$ftp->rename($src, $dest)) {
                    MError::raiseWarning(21, MText::_('MLIB_FILESYSTEM_ERROR_RENAME_FILE'));

                    return false;
                }
            }
            else {
                if (!@ rename($src, $dest)) {
                    MError::raiseWarning(21, MText::_('MLIB_FILESYSTEM_ERROR_RENAME_FILE'));

                    return false;
                }
            }

            return true;
        }
    }

    public static function read($filename, $incpath = false, $amount = 0, $chunksize = 8192, $offset = 0) {
        // Initialise variables.
        $data = null;
        if ($amount && $chunksize > $amount) {
            $chunksize = $amount;
        }

        if (false === $fh = fopen($filename, 'rb', $incpath)) {
            MError::raiseWarning(21, MText::sprintf('MLIB_FILESYSTEM_ERROR_READ_UNABLE_TO_OPEN_FILE', $filename));

            return false;
        }

        clearstatcache();

        if ($offset) {
            fseek($fh, $offset);
        }

        if ($fsize = @ filesize($filename)) {
            if ($amount && $fsize > $amount) {
                $data = fread($fh, $amount);
            }
            else {
                $data = fread($fh, $fsize);
            }
        }
        else {
            $data = '';
            // While it's:
            // 1: Not the end of the file AND
            // 2a: No Max Amount set OR
            // 2b: The length of the data is less than the max amount we want
            while (!feof($fh) && (!$amount || strlen($data) < $amount)) {
                $data .= fread($fh, $chunksize);
            }
        }
        fclose($fh);

        return $data;
    }

    public static function write($file, &$buffer, $use_streams = false) {
        @set_time_limit(ini_get('max_execution_time'));

        // If the destination directory doesn't exist we need to create it
        if (!file_exists(dirname($file))) {
            mimport('framework.filesystem.folder');
            MFolder::create(dirname($file));
        }

        if ($use_streams) {
            $stream = MFactory::getStream();
            // Beef up the chunk size to a meg
            $stream->set('chunksize', (1024 * 1024 * 1024));

            if (!$stream->writeFile($file, $buffer)) {
                MError::raiseWarning(21, MText::sprintf('MLIB_FILESYSTEM_ERROR_WRITE_STREAMS', $file, $stream->getError()));
                return false;
            }

            return true;
        }
        else {
            // Initialise variables.
            $FTPOptions = MClientHelper::getCredentials('ftp');

            if ($FTPOptions['enabled'] == 1) {
                // Connect the FTP client
                mimport('framework.client.ftp');
                $ftp = MFTP::getInstance($FTPOptions['host'], $FTPOptions['port'], null, $FTPOptions['user'], $FTPOptions['pass']);

                // Translate path for the FTP account and use FTP write buffer to file
                $file = MPath::clean(str_replace(MPATH_ROOT, $FTPOptions['root'], $file), '/');
                $ret = $ftp->write($file, $buffer);
            }
            else {
                $file = MPath::clean($file);
                $ret = is_int(file_put_contents($file, $buffer)) ? true : false;
            }

            return $ret;
        }
    }

    public static function upload($src, $dest, $use_streams = false) {
        // Ensure that the path is valid and clean
        $dest = MPath::clean($dest);

        // Create the destination directory if it does not exist
        $baseDir = dirname($dest);

        if (!file_exists($baseDir)) {
            mimport('framework.filesystem.folder');
            MFolder::create($baseDir);
        }

        if ($use_streams) {
            $stream = MFactory::getStream();

            if (!$stream->upload($src, $dest)) {
                MError::raiseWarning(21, MText::sprintf('MLIB_FILESYSTEM_ERROR_UPLOAD', $stream->getError()));
                return false;
            }

            return true;
        }
        else {
            // Initialise variables.
            $FTPOptions = MClientHelper::getCredentials('ftp');
            $ret = false;

            if ($FTPOptions['enabled'] == 1) {
                // Connect the FTP client
                mimport('framework.client.ftp');
                $ftp = MFTP::getInstance($FTPOptions['host'], $FTPOptions['port'], null, $FTPOptions['user'], $FTPOptions['pass']);

                // Translate path for the FTP account
                $dest = MPath::clean(str_replace(MPATH_ROOT, $FTPOptions['root'], $dest), '/');

                // Copy the file to the destination directory
                if (is_uploaded_file($src) && $ftp->store($src, $dest)) {
                    unlink($src);
                    $ret = true;
                }
                else {
                    MError::raiseWarning(21, MText::_('MLIB_FILESYSTEM_ERROR_WARNFS_ERR02'));
                }
            }
            else {
                if (is_writeable($baseDir) && move_uploaded_file($src, $dest)) {
                    // Short circuit to prevent file permission errors
                    if (MPath::setPermissions($dest)) {
                        $ret = true;
                    }
                    else {
                        MError::raiseWarning(21, MText::_('MLIB_FILESYSTEM_ERROR_WARNFS_ERR01'));
                    }
                }
                else {
                    MError::raiseWarning(21, MText::_('MLIB_FILESYSTEM_ERROR_WARNFS_ERR02'));
                }
            }

            return $ret;
        }
    }

    public static function exists($file) {
        return is_file(MPath::clean($file));
    }

    public static function getName($file) {
        // Convert back slashes to forward slashes
        $file = str_replace('\\', '/', $file);
        $slash = strrpos($file, '/');
        if ($slash !== false) {

            return substr($file, $slash + 1);
        }
        else {

            return $file;
        }
    }
}
