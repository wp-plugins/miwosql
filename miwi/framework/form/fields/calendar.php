<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MFormFieldCalendar extends MFormField {

    public $type = 'Calendar';

    protected function getInput() {
        // Initialize some field attributes.
        $format = $this->element['format'] ? (string)$this->element['format'] : '%Y-%m-%d';

        // Build the attributes array.
        $attributes = array();
        if ($this->element['size']) {
            $attributes['size'] = (int)$this->element['size'];
        }
        if ($this->element['maxlength']) {
            $attributes['maxlength'] = (int)$this->element['maxlength'];
        }
        if ($this->element['class']) {
            $attributes['class'] = (string)$this->element['class'];
        }
        if ((string)$this->element['readonly'] == 'true') {
            $attributes['readonly'] = 'readonly';
        }
        if ((string)$this->element['disabled'] == 'true') {
            $attributes['disabled'] = 'disabled';
        }
        if ($this->element['onchange']) {
            $attributes['onchange'] = (string)$this->element['onchange'];
        }

        // Handle the special case for "now".
        if (strtoupper($this->value) == 'NOW') {
            $this->value = strftime($format);
        }

        // Get some system objects.
        $config = MFactory::getConfig();
        $user   = MFactory::getUser();

        // If a known filter is given use it.
        switch (strtoupper((string)$this->element['filter'])) {
            case 'SERVER_UTC':
                // Convert a date to UTC based on the server timezone.
                if (intval($this->value)) {
                    // Get a date object based on the correct timezone.
                    $date = MFactory::getDate($this->value, 'UTC');
                    $date->setTimezone(new DateTimeZone($config->get('offset')));

                    // Transform the date string.
                    $this->value = $date->format('Y-m-d H:i:s', true, false);
                }
                break;

            case 'USER_UTC':
                // Convert a date to UTC based on the user timezone.
                if (intval($this->value)) {
                    // Get a date object based on the correct timezone.
                    $date = MFactory::getDate($this->value, 'UTC');
                    $date->setTimezone(new DateTimeZone($user->getParam('timezone', $config->get('offset'))));

                    // Transform the date string.
                    $this->value = $date->format('Y-m-d H:i:s', true, false);
                }
                break;
        }

        return MHtml::_('calendar', $this->value, $this->name, $this->id, $format, $attributes);
    }
}
