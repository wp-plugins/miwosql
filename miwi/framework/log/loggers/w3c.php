<?php

/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

mimport('framework.log.logger');

// Register the MLoggerFormattedText class with the autoloader.
MLoader::register('MLoggerFormattedText', dirname(__FILE__) . '/formattedtext.php');

class MLoggerW3C extends MLoggerFormattedText {

    protected $format = '{DATE}	{TIME}	{PRIORITY}	{CLIENTIP}	{CATEGORY}	{MESSAGE}';

    public function __construct(array &$options) {
        // The name of the text file defaults to 'error.w3c.php' if not explicitly given.
        if (empty($options['text_file'])) {
            $options['text_file'] = 'error.w3c.php';
        }

        // Call the parent constructor.
        parent::__construct($options);
    }
}
