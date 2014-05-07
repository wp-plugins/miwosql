<?php

/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

mimport('framework.utilities.date');

class MLogEntry {

    public $category;
    public $date;
    public $message;
    public $priority = MLog::INFO;
    protected $priorities = array(
        MLog::EMERGENCY,
        MLog::ALERT,
        MLog::CRITICAL,
        MLog::ERROR,
        MLog::WARNING,
        MLog::NOTICE,
        MLog::INFO,
        MLog::DEBUG
    );

    public function __construct($message, $priority = MLog::INFO, $category = '', $date = null) {
        $this->message = (string)$message;

        // Sanitize the priority.
        if (!in_array($priority, $this->priorities, true)) {
            $priority = MLog::INFO;
        }
        $this->priority = $priority;

        // Sanitize category if it exists.
        if (!empty($category)) {
            $this->category = (string)strtolower(preg_replace('/[^A-Z0-9_\.-]/i', '', $category));
        }

        // Get the date as a MDate object.
        $this->date = new MDate($date ? $date : 'now');
    }
}
