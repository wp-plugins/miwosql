<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MButtonCustom extends MButton {

	protected $_name = 'Custom';

	public function fetchButton($type = 'Custom', $html = '', $id = 'custom') {
		return $html;
	}

	public function fetchId($type = 'Custom', $html = '', $id = 'custom') {
		return $this->_parent->getName() . '-' . $id;
	}
}