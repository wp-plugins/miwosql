<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/
defined('MIWI') or die('MIWI');

class MDatabaseQueryMySQL extends MDatabaseQuery {

	public function concatenate($values, $separator = null) {
		if ($separator) {
			$concat_string = 'CONCAT_WS(' . $this->quote($separator);

			foreach ($values as $value) {
				$concat_string .= ', ' . $value;
			}

			return $concat_string . ')';
		}
		else {
			return 'CONCAT(' . implode(',', $values) . ')';
		}
	}
}