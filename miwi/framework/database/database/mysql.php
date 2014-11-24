<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/
defined('MIWI') or die('MIWI');

MLoader::register('MDatabaseQueryMySQL', dirname(__FILE__) . '/mysqlquery.php');
MLoader::register('MDatabaseExporterMySQL', dirname(__FILE__) . '/mysqlexporter.php');
MLoader::register('MDatabaseImporterMySQL', dirname(__FILE__) . '/mysqlimporter.php');

class MDatabaseMySQL extends MDatabase {

    public $name = 'mysql';
    protected $nameQuote = '`';
    protected $nullDate = '0000-00-00 00:00:00';
    protected $dbMinimum = '5.0.4';

    protected function __construct($options) {
        // Get some basic values from the options.
        $options['host']     = (isset($options['host'])) ? $options['host'] : 'localhost';
        $options['user']     = (isset($options['user'])) ? $options['user'] : 'root';
        $options['password'] = (isset($options['password'])) ? $options['password'] : '';
        $options['database'] = (isset($options['database'])) ? $options['database'] : '';
        $options['select']   = (isset($options['select'])) ? (bool)$options['select'] : true;

        // Make sure the MySQL extension for PHP is installed and enabled.
        if (!function_exists('mysql_connect')) {

            if (MError::$legacy) {
                $this->errorNum = 1;
                $this->errorMsg = MText::_('MLIB_DATABASE_ERROR_ADAPTER_MYSQL');

                return;
            }
            else {
                throw new MDatabaseException(MText::_('MLIB_DATABASE_ERROR_ADAPTER_MYSQL'));
            }
        }

        // Attempt to connect to the server.
        if (!($this->connection = @ mysql_connect($options['host'], $options['user'], $options['password'], true))) {

            // Legacy error handling switch based on the MError::$legacy switch.
            // @deprecated  12.1
            if (MError::$legacy) {
                $this->errorNum = 2;
                $this->errorMsg = MText::_('MLIB_DATABASE_ERROR_CONNECT_MYSQL');

                return;
            }
            else {
                throw new MDatabaseException(MText::_('MLIB_DATABASE_ERROR_CONNECT_MYSQL'));
            }
        }

        // Finalize initialisation
        parent::__construct($options);

        // Set sql_mode to non_strict mode
        mysql_query("SET @@SESSION.sql_mode = '';", $this->connection);

        // If auto-select is enabled select the given database.
        if ($options['select'] && !empty($options['database'])) {
            $this->select($options['database']);
        }
    }

    public function __destruct() {
        if (is_resource($this->connection)) {
            mysql_close($this->connection);
        }
    }

    public function escape($text, $extra = false) {
		$text = stripslashes($text);
        $result = mysql_real_escape_string($text, $this->getConnection());

        if ($extra) {
            $result = addcslashes($result, '%_');
        }

        return $result;
    }

    public static function test() {
        return (function_exists('mysql_connect'));
    }

    public function connected() {
        if (is_resource($this->connection)) {
            return mysql_ping($this->connection);
        }

        return false;
    }

    public function dropTable($tableName, $ifExists = true) {
        $query = $this->getQuery(true);

        $this->setQuery('DROP TABLE ' . ($ifExists ? 'IF EXISTS ' : '') . $query->quoteName($tableName));

        $this->execute();

        return $this;
    }

    public function getAffectedRows() {
        return mysql_affected_rows($this->connection);
    }

    public function getCollation() {
        $this->setQuery('SHOW FULL COLUMNS FROM #__posts');
        $array = $this->loadAssocList();

        return $array['2']['Collation'];
    }

    public function getExporter() {
        // Make sure we have an exporter class for this driver.
        if (!class_exists('MDatabaseExporterMySQL')) {
            throw new MDatabaseException(MText::_('MLIB_DATABASE_ERROR_MISSING_EXPORTER'));
        }

        $o = new MDatabaseExporterMySQL;
        $o->setDbo($this);

        return $o;
    }

    public function getImporter() {
        // Make sure we have an importer class for this driver.
        if (!class_exists('MDatabaseImporterMySQL')) {
            throw new MDatabaseException(MText::_('MLIB_DATABASE_ERROR_MISSING_IMPORTER'));
        }

        $o = new MDatabaseImporterMySQL;
        $o->setDbo($this);

        return $o;
    }

    public function getNumRows($cursor = null) {
        return mysql_num_rows($cursor ? $cursor : $this->cursor);
    }

    public function getQuery($new = false) {
        if ($new) {
            // Make sure we have a query class for this driver.
            if (!class_exists('MDatabaseQueryMySQL')) {
                throw new MDatabaseException(MText::_('MLIB_DATABASE_ERROR_MISSING_QUERY'));
            }

            return new MDatabaseQueryMySQL($this);
        }
        else {
            return $this->sql;
        }
    }

    public function getTableCreate($tables) {
        // Initialise variables.
        $result = array();

        // Sanitize input to an array and iterate over the list.
        settype($tables, 'array');
        foreach ($tables as $table) {
            // Set the query to get the table CREATE statement.
            $this->setQuery('SHOW CREATE table ' . $this->quoteName($this->escape($table)));
            $row = $this->loadRow();

            // Populate the result array based on the create statements.
            $result[$table] = $row[1];
        }

        return $result;
    }

    public function getTableColumns($table, $typeOnly = true) {
        $result = array();

        // Set the query to get the table fields statement.
        $this->setQuery('SHOW FULL COLUMNS FROM ' . $this->quoteName($this->escape($table)));
        $fields = $this->loadObjectList();

        // If we only want the type as the value add just that to the list.
        if ($typeOnly) {
            foreach ($fields as $field) {
                $result[$field->Field] = preg_replace("/[(0-9)]/", '', $field->Type);
            }
        }
        // If we want the whole field data object add that to the list.
        else {
            foreach ($fields as $field) {
                $result[$field->Field] = $field;
            }
        }

        return $result;
    }

    public function getTableKeys($table) {
        // Get the details columns information.
        $this->setQuery('SHOW KEYS FROM ' . $this->quoteName($table));
        $keys = $this->loadObjectList();

        return $keys;
    }

    public function getTableList() {
        // Set the query to get the tables statement.
        $this->setQuery('SHOW TABLES');
        $tables = $this->loadColumn();

        return $tables;
    }

    public function getVersion() {
        return mysql_get_server_info($this->connection);
    }

    public function hasUTF() {
        MLog::add('MDatabaseMySQL::hasUTF() is deprecated.', MLog::WARNING, 'deprecated');

        return true;
    }

    public function insertid() {
        return mysql_insert_id($this->connection);
    }

    public function lockTable($table) {
        $this->setQuery('LOCK TABLES ' . $this->quoteName($table) . ' WRITE')->execute();

        return $this;
    }

    public function execute() {
        if (!is_resource($this->connection)) {

            if (MError::$legacy) {
                if ($this->debug) {
                    MError::raiseError(500, 'MDatabaseMySQL::query: ' . $this->errorNum . ' - ' . $this->errorMsg);
                }

                return false;
            }
            else {
                MLog::add(MText::sprintf('MLIB_DATABASE_QUERY_FAILED', $this->errorNum, $this->errorMsg), MLog::ERROR, 'database');
                throw new MDatabaseException($this->errorMsg, $this->errorNum);
            }
        }

        // Take a local copy so that we don't modify the original query and cause issues later
        $sql = $this->replacePrefix((string)$this->sql);
        if ($this->limit > 0 || $this->offset > 0) {
            $sql .= ' LIMIT ' . $this->offset . ', ' . $this->limit;
        }

        // If debugging is enabled then let's log the query.
        if ($this->debug) {
            // Increment the query counter and add the query to the object queue.
            $this->count++;
            $this->log[] = $sql;

            MLog::add($sql, MLog::DEBUG, 'databasequery');
        }

        // Reset the error values.
        $this->errorNum = 0;
        $this->errorMsg = '';

		#for-multi-db
        global $wpdb;
        $multidb_file = MPATH_WP_CNT . '/db.php';
        if(!empty($wpdb) and file_exists($multidb_file)){
            $this->connection = $wpdb->db_connect($sql); #for-multi-db
            $sql = $wpdb->sanitize_multidb_query_tables($sql);
        }
		
        // Execute the query.
        $this->cursor = mysql_query($sql, $this->connection);

        // If an error occurred handle it.
        if (!$this->cursor) {
            $this->errorNum = (int)mysql_errno($this->connection);
            $this->errorMsg = (string)mysql_error($this->connection) . ' SQL=' . $sql;

            if (MError::$legacy) {
                if ($this->debug) {
                    MError::raiseError(500, 'MDatabaseMySQL::query: ' . $this->errorNum . ' - ' . $this->errorMsg);
                }

                return false;
            }
            else {
                MLog::add(MText::sprintf('MLIB_DATABASE_QUERY_FAILED', $this->errorNum, $this->errorMsg), MLog::ERROR, 'databasequery');
                throw new MDatabaseException($this->errorMsg, $this->errorNum);
            }
        }

        return $this->cursor;
    }

    public function renameTable($oldTable, $newTable, $backup = null, $prefix = null) {
        $this->setQuery('RENAME TABLE ' . $oldTable . ' TO ' . $newTable)->execute();

        return $this;
    }

    public function select($database) {
        if (!$database) {
            return false;
        }

        if (!mysql_select_db($database, $this->connection)) {
            // Legacy error handling switch based on the MError::$legacy switch.
            // @deprecated  12.1
            if (MError::$legacy) {
                $this->errorNum = 3;
                $this->errorMsg = MText::_('MLIB_DATABASE_ERROR_DATABASE_CONNECT');

                return false;
            }
            else {
                throw new MDatabaseException(MText::_('MLIB_DATABASE_ERROR_DATABASE_CONNECT'));
            }
        }

        return true;
    }

    public function setUTF() {
        return mysql_query("SET NAMES 'utf8'", $this->connection);
    }

    public function transactionCommit() {
        $this->setQuery('COMMIT');
        $this->execute();
    }

    public function transactionRollback() {
        $this->setQuery('ROLLBACK');
        $this->execute();
    }

    public function transactionStart() {
        $this->setQuery('START TRANSACTION');
        $this->execute();
    }

    protected function fetchArray($cursor = null) {
        return mysql_fetch_row($cursor ? $cursor : $this->cursor);
    }

    protected function fetchAssoc($cursor = null) {
        return mysql_fetch_assoc($cursor ? $cursor : $this->cursor);
    }

    protected function fetchObject($cursor = null, $class = 'stdClass') {
        return mysql_fetch_object($cursor ? $cursor : $this->cursor, $class);
    }

    protected function freeResult($cursor = null) {
        mysql_free_result($cursor ? $cursor : $this->cursor);
    }

    public function explain() {
        // Deprecation warning.
        MLog::add('MDatabaseMySQL::explain() is deprecated.', MLog::WARNING, 'deprecated');

        // Backup the current query so we can reset it later.
        $backup = $this->sql;

        // Prepend the current query with EXPLAIN so we get the diagnostic data.
        $this->sql = 'EXPLAIN ' . $this->sql;

        // Execute the query and get the result set cursor.
        if (!($cursor = $this->execute())) {
            return null;
        }

        // Build the HTML table.
        $first  = true;
        $buffer = '<table id="explain-sql">';
        $buffer .= '<thead><tr><td colspan="99">' . $this->getQuery() . '</td></tr>';
        while ($row = $this->fetchAssoc($cursor)) {
            if ($first) {
                $buffer .= '<tr>';
                foreach ($row as $k => $v) {
                    $buffer .= '<th>' . $k . '</th>';
                }
                $buffer .= '</tr></thead><tbody>';
                $first = false;
            }
            $buffer .= '<tr>';
            foreach ($row as $k => $v) {
                $buffer .= '<td>' . $v . '</td>';
            }
            $buffer .= '</tr>';
        }
        $buffer .= '</tbody></table>';

        // Restore the original query to its state before we ran the explain.
        $this->sql = $backup;

        // Free up system resources and return.
        $this->freeResult($cursor);

        return $buffer;
    }

    public function queryBatch($abortOnError = true, $transactionSafe = false) {
        // Deprecation warning.
        MLog::add('MDatabaseMySQL::queryBatch() is deprecated.', MLog::WARNING, 'deprecated');

        $sql            = $this->replacePrefix((string)$this->sql);
        $this->errorNum = 0;
        $this->errorMsg = '';

        // If the batch is meant to be transaction safe then we need to wrap it in a transaction.
        if ($transactionSafe) {
            $sql = 'START TRANSACTION;' . rtrim($sql, "; \t\r\n\0") . '; COMMIT;';
        }
        $queries = $this->splitSql($sql);
        $error   = 0;
        foreach ($queries as $query) {
            $query = trim($query);
            if ($query != '') {
                $this->cursor = mysql_query($query, $this->connection);
                if ($this->debug) {
                    $this->count++;
                    $this->log[] = $query;
                }
                if (!$this->cursor) {
                    $error = 1;
                    $this->errorNum .= mysql_errno($this->connection) . ' ';
                    $this->errorMsg .= mysql_error($this->connection) . " SQL=$query <br />";
                    if ($abortOnError) {
                        return $this->cursor;
                    }
                }
            }
        }

        return $error ? false : true;
    }

    public function unlockTables() {
        $this->setQuery('UNLOCK TABLES')->execute();

        return $this;
    }
}