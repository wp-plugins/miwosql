<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MButtonPopup extends MButton {

	protected $_name = 'Popup';

	public function fetchButton($type = 'Popup', $name = '', $text = '', $url = '', $width = 640, $height = 480, $top = 0, $left = 0, $onClose = '') {
		MHtml::_('behavior.modal');
		
		$text = MText::_($text);
		$class = $this->fetchIconClass($name);
		$doTask = $this->_getCommand($name, $url, $width, $height, $top, $left);
		
		$html = "<a class=\"modal add-new-h2\" href=\"$doTask\" rel=\"{handler: 'iframe', size: {x: $width, y: $height}, onClose: function() {" . $onClose . "}}\">\n";
        //$html = "<a class=\"modal button button-primary button-small\" href=\"$doTask\" rel=\"{handler: 'iframe', size: {x: $width, y: $height}, onClose: function() {" . $onClose . "}}\">\n";
		$html .= "$text\n";
		$html .= "</a>\n";
		
		return $html;
	}

	public function fetchId($type, $name) {
		return $this->_parent->getName() . '-' . "popup-$name";
	}

	protected function _getCommand($name, $url, $width, $height, $top, $left) {
		if (substr($url, 0, 4) !== 'http') {
			$url = MUri::base() . $url;
		}
		
		return $url;
	}
}