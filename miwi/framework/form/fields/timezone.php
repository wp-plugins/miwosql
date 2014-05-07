<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

MFormHelper::loadFieldClass('groupedlist');

class MFormFieldTimezone extends MFormFieldGroupedList {

    protected $type = 'Timezone';

    protected static $zones = array('Africa', 'America', 'Antarctica', 'Arctic', 'Asia', 'Atlantic', 'Australia', 'Europe', 'Indian', 'Pacific');

    protected function getGroups() {
        // Initialize variables.
        $groups = array();

        $keyField = $this->element['key_field'] ? (string)$this->element['key_field'] : 'id';
        $keyValue = $this->form->getValue($keyField);

        // If the timezone is not set use the server setting.
        if (strlen($this->value) == 0 && empty($keyValue)) {
            $this->value = MFactory::getConfig()->get('offset');
        }

        // Get the list of time zones from the server.
        $zones = DateTimeZone::listIdentifiers();

        // Build the group lists.
        foreach ($zones as $zone) {

            // Time zones not in a group we will ignore.
            if (strpos($zone, '/') === false) {
                continue;
            }

            // Get the group/locale from the timezone.
            list ($group, $locale) = explode('/', $zone, 2);

            // Only use known groups.
            if (in_array($group, self::$zones)) {

                // Initialize the group if necessary.
                if (!isset($groups[$group])) {
                    $groups[$group] = array();
                }

                // Only add options where a locale exists.
                if (!empty($locale)) {
                    $groups[$group][$zone] = MHtml::_('select.option', $zone, str_replace('_', ' ', $locale), 'value', 'text', false);
                }
            }
        }

        // Sort the group lists.
        ksort($groups);
        foreach ($groups as $zone => & $location) {
            sort($location);
        }

        // Merge any additional groups in the XML definition.
        $groups = array_merge(parent::getGroups(), $groups);

        return $groups;
    }
}