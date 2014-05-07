<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MDocumentRendererMessage extends MDocumentRenderer {

	public function render($name, $params = array (), $content = null) {
		// Initialise variables.
		$buffer = null;
		$lists = null;

		// Get the message queue
		$messages = MFactory::getApplication()->getMessageQueue();

		// Build the sorted message list
		if (is_array($messages) && !empty($messages)) {
			foreach ($messages as $msg) {
				if (isset($msg['type']) && isset($msg['message'])) {
					$lists[$msg['type']][] = $msg['message'];
				}
			}
		}

		// If messages exist render them
		if (is_array($lists)) {
			foreach ($lists as $type => $msgs) {
                $class = 'updated';
				if($type == 'error' or $type == 'warning'){
                    $class = 'error';
                }

                if (count($msgs)) {
                    foreach ($msgs as $msg) {
					    $buffer .= "\n".'<div id="message" class="'.$class.'">';
                        $buffer .= "\n\t\t".'<p>' . $msg . '</p>';
                        $buffer .= "</div>";
					}
				}
			}
		}
		return $buffer;
	}
}
