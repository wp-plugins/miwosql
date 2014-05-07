<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

abstract class MHtmlCategory {

    protected static $items = array();

    public static function options($extension, $config = array('filter.published' => array(0, 1))) {
        $hash = md5($extension . '.' . serialize($config));

        if (!isset(self::$items[$hash])) {
            $config = (array)$config;
            $db     = MFactory::getDbo();
            $query  = $db->getQuery(true);

            $query->select('a.id, a.title, a.level');
            $query->from('#__categories AS a');
            $query->where('a.parent_id > 0');

            // Filter on extension.
            $query->where('extension = ' . $db->quote($extension));

            // Filter on the published state
            if (isset($config['filter.published'])) {
                if (is_numeric($config['filter.published'])) {
                    $query->where('a.published = ' . (int)$config['filter.published']);
                }
                elseif (is_array($config['filter.published'])) {
                    MArrayHelper::toInteger($config['filter.published']);
                    $query->where('a.published IN (' . implode(',', $config['filter.published']) . ')');
                }
            }

            $query->order('a.lft');

            $db->setQuery($query);
            $items = $db->loadObjectList();

            // Assemble the list options.
            self::$items[$hash] = array();

            foreach ($items as &$item) {
                $repeat               = ($item->level - 1 >= 0) ? $item->level - 1 : 0;
                $item->title          = str_repeat('- ', $repeat) . $item->title;
                self::$items[$hash][] = MHtml::_('select.option', $item->id, $item->title);
            }
        }

        return self::$items[$hash];
    }

    public static function categories($extension, $config = array('filter.published' => array(0, 1))) {
        $hash = md5($extension . '.' . serialize($config));

        if (!isset(self::$items[$hash])) {
            $config = (array)$config;
            $db     = MFactory::getDbo();
            $query  = $db->getQuery(true);

            $query->select('a.id, a.title, a.level, a.parent_id');
            $query->from('#__categories AS a');
            $query->where('a.parent_id > 0');

            // Filter on extension.
            $query->where('extension = ' . $db->quote($extension));

            // Filter on the published state
            if (isset($config['filter.published'])) {
                if (is_numeric($config['filter.published'])) {
                    $query->where('a.published = ' . (int)$config['filter.published']);
                }
                elseif (is_array($config['filter.published'])) {
                    MArrayHelper::toInteger($config['filter.published']);
                    $query->where('a.published IN (' . implode(',', $config['filter.published']) . ')');
                }
            }

            $query->order('a.lft');

            $db->setQuery($query);
            $items = $db->loadObjectList();

            // Assemble the list options.
            self::$items[$hash] = array();

            foreach ($items as &$item) {
                $repeat               = ($item->level - 1 >= 0) ? $item->level - 1 : 0;
                $item->title          = str_repeat('- ', $repeat) . $item->title;
                self::$items[$hash][] = MHtml::_('select.option', $item->id, $item->title);
            }
            // Special "Add to root" option:
            self::$items[$hash][] = MHtml::_('select.option', '1', MText::_('MLIB_HTML_ADD_TO_ROOT'));
        }

        return self::$items[$hash];
    }
}