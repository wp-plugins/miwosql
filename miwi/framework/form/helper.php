<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

mimport('framework.filesystem.path');

class MFormHelper {

    protected static $paths;

    protected static $entities = array();

    public static function loadFieldType($type, $new = true) {
        return self::loadType('field', $type, $new);
    }

    public static function loadRuleType($type, $new = true) {
        return self::loadType('rule', $type, $new);
    }

    protected static function loadType($entity, $type, $new = true) {
        // Reference to an array with current entity's type instances
        $types = & self::$entities[$entity];

        // Initialize variables.
        $key   = md5($type);
        $class = '';

        // Return an entity object if it already exists and we don't need a new one.
        if (isset($types[$key]) && $new === false) {
            return $types[$key];
        }

        if (($class = self::loadClass($entity, $type)) !== false) {
            // Instantiate a new type object.
            $types[$key] = new $class;

            return $types[$key];
        }
        else {
            return false;
        }
    }

    public static function loadFieldClass($type) {
        return self::loadClass('field', $type);
    }

    public static function loadRuleClass($type) {
        return self::loadClass('rule', $type);
    }

    protected static function loadClass($entity, $type) {
        if (strpos($type, '.')) {
            list($prefix, $type) = explode('.', $type);
        }
        else {
            $prefix = 'M';
        }

        $class = MString::ucfirst($prefix, '_') . 'Form' . MString::ucfirst($entity, '_') . MString::ucfirst($type, '_');

        if (class_exists($class)) {
            return $class;
        }

        // Get the field search path array.
        $paths = MFormHelper::addPath($entity);

        // If the type is complex, add the base type to the paths.
        if ($pos = strpos($type, '_')) {

            // Add the complex type prefix to the paths.
            for ($i = 0, $n = count($paths); $i < $n; $i++) {
                // Derive the new path.
                $path = $paths[$i] . '/' . strtolower(substr($type, 0, $pos));

                // If the path does not exist, add it.
                if (!in_array($path, $paths)) {
                    $paths[] = $path;
                }
            }
            // Break off the end of the complex type.
            $type = substr($type, $pos + 1);
        }

        // Try to find the class file.
        $type = strtolower($type) . '.php';
        foreach ($paths as $path) {
            if ($file = MPath::find($path, $type)) {
                require_once $file;
                if (class_exists($class)) {
                    break;
                }
            }
        }

        // Check for all if the class exists.
        return class_exists($class) ? $class : false;
    }

    public static function addFieldPath($new = null) {
        return self::addPath('field', $new);
    }

    public static function addFormPath($new = null) {
        return self::addPath('form', $new);
    }

    public static function addRulePath($new = null) {
        return self::addPath('rule', $new);
    }

    protected static function addPath($entity, $new = null) {
        // Reference to an array with paths for current entity
        $paths = & self::$paths[$entity];

        // Add the default entity's search path if not set.
        if (empty($paths)) {
            // While we support limited number of entities (form, field and rule)
            // we can do this simple pluralisation:
            $entity_plural = $entity . 's';
            // But when someday we would want to support more entities, then we should consider adding
            // an inflector class to "libraries/joomla/utilities" and use it here (or somebody can use a real inflector in his subclass).
            // see also: pluralization snippet by Paul Osman in MControllerForm's constructor.
            $paths[] = dirname(__FILE__) . '/' . $entity_plural;
        }

        // Force the new path(s) to an array.
        settype($new, 'array');

        // Add the new paths to the stack if not already there.
        foreach ($new as $path) {
            if (!in_array($path, $paths)) {
                array_unshift($paths, trim($path));
            }
        }

        return $paths;
    }
}
