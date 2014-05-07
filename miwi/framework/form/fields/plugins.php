<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

MFormHelper::loadFieldClass('list');

class MFormFieldPlugins extends MFormFieldList {

    protected $type = 'Plugins';

    protected function getOptions() {
        // Initialise variables
        $folder = $this->element['folder'];

        if (!empty($folder)) {
            // Get list of plugins
            $db    = MFactory::getDbo();
            $query = $db->getQuery(true);
            $query->select('element AS value, name AS text');
            $query->from('#__extensions');
            $query->where('folder = ' . $db->q($folder));
            $query->where('enabled = 1');
            $query->order('ordering, name');
            $db->setQuery($query);

            $options = $db->loadObjectList();

            $lang = MFactory::getLanguage();
            foreach ($options as $i => $item) {
                $source    = MPATH_PLUGINS . '/' . $folder . '/' . $item->value;
                $extension = 'plg_' . $folder . '_' . $item->value;
                $lang->load($extension . '.sys', MPATH_ADMINISTRATOR, null, false, false)
                || $lang->load($extension . '.sys', $source, null, false, false)
                || $lang->load($extension . '.sys', MPATH_ADMINISTRATOR, $lang->getDefault(), false, false)
                || $lang->load($extension . '.sys', $source, $lang->getDefault(), false, false);
                $options[$i]->text = MText::_($item->text);
            }

            if ($db->getErrorMsg()) {
                MError::raiseWarning(500, MText::_('MFRAMEWORK_FORM_FIELDS_PLUGINS_ERROR_FOLDER_EMPTY'));

                return '';
            }

        }
        else {
            MError::raiseWarning(500, MText::_('MFRAMEWORK_FORM_FIELDS_PLUGINS_ERROR_FOLDER_EMPTY'));
        }

        // Merge any additional options in the XML definition.
        $options = array_merge(parent::getOptions(), $options);

        return $options;
    }
}
