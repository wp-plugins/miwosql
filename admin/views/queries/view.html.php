<?php
/**
* @version		1.0.0
* @package		MiwoSQL
* @subpackage	MiwoSQL
* @copyright	2009-2012 Mijosoft LLC, www.mijosoft.com
* @license		GNU/GPL http://www.gnu.org/copyleft/gpl.html
* @license		GNU/GPL based on AceSQL www.joomace.net
*/

// no direct access
defined( '_MEXEC' ) or die( 'Restricted access' );

class MiwosqlViewQueries extends MiwosqlView {

	function display($tpl = null) {
        $mainframe = MFactory::getApplication();
        $option = MRequest::getCmd('option');
		$document = MFactory::getDocument();
  		$document->addStyleSheet(MURL_MIWOSQL.'/admin/assets/css/miwosql.css');
		
		MToolBarHelper::title(MText::_('MiwoSQL').' - '.MText::_('COM_MIWOSQL_SAVED_QUERIES'), 'miwosql');
		MToolBarHelper::editList();
		MToolBarHelper::deleteList();
		
        // ACL
        if (version_compare(MVERSION,'1.6.0','ge') && MFactory::getUser()->authorise('core.admin', 'com_miwosql')) {
            MToolBarHelper::divider();
            MToolBarHelper::preferences('com_miwosql', '550');
        }
	
		$this->mainframe = MFactory::getApplication();
		$this->option = MRequest::getWord('option');

		$filter_order		= $mainframe->getUserStateFromRequest($option.'.queries.filter_order',		'filter_order',		'title',	'string');
		$filter_order_Dir	= $mainframe->getUserStateFromRequest($option.'.queries.filter_order_Dir',	'filter_order_Dir',	'',			'word');
		$search				= $mainframe->getUserStateFromRequest($option.'.queries.search',			'search',			'',			'string');

		// table ordering
		$lists['order_Dir']	= $filter_order_Dir;
		$lists['order']		= $filter_order;

		// search filter
		$lists['search']= $search;

		$this->lists = $lists;
		$this->items = $this->get('Data');
		$this->pagination = $this->get('Pagination');

		parent::display($tpl);
	}
}