<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

abstract class MHtmlBehavior {

    protected static $loaded = array();

    public static function framework($extras = false, $debug = null) {
        $type = $extras ? 'more' : 'core';

        // Only load once
        if (!empty(self::$loaded[__METHOD__][$type])) {
            return;
        }

        // If no debugging value is set, use the configuration setting
        if ($debug === null) {
            $config = MFactory::getConfig();
            $debug = $config->get('debug');
        }

        if ($type != 'core' && empty(self::$loaded[__METHOD__]['core'])) {
            self::framework(false, $debug);
        }

        MHtml::_('script', 'system/mootools-' . $type . '.js', false, true, false, false, $debug);
        MHtml::_('jquery.framework');
        MHtml::_('script', 'system/core.js', false, true);

        self::$loaded[__METHOD__][$type] = true;

        return;
    }

    public static function caption($selector = 'img.caption') {
        // Only load once
        if (isset(self::$loaded[__METHOD__][$selector])) {
            return;
        }

        // Include jQuery
        MHtml::_('jquery.framework');

        MHtml::_('script', 'system/caption.js', false, true);

        // Attach caption to document
        MFactory::getDocument()->addScriptDeclaration(
            "jQuery(window).on('load',  function() {
                new MCaption('" . $selector . "');
			});"
        );

        // Set static array
        self::$loaded[__METHOD__][$selector] = true;
    }

    public static function formvalidation() {
        // Only load once
        if (isset(self::$loaded[__METHOD__])) {
            return;
        }

        // Include MooTools framework
        self::framework();

        // Include jQuery Framework
        MHtml::_('jquery.framework');

        // Add validate.js language strings
        MText::script('MLIB_FORM_FIELD_INVALID');

        MHtml::_('script', 'system/punycode.js', false, true);
        MHtml::_('script', 'system/validate.js', false, true);
        self::$loaded[__METHOD__] = true;
    }

    public static function switcher() {
        // Only load once
        if (isset(self::$loaded[__METHOD__])) {
            return;
        }

        // Include jQuery
        MHtml::_('jquery.framework');

        MHtml::_('script', 'system/switcher.js', true, true);

        self::$loaded[__METHOD__] = true;
    }

    public static function combobox() {
        if (isset(self::$loaded[__METHOD__])) {
            return;
        }
        // Include MooTools framework
        self::framework();

        MHtml::_('script', 'system/combobox.js', true, true);
        self::$loaded[__METHOD__] = true;
    }

    public static function tooltip($selector = '.hasTip', $params = array()) {
        $sig = md5(serialize(array($selector, $params)));

        if (isset(self::$loaded[__METHOD__][$sig])) {
            return;
        }

        // Include MooTools framework
        self::framework(true);

        // Setup options object
        $opt['maxTitleChars'] = (isset($params['maxTitleChars']) && ($params['maxTitleChars'])) ? (int)$params['maxTitleChars'] : 50;

        // Offsets needs an array in the format: array('x'=>20, 'y'=>30)
        $opt['offset'] = (isset($params['offset']) && (is_array($params['offset']))) ? $params['offset'] : null;
        $opt['showDelay'] = (isset($params['showDelay'])) ? (int)$params['showDelay'] : null;
        $opt['hideDelay'] = (isset($params['hideDelay'])) ? (int)$params['hideDelay'] : null;
        $opt['className'] = (isset($params['className'])) ? $params['className'] : null;
        $opt['fixed'] = (isset($params['fixed']) && ($params['fixed'])) ? true : false;
        $opt['onShow'] = (isset($params['onShow'])) ? '\\' . $params['onShow'] : null;
        $opt['onHide'] = (isset($params['onHide'])) ? '\\' . $params['onHide'] : null;

        $options = MHtml::getJSObject($opt);

        // Include jQuery
        MHtml::_('jquery.framework');

        // Attach tooltips to document
        MFactory::getDocument()->addScriptDeclaration(
            "jQuery(function($) {
			 $('$selector').each(function() {
				var title = $(this).attr('title');
				if (title) {
					var parts = title.split('::', 2);
					$(this).get(0).store('tip:title', parts[0]); // Depends on Mootools store which requires for Tips
					$(this).get(0).store('tip:text', parts[1]);  // Depends on Mootools store which requires for Tips
				}
			});
			var MTooltips = new Tips($('$selector').get(), $options);
		});"
        );

        // Set static array
        self::$loaded[__METHOD__][$sig] = true;

        return;
    }

    public static function modal($selector = 'a.modal', $params = array()) {
        $document = MFactory::getDocument();

        // Load the necessary files if they haven't yet been loaded
        if (!isset(self::$loaded[__METHOD__])) {
            // Include MooTools framework
            self::framework(true);

            // Load the MavaScript and css
            MHtml::_('script', 'system/modal.js', true, true);
            MHtml::_('stylesheet', 'system/modal.css', array(), true);
        }

        $sig = md5(serialize(array($selector, $params)));

        if (isset(self::$loaded[__METHOD__][$sig])) {
            return;
        }

        // Setup options object
        $opt['ajaxOptions'] = (isset($params['ajaxOptions']) && (is_array($params['ajaxOptions']))) ? $params['ajaxOptions'] : null;
        $opt['handler'] = (isset($params['handler'])) ? $params['handler'] : null;
        $opt['parseSecure'] = (isset($params['parseSecure'])) ? (bool)$params['parseSecure'] : null;
        $opt['closable'] = (isset($params['closable'])) ? (bool)$params['closable'] : null;
        $opt['closeBtn'] = (isset($params['closeBtn'])) ? (bool)$params['closeBtn'] : null;
        $opt['iframePreload'] = (isset($params['iframePreload'])) ? (bool)$params['iframePreload'] : null;
        $opt['iframeOptions'] = (isset($params['iframeOptions']) && (is_array($params['iframeOptions']))) ? $params['iframeOptions'] : null;
        $opt['size'] = (isset($params['size']) && (is_array($params['size']))) ? $params['size'] : null;
        $opt['shadow'] = (isset($params['shadow'])) ? $params['shadow'] : null;
        $opt['overlay'] = (isset($params['overlay'])) ? $params['overlay'] : null;
        $opt['onOpen'] = (isset($params['onOpen'])) ? $params['onOpen'] : null;
        $opt['onClose'] = (isset($params['onClose'])) ? $params['onClose'] : null;
        $opt['onUpdate'] = (isset($params['onUpdate'])) ? $params['onUpdate'] : null;
        $opt['onResize'] = (isset($params['onResize'])) ? $params['onResize'] : null;
        $opt['onMove'] = (isset($params['onMove'])) ? $params['onMove'] : null;
        $opt['onShow'] = (isset($params['onShow'])) ? $params['onShow'] : null;
        $opt['onHide'] = (isset($params['onHide'])) ? $params['onHide'] : null;

        // Include jQuery
        MHtml::_('jquery.framework');

        if (isset($params['fullScreen']) && (bool)$params['fullScreen']) {
            $opt['size'] = array('x' => '\\jQuery(window).width() - 80', 'y' => '\\jQuery(window).height() - 80');
        }

        $options = MHtml::getJSObject($opt);

        // Attach modal behavior to document
        $document->addScriptDeclaration(
                "   jQuery(function($) {
                        SqueezeBox.initialize(" . $options . ");
                        SqueezeBox.assign($('" . $selector . "').get(), {
                            parse: 'rel'
                        });
                    });"
                );

        // Set static array
        self::$loaded[__METHOD__][$sig] = true;

        return;
    }

    public static function multiselect($id = 'adminForm') {
        // Only load once
        if (isset(self::$loaded[__METHOD__][$id])) {
            return;
        }

        // Include jQuery
        MHtml::_('jquery.framework');

        MHtml::_('script', 'system/multiselect.js', true, true);

        // Attach multiselect to document
        MFactory::getDocument()->addScriptDeclaration(
            "jQuery(document).ready(function () {
                new Miwi.MMultiSelect('" . $id . "');
            });"
        );

        // Set static array
        self::$loaded[__METHOD__][$id] = true;

        return;
    }

    public static function tree($id, $params = array(), $root = array()) {
        // Include MooTools framework
        self::framework();

        MHtml::_('script', 'system/mootree.js', true, true, false, false);
        MHtml::_('stylesheet', 'system/mootree.css', array(), true);

        if (isset(self::$loaded[__METHOD__][$id])) {
            return;
        }

        // Include jQuery
        MHtml::_('jquery.framework');

        // Setup options object
        $opt['div'] = (array_key_exists('div', $params)) ? $params['div'] : $id . '_tree';
        $opt['mode'] = (array_key_exists('mode', $params)) ? $params['mode'] : 'folders';
        $opt['grid'] = (array_key_exists('grid', $params)) ? '\\' . $params['grid'] : true;
        $opt['theme'] = (array_key_exists('theme', $params)) ? $params['theme'] : MHtml::_('image', 'system/mootree.gif', '', array(), true, true);

        // Event handlers
        $opt['onExpand'] = (array_key_exists('onExpand', $params)) ? '\\' . $params['onExpand'] : null;
        $opt['onSelect'] = (array_key_exists('onSelect', $params)) ? '\\' . $params['onSelect'] : null;
        $opt['onClick'] = (array_key_exists('onClick', $params)) ? '\\' . $params['onClick']
            : '\\function(node){  window.open(node.data.url, node.data.target != null ? node.data.target : \'_self\'); }';

        $options = MHtml::getJSObject($opt);

        // Setup root node
        $rt['text'] = (array_key_exists('text', $root)) ? $root['text'] : 'Root';
        $rt['id'] = (array_key_exists('id', $root)) ? $root['id'] : null;
        $rt['color'] = (array_key_exists('color', $root)) ? $root['color'] : null;
        $rt['open'] = (array_key_exists('open', $root)) ? '\\' . $root['open'] : true;
        $rt['icon'] = (array_key_exists('icon', $root)) ? $root['icon'] : null;
        $rt['openicon'] = (array_key_exists('openicon', $root)) ? $root['openicon'] : null;
        $rt['data'] = (array_key_exists('data', $root)) ? $root['data'] : null;
        $rootNode = MHtml::getJSObject($rt);

        $treeName = (array_key_exists('treeName', $params)) ? $params['treeName'] : '';

        $js = '		jQuery(function(){
			tree' . $treeName . ' = new MooTreeControl(' . $options . ',' . $rootNode . ');
			tree' . $treeName . '.adopt(\'' . $id . '\');})';

        // Attach tooltips to document
        $document = MFactory::getDocument();
        $document->addScriptDeclaration($js);

        // Set static array
        self::$loaded[__METHOD__][$id] = true;

        return;
    }

    public static function calendar() {
        // Only load once
        if (isset(self::$loaded[__METHOD__])) {
            return;
        }

        $document = MFactory::getDocument();
        $tag = MFactory::getLanguage()->getTag();

        MHtml::_('stylesheet', 'system/calendar-jos.css', array(' title' => MText::_('MLIB_HTML_BEHAVIOR_GREEN'), ' media' => 'all'), true);
        MHtml::_('script', $tag . '/calendar.js', false, true);
        MHtml::_('script', $tag . '/calendar-setup.js', false, true);

        $translation = self::calendartranslation();

        if ($translation) {
            $document->addScriptDeclaration($translation);
        }

        self::$loaded[__METHOD__] = true;
    }

    public static function colorpicker() {
        // Only load once
        if (isset(self::$loaded[__METHOD__])) {
            return;
        }

        // Include jQuery
        MHtml::_('jquery.framework');

        MHtml::_('script', 'jui/jquery.minicolors.min.js', false, true);
        MHtml::_('stylesheet', 'jui/jquery.minicolors.css', false, true);
        MFactory::getDocument()->addScriptDeclaration("
				jQuery(document).ready(function (){
					jQuery('.minicolors').each(function() {
						jQuery(this).minicolors({
							control: jQuery(this).attr('data-control') || 'hue',
							position: jQuery(this).attr('data-position') || 'right',
							theme: 'bootstrap'
						});
					});
				});
			"
        );

        self::$loaded[__METHOD__] = true;
    }

    public static function simplecolorpicker() {
        // Only load once
        if (isset(self::$loaded[__METHOD__])) {
            return;
        }

        // Include jQuery
        MHtml::_('jquery.framework');

        MHtml::_('script', 'jui/jquery.simplecolors.min.js', false, true);
        MHtml::_('stylesheet', 'jui/jquery.simplecolors.css', false, true);
        MFactory::getDocument()->addScriptDeclaration("
				jQuery(document).ready(function (){
					jQuery('select.simplecolors').simplecolors();
				});
			"
        );

        self::$loaded[__METHOD__] = true;
    }

    public static function keepalive() {
        // Only load once
        if (isset(self::$loaded[__METHOD__])) {
            return;
        }

        // Include jQuery
        MHtml::_('jquery.framework');

        $config = MFactory::getConfig();
        $lifetime = ($config->get('lifetime') * 60000);
        $refreshTime = ($lifetime <= 60000) ? 30000 : $lifetime - 60000;

        // Refresh time is 1 minute less than the liftime assined in the configuration.php file.

        // The longest refresh period is one hour to prevent integer overflow.
        if ($refreshTime > 3600000 || $refreshTime <= 0) {
            $refreshTime = 3600000;
        }

        $document = MFactory::getDocument();
        $script = '';
        $script .= 'function keepAlive() {';
        $script .= '	jQuery.get("index.php");';
        $script .= '}';
        $script .= 'jQuery(function($)';
        $script .= '{ setInterval(keepAlive, ' . $refreshTime . '); }';
        $script .= ');';

        $document->addScriptDeclaration($script);
        self::$loaded[__METHOD__] = true;

        return;
    }

    public static function highlighter(array $terms, $start = 'highlighter-start', $end = 'highlighter-end', $className = 'highlight', $tag = 'span') {
        $sig = md5(serialize(array($terms, $start, $end)));

        if (isset(self::$loaded[__METHOD__][$sig])) {
            return;
        }

        // Include jQuery
        MHtml::_('jquery.framework');

        MHtml::_('script', 'system/highlighter.js', true, true);

        $terms = str_replace('"', '\"', $terms);

        $document = MFactory::getDocument();
        $document->addScriptDeclaration("
			jQuery(function ($) {
				var start = document.getElementById('" . $start . "');
				var end = document.getElementById('" . $end . "');
				if (!start || !end || !Miwi.Highlighter) {
					return true;
				}
				highlighter = new Miwi.Highlighter({
					startElement: start,
					endElement: end,
					className: '" . $className . "',
					onlyWords: false,
					tag: '" . $tag . "'
				}).highlight([\"" . implode('","', $terms) . "\"]);
				$(start).remove();
				$(end).remove();
			});
		");

        self::$loaded[__METHOD__][$sig] = true;

        return;
    }

    public static function noframes() {
        // Only load once
        if (isset(self::$loaded[__METHOD__])) {
            return;
        }

        // Include MooTools framework
        self::framework();

        // Include jQuery
        MHtml::_('jquery.framework');

        $js = "jQuery(function () {if (top == self) {document.documentElement.style.display = 'block'; }" .
            " else {top.location = self.location; }});";
        $document = MFactory::getDocument();
        $document->addStyleDeclaration('html { display:none }');
        $document->addScriptDeclaration($js);

        MFactory::getApplication()->setHeader('X-Frames-Options', 'SAMEORIGIN');

        self::$loaded[__METHOD__] = true;
    }

    protected static function _getJSObject($array = array()) {
        MLog::add('MHtmlBehavior::_getJSObject() is deprecated. MHtml::getJSObject() instead..', MLog::WARNING, 'deprecated');

        return MHtml::getJSObject($array);
    }

    protected static function calendartranslation() {
        static $jsscript = 0;

        // Guard clause, avoids unnecessary nesting
        if ($jsscript) {
            return false;
        }

        $jsscript = 1;

        // To keep the code simple here, run strings through MText::_() using array_map()
        $callback = array('MText', '_');
        $weekdays_full = array_map(
            $callback, array(
                'SUNDAY', 'MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY', 'SUNDAY'
            )
        );
        $weekdays_short = array_map(
            $callback,
            array(
                'SUN', 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN'
            )
        );
        $months_long = array_map(
            $callback, array(
                'JANUARY', 'FEBRUARY', 'MARCH', 'APRIL', 'MAY', 'JUNE',
                'JULY', 'AUGUST', 'SEPTEMBER', 'OCTOBER', 'NOVEMBER', 'DECEMBER'
            )
        );
        $months_short = array_map(
            $callback, array(
                'JANUARY_SHORT', 'FEBRUARY_SHORT', 'MARCH_SHORT', 'APRIL_SHORT', 'MAY_SHORT', 'JUNE_SHORT',
                'JULY_SHORT', 'AUGUST_SHORT', 'SEPTEMBER_SHORT', 'OCTOBER_SHORT', 'NOVEMBER_SHORT', 'DECEMBER_SHORT'
            )
        );

        // This will become an object in Javascript but define it first in PHP for readability
        $today = " " . MText::_('MLIB_HTML_BEHAVIOR_TODAY') . " ";
        $text = array(
            'INFO' => MText::_('MLIB_HTML_BEHAVIOR_ABOUT_THE_CALENDAR'),

            'ABOUT' => "DHTML Date/Time Selector\n"
                . "(c) dynarch.com 2002-2005 / Author: Mihai Bazon\n"
                . "For latest version visit: http://www.dynarch.com/projects/calendar/\n"
                . "Distributed under GNU LGPL.  See http://gnu.org/licenses/lgpl.html for details."
                . "\n\n"
                . MText::_('MLIB_HTML_BEHAVIOR_DATE_SELECTION')
                . MText::_('MLIB_HTML_BEHAVIOR_YEAR_SELECT')
                . MText::_('MLIB_HTML_BEHAVIOR_MONTH_SELECT')
                . MText::_('MLIB_HTML_BEHAVIOR_HOLD_MOUSE'),

            'ABOUT_TIME' => "\n\n"
                . "Time selection:\n"
                . "- Click on any of the time parts to increase it\n"
                . "- or Shift-click to decrease it\n"
                . "- or click and drag for faster selection.",

            'PREV_YEAR' => MText::_('MLIB_HTML_BEHAVIOR_PREV_YEAR_HOLD_FOR_MENU'),
            'PREV_MONTH' => MText::_('MLIB_HTML_BEHAVIOR_PREV_MONTH_HOLD_FOR_MENU'),
            'GO_TODAY' => MText::_('MLIB_HTML_BEHAVIOR_GO_TODAY'),
            'NEXT_MONTH' => MText::_('MLIB_HTML_BEHAVIOR_NEXT_MONTH_HOLD_FOR_MENU'),
            'SEL_DATE' => MText::_('MLIB_HTML_BEHAVIOR_SELECT_DATE'),
            'DRAG_TO_MOVE' => MText::_('MLIB_HTML_BEHAVIOR_DRAG_TO_MOVE'),
            'PART_TODAY' => $today,
            'DAY_FIRST' => MText::_('MLIB_HTML_BEHAVIOR_DISPLAY_S_FIRST'),
            'WEEKEND' => MText::_('MLIB_HTML_BEHAVIOR_DISPLAY_WEEKEND', '0,6'),//MFactory::getLanguage()->getWeekEnd(),
            'CLOSE' => MText::_('MLIB_HTML_BEHAVIOR_CLOSE'),
            'TODAY' => MText::_('MLIB_HTML_BEHAVIOR_TODAY'),
            'TIME_PART' => MText::_('MLIB_HTML_BEHAVIOR_SHIFT_CLICK_OR_DRAG_TO_CHANGE_VALUE'),
            'DEF_DATE_FORMAT' => "%Y-%m-%d",
            'TT_DATE_FORMAT' => MText::_('MLIB_HTML_BEHAVIOR_TT_DATE_FORMAT'),
            'WK' => MText::_('MLIB_HTML_BEHAVIOR_WK'),
            'TIME' => MText::_('MLIB_HTML_BEHAVIOR_TIME')
        );

        return 'jQuery(document).ready(function (){'
                    . 'Calendar._DN = ' . json_encode($weekdays_full) . ';'
                    . ' Calendar._SDN = ' . json_encode($weekdays_short) . ';'
                    . ' Calendar._FD = 0;'
                    . ' Calendar._MN = ' . json_encode($months_long) . ';'
                    . ' Calendar._SMN = ' . json_encode($months_short) . ';'
                    . ' Calendar._TT = ' . json_encode($text) . ';'
                . '});';
    }

    public static function tabstate() {
        if (isset(self::$loaded[__METHOD__])) {
            return;
        }
        // Include jQuery
        MHtml::_('jquery.framework');
        MHtml::_('script', 'system/tabs-state.js', false, true);
        self::$loaded[__METHOD__] = true;
    }
}