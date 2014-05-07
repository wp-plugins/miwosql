<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MButtonConfirm extends MButton {

	protected $_name = 'Confirm';

	public function fetchButton($type = 'Confirm', $msg = '', $name = '', $text = '', $task = '', $list = true, $hideMenu = false) {
		$text = MText::_($text);
		$msg = MText::_($msg, true);
		$class = $this->fetchIconClass($name);
		$doTask = $this->_getCommand($msg, $name, $task, $list);

		$html = "<a href=\"#\" onclick=\"$doTask\" class=\"add-new-h2\">\n";
		$html .= "$text\n";
		$html .= "</a>\n";

		return $html;
	}

	public function fetchId($type = 'Confirm', $msg = '', $name = '', $text = '', $task = '', $list = true, $hideMenu = false) {
		return $this->_parent->getName() . '-' . $name;
	}

	protected function _getCommand($msg, $name, $task, $list) {
		MHtml::_('behavior.framework');
		$message = MText::_('MLIB_HTML_PLEASE_MAKE_A_SELECTION_FROM_THE_LIST');
		$message = addslashes($message);

		if ($list) {
			$cmd = "if (document.adminForm.boxchecked.value==0){alert('$message');}else{if (confirm('$msg')){Miwi.submitbutton('$task');}}";
		}
		else {
			$cmd = "if (confirm('$msg')){Miwi.submitbutton('$task');}";
		}

		return $cmd;
	}
}