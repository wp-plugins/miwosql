<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

MFormHelper::loadFieldClass('list');

class MFormFieldInteger extends MFormFieldList {

    protected $type = 'Integer';

    protected function getOptions() {
        // Initialize variables.
        $options = array();

        // Initialize some field attributes.
        $first = (int)$this->element['first'];
        $last  = (int)$this->element['last'];
        $step  = (int)$this->element['step'];

        // Sanity checks.
        if ($step == 0) {
            // Step of 0 will create an endless loop.
            return $options;
        }
        elseif ($first < $last && $step < 0) {
            // A negative step will never reach the last number.
            return $options;
        }
        elseif ($first > $last && $step > 0) {
            // A position step will never reach the last number.
            return $options;
        }

        // Build the options array.
        for ($i = $first; $i <= $last; $i += $step) {
            $options[] = MHtml::_('select.option', $i);
        }

        // Merge any additional options in the XML definition.
        $options = array_merge(parent::getOptions(), $options);

        return $options;
    }
}