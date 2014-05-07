<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MFormFieldSpacer extends MFormField {

    protected $type = 'Spacer';

    protected function getInput() {
        return ' ';
    }

    protected function getLabel() {
        $html  = array();
        $class = $this->element['class'] ? (string)$this->element['class'] : '';

        $html[] = '<span class="spacer">';
        $html[] = '<span class="before"></span>';
        $html[] = '<span class="' . $class . '">';
        if ((string)$this->element['hr'] == 'true') {
            $html[] = '<hr class="' . $class . '" />';
        }
        else {
            $label = '';

            // Get the label text from the XML element, defaulting to the element name.
            $text = $this->element['label'] ? (string)$this->element['label'] : (string)$this->element['name'];
            $text = $this->translateLabel ? MText::_($text) : $text;

            // Build the class for the label.
            $class = !empty($this->description) ? 'hasTip' : '';
            $class = $this->required == true ? $class . ' required' : $class;

            // Add the opening label tag and main attributes attributes.
            $label .= '<label id="' . $this->id . '-lbl" class="' . $class . '"';

            // If a description is specified, use it to build a tooltip.
            if (!empty($this->description)) {
                $label .= ' title="'
                    . htmlspecialchars(
                        trim($text, ':') . '::' . ($this->translateDescription ? MText::_($this->description) : $this->description),
                        ENT_COMPAT, 'UTF-8'
                    ) . '"';
            }

            // Add the label text and closing tag.
            $label .= '>' . $text . '</label>';
            $html[] = $label;
        }
        $html[] = '</span>';
        $html[] = '<span class="after"></span>';
        $html[] = '</span>';

        return implode('', $html);
    }

    protected function getTitle() {
        return $this->getLabel();
    }
}