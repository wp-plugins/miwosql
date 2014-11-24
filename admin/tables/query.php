<?php
/**
 * @version		1.0.0
 * @package		MiwoSQL
 * @subpackage	MiwoSQL
 * @copyright	2009-2012 Mijosoft LLC, www.mijosoft.com
 * @license		GNU/GPL http://www.gnu.org/copyleft/gpl.html
* @license		GNU/GPL based on AceSQL www.joomace.net
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