<?php
/**
* @version		1.0.0
* @package		MiwoSQL
* @subpackage	MiwoSQL
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @license		GNU General Public License version 2 or later
*
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