<?php

/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

mimport('framework.log.logger');

class MLoggerSysLog extends MLogger {

    protected $priorities = array(
        MLog::EMERGENCY => 'EMERG',
        MLog::ALERT     => 'ALERT',
        MLog::CRITICAL  => 'CRIT',
        MLog::ERROR     => 'ERR',
        MLog::WARNING   => 'WARNING',
        MLog::NOTICE    => 'NOTICE',
        MLog::INFO      => 'INFO',
        MLog::DEBUG     => 'DEBUG');

    public function __construct(array &$options) {
        // Call the parent constructor.
        parent::__construct($options);

        // Ensure that we have an identity string for the SysLog entries.
        if (empty($this->options['sys_ident'])) {
            $this->options['sys_ident'] = 'Joomla Platform';
        }

        // If the option to add the process id to SysLog entries is set use it, otherwise default to true.
        if (isset($this->options['sys_add_pid'])) {
            $this->options['sys_add_pid'] = (bool)$this->options['sys_add_pid'];
        }
        else {
            $this->options['sys_add_pid'] = true;
        }

        // If the option to also send SysLog entries to STDERR is set use it, otherwise default to false.
        if (isset($this->options['sys_use_stderr'])) {
            $this->options['sys_use_stderr'] = (bool)$this->options['sys_use_stderr'];
        }
        else {
            $this->options['sys_use_stderr'] = false;
        }

        // Build the SysLog options from our log object options.
        $sysOptions = 0;
        if ($this->options['sys_add_pid']) {
            $sysOptions = $sysOptions | LOG_PID;
        }
        if ($this->options['sys_use_stderr']) {
            $sysOptions = $sysOptions | LOG_PERROR;
        }

        // Open the SysLog connection.
        openlog((string)$this->options['sys_ident'], $sysOptions, LOG_USER);
    }

    public function __destruct() {
        closelog();
    }

    public function addEntry(MLogEntry $entry) {
        // Generate the value for the priority based on predefined constants.
        $priority = constant(strtoupper('LOG_' . $this->priorities[$entry->priority]));

        // Send the entry to SysLog.
        syslog($priority, '[' . $entry->category . '] ' . $entry->message);
    }
}