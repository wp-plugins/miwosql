<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MButtonSeparator extends MButton {

	protected $_name = 'Separator';

	public function render(&$definition) {
		// Initialise variables.
		$class = null;
		$style = null;
		
		// Separator class name
		$class = (empty($definition[1])) ? 'spacer' : $definition[1];
		// Custom width
		$style = (empty($definition[2])) ? null : ' style="width:' . intval($definition[2]) . 'px;"';
		
		//return '<li class="' . $class . '"' . $style . ">\n</li>\n";
		return "";
	}

	public function fetchButton() {}
}