<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

MFormHelper::loadFieldClass('list');

class MFormFieldContentLanguage extends MFormFieldList {

    public $type = 'ContentLanguage';

    protected function getOptions() {
        // Merge any additional options in the XML definition.
        return array_merge(parent::getOptions(), MHtml::_('contentlanguage.existing'));
    }
}
