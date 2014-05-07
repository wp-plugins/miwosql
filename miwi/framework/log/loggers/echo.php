<?php

/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

mimport('framework.log.logger');

class MLoggerEcho extends MLogger {

    protected $priorities = array(
        MLog::EMERGENCY => 'EMERGENCY',
        MLog::ALERT     => 'ALERT',
        MLog::CRITICAL  => 'CRITICAL',
        MLog::ERROR     => 'ERROR',
        MLog::WARNING   => 'WARNING',
        MLog::NOTICE    => 'NOTICE',
        MLog::INFO      => 'INFO',
        MLog::DEBUG     => 'DEBUG');

    public function addEntry(MLogEntry $entry) {
        echo $this->priorities[$entry->priority] . ': ' . $entry->message . (empty($entry->category) ? '' : ' [' . $entry->category . ']') . "\n";
    }
}
