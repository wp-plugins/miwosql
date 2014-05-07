<?php
/**
* @version		1.0.0
* @package		MiwoSQL
* @subpackage	MiwoSQL
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @license		GNU General Public License version 2 or later
*
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