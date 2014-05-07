<?php
/**
* @package		MiwoSQL
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

// no direct access
defined('MIWI') or die('Restricted access');

if (version_compare(MVERSION,'1.6.0','ge')) {
	if (!MFactory::getUser()->authorise('core.manage', 'com_miwosql')) {
		return MError::raiseWarning(404, MText::_('MERROR_ALERTNOAUTHOR'));
	}
}

require_once(MPATH_COMPONENT.'/mvc/model.php');
require_once(MPATH_COMPONENT.'/mvc/view.php');
require_once(MPATH_COMPONENT.'/mvc/controller.php');

require_once(MPATH_COMPONENT.'/toolbar.php');
require_once(MPATH_COMPONENT.'/helpers/helper.php');

MTable::addIncludePath(MPATH_COMPONENT.'/tables');

if ($controller = MRequest::getWord('controller')) {
	$path = MPATH_COMPONENT.'/controllers/'.$controller.'.php';
	if (file_exists($path)) {
		require_once $path;
	}
	else {
		$controller = '';
	}
}

$classname = 'MiwosqlController'.ucfirst($controller);

$controller = new $classname();

$controller->execute(MRequest::getCmd('task'));
$controller->redirect();

echo '<div style="margin: 10px; text-align: center;"><a href="http://miwisoft.com/wordpress-plugins/miwosql" target="_blank">MiwoSQL | Copyright &copy; 2009-2014 Miwisoft LLC</a></div>';
