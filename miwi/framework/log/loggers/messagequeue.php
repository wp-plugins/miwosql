<?php

/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

mimport('framework.log.logger');

class MLoggerMessageQueue extends MLogger {

    public function addEntry(MLogEntry $entry) {
        switch ($entry->priority) {
            case MLog::EMERGENCY:
            case MLog::ALERT:
            case MLog::CRITICAL:
            case MLog::ERROR:
                MFactory::getApplication()->enqueueMessage($entry->message, 'error');
                break;
            case MLog::WARNING:
                MFactory::getApplication()->enqueueMessage($entry->message, 'warning');
                break;
            case MLog::NOTICE:
                MFactory::getApplication()->enqueueMessage($entry->message, 'notice');
                break;
            case MLog::INFO:
                MFactory::getApplication()->enqueueMessage($entry->message, 'message');
                break;
            default:
                // Ignore other priorities.
                break;
        }
    }
}