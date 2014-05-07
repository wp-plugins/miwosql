<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

mimport('framework.filesystem.support.stringcontroller');

class MStreamString {
    protected $_currentstring;
    protected $_path;
    protected $_mode;
    protected $_options;
    protected $_opened_path;
    protected $_pos;
    protected $_len;
    protected $_stat;

    public function stream_open($path, $mode, $options, &$opened_path) {
        $this->_currentstring = & MStringController::getRef(str_replace('string://', '', $path));

        if ($this->_currentstring) {
            $this->_len = strlen($this->_currentstring);
            $this->_pos = 0;
            $this->_stat = $this->url_stat($path, 0);

            return true;
        }
        else {
            return false;
        }
    }

    public function stream_stat() {
        return $this->_stat;
    }

    public function url_stat($path, $flags = 0) {
        $now = time();
        $string = & MStringController::getRef(str_replace('string://', '', $path));
        $stat = array(
            'dev' => 0,
            'ino' => 0,
            'mode' => 0,
            'nlink' => 1,
            'uid' => 0,
            'gid' => 0,
            'rdev' => 0,
            'size' => strlen($string),
            'atime' => $now,
            'mtime' => $now,
            'ctime' => $now,
            'blksize' => '512',
            'blocks' => ceil(strlen($string) / 512));

        return $stat;
    }

    public function stream_read($count) {
        $result = substr($this->_currentstring, $this->_pos, $count);
        $this->_pos += $count;

        return $result;
    }

    public function stream_write($data) {
        // We don't support updating the string.
        return false;
    }

    public function stream_tell() {
        return $this->_pos;
    }

    public function stream_eof() {
        if ($this->_pos > $this->_len) {
            return true;
        }

        return false;
    }

    public function stream_seek($offset, $whence) {
        // $whence: SEEK_SET, SEEK_CUR, SEEK_END
        if ($offset > $this->_len) {
            // We can't seek beyond our len.
            return false;
        }

        switch ($whence) {
            case SEEK_SET:
                $this->_pos = $offset;
                break;

            case SEEK_CUR:
                if (($this->_pos + $offset) < $this->_len) {
                    $this->_pos += $offset;
                }
                else {
                    return false;
                }
                break;

            case SEEK_END:
                $this->_pos = $this->_len - $offset;
                break;
        }

        return true;
    }

    public function stream_flush() {
        // We don't store data.
        return true;
    }
}

stream_wrapper_register('string', 'MStreamString') or die(MText::_('MLIB_FILESYSTEM_STREAM_FAILED'));