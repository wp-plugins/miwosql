<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MButtonLink extends MButton {

	protected $_name = 'Link';

	public function fetchButton($type = 'Link', $name = 'back', $text = '', $url = null) {
		$text = MText::_($text);
		$class = $this->fetchIconClass($name);
		$doTask = $this->_getCommand($url);
		
		$html = "<a class=\"add-new-h2\" href=\"$doTask\">\n";
		$html .= "$text\n";
		$html .= "</a>\n";
		
		return $html;
	}

	public function fetchId($type = 'Link', $name = '') {
		return $this->_parent->getName() . '-' . $name;
	}

	protected function _getCommand($url) {
		return MRoute::_($url);
	}
}