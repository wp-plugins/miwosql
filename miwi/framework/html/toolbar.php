<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

mimport('framework.html.toolbar.button');

class MToolBar extends MObject {

	protected $_name = array();
	protected $_bar = array();
	protected $_title = array();
	protected $_buttons = array();
	protected $_buttonPath = array();

	public function __construct($name = 'toolbar') {
		$this->_name = $name;
		
		// Set base path to find buttons.
		$this->_buttonPath[] = dirname(__FILE__) . '/toolbar/button';
	}
	protected static $instances = array ();

	public static function getInstance($name = 'toolbar') {
		if (empty(self::$instances[$name])) {
			self::$instances[$name] = new MToolBar($name);
		}
		
		return self::$instances[$name];
	}

	public function appendButton() {
		// Push button onto the end of the toolbar array.
		$btn = func_get_args();
		array_push($this->_bar, $btn);
		return true;
	}

	public function getItems() {
		return $this->_bar;
	}

	public function getName() {
		return $this->_name;
	}

	public function prependButton() {
		// Insert button into the front of the toolbar array.
		$btn = func_get_args();
		array_unshift($this->_bar, $btn);
		return true;
	}

	public function render() {
		$html = array();
		
		// Start toolbar div.
		$html[] = '<h2>';
		$html[] = $this->get('_title');
		
		// Render each button in the toolbar.
		foreach ($this->_bar as $button) {
			$html[] = $this->renderButton($button);
		}

        $html[] = '</h2>';
		
		return implode("\n", $html);
	}

	public function renderButton(&$node) {
		// Get the button type.
		$type = $node[0];
		
		$button = $this->loadButtonType($type);
		
		// Check for error.
		if ($button === false) {
			return MText::sprintf('MLIB_HTML_BUTTON_NOT_DEFINED', $type);
		}
		return $button->render($node);
	}

	public function loadButtonType($type, $new = false) {
		$signature = md5($type);
		if (isset($this->_buttons[$signature]) && $new === false) {
			return $this->_buttons[$signature];
		}
		
		if (!class_exists('MButton')) {
			MError::raiseWarning('SOME_ERROR_CODE', MText::_('MLIB_HTML_BUTTON_BASE_CLASS'));
			return false;
		}
		
		$buttonClass = 'MButton' . $type;
		if (! class_exists($buttonClass)) {
			if (isset($this->_buttonPath)) {
				$dirs = $this->_buttonPath;
			}
			else {
				$dirs = array ();
			}
			
			$file = MFilterInput::getInstance()->clean(str_replace('_', DIRECTORY_SEPARATOR, strtolower($type)) . '.php', 'path');
			
			mimport('framework.filesystem.path');
			if ($buttonFile = MPath::find($dirs, $file)) {
				include_once $buttonFile;
			}
			else {
				MError::raiseWarning('SOME_ERROR_CODE', MText::sprintf('MLIB_HTML_BUTTON_NO_LOAD', $buttonClass, $buttonFile));
				return false;
			}
		}
		
		if (! class_exists($buttonClass)) {
			//return	MError::raiseError('SOME_ERROR_CODE', "Module file $buttonFile does not contain class $buttonClass.");
			return false;
		}
		$this->_buttons[$signature] = new $buttonClass($this);
		
		return $this->_buttons[$signature];
	}

	public function addButtonPath($path) {
		settype($path, 'array');
		
		// Loop through the path directories.
		foreach ($path as $dir) {
			// No surrounding spaces allowed!
			$dir = trim($dir);
			
			// Add trailing separators as needed.
			if (substr($dir, - 1) != DIRECTORY_SEPARATOR) {
				// Directory
				$dir .= DIRECTORY_SEPARATOR;
			}
			
			// Add to the top of the search dirs.
			array_unshift($this->_buttonPath, $dir);
		}
	}
}