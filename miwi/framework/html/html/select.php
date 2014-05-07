<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

abstract class MHtmlSelect {

    static protected $_optionDefaults = array(
        'option' => array('option.attr'        => null, 'option.disable' => 'disable', 'option.id' => null, 'option.key' => 'value',
                          'option.key.toHtml'  => true, 'option.label' => null, 'option.label.toHtml' => true, 'option.text' => 'text',
                          'option.text.toHtml' => true));

    public static function booleanlist($name, $attribs = null, $selected = null, $yes = 'MYES', $no = 'MNO', $id = false) {
        $arr = array(MHtml::_('select.option', '0', MText::_($no)), MHtml::_('select.option', '1', MText::_($yes)));

        return MHtml::_('select.radiolist', $arr, $name, $attribs, 'value', 'text', (int)$selected, $id);
    }

    public static function genericlist($data, $name, $attribs = null, $optKey = 'value', $optText = 'text', $selected = null, $idtag = false,
                                       $translate = false) {
        // Set default options
        $options = array_merge(MHtml::$formatOptions, array('format.depth' => 0, 'id' => false));
        if (is_array($attribs) && func_num_args() == 3) {
            // Assume we have an options array
            $options = array_merge($options, $attribs);
        }
        else {
            // Get options from the parameters
            $options['id']             = $idtag;
            $options['list.attr']      = $attribs;
            $options['list.translate'] = $translate;
            $options['option.key']     = $optKey;
            $options['option.text']    = $optText;
            $options['list.select']    = $selected;
        }
        $attribs = '';
        if (isset($options['list.attr'])) {
            if (is_array($options['list.attr'])) {
                $attribs = MArrayHelper::toString($options['list.attr']);
            }
            else {
                $attribs = $options['list.attr'];
            }
            if ($attribs != '') {
                $attribs = ' ' . $attribs;
            }
        }

        $id = $options['id'] !== false ? $options['id'] : $name;
        $id = str_replace(array('[', ']'), '', $id);

        $baseIndent = str_repeat($options['format.indent'], $options['format.depth']++);
        $html       = $baseIndent . '<select' . ($id !== '' ? ' id="' . $id . '"' : '') . ' name="' . $name . '"' . $attribs . '>' . $options['format.eol']
            . self::options($data, $options) . $baseIndent . '</select>' . $options['format.eol'];

        return $html;
    }

    public static function groupedlist($data, $name, $options = array()) {
        // Set default options and overwrite with anything passed in
        $options = array_merge(
            MHtml::$formatOptions,
            array('format.depth' => 0, 'group.items' => 'items', 'group.label' => 'text', 'group.label.toHtml' => true, 'id' => false),
            $options
        );

        // Apply option rules
        if ($options['group.items'] === null) {
            $options['group.label'] = null;
        }

        $attribs = '';

        if (isset($options['list.attr'])) {
            if (is_array($options['list.attr'])) {
                $attribs = MArrayHelper::toString($options['list.attr']);
            }
            else {
                $attribs = $options['list.attr'];
            }
            if ($attribs != '') {
                $attribs = ' ' . $attribs;
            }
        }

        $id = $options['id'] !== false ? $options['id'] : $name;
        $id = str_replace(array('[', ']'), '', $id);

        // Disable groups in the options.
        $options['groups'] = false;

        $baseIndent  = str_repeat($options['format.indent'], $options['format.depth']++);
        $html        = $baseIndent . '<select' . ($id !== '' ? ' id="' . $id . '"' : '') . ' name="' . $name . '"' . $attribs . '>' . $options['format.eol'];
        $groupIndent = str_repeat($options['format.indent'], $options['format.depth']++);

        foreach ($data as $dataKey => $group) {
            $label   = $dataKey;
            $id      = '';
            $noGroup = is_int($dataKey);

            if ($options['group.items'] == null) {
                // Sub-list is an associative array
                $subList = $group;
            }
            elseif (is_array($group)) {
                // Sub-list is in an element of an array.
                $subList = $group[$options['group.items']];
                if (isset($group[$options['group.label']])) {
                    $label   = $group[$options['group.label']];
                    $noGroup = false;
                }
                if (isset($options['group.id']) && isset($group[$options['group.id']])) {
                    $id      = $group[$options['group.id']];
                    $noGroup = false;
                }
            }
            elseif (is_object($group)) {
                // Sub-list is in a property of an object
                $subList = $group->$options['group.items'];
                if (isset($group->$options['group.label'])) {
                    $label   = $group->$options['group.label'];
                    $noGroup = false;
                }
                if (isset($options['group.id']) && isset($group->$options['group.id'])) {
                    $id      = $group->$options['group.id'];
                    $noGroup = false;
                }
            }
            else {
                throw new MException('Invalid group contents.', 1, E_WARNING);
            }

            if ($noGroup) {
                $html .= self::options($subList, $options);
            }
            else {
                $html .= $groupIndent . '<optgroup' . (empty($id) ? '' : ' id="' . $id . '"') . ' label="'
                    . ($options['group.label.toHtml'] ? htmlspecialchars($label, ENT_COMPAT, 'UTF-8') : $label) . '">' . $options['format.eol']
                    . self::options($subList, $options) . $groupIndent . '</optgroup>' . $options['format.eol'];
            }
        }

        $html .= $baseIndent . '</select>' . $options['format.eol'];

        return $html;
    }

    public static function integerlist($start, $end, $inc, $name, $attribs = null, $selected = null, $format = '') {
        // Set default options
        $options = array_merge(MHtml::$formatOptions, array('format.depth' => 0, 'option.format' => '', 'id' => null));
        if (is_array($attribs) && func_num_args() == 5) {
            // Assume we have an options array
            $options = array_merge($options, $attribs);
            // Extract the format and remove it from downstream options
            $format = $options['option.format'];
            unset($options['option.format']);
        }
        else {
            // Get options from the parameters
            $options['list.attr']   = $attribs;
            $options['list.select'] = $selected;
        }
        $start = intval($start);
        $end   = intval($end);
        $inc   = intval($inc);

        $data = array();
        for ($i = $start; $i <= $end; $i += $inc) {
            $data[$i] = $format ? sprintf($format, $i) : $i;
        }

        // Tell genericlist() to use array keys
        $options['option.key'] = null;

        return MHtml::_('select.genericlist', $data, $name, $options);
    }

    public static function optgroup($text, $optKey = 'value', $optText = 'text') {
        // Deprecation warning.
        MLog::add('MSelect::optgroup is deprecated.', MLog::WARNING, 'deprecated');

        // Set initial state
        static $state = 'open';

        // Toggle between open and close states:
        switch ($state) {
            case 'open':
                $obj           = new stdClass;
                $obj->$optKey  = '<OPTGROUP>';
                $obj->$optText = $text;
                $state         = 'close';
                break;
            case 'close':
                $obj           = new stdClass;
                $obj->$optKey  = '</OPTGROUP>';
                $obj->$optText = $text;
                $state         = 'open';
                break;
        }

        return $obj;
    }

    public static function option($value, $text = '', $optKey = 'value', $optText = 'text', $disable = false) {
        $options = array('attr'         => null, 'disable' => false, 'option.attr' => null, 'option.disable' => 'disable', 'option.key' => 'value',
                         'option.label' => null, 'option.text' => 'text');
        if (is_array($optKey)) {
            // Merge in caller's options
            $options = array_merge($options, $optKey);
        }
        else {
            // Get options from the parameters
            $options['option.key']  = $optKey;
            $options['option.text'] = $optText;
            $options['disable']     = $disable;
        }
        $obj                          = new MObject;
        $obj->$options['option.key']  = $value;
        $obj->$options['option.text'] = trim($text) ? $text : $value;

        /*
         * If a label is provided, save it. If no label is provided and there is
         * a label name, initialise to an empty string.
         */
        $hasProperty = $options['option.label'] !== null;
        if (isset($options['label'])) {
            $labelProperty       = $hasProperty ? $options['option.label'] : 'label';
            $obj->$labelProperty = $options['label'];
        }
        elseif ($hasProperty) {
            $obj->$options['option.label'] = '';
        }

        // Set attributes only if there is a property and a value
        if ($options['attr'] !== null) {
            $obj->$options['option.attr'] = $options['attr'];
        }

        // Set disable only if it has a property and a value
        if ($options['disable'] !== null) {
            $obj->$options['option.disable'] = $options['disable'];
        }

        return $obj;
    }

    public static function options($arr, $optKey = 'value', $optText = 'text', $selected = null, $translate = false) {
        $options = array_merge(
            MHtml::$formatOptions,
            self::$_optionDefaults['option'],
            array('format.depth' => 0, 'groups' => true, 'list.select' => null, 'list.translate' => false)
        );

        if (is_array($optKey)) {
            // Set default options and overwrite with anything passed in
            $options = array_merge($options, $optKey);
        }
        else {
            // Get options from the parameters
            $options['option.key']     = $optKey;
            $options['option.text']    = $optText;
            $options['list.select']    = $selected;
            $options['list.translate'] = $translate;
        }

        $html       = '';
        $baseIndent = str_repeat($options['format.indent'], $options['format.depth']);

        foreach ($arr as $elementKey => &$element) {
            $attr  = '';
            $extra = '';
            $label = '';
            $id    = '';
            if (is_array($element)) {
                $key  = $options['option.key'] === null ? $elementKey : $element[$options['option.key']];
                $text = $element[$options['option.text']];
                if (isset($element[$options['option.attr']])) {
                    $attr = $element[$options['option.attr']];
                }
                if (isset($element[$options['option.id']])) {
                    $id = $element[$options['option.id']];
                }
                if (isset($element[$options['option.label']])) {
                    $label = $element[$options['option.label']];
                }
                if (isset($element[$options['option.disable']]) && $element[$options['option.disable']]) {
                    $extra .= ' disabled="disabled"';
                }
            }
            elseif (is_object($element)) {
                $key  = $options['option.key'] === null ? $elementKey : $element->$options['option.key'];
                $text = $element->$options['option.text'];
                if (isset($element->$options['option.attr'])) {
                    $attr = $element->$options['option.attr'];
                }
                if (isset($element->$options['option.id'])) {
                    $id = $element->$options['option.id'];
                }
                if (isset($element->$options['option.label'])) {
                    $label = $element->$options['option.label'];
                }
                if (isset($element->$options['option.disable']) && $element->$options['option.disable']) {
                    $extra .= ' disabled="disabled"';
                }
            }
            else {
                // This is a simple associative array
                $key  = $elementKey;
                $text = $element;
            }

            $key = (string)$key;
            if ($options['groups'] && $key == '<OPTGROUP>') {
                $html .= $baseIndent . '<optgroup label="' . ($options['list.translate'] ? MText::_($text) : $text) . '">' . $options['format.eol'];
                $baseIndent = str_repeat($options['format.indent'], ++$options['format.depth']);
            }
            elseif ($options['groups'] && $key == '</OPTGROUP>') {
                $baseIndent = str_repeat($options['format.indent'], --$options['format.depth']);
                $html .= $baseIndent . '</optgroup>' . $options['format.eol'];
            }
            else {
                // if no string after hyphen - take hyphen out
                $splitText = explode(' - ', $text, 2);
                $text      = $splitText[0];
                if (isset($splitText[1])) {
                    $text .= ' - ' . $splitText[1];
                }

                if ($options['list.translate'] && !empty($label)) {
                    $label = MText::_($label);
                }
                if ($options['option.label.toHtml']) {
                    $label = htmlentities($label);
                }
                if (is_array($attr)) {
                    $attr = MArrayHelper::toString($attr);
                }
                else {
                    $attr = trim($attr);
                }
                $extra = ($id ? ' id="' . $id . '"' : '') . ($label ? ' label="' . $label . '"' : '') . ($attr ? ' ' . $attr : '') . $extra;
                if (is_array($options['list.select'])) {
                    foreach ($options['list.select'] as $val) {
                        $key2 = is_object($val) ? $val->$options['option.key'] : $val;
                        if ($key == $key2) {
                            $extra .= ' selected="selected"';
                            break;
                        }
                    }
                }
                elseif ((string)$key == (string)$options['list.select']) {
                    $extra .= ' selected="selected"';
                }

                if ($options['list.translate']) {
                    $text = MText::_($text);
                }

                // Generate the option, encoding as required
                $html .= $baseIndent . '<option value="' . ($options['option.key.toHtml'] ? htmlspecialchars($key, ENT_COMPAT, 'UTF-8') : $key) . '"'
                    . $extra . '>';
                $html .= $options['option.text.toHtml'] ? htmlentities(html_entity_decode($text, ENT_COMPAT, 'UTF-8'), ENT_COMPAT, 'UTF-8') : $text;
                $html .= '</option>' . $options['format.eol'];
            }
        }

        return $html;
    }

    public static function radiolist($data, $name, $attribs = null, $optKey = 'value', $optText = 'text', $selected = null, $idtag = false,
                                     $translate = false) {
        reset($data);
        $html = '';

        if (is_array($attribs)) {
            $attribs = MArrayHelper::toString($attribs);
        }

        $id_text = $idtag ? $idtag : $name;

        foreach ($data as $obj) {
            $k  = $obj->$optKey;
            $t  = $translate ? MText::_($obj->$optText) : $obj->$optText;
            $id = (isset($obj->id) ? $obj->id : null);

            $extra = '';
            $extra .= $id ? ' id="' . $obj->id . '"' : '';
            if (is_array($selected)) {
                foreach ($selected as $val) {
                    $k2 = is_object($val) ? $val->$optKey : $val;
                    if ($k == $k2) {
                        $extra .= ' selected="selected"';
                        break;
                    }
                }
            }
            else {
                $extra .= ((string)$k == (string)$selected ? ' checked="checked"' : '');
            }
            $html .= "\n\t" . '<input type="radio" name="' . $name . '"' . ' id="' . $id_text . $k . '" value="' . $k . '"' . ' ' . $extra . ' '
                . $attribs . '/>' . "\n\t" . '<label for="' . $id_text . $k . '"' . ' id="' . $id_text . $k . '-lbl" class="radiobtn">' . $t
                . '</label>';
        }
        $html .= "\n";

        return $html;
    }

}