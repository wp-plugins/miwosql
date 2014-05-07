<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MDispatcher extends MObject {

    protected $_observers = array();
    protected $_state = null;
    protected $_methods = array();
    protected static $instance = null;

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new MDispatcher;
        }

        return self::$instance;
    }

    public function getState() {
        return $this->_state;
    }

    public function register($event, $handler) {
        // Are we dealing with a class or function type handler?
        if (function_exists($handler)) {
            // Ok, function type event handler... let's attach it.
            $method = array('event' => $event, 'handler' => $handler);
            $this->attach($method);
        }
        elseif (class_exists($handler)) {
            // Ok, class type event handler... let's instantiate and attach it.
            $this->attach(new $handler($this));
        }
        else {
            return MError::raiseWarning('SOME_ERROR_CODE', MText::sprintf('MLIB_EVENT_ERROR_DISPATCHER', $handler));
        }
    }

    public function trigger($event, $args = array()) {
        // Initialise variables.
        $result = array();

        $args = (array)$args;

        $event = strtolower($event);

        // Check if any plugins are attached to the event.
        if (!isset($this->_methods[$event]) || empty($this->_methods[$event])) {
            // No Plugins Associated To Event!
            return $result;
        }
        // Loop through all plugins having a method matching our event
        foreach ($this->_methods[$event] as $key) {
            // Check if the plugin is present.
            if (!isset($this->_observers[$key])) {
                continue;
            }

            // Fire the event for an object based observer.
            if (is_object($this->_observers[$key])) {
                $args['event'] = $event;
                $value = $this->_observers[$key]->update($args);
            }
            // Fire the event for a function based observer.
            elseif (is_array($this->_observers[$key])) {
                $value = call_user_func_array($this->_observers[$key]['handler'], $args);
            }
            if (isset($value)) {
                $result[] = $value;
            }
        }

        return $result;
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
            if (!($observer instanceof MEvent)) {
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
}