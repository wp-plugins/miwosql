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

mimport('framework.application.component.controller');

class MiwosqlControllerMiwosql extends MiwosqlController {

    public function __construct($default = array()) {
		parent::__construct($default);

        $this->_model = $this->getModel('miwosql');
	}

    public function run() {
   		// Check for request forgeries
   		//MRequest::checkToken() or mexit('Invalid Token');

		MRequest::setVar('view', 'miwosql');

		parent::display();
   	}
	
    public function csv() {
        ob_end_clean();

        $file_name = 'export_'.$this->_table.'.csv';

        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Accept-Ranges: bytes');
        header('Content-Disposition: attachment; filename='.basename($file_name).';');
        header('Content-Type: text/plain; '._ISO);
        header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Pragma: no-cache');

        echo $this->_model->exportToCsv($this->_query);

        exit(); # it should be exit(), not mexit();
    }

    public function delete() {
   		// Check for request forgeries
   		//MRequest::checkToken() or mexit('Invalid Token');

        if ($this->_model->delete($this->_table)) {
            $msg = MText::_('COM_MIWOSQL_DELETE_TRUE');
        }
        else {
            $msg = MText::_('COM_MIWOSQL_DELETE_FALSE');
        }
		
		$vars = 'ja_tbl_g='.base64_encode($this->_table).'&ja_qry_g='.base64_encode($this->_query);

   		$this->setRedirect('index.php?option=com_miwosql&'.$vars, $msg);
   	}

    public function saveQuery() {
   		// Check for request forgeries
   		//MRequest::checkToken() or mexit('Invalid Token');

   		$this->setRedirect('index.php?option=com_miwosql&controller=queries&task=edit&ja_query='.base64_encode($this->_query));
   	}
}