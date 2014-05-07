<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

abstract class MHtmlTabs {

    public static function start($group = 'tabs', $params = array()) {
        self::_loadBehavior($group, $params);

        return '<dl class="tabs" id="' . $group . '"><dt style="display:none;"></dt><dd style="display:none;">';
    }

    public static function end() {
        return '</dd></dl>';
    }

    public static function panel($text, $id) {
        return '</dd><dt class="tabs ' . $id . '"><span><h3><a href="javascript:void(0);">' . $text . '</a></h3></span></dt><dd class="tabs">';
    }

    protected static function _loadBehavior($group, $params = array()) {
        static $loaded = array();

        if (!array_key_exists((string)$group, $loaded)) {
            // Include MooTools framework
            MHtml::_('behavior.framework', true);

            $options                    = '{';
            $opt['onActive']            = (isset($params['onActive'])) ? $params['onActive'] : null;
            $opt['onBackground']        = (isset($params['onBackground'])) ? $params['onBackground'] : null;
            $opt['display']             = (isset($params['startOffset'])) ? (int)$params['startOffset'] : null;
            $opt['useStorage']          = (isset($params['useCookie']) && $params['useCookie']) ? 'true' : 'false';
            $opt['titleSelector']       = "'dt.tabs'";
            $opt['descriptionSelector'] = "'dd.tabs'";

            foreach ($opt as $k => $v) {
                if ($v) {
                    $options .= $k . ': ' . $v . ',';
                }
            }

            if (substr($options, -1) == ',') {
                $options = substr($options, 0, -1);
            }

            $options .= '}';

            $js = ' jQuery(document).ready(function () {
                        $$(\'dl#' . $group . '.tabs\').each(function(tabs){
                            new MTabs(tabs, ' . $options . ');
                        });
                    });';

            $document = MFactory::getDocument();
            $document->addScriptDeclaration($js);
            MHtml::_('script', 'system/tabs.js', false, true);

            $loaded[(string)$group] = true;
        }
    }
}