<?php
/*
* @package		MiwoSQL
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

// No Permission
defined('MIWI') or die ('Restricted access');

mimport('framework.filesystem.file');
mimport('framework.filesystem.folder');

class com_MiwosqlInstallerScript {
	
	public function postflight($type, $parent) {
		if (MFolder::copy(MPath::clean(MPATH_WP_PLG . '/miwosql/languages'), MPath::clean(MPATH_MIWI . '/languages'), null, true)) {
			MFolder::delete(MPath::clean(MPATH_WP_PLG . '/miwosql/languages'));
		}
    }
}