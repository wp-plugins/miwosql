<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

define('MIWI', 1);
define('_MEXEC', 1);

if (!defined('MPATH_MIWI')) {
	define('MPATH_MIWI', dirname(__FILE__));
}

require_once(MPATH_MIWI . '/defines.php');

// Import the library loader if necessary.
if (!class_exists('MLoader')) {
	require_once(MPATH_MIWI . '/loader.php');
}

class_exists('MLoader') or die;

MLoader::setup();

MLoader::import('framework.factory');
MLoader::import('framework.error.exception');

/*
 * If the HTTP_HOST environment variable is set we assume a Web request and
 * thus we import the request library and most likely clean the request input.
 */
if (isset($_SERVER['HTTP_HOST'])) {
	MLoader::register('MRequest', MPATH_MIWI . '/framework/environment/request.php');

	// If an application flags it doesn't want this, adhere to that.
	if (!defined('_MREQUEST_NO_CLEAN') && (bool) ini_get('register_globals')) {
		//MRequest::clean();  // miwisoft ticket 110
	}
}

MLoader::import('framework.object.object');
MLoader::import('framework.text.text');
MLoader::import('framework.route.route');

// Register the library base path for CMS libraries.
MLoader::registerPrefix('M', MPATH_MIWI . '/framework');

// Define the Miwi version if not already defined.
if (!defined('MVERSION')) {
	$Mversion = new MVersion;
	define('MVERSION', $Mversion->getShortVersion());
}

mimport('framework.application.menu');
mimport('framework.environment.uri');
mimport('framework.utilities.utility');
mimport('framework.event.dispatcher');
mimport('framework.utilities.arrayhelper');

mimport('framework.access.access');
mimport('framework.user.user');
mimport('framework.document.document');
mimport('phputf8.utf8');
mimport('framework.database.table');

if (is_admin()) {
    mimport('framework.html.toolbar');
    mimport('framework.html.toolbar.helper');
}

mimport('framework.application.component.helper');

// Base.css file
MFactory::getDocument()->addStyleSheet(MURL_WP_CNT.'/miwi/media/system/css/base.css');