<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MArchiveZip extends MObject {

    private $_methods = array(0x0 => 'None', 0x1 => 'Shrunk', 0x2 => 'Super Fast', 0x3 => 'Fast', 0x4 => 'Normal', 0x5 => 'Maximum', 0x6 => 'Imploded', 0x8 => 'Deflated');
    private $_ctrlDirHeader = "\x50\x4b\x01\x02";
    private $_ctrlDirEnd = "\x50\x4b\x05\x06\x00\x00\x00\x00";
    private $_fileHeader = "\x50\x4b\x03\x04";
    private $_data = null;
    private $_metadata = null;

    public function create($archive, $files, $options = array()) {
        // Initialise variables.
        $contents = array();
        $ctrldir = array();

        foreach ($files as $file) {
            $this->_addToZIPFile($file, $contents, $ctrldir);
        }

        return $this->_createZIPFile($contents, $ctrldir, $archive);
    }

    public function extract($archive, $destination, $options = array()) {
        if (!is_file($archive)) {
            $this->set('error.message', 'Archive does not exist');

            return false;
        }

        if ($this->hasNativeSupport()) {
            return ($this->_extractNative($archive, $destination, $options)) ? true : MError::raiseWarning(100, $this->get('error.message'));
        }
        else {
            return ($this->_extract($archive, $destination, $options)) ? true : MError::raiseWarning(100, $this->get('error.message'));
        }
    }

    public static function isSupported() {
        return (self::hasNativeSupport() || extension_loaded('zlib'));
    }

    public static function hasNativeSupport() {
        return (function_exists('zip_open') && function_exists('zip_read'));
    }

    public function checkZipData(&$data) {
        if (strpos($data, $this->_fileHeader) === false) {
            return false;
        }
        else {
            return true;
        }
    }

    private function _extract($archive, $destination, $options) {
        // Initialise variables.
        $this->_data = null;
        $this->_metadata = null;

        if (!extension_loaded('zlib')) {
            $this->set('error.message', MText::_('MLIB_FILESYSTEM_ZIP_NOT_SUPPORTED'));

            return false;
        }

        if (!$this->_data = MFile::read($archive)) {
            $this->set('error.message', MText::_('MLIB_FILESYSTEM_ZIP_UNABLE_TO_READ'));

            return false;
        }

        if (!$this->_readZipInfo($this->_data)) {
            $this->set('error.message', MText::_('MLIB_FILESYSTEM_ZIP_INFO_FAILED'));

            return false;
        }

        for ($i = 0, $n = count($this->_metadata); $i < $n; $i++) {
            $lastPathCharacter = substr($this->_metadata[$i]['name'], -1, 1);

            if ($lastPathCharacter !== '/' && $lastPathCharacter !== '\\') {
                $buffer = $this->_getFileData($i);
                $path = MPath::clean($destination . '/' . $this->_metadata[$i]['name']);

                // Make sure the destination folder exists
                if (!MFolder::create(dirname($path))) {
                    $this->set('error.message', MText::_('MLIB_FILESYSTEM_ZIP_UNABLE_TO_CREATE_DESTINATION'));

                    return false;
                }

                if (MFile::write($path, $buffer) === false) {
                    $this->set('error.message', MText::_('MLIB_FILESYSTEM_ZIP_UNABLE_TO_WRITE_ENTRY'));

                    return false;
                }
            }
        }

        return true;
    }

    private function _extractNative($archive, $destination, $options) {
        $zip = zip_open($archive);
        if (is_resource($zip)) {
            // Make sure the destination folder exists
            if (!MFolder::create($destination)) {
                $this->set('error.message', 'Unable to create destination');
                return false;
            }

            // Read files in the archive
            while ($file = @zip_read($zip)) {
                if (zip_entry_open($zip, $file, "r")) {
                    if (substr(zip_entry_name($file), strlen(zip_entry_name($file)) - 1) != "/") {
                        $buffer = zip_entry_read($file, zip_entry_filesize($file));

                        if (MFile::write($destination . '/' . zip_entry_name($file), $buffer) === false) {
                            $this->set('error.message', 'Unable to write entry');
                            return false;
                        }

                        zip_entry_close($file);
                    }
                }
                else {
                    $this->set('error.message', MText::_('MLIB_FILESYSTEM_ZIP_UNABLE_TO_READ_ENTRY'));

                    return false;
                }
            }

            @zip_close($zip);
        }
        else {
            $this->set('error.message', MText::_('MLIB_FILESYSTEM_ZIP_UNABLE_TO_OPEN_ARCHIVE'));

            return false;
        }

        return true;
    }

    private function _readZipInfo(&$data) {
        // Initialise variables.
        $entries = array();

        // Find the last central directory header entry
        $fhLast = strpos($data, $this->_ctrlDirEnd);

        do {
            $last = $fhLast;
        } while (($fhLast = strpos($data, $this->_ctrlDirEnd, $fhLast + 1)) !== false);

        // Find the central directory offset
        $offset = 0;

        if ($last) {
            $endOfCentralDirectory = unpack(
                'vNumberOfDisk/vNoOfDiskWithStartOfCentralDirectory/vNoOfCentralDirectoryEntriesOnDisk/' .
                'vTotalCentralDirectoryEntries/VSizeOfCentralDirectory/VCentralDirectoryOffset/vCommentLength',
                substr($data, $last + 4)
            );
            $offset = $endOfCentralDirectory['CentralDirectoryOffset'];
        }

        // Get details from central directory structure.
        $fhStart = strpos($data, $this->_ctrlDirHeader, $offset);
        $dataLength = strlen($data);

        do {
            if ($dataLength < $fhStart + 31) {
                $this->set('error.message', MText::_('MLIB_FILESYSTEM_ZIP_INVALID_ZIP_DATA'));

                return false;
            }

            $info = unpack('vMethod/VTime/VCRC32/VCompressed/VUncompressed/vLength', substr($data, $fhStart + 10, 20));
            $name = substr($data, $fhStart + 46, $info['Length']);

            $entries[$name] = array(
                'attr' => null,
                'crc' => sprintf("%08s", dechex($info['CRC32'])),
                'csize' => $info['Compressed'],
                'date' => null,
                '_dataStart' => null,
                'name' => $name,
                'method' => $this->_methods[$info['Method']],
                '_method' => $info['Method'],
                'size' => $info['Uncompressed'],
                'type' => null
            );

            $entries[$name]['date'] = mktime(
                (($info['Time'] >> 11) & 0x1f),
                (($info['Time'] >> 5) & 0x3f),
                (($info['Time'] << 1) & 0x3e),
                (($info['Time'] >> 21) & 0x07),
                (($info['Time'] >> 16) & 0x1f),
                ((($info['Time'] >> 25) & 0x7f) + 1980)
            );

            if ($dataLength < $fhStart + 43) {
                $this->set('error.message', 'Invalid ZIP data');
                return false;
            }

            $info = unpack('vInternal/VExternal/VOffset', substr($data, $fhStart + 36, 10));

            $entries[$name]['type'] = ($info['Internal'] & 0x01) ? 'text' : 'binary';
            $entries[$name]['attr'] = (($info['External'] & 0x10) ? 'D' : '-') . (($info['External'] & 0x20) ? 'A' : '-')
                . (($info['External'] & 0x03) ? 'S' : '-') . (($info['External'] & 0x02) ? 'H' : '-') . (($info['External'] & 0x01) ? 'R' : '-');
            $entries[$name]['offset'] = $info['Offset'];

            // Get details from local file header since we have the offset
            $lfhStart = strpos($data, $this->_fileHeader, $entries[$name]['offset']);

            if ($dataLength < $lfhStart + 34) {
                $this->set('error.message', 'Invalid ZIP data');

                return false;
            }

            $info = unpack('vMethod/VTime/VCRC32/VCompressed/VUncompressed/vLength/vExtraLength', substr($data, $lfhStart + 8, 25));
            $name = substr($data, $lfhStart + 30, $info['Length']);
            $entries[$name]['_dataStart'] = $lfhStart + 30 + $info['Length'] + $info['ExtraLength'];

            // Bump the max execution time because not using the built in php zip libs makes this process slow.
            @set_time_limit(ini_get('max_execution_time'));
        } while ((($fhStart = strpos($data, $this->_ctrlDirHeader, $fhStart + 46)) !== false));

        $this->_metadata = array_values($entries);

        return true;
    }

    private function _getFileData($key) {
        if ($this->_metadata[$key]['_method'] == 0x8) {
            return gzinflate(substr($this->_data, $this->_metadata[$key]['_dataStart'], $this->_metadata[$key]['csize']));
        }
        elseif ($this->_metadata[$key]['_method'] == 0x0) {
            /* Files that aren't compressed. */
            return substr($this->_data, $this->_metadata[$key]['_dataStart'], $this->_metadata[$key]['csize']);
        }
        elseif ($this->_metadata[$key]['_method'] == 0x12) {
            // Is bz2 extension loaded?  If not try to load it
            if (!extension_loaded('bz2')) {
                if (MPATH_ISWIN) {
                    @dl('php_bz2.dll');
                }
                else {
                    @dl('bz2.so');
                }
            }

            // If bz2 extension is successfully loaded use it
            if (extension_loaded('bz2')) {
                return bzdecompress(substr($this->_data, $this->_metadata[$key]['_dataStart'], $this->_metadata[$key]['csize']));
            }
        }

        return '';
    }

    private function _unix2DOSTime($unixtime = null) {
        $timearray = (is_null($unixtime)) ? getdate() : getdate($unixtime);

        if ($timearray['year'] < 1980) {
            $timearray['year'] = 1980;
            $timearray['mon'] = 1;
            $timearray['mday'] = 1;
            $timearray['hours'] = 0;
            $timearray['minutes'] = 0;
            $timearray['seconds'] = 0;
        }

        return (($timearray['year'] - 1980) << 25) | ($timearray['mon'] << 21) | ($timearray['mday'] << 16) | ($timearray['hours'] << 11) |
        ($timearray['minutes'] << 5) | ($timearray['seconds'] >> 1);
    }

    private function _addToZIPFile(&$file, &$contents, &$ctrldir) {
        $data = & $file['data'];
        $name = str_replace('\\', '/', $file['name']);

        /* See if time/date information has been provided. */
        $ftime = null;
        if (isset($file['time'])) {
            $ftime = $file['time'];
        }

        // Get the hex time.
        $dtime = dechex($this->_unix2DosTime($ftime));
        $hexdtime = chr(hexdec($dtime[6] . $dtime[7])) . chr(hexdec($dtime[4] . $dtime[5])) . chr(hexdec($dtime[2] . $dtime[3]))
            . chr(hexdec($dtime[0] . $dtime[1]));

        /* Begin creating the ZIP data. */
        $fr = $this->_fileHeader;
        /* Version needed to extract. */
        $fr .= "\x14\x00";
        /* General purpose bit flag. */
        $fr .= "\x00\x00";
        /* Compression method. */
        $fr .= "\x08\x00";
        /* Last modification time/date. */
        $fr .= $hexdtime;

        /* "Local file header" segment. */
        $unc_len = strlen($data);
        $crc = crc32($data);
        $zdata = gzcompress($data);
        $zdata = substr(substr($zdata, 0, strlen($zdata) - 4), 2);
        $c_len = strlen($zdata);

        /* CRC 32 information. */
        $fr .= pack('V', $crc);
        /* Compressed filesize. */
        $fr .= pack('V', $c_len);
        /* Uncompressed filesize. */
        $fr .= pack('V', $unc_len);
        /* Length of filename. */
        $fr .= pack('v', strlen($name));
        /* Extra field length. */
        $fr .= pack('v', 0);
        /* File name. */
        $fr .= $name;

        /* "File data" segment. */
        $fr .= $zdata;

        /* Add this entry to array. */
        $old_offset = strlen(implode('', $contents));
        $contents[] = & $fr;

        /* Add to central directory record. */
        $cdrec = $this->_ctrlDirHeader;
        /* Version made by. */
        $cdrec .= "\x00\x00";
        /* Version needed to extract */
        $cdrec .= "\x14\x00";
        /* General purpose bit flag */
        $cdrec .= "\x00\x00";
        /* Compression method */
        $cdrec .= "\x08\x00";
        /* Last mod time/date. */
        $cdrec .= $hexdtime;
        /* CRC 32 information. */
        $cdrec .= pack('V', $crc);
        /* Compressed filesize. */
        $cdrec .= pack('V', $c_len);
        /* Uncompressed filesize. */
        $cdrec .= pack('V', $unc_len);
        /* Length of filename. */
        $cdrec .= pack('v', strlen($name));
        /* Extra field length. */
        $cdrec .= pack('v', 0);
        /* File comment length. */
        $cdrec .= pack('v', 0);
        /* Disk number start. */
        $cdrec .= pack('v', 0);
        /* Internal file attributes. */
        $cdrec .= pack('v', 0);
        /* External file attributes -'archive' bit set. */
        $cdrec .= pack('V', 32);
        /* Relative offset of local header. */
        $cdrec .= pack('V', $old_offset);
        /* File name. */
        $cdrec .= $name;
        /* Optional extra field, file comment goes here. */

        /* Save to central directory array. */
        $ctrldir[] = & $cdrec;
    }

    private function _createZIPFile(&$contents, &$ctrlDir, $path) {
        $data = implode('', $contents);
        $dir = implode('', $ctrlDir);

        $buffer = $data . $dir . $this->_ctrlDirEnd . /* Total # of entries "on this disk". */
            pack('v', count($ctrlDir)) . /* Total # of entries overall. */
            pack('v', count($ctrlDir)) . /* Size of central directory. */
            pack('V', strlen($dir)) . /* Offset to start of central dir. */
            pack('V', strlen($data)) . /* ZIP file comment length. */
            "\x00\x00";

        if (MFile::write($path, $buffer) === false) {
            return false;
        }
        else {
            return true;
        }
    }
}