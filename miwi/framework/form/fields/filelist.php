<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

mimport('framework.filesystem.folder');
mimport('framework.filesystem.file');
MFormHelper::loadFieldClass('list');

class MFormFieldFileList extends MFormFieldList {

    public $type = 'FileList';

    protected function getOptions() {
        // Initialize variables.
        $options = array();

        // Initialize some field attributes.
        $filter      = (string)$this->element['filter'];
        $exclude     = (string)$this->element['exclude'];
        $stripExt    = (string)$this->element['stripext'];
        $hideNone    = (string)$this->element['hide_none'];
        $hideDefault = (string)$this->element['hide_default'];

        // Get the path in which to search for file options.
        $path = (string)$this->element['directory'];
        if (!is_dir($path)) {
            $path = MPATH_ROOT . '/' . $path;
        }

        // Prepend some default options based on field attributes.
        if (!$hideNone) {
            $options[] = MHtml::_('select.option', '-1', MText::alt('MOPTION_DO_NOT_USE', preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->fieldname)));
        }
        if (!$hideDefault) {
            $options[] = MHtml::_('select.option', '', MText::alt('MOPTION_USE_DEFAULT', preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->fieldname)));
        }

        // Get a list of files in the search path with the given filter.
        $files = MFolder::files($path, $filter);

        // Build the options list from the list of files.
        if (is_array($files)) {
            foreach ($files as $file) {

                // Check to see if the file is in the exclude mask.
                if ($exclude) {
                    if (preg_match(chr(1) . $exclude . chr(1), $file)) {
                        continue;
                    }
                }

                // If the extension is to be stripped, do it.
                if ($stripExt) {
                    $file = MFile::stripExt($file);
                }

                $options[] = MHtml::_('select.option', $file, $file);
            }
        }

        // Merge any additional options in the XML definition.
        $options = array_merge(parent::getOptions(), $options);

        return $options;
    }
}
