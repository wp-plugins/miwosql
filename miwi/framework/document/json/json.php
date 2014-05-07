<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MDocumentJSON extends MDocument {

	protected $_name = 'joomla';

	public function __construct($options = array()) {
		parent::__construct($options);

		// Set mime type
		$this->_mime = 'application/json';

		// Set document type
		$this->_type = 'json';
	}

	public function render($cache = false, $params = array()) {
		MResponse::allowCache(false);
		MResponse::setHeader('Content-disposition', 'attachment; filename="' . $this->getName() . '.json"', true);

		parent::render();

		return $this->getBuffer();
	}

	public function getName() {
		return $this->_name;
	}

	public function setName($name = 'joomla') {
		$this->_name = $name;

		return $this;
	}
}
