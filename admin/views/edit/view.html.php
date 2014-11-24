<?php
/**
* @version		1.0.0
* @package		MiwoSQL
* @subpackage	MiwoSQL
* @copyright	2009-2012 Mijosoft LLC, www.mijosoft.com
* @license		GNU/GPL http://www.gnu.org/copyleft/gpl.html
* @license		GNU/GPL based on AceSQL www.joomace.net
*/

//No Permision
defined('MIWI') or die('Restricted access');

class MiwosqlViewEdit extends MiwosqlView {

	public function display($tpl = null) {
		$db = MFactory::getDbo();
		
		$task = MRequest::getCmd('task');
		$table = MiwosqlHelper::getVar('tbl');
        $query = MiwosqlHelper::getVar('qry');
		$id = MRequest::getInt('id', MRequest::getInt('id', null, 'post'), 'get');
		$key = MRequest::getCmd('key', MRequest::getCmd('key', null, 'post'), 'get');
		
		$document = MFactory::getDocument();
		$document->addStyleSheet(MURL_MIWOSQL.'/admin/assets/css/miwosql.css');
		
		// Toolbar
		MToolBarHelper::title(MText::_('MiwoSQL') .': <small><small> '. $table.' [ '.$key.' = '.$id.' ]' .' </small></small>', 'miwosql');
		MToolBarHelper::apply();
		MToolBarHelper::save();
		MToolBarHelper::divider();
		MToolBarHelper::cancel();
       
		if ($task == 'edit') {
			$fld_value = '$value = $this->rows[$this->id][$field];';
		}
		else {
			$fld_value = '$value = "";';
		}
		
		list($rows, $last_key_vol) = $this->get('Data');
		
		$this->task = $task;
		$this->id = $id;
		$this->key = $key;
		$this->table = $table;
		$this->query = $query;
		$this->fld_value = $fld_value;
		$this->last_key_vol = $last_key_vol;
		$this->rows = $rows;
		
		$fields = $this->get('Fields');
		if (!MiwosqlHelper::is30()) {
			$fields = $fields[$this->table];
		}
		
		$this->fields = $fields;
		
		parent::display($tpl);
	}
}