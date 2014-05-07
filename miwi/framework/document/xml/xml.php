<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MDocumentXml extends MDocument {

	protected $_name = 'joomla';

	public function __construct($options = array())
	{
		parent::__construct($options);

		$this->_mime = 'application/xml';

		$this->_type = 'xml';
	}

	public function render($cache = false, $params = array())
	{
		parent::render();
		MResponse::setHeader('Content-disposition', 'inline; filename="' . $this->getName() . '.xml"', true);

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
