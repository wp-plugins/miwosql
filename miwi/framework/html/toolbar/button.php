<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

abstract class MButton extends MObject {

	protected $_name = null;
	protected $_parent = null;

	public function __construct($parent = null) {
		$this->_parent = $parent;
	}

	public function getName() {
		return $this->_name;
	}

	public function render(&$definition) {
		/*
		 * Initialise some variables
		 */
		$html = null;
		$id = call_user_func_array(array(&$this, 'fetchId'), $definition);
		$action = call_user_func_array(array(&$this, 'fetchButton'), $definition);
		
		// Build id attribute
		if ($id) {
			$id = "id=\"$id\"";
		}
		
		// Build the HTML Button
		//$html .= "<li class=\"button\" $id>\n";
		$html .= $action;
		//$html .= "</li>\n";
		
		return $html;
	}

	public function fetchIconClass($identifier) {
		return "icon-32-$identifier";
	}

	abstract public function fetchButton();
}