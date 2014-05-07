<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

abstract class MLoader {

    protected static $classes = array();
    protected static $imported = array();
    protected static $prefixes = array();

    public static function discover($classPrefix, $parentPath, $force = true, $recurse = false) {
        try {
            if ($recurse) {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($parentPath),
                    RecursiveIteratorIterator::SELF_FIRST
                );
            }
            else {
                $iterator = new DirectoryIterator($parentPath);
            }

            foreach ($iterator as $file) {
                $fileName = $file->getFilename();

                // Only load for php files.
                // Note: DirectoryIterator::getExtension only available PHP >= 5.3.6
                if ($file->isFile() && substr($fileName, strrpos($fileName, '.') + 1) == 'php') {
                    // Get the class name and full path for each file.
                    $class = strtolower($classPrefix . preg_replace('#\.php$#', '', $fileName));

                    // Register the class with the autoloader if not already registered or the force flag is set.
                    if (empty(self::$classes[$class]) || $force) {
                        self::register($class, $file->getPath() . '/' . $fileName);
                    }
                }
            }
        } catch (UnexpectedValueException $e) {
            // Exception will be thrown if the path is not a directory. Ignore it.
        }
    }

    public static function getClassList() {
        return self::$classes;
    }

    public static function import($key, $base = null) {
        // Only import the library if not already attempted.
        if (!isset(self::$imported[$key])) {
            // Setup some variables.
            $success = false;
            $parts   = explode('.', $key);
            $class   = array_pop($parts);
			$base    = (!empty($base)) ? $base : dirname(__FILE__);
            $path    = str_replace('.', DIRECTORY_SEPARATOR, $key);

            // Handle special case for helper classes.
            if ($class == 'helper') {
                $class = ucfirst(array_pop($parts)) . ucfirst($class);
            }
            // Standard class.
            else {
                $class = ucfirst($class);
            }

            // If we are importing a library from the Miwi namespace set the class to autoload.
            if (strpos($key, 'framework') === 0) {
                // Since we are in the Miwi namespace prepend the classname with M.
                $class = 'M' . $class;

				$path = str_replace('framework' . DIRECTORY_SEPARATOR, '', $path); // remove the "framework" folder
			
				$frame   = $base . '/framework';
				$proxy   = $base . '/proxy';
				
                // Only register the class for autoloading if the file exists.
                if (is_file($proxy . '/' . $path . '.php')) {
                    self::$classes[strtolower($class)] = $proxy . '/' . $path . '.php';
                    $success = true;
                }
                elseif (is_file($frame . '/' . $path . '.php')) {
                    self::$classes[strtolower($class)] = $frame . '/' . $path . '.php';
                    $success = true;
                }
            }
            /*
             * If we are not importing a library from the Miwi namespace directly include the
            * file since we cannot assert the file/folder naming conventions.
            */
            else {
                // If the file exists attempt to include it.
				
                // If the file exists attempt to include it.
				if (is_file($base . '/' . $path . '.php')) {
					$success = (bool) include_once $base . '/' . $path . '.php';
				}
            }

            // Add the import key to the memory cache container.
            self::$imported[$key] = $success;
        }

        return self::$imported[$key];
    }

    public static function load($class) {
        // Sanitize class name.
        $class = strtolower($class);

        // If the class already exists do nothing.
        if (class_exists($class)) {
            return true;
        }

        // If the class is registered include the file.
        if (isset(self::$classes[$class])) {
            include_once self::$classes[$class];

            return true;
        }

        return false;
    }

    public static function register($class, $path, $force = true) {
        // Sanitize class name.
        $class = strtolower($class);

        // Only attempt to register the class if the name and file exist.
        if (!empty($class) && is_file($path)) {
            // Register the class with the autoloader if not already registered or the force flag is set.
            if (empty(self::$classes[$class]) || $force) {
                self::$classes[$class] = $path;
            }
        }
    }

    public static function registerPrefix($prefix, $path, $reset = false) {
        // Verify the library path exists.
        if (!file_exists($path)) {
            throw new RuntimeException('Library path ' . $path . ' cannot be found.', 500);
        }

        // If the prefix is not yet registered or we have an explicit reset flag then set set the path.
        if (!isset(self::$prefixes[$prefix]) || $reset) {
            self::$prefixes[$prefix] = array($path);
        }
        // Otherwise we want to simply add the path to the prefix.
        else {
            self::$prefixes[$prefix][] = $path;
        }
    }

    public static function setup() {
        // Register the base path for Miwi Framework libraries.
        self::registerPrefix('M', MPATH_MIWI);

        // Register the autoloader functions.
        spl_autoload_register(array('MLoader', 'load'));
        spl_autoload_register(array('MLoader', '_autoload'));
    }

    private static function _autoload($class) {
        foreach (self::$prefixes as $prefix => $lookup) {
            if (strpos($class, $prefix) === 0) {
                return self::_load(substr($class, strlen($prefix)), $lookup);
            }
        }
    }

    private static function _load($class, $lookup) {
        // Split the class name into parts separated by camelCase.
        $parts = preg_split('/(?<=[a-z0-9])(?=[A-Z])/x', $class);

        // If there is only one part we want to duplicate that part for generating the path.
        $parts = (count($parts) === 1) ? array($parts[0], $parts[0]) : $parts;

        foreach ($lookup as $base) {
            // Generate the path based on the class name parts.
            $path = $base . '/' . implode('/', array_map('strtolower', $parts)) . '.php';

            // Load the file if it exists.
            if (file_exists($path)) {
                return include $path;
            }
        }
    }
}

function mexit($message = '') {
    wp_die($message);
}

function mimport($path) {
    return MLoader::import($path);
}