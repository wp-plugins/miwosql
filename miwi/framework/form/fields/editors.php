<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

MFormHelper::loadFieldClass('list');

class MFormFieldEditors extends MFormFieldList {

    public $type = 'Editors';

    protected function getOptions() {
        MLog::add('MFormFieldEditors is deprecated. Use MFormFieldPlugins instead (with folder="editors").', MLog::WARNING, 'deprecated');

        // Get the database object and a new query object.
        $db    = MFactory::getDBO();
        $query = $db->getQuery(true);

        // Build the query.
        $query->select('element AS value, name AS text');
        $query->from('#__extensions');
        $query->where('folder = ' . $db->quote('editors'));
        $query->where('enabled = 1');
        $query->order('ordering, name');

        // Set the query and load the options.
        $db->setQuery($query);
        $options = $db->loadObjectList();
        $lang    = MFactory::getLanguage();
        foreach ($options as $i => $option) {
            $lang->load('plg_editors_' . $option->value, MPATH_ADMINISTRATOR, null, false, false)
            || $lang->load('plg_editors_' . $option->value, MPATH_PLUGINS . '/editors/' . $option->value, null, false, false)
            || $lang->load('plg_editors_' . $option->value, MPATH_ADMINISTRATOR, $lang->getDefault(), false, false)
            || $lang->load('plg_editors_' . $option->value, MPATH_PLUGINS . '/editors/' . $option->value, $lang->getDefault(), false, false);
            $options[$i]->text = MText::_($option->text);
        }

        // Check for a database error.
        if ($db->getErrorNum()) {
            MError::raiseWarning(500, $db->getErrorMsg());
        }

        // Merge any additional options in the XML definition.
        $options = array_merge(parent::getOptions(), $options);

        return $options;
    }
}
