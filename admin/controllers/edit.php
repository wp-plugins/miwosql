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
defined('MIWI') or die('Restricted access');

class MiwosqlControllerEdit extends MiwosqlController {

    public function __construct($default = array()) {
		parent::__construct($default);

        $this->_model = $this->getModel('edit');
	}
	
    public function add() {
   		// Check for request forgeries
   		MRequest::checkToken() or mexit('Invalid Token');

        if ($this->_model->add($this->_table, $this->_query)) {
            $msg = "Yes";
        }
        else {
            $msg = "No";
        }
		
		$vars = '&ja_tbl_g='.base64_encode($this->_table).'&ja_qry_g='.base64_encode($this->_query);

   		$this->setRedirect('index.php?option=com_miwosql'.$vars, $msg);
   	}

    public function apply() {
   		// Check for request forgeries
   		MRequest::checkToken() or mexit('Invalid Token');

        if ($this->_model->save($this->_table, $this->_query)) {
            $msg = MText::_('COM_MIWOSQL_SAVE_TRUE');
        }
        else {
            $msg = MText::_('COM_MIWOSQL_SAVE_FALSE');
        }
		
		$id = MRequest::getInt('id', MRequest::getInt('id', null, 'post'), 'get');
		$key = MRequest::getCmd('key', MRequest::getCmd('key', null, 'post'), 'get');
		
		$vars = '&ja_tbl_g='.base64_encode($this->_table).'&ja_qry_g='.base64_encode($this->_query).'&key='.$key.'&id='.$id;

   		$this->setRedirect('index.php?option=com_miwosql&task=edit'.$vars, $msg);
   	}

    public function save() {
   		// Check for request forgeries
   		MRequest::checkToken() or mexit('Invalid Token');

        if ($this->_model->save($this->_table, $this->_query)) {
            $msg = MText::_('COM_MIWOSQL_SAVE_TRUE');
        }
        else {
            $msg = MText::_('COM_MIWOSQL_SAVE_FALSE');
        }
		
		$vars = '&ja_tbl_g='.base64_encode($this->_table).'&ja_qry_g='.base64_encode($this->_query);

   		$this->setRedirect('index.php?option=com_miwosql'.$vars, $msg);
   	}

    public function cancel() {
   		// Check for request forgeries
   		MRequest::checkToken() or mexit('Invalid Token');
		
		$vars = '&ja_tbl_g='.base64_encode($this->_table).'&ja_qry_g='.base64_encode($this->_query);

   		$this->setRedirect('index.php?option=com_miwosql'.$vars, $msg);
   	}
}