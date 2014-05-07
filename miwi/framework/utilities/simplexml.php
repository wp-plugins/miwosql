<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

 defined('MIWI') or die('MIWI');
 
class MSimpleXML extends MObject {

	private $_parser = null;

	public $document = null;

	private $_stack = array();

	public function __construct($options = null) {
		// Deprecation warning.
		MLog::add('MSimpleXML::__construct() is deprecated.', MLog::WARNING, 'deprecated');

		if (! function_exists('xml_parser_create'))
		{
			// TODO throw warning
			return false;
		}

		// Create the parser resource and make sure both versions of PHP autodetect the format.
		$this->_parser = xml_parser_create('');

		// Check parser resource
		xml_set_object($this->_parser, $this);
		xml_parser_set_option($this->_parser, XML_OPTION_CASE_FOLDING, 0);
		if (is_array($options))
		{
			foreach ($options as $option => $value)
			{
				xml_parser_set_option($this->_parser, $option, $value);
			}
		}

		// Set the handlers
		xml_set_element_handler($this->_parser, '_startElement', '_endElement');
		xml_set_character_data_handler($this->_parser, '_characterData');
	}

	public function loadString($string, $classname = null) {
		// Deprecation warning.
		MLog::add('MSimpleXML::loadString() is deprecated.', MLog::WARNING, 'deprecated');

		$this->_parse($string);

		return true;
	}

	public function loadFile($path, $classname = null) {
		// Deprecation warning.
		MLog::add('MSimpleXML::loadfile() is deprecated.', MLog::WARNING, 'deprecated');

		//Check to see of the path exists
		if (!file_exists($path))
		{

			return false;
		}

		//Get the XML document loaded into a variable
		$xml = trim(file_get_contents($path));
		if ($xml == '')
		{
			return false;
		}
		else
		{
			$this->_parse($xml);

			return true;
		}
	}

	public function importDOM($node, $classname = null) {
		// Deprecation warning.
		MLog::add('MSimpleXML::importDOM() is deprecated.', MLog::WARNING, 'deprecated');

		return false;
	}

	public function getParser() {
		// Deprecation warning.
		MLog::add('MSimpleXML::getParser() is deprecated.', MLog::WARNING, 'deprecated');

		return $this->_parser;
	}

	public function setParser($parser) {
		// Deprecation warning.
		MLog::add('MSimpleXML::setParser() is deprecated.', MLog::WARNING, 'deprecated');

		$this->_parser = $parser;
	}

	protected function _parse($data = '') {
		// Deprecation warning.
		MLog::add('MSimpleXML::_parse() is deprecated.', MLog::WARNING, 'deprecated');

		//Error handling
		if (!xml_parse($this->_parser, $data))
		{
			$this->_handleError(
				xml_get_error_code($this->_parser), xml_get_current_line_number($this->_parser),
				xml_get_current_column_number($this->_parser)
			);
		}

		//Free the parser
		xml_parser_free($this->_parser);
	}

	protected function _handleError($code, $line, $col) {
		// Deprecation warning.
		MLog::add('MSimpleXML::_handleError() is deprecated.', MLog::WARNING, 'deprecated');

		MError::raiseWarning('SOME_ERROR_CODE', 'XML Parsing Error at ' . $line . ':' . $col . '. Error ' . $code . ': ' . xml_error_string($code));

	}

	protected function _getStackLocation() {
		// Deprecation warning.
		MLog::add('MSimpleXML::_getStackLocation() is deprecated.', MLog::WARNING, 'deprecated');

		$return = '';
		foreach ($this->_stack as $stack)
		{
			$return .= $stack . '->';
		}

		return rtrim($return, '->');
	}

	protected function _startElement($parser, $name, $attrs = array()) {
		// Deprecation warning.
		MLog::add('MSimpleXML::startElement() is deprecated.', MLog::WARNING, 'deprecated');

		//  Check to see if tag is root-level
		$count = count($this->_stack);
		if ($count == 0)
		{
			// If so, set the document as the current tag
			$classname = get_class($this) . 'Element';
			$this->document = new $classname($name, $attrs);

			// And start out the stack with the document tag
			$this->_stack = array('document');
		}
		// If it isn't root level, use the stack to find the parent
		else
		{
			// Get the name which points to the current direct parent, relative to $this
			$parent = $this->_getStackLocation();

			// Add the child
			eval('$this->' . $parent . '->addChild($name, $attrs, ' . $count . ');');

			// Update the stack
			eval('$this->_stack[] = $name.\'[\'.(count($this->' . $parent . '->' . $name . ') - 1).\']\';');
		}
	}

	protected function _endElement($parser, $name) {
		// Deprecation warning.
		MLog::add('MSimpleXML::endElement() is deprecated.', MLog::WARNING, 'deprecated');

		//Update stack by removing the end value from it as the parent
		array_pop($this->_stack);
	}

	protected function _characterData($parser, $data) {
		// Deprecation warning.
		MLog::add('MSimpleXML::_characterData() is deprecated.', MLog::WARNING, 'deprecated');

		// Get the reference to the current parent object
		$tag = $this->_getStackLocation();

		// Assign data to it
		eval('$this->' . $tag . '->_data .= $data;');
	}
}

class MSimpleXMLElement extends MObject {

	public $_attributes = array();
	public $_name = '';
	public $_data = '';
	public $_children = array();
	public $_level = 0;

	public function __construct($name, $attrs = array(), $level = 0) {
		// Deprecation warning.
		MLog::add('MSimpleXMLElement::__construct() is deprecated.', MLog::WARNING, 'deprecated');

		//Make the keys of the attr array lower case, and store the value
		$this->_attributes = array_change_key_case($attrs, CASE_LOWER);

		//Make the name lower case and store the value
		$this->_name = strtolower($name);

		//Set the level
		$this->_level = $level;
	}

	public function name() {
		// Deprecation warning.
		MLog::add('MSimpleXMLElement::name() is deprecated.', MLog::WARNING, 'deprecated');

		return $this->_name;
	}

	public function attributes($attribute = null) {
		// Deprecation warning.
		MLog::add('MSimpleXMLElement::attributes() is deprecated.', MLog::WARNING, 'deprecated');

		if (!isset($attribute))
		{
			return $this->_attributes;
		}

		return isset($this->_attributes[$attribute]) ? $this->_attributes[$attribute] : null;
	}

	public function data() {
		// Deprecation warning.
		MLog::add('MSimpleXMLElement::data() is deprecated.', MLog::WARNING, 'deprecated');

		return $this->_data;
	}

	public function setData($data) {
		// Deprecation warning.
		MLog::add('MSimpleXMLElement::data() is deprecated.', MLog::WARNING, 'deprecated');

		$this->_data = $data;
	}

	public function children() {
		// Deprecation warning.
		MLog::add('MSimpleXMLElement::children() is deprecated.', MLog::WARNING, 'deprecated');

		return $this->_children;
	}
	
	public function level() {
		// Deprecation warning.
		MLog::add('MSimpleXMLElement::level() is deprecated.', MLog::WARNING, 'deprecated');

		return $this->_level;
	}

	public function addAttribute($name, $value) {
		// Deprecation warning.
		MLog::add('MSimpleXMLElement::addAttribute() is deprecated.', MLog::WARNING, 'deprecated');

		// Add the attribute to the element, override if it already exists
		$this->_attributes[$name] = $value;
	}

	public function removeAttribute($name) {
		// Deprecation warning.
		MLog::add('MSimpleXMLElement::removeAttribute() is deprecated.', MLog::WARNING, 'deprecated');

		unset($this->_attributes[$name]);
	}

	public function addChild($name, $attrs = array(), $level = null) {
		// Deprecation warning.
		MLog::add('MSimpleXMLElement::addChild() is deprecated.', MLog::WARNING, 'deprecated');

		//If there is no array already set for the tag name being added,
		//create an empty array for it
		if (!isset($this->$name))
		{
			$this->$name = array();
		}

		// set the level if not already specified
		if ($level == null)
		{
			$level = ($this->_level + 1);
		}

		//Create the child object itself
		$classname = get_class($this);
		$child = new $classname($name, $attrs, $level);

		//Add the reference of it to the end of an array member named for the elements name
		$this->{$name}[] = &$child;

		//Add the reference to the children array member
		$this->_children[] = &$child;

		//return the new child
		return $child;
	}

	public function removeChild(&$child) {
		// Deprecation warning.
		MLog::add('MSimpleXMLElement::removeChild() is deprecated.', MLog::WARNING, 'deprecated');

		$name = $child->name();
		for ($i = 0, $n = count($this->_children); $i < $n; $i++)
		{
			if ($this->_children[$i] == $child)
			{
				unset($this->_children[$i]);
			}
		}
		for ($i = 0, $n = count($this->{$name}); $i < $n; $i++)
		{
			if ($this->{$name}[$i] == $child)
			{
				unset($this->{$name}[$i]);
			}
		}
		$this->_children = array_values($this->_children);
		$this->{$name} = array_values($this->{$name});
		unset($child);
	}

	public function getElementByPath($path) {
		// Deprecation warning.
		MLog::add('MSimpleXMLElement::getElementByPath() is deprecated.', MLog::WARNING, 'deprecated');

		$tmp	= &$this;
		$parts	= explode('/', trim($path, '/'));

		foreach ($parts as $node)
		{
			$found = false;
			foreach ($tmp->_children as $child)
			{
				if (strtoupper($child->_name) == strtoupper($node))
				{
					$tmp = &$child;
					$found = true;
					break;
				}
			}
			if (!$found)
			{
				break;
			}
		}

		if ($found)
		{
			return $tmp;
		}

		return false;
	}

	public function map($callback, $args = array()) {
		// Deprecation warning.
		MLog::add('MSimpleXMLElement::map) is deprecated.', MLog::WARNING, 'deprecated');

		$callback($this, $args);
		// Map to all children
		if ($n = count($this->_children))
		{
			for ($i = 0; $i < $n; $i++)
			{
				$this->_children[$i]->map($callback, $args);
			}
		}
	}

	public function toString($whitespace = true) {
		// Deprecation warning.
		MLog::add('MSimpleXMLElement::toString() is deprecated.', MLog::WARNING, 'deprecated');

		// Start a new line, indent by the number indicated in $this->level, add a <, and add the name of the tag
		if ($whitespace)
		{
			$out = "\n" . str_repeat("\t", $this->_level) . '<' . $this->_name;
		}
		else
		{
			$out = '<' . $this->_name;
		}

		// For each attribute, add attr="value"
		foreach ($this->_attributes as $attr => $value)
		{
			$out .= ' ' . $attr . '="' . htmlspecialchars($value, ENT_COMPAT, 'UTF-8') . '"';
		}

		// If there are no children and it contains no data, end it off with a />
		if (empty($this->_children) && empty($this->_data))
		{
			$out .= " />";
		}
		// Otherwise...
		else
		{
			// If there are children
			if (!empty($this->_children))
			{
				// Close off the start tag
				$out .= '>';

				// For each child, call the asXML function (this will ensure that all children are added recursively)
				foreach ($this->_children as $child)
				{
					$out .= $child->toString($whitespace);
				}

				// Add the newline and indentation to go along with the close tag
				if ($whitespace)
				{
					$out .= "\n" . str_repeat("\t", $this->_level);
				}
			}
			// If there is data, close off the start tag and add the data
			elseif (!empty($this->_data))
				$out .= '>' . htmlspecialchars($this->_data, ENT_COMPAT, 'UTF-8');

			// Add the end tag
			$out .= '</' . $this->_name . '>';
		}

		//Return the final output
		return $out;
	}
}