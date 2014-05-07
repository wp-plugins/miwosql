<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

mimport('framework.utilities.arrayhelper');
MLoader::register('MRegistryFormat', dirname(__FILE__) . '/format.php');

class MRegistry {

	protected $data;
	protected static $instances = array ();

	public function __construct($data = null) {
		// Instantiate the internal data object.
		$this->data = new stdClass();
		
		// Optionally load supplied data.
		if (is_array($data) || is_object($data)) {
			$this->bindData($this->data, $data);
		}
		elseif (! empty($data) && is_string($data)) {
			$this->loadString($data);
		}
	}

	public function __clone() {
		$this->data = unserialize(serialize($this->data));
	}

	public function __toString() {
		return $this->toString();
	}

	public function def($key, $default = '') {
		$value = $this->get($key, (string) $default);
		$this->set($key, $value);
		return $value;
	}

	public function exists($path) {
		// Explode the registry path into an array
		if ($nodes = explode('.', $path)) {
			// Initialize the current node to be the registry root.
			$node = $this->data;
			
			// Traverse the registry to find the correct node for the result.
			for ($i = 0, $n = count($nodes); $i < $n; $i ++) {
				if (isset($node->$nodes[$i])) {
					$node = $node->$nodes[$i];
				}
				else {
					break;
				}
				
				if ($i + 1 == $n) {
					return true;
				}
			}
		}
		
		return false;
	}

	public function get($path, $default = null) {
		// Initialise variables.
		$result = $default;
		
		if (! strpos($path, '.')) {
			return (isset($this->data->$path) && $this->data->$path !== null && $this->data->$path !== '') ? $this->data->$path : $default;
		}
		// Explode the registry path into an array
		$nodes = explode('.', $path);
		
		// Initialize the current node to be the registry root.
		$node = $this->data;
		$found = false;
		// Traverse the registry to find the correct node for the result.
		foreach ($nodes as $n) {
			if (isset($node->$n)) {
				$node = $node->$n;
				$found = true;
			}
			else {
				$found = false;
				break;
			}
		}
		if ($found && $node !== null && $node !== '') {
			$result = $node;
		}
		
		return $result;
	}

	public static function getInstance($id) {
		if (empty(self::$instances[$id])) {
			self::$instances[$id] = new MRegistry();
		}
		
		return self::$instances[$id];
	}

	public function loadArray($array) {
		$this->bindData($this->data, $array);
		
		return true;
	}

	public function loadObject($object) {
		$this->bindData($this->data, $object);
		
		return true;
	}

	public function loadFile($file, $format = 'JSON', $options = array()) {
		// Get the contents of the file
		mimport('framework.filesystem.file');
		$data = MFile::read($file);
		
		return $this->loadString($data, $format, $options);
	}

	public function loadString($data, $format = 'JSON', $options = array()) {
		// Load a string into the given namespace [or default namespace if not given]
		$handler = MRegistryFormat::getInstance($format);
		
		$obj = $handler->stringToObject($data, $options);
		$this->loadObject($obj);
		
		return true;
	}

	public function merge(&$source) {
		if ($source instanceof MRegistry) {
			// Load the variables into the registry's default namespace.
			foreach ($source->toArray() as $k => $v) {
				if (($v !== null) && ($v !== '')) {
					$this->data->$k = $v;
				}
			}
			return true;
		}
		return false;
	}

	public function set($path, $value) {
		$result = null;
		
		// Explode the registry path into an array
		if ($nodes = explode('.', $path)) {
			// Initialize the current node to be the registry root.
			$node = $this->data;
			
			// Traverse the registry to find the correct node for the result.
			for ($i = 0, $n = count($nodes) - 1; $i < $n; $i ++) {
				if (! isset($node->$nodes[$i]) && ($i != $n)) {
					$node->$nodes[$i] = new stdClass();
				}
				$node = $node->$nodes[$i];
			}
			
			// Get the old value if exists so we can return it
			$result = $node->$nodes[$i] = $value;
		}
		
		return $result;
	}

	public function toArray() {
		return (array) $this->asArray($this->data);
	}

	public function toObject() {
		return $this->data;
	}

	public function toString($format = 'JSON', $options = array()) {
		// Return a namespace in a given format
		$handler = MRegistryFormat::getInstance($format);
		
		return $handler->objectToString($this->data, $options);
	}

	protected function bindData(&$parent, $data) {
		// Ensure the input data is an array.
		if (is_object($data)) {
			$data = get_object_vars($data);
		}
		else {
			$data = (array) $data;
		}
		
		foreach ($data as $k => $v) {
			if ((is_array($v) && MArrayHelper::isAssociative($v)) || is_object($v)) {
				$parent->$k = new stdClass();
				$this->bindData($parent->$k, $v);
			}
			else {
				$parent->$k = $v;
			}
		}
	}

	protected function asArray($data) {
		$array = array ();
		
		foreach (get_object_vars((object) $data) as $k => $v) {
			if (is_object($v)) {
				$array[$k] = $this->asArray($v);
			}
			else {
				$array[$k] = $v;
			}
		}
		
		return $array;
	}
	
	public function loadXML($data, $namespace = null) {
		// Deprecation warning.
		MLog::add('MRegistry::loadXML() is deprecated.', MLog::WARNING, 'deprecated');
		
		return $this->loadString($data, 'XML');
	}

	public function loadINI($data, $namespace = null, $options = array()) {
		// Deprecation warning.
		MLog::add('MRegistry::loadINI() is deprecated.', MLog::WARNING, 'deprecated');
		
		return $this->loadString($data, 'INI', $options);
	}

	public function loadJSON($data) {
		// Deprecation warning.
		MLog::add('MRegistry::loadJSON() is deprecated.', MLog::WARNING, 'deprecated');
		
		return $this->loadString($data, 'JSON');
	}

	public function makeNameSpace($namespace) {
		// Deprecation warning.
		MLog::add('MRegistry::makeNameSpace() is deprecated.', MLog::WARNING, 'deprecated');
		
		//$this->_registry[$namespace] = array('data' => new stdClass());
		return true;
	}

	public function getNameSpaces() {
		// Deprecation warning.
		MLog::add('MRegistry::getNameSpaces() is deprecated.', MLog::WARNING, 'deprecated');
		
		//return array_keys($this->_registry);
		return array ();
	}

	public function getValue($path, $default = null) {
		// Deprecation warning.
		MLog::add('MRegistry::getValue() is deprecated. Use get instead.', MLog::WARNING, 'deprecated');
		
		$parts = explode('.', $path);
		if (count($parts) > 1) {
			unset($parts[0]);
			$path = implode('.', $parts);
		}
		return $this->get($path, $default);
	}

	public function setValue($path, $value) {
		// Deprecation warning.
		MLog::add('MRegistry::setValue() is deprecated. Use set instead.', MLog::WARNING, 'deprecated');
		
		$parts = explode('.', $path);
		if (count($parts) > 1) {
			unset($parts[0]);
			$path = implode('.', $parts);
		}
		return $this->set($path, $value);
	}

	public function loadSetupFile() {
		// Deprecation warning.
		MLog::add('MRegistry::loadSetupFile() is deprecated.', MLog::WARNING, 'deprecated');
		
		return true;
	}
}