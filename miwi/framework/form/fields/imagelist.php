<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

MFormHelper::loadFieldClass('filelist');

class MFormFieldImageList extends MFormFieldFileList {

    public $type = 'ImageList';

    protected function getOptions() {
        // Define the image file type filter.
        $filter = '\.png$|\.gif$|\.jpg$|\.bmp$|\.ico$|\.jpeg$|\.psd$|\.eps$';

        // Set the form field element attribute for file type filter.
        $this->element->addAttribute('filter', $filter);

        // Get the field options.
        return parent::getOptions();
    }
}
