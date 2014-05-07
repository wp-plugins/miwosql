<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MButtonHelp extends MButton {

	protected $_name = 'Help';

	public function fetchButton($type = 'Help', $ref = '', $com = false, $override = null, $component = null) {
		$text = MText::_('MTOOLBAR_HELP');
		$class = $this->fetchIconClass('help');
		$doTask = $this->_getCommand($ref, $com, $override, $component);
		
		$html = "<a href=\"#\" onclick=\"$doTask\" rel=\"help\" class=\"add-new-h2\">\n";
		$html .= "$text\n";
		$html .= "</a>\n";
		
		return $html;
	}

	public function fetchId() {
		return $this->_parent->getName() . '-' . "help";
	}

	protected function _getCommand($ref, $com, $override, $component) {
		// Get Help URL
		mimport('framework.language.help');
		$url = MHelp::createURL($ref, $com, $override, $component);
		$url = htmlspecialchars($url, ENT_QUOTES);
		$cmd = "Miwi.popupWindow('$url', '" . MText::_('MHELP', true) . "', 700, 500, 1)";
		
		return $cmd;
	}
}