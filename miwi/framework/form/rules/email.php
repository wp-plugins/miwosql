<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MFormRuleEmail extends MFormRule {

    protected $regex = '^[\w.-]+(\+[\w.-]+)*@\w+[\w.-]*?\.\w{2,4}$';

    public function test(&$element, $value, $group = null, &$input = null, &$form = null) {
        // If the field is empty and not required, the field is valid.
        $required = ((string)$element['required'] == 'true' || (string)$element['required'] == 'required');
        if (!$required && empty($value)) {
            return true;
        }

        // Test the value against the regular expression.
        if (!parent::test($element, $value, $group, $input, $form)) {
            return false;
        }

        // Check if we should test for uniqueness.
        $unique = ((string)$element['unique'] == 'true' || (string)$element['unique'] == 'unique');
        if ($unique) {

            // Get the database object and a new query object.
            $db    = MFactory::getDBO();
            $query = $db->getQuery(true);

            // Build the query.
            $query->select('COUNT(*)');
            $query->from('#__users');
            $query->where('email = ' . $db->quote($value));

            // Get the extra field check attribute.
            $userId = ($form instanceof MForm) ? $form->getValue('id') : '';
            $query->where($db->quoteName('id') . ' <> ' . (int)$userId);

            // Set and query the database.
            $db->setQuery($query);
            $duplicate = (bool)$db->loadResult();

            // Check for a database error.
            if ($db->getErrorNum()) {
                MError::raiseWarning(500, $db->getErrorMsg());
            }

            if ($duplicate) {
                return false;
            }
        }

        return true;
    }
}