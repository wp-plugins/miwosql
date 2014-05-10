<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

abstract class MHtmlRules {

    public static function assetFormWidget($actions, $assetId = null, $parent = null, $control = 'mform[rules]', $idPrefix = 'mform_rules') {
        $images = self::_getImagesArray();

        // Get the user groups.
        $groups = self::_getUserGroups();

        // Get the incoming inherited rules as well as the asset specific rules.
        $inheriting = MAccess::getAssetRules($parent ? $parent : self::_getParentAssetId($assetId), true);
        $inherited  = MAccess::getAssetRules($assetId, true);
        $rules      = MAccess::getAssetRules($assetId);

        $html = array();

        $html[] = '<div class="acl-options">';
        $html[] = MHtml::_('tabs.start', 'acl-rules-' . $assetId, array('useCookie' => 1));
        $html[] = MHtml::_('tabs.panel', MText::_('MLIB_HTML_ACCESS_SUMMARY'), 'summary');
        $html[] = '			<p>' . MText::_('MLIB_HTML_ACCESS_SUMMARY_DESC') . '</p>';
        $html[] = '			<table class="aclsummary-table" summary="' . MText::_('MLIB_HTML_ACCESS_SUMMARY_DESC') . '">';
        $html[] = '			<caption>' . MText::_('MLIB_HTML_ACCESS_SUMMARY_DESC_CAPTION') . '</caption>';
        $html[] = '			<tr>';
        $html[] = '				<th class="col1 hidelabeltxt">' . MText::_('MLIB_RULES_GROUPS') . '</th>';
        foreach ($actions as $i => $action) {
            $html[] = '				<th class="col' . ($i + 2) . '">' . MText::_($action->title) . '</th>';
        }
        $html[] = '			</tr>';

        foreach ($groups as $i => $group) {
            $html[] = '			<tr class="row' . ($i % 2) . '">';
            $html[] = '				<td class="col1">' . $group->text . '</td>';
            foreach ($actions as $j => $action) {
                $html[] = '				<td class="col' . ($j + 2) . '">'
                    . ($assetId ? ($inherited->allow($action->name, $group->identities) ? $images['allow'] : $images['deny'])
                        : ($inheriting->allow($action->name, $group->identities) ? $images['allow'] : $images['deny'])) . '</td>';
            }
            $html[] = '			</tr>';
        }

        $html[] = ' 		</table>';

        foreach ($actions as $action) {
            $actionTitle = MText::_($action->title);
            $actionDesc  = MText::_($action->description);
            $html[]      = MHtml::_('tabs.panel', $actionTitle, $action->name);
            $html[]      = '			<p>' . $actionDesc . '</p>';
            $html[]      = '			<table class="aclmodify-table" summary="' . strip_tags($actionDesc) . '">';
            $html[]      = '			<caption>' . MText::_('MLIB_HTML_ACCESS_MODIFY_DESC_CAPTION_ACL') . ' ' . $actionTitle . ' '
                . MText::_('MLIB_HTML_ACCESS_MODIFY_DESC_CAPTION_TABLE') . '</caption>';
            $html[]      = '			<tr>';
            $html[]      = '				<th class="col1 hidelabeltxt">' . MText::_('MLIB_RULES_GROUP') . '</th>';
            $html[]      = '				<th class="col2">' . MText::_('MLIB_RULES_INHERIT') . '</th>';
            $html[]      = '				<th class="col3 hidelabeltxt">' . MText::_('MMODIFY') . '</th>';
            $html[]      = '				<th class="col4">' . MText::_('MCURRENT') . '</th>';
            $html[]      = '			</tr>';

            foreach ($groups as $i => $group) {
                $selected = $rules->allow($action->name, $group->value);

                $html[] = '			<tr class="row' . ($i % 2) . '">';
                $html[] = '				<td class="col1">' . $group->text . '</td>';
                $html[] = '				<td class="col2">'
                    . ($inheriting->allow($action->name, $group->identities) ? $images['allow-i'] : $images['deny-i']) . '</td>';
                $html[] = '				<td class="col3">';
                $html[] = '					<select id="' . $idPrefix . '_' . $action->name . '_' . $group->value
                    . '" class="inputbox" size="1" name="' . $control . '[' . $action->name . '][' . $group->value . ']" title="'
                    . MText::sprintf('MLIB_RULES_SELECT_ALLOW_DENY_GROUP', $actionTitle, $group->text) . '">';
                $html[] = '						<option value=""' . ($selected === null ? ' selected="selected"' : '') . '>'
                    . MText::_('MLIB_RULES_INHERIT') . '</option>';
                $html[] = '						<option value="1"' . ($selected === true ? ' selected="selected"' : '') . '>'
                    . MText::_('MLIB_RULES_ALLOWED') . '</option>';
                $html[] = '						<option value="0"' . ($selected === false ? ' selected="selected"' : '') . '>'
                    . MText::_('MLIB_RULES_DENIED') . '</option>';
                $html[] = '					</select>';
                $html[] = '				</td>';
                $html[] = '				<td class="col4">'
                    . ($assetId ? ($inherited->allow($action->name, $group->identities) ? $images['allow'] : $images['deny'])
                        : ($inheriting->allow($action->name, $group->identities) ? $images['allow'] : $images['deny'])) . '</td>';
                $html[] = '			</tr>';
            }

            $html[] = '			</table>';
        }

        $html[] = MHtml::_('tabs.end');

        // Build the footer with legend and special purpose buttons.
        $html[] = '	<div class="clr"></div>';
        $html[] = '	<ul class="acllegend fltlft">';
        $html[] = '		<li class="acl-allowed">' . MText::_('MLIB_RULES_ALLOWED') . '</li>';
        $html[] = '		<li class="acl-denied">' . MText::_('MLIB_RULES_DENIED') . '</li>';
        $html[] = '	</ul>';
        $html[] = '</div>';

        return implode("\n", $html);
    }

    protected static function _getParentAssetId($assetId) {
        // Get a database object.
        $db    = MFactory::getDBO();
        $query = $db->getQuery(true);

        // Get the user groups from the database.
        $query->select($db->quoteName('parent_id'));
        $query->from($db->quoteName('#__assets'));
        $query->where($db->quoteName('id') . ' = ' . (int)$assetId);
        $db->setQuery($query);

        return (int)$db->loadResult();
    }

    protected static function _getUserGroups() {
        // Get a database object.
        $db = MFactory::getDBO();

        // Get the user groups from the database.
        $db->setQuery(
            'SELECT a.id AS value, a.title AS text, b.id as parent'
            . ' FROM #__usergroups AS a' . ' LEFT JOIN #__usergroups AS b ON a.lft >= b.lft AND a.rgt <= b.rgt'
            . ' ORDER BY a.lft ASC, b.lft ASC'
        );
        $result  = $db->loadObjectList();
        $options = array();

        // Pre-compute additional values.
        foreach ($result as $option) {
            $end = end($options);
            if ($end === false || $end->value != $option->value) {
                $end        = $option;
                $end->level = 0;
                $options[]  = $end;
            }
            else {
                $end->level++;
            }
            $end->identities[] = $option->parent;
        }

        return $options;
    }

    protected static function _getImagesArray() {
        $images['allow-l'] = '<label class="icon-16-allow" title="' . MText::_('MLIB_RULES_ALLOWED') . '">' . MText::_('MLIB_RULES_ALLOWED')
            . '</label>';
        $images['deny-l']  = '<label class="icon-16-deny" title="' . MText::_('MLIB_RULES_DENIED') . '">' . MText::_('MLIB_RULES_DENIED') . '</label>';
        $images['allow']   = '<a class="icon-16-allow" title="' . MText::_('MLIB_RULES_ALLOWED') . '"> </a>';
        $images['deny']    = '<a class="icon-16-deny" title="' . MText::_('MLIB_RULES_DENIED') . '"> </a>';
        $images['allow-i'] = '<a class="icon-16-allowinactive" title="' . MText::_('MRULE_ALLOWED_INHERITED') . '"> </a>';
        $images['deny-i']  = '<a class="icon-16-denyinactive" title="' . MText::_('MRULE_DENIED_INHERITED') . '"> </a>';

        return $images;
    }
}