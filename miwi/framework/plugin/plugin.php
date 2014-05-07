<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

abstract class MPlugin extends MEvent {

	public $params = null;
	protected $_name = null;
	protected $_type = null;

	public function __construct(&$subject, $config = array()) {
		// Get the parameters.
		if (isset($config['params'])) {
			if ($config['params'] instanceof MRegistry) {
				$this->params = $config['params'];
			}
			else {
				$this->params = new MRegistry;
				$this->params->loadString($config['params']);
			}
		}

		// Get the plugin name.
		if (isset($config['name'])) {
			$this->_name = $config['name'];
		}

		// Get the plugin type.
		if (isset($config['type'])) {
			$this->_type = $config['type'];
		}

		parent::__construct($subject);
	}

	public function loadLanguage($extension = '', $basePath = MPATH_ADMINISTRATOR) {
		if (empty($extension)) {
			$extension = 'plg_' . $this->_type . '_' . $this->_name;
		}

		$lang = MFactory::getLanguage();
		return $lang->load(strtolower($extension), $basePath, null, false, true)
			|| $lang->load(strtolower($extension), MPATH_PLUGINS . '/' . $extension, null, false, true);
	}
}