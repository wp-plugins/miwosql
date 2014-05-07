<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

abstract class MHtmlUser {

    public static function groups($includeSuperAdmin = false) {
        if(!class_exists('WP_Roles')) {
            require_once(ABSPATH. 'wp-includes/capabilities.php');
        }

        $wp_roles = new WP_Roles();
        $roles = $wp_roles->role_names;

        foreach ($roles as $key => $role) {
            $groups[] = MHtml::_('select.option', $key, $role);
        }

        return $groups;
    }

    public static function userlist() {
        // Get the database object and a new query object.
        $db    = MFactory::getDBO();
        $query = $db->getQuery(true);

        // Build the query.
        $query->select('a.ID AS value, a.display_name AS text');
        $query->from('#__users AS a');
        $query->where('a.user_status = 0');
        $query->order('a.display_name');

        // Set the query and load the options.
        $db->setQuery($query);
        $items = $db->loadObjectList();

        // Detect errors
        if ($db->getErrorNum()) {
            MError::raiseWarning(500, $db->getErrorMsg());
        }

        return $items;
    }
}
