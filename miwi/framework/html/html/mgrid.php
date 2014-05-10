<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');


abstract class MHtmlMGrid {

    public static function action($i, $task, $prefix = '', $text = '', $active_title = '', $inactive_title = '', $tip = false, $active_class = '',
                                  $inactive_class = '', $enabled = true, $translate = true, $checkbox = 'cb') {
        if (is_array($prefix)) {
            $options        = $prefix;
            $text           = array_key_exists('text', $options) ? $options['text'] : $text;
            $active_title   = array_key_exists('active_title', $options) ? $options['active_title'] : $active_title;
            $inactive_title = array_key_exists('inactive_title', $options) ? $options['inactive_title'] : $inactive_title;
            $tip            = array_key_exists('tip', $options) ? $options['tip'] : $tip;
            $active_class   = array_key_exists('active_class', $options) ? $options['active_class'] : $active_class;
            $inactive_class = array_key_exists('inactive_class', $options) ? $options['inactive_class'] : $inactive_class;
            $enabled        = array_key_exists('enabled', $options) ? $options['enabled'] : $enabled;
            $translate      = array_key_exists('translate', $options) ? $options['translate'] : $translate;
            $checkbox       = array_key_exists('checkbox', $options) ? $options['checkbox'] : $checkbox;
            $prefix         = array_key_exists('prefix', $options) ? $options['prefix'] : '';
        }
        if ($tip) {
            MHtml::_('behavior.tooltip');
        }
        if ($enabled) {
            $html[] = '<a class="mgrid' . ($tip ? ' hasTip' : '') . '"';
            $html[] = ' href="javascript:void(0);" onclick="return listItemTask(\'' . $checkbox . $i . '\',\'' . $prefix . $task . '\')"';
            $html[] = ' title="' . addslashes(htmlspecialchars($translate ? MText::_($active_title) : $active_title, ENT_COMPAT, 'UTF-8')) . '">';
            $html[] = '<span class="state ' . $active_class . '">';
            $html[] = $text ? ('<span class="text">' . ($translate ? MText::_($text) : $text) . '</span>') : '';
            $html[] = '</span>';
            $html[] = '</a>';
        }
        else {
            $html[] = '<a class="mgrid' . ($tip ? ' hasTip' : '') . '"';
            $html[] = ' title="' . addslashes(htmlspecialchars($translate ? MText::_($inactive_title) : $inactive_title, ENT_COMPAT, 'UTF-8')) . '">';
            $html[] = '<span class="state ' . $inactive_class . '">';
            $html[] = $text ? ('<span class="text">' . ($translate ? MText::_($text) : $text) . '</span>') : '';
            $html[] = '</span>';
            $html[] = '</a>';
        }

        return implode($html);
    }

    public static function state($states, $value, $i, $prefix = '', $enabled = true, $translate = true, $checkbox = 'cb') {
        if (is_array($prefix)) {
            $options   = $prefix;
            $enabled   = array_key_exists('enabled', $options) ? $options['enabled'] : $enabled;
            $translate = array_key_exists('translate', $options) ? $options['translate'] : $translate;
            $checkbox  = array_key_exists('checkbox', $options) ? $options['checkbox'] : $checkbox;
            $prefix    = array_key_exists('prefix', $options) ? $options['prefix'] : '';
        }
        $state          = MArrayHelper::getValue($states, (int)$value, $states[0]);
        $task           = array_key_exists('task', $state) ? $state['task'] : $state[0];
        $text           = array_key_exists('text', $state) ? $state['text'] : (array_key_exists(1, $state) ? $state[1] : '');
        $active_title   = array_key_exists('active_title', $state) ? $state['active_title'] : (array_key_exists(2, $state) ? $state[2] : '');
        $inactive_title = array_key_exists('inactive_title', $state) ? $state['inactive_title'] : (array_key_exists(3, $state) ? $state[3] : '');
        $tip            = array_key_exists('tip', $state) ? $state['tip'] : (array_key_exists(4, $state) ? $state[4] : false);
        $active_class   = array_key_exists('active_class', $state) ? $state['active_class'] : (array_key_exists(5, $state) ? $state[5] : '');
        $inactive_class = array_key_exists('inactive_class', $state) ? $state['inactive_class'] : (array_key_exists(6, $state) ? $state[6] : '');

        return self::action(
            $i, $task, $prefix, $text, $active_title, $inactive_title, $tip,
            $active_class, $inactive_class, $enabled, $translate, $checkbox
        );
    }

    public static function published($value, $i, $prefix = '', $enabled = true, $checkbox = 'cb', $publish_up = null, $publish_down = null) {
        if (is_array($prefix)) {
            $options  = $prefix;
            $enabled  = array_key_exists('enabled', $options) ? $options['enabled'] : $enabled;
            $checkbox = array_key_exists('checkbox', $options) ? $options['checkbox'] : $checkbox;
            $prefix   = array_key_exists('prefix', $options) ? $options['prefix'] : '';
        }
        $states = array(1  => array('unpublish', 'MPUBLISHED', 'MLIB_HTML_UNPUBLISH_ITEM', 'MPUBLISHED', false, 'publish', 'publish'),
                        0  => array('publish', 'MUNPUBLISHED', 'MLIB_HTML_PUBLISH_ITEM', 'MUNPUBLISHED', false, 'unpublish', 'unpublish'),
                        2  => array('unpublish', 'MARCHIVED', 'MLIB_HTML_UNPUBLISH_ITEM', 'MARCHIVED', false, 'archive', 'archive'),
                        -2 => array('publish', 'MTRASHED', 'MLIB_HTML_PUBLISH_ITEM', 'MTRASHED', false, 'trash', 'trash'));

        // Special state for dates
        if ($publish_up || $publish_down) {
            $nullDate = MFactory::getDBO()->getNullDate();
            $nowDate  = MFactory::getDate()->toUnix();

            $tz = new DateTimeZone(MFactory::getUser()->getParam('timezone', MFactory::getConfig()->get('offset')));

            $publish_up   = ($publish_up != $nullDate) ? MFactory::getDate($publish_up, 'UTC')->setTimeZone($tz) : false;
            $publish_down = ($publish_down != $nullDate) ? MFactory::getDate($publish_down, 'UTC')->setTimeZone($tz) : false;

            // Create tip text, only we have publish up or down settings
            $tips = array();
            if ($publish_up) {
                $tips[] = MText::sprintf('MLIB_HTML_PUBLISHED_START', $publish_up->format(MDate::$format, true));
            }
            if ($publish_down) {
                $tips[] = MText::sprintf('MLIB_HTML_PUBLISHED_FINISHED', $publish_down->format(MDate::$format, true));
            }
            $tip = empty($tips) ? false : implode('<br/>', $tips);

            // Add tips and special titles
            foreach ($states as $key => $state) {
                // Create special titles for published items
                if ($key == 1) {
                    $states[$key][2] = $states[$key][3] = 'MLIB_HTML_PUBLISHED_ITEM';
                    if ($publish_up > $nullDate && $nowDate < $publish_up->toUnix()) {
                        $states[$key][2] = $states[$key][3] = 'MLIB_HTML_PUBLISHED_PENDING_ITEM';
                        $states[$key][5] = $states[$key][6] = 'pending';
                    }
                    if ($publish_down > $nullDate && $nowDate > $publish_down->toUnix()) {
                        $states[$key][2] = $states[$key][3] = 'MLIB_HTML_PUBLISHED_EXPIRED_ITEM';
                        $states[$key][5] = $states[$key][6] = 'expired';
                    }
                }

                // Add tips to titles
                if ($tip) {
                    $states[$key][1] = MText::_($states[$key][1]);
                    $states[$key][2] = MText::_($states[$key][2]) . '::' . $tip;
                    $states[$key][3] = MText::_($states[$key][3]) . '::' . $tip;
                    $states[$key][4] = true;
                }
            }

            return self::state($states, $value, $i, array('prefix' => $prefix, 'translate' => !$tip), $enabled, true, $checkbox);
        }

        return self::state($states, $value, $i, $prefix, $enabled, true, $checkbox);
    }

    public static function isdefault($value, $i, $prefix = '', $enabled = true, $checkbox = 'cb') {
        if (is_array($prefix)) {
            $options  = $prefix;
            $enabled  = array_key_exists('enabled', $options) ? $options['enabled'] : $enabled;
            $checkbox = array_key_exists('checkbox', $options) ? $options['checkbox'] : $checkbox;
            $prefix   = array_key_exists('prefix', $options) ? $options['prefix'] : '';
        }

        $states = array(
            1 => array('unsetDefault', 'MDEFAULT', 'MLIB_HTML_UNSETDEFAULT_ITEM', 'MDEFAULT', false, 'default', 'default'),
            0 => array('setDefault', '', 'MLIB_HTML_SETDEFAULT_ITEM', '', false, 'notdefault', 'notdefault'),
        );

        return self::state($states, $value, $i, $prefix, $enabled, true, $checkbox);
    }

    public static function publishedOptions($config = array()) {
        // Build the active state filter options.
        $options = array();
        if (!array_key_exists('published', $config) || $config['published']) {
            $options[] = MHtml::_('select.option', '1', 'MPUBLISHED');
        }
        if (!array_key_exists('unpublished', $config) || $config['unpublished']) {
            $options[] = MHtml::_('select.option', '0', 'MUNPUBLISHED');
        }
        if (!array_key_exists('archived', $config) || $config['archived']) {
            $options[] = MHtml::_('select.option', '2', 'MARCHIVED');
        }
        if (!array_key_exists('trash', $config) || $config['trash']) {
            $options[] = MHtml::_('select.option', '-2', 'MTRASHED');
        }
        if (!array_key_exists('all', $config) || $config['all']) {
            $options[] = MHtml::_('select.option', '*', 'MALL');
        }

        return $options;
    }

    public static function checkedout($i, $editorName, $time, $prefix = '', $enabled = false, $checkbox = 'cb') {
        if (is_array($prefix)) {
            $options  = $prefix;
            $enabled  = array_key_exists('enabled', $options) ? $options['enabled'] : $enabled;
            $checkbox = array_key_exists('checkbox', $options) ? $options['checkbox'] : $checkbox;
            $prefix   = array_key_exists('prefix', $options) ? $options['prefix'] : '';
        }

        $text           = addslashes(htmlspecialchars($editorName, ENT_COMPAT, 'UTF-8'));
        $date           = addslashes(htmlspecialchars(MHtml::_('date', $time, MText::_('DATE_FORMAT_LC')), ENT_COMPAT, 'UTF-8'));
        $time           = addslashes(htmlspecialchars(MHtml::_('date', $time, 'H:i'), ENT_COMPAT, 'UTF-8'));
        $active_title   = MText::_('MLIB_HTML_CHECKIN') . '::' . $text . '<br />' . $date . '<br />' . $time;
        $inactive_title = MText::_('MLIB_HTML_CHECKED_OUT') . '::' . $text . '<br />' . $date . '<br />' . $time;

        return self::action(
            $i, 'checkin', $prefix, MText::_('MLIB_HTML_CHECKED_OUT'), $active_title, $inactive_title, true, 'checkedout',
            'checkedout', $enabled, false, $checkbox
        );
    }

    public static function orderUp($i, $task = 'orderup', $prefix = '', $text = 'MLIB_HTML_MOVE_UP', $enabled = true, $checkbox = 'cb') {
        if (is_array($prefix)) {
            $options  = $prefix;
            $text     = array_key_exists('text', $options) ? $options['text'] : $text;
            $enabled  = array_key_exists('enabled', $options) ? $options['enabled'] : $enabled;
            $checkbox = array_key_exists('checkbox', $options) ? $options['checkbox'] : $checkbox;
            $prefix   = array_key_exists('prefix', $options) ? $options['prefix'] : '';
        }

        return self::action($i, $task, $prefix, $text, $text, $text, false, 'uparrow', 'uparrow_disabled', $enabled, true, $checkbox);
    }

    public static function orderDown($i, $task = 'orderdown', $prefix = '', $text = 'MLIB_HTML_MOVE_DOWN', $enabled = true, $checkbox = 'cb') {
        if (is_array($prefix)) {
            $options  = $prefix;
            $text     = array_key_exists('text', $options) ? $options['text'] : $text;
            $enabled  = array_key_exists('enabled', $options) ? $options['enabled'] : $enabled;
            $checkbox = array_key_exists('checkbox', $options) ? $options['checkbox'] : $checkbox;
            $prefix   = array_key_exists('prefix', $options) ? $options['prefix'] : '';
        }

        return self::action($i, $task, $prefix, $text, $text, $text, false, 'downarrow', 'downarrow_disabled', $enabled, true, $checkbox);
    }
}