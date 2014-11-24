<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

abstract class MFactory {

    public static $application = null;
    public static $cache = null;
    public static $config = null;
    public static $dates = array();
    public static $session = null;
    public static $language = null;
    public static $document = null;
    public static $acl = null;
    public static $database = null;
    public static $mailer = null;

    public static function get(){
        //todo:: fill function
    }

    public static function isW() {
        return defined('ABSPATH');
    }

    public static function isJ() {
        return defined('_JEXEC');
    }
	
    public static function getApplication($id = null, $config = array(), $prefix = 'M') {
        if (!self::$application) {
            if (!$id) {
                //MError::raiseError(500, 'Application Instantiation Error');
            }

            self::$application = MApplication::getInstance($id, $config, $prefix);
        }

        return self::$application;
    }

    public static function getConfig($file = null, $type = 'PHP') {
        if (!self::$config) {
            if ($file === null) {
                $file = MPATH_CONFIGURATION . '/config.php';
            }

            self::$config = self::createConfig($file, $type);
        }

        return self::$config;
    }

    public static function getWOption($name, $default = false, $id = null) {
		$data = null;
        $opt = get_option($name);

        if (is_string($opt)) {
            $data = $opt;
        }
        elseif (is_array($opt)) {
            if(!empty($id) and !empty($opt[$id])){
                return $opt[$id];
            }

            $found = false;

            foreach ($opt as $o) {
                if (!is_array($o)) {
                    continue;
                }

                $data = $o;
                $found = true;
                break;
            }

            if ($found == false) {
                $data = $opt;
            }
        }

        if(empty($data)){
            return $default;
        }

        return $data;
    }

    public static function getWPost($id) {
        $post = get_post($id);

        return $post;
    }

    public static function getSession($options = array()) {
        if (!self::$session) {
            self::$session = self::createSession($options);
        }

        return self::$session;
    }

    public static function getLanguage() {
        if (!self::$language) {
            self::$language = self::createLanguage();
        }

        return self::$language;
    }

    public static function getDocument() {
        if (!self::$document) {
            self::$document = self::createDocument();
        }

        return self::$document;
    }

    public static function getUser($id = null) {
	    $instance = MUser::getInstance($id);
        return $instance;
    }

    public static function getCache($group = '', $handler = 'callback', $storage = null) {
        $hash = md5($group . $handler . $storage);
        if (isset(self::$cache[$hash])) {
            return self::$cache[$hash];
        }
        $handler = ($handler == 'function') ? 'callback' : $handler;

        $options = array('defaultgroup' => $group);

        if (isset($storage)) {
            $options['storage'] = $storage;
        }

        $cache = MCache::getInstance($handler, $options);

        self::$cache[$hash] = $cache;

        return self::$cache[$hash];
    }

    public static function getACL() {
        if (!self::$acl) {
            self::$acl = new MAccess;
        }

        return self::$acl;
    }

    public static function getDbo() {
        if (!self::$database) {
            self::$database = self::createDbo();
        }

        return self::$database;
    }

    public static function getMailer() {
        if (!self::$mailer) {
            self::$mailer = self::createMailer();
        }
        $copy = clone self::$mailer;

        return $copy;
    }

    public static function getFeedParser($url, $cache_time = 0) {
        mimport('simplepie.simplepie');

        $cache = self::getCache('feed_parser', 'callback');

        if ($cache_time > 0) {
            $cache->setLifeTime($cache_time);
        }

        $simplepie = new SimplePie(null, null, 0);

        $simplepie->enable_cache(false);
        $simplepie->set_feed_url($url);
        $simplepie->force_feed(true);

        $contents = $cache->get(array($simplepie, 'init'), null, false, false);

        if ($contents) {
            return $simplepie;
        }
        else {
            MError::raiseWarning('SOME_ERROR_CODE', MText::_('MLIB_UTIL_ERROR_LOADING_FEED_DATA'));
        }

        return false;
    }

    public static function getXMLParser($type = '', $options = array()) {
        // Deprecation warning.
        MLog::add('MFactory::getXMLParser() is deprecated.', MLog::WARNING, 'deprecated');

        $doc = null;

        switch (strtolower($type)) {
            case 'rss':
            case 'atom':
                $cache_time = isset($options['cache_time']) ? $options['cache_time'] : 0;
                $doc = self::getFeedParser($options['rssUrl'], $cache_time);
                break;

            case 'simple':
                // MError::raiseWarning('SOME_ERROR_CODE', 'MSimpleXML is deprecated. Use self::getXML instead');
                mimport('framework.utilities.simplexml');
                $doc = new MSimpleXML;
                break;

            case 'dom':
                MError::raiseWarning('SOME_ERROR_CODE', MText::_('MLIB_UTIL_ERROR_DOMIT'));
                $doc = null;
                break;

            default:
                $doc = null;
        }

        return $doc;
    }

    public static function getXML($data, $isFile = true) {
        mimport('framework.utilities.xmlelement');

        // Disable libxml errors and allow to fetch error information as needed
        libxml_use_internal_errors(true);

        if ($isFile) {
            // Try to load the XML file
            $xml = simplexml_load_file($data, 'MXMLElement');
        }
        else {
            // Try to load the XML string
            $xml = simplexml_load_string($data, 'MXMLElement');
        }

        if (empty($xml)) {
            // There was an error
            MError::raiseWarning(100, MText::_('MLIB_UTIL_ERROR_XML_LOAD'));

            if ($isFile) {
                MError::raiseWarning(100, $data);
            }

            foreach (libxml_get_errors() as $error) {
                MError::raiseWarning(100, 'XML: ' . $error->message);
            }
        }

        return $xml;
    }

    public static function getEditor($editor = null) {
        mimport('framework.html.editor');

        return new MEditor();
    }

    public static function getUri($uri = 'SERVER') {
        mimport('framework.environment.uri');

        return MUri::getInstance($uri);
    }

    public static function getDate($time = 'now', $tzOffset = null) {
        mimport('framework.utilities.date');
        static $classname;
        static $mainLocale;

        $language = self::getLanguage();
        $locale = $language->getTag();

        if (!isset($classname) || $locale != $mainLocale) {
            //Store the locale for future reference
            $mainLocale = $locale;

            if ($mainLocale !== false) {
                $classname = str_replace('-', '_', $mainLocale) . 'Date';

                if (!class_exists($classname)) {
                    //The class does not exist, default to MDate
                    $classname = 'MDate';
                }
            }
            else {
                //No tag, so default to MDate
                $classname = 'MDate';
            }
        }

        $key = $time . '-' . ($tzOffset instanceof DateTimeZone ? $tzOffset->getName() : (string)$tzOffset);

        if (!isset(self::$dates[$classname][$key])) {
            self::$dates[$classname][$key] = new $classname($time, $tzOffset);
        }

        $date = clone self::$dates[$classname][$key];

        return $date;
    }

    protected static function createConfig($file, $type = 'PHP', $namespace = '') {
        if (is_file($file)) {
            include_once $file;
        }

        // Create the registry with a default namespace of config
        $registry = new MRegistry;

        // Sanitize the namespace.
        $namespace = ucfirst((string)preg_replace('/[^A-Z_]/i', '', $namespace));

        // Build the config name.
        $name = 'MConfig' . $namespace;

        // Handle the PHP configuration type.
        if ($type == 'PHP' && class_exists($name)) {
            // Create the MConfig object
            $config = new $name;

            // Load the configuration values into the registry
            $registry->loadObject($config);
        }

        return $registry;
    }

    protected static function createSession($options = array()) {
        // Get the editor configuration setting
        $conf = self::getConfig();
        $handler = $conf->get('session_handler', 'none');

        // Config time is in minutes
        //$options['id']      = 'miwisoft';
        $options['name']    = '_miwisoft';
        $options['expire']  = ($conf->get('lifetime')) ? $conf->get('lifetime') * 60 : 900;

        $session = MSession::getInstance($handler, $options);
        if ($session->getState() == 'expired') {
            $session->restart();
        }

        return $session;
    }

    protected static function _createDbo() {
        MLog::add(__METHOD__ . '() is deprecated.', MLog::WARNING, 'deprecated');

        return self::createDbo();
    }

    protected static function createDbo() {
        mimport('framework.database.table');
		
        $conf = self::getConfig();

        $host = $conf->get('host');
        $user = $conf->get('user');
        $password = $conf->get('password');
        $database = $conf->get('db');
        $prefix = $conf->get('dbprefix');
        $driver = $conf->get('dbtype');
        $debug = $conf->get('debug');

        $options = array('driver' => $driver, 'host' => $host, 'user' => $user, 'password' => $password, 'database' => $database, 'prefix' => $prefix);

        $db = MDatabase::getInstance($options);

        if ($db instanceof Exception) {
            if (!headers_sent()) {
                header('HTTP/1.1 500 Internal Server Error');
            }
            mexit('Database Error: ' . (string)$db);
        }

        if ($db->getErrorNum() > 0) {
            die(sprintf('Database connection error (%d): %s', $db->getErrorNum(), $db->getErrorMsg()));
        }

        $db->setDebug($debug);

        return $db;
    }

    protected static function createMailer() {
        MLoader::register('MMail', MPATH_MIWI .'/proxy/mail/mail.php');
	    $mail = new MMail();
        return $mail;
    }

    protected static function createLanguage() {
        $locale = apply_filters( 'plugin_locale', get_locale()); //$locale = $conf->get('language');
        $debug = false; // $debug = $conf->get('debug_lang');
        $locale = str_replace('en_US', 'en-GB', $locale);
        $locale = str_replace('_', '-', $locale);
        $lang = MLanguage::getInstance($locale, $debug);

        return $lang;
    }

    protected static function createDocument() {
        $lang = self::getLanguage();

        // @deprecated 12.1 This will be removed in the next version
        $raw = MRequest::getBool('no_html');
        $type = MRequest::getWord('format', $raw ? 'raw' : 'html');

        $attributes = array('charset' => 'utf-8', 'lineend' => 'unix', 'tab' => '  ', 'language' => $lang->getTag(),
            'direction' => $lang->isRTL() ? 'rtl' : 'ltr');

        return MDocument::getInstance($type, $attributes);
    }

    public static function getStream($use_prefix = true, $use_network = true, $ua = null, $uamask = false) {
        mimport('framework.filesystem.stream');

        // Setup the context;
        $context = array();
        $version = new MVersion;
        // set the UA for HTTP and overwrite for FTP
        $context['http']['user_agent'] = $version->getUserAgent($ua, $uamask);
        $context['ftp']['overwrite'] = true;

        if ($use_prefix) {
            $FTPOptions = MClientHelper::getCredentials('ftp');
            $SCPOptions = MClientHelper::getCredentials('scp');

            if ($FTPOptions['enabled'] == 1 && $use_network) {
                $prefix = 'ftp://' . $FTPOptions['user'] . ':' . $FTPOptions['pass'] . '@' . $FTPOptions['host'];
                $prefix .= $FTPOptions['port'] ? ':' . $FTPOptions['port'] : '';
                $prefix .= $FTPOptions['root'];
            }
            elseif ($SCPOptions['enabled'] == 1 && $use_network) {
                $prefix = 'ssh2.sftp://' . $SCPOptions['user'] . ':' . $SCPOptions['pass'] . '@' . $SCPOptions['host'];
                $prefix .= $SCPOptions['port'] ? ':' . $SCPOptions['port'] : '';
                $prefix .= $SCPOptions['root'];
            }
            else {
                $prefix = MPATH_ROOT . '/';
            }

            $retval = new MStream($prefix, MPATH_ROOT, $context);
        }
        else {
            $retval = new MStream('', '', $context);
        }

        return $retval;
    }
}