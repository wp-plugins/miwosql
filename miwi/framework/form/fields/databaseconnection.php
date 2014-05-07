<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

MFormHelper::loadFieldClass('list');

class MFormFieldDatabaseConnection extends MFormFieldList {

    public $type = 'DatabaseConnection';

    protected function getOptions() {
        // Initialize variables.
        // This gets the connectors available in the platform and supported by the server.
        $available = MDatabase::getConnectors();

        $supported = $this->element['supported'];
        if (!empty($supported)) {
            $supported = explode(',', $supported);
            foreach ($supported as $support) {
                if (in_array($support, $available)) {
                    $options[$support] = ucfirst($support);
                }
            }
        }
        else {
            foreach ($available as $support) {
                $options[$support] = ucfirst($support);
            }
        }

        // This will come into play if an application is installed that requires
        // a database that is not available on the server.
        if (empty($options)) {
            $options[''] = MText::_('MNONE');
        }

        return $options;
    }
}