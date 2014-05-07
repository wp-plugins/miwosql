<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

MFormHelper::loadFieldClass('list');

class MFormFieldCacheHandler extends MFormFieldList {

    public $type = 'CacheHandler';

    protected function getOptions() {
        // Initialize variables.
        $options = array();

        // Convert to name => name array.
        foreach (MCache::getStores() as $store) {
            $options[] = MHtml::_('select.option', $store, MText::_('MLIB_FORM_VALUE_CACHE_' . $store), 'value', 'text');
        }

        $options = array_merge(parent::getOptions(), $options);

        return $options;
    }
}
