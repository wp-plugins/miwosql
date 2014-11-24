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

class MiwosqlModelMiwosql extends MiwosqlModel {

	public $_task = null;
	public $_table = null;
	public $_query = null;

	public function __construct()	{
		parent::__construct();
		
		$this->_task = MRequest::getCmd('task');
		
		$this->_table = MiwosqlHelper::getVar('tbl');
        $this->_query = MiwosqlHelper::getVar('qry');
	}

    public function getData() {
        $db = MFactory::getDbo();

		$html = '';
		if (empty($this->_query)) {
			return $html;
		}
		
		if (preg_match('/REPLACE PREFIX (.*) TO (.*)/', $this->_query)) {
			self::_replacePrefix($db, $db, $this->_query);
		}
		else {
			$query_arr = self::_splitSQL($this->_query);
			
			for ($i = 0; $i <= (count($query_arr) -1 ); $i++) {
				if (empty($query_arr[$i])) {
					continue;
				}
				
				$html .= self::_getHtmlTable($query_arr[$i], $i, $db);
			}
		}
		
		return $html;
    }
	
	public function getTables() {
		$table_list = '';
		
		// Get table list
		$this->_db->setQuery('SHOW TABLES');
		$tables = $this->_db->loadAssocList();

		if (empty($tables)) {
			return $table_list;
		}
		
		$config = MFactory::getConfig();
		if (version_compare(MVERSION, '1.6.0', 'ge')) {
    	    $database = $config->get('db');
        }
        else {
            $database = $config->getValue('config.db');
        }
		
		$key = 'Tables_in_'.$database;
		
		foreach ($tables as $table) {
			$_sel = '';
			if ($table[$key] == $this->_table) {
				$_sel = 'selected';
			}
			
			$table_list .= '<option '.$_sel.' value="'.$table[$key].'">'.$table[$key].'</option>';
		}
		
		return $table_list;
	}
	
	public function getPrefix() {
		return $this->_db->getPrefix();
	}
	
    public function delete($table) {
        $sql = MiwosqlHelper::getVar('qry');
        $key = MRequest::getString('key', null, 'get');

        if (!is_null($sql) && !is_null($key)) {
            $id = MRequest::getCmd('id', null, 'get');

            $this->_db->setQuery("DELETE FROM {$table} WHERE $key = '$id'");
            $this->_db->query();

            if (!empty($this->_db->_errorMsg)) {
                echo '<small style="color:red;">'.$this->_db->_errorMsg.'</small><br/>';
                return false;
            }
            else {
                return true;
            }
        }
    }
	
    public function _replacePrefix($query) {
        $mainframe = MFactory::getApplication();

        $msg = '';

        $config_file = MPATH_CONFIGURATION.'/configuration.php';

        list($prefix, $new_prefix) = sscanf(str_replace(array('`', '"', "'"),'',strtolower(trim($query))), "replace prefix %s to %s");

        if (!is_writable($config_file)) {
            echo '<h2 style="color: red;">'.sprintf(MText::_('COM_MIWOSQL_CONFIG_NOT_WRITABLE'), $config_fname).'</h2>';
            return $msg;
        }

        $this->_db->setQuery("SHOW TABLES LIKE '".$prefix."%'");
        $tables = $this->_db->loadResultArray();

        foreach($tables as $tbl) {
            $new_tbl = str_replace($prefix, $new_prefix, $tbl);
            $this->_db->setQuery( 'ALTER TABLE `'.$tbl.'` RENAME `'.$new_tbl.'`' );
            $this->_db->query();

    		if (!empty($this->_db->_errorMsg)) {
                echo '<small style="color:red;">'.$this->_db->_errorMsg.'</small><br/>';
            }
        }

    	$config = MFactory::getConfig();
        if (version_compare(MVERSION, '1.6.0', 'ge')) {
    	    $config->set('dbprefix', $new_prefix);
        }
        else {
            $config->setValue('config.dbprefix', $new_prefix);
        }

    	/*jimport('joomla.filesystem.path');
    	if (!$ftp['enabled'] && MPath::isOwner($config_fname) && !MPath::setPermissions($config_fname, '0644')) {
    		MError::raiseNotice('SOME_ERROR_CODE', 'Could not make configuration.php writable');
    	}*/

    	mimport('framwork.filesystem.file');

        if (version_compare(MVERSION,'1.6.0','ge')) {
            if (!MFile::write($config_file, $config->toString('PHP', array('class' => 'MConfig', 'closingtag' => false))) ) {
                $msg = MText::_('COM_MIWOSQL_DONE');
            }
            else {
                $msg = MText::_('ERRORCONFIGFILE');
            }
        }
        else {
            if (MFile::write($config_file, $config->toString('PHP', 'config', array('class' => 'MConfig')))) {
                $msg = MText::_('COM_MIWOSQL_DONE');
            }
            else {
                $msg = MText::_('ERRORCONFIGFILE');
            }
        }

    	return $msg;
    }

    public function exportToCsv($query) {
        $csv_save = '';

        $this->_db->setQuery($query);
        $rows = $this->_db->loadAssocList();

        if (!empty($rows)) {
            $comma = MText::_('COM_MIWOSQL_CSV_DELIMITER');
            $CR = "\r";

            // Make csv rows for field name
            $i = 0;
            $fields = $rows[0];
            $cnt_fields = count($fields);
            $csv_fields = '';
            foreach($fields as $name => $val) {
                $i++;

                if ($cnt_fields <= $i) {
                    $comma = '';
                }

                $csv_fields .= $name.$comma;
            }

            // Make csv rows for data
            $csv_values = '';
            foreach($rows as $row) {
                $i = 0;
                $comma = MText::_('COM_MIWOSQL_CSV_DELIMITER');

                foreach($row as $name=>$val) {
                    $i++;
                    if ($cnt_fields <= $i) {
                        $comma = '';
                    }

                    $csv_values .= $val.$comma;
                }

                $csv_values .= $CR;
            }

            $csv_save = $csv_fields.$CR.$csv_values;
        }

        return $csv_save;
    }
	
	public function _getHtmlTable($query, $num, $db) {
       // trim long query for output
       $show_query = (strlen(trim($query)) > 100) ? substr($query, 0, 50).'...' : $query;
       
	   // run query
       $db->setQuery($query);
       $rows = $db->loadAssocList();
       $aff_rows = $db->getAffectedRows();
	   
       $num++;
       $body = "<br> $num. [ ".$show_query." ], ";
       $body .= 'rows: '.$aff_rows;
       $body .= '<br />';
	   
		$table = self::_getTableFromSQL($query); // get table name from query string
		$_sel = (substr(strtolower($query), 0, 6) == 'select' && !strpos(strtolower($query), 'procedure analyse'));
		
		// If return rows then display table
		if (!empty($rows)) {
			// Begin form and table
			$body .= '<br />';
			$body .= '<div style="overflow: auto;">';
			$body .= '<table class="wp-list-table widefat">';
			$body .= "<thead>";
			$body .= "<tr>";
			
			// Display table header
			if ($_sel) {
				$body .= '<th>'.MText::_('COM_MIWOSQL_ACTION').'</th>';
			}
			
			$k_arr = $rows[0];
			$f = 1;
			$key = '';
			foreach($k_arr as $var => $val) {
				if ($f) {
					$f = 0;
					$key = $var;
				}
				
				if (preg_match("/[a-zA-Z]+/", $var, $array)) {
					$body .= '<th>'.$var."</th>";
				}
			}
			
			$body .= "</tr>";
			$body .= "</thead>";
			
			// Get unique field of table
			$uniq_fld = (self::_isTable($table)) ? self::_getUniqFld($table) : '';
			$key = empty($uniq_fld) ? $key : $uniq_fld;
			
			// Display table rows
			$k = 0;
			$i = 0;
			foreach($rows as $row) {
				$body .= '<tbody>';
				$body .= '<tr valign="top" class="row'.$k.'">';
			   
				if ($_sel) {
                    $edit_link = MRoute::_('index.php?option=com_miwosql&task=edit&ja_tbl_g='.base64_encode($table).'&ja_qry_g='.base64_encode($query).'&key='.$key.'&id='.$row[$key]);
                    $delete_link = MRoute::_('index.php?option=com_miwosql&controller=miwosql&task=delete&ja_tbl_g='.base64_encode($table).'&ja_qry_g='.base64_encode($query).'&key='.$key.'&id='.$row[$key]);

					$body .= '<td align="left" nowrap>';
						$body .= '<a href="'.$edit_link.'">';
							$body .= '<img border="0" src="'.MURL_MIWOSQL.'/admin/assets/images/icon-16-edit.png" alt="'.MText::_('COM_MIWOSQL_EDIT').'" title="'.MText::_('COM_MIWOSQL_EDIT').'" />';
						$body .= '</a>';
						$body .= '&nbsp;';
						$body .= '<a href="#" onclick="if (confirm(\'Are you sure you want to delete this record?\')) {this.href=\''.$delete_link.'\'};">';
							$body .= '<img border="0" src="'.MURL_MIWOSQL.'/admin/assets/images/icon-16-delete.png" alt="'.MText::_('COM_MIWOSQL_DELETE').'" title="'.MText::_('COM_MIWOSQL_DELETE').'" />';
						$body .= '</a>';
					$body .= '</td>';
				}
				
				foreach ($row as $var => $val) {
					if (preg_match("/[a-zA-Z]+/", $var, $array)) {
						$body .= '<td>&nbsp;'.htmlspecialchars(substr($val, 0, 100))."&nbsp;</td>\n";
					}
				}
				
				$body .= "</tbody>";
				$body .= "</tr>";
				$k = 1 - $k;
				$i++;
			}
			
			// End table and form
			$body .= '</table>';
			$body .= '<br />';
			$body .= '</div>';
			$body .= '<input type="hidden" name="key" value="'.$key.'">';
		}
		else {
			// Display DB errors
			$body .= '<small style="color:red;">'.$db->_errorMsg.'</small><br/>';
		}
	   
       return $body.'<br />';
	}
	
	public function _getTableFromSQL($sql) {
		$in = strpos(strtolower($sql), 'from ')+5;
		$end = strpos($sql, ' ', $in);
		$end = empty($end) ? strlen($sql) : $end;  // If table name in query end
		
		return substr($sql, $in, $end-$in);
	}
	
	public function _splitSQL($sql) {
		$sql = trim($sql);
		$sql = preg_replace("/\n#[^\n]*\n/", "\n", $sql);
		
		$buffer = array();
		$ret = array();
		$in_string = false;
		
		for($i = 0; $i < strlen($sql) - 1; $i++) {
			if ($sql[$i] == ";" && !$in_string) {
				$ret[] = substr($sql, 0, $i);
				$sql = substr($sql, $i + 1);
				$i = 0;
			}
		   
			if ($in_string && ($sql[$i] == $in_string) && $buffer[1] != "\\") {
				$in_string = false;
			}
			elseif(!$in_string && ($sql[$i] == '"' || $sql[$i] == "'") && (!isset($buffer[0]) || $buffer[0] != "\\")) {
				$in_string = $sql[$i];
			}
		   
			if (isset($buffer[1])) {
				$buffer[0] = $buffer[1];
			}
			
			$buffer[1] = $sql[$i];
		}
	   
		if (!empty($sql)) {
            $ret[] = $sql;
		}
		
		return($ret);
	}
	
	public function _isTable($table) {
		$tables = $this->_db->getTableList();
		$table = str_replace("#__", $this->_db->getPrefix(), $table);
		
		return (strpos(implode(";", $tables),$table) > 0);
	}
	
	public function _getUniqFld($table) {
		$this->_db->setQuery('SHOW KEYS FROM '.$table);
		$indexes = $this->_db->loadAssocList();

		$uniq_fld = '';
		if (empty($indexes)) {
			return $uniq_fld;
		}
		
		foreach($indexes as $index) {
			if ($index['Non_unique'] == 0) {
				$uniq_fld = $index['Column_name'];
				break;
			}
		}
		
		return $uniq_fld;
	}
}