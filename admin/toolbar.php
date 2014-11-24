<?php
/**
* @package		MiwoSQL
* @copyright	2009-2012 Mijosoft LLC, www.mijosoft.com
* @license		GNU/GPL http://www.gnu.org/copyleft/gpl.html
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