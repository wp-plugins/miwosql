<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

MFormHelper::loadFieldClass('list');

class MFormFieldSQL extends MFormFieldList {

    public $type = 'SQL';

    protected function getOptions() {
        // Initialize variables.
        $options = array();

        // Initialize some field attributes.
        $key       = $this->element['key_field'] ? (string)$this->element['key_field'] : 'value';
        $value     = $this->element['value_field'] ? (string)$this->element['value_field'] : (string)$this->element['name'];
        $translate = $this->element['translate'] ? (string)$this->element['translate'] : false;
        $query     = (string)$this->element['query'];

        // Get the database object.
        $db = MFactory::getDBO();

        // Set the query and get the result list.
        $db->setQuery($query);
        $items = $db->loadObjectlist();

        // Check for an error.
        if ($db->getErrorNum()) {
            MError::raiseWarning(500, $db->getErrorMsg());

            return $options;
        }

        // Build the field options.
        if (!empty($items)) {
            foreach ($items as $item) {
                if ($translate == true) {
                    $options[] = MHtml::_('select.option', $item->$key, MText::_($item->$value));
                }
                else {
                    $options[] = MHtml::_('select.option', $item->$key, $item->$value);
                }
            }
        }

        // Merge any additional options in the XML definition.
        $options = array_merge(parent::getOptions(), $options);

        return $options;
    }
}