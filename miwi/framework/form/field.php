<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/




defined('MIWI') or die('MIWI');

abstract class MFormField extends MObject {

    protected $description;
    protected $element;
    protected $form;
    protected $formControl;
    protected $hidden = false;
    protected $translateLabel = true;
    protected $translateDescription = true;
    protected $id;
    protected $input;
    protected $label;
    protected $multiple = false;
    protected $name;
    protected $fieldname;
    protected $group;
    protected $required = false;
    protected $type;
    protected $validate;
    protected $value;
    protected $labelClass;
    protected static $count = 0;
    protected static $generated_fieldname = '__field';
    public function __construct($form = null) {
        // If there is a form passed into the constructor set the form and form control properties.
        if ($form instanceof MForm) {
            $this->form        = $form;
            $this->formControl = $form->getFormControl();
        }

        // Detect the field type if not set
        if (!isset($this->type)) {
            $parts = MString::splitCamelCase(get_class($this));
            if ($parts[0] == 'M') {
                $this->type = MString::ucfirst($parts[count($parts) - 1], '_');
            }
            else {
                $this->type = MString::ucfirst($parts[0], '_') . MString::ucfirst($parts[count($parts) - 1], '_');
            }
        }
    }

    public function __get($name) {
        switch ($name) {
            case 'class':
            case 'description':
            case 'formControl':
            case 'hidden':
            case 'id':
            case 'multiple':
            case 'name':
            case 'required':
            case 'type':
            case 'validate':
            case 'value':
            case 'labelClass':
            case 'fieldname':
            case 'group':
                return $this->$name;
                break;

            case 'input':
                // If the input hasn't yet been generated, generate it.
                if (empty($this->input)) {
                    $this->input = $this->getInput();
                }

                return $this->input;
                break;

            case 'label':
                // If the label hasn't yet been generated, generate it.
                if (empty($this->label)) {
                    $this->label = $this->getLabel();
                }

                return $this->label;
                break;
            case 'title':
                return $this->getTitle();
                break;
        }

        return null;
    }

    public function setForm(MForm $form) {
        $this->form        = $form;
        $this->formControl = $form->getFormControl();

        return $this;
    }

    public function setup(&$element, $value, $group = null) {
        // Make sure there is a valid MFormField XML element.
        if (!($element instanceof SimpleXMLElement) || (string)$element->getName() != 'field') {
            return false;
        }

        // Reset the input and label values.
        $this->input = null;
        $this->label = null;

        // Set the XML element object.
        $this->element = $element;




        // Get some important attributes from the form field element.
        $class    = (string)$element['class'];
        $id       = (string)$element['id'];
        $multiple = (string)$element['multiple'];
        $name     = (string)$element['name'];
        $required = (string)$element['required'];

        // Set the required and validation options.
        $this->required = ($required == 'true' || $required == 'required' || $required == '1');
        $this->validate = (string)$element['validate'];

        // Add the required class if the field is required.
        if ($this->required) {
            if ($class) {
                if (strpos($class, 'required') === false) {
                    $this->element['class'] = $class . ' required';
                }
            }
            else {
                $this->element->addAttribute('class', 'required');
            }
        }

        // Set the multiple values option.
        $this->multiple = ($multiple == 'true' || $multiple == 'multiple');


        // Allow for field classes to force the multiple values option.
        if (isset($this->forceMultiple)) {
            $this->multiple = (bool)$this->forceMultiple;
        }

        // Set the field description text.
        $this->description = (string)$element['description'];

        // Set the visibility.
        $this->hidden = ((string)$element['type'] == 'hidden' || (string)$element['hidden'] == 'true');

        // Determine whether to translate the field label and/or description.
        $this->translateLabel       = !((string)$this->element['translate_label'] == 'false' || (string)$this->element['translate_label'] == '0');
        $this->translateDescription = !((string)$this->element['translate_description'] == 'false'
            || (string)$this->element['translate_description'] == '0');

        // Set the group of the field.
        $this->group = $group;

        // Set the field name and id.
        $this->fieldname = $this->getFieldName($name);
        $this->name      = $this->getName($this->fieldname);
        $this->id        = $this->getId($id, $this->fieldname);

        // Set the field default value.
        $this->value = $value;

        // Set the CSS class of field label
        $this->labelClass = (string)$element['labelclass'];

        return true;
    }

    protected function getId($fieldId, $fieldName) {
        // Initialise variables.
        $id = '';

        // If there is a form control set for the attached form add it first.
        if ($this->formControl) {
            $id .= $this->formControl;
        }

        // If the field is in a group add the group control to the field id.
        if ($this->group) {
            // If we already have an id segment add the group control as another level.
            if ($id) {
                $id .= '_' . str_replace('.', '_', $this->group);
            }
            else {
                $id .= str_replace('.', '_', $this->group);
            }
        }

        // If we already have an id segment add the field id/name as another level.
        if ($id) {
            $id .= '_' . ($fieldId ? $fieldId : $fieldName);
        }
        else {
            $id .= ($fieldId ? $fieldId : $fieldName);
        }

        // Clean up any invalid characters.
        $id = preg_replace('#\W#', '_', $id);

        return $id;
    }

    abstract protected function getInput();

    protected function getTitle() {
        // Initialise variables.
        $title = '';

        if ($this->hidden) {



            return $title;
        }

        // Get the label text from the XML element, defaulting to the element name.
        $title = $this->element['label'] ? (string)$this->element['label'] : (string)$this->element['name'];
        $title = $this->translateLabel ? MText::_($title) : $title;



        return $title;
    }

    protected function getLabel() {
        // Initialise variables.
        $label = '';

        if ($this->hidden) {
            return $label;
        }

        // Get the label text from the XML element, defaulting to the element name.
        $text = $this->element['label'] ? (string)$this->element['label'] : (string)$this->element['name'];
        $text = $this->translateLabel ? MText::_($text) : $text;

        // Build the class for the label.
        $class = !empty($this->description) ? 'hasTip' : '';
        $class = $this->required == true ? $class . ' required' : $class;
        $class = !empty($this->labelClass) ? $class . ' ' . $this->labelClass : $class;

        // Add the opening label tag and main attributes attributes.
        $label .= '<label id="' . $this->id . '-lbl" for="' . $this->id . '" class="' . $class . '"';

        // If a description is specified, use it to build a tooltip.
        if (!empty($this->description)) {
            $label .= ' title="'
                . htmlspecialchars(
                    trim($text, ':') . '::' . ($this->translateDescription ? MText::_($this->description) : $this->description),
                    ENT_COMPAT, 'UTF-8'
                ) . '"';
        }

        // Add the label text and closing tag.
        if ($this->required) {
            $label .= '>' . $text . '<span class="star">&#160;*</span></label>';
        }
        else {
            $label .= '>' . $text . '</label>';
        }

        return $label;
    }

    protected function getName($fieldName) {
        // Initialise variables.
        $name = '';

        // If there is a form control set for the attached form add it first.
        if ($this->formControl) {
            $name .= $this->formControl;
        }

        // If the field is in a group add the group control to the field name.
        if ($this->group) {
            // If we already have a name segment add the group control as another level.
            $groups = explode('.', $this->group);
            if ($name) {
                foreach ($groups as $group) {
                    $name .= '[' . $group . ']';
                }
            }
            else {
                $name .= array_shift($groups);
                foreach ($groups as $group) {
                    $name .= '[' . $group . ']';
                }
            }
        }

        // If we already have a name segment add the field name as another level.
        if ($name) {
            $name .= '[' . $fieldName . ']';
        }
        else {
            $name .= $fieldName;
        }

        // If the field should support multiple values add the final array segment.
        if ($this->multiple) {
            $name .= '[]';
        }

        return $name;
    }

    protected function getFieldName($fieldName) {
        if ($fieldName) {
            return $fieldName;
        }
        else {
            self::$count = self::$count + 1;

            return self::$generated_fieldname . self::$count;
        }
    }

	public function setValue($value) {
        $this->value = $value;
    }
}
