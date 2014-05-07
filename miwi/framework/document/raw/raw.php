<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MDocumentRaw extends MDocument
{

	public function __construct($options = array()) {
		parent::__construct($options);

		// Set mime type
		$this->_mime = 'text/html';

		// Set document type
		$this->_type = 'raw';
	}

	public function render($cache = false, $params = array()) {
		parent::render();
		return $this->getBuffer();
	}
}
