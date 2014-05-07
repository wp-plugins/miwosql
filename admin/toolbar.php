<?php
/**
* @package		MiwoSQL
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

// No Permission
defined('MIWI') or die('Restricted access');

$views = array( '&controller=miwosql'			=> MText::_('COM_MIWOSQL_RUN_QUERY'),
				'&controller=queries'			=> MText::_('COM_MIWOSQL_SAVED_QUERIES')
				);

if (!class_exists('JSubMenuHelper')) {
    return;
}

require_once(MPATH_COMPONENT.'/helpers/helper.php');

MHTML::_('behavior.switcher');

if (MRequest::getInt('hidemainmenu') != 1) {
	JSubMenuHelper::addEntry(MText::_('COM_MIWOSQL_RUN_QUERY'), 'index.php?option=com_miwosql', MiwosqlHelper::isActiveSubMenu('query'));
	JSubMenuHelper::addEntry(MText::_('COM_MIWOSQL_SAVED_QUERIES'), 'index.php?option=com_miwosql&controller=queries', MiwosqlHelper::isActiveSubMenu('queries'));
}