<?php
/**
* @version		1.0.0
* @package		MiwoSQL
* @subpackage	MiwoSQL
* @copyright	2009-2012 Mijosoft LLC, www.mijosoft.com
* @license		GNU/GPL http://www.gnu.org/copyleft/gpl.html
* @license		GNU/GPL based on AceSQL www.joomace.net
*
* Based on EasySQL Component
* @copyright (C) 2008 - 2011 Serebro All rights reserved
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* @link http://www.lurm.net
*/

// Check to ensure this file is included in Moomla!
defined('MIWI') or die( 'Restricted access' );

class MiwosqlModelEdit extends MiwosqlModel {

	public function __construct()	{
		parent::__construct();
	}
	
    public function getData() {
		$task = MRequest::getCmd('task');
		$query = MiwosqlHelper::getVar('qry');
		$key = MRequest::getCmd('key', MRequest::getCmd('key', null, 'post'), 'get');
	
		if (!is_null($query) && !is_null($key)) {
			$this->_db->setQuery($query);
			$rows = $this->_db->loadAssocList();
			
			$last_key_vol = $rows[count($rows) -1][$key];
			
			if ($task == 'edit') {
				foreach ($rows as $row) {
					$rows[$row[$key]] = $row;
				}
			}
			else {
				$rows[0] = array();
			}
		}
		else {
			$rows[0] = array();
			$last_key_vol = '';
		}
		
		return array($rows, $last_key_vol);
	}
	
    public function getFields() {
		$table = MiwosqlHelper::getVar('tbl');
		
		if (MiwosqlHelper::is30()) {
			$fields = $this->_db->getTableColumns($table);
		}
		else {
			$fields = $this->_db->getTableFields(array($table));
		}
		
		return $fields;
	}

    public function add($table, $sql) {
        $fields = MRequest::getVar('fields', array(), 'post', 'array');

        $query = "";

        if ((!is_null($table)) && !is_null($sql) && !is_null($fields)) {
           $i = 0;
           $comma = ', ';
           $cnt = count($fields);
           $sql_fields = '';
           $sql_values = '';

			foreach($fields as $name => $val) {
				$i++;
				if ($cnt <= $i) {
					$comma = '';
				}

				$sql_fields .= "`$name`".$comma;
				$sql_values .= "'$val'".$comma;
			}

			$query = "INSERT INTO $table ($sql_fields) VALUES($sql_values)";
        }

        $this->_db->setQuery($query);
        $this->_db->query();

        if (!empty($this->_db->_errorMsg)) {
            echo '<small style="color:red;">'.$this->_db->_errorMsg.'</small><br/>';
            return false;
        }
        else {
            return true;
        }
    }
	
    public function save($table, $query) {
        $key = MRequest::getCmd('key', null, 'post');
        $fields = MRequest::getVar('fields', array(), 'post', 'array');

        if ((!is_null($table)) && !is_null($query) && !empty($fields)) {
            $sql_save = "UPDATE {$table} SET ";

            $i = 0;
            $comma = ', ';
            $cnt = count($fields);

            foreach ($fields as $name => $val) {
				$i++;
				if ($cnt <= $i) {
                   $comma = '';
				}

				$sql_save .= "`{$name}`='".htmlspecialchars($val, ENT_QUOTES)."'".$comma;
            }

            $sql_save .= " WHERE `{$key}`='".$fields[$key]."'";
        }

        $this->_db->setQuery($sql_save);
        $this->_db->loadAssocList();

        if (!empty($this->_db->_errorMsg)) {
           echo '<small style="color:red;">'.$this->_db->_errorMsg.'</small><br/>';
           return false;
        }
        else {
           return true;
        }
    }
}