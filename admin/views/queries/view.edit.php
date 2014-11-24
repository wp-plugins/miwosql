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

class MiwosqlViewQueries extends MiwosqlView {

	public function display($tpl = null) {
		$document = MFactory::getDocument();
		$document->addStyleSheet(MURL_MIWOSQL.'/admin/assets/css/miwosql.css');
		
		// Toolbar
		MToolBarHelper::title(MText::_('MiwoSQL'), 'miwosql');
		MToolBarHelper::save();
		MToolBarHelper::cancel();

		$this->row = $this->get('QueryData');
		
		parent::display($tpl);
	}
}