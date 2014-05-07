<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

MFormHelper::loadFieldClass('list');

class MFormFieldLanguage extends MFormFieldList {

    protected $type = 'Language';

    protected function getOptions() {
        // Initialize some field attributes.
        $client = (string)$this->element['client'];
        if ($client != 'site' && $client != 'administrator') {
            $client = 'site';
        }

        // Merge any additional options in the XML definition.
        $options = array_merge(
            parent::getOptions(),
            MLanguageHelper::createLanguageList($this->value, constant('MPATH_' . strtoupper($client)), true, true)
        );

        return $options;
    }
}
