<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

abstract class MHtmlAccess {

    protected static $asset_groups = null;

    public static function level($name, $selected, $attribs = '', $params = true, $id = false) {
        $db    = MFactory::getDbo();
        $query = $db->getQuery(true);

        $query->select('a.id AS value, a.title AS text');
        $query->from('#__viewlevels AS a');
        $query->group('a.id, a.title, a.ordering');
        $query->order('a.ordering ASC');
        $query->order($query->qn('title') . ' ASC');

        // Get the options.
        $db->setQuery($query);
        $options = $db->loadObjectList();

        // Check for a database error.
        if ($db->getErrorNum()) {
            MError::raiseWarning(500, $db->getErrorMsg());

            return null;
        }

        // If params is an array, push these options to the array
        if (is_array($params)) {
            $options = array_merge($params, $options);
        }
        // If all levels is allowed, push it into the array.
        elseif ($params) {
            array_unshift($options, MHtml::_('select.option', '', MText::_('MOPTION_ACCESS_SHOW_ALL_LEVELS')));
        }

        return MHtml::_(
            'select.genericlist',
            $options,
            $name,
            array(
                'list.attr'   => $attribs,
                'list.select' => $selected,
                'id'          => $id
            )
        );
    }

    public static function usergroup($name, $selected, $attribs = '', $allowAll = true) {
        $db    = MFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select('a.id AS value, a.title AS text, COUNT(DISTINCT b.id) AS level');
        $query->from($db->quoteName('#__usergroups') . ' AS a');
        $query->join('LEFT', $db->quoteName('#__usergroups') . ' AS b ON a.lft > b.lft AND a.rgt < b.rgt');
        $query->group('a.id, a.title, a.lft, a.rgt');
        $query->order('a.lft ASC');
        $db->setQuery($query);
        $options = $db->loadObjectList();

        // Check for a database error.
        if ($db->getErrorNum()) {
            MError::raiseNotice(500, $db->getErrorMsg());

            return null;
        }

        for ($i = 0, $n = count($options); $i < $n; $i++) {
            $options[$i]->text = str_repeat('- ', $options[$i]->level) . $options[$i]->text;
        }

        // If all usergroups is allowed, push it into the array.
        if ($allowAll) {
            array_unshift($options, MHtml::_('select.option', '', MText::_('MOPTION_ACCESS_SHOW_ALL_GROUPS')));
        }

        return MHtml::_('select.genericlist', $options, $name, array('list.attr' => $attribs, 'list.select' => $selected));
    }

    public static function usergroups($name, $selected, $checkSuperAdmin = false) {
        static $count;

        $count++;

        $isSuperAdmin = MFactory::getUser()->authorise('core.admin');

        $db    = MFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select('a.*, COUNT(DISTINCT b.id) AS level');
        $query->from($db->quoteName('#__usergroups') . ' AS a');
        $query->join('LEFT', $db->quoteName('#__usergroups') . ' AS b ON a.lft > b.lft AND a.rgt < b.rgt');
        $query->group('a.id, a.title, a.lft, a.rgt, a.parent_id');
        $query->order('a.lft ASC');
        $db->setQuery($query);
        $groups = $db->loadObjectList();

        // Check for a database error.
        if ($db->getErrorNum()) {
            MError::raiseNotice(500, $db->getErrorMsg());

            return null;
        }

        $html = array();

        $html[] = '<ul class="checklist usergroups">';

        for ($i = 0, $n = count($groups); $i < $n; $i++) {
            $item = & $groups[$i];

            // If checkSuperAdmin is true, only add item if the user is superadmin or the group is not super admin
            if ((!$checkSuperAdmin) || $isSuperAdmin || (!MAccess::checkGroup($item->id, 'core.admin'))) {
                // Setup  the variable attributes.
                $eid = $count . 'group_' . $item->id;
                // Don't call in_array unless something is selected
                $checked = '';
                if ($selected) {
                    $checked = in_array($item->id, $selected) ? ' checked="checked"' : '';
                }
                $rel = ($item->parent_id > 0) ? ' rel="' . $count . 'group_' . $item->parent_id . '"' : '';

                // Build the HTML for the item.
                $html[] = '	<li>';
                $html[] = '		<input type="checkbox" name="' . $name . '[]" value="' . $item->id . '" id="' . $eid . '"';
                $html[] = '				' . $checked . $rel . ' />';
                $html[] = '		<label for="' . $eid . '">';
                $html[] = '		' . str_repeat('<span class="gi">|&mdash;</span>', $item->level) . $item->title;
                $html[] = '		</label>';
                $html[] = '	</li>';
            }
        }
        $html[] = '</ul>';

        return implode("\n", $html);
    }

    public static function actions($name, $selected, $component, $section = 'global') {
        static $count;

        $count++;

        $actions = MAccess::getActions($component, $section);

        $html   = array();
        $html[] = '<ul class="checklist access-actions">';

        for ($i = 0, $n = count($actions); $i < $n; $i++) {
            $item = & $actions[$i];

            // Setup  the variable attributes.
            $eid     = $count . 'action_' . $item->id;
            $checked = in_array($item->id, $selected) ? ' checked="checked"' : '';

            // Build the HTML for the item.
            $html[] = '	<li>';
            $html[] = '		<input type="checkbox" name="' . $name . '[]" value="' . $item->id . '" id="' . $eid . '"';
            $html[] = '			' . $checked . ' />';
            $html[] = '		<label for="' . $eid . '">';
            $html[] = '			' . MText::_($item->title);
            $html[] = '		</label>';
            $html[] = '	</li>';
        }
        $html[] = '</ul>';

        return implode("\n", $html);
    }

    public static function assetgroups($config = array()) {
        if (empty(MHtmlAccess::$asset_groups)) {
            $db    = MFactory::getDbo();
            $query = $db->getQuery(true);

            $query->select('a.id AS value, a.title AS text');
            $query->from($db->quoteName('#__viewlevels') . ' AS a');
            $query->group('a.id, a.title, a.ordering');
            $query->order('a.ordering ASC');

            $db->setQuery($query);
            MHtmlAccess::$asset_groups = $db->loadObjectList();

            // Check for a database error.
            if ($db->getErrorNum()) {
                MError::raiseNotice(500, $db->getErrorMsg());

                return false;
            }
        }

        return MHtmlAccess::$asset_groups;
    }

    public static function assetgrouplist($name, $selected, $attribs = null, $config = array()) {
        static $count;

        $options = MHtmlAccess::assetgroups();
        if (isset($config['title'])) {
            array_unshift($options, MHtml::_('select.option', '', $config['title']));
        }

        return MHtml::_(
            'select.genericlist',
            $options,
            $name,
            array(
                'id'          => isset($config['id']) ? $config['id'] : 'assetgroups_' . ++$count,
                'list.attr'   => (is_null($attribs) ? 'class="inputbox" size="3"' : $attribs),
                'list.select' => (int)$selected
            )
        );
    }
}