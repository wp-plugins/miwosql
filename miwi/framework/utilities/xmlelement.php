<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MXMLElement extends SimpleXMLElement
{

	public function name()
	{
		MLog::add('MXMLElement::name() is deprecated, use SimpleXMLElement::getName() instead.', MLog::WARNING, 'deprecated');
		return (string) $this->getName();
	}

	public function data()
	{
		// Deprecation warning.
		MLog::add('MXMLElement::data() is deprecated.', MLog::WARNING, 'deprecated');

		return (string) $this;
	}

	public function getAttribute($name)
	{
		// Deprecation warning.
		MLog::add('MXMLelement::getAttributes() is deprecated.', MLog::WARNING, 'deprecated');

		return (string) $this->attributes()->$name;
	}

	public function asFormattedXML($compressed = false, $indent = "\t", $level = 0)
	{
		MLog::add('MXMLElement::asFormattedXML() is deprecated, use SimpleXMLElement::asXML() instead.', MLog::WARNING, 'deprecated');
		$out = '';

		// Start a new line, indent by the number indicated in $level
		$out .= ($compressed) ? '' : "\n" . str_repeat($indent, $level);

		// Add a <, and add the name of the tag
		$out .= '<' . $this->getName();

		// For each attribute, add attr="value"
		foreach ($this->attributes() as $attr)
		{
			$out .= ' ' . $attr->getName() . '="' . htmlspecialchars((string) $attr, ENT_COMPAT, 'UTF-8') . '"';
		}

		// If there are no children and it contains no data, end it off with a />
		if (!count($this->children()) && !(string) $this)
		{
			$out .= " />";
		}
		else
		{
			// If there are children
			if (count($this->children()))
			{
				// Close off the start tag
				$out .= '>';

				$level++;

				// For each child, call the asFormattedXML function (this will ensure that all children are added recursively)
				foreach ($this->children() as $child)
				{
					$out .= $child->asFormattedXML($compressed, $indent, $level);
				}

				$level--;

				// Add the newline and indentation to go along with the close tag
				$out .= ($compressed) ? '' : "\n" . str_repeat($indent, $level);

			}
			elseif ((string) $this)
			{
				// If there is data, close off the start tag and add the data
				$out .= '>' . htmlspecialchars((string) $this, ENT_COMPAT, 'UTF-8');
			}

			// Add the end tag
			$out .= '</' . $this->getName() . '>';
		}

		return $out;
	}
}