<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MPathway extends MObject {

	protected $_pathway = null;
	protected $_count = 0;
	protected static $instances = array();

	public function __construct($options = array()) {
		//Initialise the array
		$this->_pathway = array();
	}

	public static function getInstance($client, $options = array()) {
		if (empty(self::$instances[$client])) {
			// Create a MPathway object
			$classname = 'MPathway';
			$instance = new $classname($options);

			self::$instances[$client] = $instance;
		}

		//return $this;
		return self::$instances[$client];
	}

	public function getPathway() {
		$pw = $this->_pathway;

		// Use array_values to reset the array keys numerically
		return array_values($pw);
	}

	public function setPathway($pathway) {
		$oldPathway = $this->_pathway;
		$pathway = (array) $pathway;

		// Set the new pathway.
		$this->_pathway = array_values($pathway);

		return array_values($oldPathway);
	}

	public function getPathwayNames() {
		// Initialise variables.
		$names = array(null);

		// Build the names array using just the names of each pathway item
		foreach ($this->_pathway as $item) {
			$names[] = $item->name;
		}

		//Use array_values to reset the array keys numerically
		return array_values($names);
	}

	public function addItem($name, $link = '') {
		// Initialize variables
		$ret = false;

		if ($this->_pathway[] = $this->_makeItem($name, $link)) {
			$ret = true;
			$this->_count++;
		}

		return $ret;
	}

	public function setItemName($id, $name) {
		// Initialize variables
		$ret = false;

		if (isset($this->_pathway[$id])) {
			$this->_pathway[$id]->name = $name;
			$ret = true;
		}

		return $ret;
	}

	protected function _makeItem($name, $link) {
		$item = new stdClass;
		$item->name = html_entity_decode($name, ENT_COMPAT, 'UTF-8');
		$item->link = $link;

		return $item;
	}
}
