<?php
/*
* @package		MiwoSQL
* @copyright	2009-2012 Mijosoft LLC, mijosoft.com
* @license		GNU/GPL http://www.gnu.org/copyleft/gpl.html
*/

// No Permission
defined('MIWI') or die ('Restricted access');

mimport('framework.application.component.controller');

class MiwosqlController extends MController {

	public function __construct($default = array()) {
		parent::__construct($default);

        $this->_db = MFactory::getDBO();

        $this->_table = MiwosqlHelper::getVar('tbl');
        $this->_query = MiwosqlHelper::getVar('qry');

		$this->registerTask('add', 'edit');
		$this->registerTask('new', 'edit');
	}

    public function display($cachable = false, $urlparams = false) {
		$controller = MRequest::getWord('controller', 'miwosql');
		MRequest::setVar('view', $controller);

		parent::display($cachable, $urlparams);
	}
	
    public function edit() {
        MRequest::setVar('hidemainmenu', 1);
		MRequest::setVar('view', 'edit');
		MRequest::setVar('edit', true);

		parent::display();
	}
}
