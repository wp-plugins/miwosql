<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU/GPL based on Moomla www.joomla.org
*/

defined('MIWI') or die('MIWI');

mimport('framework.filesystem.helper');

//mimport('framework.utilities.utility'); kullanılan fonction göremedim

class MStream extends MObject {

    protected $filemode = 0644;

    protected $dirmode = 0755;

    protected $chunksize = 8192;

    protected $filename;

    protected $writeprefix;

    protected $readprefix;

    protected $processingmethod = 'f';

    protected $filters = array();

    protected $_fh;

    protected $_filesize;

    protected $_context = null;

    protected $_contextOptions;

    protected $_openmode;

    public function __construct($writeprefix = '', $readprefix = '', $context = array()) {
        $this->writeprefix = $writeprefix;
        $this->readprefix = $readprefix;
        $this->_contextOptions = $context;
        $this->_buildContext();
    }

    public function __destruct() {
        // Attempt to close on destruction if there is a file handle
        if ($this->_fh) {
            @$this->close();
        }
    }

    public function open($filename, $mode = 'r', $use_include_path = false, $context = null, $use_prefix = false, $relative = false, $detectprocessingmode = false) {
        $filename = $this->_getFilename($filename, $mode, $use_prefix, $relative);

        if (!$filename) {
            $this->setError(MText::_('MLIB_FILESYSTEM_ERROR_STREAMS_FILENAME'));
            return false;
        }

        $this->filename = $filename;
        $this->_openmode = $mode;

        $url = parse_url($filename);
        $retval = false;

        if (isset($url['scheme'])) {
            // If we're dealing with a Moomla! stream, load it
            if (MFilesystemHelper::isMiwiStream($url['scheme'])) {
                require_once dirname(__FILE__) . '/streams/' . $url['scheme'] . '.php';
            }

            // We have a scheme! force the method to be f
            $this->processingmethod = 'f';
        }
        elseif ($detectprocessingmode) {
            $ext = strtolower(MFile::getExt($this->filename));

            switch ($ext) {
                case 'tgz':
                case 'gz':
                case 'gzip':
                    $this->processingmethod = 'gz';
                    break;

                case 'tbz2':
                case 'bz2':
                case 'bzip2':
                    $this->processingmethod = 'bz';
                    break;

                default:
                    $this->processingmethod = 'f';
                    break;
            }
        }

        // Capture PHP errors
        $php_errormsg = 'Error Unknown whilst opening a file';
        $track_errors = ini_get('track_errors');
        ini_set('track_errors', true);

        // Decide which context to use:
        switch ($this->processingmethod) {
            // gzip doesn't support contexts or streams
            case 'gz':
                $this->_fh = gzopen($filename, $mode, $use_include_path);
                break;

            // bzip2 is much like gzip except it doesn't use the include path
            case 'bz':
                $this->_fh = bzopen($filename, $mode);
                break;

            // fopen can handle streams
            case 'f':
            default:
                // One supplied at open; overrides everything
                if ($context) {
                    $this->_fh = fopen($filename, $mode, $use_include_path, $context);
                }
                // One provided at initialisation
                elseif ($this->_context) {
                    $this->_fh = fopen($filename, $mode, $use_include_path, $this->_context);
                }
                // No context; all defaults
                else {
                    $this->_fh = fopen($filename, $mode, $use_include_path);
                }
                break;
        }

        if (!$this->_fh) {
            $this->setError($php_errormsg);
        }
        else {
            $retval = true;
        }

        // Restore error tracking to what it was before
        ini_set('track_errors', $track_errors);

        // Return the result
        return $retval;
    }

    public function close() {
        if (!$this->_fh) {
            $this->setError(MText::_('MLIB_FILESYSTEM_ERROR_STREAMS_FILE_NOT_OPEN'));
            return true;
        }

        $retval = false;
        // Capture PHP errors
        $php_errormsg = 'Error Unknown';
        $track_errors = ini_get('track_errors');
        ini_set('track_errors', true);

        switch ($this->processingmethod) {
            case 'gz':
                $res = gzclose($this->_fh);
                break;

            case 'bz':
                $res = bzclose($this->_fh);
                break;

            case 'f':
            default:
                $res = fclose($this->_fh);
                break;
        }

        if (!$res) {
            $this->setError($php_errormsg);
        }
        else {
            // reset this
            $this->_fh = null;
            $retval = true;
        }

        // If we wrote, chmod the file after it's closed
        if ($this->_openmode[0] == 'w') {
            $this->chmod();
        }

        // Restore error tracking to what it was before
        ini_set('track_errors', $track_errors);

        // Return the result
        return $retval;
    }

    public function eof() {
        if (!$this->_fh) {
            $this->setError(MText::_('MLIB_FILESYSTEM_ERROR_STREAMS_FILE_NOT_OPEN'));

            return false;
        }

        // Capture PHP errors
        $php_errormsg = '';
        $track_errors = ini_get('track_errors');
        ini_set('track_errors', true);

        switch ($this->processingmethod) {
            case 'gz':
                $res = gzeof($this->_fh);
                break;

            case 'bz':
            case 'f':
            default:
                $res = feof($this->_fh);
                break;
        }

        if ($php_errormsg) {
            $this->setError($php_errormsg);
        }

        // Restore error tracking to what it was before
        ini_set('track_errors', $track_errors);

        // Return the result
        return $res;
    }

    public function filesize() {
        if (!$this->filename) {
            $this->setError(MText::_('MLIB_FILESYSTEM_ERROR_STREAMS_FILE_NOT_OPEN'));

            return false;
        }

        $retval = false;
        // Capture PHP errors
        $php_errormsg = '';
        $track_errors = ini_get('track_errors');
        ini_set('track_errors', true);
        $res = @filesize($this->filename);

        if (!$res) {
            $tmp_error = '';

            if ($php_errormsg) {
                // Something went wrong.
                // Store the error in case we need it.
                $tmp_error = $php_errormsg;
            }

            $res = MFilesystemHelper::remotefsize($this->filename);

            if (!$res) {
                if ($tmp_error) {
                    // Use the php_errormsg from before
                    $this->setError($tmp_error);
                }
                else {
                    // Error but nothing from php? How strange! Create our own
                    $this->setError(MText::_('MLIB_FILESYSTEM_ERROR_STREAMS_FILE_SIZE'));
                }
            }
            else {
                $this->_filesize = $res;
                $retval = $res;
            }
        }
        else {
            $this->_filesize = $res;
            $retval = $res;
        }

        // Restore error tracking to what it was before.
        ini_set('track_errors', $track_errors);

        // return the result
        return $retval;
    }

    public function gets($length = 0) {
        if (!$this->_fh) {
            $this->setError(MText::_('MLIB_FILESYSTEM_ERROR_STREAMS_FILE_NOT_OPEN'));

            return false;
        }

        $retval = false;
        // Capture PHP errors
        $php_errormsg = 'Error Unknown';
        $track_errors = ini_get('track_errors');
        ini_set('track_errors', true);

        switch ($this->processingmethod) {
            case 'gz':
                $res = $length ? gzgets($this->_fh, $length) : gzgets($this->_fh);
                break;

            case 'bz':
            case 'f':
            default:
                $res = $length ? fgets($this->_fh, $length) : fgets($this->_fh);
                break;
        }

        if (!$res) {
            $this->setError($php_errormsg);
        }
        else {
            $retval = $res;
        }

        // Restore error tracking to what it was before
        ini_set('track_errors', $track_errors);

        // return the result
        return $retval;
    }

    public function read($length = 0) {
        if (!$this->_filesize && !$length) {
            // Get the filesize
            $this->filesize();

            if (!$this->_filesize) {
                // Set it to the biggest and then wait until eof
                $length = -1;
            }
            else {
                $length = $this->_filesize;
            }
        }

        if (!$this->_fh) {
            $this->setError(MText::_('MLIB_FILESYSTEM_ERROR_STREAMS_FILE_NOT_OPEN'));

            return false;
        }

        $retval = false;
        // Capture PHP errors
        $php_errormsg = 'Error Unknown';
        $track_errors = ini_get('track_errors');
        ini_set('track_errors', true);
        $remaining = $length;

        do {
            // Do chunked reads where relevant
            switch ($this->processingmethod) {
                case 'bz':
                    $res = ($remaining > 0) ? bzread($this->_fh, $remaining) : bzread($this->_fh, $this->chunksize);
                    break;

                case 'gz':
                    $res = ($remaining > 0) ? gzread($this->_fh, $remaining) : gzread($this->_fh, $this->chunksize);
                    break;

                case 'f':
                default:
                    $res = ($remaining > 0) ? fread($this->_fh, $remaining) : fread($this->_fh, $this->chunksize);
                    break;
            }

            if (!$res) {
                $this->setError($php_errormsg);
                $remaining = 0; // jump from the loop
            }
            else {
                if (!$retval) {
                    $retval = '';
                }

                $retval .= $res;

                if (!$this->eof()) {
                    $len = strlen($res);
                    $remaining -= $len;
                }
                else {
                    // If it's the end of the file then we've nothing left to read; reset remaining and len
                    $remaining = 0;
                    $length = strlen($retval);
                }
            }
        } while ($remaining || !$length);

        // Restore error tracking to what it was before
        ini_set('track_errors', $track_errors);

        // Return the result
        return $retval;
    }

    public function seek($offset, $whence = SEEK_SET) {
        if (!$this->_fh) {
            $this->setError(MText::_('MLIB_FILESYSTEM_ERROR_STREAMS_FILE_NOT_OPEN'));

            return false;
        }

        $retval = false;
        // Capture PHP errors
        $php_errormsg = '';
        $track_errors = ini_get('track_errors');
        ini_set('track_errors', true);

        switch ($this->processingmethod) {
            case 'gz':
                $res = gzseek($this->_fh, $offset, $whence);
                break;

            case 'bz':
            case 'f':
            default:
                $res = fseek($this->_fh, $offset, $whence);
                break;
        }

        // Seek, interestingly, returns 0 on success or -1 on failure.
        if ($res == -1) {
            $this->setError($php_errormsg);
        }
        else {
            $retval = true;
        }

        // Restore error tracking to what it was before
        ini_set('track_errors', $track_errors);

        // Return the result
        return $retval;
    }

    public function tell() {
        if (!$this->_fh) {
            $this->setError(MText::_('MLIB_FILESYSTEM_ERROR_STREAMS_FILE_NOT_OPEN'));

            return false;
        }

        $res = false;
        // Capture PHP errors
        $php_errormsg = '';
        $track_errors = ini_get('track_errors');
        ini_set('track_errors', true);

        switch ($this->processingmethod) {
            case 'gz':
                $res = gztell($this->_fh);
                break;

            case 'bz':
            case 'f':
            default:
                $res = ftell($this->_fh);
                break;
        }

        // May return 0 so check if it's really false
        if ($res === false) {
            $this->setError($php_errormsg);
        }

        // Restore error tracking to what it was before
        ini_set('track_errors', $track_errors);

        // Return the result
        return $res;
    }

    public function write(&$string, $length = 0, $chunk = 0) {
        if (!$this->_fh) {
            $this->setError(MText::_('MLIB_FILESYSTEM_ERROR_STREAMS_FILE_NOT_OPEN'));

            return false;
        }

        // If the length isn't set, set it to the length of the string.
        if (!$length) {
            $length = strlen($string);
        }

        // If the chunk isn't set, set it to the default.
        if (!$chunk) {
            $chunk = $this->chunksize;
        }

        $retval = true;
        // Capture PHP errors
        $php_errormsg = '';
        $track_errors = ini_get('track_errors');
        ini_set('track_errors', true);
        $remaining = $length;

        do {
            // If the amount remaining is greater than the chunk size, then use the chunk
            $amount = ($remaining > $chunk) ? $chunk : $remaining;
            $res = fwrite($this->_fh, $string, $amount);

            // Returns false on error or the number of bytes written
            if ($res === false) {
                // Returned error
                $this->setError($php_errormsg);
                $retval = false;
                $remaining = 0;
            }
            elseif ($res === 0) {
                // Wrote nothing?
                $remaining = 0;
                $this->setError(MText::_('MLIB_FILESYSTEM_ERROR_NO_DATA_WRITTEN'));
            }
            else {
                // Wrote something
                $remaining -= $res;
            }
        } while ($remaining);

        // Restore error tracking to what it was before.
        ini_set('track_errors', $track_errors);

        // Return the result
        return $retval;
    }

    public function chmod($filename = '', $mode = 0) {
        if (!$filename) {
            if (!isset($this->filename) || !$this->filename) {
                $this->setError(MText::_('MLIB_FILESYSTEM_ERROR_STREAMS_FILENAME'));

                return false;
            }

            $filename = $this->filename;
        }

        // If no mode is set use the default
        if (!$mode) {
            $mode = $this->filemode;
        }

        $retval = false;
        // Capture PHP errors
        $php_errormsg = '';
        $track_errors = ini_get('track_errors');
        ini_set('track_errors', true);
        $sch = parse_url($filename, PHP_URL_SCHEME);

        // Scheme specific options; ftp's chmod support is fun.
        switch ($sch) {
            case 'ftp':
            case 'ftps':
                $res = MFilesystemHelper::ftpChmod($filename, $mode);
                break;

            default:
                $res = chmod($filename, $mode);
                break;
        }

        // Seek, interestingly, returns 0 on success or -1 on failure
        if (!$res) {
            $this->setError($php_errormsg);
        }
        else {
            $retval = true;
        }

        // Restore error tracking to what it was before.
        ini_set('track_errors', $track_errors);

        // Return the result
        return $retval;
    }

    public function get_meta_data() {
        if (!$this->_fh) {
            $this->setError(MText::_('MLIB_FILESYSTEM_ERROR_STREAMS_FILE_NOT_OPEN'));

            return false;
        }

        return stream_get_meta_data($this->_fh);
    }

    public function _buildContext() {
        // According to the manual this always works!
        if (count($this->_contextOptions)) {
            $this->_context = @stream_context_create($this->_contextOptions);
        }
        else {
            $this->_context = null;
        }
    }

    public function setContextOptions($context) {
        $this->_contextOptions = $context;
        $this->_buildContext();
    }

    public function addContextEntry($wrapper, $name, $value) {
        $this->_contextOptions[$wrapper][$name] = $value;
        $this->_buildContext();
    }

    public function deleteContextEntry($wrapper, $name) {
        // Check whether the wrapper is set
        if (isset($this->_contextOptions[$wrapper])) {
            // Check that entry is set for that wrapper
            if (isset($this->_contextOptions[$wrapper][$name])) {
                // Unset the item
                unset($this->_contextOptions[$wrapper][$name]);

                // Check that there are still items there
                if (!count($this->_contextOptions[$wrapper])) {
                    // Clean up an empty wrapper context option
                    unset($this->_contextOptions[$wrapper]);
                }
            }
        }

        // Rebuild the context and apply it to the stream
        $this->_buildContext();
    }

    public function applyContextToStream() {
        $retval = false;

        if ($this->_fh) {
            // Capture PHP errors
            $php_errormsg = 'Unknown error setting context option';
            $track_errors = ini_get('track_errors');
            ini_set('track_errors', true);
            $retval = @stream_context_set_option($this->_fh, $this->_contextOptions);

            if (!$retval) {
                $this->setError($php_errormsg);
            }

            // restore error tracking to what it was before
            ini_set('track_errors', $track_errors);
        }

        return $retval;
    }

    public function appendFilter($filtername, $read_write = STREAM_FILTER_READ, $params = array()) {
        $res = false;

        if ($this->_fh) {
            // Capture PHP errors
            $php_errormsg = '';
            $track_errors = ini_get('track_errors');
            ini_set('track_errors', true);

            $res = @stream_filter_append($this->_fh, $filtername, $read_write, $params);

            if (!$res && $php_errormsg) {
                $this->setError($php_errormsg);
            }
            else {
                $this->filters[] = & $res;
            }

            // Restore error tracking to what it was before.
            ini_set('track_errors', $track_errors);
        }

        return $res;
    }

    public function prependFilter($filtername, $read_write = STREAM_FILTER_READ, $params = array()) {
        $res = false;

        if ($this->_fh) {
            // Capture PHP errors
            $php_errormsg = '';
            $track_errors = ini_get('track_errors');
            ini_set('track_errors', true);
            $res = @stream_filter_prepend($this->_fh, $filtername, $read_write, $params);

            if (!$res && $php_errormsg) {
                $this->setError($php_errormsg); // set the error msg
            }
            else {
                array_unshift($res, '');
                $res[0] = & $this->filters;
            }

            // Restore error tracking to what it was before.
            ini_set('track_errors', $track_errors);
        }

        return $res;
    }

    public function removeFilter(&$resource, $byindex = false) {
        $res = false;
        // Capture PHP errors
        $php_errormsg = '';
        $track_errors = ini_get('track_errors');
        ini_set('track_errors', true);

        if ($byindex) {
            $res = stream_filter_remove($this->filters[$resource]);
        }
        else {
            $res = stream_filter_remove($resource);
        }

        if ($res && $php_errormsg) {
            $this->setError($php_errormsg);
        }

        // Restore error tracking to what it was before.
        ini_set('track_errors', $track_errors);

        return $res;
    }

    public function copy($src, $dest, $context = null, $use_prefix = true, $relative = false) {
        $res = false;

        // Capture PHP errors
        $php_errormsg = '';
        $track_errors = ini_get('track_errors');
        ini_set('track_errors', true);

        $chmodDest = $this->_getFilename($dest, 'w', $use_prefix, $relative);
        $exists = file_exists($dest);
        $context_support = version_compare(PHP_VERSION, '5.3', '>='); // 5.3 provides context support

        if ($exists && !$context_support) {

            $res = $this->open($src);

            if ($res) {
                $reader = $this->_fh;
                $res = $this->open($dest, 'w');

                if ($res) {
                    $res = stream_copy_to_stream($reader, $this->_fh);
                    $tmperror = $php_errormsg; // save this in case fclose throws an error
                    @fclose($reader);
                    $php_errormsg = $tmperror; // restore after fclose
                }
                else {
                    @fclose($reader); // close the reader off
                    $php_errormsg = MText::sprintf('MLIB_FILESYSTEM_ERROR_STREAMS_FAILED_TO_OPEN_WRITER', $this->getError());
                }
            }
            else {
                if (!$php_errormsg) {
                    $php_errormsg = MText::sprintf('MLIB_FILESYSTEM_ERROR_STREAMS_FAILED_TO_OPEN_READER', $this->getError());
                }
            }
        }
        else {
            // Since we're going to open the file directly we need to get the filename.
            // We need to use the same prefix so force everything to write.
            $src = $this->_getFilename($src, 'w', $use_prefix, $relative);
            $dest = $this->_getFilename($dest, 'w', $use_prefix, $relative);

            if ($context_support && $context) {
                // Use the provided context
                $res = @copy($src, $dest, $context);
            }
            elseif ($context_support && $this->_context) {
                // Use the objects context
                $res = @copy($src, $dest, $this->_context);
            }
            else {
                // Don't use any context
                $res = @copy($src, $dest);
            }
        }

        if (!$res && $php_errormsg) {
            $this->setError($php_errormsg);
        }
        else {
            $this->chmod($chmodDest);
        }

        // Restore error tracking to what it was before
        ini_set('track_errors', $track_errors);

        return $res;
    }

    public function move($src, $dest, $context = null, $use_prefix = true, $relative = false) {
        $res = false;

        // Capture PHP errors
        $php_errormsg = '';
        $track_errors = ini_get('track_errors');
        ini_set('track_errors', true);

        $src = $this->_getFilename($src, 'w', $use_prefix, $relative);
        $dest = $this->_getFilename($dest, 'w', $use_prefix, $relative);

        if ($context) {
            // Use the provided context
            $res = @rename($src, $dest, $context);
        }
        elseif ($this->_context) {
            // Use the object's context
            $res = @rename($src, $dest, $this->_context);
        }
        else {
            // Don't use any context
            $res = @rename($src, $dest);
        }

        if (!$res && $php_errormsg) {
            $this->setError($php_errormsg());
        }

        $this->chmod($dest);

        // Restore error tracking to what it was before
        ini_set('track_errors', $track_errors);

        return $res;
    }

    public function delete($filename, $context = null, $use_prefix = true, $relative = false) {
        $res = false;

        // Capture PHP errors
        $php_errormsg = '';
        $track_errors = ini_get('track_errors');
        ini_set('track_errors', true);

        $filename = $this->_getFilename($filename, 'w', $use_prefix, $relative);

        if ($context) {
            // Use the provided context
            $res = @unlink($filename, $context);
        }
        elseif ($this->_context) {
            // Use the object's context
            $res = @unlink($filename, $this->_context);
        }
        else {
            // Don't use any context
            $res = @unlink($filename);
        }

        if (!$res && $php_errormsg) {
            $this->setError($php_errormsg());
        }

        // Restore error tracking to what it was before.
        ini_set('track_errors', $track_errors);

        return $res;
    }

    public function upload($src, $dest, $context = null, $use_prefix = true, $relative = false) {
        if (is_uploaded_file($src)) {
            // Make sure it's an uploaded file
            return $this->copy($src, $dest, $context, $use_prefix, $relative);
        }
        else {
            $this->setError(MText::_('MLIB_FILESYSTEM_ERROR_STREAMS_NOT_UPLOADED_FILE'));

            return false;
        }
    }

    public function writeFile($filename, &$buffer) {
        if ($this->open($filename, 'w')) {
            $result = $this->write($buffer);
            $this->chmod();
            $this->close();

            return $result;
        }

        return false;
    }

    public function _getFilename($filename, $mode, $use_prefix, $relative)
    {
        if ($use_prefix) {
            // Get rid of binary or t, should be at the end of the string
            $tmode = trim($mode, 'btf123456789');

            // Check if it's a write mode then add the appropriate prefix
            // Get rid of MPATH_ROOT (legacy compat) along the way
            if (in_array($tmode, MFilesystemHelper::getWriteModes())) {
                if (!$relative && $this->writeprefix) {
                    $filename = str_replace(MPATH_ROOT, '', $filename);
                }

                $filename = $this->writeprefix . $filename;
            }
            else {
                if (!$relative && $this->readprefix) {
                    $filename = str_replace(MPATH_ROOT, '', $filename);
                }

                $filename = $this->readprefix . $filename;
            }
        }

        return $filename;
    }

    public function getFileHandle() {
        return $this->_fh;
    }
}
