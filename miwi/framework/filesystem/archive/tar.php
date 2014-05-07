<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU/GPL based on Moomla www.joomla.org
*/

defined('MIWI') or die('MIWI');

class MArchiveTar extends MObject {

    private $_types = array(
        0x0 => 'Unix file',
        0x30 => 'File',
        0x31 => 'Link',
        0x32 => 'Symbolic link',
        0x33 => 'Character special file',
        0x34 => 'Block special file',
        0x35 => 'Directory',
        0x36 => 'FIFO special file',
        0x37 => 'Contiguous file');

    private $_data = null;
    private $_metadata = null;

    public function extract($archive, $destination, $options = array()) {
        // Initialise variables.
        $this->_data = null;
        $this->_metadata = null;

        if (!$this->_data = MFile::read($archive)) {
            $this->set('error.message', 'Unable to read archive');
            return MError::raiseWarning(100, $this->get('error.message'));
        }

        if (!$this->_getTarInfo($this->_data)) {
            return MError::raiseWarning(100, $this->get('error.message'));
        }

        for ($i = 0, $n = count($this->_metadata); $i < $n; $i++) {
            $type = strtolower($this->_metadata[$i]['type']);
            if ($type == 'file' || $type == 'unix file') {
                $buffer = $this->_metadata[$i]['data'];
                $path = MPath::clean($destination . '/' . $this->_metadata[$i]['name']);
                // Make sure the destination folder exists
                if (!MFolder::create(dirname($path))) {
                    $this->set('error.message', 'Unable to create destination');
                    return MError::raiseWarning(100, $this->get('error.message'));
                }
                if (MFile::write($path, $buffer) === false) {
                    $this->set('error.message', 'Unable to write entry');
                    return MError::raiseWarning(100, $this->get('error.message'));
                }
            }
        }
        return true;
    }

    public static function isSupported() {
        return true;
    }

    protected function _getTarInfo(& $data) {
        $position = 0;
        $return_array = array();

        while ($position < strlen($data)) {
            $info = @unpack(
                "a100filename/a8mode/a8uid/a8gid/a12size/a12mtime/a8checksum/Ctypeflag/a100link/a6magic/a2version/a32uname/a32gname/a8devmajor/a8devminor",
                substr($data, $position)
            );
            if (!$info) {
                $this->set('error.message', 'Unable to decompress data');
                return false;
            }

            $position += 512;
            $contents = substr($data, $position, octdec($info['size']));
            $position += ceil(octdec($info['size']) / 512) * 512;

            if ($info['filename']) {
                $file = array(
                    'attr' => null,
                    'data' => null,
                    'date' => octdec($info['mtime']),
                    'name' => trim($info['filename']),
                    'size' => octdec($info['size']),
                    'type' => isset($this->_types[$info['typeflag']]) ? $this->_types[$info['typeflag']] : null);

                if (($info['typeflag'] == 0) || ($info['typeflag'] == 0x30) || ($info['typeflag'] == 0x35)) {
                    /* File or folder. */
                    $file['data'] = $contents;

                    $mode = hexdec(substr($info['mode'], 4, 3));
                    $file['attr'] = (($info['typeflag'] == 0x35) ? 'd' : '-') . (($mode & 0x400) ? 'r' : '-') . (($mode & 0x200) ? 'w' : '-') .
                        (($mode & 0x100) ? 'x' : '-') . (($mode & 0x040) ? 'r' : '-') . (($mode & 0x020) ? 'w' : '-') . (($mode & 0x010) ? 'x' : '-') .
                        (($mode & 0x004) ? 'r' : '-') . (($mode & 0x002) ? 'w' : '-') . (($mode & 0x001) ? 'x' : '-');
                }
                else {
                    /* Some other type. */
                }
                $return_array[] = $file;
            }
        }
        $this->_metadata = $return_array;
        return true;
    }
}