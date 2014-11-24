<?php
/*
* @package		MiwoSQL
* @copyright	2009-2012 Mijosoft LLC, www.mijosoft.com
* @license		GNU/GPL http://www.gnu.org/copyleft/gpl.html
*/

// No Permission
defined('MIWI') or die ('Restricted access');

mimport('framework.filesystem.file');
mimport('framework.filesystem.folder');

class com_MiwosqlInstallerScript {
	
	public function postflight($type, $parent) {
		if (MFolder::copy(MPath::clean(MPATH_WP_PLG.'/miwosql/languages'), MPath::clean(MPATH_WP_CNT.'/miwi/languages'), null, true)) {
			MFolder::delete(MPath::clean(MPATH_WP_PLG.'/miwosql/languages'));
		}
    }
}