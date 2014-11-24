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

abstract class MiwosqlHelper {
	
	public static function is30() {
		static $status;
		
		if (!isset($status)) {
			if (version_compare(MVERSION, '3.0.0', 'ge')) {
				$status = true;
			}
			else {
				$status = false;
			}
		}
		
		return $status;
	}

    public static function getVar($v = 'tbl') {
		static $vars = array();
		
		if (!isset($vars[$v])) {
			$vars[$v] = MRequest::getString('ja_'.$v.'_p', '', 'post');
			
			if (empty($vars[$v])) {
				$_var = base64_decode(MRequest::getString('ja_'.$v.'_g', '', 'get'));
				$vars[$v] = stripslashes($_var);
			}
		}

   		return $vars[$v];
   	}

    public static function renderHtml($name, $type, $value) {
		$type = trim(preg_replace('/unsigned/i', '', $type));

		switch (strtolower($type)) {
			case 'hidden':
				$ret = '<input type="hidden" name="fields['.$name.']" value="'.$value.'">';
				break;
			case 'disabled':
				$ret = '<input type="text" name="fields['.$name.']" value="'.$value.'" disabled="disabled">';
				break;
			case 'char':
			case 'nchar':
				$ret = '<input type="text" name="fields['.$name.']"  style="width:7%;" value="'.$value.'">';
				break;
			case 'varchar':
			case 'nvarchar':
				$ret = '<input type="text" name="fields['.$name.']" style="width:40%;" value="'.$value.'">';
				break;
			case 'tinyblob':
			case 'tinytext':
			case 'blob':
			case 'text':
				$ret = '<textarea name="fields['.$name.']" style="width:70%;">'.$value.'</textarea>';
				break;
			case 'mediumblob':
			case 'mediumtext':
			case 'longblob':
			case 'longtext':
				$ret = '<textarea name="fields['.$name.']" style="width:70%; height:150px;">'.$value.'</textarea>';
				break;
			//int
			case 'bit':
			case 'bool':
				$ret = '<input type="checkbox" name="fields['.$name.']">';
				break;
			case 'tinyint':
			case 'smallint':
			case 'mediumint':
			case 'integer':
			case 'int':
			case 'bigint':
			case 'datetime':
			case 'time':
				$ret = '<input type="text" name="fields['.$name.']" style="width:15%;" value="'.$value.'">';
				break;
			//real
			case 'real':
			case 'float':
			case 'decimal':
			case 'numeric':
			case 'double':
			case 'double precesion':
				$ret = '<input type="text" name="fields['.$name.']" style="width:15%;" value="'.$value.'">';
				break;
			default:
				$ret = '';
		}

		return $ret;
    }
	
	public static function isActiveSubMenu($src) {
        $state = false;
		$controller = MRequest::getCmd('controller');
		
		switch ($src) {
			case 'query':
				if (empty($controller) || $controller == 'miwosql') {
					$state = true;
				}
				break;
			case 'queries':
				if ($controller == 'queries') {
					$state = true;
				}
				break;
		}

        return $state;
	}
}