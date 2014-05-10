<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

mimport('framework.filesystem.stream');

class MArchiveBzip2 extends MObject {

    private $_data = null;

    public function __construct() {
        self::loadExtension();
    }

    public function extract($archive, $destination, $options = array()) {
        // Initialise variables.
        $this->_data = null;

        if (!extension_loaded('bz2')) {
            $this->set('error.message', MText::_('MLIB_FILESYSTEM_BZIP_NOT_SUPPORTED'));

            return MError::raiseWarning(100, $this->get('error.message'));
        }

        if (!isset($options['use_streams']) || $options['use_streams'] == false) {
            // Old style: read the whole file and then parse it
            if (!$this->_data = MFile::read($archive)) {
                $this->set('error.message', 'Unable to read archive');
                return MError::raiseWarning(100, $this->get('error.message'));
            }

            $buffer = bzdecompress($this->_data);
            unset($this->_data);
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
            $input->set('processingmethod', 'bz'); // use bzip

            if (!$input->open($archive)) {
                $this->set('error.message', MText::_('MLIB_FILESYSTEM_BZIP_UNABLE_TO_READ'));

                return MError::raiseWarning(100, $this->get('error.message'));
            }

            $output = MFactory::getStream();

            if (!$output->open($destination, 'w')) {
                $this->set('error.message', MText::_('MLIB_FILESYSTEM_BZIP_UNABLE_TO_WRITE'));
                $input->close(); // close the previous file

                return MError::raiseWarning(100, $this->get('error.message'));
            }

            do {
                $this->_data = $input->read($input->get('chunksize', 8196));
                if ($this->_data) {
                    if (!$output->write($this->_data)) {
                        $this->set('error.message', MText::_('MLIB_FILESYSTEM_BZIP_UNABLE_TO_WRITE_FILE'));

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
        self::loadExtension();

        return extension_loaded('bz2');
    }

    private static function loadExtension() {
        // Is bz2 extension loaded?  If not try to load it
        if (!extension_loaded('bz2')) {
            if (MPATH_ISWIN) {
                @ dl('php_bz2.dll');
            }
            else {
                @ dl('bz2.so');
            }
        }
    }
}