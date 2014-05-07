<?php

/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

mimport('framework.log.logger');

MLoader::register('LogException', MPATH_PLATFORM . '/joomla/log/logexception.php');

MLoader::discover('MLogger', dirname(__FILE__) . '/loggers');

// @deprecated  12.1
mimport('framework.filesystem.path');

class MLog {

    const ALL = 30719;
    const EMERGENCY = 1;
    const ALERT = 2;
    const CRITICAL = 4;
    const ERROR = 8;
    const WARNING = 16;
    const NOTICE = 32;
    const INFO = 64;
    const DEBUG = 128;

    protected static $instance;
    public static $legacy = array();
    protected $configurations = array();
    protected $loggers = array();
    protected $lookup = array();
	
    protected function __construct() {
    }

    public static function add($entry, $priority = MLog::INFO, $category = '', $date = null) {
        // Automatically instantiate the singleton object if not already done.
        if (empty(self::$instance)) {
            self::setInstance(new MLog);
        }

        // If the entry object isn't a MLogEntry object let's make one.
        if (!($entry instanceof MLogEntry)) {
            $entry = new MLogEntry((string)$entry, $priority, $category, $date);
        }

        self::$instance->addLogEntry($entry);
    }

    public static function addLogger(array $options, $priorities = MLog::ALL, $categories = array()) {
        // Automatically instantiate the singleton object if not already done.
        if (empty(self::$instance)) {
            self::setInstance(new MLog);
        }

        // The default logger is the formatted text log file.
        if (empty($options['logger'])) {
            $options['logger'] = 'formattedtext';
        }
        $options['logger'] = strtolower($options['logger']);

        // Generate a unique signature for the MLog instance based on its options.
        $signature = md5(serialize($options));

        // Register the configuration if it doesn't exist.
        if (empty(self::$instance->configurations[$signature])) {
            self::$instance->configurations[$signature] = $options;
        }

        self::$instance->lookup[$signature] = (object)array(
            'priorities' => $priorities,
            'categories' => array_map('strtolower', (array)$categories));
    }

    public static function getInstance($file = 'error.php', $options = null, $path = null) {
        // Deprecation warning.
        MLog::add('MLog::getInstance() is deprecated.  See MLog::addLogger().', MLog::WARNING, 'deprecated');

        // Get the system configuration object.
        $config = MFactory::getConfig();

        // Set default path if not set and sanitize it.
        if (!$path) {
            $path = $config->get('log_path');
        }

        // If no options were explicitly set use the default from configuration.
        if (empty($options)) {
            $options = (array)$config->get('log_options');
        }

        // Fix up the options so that we use the w3c format.
        $options['text_entry_format'] = empty($options['format']) ? null : $options['format'];
        $options['text_file']         = $file;
        $options['text_file_path']    = $path;
        $options['logger']            = 'w3c';

        // Generate a unique signature for the MLog instance based on its options.
        $signature = md5(serialize($options));

        // Only create the object if not already created.
        if (empty(self::$legacy[$signature])) {
            self::$legacy[$signature] = new MLog;

            // Register the configuration.
            self::$legacy[$signature]->configurations[$signature] = $options;

            // Setup the lookup to catch all.
            self::$legacy[$signature]->lookup[$signature] = (object)array('priorities' => MLog::ALL, 'categories' => array());
        }

        return self::$legacy[$signature];
    }

    public static function setInstance($instance) {
        if (($instance instanceof MLog) || $instance === null) {
            self::$instance = & $instance;
        }
    }

    public function addEntry($entry) {
        // Deprecation warning.
        MLog::add('MLog::addEntry() is deprecated, use MLog::add() instead.', MLog::WARNING, 'deprecated');

        // Easiest case is we already have a MLogEntry object to add.
        if ($entry instanceof MLogEntry) {
            return $this->addLogEntry($entry);
        }
        // We have either an object or array that needs to be converted to a MLogEntry.
        elseif (is_array($entry) || is_object($entry)) {
            $tmp = new MLogEntry('');
            foreach ((array)$entry as $k => $v) {
                switch ($k) {
                    case 'c-ip':
                        $tmp->clientIP = $v;
                        break;
                    case 'status':
                        $tmp->category = $v;
                        break;
                    case 'level':
                        $tmp->priority = $v;
                        break;
                    case 'comment':
                        $tmp->message = $v;
                        break;
                    default:
                        $tmp->$k = $v;
                        break;
                }
            }
        }
        // Unrecognized type.
        else {
            return false;
        }

        return $this->addLogEntry($tmp);
    }

    protected function addLogEntry(MLogEntry $entry) {
        // Find all the appropriate loggers based on priority and category for the entry.
        $loggers = $this->findLoggers($entry->priority, $entry->category);

        foreach ((array)$loggers as $signature) {
            // Attempt to instantiate the logger object if it doesn't already exist.
            if (empty($this->loggers[$signature])) {

                $class = 'MLogger' . ucfirst($this->configurations[$signature]['logger']);
                if (class_exists($class)) {
                    $this->loggers[$signature] = new $class($this->configurations[$signature]);
                }
                else {
                    throw new LogException(MText::_('Unable to create a MLogger instance: '));
                }
            }

            // Add the entry to the logger.
            $this->loggers[$signature]->addEntry($entry);
        }
    }

    protected function findLoggers($priority, $category) {
        // Initialize variables.
        $loggers = array();

        // Sanitize inputs.
        $priority = (int)$priority;
        $category = strtolower($category);

        // Let's go iterate over the loggers and get all the ones we need.
        foreach ((array)$this->lookup as $signature => $rules) {
            // Check to make sure the priority matches the logger.
            if ($priority & $rules->priorities) {

                // If either there are no set categories (meaning all) or the specific category is set, add this logger.
                if (empty($category) || empty($rules->categories) || in_array($category, $rules->categories)) {
                    $loggers[] = $signature;
                }
            }
        }

        return $loggers;
    }
}