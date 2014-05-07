<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

abstract class MHtmlList {

    public static function accesslevel(&$row) {
        // Deprecation warning.
        MLog::add('MList::accesslevel is deprecated.', MLog::WARNING, 'deprecated');

        return MHtml::_('access.assetgrouplist', 'access', $row->access);
    }

    public static function images($name, $active = null, $javascript = null, $directory = null, $extensions = "bmp|gif|jpg|png") {
        if (!$directory) {
            $directory = '/images/';
        }

        if (!$javascript) {
            $javascript = "onchange=\"if (document.forms.adminForm." . $name
                . ".options[selectedIndex].value!='') {document.imagelib.src='..$directory' + document.forms.adminForm." . $name
                . ".options[selectedIndex].value} else {document.imagelib.src='media/system/images/blank.png'}\"";
        }

        mimport('framework.filesystem.folder');
        $imageFiles = MFolder::files(MPATH_SITE . '/' . $directory);
        $images     = array(MHtml::_('select.option', '', MText::_('MOPTION_SELECT_IMAGE')));

        foreach ($imageFiles as $file) {
            if (preg_match('#(' . $extensions . ')$#', $file)) {
                $images[] = MHtml::_('select.option', $file);
            }
        }

        $images = MHtml::_(
            'select.genericlist',
            $images,
            $name,
            array(
                'list.attr'   => 'class="inputbox" size="1" ' . $javascript,
                'list.select' => $active
            )
        );

        return $images;
    }

    public static function genericordering($sql, $chop = '30') {
        $db      = MFactory::getDbo();
        $options = array();
        $db->setQuery($sql);

        $items = $db->loadObjectList();

        // Check for a database error.
        if ($db->getErrorNum()) {
            MError::raiseNotice(500, $db->getErrorMsg());

            return false;
        }

        if (empty($items)) {
            $options[] = MHtml::_('select.option', 1, MText::_('MOPTION_ORDER_FIRST'));

            return $options;
        }

        $options[] = MHtml::_('select.option', 0, '0 ' . MText::_('MOPTION_ORDER_FIRST'));
        for ($i = 0, $n = count($items); $i < $n; $i++) {
            $items[$i]->text = MText::_($items[$i]->text);
            if (MString::strlen($items[$i]->text) > $chop) {
                $text = MString::substr($items[$i]->text, 0, $chop) . "...";
            }
            else {
                $text = $items[$i]->text;
            }

            $options[] = MHtml::_('select.option', $items[$i]->value, $items[$i]->value . '. ' . $text);
        }
        $options[] = MHtml::_('select.option', $items[$i - 1]->value + 1, ($items[$i - 1]->value + 1) . ' ' . MText::_('MOPTION_ORDER_LAST'));

        return $options;
    }

    public static function specificordering($value, $id, $query, $neworder = 0) {
        if (is_object($value)) {
            $value = $value->ordering;
        }

        if ($id) {
            $neworder = 0;
        }
        else {
            if ($neworder) {
                $neworder = 1;
            }
            else {
                $neworder = -1;
            }
        }

        return MHtmlList::ordering('ordering', $query, '', $value, $neworder);
    }

    public static function ordering($name, $query, $attribs = null, $selected = null, $neworder = null, $chop = null) {
        if (empty($attribs)) {
            $attribs = 'class="inputbox" size="1"';
        }

        if (empty($neworder)) {
            $orders = MHtml::_('list.genericordering', $query);
            $html   = MHtml::_('select.genericlist', $orders, $name, array('list.attr' => $attribs, 'list.select' => (int)$selected));
        }
        else {
            if ($neworder > 0) {
                $text = MText::_('MGLOBAL_NEWITEMSLAST_DESC');
            }
            elseif ($neworder <= 0) {
                $text = MText::_('MGLOBAL_NEWITEMSFIRST_DESC');
            }
            $html = '<input type="hidden" name="' . $name . '" value="' . (int)$selected . '" />' . '<span class="readonly">' . $text . '</span>';
        }

        return $html;
    }

    public static function users($name, $active, $nouser = 0, $javascript = null, $order = 'name', $reg = 1) {
        $db    = MFactory::getDbo();
        $query = $db->getQuery(true);

        if ($reg) {
            // Does not include registered users in the list
            // @deprecated
            $query->where('m.group_id != 2');
        }

        $query->select('u.id AS value, u.name AS text');
        $query->from('#__users AS u');
        $query->join('LEFT', '#__user_usergroup_map AS m ON m.user_id = u.id');
        $query->where('u.block = 0');
        $query->order($order);
        $db->setQuery($query);

        if ($nouser) {
            $users[] = MHtml::_('select.option', '0', MText::_('MOPTION_NO_USER'));
            $users   = array_merge($users, $db->loadObjectList());
        }
        else {
            $users = $db->loadObjectList();
        }

        $users = MHtml::_(
            'select.genericlist',
            $users,
            $name,
            array(
                'list.attr'   => 'class="inputbox" size="1" ' . $javascript,
                'list.select' => $active
            )
        );

        return $users;
    }

    public static function positions($name, $active = null, $javascript = null, $none = 1, $center = 1, $left = 1, $right = 1, $id = false) {
        $pos = array();
        if ($none) {
            $pos[''] = MText::_('MNONE');
        }

        if ($center) {
            $pos['center'] = MText::_('MGLOBAL_CENTER');
        }

        if ($left) {
            $pos['left'] = MText::_('MGLOBAL_LEFT');
        }

        if ($right) {
            $pos['right'] = MText::_('MGLOBAL_RIGHT');
        }

        $positions = MHtml::_(
            'select.genericlist', $pos, $name,
            array(
                'id'          => $id,
                'list.attr'   => 'class="inputbox" size="1"' . $javascript,
                'list.select' => $active,
                'option.key'  => null,
            )
        );

        return $positions;
    }

    public static function category($name, $extension, $selected = null, $javascript = null, $order = null, $size = 1, $sel_cat = 1) {
        // Deprecation warning.
        MLog::add('MList::category is deprecated.', MLog::WARNING, 'deprecated');

        $categories = MHtml::_('category.options', $extension);
        if ($sel_cat) {
            array_unshift($categories, MHtml::_('select.option', '0', MText::_('MOPTION_SELECT_CATEGORY')));
        }

        $category = MHtml::_(
            'select.genericlist', $categories, $name, 'class="inputbox" size="' . $size . '" ' . $javascript, 'value', 'text',
            $selected
        );

        return $category;
    }
}