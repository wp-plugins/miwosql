<?php

/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

define('_QQ_', '"');

// import some libraries
mimport('framework.filesystem.stream');

class MLanguage extends MObject {
    protected static $languages = array();

    protected $debug = false;

    protected $default = 'en-GB';

    protected $orphans = array();

    protected $metadata = null;

    protected $locale = null;

    protected $lang = null;

    protected $paths = array();

    protected $errorfiles = array();

    protected $strings = null;

    protected $used = array();

    protected $counter = 0;

    protected $override = array();

    protected $transliterator = null;

    protected $pluralSuffixesCallback = null;

    protected $ignoredSearchWordsCallback = null;

    protected $lowerLimitSearchWordCallback = null;

    protected $upperLimitSearchWordCallback = null;

    protected $searchDisplayedCharactersNumberCallback = null;

    public function __construct($lang = null, $debug = false) {
        $this->strings = array();

        if ($lang == null) {
            $lang = $this->default;
        }

        $this->setLanguage($lang);
        $this->setDebug($debug);

        $filename = MPATH_LANGUAGES . "/site/overrides/$lang.override.ini";

        if (file_exists($filename) && $contents = $this->parse($filename)) {
            if (is_array($contents)) {
                $this->override = $contents;
            }
            unset($contents);
        }

        // Look for a language specific localise class
        $class = str_replace('-', '_', $lang . 'Localise');
        $paths = array();
        if (defined('MPATH_SITE')) {
            // Note: Manual indexing to enforce load order.
            $paths[0] = MPATH_LANGUAGES . "/site/overrides/$lang.localise.php";
            $paths[2] = MPATH_LANGUAGES . "/site/$lang/$lang.localise.php";
        }

        if (defined('MPATH_ADMINISTRATOR')) {
            // Note: Manual indexing to enforce load order.
            $paths[1] = MPATH_LANGUAGES . "/admin/overrides/$lang.localise.php";
            $paths[3] = MPATH_LANGUAGES . "/admin/$lang/$lang.localise.php";
        }

        ksort($paths);
        $path = reset($paths);

        while (!class_exists($class) && $path) {
            if (file_exists($path)) {
                require_once $path;
            }
            $path = next($paths);
        }

        if (class_exists($class)) {
            /* Class exists. Try to find
             * -a transliterate method,
             * -a getPluralSuffixes method,
             * -a getIgnoredSearchWords method
             * -a getLowerLimitSearchWord method
             * -a getUpperLimitSearchWord method
             * -a getSearchDisplayCharactersNumber method
             */
            if (method_exists($class, 'transliterate')) {
                $this->transliterator = array($class, 'transliterate');
            }

            if (method_exists($class, 'getPluralSuffixes')) {
                $this->pluralSuffixesCallback = array($class, 'getPluralSuffixes');
            }

            if (method_exists($class, 'getIgnoredSearchWords')) {
                $this->ignoredSearchWordsCallback = array($class, 'getIgnoredSearchWords');
            }

            if (method_exists($class, 'getLowerLimitSearchWord')) {
                $this->lowerLimitSearchWordCallback = array($class, 'getLowerLimitSearchWord');
            }

            if (method_exists($class, 'getUpperLimitSearchWord')) {
                $this->upperLimitSearchWordCallback = array($class, 'getUpperLimitSearchWord');
            }

            if (method_exists($class, 'getSearchDisplayedCharactersNumber')) {
                $this->searchDisplayedCharactersNumberCallback = array($class, 'getSearchDisplayedCharactersNumber');
            }
        }

        $this->load();
    }

    public static function getInstance($lang, $debug = false) {
        if (!isset(self::$languages[$lang . $debug])) {
            self::$languages[$lang . $debug] = new MLanguage($lang, $debug);
        }

        return self::$languages[$lang . $debug];
    }

    public function _($string, $jsSafe = false, $interpretBackSlashes = true) {
        // Detect empty string
        if ($string == '') {
            return '';
        }

        $key = strtoupper($string);

        if (isset($this->strings[$key])) {
            $string = $this->debug ? '**' . $this->strings[$key] . '**' : $this->strings[$key];

            // Store debug information
            if ($this->debug) {
                $caller = $this->getCallerInfo();

                if (!array_key_exists($key, $this->used)) {
                    $this->used[$key] = array();
                }

                $this->used[$key][] = $caller;
            }
        }
        else {
            if ($this->debug) {
                $caller           = $this->getCallerInfo();
                $caller['string'] = $string;

                if (!array_key_exists($key, $this->orphans)) {
                    $this->orphans[$key] = array();
                }

                $this->orphans[$key][] = $caller;

                $string = '??' . $string . '??';
            }
        }

        if ($jsSafe) {
            // Javascript filter
            $string = addslashes($string);
        }
        elseif ($interpretBackSlashes) {
            // Interpret \n and \t characters
            $string = str_replace(array('\\\\', '\t', '\n'), array("\\", "\t", "\n"), $string);
        }

        return $string;
    }

    public function transliterate($string) {
        include_once dirname(__FILE__) . '/latin_transliterate.php';

        if ($this->transliterator !== null) {
            return call_user_func($this->transliterator, $string);
        }

        $string = MLanguageTransliterate::utf8_latin_to_ascii($string);
        $string = MString::strtolower($string);

        return $string;
    }

    public function getTransliterator() {
        return $this->transliterator;
    }

    public function setTransliterator($function) {
        $previous             = $this->transliterator;
        $this->transliterator = $function;

        return $previous;
    }

    public function getPluralSuffixes($count) {
        if ($this->pluralSuffixesCallback !== null) {
            return call_user_func($this->pluralSuffixesCallback, $count);
        }
        else {
            return array((string)$count);
        }
    }

    public function getPluralSufficesCallback() {
        // Deprecation warning.
        MLog::add('MLanguage::_getPluralSufficesCallback() is deprecated.', MLog::WARNING, 'deprecated');

        return $this->getPluralSuffixesCallback();
    }

    public function getPluralSuffixesCallback() {
        return $this->pluralSuffixesCallback;
    }

    public function setPluralSuffixesCallback($function) {
        $previous                     = $this->pluralSuffixesCallback;
        $this->pluralSuffixesCallback = $function;

        return $previous;
    }

    public function getIgnoredSearchWords() {
        if ($this->ignoredSearchWordsCallback !== null) {
            return call_user_func($this->ignoredSearchWordsCallback);
        }
        else {
            return array();
        }
    }

    public function getIgnoredSearchWordsCallback() {
        return $this->ignoredSearchWordsCallback;
    }

    public function setIgnoredSearchWordsCallback($function) {
        $previous                         = $this->ignoredSearchWordsCallback;
        $this->ignoredSearchWordsCallback = $function;

        return $previous;
    }

    public function getLowerLimitSearchWord() {
        if ($this->lowerLimitSearchWordCallback !== null) {
            return call_user_func($this->lowerLimitSearchWordCallback);
        }
        else {
            return 3;
        }
    }

    public function getLowerLimitSearchWordCallback() {
        return $this->lowerLimitSearchWordCallback;
    }

    public function setLowerLimitSearchWordCallback($function) {
        $previous                           = $this->lowerLimitSearchWordCallback;
        $this->lowerLimitSearchWordCallback = $function;

        return $previous;
    }

    public function getUpperLimitSearchWord() {
        if ($this->upperLimitSearchWordCallback !== null) {
            return call_user_func($this->upperLimitSearchWordCallback);
        }
        else {
            return 20;
        }
    }

    public function getUpperLimitSearchWordCallback() {
        return $this->upperLimitSearchWordCallback;
    }

    public function setUpperLimitSearchWordCallback($function) {
        $previous                           = $this->upperLimitSearchWordCallback;
        $this->upperLimitSearchWordCallback = $function;

        return $previous;
    }

    public function getSearchDisplayedCharactersNumber() {
        if ($this->searchDisplayedCharactersNumberCallback !== null) {
            return call_user_func($this->searchDisplayedCharactersNumberCallback);
        }
        else {
            return 200;
        }
    }

    public function getSearchDisplayedCharactersNumberCallback() {
        return $this->searchDisplayedCharactersNumberCallback;
    }

    public function setSearchDisplayedCharactersNumberCallback($function) {
        $previous                                      = $this->searchDisplayedCharactersNumberCallback;
        $this->searchDisplayedCharactersNumberCallback = $function;

        return $previous;
    }

    public static function exists($lang, $basePath = MPATH_WP_PLG) {
        static $paths = array();

        // Return false if no language was specified
        if (!$lang) {
            return false;
        }

        if(is_admin()) {
            $path = "$basePath/miwi/languages/admin/$lang";
        }
        else {
            $path = "$basePath/miwi/languages/site/$lang";
        }

        // Return previous check results if it exists
        if (isset($paths[$path])) {
            return $paths[$path];
        }

        // Check if the language exists
        mimport('framework.filesystem.folder');

        $paths[$path] = MFolder::exists($path);

        return $paths[$path];
    }

    public function load($extension = 'miwi', $basePath = null, $lang = null, $reload = false, $default = true) {
        if (!$lang) {
            $lang = $this->lang;
        }

        $path = self::getLanguagePath($basePath, $lang);

        $internal = $extension == 'miwi' || $extension == '';
        $filename = $internal ? $lang : $lang . '.' . $extension;
        $filename = "$path/$filename.ini";

        $result = false;

        if (isset($this->paths[$extension][$filename]) && !$reload) {
            // This file has already been tested for loading.
            $result = $this->paths[$extension][$filename];
        }
        else {
            // Load the language file
            $result = $this->loadLanguage($filename, $extension);

            // Check whether there was a problem with loading the file
            if ($result === false && $default) {
                // No strings, so either file doesn't exist or the file is invalid
                $oldFilename = $filename;

                // Check the standard file name
                $path     = self::getLanguagePath($basePath, $this->default);
                $filename = $internal ? $this->default : $this->default . '.' . $extension;
                $filename = "$path/$filename.ini";

                // If the one we tried is different than the new name, try again
                if ($oldFilename != $filename) {
                    $result = $this->loadLanguage($filename, $extension, false);
                }
            }
        }

        return $result;
    }

    protected function loadLanguage($filename, $extension = 'unknown', $overwrite = true) {
        $this->counter++;

        $result  = false;
        $strings = false;

        if (file_exists($filename)) {
            $strings = $this->parse($filename);
        }

        if ($strings) {
            if (is_array($strings)) {
                $this->strings = array_merge($this->strings, $strings);
            }

            if (is_array($strings) && count($strings)) {
                $this->strings = array_merge($this->strings, $this->override);
                $result        = true;
            }
        }

        // Record the result of loading the extension's file.
        if (!isset($this->paths[$extension])) {
            $this->paths[$extension] = array();
        }

        $this->paths[$extension][$filename] = $result;

        return $result;
    }

    protected function parse($filename) {
        $version = phpversion();

        // Capture hidden PHP errors from the parsing.
        $php_errormsg = null;
        $track_errors = ini_get('track_errors');
        ini_set('track_errors', true);

        if ($version >= '5.3.1') {
            $contents = file_get_contents($filename);
            $contents = str_replace('_QQ_', '"\""', $contents);
            $strings  = @parse_ini_string($contents);
        }
        else {
            $strings = @parse_ini_file($filename);

            if ($version == '5.3.0' && is_array($strings)) {
                foreach ($strings as $key => $string) {
                    $strings[$key] = str_replace('_QQ_', '"', $string);
                }
            }
        }

        // Restore error tracking to what it was before.
        ini_set('track_errors', $track_errors);

        if (!is_array($strings)) {
            $strings = array();
        }

        if ($this->debug) {
            // Initialise variables for manually parsing the file for common errors.
            $blacklist   = array('YES', 'NO', 'NULL', 'FALSE', 'ON', 'OFF', 'NONE', 'TRUE');
            $regex       = '/^(|(\[[^\]]*\])|([A-Z][A-Z0-9_\-]*\s*=(\s*(("[^"]*")|(_QQ_)))+))\s*(;.*)?$/';
            $this->debug = false;
            $errors      = array();
            $lineNumber  = 0;

            // Open the file as a stream.
            $stream = new MStream;
            $stream->open($filename);

            while (!$stream->eof()) {
                $line = $stream->gets();
                // Avoid BOM error as BOM is OK when using parse_ini
                if ($lineNumber == 0) {
                    $line = str_replace("\xEF\xBB\xBF", '', $line);
                }
                $lineNumber++;

                // Check that the key is not in the blacklist and that the line format passes the regex.
                $key = strtoupper(trim(substr($line, 0, strpos($line, '='))));

                if (!preg_match($regex, $line) || in_array($key, $blacklist)) {
                    $errors[] = $lineNumber;
                }
            }

            $stream->close();

            // Check if we encountered any errors.
            if (count($errors)) {
                if (basename($filename) != $this->lang . '.ini') {
                    $this->errorfiles[$filename] = $filename . MText::sprintf('MERROR_PARSING_LANGUAGE_FILE', implode(', ', $errors));
                }
                else {
                    $this->errorfiles[$filename] = $filename . '&#160;: error(s) in line(s) ' . implode(', ', $errors);
                }
            }
            elseif ($php_errormsg) {
                // We didn't find any errors but there's probably a parse notice.
                $this->errorfiles['PHP' . $filename] = 'PHP parser errors :' . $php_errormsg;
            }

            $this->debug = true;
        }

        return $strings;
    }

    public function get($property, $default = null) {
        if (isset($this->metadata[$property])) {
            return $this->metadata[$property];
        }

        return $default;
    }

    protected function getCallerInfo() {
        // Try to determine the source if none was provided
        if (!function_exists('debug_backtrace')) {
            return null;
        }

        $backtrace = debug_backtrace();
        $info      = array();

        // Search through the backtrace to our caller
        $continue = true;
        while ($continue && next($backtrace)) {
            $step  = current($backtrace);
            $class = @ $step['class'];

            // We're looking for something outside of language.php
            if ($class != 'MLanguage' && $class != 'MText') {
                $info['function'] = @ $step['function'];
                $info['class']    = $class;
                $info['step']     = prev($backtrace);

                // Determine the file and name of the file
                $info['file'] = @ $step['file'];
                $info['line'] = @ $step['line'];

                $continue = false;
            }
        }

        return $info;
    }

    public function getName() {
        return $this->metadata['name'];
    }

    public function getPaths($extension = null) {
        if (isset($extension)) {
            if (isset($this->paths[$extension])) {
                return $this->paths[$extension];
            }

            return null;
        }
        else {
            return $this->paths;
        }
    }

    public function getErrorFiles() {
        return $this->errorfiles;
    }

    public function getTag() {
        return $this->metadata['tag'];
    }

    public function isRTL() {
        return $this->metadata['rtl'];
    }

    public function setDebug($debug) {
        $previous    = $this->debug;
        $this->debug = $debug;

        return $previous;
    }

    public function getDebug() {
        return $this->debug;
    }

    public function getDefault() {
        return $this->default;
    }

    public function setDefault($lang) {
        $previous      = $this->default;
        $this->default = $lang;

        return $previous;
    }

    public function getOrphans() {
        return $this->orphans;
    }

    public function getUsed() {
        return $this->used;
    }

    public function hasKey($string) {
        $key = strtoupper($string);

        return isset($this->strings[$key]);
    }

    public static function getMetadata($lang) {
        $path = self::getLanguagePath(MPATH_BASE, $lang);
        $file = "$lang.xml";

        $result = null;

        if (is_file("$path/$file")) {
            $result = self::parseXMLLanguageFile("$path/$file");
        }

        return $result;
    }

    public static function getKnownLanguages($basePath = MPATH_BASE) {
        $dir            = self::getLanguagePath($basePath);
        $knownLanguages = self::parseLanguageFiles($dir);

        return $knownLanguages;
    }

    public static function getLanguagePath($basePath = null, $language = null) {
        if (is_null($basePath)) {
            $basePath = MPATH_SITE;
            if (MFactory::getApplication()->isAdmin()) {
                $side = 'admin';
            }
            else {
                $side = 'site';
            }
        } else {
            if (strpos($basePath, 'admin') === false) {
                $side = 'site';
            }
            else {
                $side = 'admin';
            }
        }

        $basePath = str_replace('/admin', '', $basePath);
        $dir = "$basePath/languages/$side";

        if (!empty($language)) {
            $dir .= "/$language";
        }

        return $dir;
    }

    public function setLanguage($lang) {
        $previous       = $this->lang;
        $this->lang     = $lang;
        $this->metadata = $this->getMetadata($this->lang);

        return $previous;
    }

    public function getLocale() {
        if (!isset($this->locale)) {
            $locale = str_replace(' ', '', isset($this->metadata['locale']) ? $this->metadata['locale'] : '');

            if ($locale) {
                $this->locale = explode(',', $locale);
            }
            else {
                $this->locale = false;
            }
        }

        return $this->locale;
    }

    public function getFirstDay() {
        return (int)(isset($this->metadata['firstDay']) ? $this->metadata['firstDay'] : 0);
    }

    public static function _parseLanguageFiles($dir = null) {
        // Deprecation warning.
        MLog::add('MLanguage::_parseLanguageFiles() is deprecated.', MLog::WARNING, 'deprecated');

        return self::parseLanguageFiles($dir);
    }

    public static function parseLanguageFiles($dir = null) {
        mimport('framework.filesystem.folder');

        $languages = array();

        $subdirs = MFolder::folders($dir);

        foreach ($subdirs as $path) {
            $langs     = self::parseXMLLanguageFiles("$dir/$path");
            $languages = array_merge($languages, $langs);
        }

        return $languages;
    }

    public static function _parseXMLLanguageFiles($dir = null) {
        // Deprecation warning.
        MLog::add('MLanguage::_parseXMLLanguageFiles() is deprecated.', MLog::WARNING, 'deprecated');

        return self::parseXMLLanguageFiles($dir);
    }

    public static function parseXMLLanguageFiles($dir = null) {
        if ($dir == null) {
            return null;
        }

        $languages = array();
        mimport('framework.filesystem.folder');
        $files = MFolder::files($dir, '^([-_A-Za-z]*)\.xml$');

        foreach ($files as $file) {
            if ($content = file_get_contents("$dir/$file")) {
                if ($metadata = self::parseXMLLanguageFile("$dir/$file")) {
                    $lang             = str_replace('.xml', '', $file);
                    $languages[$lang] = $metadata;
                }
            }
        }

        return $languages;
    }

    public static function _parseXMLLanguageFile($path) {
        return self::parseXMLLanguageFile($path);
    }

    public static function parseXMLLanguageFile($path) {
        // Try to load the file
        if (!$xml = MFactory::getXML($path)) {
            return null;
        }

        // Check that it's a metadata file
        if ((string)$xml->getName() != 'metafile') {
            return null;
        }

        $metadata = array();

        foreach ($xml->metadata->children() as $child) {
            $metadata[$child->getName()] = (string)$child;
        }

        return $metadata;
    }
	
	public function getLang(){
        return $this->lang;
    }
}