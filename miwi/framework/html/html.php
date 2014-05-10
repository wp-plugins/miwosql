<?php

/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

MHtml::addIncludePath(MPATH_PLATFORM . '/framework/html/html');

mimport('framework.environment.uri');
mimport('framework.environment.browser');
mimport('framework.filesystem.file');


class MHtml {

    public static $formatOptions = array('format.depth' => 0, 'format.eol' => "\n", 'format.indent' => "\t");
    protected static $includePaths = array();
    protected static $registry = array();

    protected static function extract($key) {
        $key = preg_replace('#[^A-Z0-9_\.]#i', '', $key);

        // Check to see whether we need to load a helper file
        $parts = explode('.', $key);

        $prefix = (count($parts) == 3 ? array_shift($parts) : 'MHtml');
        $file   = (count($parts) == 2 ? array_shift($parts) : '');
        $func   = array_shift($parts);

        return array(strtolower($prefix . '.' . $file . '.' . $func), $prefix, $file, $func);
    }

    public static function _($key) {
        list($key, $prefix, $file, $func) = self::extract($key);
        if (array_key_exists($key, self::$registry)) {
            $function = self::$registry[$key];
            $args     = func_get_args();
            // Remove function name from arguments
            array_shift($args);

            return MHtml::call($function, $args);
        }

        $className = $prefix . ucfirst($file);

        if (!class_exists($className)) {
            mimport('framework.filesystem.path');
			self::addIncludePath(MPATH_MIWI.'/proxy/html/html');
            if ($path = MPath::find(MHtml::$includePaths, strtolower($file) . '.php')) {
                require_once $path;

                if (!class_exists($className)) {
                    MError::raiseError(500, MText::sprintf('MLIB_HTML_ERROR_NOTFOUNDINFILE', $className, $func));

                    return false;
                }
            }
            else {
                MError::raiseError(500, MText::sprintf('MLIB_HTML_ERROR_NOTSUPPORTED_NOFILE', $prefix, $file));

                return false;
            }
        }

        $toCall = array($className, $func);
        if (is_callable($toCall)) {
            MHtml::register($key, $toCall);
            $args = func_get_args();
            // Remove function name from arguments
            array_shift($args);

            return MHtml::call($toCall, $args);
        }
        else {
            MError::raiseError(500, MText::sprintf('MLIB_HTML_ERROR_NOTSUPPORTED', $className, $func));

            return false;
        }
    }

    public static function register($key, $function) {
        list($key) = self::extract($key);
        if (is_callable($function)) {
            self::$registry[$key] = $function;

            return true;
        }

        return false;
    }

    public static function unregister($key) {
        list($key) = self::extract($key);
        if (isset(self::$registry[$key])) {
            unset(self::$registry[$key]);

            return true;
        }

        return false;
    }

    public static function isRegistered($key) {
        list($key) = self::extract($key);

        return isset(self::$registry[$key]);
    }

    protected static function call($function, $args) {
        if (is_callable($function)) {
            // PHP 5.3 workaround
            $temp = array();
            foreach ($args as &$arg) {
                $temp[] = & $arg;
            }

            return call_user_func_array($function, $temp);
        }
        else {
            MError::raiseError(500, MText::_('MLIB_HTML_ERROR_FUNCTION_NOT_SUPPORTED'));

            return false;
        }
    }

    public static function link($url, $text, $attribs = null) {
        if (is_array($attribs)) {
            $attribs = MArrayHelper::toString($attribs);
        }

        return '<a href="' . $url . '" ' . $attribs . '>' . $text . '</a>';
    }

    public static function iframe($url, $name, $attribs = null, $noFrames = '') {
        if (is_array($attribs)) {
            $attribs = MArrayHelper::toString($attribs);
        }

        return '<iframe src="' . $url . '" ' . $attribs . ' name="' . $name . '">' . $noFrames . '</iframe>';
    }

    protected static function _includeRelativeFiles($file, $relative, $detect_browser, $folder) {
        MLog::add('MHtml::_includeRelativeFiles() is deprecated.  Use MHtml::includeRelativeFiles().', MLog::WARNING, 'deprecated');

        return self::includeRelativeFiles($folder, $file, $relative, $detect_browser, false);
    }

    protected static function includeRelativeFiles($folder, $file, $relative, $detect_browser, $detect_debug) {
        // If http is present in filename
        if (strpos($file, 'http') === 0) {
            $includes = array($file);
        }
        else {
            // Extract extension and strip the file
            $strip = MFile::stripExt($file);
            $ext   = MFile::getExt($file);

            // Detect browser and compute potential files
            if ($detect_browser) {
                $navigator = MBrowser::getInstance();
                $browser   = $navigator->getBrowser();
                $major     = $navigator->getMajor();
                $minor     = $navigator->getMinor();

                // Try to include files named filename.ext, filename_browser.ext, filename_browser_major.ext, filename_browser_major_minor.ext
                // where major and minor are the browser version names
                $potential = array($strip, $strip . '_' . $browser, $strip . '_' . $browser . '_' . $major,
                    $strip . '_' . $browser . '_' . $major . '_' . $minor);
            }
            else {
                $potential = array($strip);
            }

            // If relative search in template directory or media directory
            if ($relative) {

                // Get the template
                $app      = MFactory::getApplication();
                $template = $app->getTemplate();

                // Prepare array of files
                $includes = array();

                // For each potential files
                foreach ($potential as $strip) {
                    $files = array();
                    // Detect debug mode
                    if ($detect_debug && MFactory::getConfig()->get('debug')) {
                        $files[] = $strip . '-uncompressed.' . $ext;
                    }
                    $files[] = $strip . '.' . $ext;

                    // Loop on 1 or 2 files and break on first found
                    foreach ($files as $file) {
                        // If the file is in the template folder
                        if (file_exists(MPATH_THEMES . "/$template/$folder/$file")) {
                            $includes[] = MURI::base(true) . "/templates/$template/$folder/$file";
                            break;
                        }
                        else {
                            // If the file contains any /: it can be in an media extension subfolder
                            if (strpos($file, '/')) {
                                // Divide the file extracting the extension as the first part before /
                                list($extension, $file) = explode('/', $file, 2);

                                // If the file yet contains any /: it can be a plugin
                                if (strpos($file, '/')) {
                                    // Divide the file extracting the element as the first part before /
                                    list($element, $file) = explode('/', $file, 2);

                                    // Try to deal with plugins group in the media folder
                                    if (file_exists(MPATH_ROOT . "/media/$extension/$element/$folder/$file")) {
                                        $includes[] = MURL_WP_CNT."/miwi/media/$extension/$element/$folder/$file";
                                        break;
                                    }
                                    // Try to deal with classical file in a a media subfolder called element
                                    elseif (file_exists(MPATH_ROOT . "/media/$extension/$folder/$element/$file")) {
                                        $includes[] = MURL_WP_CNT."/miwi/media/$extension/$folder/$element/$file";
                                        break;
                                    }
                                    // Try to deal with system files in the template folder
                                    elseif (file_exists(MPATH_THEMES . "/$template/$folder/system/$element/$file")) {
                                        $includes[] =  MURL_WP_CNT."/miwi/templates/$template/$folder/system/$element/$file";
                                        break;
                                    }
                                    // Try to deal with system files in the media folder
                                    elseif (file_exists(MPATH_ROOT . "/media/system/$folder/$element/$file")) {
                                        $includes[] =  MURL_WP_CNT."/miwi/media/system/$folder/$element/$file";
                                        break;
                                    }
                                }
                                // Try to deals in the extension media folder
                                elseif (file_exists(MPATH_ROOT . "/media/$extension/$folder/$file")) {
                                    $includes[] = MURL_WP_CNT."/miwi/media/$extension/$folder/$file";
                                    break;
                                }
                                // Try to deal with system files in the template folder
                                elseif (file_exists(MPATH_THEMES . "/$template/$folder/system/$file")) {
                                    $includes[] = MURL_WP_CNT."/miwi/templates/$template/$folder/system/$file";
                                    break;
                                }
                                // Try to deal with system files in the media folder
                                elseif (file_exists(MPATH_ROOT . "/media/system/$folder/$file")) {
                                    $includes[] = MURL_WP_CNT."/miwi/media/system/$folder/$file";
                                    break;
                                }
                            }
                            // Try to deal with system files in the media folder
                            elseif (file_exists(MPATH_ROOT . "/media/system/$folder/$file")) {
                                $includes[] = MURL_WP_CNT."/miwi/media/system/$folder/$file";
                                break;
                            }
                        }
                    }
                }
            }
            // If not relative and http is not present in filename
            else {
                $includes = array();
                foreach ($potential as $strip) {
                    // Detect debug mode
                    if ($detect_debug && MFactory::getConfig()->get('debug') && file_exists(MPATH_ROOT . "/$strip-uncompressed.$ext")) {
                        $includes[] = MURI::root(false) . "/$strip-uncompressed.$ext";
                    }
                    elseif (file_exists(MPATH_ROOT . "/$strip.$ext")) {
                        $includes[] = MURI::root(false) . "/$strip.$ext";
                    }
                }
            }
        }

        return $includes;
    }

    public static function image($file, $alt, $attribs = null, $relative = false, $path_only = false) {
        if (is_array($attribs)) {
            $attribs = MArrayHelper::toString($attribs);
        }

        $includes = self::includeRelativeFiles('images', $file, $relative, false, false);

        // If only path is required
        if ($path_only) {
            if (count($includes)) {
                return $includes[0];
            }
            else {
                return null;
            }
        }
        else {
            return '<img src="' . (count($includes) ? $includes[0] : '') . '" alt="' . $alt . '" ' . $attribs . ' />';
        }
    }

    public static function stylesheet($file, $attribs = array(), $relative = false, $path_only = false, $detect_browser = true, $detect_debug = true) {
        // Need to adjust for the change in API from 1.5 to 1.6.
        // Function stylesheet($filename, $path = 'media/system/css/', $attribs = array())
        if (is_string($attribs)) {
            MLog::add('The used parameter set in MHtml::stylesheet() is deprecated.', MLog::WARNING, 'deprecated');
            // Assume this was the old $path variable.
            $file = $attribs . $file;
        }

        if (is_array($relative)) {
            // Assume this was the old $attribs variable.
            $attribs  = $relative;
            $relative = false;
        }

        $includes = self::includeRelativeFiles('css', $file, $relative, $detect_browser, $detect_debug);

        // If only path is required
        if ($path_only) {
            if (count($includes) == 0) {
                return null;
            }
            elseif (count($includes) == 1) {
                return $includes[0];
            }
            else {
                return $includes;
            }
        }
        // If inclusion is required
        else {
            $document = MFactory::getDocument();
            foreach ($includes as $include) {
                $document->addStylesheet($include, 'text/css', null, $attribs);
            }
        }
    }

    public static function script($file, $framework = false, $relative = false, $path_only = false, $detect_browser = true, $detect_debug = true) {
        // Need to adjust for the change in API from 1.5 to 1.6.
        // function script($filename, $path = 'media/system/js/', $mootools = true)
        if (is_string($framework)) {
            MLog::add('The used parameter set in MHtml::script() is deprecated.', MLog::WARNING, 'deprecated');
            // Assume this was the old $path variable.
            $file      = $framework . $file;
            $framework = $relative;
        }

        // Include MooTools framework
        if ($framework) {
            MHtml::_('behavior.framework');
        }

        $includes = self::includeRelativeFiles('js', $file, $relative, $detect_browser, $detect_debug);

        // If only path is required
        if ($path_only) {
            if (count($includes) == 0) {
                return null;
            }
            elseif (count($includes) == 1) {
                return $includes[0];
            }
            else {
                return $includes;
            }
        }
        // If inclusion is required
        else {
            $document = MFactory::getDocument();
            foreach ($includes as $include) {
                $document->addScript($include);
            }
        }
    }

    public static function core($debug = null) {
        MLog::add('MHtml::core() is deprecated. Use MHtml::_(\'behavior.framework\');.', MLog::WARNING, 'deprecated');
        MHtml::_('behavior.framework', false, $debug);
    }

    public static function setFormatOptions($options) {
        foreach ($options as $key => $val) {
            if (isset(self::$formatOptions[$key])) {
                self::$formatOptions[$key] = $val;
            }
        }
    }

    public static function date($input = 'now', $format = null, $tz = true, $gregorian = false) {
        // Get some system objects.
        if (!$offset = MFactory::getWOption('timezone_string')) {
            $offset = self::getTimezoneString();
        }
        $user   = MFactory::getUser();

        // UTC date converted to user time zone.
        if ($tz === true) {
            // Get a date object based on UTC.
            $date = MFactory::getDate($input, 'UTC');

            // Set the correct time zone based on the user configuration.
            $date->setTimeZone(new DateTimeZone($user->getParam('timezone', $offset)));
        }
        // UTC date converted to server time zone.
        elseif ($tz === false) {
            // Get a date object based on UTC.
            $date = MFactory::getDate($input, 'UTC');

            // Set the correct time zone based on the server configuration.
            $date->setTimeZone(new DateTimeZone($offset));
        }
        // No date conversion.
        elseif ($tz === null) {
            $date = MFactory::getDate($input);
        }
        // UTC date converted to given time zone.
        else {
            // Get a date object based on UTC.
            $date = MFactory::getDate($input, 'UTC');

            // Set the correct time zone based on the server configuration.
            $date->setTimeZone(new DateTimeZone($tz));
        }

        // If no format is given use the default locale based format.
        if (!$format) {
            $format = MText::_('DATE_FORMAT_LC1');
        }
        // format is an existing language key
        elseif (MFactory::getLanguage()->hasKey($format)) {
            $format = MText::_($format);
        }

        if ($gregorian) {
            return $date->format($format, true);
        }
        else {
            return $date->calendar($format, true);
        }
    }

    public static function tooltip($tooltip, $title = '', $image = 'tooltip.png', $text = '', $href = '', $alt = 'Tooltip', $class = 'hasTip') {
        if (is_array($title)) {
            if (isset($title['image'])) {
                $image = $title['image'];
            }
            if (isset($title['text'])) {
                $text = $title['text'];
            }
            if (isset($title['href'])) {
                $href = $title['href'];
            }
            if (isset($title['alt'])) {
                $alt = $title['alt'];
            }
            if (isset($title['class'])) {
                $class = $title['class'];
            }
            if (isset($title['title'])) {
                $title = $title['title'];
            }
            else {
                $title = '';
            }
        }

        $tooltip = htmlspecialchars($tooltip, ENT_COMPAT, 'UTF-8');
        $title   = htmlspecialchars($title, ENT_COMPAT, 'UTF-8');
        $alt     = htmlspecialchars($alt, ENT_COMPAT, 'UTF-8');

        if (!$text) {
            $text = self::image($image, $alt, null, true);
        }

        if ($href) {
            $tip = '<a href="' . $href . '">' . $text . '</a>';
        }
        else {
            $tip = $text;
        }

        if ($title) {
            $tooltip = $title . '::' . $tooltip;
        }

        return '<span class="' . $class . '" title="' . $tooltip . '">' . $tip . '</span>';
    }

    public static function calendar($value, $name, $id, $format = 'Y-m-d', $attribs = null) {
        static $done;

        if ($done === null) {
            $done = array();
        }

        $readonly = isset($attribs['readonly']) && $attribs['readonly'] == 'readonly';
        $disabled = isset($attribs['disabled']) && $attribs['disabled'] == 'disabled';
        if (is_array($attribs)) {
            $attribs = MArrayHelper::toString($attribs);
        }

        if (!$readonly && !$disabled) {
            // Load the calendar behavior
            self::_('behavior.calendar');
            self::_('behavior.tooltip');

            // Only display the triggers once for each control.
            if (!in_array($id, $done)) {
                $document = MFactory::getDocument();
                $document ->addScriptDeclaration(
                        'jQuery(document).ready(function () {
                            Calendar.setup({
                                // Id of the input field
                                inputField: "' . $id . '",
                                // Format of the input field
                                ifFormat: "' . $format . '",
                                // Trigger for the calendar (button ID)
                                button: "' . $id . '_img",
                                // Alignment (defaults to "Bl")
                                align: "Tl",
                                singleClick: true,
                                firstDay: ' . MFactory::getLanguage()->getFirstDay() . '
                            });
                        });'
                            );
                $done[] = $id;
            }

            return '<input type="text" title="' . (0 !== (int)$value ? self::_('date', $value, null, null) : '') . '" name="' . $name . '" id="' . $id
            . '" value="' . htmlspecialchars($value, ENT_COMPAT, 'UTF-8') . '" ' . $attribs . ' />'
            . self::_('image', 'system/calendar.png', MText::_('MLIB_HTML_CALENDAR'), array('class' => 'calendar', 'id' => $id . '_img'), true);
        }
        else {
            return '<input type="text" title="' . (0 !== (int)$value ? self::_('date', $value, null, null) : '')
            . '" value="' . (0 !== (int)$value ? self::_('date', $value, 'Y-m-d H:i:s', null) : '') . '" ' . $attribs
            . ' /><input type="hidden" name="' . $name . '" id="' . $id . '" value="' . htmlspecialchars($value, ENT_COMPAT, 'UTF-8') . '" />';
        }
    }

    public static function addIncludePath($path = '') {
        // Force path to array
        settype($path, 'array');

        // Loop through the path directories
        foreach ($path as $dir) {
            if (!empty($dir) && !in_array($dir, MHtml::$includePaths)) {
                mimport('joomla.filesystem.path');
                array_unshift(MHtml::$includePaths, MPath::clean($dir));
            }
        }

        return MHtml::$includePaths;
    }

    public static function getJSObject(array $array = array())
    {
        $elements = array();

        foreach ($array as $k => $v)
        {
            // Don't encode either of these types
            if (is_null($v) || is_resource($v))
            {
                continue;
            }

            // Safely encode as a Javascript string
            $key = json_encode((string) $k);

            if (is_bool($v))
            {
                $elements[] = $key . ': ' . ($v ? 'true' : 'false');
            }
            elseif (is_numeric($v))
            {
                $elements[] = $key . ': ' . ($v + 0);
            }
            elseif (is_string($v))
            {
                if (strpos($v, '\\') === 0)
                {
                    // Items such as functions and JSON objects are prefixed with \, strip the prefix and don't encode them
                    $elements[] = $key . ': ' . substr($v, 1);
                }
                else
                {
                    // The safest way to insert a string
                    $elements[] = $key . ': ' . json_encode((string) $v);
                }
            }
            else
            {
                $elements[] = $key . ': ' . self::getJSObject(is_object($v) ? get_object_vars($v) : $v);
            }
        }

        return '{' . implode(',', $elements) . '}';
    }

    protected static function getTimezoneString() {

        // if site timezone string exists, return it
        if ($timezone = MFactory::getWOption('timezone_string')) {
            return $timezone;
        }

        // get UTC offset, if it isn't set then return UTC
        if (0 === ($utc_offset = MFactory::getWOption('gmt_offset', 0))) {
            return 'UTC';
        }

        // adjust UTC offset from hours to seconds
        $utc_offset *= 3600;

        // attempt to guess the timezone string from the UTC offset
        $timezone = timezone_name_from_abbr('', $utc_offset);

        // last try, guess timezone string manually
        if (false === $timezone) {

            $is_dst = date('I');

            foreach (timezone_abbreviations_list() as $abbr) {
                foreach ($abbr as $city) {

                    if ($city['dst'] == $is_dst && $city['offset'] == $utc_offset) {
                        return $city['timezone_id'];
                    }
                }
            }
        }

        // fallback to UTC
        return 'UTC';
    }
} 