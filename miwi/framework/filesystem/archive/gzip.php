<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/


defined('MIWI') or die('MIWI');

class MArchiveGzip extends MObject {

    private $_flags = array('FTEXT' => 0x01, 'FHCRC' => 0x02, 'FEXTRA' => 0x04, 'FNAME' => 0x08, 'FCOMMENT' => 0x10);
    private $_data = null;

    public function extract($archive, $destination, $options = array()) {
        // Initialise variables.
        $this->_data = null;

        if (!extension_loaded('zlib')) {
            $this->set('error.message', MText::_('MLIB_FILESYSTEM_GZIP_NOT_SUPPORTED'));

            return MError::raiseWarning(100, $this->get('error.message'));
        }

        if (!isset($options['use_streams']) || $options['use_streams'] == false) {
            if (!$this->_data = MFile::read($archive)) {
                $this->set('error.message', 'Unable to read archive');
                return MError::raiseWarning(100, $this->get('error.message'));
            }

            $position = $this->_getFilePosition();
            $buffer = gzinflate(substr($this->_data, $position, strlen($this->_data) - $position));
            if (empty($buffer)) {
                $this->set('error.message', 'Unable to decompress data');
                return MError::raiseWarning(100, $this->get('error.message'));
            }

            if (MFile::write($destination, $buffer) === false) {
                $this->set('error.message', 'Unable to write archive');
                return MError::raiseWarning(100, $this->get('error.message'));
            }
        }
        else {
            // New style! streams!
            $input = MFactory::getStream();
            $input->set('processingmethod', 'gz'); // use gz

            if (!$input->open($archive)) {
                $this->set('error.message', MText::_('MLIB_FILESYSTEM_GZIP_UNABLE_TO_READ'));

                return MError::raiseWarning(100, $this->get('error.message'));
            }

            $output = MFactory::getStream();

            if (!$output->open($destination, 'w')) {
                $this->set('error.message', MText::_('MLIB_FILESYSTEM_GZIP_UNABLE_TO_WRITE'));
                $input->close(); // close the previous file

                return MError::raiseWarning(100, $this->get('error.message'));
            }

            do {
                $this->_data = $input->read($input->get('chunksize', 8196));
                if ($this->_data) {
                    if (!$output->write($this->_data)) {
                        $this->set('error.message', MText::_('MLIB_FILESYSTEM_GZIP_UNABLE_TO_WRITE_FILE'));

                        return MError::raiseWarning(100, $this->get('error.message'));
                    }
                }
            } while ($this->_data);

            $output->close();
            $input->close();
        }
        return true;
    }

    public static function isSupported() {
        return extension_loaded('zlib');
    }

    public function _getFilePosition() {
        // gzipped file... unpack it first
        $position = 0;
        $info = @ unpack('CCM/CFLG/VTime/CXFL/COS', substr($this->_data, $position + 2));

        if (!$info) {
            $this->set('error.message', MText::_('MLIB_FILESYSTEM_GZIP_UNABLE_TO_DECOMPRESS'));
            return false;
        }

        $position += 10;

        if ($info['FLG'] & $this->_flags['FEXTRA']) {
            $XLEN = unpack('vLength', substr($this->_data, $position + 0, 2));
            $XLEN = $XLEN['Length'];
            $position += $XLEN + 2;
        }

        if ($info['FLG'] & $this->_flags['FNAME']) {
            $filenamePos = strpos($this->_data, "\x0", $position);
            $position = $filenamePos + 1;
        }

        if ($info['FLG'] & $this->_flags['FCOMMENT']) {
            $commentPos = strpos($this->_data, "\x0", $position);
            $position = $commentPos + 1;
        }

        if ($info['FLG'] & $this->_flags['FHCRC']) {
            $hcrc = unpack('vCRC', substr($this->_data, $position + 0, 2));
            $hcrc = $hcrc['CRC'];
            $position += 2;
        }

        return $position;
    }
}