<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

mimport('framework.event.dispatcher');
define('MAUTHENTICATE_STATUS_SUCCESS', 1);
define('MAUTHENTICATE_STATUS_CANCEL', 2);
define('MAUTHENTICATE_STATUS_FAILURE', 4);

class MAuthentication extends MObject {

    const STATUS_SUCCESS = 1;

    const STATUS_CANCEL = 2;

    const STATUS_FAILURE = 4;

    const STATUS_EXPIRED = 8;

    const STATUS_DENIED = 16;

    const STATUS_UNKNOWN = 32;

    protected $_observers = array();

    protected $_state = null;

    protected $_methods = array();

    protected static $instance;

    public function __construct() {
        $isLoaded = MPluginHelper::importPlugin('authentication');

        if (!$isLoaded) {
            MError::raiseWarning('SOME_ERROR_CODE', MText::_('MLIB_USER_ERROR_AUTHENTICATION_LIBRARIES'));
        }
    }

    public static function getInstance() {
        if (empty(self::$instance)) {
            self::$instance = new MAuthentication;
        }

        return self::$instance;
    }

    public function getState() {
        return $this->_state;
    }

    public function attach($observer) {
        if (is_array($observer)) {
            if (!isset($observer['handler']) || !isset($observer['event']) || !is_callable($observer['handler'])) {
                return;
            }

            // Make sure we haven't already attached this array as an observer
            foreach ($this->_observers as $check) {
                if (is_array($check) && $check['event'] == $observer['event'] && $check['handler'] == $observer['handler']) {
                    return;
                }
            }

            $this->_observers[] = $observer;
            end($this->_observers);
            $methods = array($observer['event']);
        }
        else {
            if (!($observer instanceof MAuthentication)) {
                return;
            }

            // Make sure we haven't already attached this object as an observer
            $class = get_class($observer);

            foreach ($this->_observers as $check) {
                if ($check instanceof $class) {
                    return;
                }
            }

            $this->_observers[] = $observer;
            $methods = array_diff(get_class_methods($observer), get_class_methods('MPlugin'));
        }

        $key = key($this->_observers);

        foreach ($methods as $method) {
            $method = strtolower($method);

            if (!isset($this->_methods[$method])) {
                $this->_methods[$method] = array();
            }

            $this->_methods[$method][] = $key;
        }
    }

    public function detach($observer) {
        // Initialise variables.
        $retval = false;

        $key = array_search($observer, $this->_observers);

        if ($key !== false) {
            unset($this->_observers[$key]);
            $retval = true;

            foreach ($this->_methods as &$method) {
                $k = array_search($key, $method);

                if ($k !== false) {
                    unset($method[$k]);
                }
            }
        }

        return $retval;
    }

    public function authenticate($credentials, $options = array()) {
        // Get plugins
        $plugins = MPluginHelper::getPlugin('authentication');

        // Create authentication response
        $response = new MAuthenticationResponse;

        foreach ($plugins as $plugin) {
            $className = 'plg' . $plugin->type . $plugin->name;
            if (class_exists($className)) {
                $plugin = new $className($this, (array)$plugin);
            }
            else {
                // Bail here if the plugin can't be created
                MError::raiseWarning(50, MText::sprintf('MLIB_USER_ERROR_AUTHENTICATION_FAILED_LOAD_PLUGIN', $className));
                continue;
            }

            // Try to authenticate
            $plugin->onUserAuthenticate($credentials, $options, $response);

            // If authentication is successful break out of the loop
            if ($response->status === MAuthentication::STATUS_SUCCESS) {
                if (empty($response->type)) {
                    $response->type = isset($plugin->_name) ? $plugin->_name : $plugin->name;
                }
                break;
            }
        }

        if (empty($response->username)) {
            $response->username = $credentials['username'];
        }

        if (empty($response->fullname)) {
            $response->fullname = $credentials['username'];
        }

        if (empty($response->password)) {
            $response->password = $credentials['password'];
        }

        return $response;
    }

    public static function authorise($response, $options = array()) {
        // Get plugins in case they haven't been loaded already
        MPluginHelper::getPlugin('user');
        MPluginHelper::getPlugin('authentication');
        $dispatcher = MDispatcher::getInstance();
        $results = $dispatcher->trigger('onUserAuthorisation', array($response, $options));
        return $results;
    }
}

class MAuthenticationResponse extends MObject {

    public $status = MAuthentication::STATUS_FAILURE;

    public $type = '';

    public $error_message = '';

    public $username = '';

    public $password = '';

    public $email = '';

    public $fullname = '';

    public $birthdate = '';

    public $gender = '';

    public $postcode = '';

    public $country = '';

    public $language = '';

    public $timezone = '';

    public function __construct() {}
}
