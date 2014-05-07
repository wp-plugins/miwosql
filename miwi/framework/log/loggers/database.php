<?php

/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

mimport('framework.log.logger');

class MLoggerDatabase extends MLogger {

    protected $driver = 'mysql';
    protected $host = '127.0.0.1';
    protected $user = 'root';
    protected $password = '';
    protected $database = 'logging';
    protected $table = 'jos_';
    protected $dbo;

    public function __construct(array &$options) {
        // Call the parent constructor.
        parent::__construct($options);

        // If both the database object and driver options are empty we want to use the system database connection.
        if (empty($this->options['db_object']) && empty($this->options['db_driver'])) {
            $this->dbo      = MFactory::getDBO();
            $this->driver   = MFactory::getConfig()->get('dbtype');
            $this->host     = MFactory::getConfig()->get('host');
            $this->user     = MFactory::getConfig()->get('user');
            $this->password = MFactory::getConfig()->get('password');
            $this->database = MFactory::getConfig()->get('db');
            $this->prefix   = MFactory::getConfig()->get('dbprefix');
        }
        // We need to get the database connection settings from the configuration options.
        else {
            $this->driver   = (empty($this->options['db_driver'])) ? 'mysql' : $this->options['db_driver'];
            $this->host     = (empty($this->options['db_host'])) ? '127.0.0.1' : $this->options['db_host'];
            $this->user     = (empty($this->options['db_user'])) ? 'root' : $this->options['db_user'];
            $this->password = (empty($this->options['db_pass'])) ? '' : $this->options['db_pass'];
            $this->database = (empty($this->options['db_database'])) ? 'logging' : $this->options['db_database'];
            $this->prefix   = (empty($this->options['db_prefix'])) ? 'jos_' : $this->options['db_prefix'];
        }

        // The table name is independent of how we arrived at the connection object.
        $this->table = (empty($this->options['db_table'])) ? '#__log_entries' : $this->options['db_table'];
    }

    public function addEntry(MLogEntry $entry) {
        // Connect to the database if not connected.
        if (empty($this->dbo)) {
            $this->connect();
        }

        // Convert the date.
        $entry->date = $entry->date->toSql();

        $this->dbo->insertObject($this->table, $entry);
    }

    protected function connect() {
        // Build the configuration object to use for MDatabase.
        $options = array(
            'driver'   => $this->driver,
            'host'     => $this->host,
            'user'     => $this->user,
            'password' => $this->password,
            'database' => $this->database,
            'prefix'   => $this->prefix);

        try {
            $db = MDatabase::getInstance($options);

            if ($db instanceof Exception) {
                throw new LogException('Database Error: ' . (string)$db);
            }

            if ($db->getErrorNum() > 0) {
                throw new LogException(MText::sprintf('MLIB_UTIL_ERROR_CONNECT_DATABASE', $db->getErrorNum(), $db->getErrorMsg()));
            }

            // Assign the database connector to the class.
            $this->dbo = $db;
        } catch (RuntimeException $e) {
            throw new LogException($e->getMessage());
        }
    }
}