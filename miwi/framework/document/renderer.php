<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MDocumentRenderer extends MObject
{
	protected	$_doc = null;

	protected $_mime = "text/html";

	public function __construct(&$doc) {
		$this->_doc = &$doc;
	}

	public function render($name, $params = null, $content = null) { }

	public function getContentType() {
		return $this->_mime;
	}
}
