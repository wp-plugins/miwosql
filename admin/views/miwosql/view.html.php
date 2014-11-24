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

class MiwosqlViewMiwosql extends MiwosqlView {

	public function display($tpl = null){
		$document = MFactory::getDocument();
		$document->addStyleSheet(MURL_MIWOSQL.'/admin/assets/css/miwosql.css');
		
		// Toolbar
		MToolBarHelper::title(MText::_('MiwoSQL').' - '.MText::_('COM_MIWOSQL_RUN_QUERY'), 'miwosql');
		
		if (MiwosqlHelper::is30()) {
			MToolBarHelper::custom('run', 'play.png', 'play.png', MText::_('COM_MIWOSQL_RUN_QUERY'), false);
			MToolBarHelper::divider();
			MToolBarHelper::custom('savequery', 'folder-close.png', 'folder-close.png', MText::_('COM_MIWOSQL_SAVE_QUERY'), false);
			MToolBarHelper::divider();
			MToolBarHelper::custom('csv', 'upload.png', 'upload.png', MText::_('COM_MIWOSQL_EXPORT_CSV'), false);
		}
		else {
			MToolBarHelper::custom('run', 'run.png', 'run.png', MText::_('COM_MIWOSQL_RUN_QUERY'), false);
			MToolBarHelper::divider();
			MToolBarHelper::custom('savequery', 'savequery.png', 'savequery.png', MText::_('COM_MIWOSQL_SAVE_QUERY'), false);
			MToolBarHelper::divider();
			MToolBarHelper::custom('csv', 'csv.png', 'csv.png', MText::_('COM_MIWOSQL_EXPORT_CSV'), false);

		}
		
		// ACL
		if (version_compare(MVERSION,'1.6.0','ge') && MFactory::getUser()->authorise('core.admin', 'com_miwosql')) {
			MToolBarHelper::divider();
			MToolBarHelper::preferences('com_miwosql', '550');
		}
		
		$this->data = $this->get('Data');
		$this->tables = $this->get('Tables');
		$this->prefix = $this->get('Prefix');
		
		parent::display($tpl);
	}
}