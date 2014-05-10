<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

mimport('framework.database.table');

class MTableUser extends MTable {

	public $groups;

	public function __construct(&$db) {
		parent::__construct('#__users', 'id', $db);

		// Initialise.
		$this->id = 0;
		$this->sendEmail = 0;
	}

	public function load($userId = null, $reset = true) {
		// Get the id to load.
		if ($userId !== null)
		{
			$this->id = $userId;
		}
		else
		{
			$userId = $this->id;
		}

		// Check for a valid id to load.
		if ($userId === null)
		{
			return false;
		}

		// Reset the table.
		$this->reset();

		// Load the user data.
		$query = $this->_db->getQuery(true);
		$query->select('*');
		$query->from($this->_db->quoteName('#__users'));
		$query->where($this->_db->quoteName('ID') . ' = ' . (int) $userId);
		$this->_db->setQuery($query);
		$data = (array) $this->_db->loadAssoc();

		// Check for an error message.
		if ($this->_db->getErrorNum())
		{
			$this->setError($this->_db->getErrorMsg());
			return false;
		}

		if (!count($data))
		{
			return false;
		}
		// Bind the data to the table.
		$return = $this->bind($data);

		return $return;
	}

	public function bind($array, $ignore = '') {
		if (key_exists('params', $array) && is_array($array['params']))
		{
			$registry = new MRegistry;
			$registry->loadArray($array['params']);
			$array['params'] = (string) $registry;
		}

		// Attempt to bind the data.
		$return = parent::bind($array, $ignore);

		// Load the real group data based on the bound ids.
		if ($return && !empty($this->groups))
		{
			// Set the group ids.
			MArrayHelper::toInteger($this->groups);

			// Get the titles for the user groups.
			$query = $this->_db->getQuery(true);
			$query->select($this->_db->quoteName('id'));
			$query->select($this->_db->quoteName('title'));
			$query->from($this->_db->quoteName('#__usergroups'));
			$query->where($this->_db->quoteName('id') . ' = ' . implode(' OR ' . $this->_db->quoteName('id') . ' = ', $this->groups));
			$this->_db->setQuery($query);
			// Set the titles for the user groups.
			$this->groups = $this->_db->loadAssocList('id', 'id');

			// Check for a database error.
			if ($this->_db->getErrorNum())
			{
				$this->setError($this->_db->getErrorMsg());
				return false;
			}
		}

		return $return;
	}

}
