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

class MiwosqlControllerQueries extends MiwosqlController {

    public function __construct($default = array()) {
		parent::__construct($default);

        $this->_model = $this->getModel('Queries');
	}
	
    /*public function view() {
   		$view = $this->getView(ucfirst('Queries'), 'html');
		$view->setModel($this->_model, true);
		$view->view();
   	}*/

    public function edit() {
        MRequest::setVar('hidemainmenu', 1);

        $view = $this->getView(ucfirst('Queries'), 'edit');
        $view->setModel($this->_model, true);
        $view->display('edit');
   	}

    public function save() {
   		// Check for request forgeries
   		MRequest::checkToken() or jexit('Invalid Token');

        $post = MRequest::get('post');
        $post['query'] = base64_encode(MRequest::getVar('ja_query', '', 'post', 'string', MREQUEST_ALLOWRAW));
        unset($post['ja_query']);

        if ($this->_model->saveQuery($post)) {
            $msg = MText::_('COM_MIWOSQL_SAVE_TRUE');
        }
        else {
            $msg = MText::_('COM_MIWOSQL_SAVE_FALSE');
        }

   		$this->setRedirect('index.php?option=com_miwosql&controller=queries', $msg);
   	}

    public function cancel() {
   		// Check for request forgeries
   		MRequest::checkToken() or jexit('Invalid Token');

   		$this->setRedirect('index.php?option=com_miwosql&controller=queries');
   	}

    public function remove() {
        // Check for request forgeries
        MRequest::checkToken() or jexit('Invalid Token');

        $cid = MRequest::getVar('cid', array(), '', 'array');

        MArrayHelper::toInteger($cid);
        $msg = '';

        for ($i=0, $n=count($cid); $i < $n; $i++) {
            $query = MTable::getInstance('Query', 'Table');

            if (!$query->delete($cid[$i])) {
                $msg .= $query->getError();
                $tom = "error";
            }
            else {
                $msg = MTEXT::_('COM_MIWOSQL_QUERY_DELETED');
                $tom = "";
            }
        }

        $this->setRedirect('index.php?option=com_miwosql&controller=queries', $msg, $tom);
    }
}