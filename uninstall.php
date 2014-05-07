<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('ABSPATH') or die('MIWI');

preg_match('/miwo([a-z]+)/', $plugin, $matches);

$class_name = $matches[0].'Uninstaller';

$uninstaller = new $class_name($matches[0]);
$uninstaller->delete();

class miwosqlUninstaller {

	protected $context = null;

	public function __construct($context) {
		$this->context = $context;
		
		$this->constants();

		$miwi = MPATH_WP_CNT.'/miwi/initialise.php';

		if (!file_exists($miwi)) {
			return false;
		}

		require_once($miwi);

		$this->app = MFactory::getApplication();
		$this->app->initialise();
		
		mimport('framework.filesystem.file');
	}

	public function constants() {
		if (!defined('MPATH_WP_PLG')) {
			define('MPATH_WP_PLG', dirname(plugin_dir_path(__FILE__)));
		}

		if (!defined('MPATH_WP_CNT')) {
			define('MPATH_WP_CNT', dirname(MPATH_WP_PLG));
		}

		$upload_dir = wp_upload_dir();

		if (!defined('MPATH_MEDIA')) {
			define('MPATH_MEDIA', $upload_dir['basedir']);
		}

		if (!defined('MURL_MEDIA')) {
			define('MURL_MEDIA', $upload_dir['baseurl']);
		}

		if (!defined('MURL_WP_CNT')) {
			define('MURL_WP_CNT', content_url());
		}

		if (!defined('MURL_ADMIN')) {
			define('MURL_ADMIN', admin_url());
		}
	}

	public function delete() {
		$this->deleteTables();
		$this->deleteSpecificFolders();
		$this->deleteMiwi();
		$this->deleteOptions();
		
		if (file_exists(MPATH_WP_CNT.'/miwi')) {
			$this->deleteRelFolders();
		}
	}

	public function deleteOptions() {
		delete_option($this->context);
	}

	public function deleteTables() {
		if (!MFile::exists($filename = MPATH_WP_PLG.'/'.$this->context.'/admin/uninstall.sql')) {
			return null;
		}
		
		$db = MFactory::getDbo();

		$file_content = file($filename);
		
		if (empty($file_content)) {
			return null;
		}
		
		$query = '';
		foreach ($file_content as $sql_line) {
			$tsl = trim($sql_line);
			
			if (($tsl != '') && (strpos($tsl, '--') != 0 || strpos($tsl, '--') != 1) && (substr($tsl, 0, 1) != '#')) {
				$query .= $sql_line;
				
				if (preg_match('/;\s*$/', $sql_line)) {
					$db->setQuery($query);
					$db->query();
					
					$query = '';
				}
			}
		}
	}

	public function deleteMiwi() {
		if (!MFolder::exists(MPATH_WP_PLG)) {
			return;
		}
		
		$folders = MFolder::folders(MPATH_WP_PLG);
		
		$i = 0;
		foreach ($folders as $folder) {
			if (substr($folder, 0, 4) != 'miwo') {
				continue;
			}
			
			$i++;
		}

		if ($i < 2) {
			MFolder::delete(MPATH_WP_CNT.'/miwi');
		}
	}

	public function deleteRelFolders() {
		$this->deleteLangs();
		$this->deleteMedia();
		$this->deleteModules();
		$this->deletePlugins();
		$this->deleteUploads();
	}

	public function deleteLangs() {
		# Admin
		$admin_lang_dir = MPATH_WP_CNT.'/miwi/languages/admin';
		if (MFolder::exists($admin_lang_dir)) {
			$lang_folders = MFolder::folders($admin_lang_dir);
		
			foreach ($lang_folders as $lang_folder) {
				$files = MFolder::files($admin_lang_dir.'/'.$lang_folder);
				
				foreach ($files as $file) {
					if (strpos($file, $this->context) !== false) {
						MFile::delete($admin_lang_dir.'/'.$lang_folder.'/'.$file);
					}
				}
			}
		}
		
		# Site
		$site_lang_dir = MPATH_WP_CNT.'/miwi/languages/site';
		if (MFolder::exists($site_lang_dir)) {
			$lang_folders = MFolder::folders($site_lang_dir);
			
			foreach ($lang_folders as $lang_folder) {
				$files = MFolder::files($site_lang_dir.'/'.$lang_folder);
				
				foreach ($files as $file) {
					if (strpos($file, $this->context) !== false) {
						MFile::delete($site_lang_dir.'/'.$lang_folder.'/'.$file);
					}
				}
			}
		}
	}

	public function deleteMedia() {
		$media_dir = MPATH_WP_CNT.'/miwi/media/'.$this->context;
		
		if (!MFolder::exists($media_dir)) {
			return;
		}
		
		MFolder::delete($media_dir);
	}

	public function deleteModules() {
		$modules_dir = MPATH_WP_CNT.'/miwi/modules';
		
		if (!MFolder::exists($modules_dir)) {
			return;
		}
		
		$folders = MFolder::folders($modules_dir);
		
		foreach ($folders as $folder) {
			if (strpos($folder, $this->context) !== false) {
				MFolder::delete($modules_dir.'/'.$folder);
			}
		}
	}

	public function deleteSpecificFolders() {
		return null;
	}

	public function deletePlugins() {
		$plugins_dir = MPATH_WP_CNT.'/miwi/plugins';
		
		if (!MFolder::exists($plugins_dir)) {
			return;
		}
		
		$folders = MFolder::folders($plugins_dir);
		
		foreach ($folders as $folder) {
			if (strpos($folder, $this->context) !== false) {
				MFolder::delete($plugins_dir.'/'.$folder);
			}
		}
	}

	public function deleteUploads() {
		$uploads_dir = MPATH_MEDIA.'/'.$this->context;
		
		if (MFolder::exists($uploads_dir)) {
			return;
		}
		
		MFolder::delete($uploads_dir);
	}
}