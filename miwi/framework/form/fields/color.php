<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MFormFieldColor extends MFormField {

    protected $type = 'Color';

    protected function getInput() {
        // Translate placeholder text
        $hint = $this->translateHint ? MText::_($this->hint) : $this->hint;

        // Control value can be: hue (default), saturation, brightness, wheel or simple
        $control = $this->control;

        // Position of the panel can be: right (default), left, top or bottom
        $position = ' data-position="' . $this->position . '"';

        $onchange  = !empty($this->onchange) ? ' onchange="' . $this->onchange . '"' : '';
	    $class     = (string) $this->element['class'];
        $required  = $this->required ? ' required aria-required="true"' : '';
        $disabled  = $this->disabled ? ' disabled' : '';
        $autofocus = $this->autofocus ? ' autofocus' : '';

        $color = strtolower($this->value);

        if (!$color || in_array($color, array('none', 'transparent'))) {
            $color = 'none';
        }
        elseif ($color['0'] != '#') {
            $color = '#' . $color;
        }

        if ($control == 'simple') {
            $class = ' class="' . trim('simplecolors chzn-done ' . $class) . '"';
            MHtml::_('behavior.simplecolorpicker');

            $colors = strtolower($this->colors);

            if (empty($colors)) {
                $colors = array(
                    'none',
                    '#049cdb',
                    '#46a546',
                    '#9d261d',
                    '#ffc40d',
                    '#f89406',
                    '#c3325f',
                    '#7a43b6',
                    '#ffffff',
                    '#999999',
                    '#555555',
                    '#000000'
                );
            }
            else {
                $colors = explode(',', $colors);
            }

            $split = $this->split;

            if (!$split) {
                $count = count($colors);

                if ($count % 5 == 0) {
                    $split = 5;
                }
                else {
                    if ($count % 4 == 0) {
                        $split = 4;
                    }
                }
            }

            $split = $split ? $split : 3;

            $html   = array();
            $html[] = '<select name="' . $this->name . '" id="' . $this->id . '"' . $disabled . $required
                . $class . $position . $onchange . $autofocus . ' style="visibility:hidden;width:22px;height:1px">';

            foreach ($colors as $i => $c) {
                $html[] = '<option' . ($c == $color ? ' selected="selected"' : '') . '>' . $c . '</option>';

                if (($i + 1) % $split == 0) {
                    $html[] = '<option>-</option>';
                }
            }

            $html[] = '</select>';

            return implode('', $html);
        }
        else {
            $class        = ' class="' . trim('minicolors ' . $class) . '"';
            $control      = $control ? ' data-control="' . $control . '"' : '';
            $readonly     = $this->readonly ? ' readonly' : '';
            $hint         = $hint ? ' placeholder="' . $hint . '"' : ' placeholder="#rrggbb"';
            $autocomplete = !$this->autocomplete ? ' autocomplete="off"' : '';

            // Including fallback code for HTML5 non supported browsers.
            MHtml::_('jquery.framework');
            MHtml::_('script', 'system/html5fallback.js', false, true);

            MHtml::_('behavior.colorpicker');

            return '<input type="text" name="' . $this->name . '" id="' . $this->id . '"' . ' value="'
            . htmlspecialchars($color, ENT_COMPAT, 'UTF-8') . '"' . $hint . $class . $position . $control
            . $readonly . $disabled . $required . $onchange . $autocomplete . $autofocus . '/>';
        }
    }
}
