<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

mimport('framework.filesystem.path');
mimport('framework.utilities.arrayhelper');

class MForm {

	protected        $data;
	protected        $errors  = array();
	protected        $name;
	protected        $options = array();
	protected        $xml;
	protected static $forms   = array();

	public function __construct($name, array $options = array()) {
		// Set the name for the form.
		$this->name = $name;

		// Initialise the MRegistry data.
		$this->data = new MRegistry;

		// Set the options if specified.
		$this->options['control'] = isset($options['control']) ? $options['control'] : false;
	}

	public function bind($data) {
		// Make sure there is a valid MForm XML document.
		if (!($this->xml instanceof SimpleXMLElement)) {
			return false;
		}

		// The data must be an object or array.
		if (!is_object($data) && !is_array($data)) {
			return false;
		}

		// Convert the input to an array.
		if (is_object($data)) {
			if ($data instanceof MRegistry) {
				// Handle a MRegistry.
				$data = $data->toArray();
			}
			elseif ($data instanceof MObject) {
				// Handle a MObject.
				$data = $data->getProperties();
			}
			else {
				// Handle other types of objects.
				$data = (array)$data;
			}
		}

		// Process the input data.
		foreach ($data as $k => $v) {

			if ($this->findField($k)) {
				// If the field exists set the value.
				$this->data->set($k, $v);
			}
			elseif (is_object($v) || MArrayHelper::isAssociative($v)) {
				// If the value is an object or an associative array hand it off to the recursive bind level method.
				$this->bindLevel($k, $v);
			}
		}

		return true;
	}

	protected function bindLevel($group, $data) {
		// Ensure the input data is an array.
		settype($data, 'array');

		// Process the input data.
		foreach ($data as $k => $v) {

			if ($this->findField($k, $group)) {
				// If the field exists set the value.
				$this->data->set($group.'.'.$k, $v);
			}
			elseif (is_object($v) || MArrayHelper::isAssociative($v)) {
				// If the value is an object or an associative array, hand it off to the recursive bind level method
				$this->bindLevel($group.'.'.$k, $v);
			}
		}
	}

	public function filter($data, $group = null) {
		// Make sure there is a valid MForm XML document.
		if (!($this->xml instanceof SimpleXMLElement)) {
			return false;
		}

		// Initialise variables.
		$input  = new MRegistry($data);
		$output = new MRegistry;

		// Get the fields for which to filter the data.
		$fields = $this->findFieldsByGroup($group);
		if (!$fields) {
			// PANIC!
			return false;
		}

		// Filter the fields.
		foreach ($fields as $field) {
			// Initialise variables.
			$name = (string)$field['name'];

			// Get the field groups for the element.
			$attrs  = $field->xpath('ancestor::fields[@name]/@name');
			$groups = array_map('strval', $attrs ? $attrs : array());
			$group  = implode('.', $groups);

			// Get the field value from the data input.
			if ($group) {
				// Filter the value if it exists.
				if ($input->exists($group.'.'.$name)) {
					$output->set($group.'.'.$name, $this->filterField($field, $input->get($group.'.'.$name, (string)$field['default'])));
				}
			}
			else {
				// Filter the value if it exists.
				if ($input->exists($name)) {
					$output->set($name, $this->filterField($field, $input->get($name, (string)$field['default'])));
				}
			}
		}

		return $output->toArray();
	}

	public function getErrors() {
		return $this->errors;
	}

	public function getField($name, $group = null, $value = null) {
		// Make sure there is a valid MForm XML document.
		if (!($this->xml instanceof SimpleXMLElement)) {
			return false;
		}

		// Attempt to find the field by name and group.
		$element = $this->findField($name, $group);

		// If the field element was not found return false.
		if (!$element) {
			return false;
		}

		return $this->loadField($element, $group, $value);
	}

	public function getFieldAttribute($name, $attribute, $default = null, $group = null) {
		// Make sure there is a valid MForm XML document.
		if (!($this->xml instanceof SimpleXMLElement)) {
			// TODO: throw exception.

			return $default;
		}

		// Find the form field element from the definition.
		$element = $this->findField($name, $group);

		// If the element exists and the attribute exists for the field return the attribute value.
		if (($element instanceof SimpleXMLElement) && ((string)$element[ $attribute ])) {
			return (string)$element[ $attribute ];
		}
		// Otherwise return the given default value.
		else {
			return $default;
		}
	}

	public function getFieldset($set = null) {
		// Initialise variables.
		$fields = array();

		// Get all of the field elements in the fieldset.
		if ($set) {
			$elements = $this->findFieldsByFieldset($set);
		}
		// Get all fields.
		else {
			$elements = $this->findFieldsByGroup();
		}

		// If no field elements were found return empty.
		if (empty($elements)) {
			return $fields;
		}

		// Build the result array from the found field elements.
		foreach ($elements as $element) {
			// Get the field groups for the element.
			$attrs  = $element->xpath('ancestor::fields[@name]/@name');
			$groups = array_map('strval', $attrs ? $attrs : array());
			$group  = implode('.', $groups);

			// If the field is successfully loaded add it to the result array.
			if ($field = $this->loadField($element, $group)) {
				$fields[ $field->id ] = $field;
			}
		}

		return $fields;
	}

	public function getFieldsets($group = null) {
		// Initialise variables.
		$fieldsets = array();
		$sets      = array();

		// Make sure there is a valid MForm XML document.
		if (!($this->xml instanceof SimpleXMLElement)) {
			return $fieldsets;
		}

		if ($group) {
			// Get the fields elements for a given group.
			$elements = & $this->findGroup($group);

			foreach ($elements as &$element) {
				// Get an array of <fieldset /> elements and fieldset attributes within the fields element.
				if ($tmp = $element->xpath('descendant::fieldset[@name] | descendant::field[@fieldset]/@fieldset')) {
					$sets = array_merge($sets, (array)$tmp);
				}
			}
		}
		else {
			// Get an array of <fieldset /> elements and fieldset attributes.
			$sets = $this->xml->xpath('//fieldset[@name] | //field[@fieldset]/@fieldset');
		}

		// If no fieldsets are found return empty.
		if (empty($sets)) {

			return $fieldsets;
		}

		// Process each found fieldset.
		foreach ($sets as $set) {
			// Are we dealing with a fieldset element?
			if ((string)$set['name']) {

				// Only create it if it doesn't already exist.
				if (empty($fieldsets[ (string)$set['name'] ])) {

					// Build the fieldset object.
					$fieldset = (object)array('name' => '', 'label' => '', 'description' => '');
					foreach ($set->attributes() as $name => $value) {
						$fieldset->$name = (string)$value;
					}

					// Add the fieldset object to the list.
					$fieldsets[ $fieldset->name ] = $fieldset;
				}
			}
			// Must be dealing with a fieldset attribute.
			else {

				// Only create it if it doesn't already exist.
				if (empty($fieldsets[ (string)$set ])) {

					// Attempt to get the fieldset element for data (throughout the entire form document).
					$tmp = $this->xml->xpath('//fieldset[@name="'.(string)$set.'"]');

					// If no element was found, build a very simple fieldset object.
					if (empty($tmp)) {
						$fieldset = (object)array('name' => (string)$set, 'label' => '', 'description' => '');
					}
					// Build the fieldset object from the element.
					else {
						$fieldset = (object)array('name' => '', 'label' => '', 'description' => '');
						foreach ($tmp[0]->attributes() as $name => $value) {
							$fieldset->$name = (string)$value;
						}
					}

					// Add the fieldset object to the list.
					$fieldsets[ $fieldset->name ] = $fieldset;
				}
			}
		}

		return $fieldsets;
	}

	public function getFormControl() {
		return (string)$this->options['control'];
	}

	public function getGroup($group, $nested = false) {
		// Initialise variables.
		$fields = array();

		// Get all of the field elements in the field group.
		$elements = $this->findFieldsByGroup($group, $nested);

		// If no field elements were found return empty.
		if (empty($elements)) {
			return $fields;
		}

		// Build the result array from the found field elements.
		foreach ($elements as $element) {
			// Get the field groups for the element.
			$attrs  = $element->xpath('ancestor::fields[@name]/@name');
			$groups = array_map('strval', $attrs ? $attrs : array());
			$group  = implode('.', $groups);

			// If the field is successfully loaded add it to the result array.
			if ($field = $this->loadField($element, $group)) {
				$fields[ $field->id ] = $field;
			}
		}

		return $fields;
	}

	public function getInput($name, $group = null, $value = null) {
		// Attempt to get the form field.
		if ($field = $this->getField($name, $group, $value)) {
			return $field->input;
		}

		return '';
	}

	public function getLabel($name, $group = null) {
		// Attempt to get the form field.
		if ($field = $this->getField($name, $group)) {
			return $field->label;
		}

		return '';
	}

	public function getName() {
		return $this->name;
	}

	public function getValue($name, $group = null, $default = null) {
		// If a group is set use it.
		if ($group) {
			$return = $this->data->get($group.'.'.$name, $default);
		}
		else {
			$return = $this->data->get($name, $default);
		}

		return $return;
	}

	public function load($data, $replace = true, $xpath = false) {
		// If the data to load isn't already an XML element or string return false.
		if ((!($data instanceof SimpleXMLElement)) && (!is_string($data))) {
			return false;
		}

		// Attempt to load the XML if a string.
		if (is_string($data)) {
			$data = MFactory::getXML($data, false);

			// Make sure the XML loaded correctly.
			if (!$data) {
				return false;
			}
		}

		// If we have no XML definition at this point let's make sure we get one.
		if (empty($this->xml)) {
			// If no XPath query is set to search for fields, and we have a <form />, set it and return.
			if (!$xpath && ($data->getName() == 'form')) {
				$this->xml = $data;

				// Synchronize any paths found in the load.
				$this->syncPaths();

				return true;
			}
			// Create a root element for the form.
			else {
				$this->xml = new MXMLElement('<form></form>');
			}
		}

		// Get the XML elements to load.
		$elements = array();
		if ($xpath) {
			$elements = $data->xpath($xpath);
		}
		elseif ($data->getName() == 'form') {
			$elements = $data->children();
		}

		// If there is nothing to load return true.
		if (empty($elements)) {
			return true;
		}

		// Load the found form elements.
		foreach ($elements as $element) {
			// Get an array of fields with the correct name.
			$fields = $element->xpath('descendant-or-self::field');
			foreach ($fields as $field) {
				// Get the group names as strings for ancestor fields elements.
				$attrs  = $field->xpath('ancestor::fields[@name]/@name');
				$groups = array_map('strval', $attrs ? $attrs : array());

				// Check to see if the field exists in the current form.
				if ($current = $this->findField((string)$field['name'], implode('.', $groups))) {

					// If set to replace found fields, replace the data and remove the field so we don't add it twice.
					if ($replace) {
						$olddom    = dom_import_simplexml($current);
						$loadeddom = dom_import_simplexml($field);
						$addeddom  = $olddom->ownerDocument->importNode($loadeddom);
						$olddom->parentNode->replaceChild($addeddom, $olddom);
						$loadeddom->parentNode->removeChild($loadeddom);
					}
					else {
						unset($field);
					}
				}
			}

			// Merge the new field data into the existing XML document.
			self::addNode($this->xml, $element);
		}

		// Synchronize any paths found in the load.
		$this->syncPaths();

		return true;
	}

	public function loadFile($file, $reset = true, $xpath = false) {
		// Check to see if the path is an absolute path.
		if (!is_file($file)) {

			// Not an absolute path so let's attempt to find one using MPath.
			$file = MPath::find(self::addFormPath(), strtolower($file).'.xml');

			// If unable to find the file return false.
			if (!$file) {
				return false;
			}
		}
		// Attempt to load the XML file.
		$xml = MFactory::getXML($file, true);

		return $this->load($xml, $reset, $xpath);
	}

	public function removeField($name, $group = null) {
		// Make sure there is a valid MForm XML document.
		if (!($this->xml instanceof SimpleXMLElement)) {
			// TODO: throw exception.
			return false;
		}

		// Find the form field element from the definition.
		$element = $this->findField($name, $group);

		// If the element exists remove it from the form definition.
		if ($element instanceof SimpleXMLElement) {
			$dom = dom_import_simplexml($element);
			$dom->parentNode->removeChild($dom);
		}

		return true;
	}

	public function removeGroup($group) {
		// Make sure there is a valid MForm XML document.
		if (!($this->xml instanceof SimpleXMLElement)) {
			// TODO: throw exception.
			return false;
		}

		// Get the fields elements for a given group.
		$elements = & $this->findGroup($group);
		foreach ($elements as &$element) {
			$dom = dom_import_simplexml($element);
			$dom->parentNode->removeChild($dom);
		}

		return true;
	}

	public function reset($xml = false) {
		unset($this->data);
		$this->data = new MRegistry;

		if ($xml) {
			unset($this->xml);
			$this->xml = new MXMLElement('<form></form>');
		}

		return true;
	}

	public function setField(&$element, $group = null, $replace = true) {
		// Make sure there is a valid MForm XML document.
		if (!($this->xml instanceof SimpleXMLElement)) {
			// TODO: throw exception.

			return false;
		}

		// Make sure the element to set is valid.
		if (!($element instanceof SimpleXMLElement)) {
			// TODO: throw exception.

			return false;
		}

		// Find the form field element from the definition.
		$old = & $this->findField((string)$element['name'], $group);

		// If an existing field is found and replace flag is false do nothing and return true.
		if (!$replace && !empty($old)) {

			return true;
		}

		// If an existing field is found and replace flag is true remove the old field.
		if ($replace && !empty($old) && ($old instanceof SimpleXMLElement)) {
			$dom = dom_import_simplexml($old);
			$dom->parentNode->removeChild($dom);
		}

		// If no existing field is found find a group element and add the field as a child of it.
		if ($group) {

			// Get the fields elements for a given group.
			$fields = & $this->findGroup($group);

			// If an appropriate fields element was found for the group, add the element.
			if (isset($fields[0]) && ($fields[0] instanceof SimpleXMLElement)) {
				self::addNode($fields[0], $element);
			}
		}
		else {
			// Set the new field to the form.
			self::addNode($this->xml, $element);
		}

		// Synchronize any paths found in the load.
		$this->syncPaths();

		return true;
	}

	public function setFieldAttribute($name, $attribute, $value, $group = null) {
		// Make sure there is a valid MForm XML document.
		if (!($this->xml instanceof SimpleXMLElement)) {
			// TODO: throw exception.

			return false;
		}

		// Find the form field element from the definition.
		$element = $this->findField($name, $group);

		// If the element doesn't exist return false.
		if (!($element instanceof SimpleXMLElement)) {

			return false;
		}
		// Otherwise set the attribute and return true.
		else {
			$element[ $attribute ] = $value;

			// Synchronize any paths found in the load.
			$this->syncPaths();

			return true;
		}
	}

	public function setFields(&$elements, $group = null, $replace = true) {
		// Make sure there is a valid MForm XML document.
		if (!($this->xml instanceof SimpleXMLElement)) {
			// TODO: throw exception.

			return false;
		}

		// Make sure the elements to set are valid.
		foreach ($elements as $element) {
			if (!($element instanceof SimpleXMLElement)) {
				// TODO: throw exception.

				return false;
			}
		}

		// Set the fields.
		$return = true;
		foreach ($elements as $element) {
			if (!$this->setField($element, $group, $replace)) {

				$return = false;
			}
		}

		// Synchronize any paths found in the load.
		$this->syncPaths();

		return $return;
	}

	public function setValue($name, $group = null, $value = null) {
		// If the field does not exist return false.
		if (!$this->findField($name, $group)) {
			return false;
		}

		// If a group is set use it.
		if ($group) {
			$this->data->set($group.'.'.$name, $value);
		}
		else {
			$this->data->set($name, $value);
		}

		return true;
	}

	public function validate($data, $group = null) {
		// Make sure there is a valid MForm XML document.
		if (!($this->xml instanceof SimpleXMLElement)) {
			return false;
		}

		// Initialise variables.
		$return = true;

		// Create an input registry object from the data to validate.
		$input = new MRegistry($data);

		// Get the fields for which to validate the data.
		$fields = $this->findFieldsByGroup($group);
		if (!$fields) {
			// PANIC!
			return false;
		}

		// Validate the fields.
		foreach ($fields as $field) {
			// Initialise variables.
			$value = null;
			$name  = (string)$field['name'];

			// Get the group names as strings for ancestor fields elements.
			$attrs  = $field->xpath('ancestor::fields[@name]/@name');
			$groups = array_map('strval', $attrs ? $attrs : array());
			$group  = implode('.', $groups);

			// Get the value from the input data.
			if ($group) {
				$value = $input->get($group.'.'.$name);
			}
			else {
				$value = $input->get($name);
			}

			// Validate the field.
			$valid = $this->validateField($field, $group, $value, $input);

			// Check for an error.
			if ($valid instanceof Exception) {
				switch ($valid->get('level')) {
					case E_ERROR:
						MError::raiseWarning(0, $valid->getMessage());

						return false;
						break;

					default:
						array_push($this->errors, $valid);
						$return = false;
						break;
				}
			}
		}

		return $return;
	}

	protected function filterField($element, $value) {
		// Make sure there is a valid SimpleXMLElement.
		if (!($element instanceof SimpleXMLElement)) {
			return false;
		}

		// Get the field filter type.
		$filter = (string)$element['filter'];

		// Process the input value based on the filter.
		$return = null;

		switch (strtoupper($filter)) {
			// Access Control Rules.
			case 'RULES':
				$return = array();
				foreach ((array)$value as $action => $ids) {
					// Build the rules array.
					$return[ $action ] = array();
					foreach ($ids as $id => $p) {
						if ($p !== '') {
							$return[ $action ][ $id ] = ($p == '1' || $p == 'true') ? true : false;
						}
					}
				}
				break;

			// Do nothing, thus leaving the return value as null.
			case 'UNSET':
				break;

			// No Filter.
			case 'RAW':
				$return = $value;
				break;

			// Filter the input as an array of integers.
			case 'INT_ARRAY':
				// Make sure the input is an array.
				if (is_object($value)) {
					$value = get_object_vars($value);
				}
				$value = is_array($value) ? $value : array($value);

				MArrayHelper::toInteger($value);
				$return = $value;
				break;

			// Filter safe HTML.
			case 'SAFEHTML':
				$return = MFilterInput::getInstance(null, null, 1, 1)->clean($value, 'string');
				break;

			// Convert a date to UTC based on the server timezone offset.
			case 'SERVER_UTC':
				if (intval($value) > 0) {
					// Get the server timezone setting.
					$offset = MFactory::getConfig()->get('offset');

					// Return an SQL formatted datetime string in UTC.
					$return = MFactory::getDate($value, $offset)->toSql();
				}
				else {
					$return = '';
				}
				break;

			// Convert a date to UTC based on the user timezone offset.
			case 'USER_UTC':
				if (intval($value) > 0) {
					// Get the user timezone setting defaulting to the server timezone setting.
					$offset = MFactory::getUser()->getParam('timezone', MFactory::getConfig()->get('offset'));

					// Return a MySQL formatted datetime string in UTC.
					$return = MFactory::getDate($value, $offset)->toSql();
				}
				else {
					$return = '';
				}
				break;

			// Ensures a protocol is present in the saved field. Only use when
			// the only permitted protocols requre '://'. See MFormRuleUrl for list of these.

			case 'URL':
				if (empty($value)) {
					return false;
				}
				$value = MFilterInput::getInstance()->clean($value, 'html');
				$value = trim($value);

				// <>" are never valid in a uri see http://www.ietf.org/rfc/rfc1738.txt.
				$value = str_replace(array('<', '>', '"'), '', $value);

				// Check for a protocol
				$protocol = parse_url($value, PHP_URL_SCHEME);

				// If there is no protocol and the relative option is not specified,
				// we assume that it is an external URL and prepend http://.
				if (($element['type'] == 'url' && !$protocol && !$element['relative'])
				    || (!$element['type'] == 'url' && !$protocol)
				) {
					$protocol = 'http';
					// If it looks like an internal link, then add the root.
					if (substr($value, 0) == 'index.php') {
						$value = MURI::root().$value;
					}

					// Otherwise we treat it is an external link.
					// Put the url back together.
					$value = $protocol.'://'.$value;
				}

				// If relative URLS are allowed we assume that URLs without protocols are internal.
				elseif (!$protocol && $element['relative']) {
					$host = MURI::getInstance('SERVER')->gethost();

					// If it starts with the host string, just prepend the protocol.
					if (substr($value, 0) == $host) {
						$value = 'http://'.$value;
					}
					// Otherwise prepend the root.
					else {
						$value = MURI::root().$value;
					}
				}

				$return = $value;
				break;

			case 'TEL':
				$value = trim($value);
				// Does it match the NANP pattern?
				if (preg_match('/^(?:\+?1[-. ]?)?\(?([2-9][0-8][0-9])\)?[-. ]?([2-9][0-9]{2})[-. ]?([0-9]{4})$/', $value) == 1) {
					$number = (string)preg_replace('/[^\d]/', '', $value);
					if (substr($number, 0, 1) == 1) {
						$number = substr($number, 1);
					}
					if (substr($number, 0, 2) == '+1') {
						$number = substr($number, 2);
					}
					$result = '1.'.$number;
				}
				// If not, does it match ITU-T?
				elseif (preg_match('/^\+(?:[0-9] ?){6,14}[0-9]$/', $value) == 1) {
					$countrycode = substr($value, 0, strpos($value, ' '));
					$countrycode = (string)preg_replace('/[^\d]/', '', $countrycode);
					$number      = strstr($value, ' ');
					$number      = (string)preg_replace('/[^\d]/', '', $number);
					$result      = $countrycode.'.'.$number;
				}
				// If not, does it match EPP?
				elseif (preg_match('/^\+[0-9]{1,3}\.[0-9]{4,14}(?:x.+)?$/', $value) == 1) {
					if (strstr($value, 'x')) {
						$xpos  = strpos($value, 'x');
						$value = substr($value, 0, $xpos);
					}
					$result = str_replace('+', '', $value);

				}
				// Maybe it is already ccc.nnnnnnn?
				elseif (preg_match('/[0-9]{1,3}\.[0-9]{4,14}$/', $value) == 1) {
					$result = $value;
				}
				// If not, can we make it a string of digits?
				else {
					$value = (string)preg_replace('/[^\d]/', '', $value);
					if ($value != null && strlen($value) <= 15) {
						$length = strlen($value);
						// if it is fewer than 13 digits assume it is a local number
						if ($length <= 12) {
							$result = '.'.$value;

						}
						else {
							// If it has 13 or more digits let's make a country code.
							$cclen  = $length - 12;
							$result = substr($value, 0, $cclen).'.'.substr($value, $cclen);
						}
					}
					// If not let's not save anything.
					else {
						$result = '';
					}
				}
				$return = $result;

				break;
			default:
				// Check for a callback filter.
				if (strpos($filter, '::') !== false && is_callable(explode('::', $filter))) {
					$return = call_user_func(explode('::', $filter), $value);
				}
				// Filter using a callback function if specified.
				elseif (function_exists($filter)) {
					$return = call_user_func($filter, $value);
				}
				// Filter using MFilterInput. All HTML code is filtered by default.
				else {
					$return = MFilterInput::getInstance()->clean($value, $filter);
				}
				break;
		}

		return $return;
	}

	protected function findField($name, $group = null) {
		// Initialise variables.
		$element = false;
		$fields  = array();

		// Make sure there is a valid MForm XML document.
		if (!($this->xml instanceof SimpleXMLElement)) {
			return false;
		}

		// Let's get the appropriate field element based on the method arguments.
		if ($group) {

			// Get the fields elements for a given group.
			$elements = & $this->findGroup($group);

			// Get all of the field elements with the correct name for the fields elements.
			foreach ($elements as $element) {
				// If there are matching field elements add them to the fields array.
				if ($tmp = $element->xpath('descendant::field[@name="'.$name.'"]')) {
					$fields = array_merge($fields, $tmp);
				}
			}

			// Make sure something was found.
			if (!$fields) {
				return false;
			}

			// Use the first correct match in the given group.
			$groupNames = explode('.', $group);
			foreach ($fields as &$field) {
				// Get the group names as strings for ancestor fields elements.
				$attrs = $field->xpath('ancestor::fields[@name]/@name');
				$names = array_map('strval', $attrs ? $attrs : array());

				// If the field is in the exact group use it and break out of the loop.
				if ($names == (array)$groupNames) {
					$element = & $field;
					break;
				}
			}
		}
		else {
			// Get an array of fields with the correct name.
			$fields = $this->xml->xpath('//field[@name="'.$name.'"]');

			// Make sure something was found.
			if (!$fields) {
				return false;
			}

			// Search through the fields for the right one.
			foreach ($fields as &$field) {
				// If we find an ancestor fields element with a group name then it isn't what we want.
				if ($field->xpath('ancestor::fields[@name]')) {
					continue;
				}
				// Found it!
				else {
					$element = & $field;
					break;
				}
			}
		}

		return $element;
	}

	protected function &findFieldsByFieldset($name) {
		// Initialise variables.
		$false = false;

		// Make sure there is a valid MForm XML document.
		if (!($this->xml instanceof SimpleXMLElement)) {
			return $false;
		}

		$fields = $this->xml->xpath('//fieldset[@name="'.$name.'"]//field | //field[@fieldset="'.$name.'"]');

		return $fields;
	}

	protected function &findFieldsByGroup($group = null, $nested = false) {
		// Initialise variables.
		$false  = false;
		$fields = array();

		// Make sure there is a valid MForm XML document.
		if (!($this->xml instanceof SimpleXMLElement)) {
			return $false;
		}

		// Get only fields in a specific group?
		if ($group) {

			// Get the fields elements for a given group.
			$elements = & $this->findGroup($group);

			// Get all of the field elements for the fields elements.
			foreach ($elements as $element) {

				// If there are field elements add them to the return result.
				if ($tmp = $element->xpath('descendant::field')) {

					// If we also want fields in nested groups then just merge the arrays.
					if ($nested) {
						$fields = array_merge($fields, $tmp);
					}
					// If we want to exclude nested groups then we need to check each field.
					else {
						$groupNames = explode('.', $group);
						foreach ($tmp as $field) {
							// Get the names of the groups that the field is in.
							$attrs = $field->xpath('ancestor::fields[@name]/@name');
							$names = array_map('strval', $attrs ? $attrs : array());

							// If the field is in the specific group then add it to the return list.
							if ($names == (array)$groupNames) {
								$fields = array_merge($fields, array($field));
							}
						}
					}
				}
			}
		}
		elseif ($group === false) {
			// Get only field elements not in a group.
			$fields = $this->xml->xpath('descendant::fields[not(@name)]/field | descendant::fields[not(@name)]/fieldset/field ');
		}
		else {
			// Get an array of all the <field /> elements.
			$fields = $this->xml->xpath('//field');
		}

		return $fields;
	}

	protected function &findGroup($group) {
		// Initialise variables.
		$false  = false;
		$groups = array();
		$tmp    = array();

		// Make sure there is a valid MForm XML document.
		if (!($this->xml instanceof SimpleXMLElement)) {
			return $false;
		}

		// Make sure there is actually a group to find.
		$group = explode('.', $group);
		if (!empty($group)) {

			// Get any fields elements with the correct group name.
			$elements = $this->xml->xpath('//fields[@name="'.(string)$group[0].'"]');

			// Check to make sure that there are no parent groups for each element.
			foreach ($elements as $element) {
				if (!$element->xpath('ancestor::fields[@name]')) {
					$tmp[] = $element;
				}
			}

			// Iterate through the nested groups to find any matching form field groups.
			for ($i = 1, $n = count($group); $i < $n; $i++) {
				// Initialise some loop variables.
				$validNames = array_slice($group, 0, $i + 1);
				$current    = $tmp;
				$tmp        = array();

				// Check to make sure that there are no parent groups for each element.
				foreach ($current as $element) {
					// Get any fields elements with the correct group name.
					$children = $element->xpath('descendant::fields[@name="'.(string)$group[ $i ].'"]');

					// For the found fields elements validate that they are in the correct groups.
					foreach ($children as $fields) {
						// Get the group names as strings for ancestor fields elements.
						$attrs = $fields->xpath('ancestor-or-self::fields[@name]/@name');
						$names = array_map('strval', $attrs ? $attrs : array());

						// If the group names for the fields element match the valid names at this
						// level add the fields element.
						if ($validNames == $names) {
							$tmp[] = $fields;
						}
					}
				}
			}

			// Only include valid XML objects.
			foreach ($tmp as $element) {
				if ($element instanceof SimpleXMLElement) {
					$groups[] = $element;
				}
			}
		}

		return $groups;
	}

	protected function loadField($element, $group = null, $value = null) {
		// Make sure there is a valid SimpleXMLElement.
		if (!($element instanceof SimpleXMLElement)) {
			return false;
		}

		// Get the field type.
		$type = $element['type'] ? (string)$element['type'] : 'text';

		// Load the MFormField object for the field.
		$field = $this->loadFieldType($type);

		// If the object could not be loaded, get a text field object.
		if ($field === false) {
			$field = $this->loadFieldType('text');
		}

		// Get the value for the form field if not set.
		// Default to the translated version of the 'default' attribute
		// if 'translate_default' attribute if set to 'true' or '1'
		// else the value of the 'default' attribute for the field.
		if ($value === null) {
			$default = (string)$element['default'];
			if (($translate = $element['translate_default']) && ((string)$translate == 'true' || (string)$translate == '1')) {
				$lang = MFactory::getLanguage();
				if ($lang->hasKey($default)) {
					$debug   = $lang->setDebug(false);
					$default = MText::_($default);
					$lang->setDebug($debug);
				}
				else {
					$default = MText::_($default);
				}
			}
			$value = $this->getValue((string)$element['name'], $group, $default);
		}

		// Setup the MFormField object.
		$field->setForm($this);

		if ($field->setup($element, $value, $group)) {
			return $field;
		}
		else {
			return false;
		}
	}

	protected function loadFieldType($type, $new = true) {
		return MFormHelper::loadFieldType($type, $new);
	}

	protected function loadRuleType($type, $new = true) {
		return MFormHelper::loadRuleType($type, $new);
	}

	protected function syncPaths() {
		// Make sure there is a valid MForm XML document.
		if (!($this->xml instanceof SimpleXMLElement)) {
			return false;
		}

		// Get any addfieldpath attributes from the form definition.
		$paths = $this->xml->xpath('//*[@addfieldpath]/@addfieldpath');
		$paths = array_map('strval', $paths ? $paths : array());

		// Add the field paths.
		foreach ($paths as $path) {
			$path = MPATH_WP_PLG.$path;
			self::addFieldPath($path);
		}

		// Get any addformpath attributes from the form definition.
		$paths = $this->xml->xpath('//*[@addformpath]/@addformpath');
		$paths = array_map('strval', $paths ? $paths : array());

		// Add the form paths.
		foreach ($paths as $path) {
			$path = MPATH_WP_PLG.$path;
			self::addFormPath($path);
		}

		// Get any addrulepath attributes from the form definition.
		$paths = $this->xml->xpath('//*[@addrulepath]/@addrulepath');
		$paths = array_map('strval', $paths ? $paths : array());

		// Add the rule paths.
		foreach ($paths as $path) {
			$path = MPATH_WP_PLG.$path;
			self::addRulePath($path);
		}

		return true;
	}

	protected function validateField($element, $group = null, $value = null, $input = null) {
		// Make sure there is a valid SimpleXMLElement.
		if (!$element instanceof SimpleXMLElement) {
			return new MException(MText::_('MLIB_FORM_ERROR_VALIDATE_FIELD'), -1, E_ERROR);
		}

		// Initialise variables.
		$valid = true;

		// Check if the field is required.
		$required = ((string)$element['required'] == 'true' || (string)$element['required'] == 'required');

		if ($required) {
			// If the field is required and the value is empty return an error message.
			if (($value === '') || ($value === null)) {

				// Does the field have a defined error message?
				if ($element['message']) {
					$message = $element['message'];
				}
				else {
					if ($element['label']) {
						$message = MText::_($element['label']);
					}
					else {
						$message = MText::_($element['name']);
					}
					$message = MText::sprintf('MLIB_FORM_VALIDATE_FIELD_REQUIRED', $message);
				}

				return new MException($message, 2, E_WARNING);
			}
		}

		// Get the field validation rule.
		if ($type = (string)$element['validate']) {
			// Load the MFormRule object for the field.
			$rule = $this->loadRuleType($type);

			// If the object could not be loaded return an error message.
			if ($rule === false) {
				return new MException(MText::sprintf('MLIB_FORM_VALIDATE_FIELD_RULE_MISSING', $type), -2, E_ERROR);
			}

			// Run the field validation rule test.
			$valid = $rule->test($element, $value, $group, $input, $this);

			// Check for an error in the validation test.
			if ($valid instanceof Exception) {
				return $valid;
			}
		}

		// Check if the field is valid.
		if ($valid === false) {

			// Does the field have a defined error message?
			$message = (string)$element['message'];

			if ($message) {
				return new MException(MText::_($message), 1, E_WARNING);
			}
			else {
				return new MException(MText::sprintf('MLIB_FORM_VALIDATE_FIELD_INVALID', MText::_((string)$element['label'])), 1, E_WARNING);
			}
		}

		return true;
	}

	public static function addFieldPath($new = null) {
		return MFormHelper::addFieldPath($new);
	}

	public static function addFormPath($new = null) {
		return MFormHelper::addFormPath($new);
	}

	public static function addRulePath($new = null) {
		return MFormHelper::addRulePath($new);
	}

	public static function getInstance($name, $data = null, $options = array(), $replace = true, $xpath = false) {
		// Reference to array with form instances
		$forms = & self::$forms;

		// Only instantiate the form if it does not already exist.
		if (!isset($forms[ $name ])) {

			$data = trim($data);

			if (empty($data)) {
				throw new Exception(MText::_('MLIB_FORM_ERROR_NO_DATA'));
			}

			// Instantiate the form.
			$forms[ $name ] = new MForm($name, $options);

			// Load the data.
			if (substr(trim($data), 0, 1) == '<') {
				if ($forms[ $name ]->load($data, $replace, $xpath) == false) {
					throw new Exception(MText::_('MLIB_FORM_ERROR_XML_FILE_DID_NOT_LOAD'));

					return false;
				}
			}
			else {
				if ($forms[ $name ]->loadFile($data, $replace, $xpath) == false) {
					throw new Exception(MText::_('MLIB_FORM_ERROR_XML_FILE_DID_NOT_LOAD'));

					return false;
				}
			}
		}

		return $forms[ $name ];
	}

	protected static function addNode(SimpleXMLElement $source, SimpleXMLElement $new) {
		// Add the new child node.
		$node = $source->addChild($new->getName(), trim($new));

		// Add the attributes of the child node.
		foreach ($new->attributes() as $name => $value) {
			$node->addAttribute($name, $value);
		}

		// Add any children of the new node.
		foreach ($new->children() as $child) {
			self::addNode($node, $child);
		}
	}

	protected static function mergeNode(SimpleXMLElement $source, SimpleXMLElement $new) {
		// Update the attributes of the child node.
		foreach ($new->attributes() as $name => $value) {
			if (isset($source[ $name ])) {
				$source[ $name ] = (string)$value;
			}
			else {
				$source->addAttribute($name, $value);
			}
		}

		// What to do with child elements?
	}

	protected static function mergeNodes(SimpleXMLElement $source, SimpleXMLElement $new) {
		// The assumption is that the inputs are at the same relative level.
		// So we just have to scan the children and deal with them.

		// Update the attributes of the child node.
		foreach ($new->attributes() as $name => $value) {
			if (isset($source[ $name ])) {
				$source[ $name ] = (string)$value;
			}
			else {
				$source->addAttribute($name, $value);
			}
		}

		foreach ($new->children() as $child) {
			$type = $child->getName();
			$name = $child['name'];

			// Does this node exist?
			$fields = $source->xpath($type.'[@name="'.$name.'"]');

			if (empty($fields)) {
				// This node does not exist, so add it.
				self::addNode($source, $child);
			}
			else {
				// This node does exist.
				switch ($type) {
					case 'field':
						self::mergeNode($fields[0], $child);
						break;

					default:
						self::mergeNodes($fields[0], $child);
						break;
				}
			}
		}
	}
}