<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MMenu extends MObject {

	protected $_items = array();
	protected $_default = array();
	protected $_active = 0;
	protected static $instances = array();

	public function __construct($options = array()) {
		// Load the menu items
		$this->load();

		foreach ($this->_items as $item) {
			if ($item->home) {
				$this->_default[trim($item->language)] = $item->id;
			}

			// Decode the item params
			$result = new MRegistry;
			$result->loadString($item->params);
			$item->params = $result;
		}
	}

	public static function getInstance($client, $options = array()) {
		if (empty(self::$instances[$client])) {
			// Create a MMenu object
			$classname = 'MMenu';
			$instance = new $classname($options);

			self::$instances[$client] = $instance;
		}

		//return $this;
		return self::$instances[$client];
	}

	public function getItem($id) {
		$result = null;
		if (isset($this->_items[$id])) {
			$result = &$this->_items[$id];
		}

		return $result;
	}

	public function setDefault($id, $language = '') {
		if (isset($this->_items[$id])) {
			$this->_default[$language] = $id;
			return true;
		}

		return false;
	}

	public function getDefault($language = '*') {
		if (array_key_exists($language, $this->_default)) {
			return $this->_items[$this->_default[$language]];
		}
		elseif (array_key_exists('*', $this->_default)) {
			return $this->_items[$this->_default['*']];
		}
		else {
			return 0;
		}
	}

	public function setActive($id) {
		if (isset($this->_items[$id])) {
			$this->_active = $id;
			$result = &$this->_items[$id];
			
			return $result;
		}

		return null;
	}

	public function getActive() {
		if ($this->_active) {
			$item = &$this->_items[$this->_active];
			
			return $item;
		}

		return null;
	}

	public function getItems($attributes, $values, $firstonly = false) {
		$items = array();
		$attributes = (array) $attributes;
		$values = (array) $values;

		foreach ($this->_items as $item) {
			if (!is_object($item)) {
				continue;
			}

			$test = true;
			for ($i = 0, $count = count($attributes); $i < $count; $i++) {
				if (is_array($values[$i])) {
					if (!in_array($item->$attributes[$i], $values[$i])) {
						$test = false;
						break;
					}
				}
				else {
					if ($item->$attributes[$i] != $values[$i]) {
						$test = false;
						break;
					}
				}
			}

			if ($test) {
				if ($firstonly) {
					return $item;
				}

				$items[] = $item;
			}
		}

		return $items;
	}

	public function getParams($id) {
		if ($menu = $this->getItem($id)) {
			return $menu->params;
		}
		else {
			return new MRegistry;
		}
	}

	public function getMenu() {
		return $this->_items;
	}

	public function authorise($id) {
		$menu = $this->getItem($id);
		$user = MFactory::getUser();

		if ($menu) {
			return in_array((int) $menu->access, $user->getAuthorisedViewLevels());
		}
		else {
			return true;
		}
	}

	public function load() {
		return array();
	}
}