<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

abstract class MEvent extends MObject {

	protected $_subject = null;

	public function __construct(&$subject) {
		// Register the observer ($this) so we can be notified
		$subject->attach($this);

		// Set the subject to observe
		$this->_subject = &$subject;
	}

	public function update(&$args) {
		// First let's get the event from the argument array.  Next we will unset the
		// event argument as it has no bearing on the method to handle the event.
		$event = $args['event'];
		unset($args['event']);

		if (method_exists($this, $event)) {
			return call_user_func_array(array($this, $event), $args);
		}
		else {
			return null;
		}
	}
}