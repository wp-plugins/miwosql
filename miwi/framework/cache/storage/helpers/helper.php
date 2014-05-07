<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MCacheStorageHelper {

	public $group = '';
	public $size = 0;
	public $count = 0;

	public function __construct($group) {
		$this->group = $group;
	}

	public function updateSize($size) {
		$this->size = number_format($this->size + $size, 2, '.', '');
		$this->count++;
	}
}