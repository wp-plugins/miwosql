<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/
defined('MIWI') or die('MIWI');

class MDatabaseExporterMySQL {

	protected $cache = array();
	protected $db = null;
	protected $from = array();
	protected $asFormat = 'xml';
	protected $options = null;

	public function __construct() {
		$this->options = new MObject;

		$this->cache = array('columns' => array(), 'keys' => array());

		// Set up the class defaults:

		// Export with only structure
		$this->withStructure();

		// Export as xml.
		$this->asXml();

	}

	public function __toString() {
		// Check everything is ok to run first.
		$this->check();

		$buffer = '';

		// Get the format.
		switch ($this->asFormat) {
			case 'xml':
			default:
				$buffer = $this->buildXml();
				break;
		}

		return $buffer;
	}

	public function asXml() {
		$this->asFormat = 'xml';

		return $this;
	}

	protected function buildXml() {
		$buffer = array();

		$buffer[] = '<?xml version="1.0"?>';
		$buffer[] = '<mysqldump xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">';
		$buffer[] = ' <database name="">';

		$buffer = array_merge($buffer, $this->buildXmlStructure());

		$buffer[] = ' </database>';
		$buffer[] = '</mysqldump>';

		return implode("\n", $buffer);
	}

	protected function buildXmlStructure() {
		$buffer = array();

		foreach ($this->from as $table) {
			// Replace the magic prefix if found.
			$table = $this->getGenericTableName($table);

			// Get the details columns information.
			$fields = $this->db->getTableColumns($table);
			$keys = $this->db->getTableKeys($table);

			$buffer[] = '  <table_structure name="' . $table . '">';

			foreach ($fields as $field) {
				$buffer[] = '   <field Field="' . $field->Field . '"' . ' Type="' . $field->Type . '"' . ' Null="' . $field->Null . '"' . ' Key="' .
					$field->Key . '"' . (isset($field->Default) ? ' Default="' . $field->Default . '"' : '') . ' Extra="' . $field->Extra . '"' .
					' />';
			}

			foreach ($keys as $key) {
				$buffer[] = '   <key Table="' . $table . '"' . ' Non_unique="' . $key->Non_unique . '"' . ' Key_name="' . $key->Key_name . '"' .
					' Seq_in_index="' . $key->Seq_in_index . '"' . ' Column_name="' . $key->Column_name . '"' . ' Collation="' . $key->Collation . '"' .
					' Null="' . $key->Null . '"' . ' Index_type="' . $key->Index_type . '"' . ' Comment="' . htmlspecialchars($key->Comment) . '"' .
					' />';
			}

			$buffer[] = '  </table_structure>';
		}

		return $buffer;
	}

	public function check() {
		// Check if the db connector has been set.
		if (!($this->db instanceof MDatabaseMySQL)) {
			throw new Exception('MPLATFORM_ERROR_DATABASE_CONNECTOR_WRONG_TYPE');
		}

		// Check if the tables have been specified.
		if (empty($this->from)) {
			throw new Exception('MPLATFORM_ERROR_NO_TABLES_SPECIFIED');
		}

		return $this;
	}

	protected function getGenericTableName($table) {
		// TODO Incorporate into parent class and use $this.
		$prefix = $this->db->getPrefix();

		// Replace the magic prefix if found.
		$table = preg_replace("|^$prefix|", '#__', $table);

		return $table;
	}

	public function from($from) {
		if (is_string($from)) {
			$this->from = array($from);
		}
		elseif (is_array($from)) {
			$this->from = $from;
		}
		else {
			throw new Exception('MPLATFORM_ERROR_INPUT_REQUIRES_STRING_OR_ARRAY');
		}

		return $this;
	}

	public function setDbo(MDatabaseMySQL $db) {
		$this->db = $db;

		return $this;
	}

	public function withStructure($setting = true) {
		$this->options->set('with-structure', (boolean) $setting);

		return $this;
	}
}