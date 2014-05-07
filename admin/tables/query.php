<?php
/**
 * @version		1.0.0
 * @package		MiwoSQL
 * @subpackage	MiwoSQL
 * @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
 * @license		GNU General Public License version 2 or later
*
 */

// Check to ensure this file is included in Moomla!
defined('MIWI') or die('Restricted access');

class TableQuery extends MTable {

	public $id					= 0;
	public $title				= '';
	public $query				= '';

	public function __construct(&$db) {
		parent::__construct('#__miwosql_queries', 'id', $db);
	}
}