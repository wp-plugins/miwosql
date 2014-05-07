<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

MFormHelper::loadFieldClass('groupedlist');

// Import the com_menus helper.
require_once realpath(MPATH_ADMINISTRATOR . '/components/com_menus/helpers/menus.php');

class MFormFieldMenuItem extends MFormFieldGroupedList {

    public $type = 'MenuItem';

    protected function getGroups() {
        // Initialize variables.
        $groups = array();

        // Initialize some field attributes.
        $menuType  = (string)$this->element['menu_type'];
        $published = $this->element['published'] ? explode(',', (string)$this->element['published']) : array();
        $disable   = $this->element['disable'] ? explode(',', (string)$this->element['disable']) : array();
        $language  = $this->element['language'] ? explode(',', (string)$this->element['language']) : array();

        // Get the menu items.
        $items = MenusHelper::getMenuLinks($menuType, 0, 0, $published, $language);

        // Build group for a specific menu type.
        if ($menuType) {
            // Initialize the group.
            $groups[$menuType] = array();

            // Build the options array.
            foreach ($items as $link) {
                $groups[$menuType][] = MHtml::_('select.option', $link->value, $link->text, 'value', 'text', in_array($link->type, $disable));
            }
        }
        // Build groups for all menu types.
        else {
            // Build the groups arrays.
            foreach ($items as $menu) {
                // Initialize the group.
                $groups[$menu->menutype] = array();

                // Build the options array.
                foreach ($menu->links as $link) {
                    $groups[$menu->menutype][] = MHtml::_(
                        'select.option', $link->value, $link->text, 'value', 'text',
                        in_array($link->type, $disable)
                    );
                }
            }
        }

        // Merge any additional groups in the XML definition.
        $groups = array_merge(parent::getGroups(), $groups);

        return $groups;
    }
}
