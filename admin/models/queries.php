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
defined('MIWI') or die( 'Restricted access' );

class MiwosqlModelQueries extends MiwosqlModel {

	var $_query = null;
	var $_data = null;
	var $_total = null;
	var $_pagination = null;

	function __construct() {
		parent::__construct();

		$this->mainframe = MFactory::getApplication();
		$this->option = MRequest::getWord('option');

		// Get the pagination request variables
		$limit		= $this->mainframe->getUserStateFromRequest($this->option.'.queries.limit', 'limit', $this->mainframe->getCfg('list_limit'), 'int');
		$limitstart	= $this->mainframe->getUserStateFromRequest($this->option.'.queries.limitstart', 'limitstart', 0, 'int');

		// In case limit has been changed, adjust limitstart accordingly
		$limitstart = ($limit != 0 ? (floor($limitstart / $limit) * $limit) : 0);

		$this->setState($this->option.'.queries.limit', $limit);
		$this->setState($this->option.'.queries.limitstart', $limitstart);
		
		$this->_buildViewQuery();
	}
	
	function getData() {
		if (empty($this->_data)) {
			$this->_data = $this->_getList($this->_query, $this->getState('limitstart'), $this->getState('limit'));
		}

		return $this->_data;
	}
	
	function getTotal()	{
		if (empty($this->_total)) {
			$this->_total = $this->_getListCount($this->_query);
		}

		return $this->_total;
	}

	function getPagination() {
		if (empty($this->_pagination)) {
			mimport('framework.html.pagination');
			$this->_pagination = new MPagination($this->getTotal(), $this->getState($this->option.'.queries.limitstart'), $this->getState($this->option.'.queries.limit'));
		}

		return $this->_pagination;
	}
	
	function _buildViewQuery() {
		if (empty($this->_query)) {
			$where		= $this->_buildViewWhere();
			$orderby	= $this->_buildViewOrderBy();
			
			$this->_query = "SELECT * FROM #__miwosql_queries". $where . $orderby;
		}

		return $this->_query;
	}
	
	function _buildViewWhere() {
		$db	= MFactory::getDBO();
		
		$search	= $this->mainframe->getUserStateFromRequest($this->option.'.queries.search', 'search', '', 'string');
		$search	= MString::strtolower($search);

		$where = array();

		if ($search) {
			$where[] = 'LOWER(title) LIKE '.$db->Quote('%'.$db->getEscaped($search, true).'%', false);
		}
		
		$where = (count($where) ? ' WHERE '. implode(' AND ', $where) : '');

		return $where;
	}

    function _buildViewOrderBy()	{
        $filter_order		= $this->mainframe->getUserStateFromRequest($this->option.'.queries.filter_order',		'filter_order',		'title',	'string');
        $filter_order_Dir	= $this->mainframe->getUserStateFromRequest($this->option.'.queries.filter_order_Dir',	'filter_order_Dir',	'',			'word');

        $orderby = ' ORDER BY '. $filter_order .' '. $filter_order_Dir;

        return $orderby;
    }

    function getQueryData() {
        $query = MRequest::getString('ja_query', '', 'get');
        if (!empty($query)) {
            $data = new stdClass();
            $data->id = 0;
            $data->title = '';
            $data->query = base64_decode($query);
        }
        else {
            $cid = MRequest::getVar('cid', array(0), 'method', 'array');
            $id = $cid[0];

            $data = MTable::getInstance('Query', 'Table');
            $data->load($id);

            $data->query = base64_decode($data->query);
        }

        return $data;
    }

    function saveQuery($post) {
        $row = MTable::getInstance('Query', 'Table');

        // Bind the form fields to the web link table
        if (!$row->bind($post)) {
            $this->setError($this->_db->getErrorMsg());
            return false;
        }

        // Make sure the web link table is valid
        if (!$row->check()) {
            $this->setError($this->_db->getErrorMsg());
            return false;
        }

        // Store the web link table to the database
        if (!$row->store()) {
            $this->setError($this->_db->getErrorMsg());
            return false;
        }

        return true;

        return;
    }
}