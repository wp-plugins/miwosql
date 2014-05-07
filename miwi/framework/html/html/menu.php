<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

abstract class MHtmlMenu {

    protected static $menus = null;

    protected static $items = null;

    public static function menus() {
        if (empty(self::$menus)) {
            $db    = MFactory::getDbo();
            $query = $db->getQuery(true);
            $query->select('menutype AS value, title AS text');
            $query->from($db->quoteName('#__menu_types'));
            $query->order('title');
            $db->setQuery($query);
            self::$menus = $db->loadObjectList();
        }

        return self::$menus;
    }

    public static function menuitems($config = array()) {
        if (empty(self::$items)) {
            $db    = MFactory::getDbo();
            $query = $db->getQuery(true);
            $query->select('menutype AS value, title AS text');
            $query->from($db->quoteName('#__menu_types'));
            $query->order('title');
            $db->setQuery($query);
            $menus = $db->loadObjectList();

            $query->clear();
            $query->select('a.id AS value, a.title AS text, a.level, a.menutype');
            $query->from('#__menu AS a');
            $query->where('a.parent_id > 0');
            $query->where('a.type <> ' . $db->quote('url'));
            $query->where('a.client_id = 0');

            // Filter on the published state
            if (isset($config['published'])) {
                if (is_numeric($config['published'])) {
                    $query->where('a.published = ' . (int)$config['published']);
                }
                elseif ($config['published'] === '') {
                    $query->where('a.published IN (0,1)');
                }
            }

            $query->order('a.lft');

            $db->setQuery($query);
            $items = $db->loadObjectList();

            // Collate menu items based on menutype
            $lookup = array();
            foreach ($items as &$item) {
                if (!isset($lookup[$item->menutype])) {
                    $lookup[$item->menutype] = array();
                }
                $lookup[$item->menutype][] = & $item;

                $item->text = str_repeat('- ', $item->level) . $item->text;
            }
            self::$items = array();

            foreach ($menus as &$menu) {
                // Start group:
                self::$items[] = MHtml::_('select.optgroup', $menu->text);

                // Special "Add to this Menu" option:
                self::$items[] = MHtml::_('select.option', $menu->value . '.1', MText::_('MLIB_HTML_ADD_TO_THIS_MENU'));

                // Menu items:
                if (isset($lookup[$menu->value])) {
                    foreach ($lookup[$menu->value] as &$item) {
                        self::$items[] = MHtml::_('select.option', $menu->value . '.' . $item->value, $item->text);
                    }
                }

                // Finish group:
                self::$items[] = MHtml::_('select.optgroup', $menu->text);
            }
        }

        return self::$items;
    }

    public static function menuitemlist($name, $selected = null, $attribs = null, $config = array()) {
        static $count;

        $options = self::menuitems($config);

        return MHtml::_(
            'select.genericlist', $options, $name,
            array(
                'id'             => isset($config['id']) ? $config['id'] : 'assetgroups_' . ++$count,
                'list.attr'      => (is_null($attribs) ? 'class="inputbox" size="1"' : $attribs),
                'list.select'    => (int)$selected,
                'list.translate' => false
            )
        );
    }

    public static function ordering(&$row, $id) {
        $db    = MFactory::getDbo();
        $query = $db->getQuery(true);

        if ($id) {
            $query->select('ordering AS value, title AS text');
            $query->from($db->quoteName('#__menu'));
            $query->where($db->quoteName('menutype') . ' = ' . $db->quote($row->menutype));
            $query->where($db->quoteName('parent_id') . ' = ' . (int)$row->parent_id);
            $query->where($db->quoteName('published') . ' != -2');
            $query->order('ordering');
            $order    = MHtml::_('list.genericordering', $query);
            $ordering = MHtml::_(
                'select.genericlist', $order, 'ordering',
                array('list.attr' => 'class="inputbox" size="1"', 'list.select' => intval($row->ordering))
            );
        }
        else {
            $ordering = '<input type="hidden" name="ordering" value="' . $row->ordering . '" />' . MText::_('MGLOBAL_NEWITEMSLAST_DESC');
        }

        return $ordering;
    }

    public static function linkoptions($all = false, $unassigned = false) {
        $db    = MFactory::getDbo();
        $query = $db->getQuery(true);

        // get a list of the menu items
        $query->select('m.id, m.parent_id, m.title, m.menutype');
        $query->from($db->quoteName('#__menu') . ' AS m');
        $query->where($db->quoteName('m.published') . ' = 1');
        $query->order('m.menutype, m.parent_id, m.ordering');
        $db->setQuery($query);

        $mitems = $db->loadObjectList();

        // Check for a database error.
        if ($db->getErrorNum()) {
            MError::raiseNotice(500, $db->getErrorMsg());
        }

        if (!$mitems) {
            $mitems = array();
        }

        $mitems_temp = $mitems;

        // Establish the hierarchy of the menu
        $children = array();
        // First pass - collect children
        foreach ($mitems as $v) {
            $pt   = $v->parent_id;
            $list = @$children[$pt] ? $children[$pt] : array();
            array_push($list, $v);
            $children[$pt] = $list;
        }
        // Second pass - get an indent list of the items
        $list = MHtmlMenu::TreeRecurse(intval($mitems[0]->parent_id), '', array(), $children, 9999, 0, 0);

        // Code that adds menu name to Display of Page(s)

        $mitems = array();
        if ($all | $unassigned) {
            $mitems[] = MHtml::_('select.option', '<OPTGROUP>', MText::_('MOPTION_MENUS'));

            if ($all) {
                $mitems[] = MHtml::_('select.option', 0, MText::_('MALL'));
            }
            if ($unassigned) {
                $mitems[] = MHtml::_('select.option', -1, MText::_('MOPTION_UNASSIGNED'));
            }

            $mitems[] = MHtml::_('select.option', '</OPTGROUP>');
        }

        $lastMenuType = null;
        $tmpMenuType  = null;
        foreach ($list as $list_a) {
            if ($list_a->menutype != $lastMenuType) {
                if ($tmpMenuType) {
                    $mitems[] = MHtml::_('select.option', '</OPTGROUP>');
                }
                $mitems[]     = MHtml::_('select.option', '<OPTGROUP>', $list_a->menutype);
                $lastMenuType = $list_a->menutype;
                $tmpMenuType  = $list_a->menutype;
            }

            $mitems[] = MHtml::_('select.option', $list_a->id, $list_a->title);
        }
        if ($lastMenuType !== null) {
            $mitems[] = MHtml::_('select.option', '</OPTGROUP>');
        }

        return $mitems;
    }

    public static function treerecurse($id, $indent, $list, &$children, $maxlevel = 9999, $level = 0, $type = 1) {
        if (@$children[$id] && $level <= $maxlevel) {
            foreach ($children[$id] as $v) {
                $id = $v->id;

                if ($type) {
                    $pre    = '<sup>|_</sup>&#160;';
                    $spacer = '.&#160;&#160;&#160;&#160;&#160;&#160;';
                }
                else {
                    $pre    = '- ';
                    $spacer = '&#160;&#160;';
                }

                if ($v->parent_id == 0) {
                    $txt = $v->title;
                }
                else {
                    $txt = $pre . $v->title;
                }
                $pt                  = $v->parent_id;
                $list[$id]           = $v;
                $list[$id]->treename = "$indent$txt";
                $list[$id]->children = count(@$children[$id]);
                $list                = MHtmlMenu::TreeRecurse($id, $indent . $spacer, $list, $children, $maxlevel, $level + 1, $type);
            }
        }

        return $list;
    }
}