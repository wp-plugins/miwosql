<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MButtonStandard extends MButton {

	protected $_name = 'Standard';

	public function fetchButton($type = 'Standard', $name = '', $text = '', $task = '', $list = true) {
		$i18n_text = MText::_($text);
		//$class = $this->fetchIconClass($name);
		$class = ($task == 'apply') ? 'button button-primary button-small' : 'add-new-h2';
        //$class = 'button button-primary button-small';
		$doTask = $this->_getCommand($text, $task, $list);
		
		$html = "<a href=\"#\" onclick=\"$doTask\" class=\"$class\">\n";
		$html .= "$i18n_text\n";
		$html .= "</a>\n";
		
		return $html;
	}

	public function fetchId($type = 'Standard', $name = '', $text = '', $task = '', $list = true, $hideMenu = false) {
		return $this->_parent->getName() . '-' . $name;
	}

	protected function _getCommand($name, $task, $list) {
		MHtml::_('behavior.framework');
		$message = MText::_('MLIB_HTML_PLEASE_MAKE_A_SELECTION_FROM_THE_LIST');
		$message = addslashes($message);
		
		if ($list) {
			$cmd = "if (document.adminForm.boxchecked.value==0){alert('$message');}else{ Miwi.submitbutton('$task')}";
		}
		else {
			$cmd = "Miwi.submitbutton('$task')";
		}
		
		return $cmd;
	}
}