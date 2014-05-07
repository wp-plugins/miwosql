<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

mimport('framework.filesystem.folder');

interface MDatabaseInterface {

    public static function test();
}

abstract class MDatabase implements MDatabaseInterface {

    private $_database;
    public $name;
    protected $connection;
    protected $count = 0;
    protected $cursor;
    protected $debug = false;
    protected $limit = 0;
    protected $log = array();
    protected $nameQuote;
    protected $nullDate;
    protected $offset = 0;
    protected $sql;
    protected $tablePrefix;
    protected $utf = true;
    protected $errorNum = 0;
    protected $errorMsg;
    protected $hasQuoted = false;
    protected $quoted = array();
    protected static $instances = array();
    protected $dbMinimum;

    public static function getConnectors() {
        // Instantiate variables.
        $connectors = array();

        // Get a list of types.
        $types = MFolder::files(dirname(__FILE__) . '/database');

        // Loop through the types and find the ones that are available.
        foreach ($types as $type) {
            // Ignore some files.
            if (($type == 'index.html') || stripos($type, 'importer') || stripos($type, 'exporter') || stripos($type, 'query') || stripos($type, 'exception')) {
                continue;
            }

            // Derive the class name from the type.
            $class = str_ireplace(array('.php', 'sql'), array('', 'SQL'), 'MDatabase' . ucfirst(trim($type)));

            // If the class doesn't exist, let's look for it and register it.
            if (!class_exists($class)) {
                // Derive the file path for the driver class.
                $path = dirname(__FILE__) . '/database/' . $type;

                // If the file exists register the class with our class loader.
                if (file_exists($path)) {
                    MLoader::register($class, $path);
                }
                // If it doesn't exist we are at an impasse so move on to the next type.
                else {
                    continue;
                }
            }

            // If the class still doesn't exist we have nothing left to do but look at the next type.  We did our best.
            if (!class_exists($class)) {
                continue;
            }

            // Sweet!  Our class exists, so now we just need to know if it passes it's test method.
            if (call_user_func_array(array($class, 'test'), array())) {
                // Connector names should not have file extensions.
                $connectors[] = str_ireplace('.php', '', $type);
            }
        }

        return $connectors;
    }

    public static function getInstance($options = array()) {
        // Sanitize the database connector options.
        $options['driver']   = (isset($options['driver'])) ? preg_replace('/[^A-Z0-9_\.-]/i', '', $options['driver']) : 'mysql';
        $options['database'] = (isset($options['database'])) ? $options['database'] : null;
        $options['select']   = (isset($options['select'])) ? $options['select'] : true;

        // Get the options signature for the database connector.
        $signature = md5(serialize($options));

        // If we already have a database connector instance for these options then just use that.
        if (empty(self::$instances[$signature])) {

            // Derive the class name from the driver.
            $class = 'MDatabase' . ucfirst($options['driver']);

            // If the class doesn't exist, let's look for it and register it.
            if (!class_exists($class)) {

                // Derive the file path for the driver class.
                $path = dirname(__FILE__) . '/database/' . $options['driver'] . '.php';

                // If the file exists register the class with our class loader.
                if (file_exists($path)) {
                    MLoader::register($class, $path);
                }
                // If it doesn't exist we are at an impasse so throw an exception.
                else {

                    // Legacy error handling switch based on the MError::$legacy switch.
                    // @deprecated  12.1

                    if (MError::$legacy) {
                        // Deprecation warning.
                        MLog::add('MError is deprecated.', MLog::WARNING, 'deprecated');
                        MError::setErrorHandling(E_ERROR, 'die');

                        return MError::raiseError(500, MText::sprintf('MLIB_DATABASE_ERROR_LOAD_DATABASE_DRIVER', $options['driver']));
                    }
                    else {
                        throw new MDatabaseException(MText::sprintf('MLIB_DATABASE_ERROR_LOAD_DATABASE_DRIVER', $options['driver']));
                    }
                }
            }

            // If the class still doesn't exist we have nothing left to do but throw an exception.  We did our best.
            if (!class_exists($class)) {


                if (MError::$legacy) {
                    // Deprecation warning.
                    MLog::add('MError() is deprecated.', MLog::WARNING, 'deprecated');

                    MError::setErrorHandling(E_ERROR, 'die');

                    return MError::raiseError(500, MText::sprintf('MLIB_DATABASE_ERROR_LOAD_DATABASE_DRIVER', $options['driver']));
                }
                else {
                    throw new MDatabaseException(MText::sprintf('MLIB_DATABASE_ERROR_LOAD_DATABASE_DRIVER', $options['driver']));
                }
            }

            // Create our new MDatabase connector based on the options given.
            try {
                $instance = new $class($options);
            } catch (MDatabaseException $e) {

                // Legacy error handling switch based on the MError::$legacy switch.
                // @deprecated  12.1

                if (MError::$legacy) {
                    // Deprecation warning.
                    MLog::add('MError() is deprecated.', MLog::WARNING, 'deprecated');

                    MError::setErrorHandling(E_ERROR, 'ignore');

                    return MError::raiseError(500, MText::sprintf('MLIB_DATABASE_ERROR_CONNECT_DATABASE', $e->getMessage()));
                }
                else {
                    throw new MDatabaseException(MText::sprintf('MLIB_DATABASE_ERROR_CONNECT_DATABASE', $e->getMessage()));
                }
            }

            // Set the new connector to the global instances based on signature.
            self::$instances[$signature] = $instance;
        }

        return self::$instances[$signature];
    }

    public static function splitSql($sql) {
        $start   = 0;
        $open    = false;
        $char    = '';
        $end     = strlen($sql);
        $queries = array();

        for ($i = 0; $i < $end; $i++) {
            $current = substr($sql, $i, 1);
            if (($current == '"' || $current == '\'')) {
                $n = 2;

                while (substr($sql, $i - $n + 1, 1) == '\\' && $n < $i) {
                    $n++;
                }

                if ($n % 2 == 0) {
                    if ($open) {
                        if ($current == $char) {
                            $open = false;
                            $char = '';
                        }
                    }
                    else {
                        $open = true;
                        $char = $current;
                    }
                }
            }

            if (($current == ';' && !$open) || $i == $end - 1) {
                $queries[] = substr($sql, $start, ($i - $start + 1));
                $start     = $i + 1;
            }
        }

        return $queries;
    }

    public function __call($method, $args) {
        if (empty($args)) {
            return;
        }

        switch ($method) {
            case 'q':
                return $this->quote($args[0], isset($args[1]) ? $args[1] : true);
                break;
            case 'nq':
            case 'qn':
                return $this->quoteName($args[0], isset($args[1]) ? $args[1] : null);
                break;
        }
    }

    protected function __construct($options) {
        // Initialise object variables.
        $this->_database = (isset($options['database'])) ? $options['database'] : '';

        $this->tablePrefix = (isset($options['prefix'])) ? $options['prefix'] : 'wp_';
        $this->count       = 0;
        $this->errorNum    = 0;
        $this->log         = array();
        $this->quoted      = array();
        $this->hasQuoted   = false;

        // Set charactersets (needed for MySQL 4.1.2+).
        $this->setUTF();
    }

    public function addQuoted($quoted) {
        // Deprecation warning.
        MLog::add('MDatabase::addQuoted() is deprecated.', MLog::WARNING, 'deprecated');

        if (is_string($quoted)) {
            $this->quoted[] = $quoted;
        }
        else {
            $this->quoted = array_merge($this->quoted, (array)$quoted);
        }

        $this->hasQuoted = true;
    }

    abstract public function connected();

    public abstract function dropTable($table, $ifExists = true);

    abstract public function escape($text, $extra = false);

    abstract protected function fetchArray($cursor = null);

    abstract protected function fetchAssoc($cursor = null);

    abstract protected function fetchObject($cursor = null, $class = 'stdClass');

    abstract protected function freeResult($cursor = null);

    abstract public function getAffectedRows();

    abstract public function getCollation();

    public function getConnection() {
        return $this->connection;
    }

    public function getCount() {
        return $this->count;
    }

    protected function getDatabase() {
        return $this->_database;
    }

    public function getDateFormat() {
        return 'Y-m-d H:i:s';
    }

    public function getLimit() {
        return $this->limit;
    }

    public function getLog() {
        return $this->log;
    }

    public function getMinimum() {
        return $this->dbMinimum;
    }

    public function getNullDate() {
        return $this->nullDate;
    }

    abstract public function getNumRows($cursor = null);

    public function getOffset() {
        return $this->offset;
    }

    public function getPrefix() {
        return $this->tablePrefix;
    }

    abstract public function getQuery($new = false);

    abstract public function getTableColumns($table, $typeOnly = true);

    abstract public function getTableCreate($tables);

    abstract public function getTableKeys($tables);

    abstract public function getTableList();

    public function getUTFSupport() {
        return $this->utf;
    }

    abstract public function getVersion();

    abstract public function hasUTF();

    abstract public function insertid();

    public function insertObject($table, &$object, $key = null) {
        // Initialise variables.
        $fields = array();
        $values = array();

        // Create the base insert statement.
        $statement = 'INSERT INTO ' . $this->quoteName($table) . ' (%s) VALUES (%s)';

        // Iterate over the object variables to build the query fields and values.
        foreach (get_object_vars($object) as $k => $v) {
            // Only process non-null scalars.
            if (is_array($v) or is_object($v) or $v === null) {
                continue;
            }

            // Ignore any internal fields.
            if ($k[0] == '_') {
                continue;
            }

            // Prepare and sanitize the fields and values for the database query.
            $fields[] = $this->quoteName($k);
            $values[] = $this->quote($v);
        }

        // Set the query and execute the insert.
        $this->setQuery(sprintf($statement, implode(',', $fields), implode(',', $values)));
        if (!$this->execute()) {
			return false;
		}

        // Update the primary key if it exists.
        $id = $this->insertid();
        if ($key && $id) {
            $object->$key = $id;
        }

        return true;
    }

    public function isMinimumVersion() {
        return version_compare($this->getVersion(), $this->dbMinimum) >= 0;
    }

    public function loadAssoc() {
        // Initialise variables.
        $ret = null;

        // Execute the query and get the result set cursor.
        if (!($cursor = $this->execute())) {
            return null;
        }

        // Get the first row from the result set as an associative array.
        if ($array = $this->fetchAssoc($cursor)) {
            $ret = $array;
        }

        // Free up system resources and return.
        $this->freeResult($cursor);

        return $ret;
    }

    public function loadAssocList($key = null, $column = null) {
        // Initialise variables.
        $array = array();

        // Execute the query and get the result set cursor.
        if (!($cursor = $this->execute())) {
            return null;
        }

        // Get all of the rows from the result set.
        while ($row = $this->fetchAssoc($cursor)) {
            $value = ($column) ? (isset($row[$column]) ? $row[$column] : $row) : $row;
            if ($key) {
                $array[$row[$key]] = $value;
            }
            else {
                $array[] = $value;
            }
        }

        // Free up system resources and return.
        $this->freeResult($cursor);

        return $array;
    }

    public function loadColumn($offset = 0) {
        // Initialise variables.
        $array = array();

        // Execute the query and get the result set cursor.
        if (!($cursor = $this->execute())) {
            return null;
        }

        // Get all of the rows from the result set as arrays.
        while ($row = $this->fetchArray($cursor)) {
            $array[] = $row[$offset];
        }

        // Free up system resources and return.
        $this->freeResult($cursor);

        return $array;
    }

    public function loadNextObject($class = 'stdClass') {
        static $cursor;

        // Execute the query and get the result set cursor.
        if (!($cursor = $this->execute())) {
            return $this->errorNum ? null : false;
        }

        // Get the next row from the result set as an object of type $class.
        if ($row = $this->fetchObject($cursor, $class)) {
            return $row;
        }

        // Free up system resources and return.
        $this->freeResult($cursor);
        $cursor = null;

        return false;
    }

    public function loadNextRow() {
        static $cursor;

        // Execute the query and get the result set cursor.
        if (!($cursor = $this->execute())) {
            return $this->errorNum ? null : false;
        }

        // Get the next row from the result set as an object of type $class.
        if ($row = $this->fetchArray($cursor)) {
            return $row;
        }

        // Free up system resources and return.
        $this->freeResult($cursor);
        $cursor = null;

        return false;
    }

    public function loadObject($class = 'stdClass') {
        // Initialise variables.
        $ret = null;

        // Execute the query and get the result set cursor.
        if (!($cursor = $this->execute())) {
            return null;
        }

        // Get the first row from the result set as an object of type $class.
        if ($object = $this->fetchObject($cursor, $class)) {
            $ret = $object;
        }

        // Free up system resources and return.
        $this->freeResult($cursor);

        return $ret;
    }

    public function loadObjectList($key = '', $class = 'stdClass') {
        // Initialise variables.
        $array = array();

        // Execute the query and get the result set cursor.
        if (!($cursor = $this->execute())) {
            return null;
        }

        // Get all of the rows from the result set as objects of type $class.
        while ($row = $this->fetchObject($cursor, $class)) {
            if ($key) {
                $array[$row->$key] = $row;
            }
            else {
                $array[] = $row;
            }
        }

        // Free up system resources and return.
        $this->freeResult($cursor);

        return $array;
    }

    public function loadResult() {
        // Initialise variables.
        $ret = null;

        // Execute the query and get the result set cursor.
        if (!($cursor = $this->execute())) {
            return null;
        }

        // Get the first row from the result set as an array.
        if ($row = $this->fetchArray($cursor)) {
            $ret = $row[0];
        }

        // Free up system resources and return.
        $this->freeResult($cursor);

        return $ret;
    }

    public function loadRow() {
        // Initialise variables.
        $ret = null;

        // Execute the query and get the result set cursor.
        if (!($cursor = $this->execute())) {
            return null;
        }

        // Get the first row from the result set as an array.
        if ($row = $this->fetchArray($cursor)) {
            $ret = $row;
        }

        // Free up system resources and return.
        $this->freeResult($cursor);

        return $ret;
    }

    public function loadRowList($key = null) {
        // Initialise variables.
        $array = array();

        // Execute the query and get the result set cursor.
        if (!($cursor = $this->execute())) {
            return null;
        }

        // Get all of the rows from the result set as arrays.
        while ($row = $this->fetchArray($cursor)) {
            if ($key !== null) {
                $array[$row[$key]] = $row;
            }
            else {
                $array[] = $row;
            }
        }

        // Free up system resources and return.
        $this->freeResult($cursor);

        return $array;
    }

    public abstract function lockTable($tableName);

    public function query() {
        return $this->execute();
    }

    abstract public function execute();

    public function quote($text, $escape = true) {
        return '\'' . ($escape ? $this->escape($text) : $text) . '\'';
    }

    public function quoteName($name, $as = null) {
        if (is_string($name)) {
            $quotedName = $this->quoteNameStr(explode('.', $name));

            $quotedAs = '';
            if (!is_null($as)) {
                settype($as, 'array');
                $quotedAs .= ' AS ' . $this->quoteNameStr($as);
            }

            return $quotedName . $quotedAs;
        }
        else {
            $fin = array();

            if (is_null($as)) {
                foreach ($name as $str) {
                    $fin[] = $this->quoteName($str);
                }
            }
            elseif (is_array($name) && (count($name) == count($as))) {
                for ($i = 0; $i < count($name); $i++) {
                    $fin[] = $this->quoteName($name[$i], $as[$i]);
                }
            }

            return $fin;
        }
    }

    protected function quoteNameStr($strArr) {
        $parts = array();
        $q     = $this->nameQuote;

        foreach ($strArr as $part) {
            if (is_null($part)) {
                continue;
            }

            if (strlen($q) == 1) {
                $parts[] = $q . $part . $q;
            }
            else {
                $parts[] = $q{0} . $part . $q{1};
            }
        }

        return implode('.', $parts);
    }

    public function replacePrefix($sql, $prefix = '#__') {
        // Initialize variables.
        $escaped   = false;
        $startPos  = 0;
        $quoteChar = '';
        $literal   = '';

        $sql = trim($sql);
        $n   = strlen($sql);

        while ($startPos < $n) {
            $ip = strpos($sql, $prefix, $startPos);
            if ($ip === false) {
                break;
            }

            $j = strpos($sql, "'", $startPos);
            $k = strpos($sql, '"', $startPos);
            if (($k !== false) && (($k < $j) || ($j === false))) {
                $quoteChar = '"';
                $j         = $k;
            }
            else {
                $quoteChar = "'";
            }

            if ($j === false) {
                $j = $n;
            }

            $literal .= str_replace($prefix, $this->tablePrefix, substr($sql, $startPos, $j - $startPos));
            $startPos = $j;

            $j = $startPos + 1;

            if ($j >= $n) {
                break;
            }

            // quote comes first, find end of quote
            while (true) {
                $k       = strpos($sql, $quoteChar, $j);
                $escaped = false;
                if ($k === false) {
                    break;
                }
                $l = $k - 1;
                while ($l >= 0 && $sql{$l} == '\\') {
                    $l--;
                    $escaped = !$escaped;
                }
                if ($escaped) {
                    $j = $k + 1;
                    continue;
                }
                break;
            }
            if ($k === false) {
                // error in the query - no end quote; ignore it
                break;
            }
            $literal .= substr($sql, $startPos, $k - $startPos + 1);
            $startPos = $k + 1;
        }
        if ($startPos < $n) {
            $literal .= substr($sql, $startPos, $n - $startPos);
        }

        return $literal;
    }

    public abstract function renameTable($oldTable, $newTable, $backup = null, $prefix = null);

    abstract public function select($database);

    public function setDebug($level) {
        $previous    = $this->debug;
        $this->debug = (bool)$level;

        return $previous;
    }

    public function setQuery($query, $offset = 0, $limit = 0) {
        $this->sql    = $query;
        $this->limit  = (int)max(0, $limit);
        $this->offset = (int)max(0, $offset);

        return $this;
    }

    abstract public function setUTF();

    abstract public function transactionCommit();

    abstract public function transactionRollback();

    abstract public function transactionStart();

    public function truncateTable($table) {
        $this->setQuery('TRUNCATE TABLE ' . $this->quoteName($table));
        $this->execute();
    }

    public function updateObject($table, &$object, $key, $nulls = false) {
        // Initialise variables.
        $fields = array();
        $where  = '';

        // Create the base update statement.
        $statement = 'UPDATE ' . $this->quoteName($table) . ' SET %s WHERE %s';

        // Iterate over the object variables to build the query fields/value pairs.
        foreach (get_object_vars($object) as $k => $v) {
            // Only process scalars that are not internal fields.
            if (is_array($v) or is_object($v) or $k[0] == '_') {
                continue;
            }

            // Set the primary key to the WHERE clause instead of a field to update.
            if ($k == $key) {
                $where = $this->quoteName($k) . '=' . $this->quote($v);
                continue;
            }

            // Prepare and sanitize the fields and values for the database query.
            if ($v === null) {
                // If the value is null and we want to update nulls then set it.
                if ($nulls) {
                    $val = 'NULL';
                }
                // If the value is null and we do not want to update nulls then ignore this field.
                else {
                    continue;
                }
            }
            // The field is not null so we prep it for update.
            else {
                $val = $this->quote($v);
            }

            // Add the field to be updated.
            $fields[] = $this->quoteName($k) . '=' . $val;
        }

        // We don't have any fields to update.
        if (empty($fields)) {
            return true;
        }

        // Set the query and execute the update.
        $this->setQuery(sprintf($statement, implode(",", $fields), $where));

        return $this->execute();
    }

    public abstract function unlockTables();

    //
    // Deprecated methods.
    //

    public function debug($level) {
        // Deprecation warning.
        MLog::add('MDatabase::debug() is deprecated, use MDatabase::setDebug() instead.', MLog::NOTICE, 'deprecated');

        $this->setDebug(($level == 0) ? false : true);
    }

    abstract public function explain();

    public function getErrorMsg($escaped = false) {
        // Deprecation warning.
        MLog::add('MDatabase::getErrorMsg() is deprecated, use exception handling instead.', MLog::WARNING, 'deprecated');

        if ($escaped) {
            return addslashes($this->errorMsg);
        }
        else {
            return $this->errorMsg;
        }
    }

    public function getErrorNum() {
        // Deprecation warning.
        MLog::add('MDatabase::getErrorNum() is deprecated, use exception handling instead.', MLog::WARNING, 'deprecated');

        return $this->errorNum;
    }

    public function getEscaped($text, $extra = false) {
        // Deprecation warning.
        MLog::add('MDatabase::getEscaped() is deprecated. Use MDatabase::escape().', MLog::WARNING, 'deprecated');

        return $this->escape($text, $extra);
    }

    public function getTableFields($tables, $typeOnly = true) {
        // Deprecation warning.
        MLog::add('MDatabase::getTableFields() is deprecated. Use MDatabase::getTableColumns().', MLog::WARNING, 'deprecated');

        $results = array();

        settype($tables, 'array');

        foreach ($tables as $table) {
            $results[$table] = $this->getTableColumns($table, $typeOnly);
        }

        return $results;
    }

    public function getTicker() {
        // Deprecation warning.
        MLog::add('MDatabase::getTicker() is deprecated, use MDatabase::getCount() instead.', MLog::NOTICE, 'deprecated');

        return $this->count;
    }

    public function isQuoted($field) {
        // Deprecation warning.
        MLog::add('MDatabase::isQuoted() is deprecated.', MLog::WARNING, 'deprecated');

        if ($this->hasQuoted) {
            return in_array($field, $this->quoted);
        }
        else {
            return true;
        }
    }

    public function loadResultArray($offset = 0) {
        // Deprecation warning.
        MLog::add('MDatabase::loadResultArray() is deprecated. Use MDatabase::loadColumn().', MLog::WARNING, 'deprecated');

        return $this->loadColumn($offset);
    }

    public function nameQuote($name) {
        // Deprecation warning.
        MLog::add('MDatabase::nameQuote() is deprecated. Use MDatabase::quoteName().', MLog::WARNING, 'deprecated');

        return $this->quoteName($name);
    }

    abstract public function queryBatch($abortOnError = true, $transactionSafe = false);

    public function stderr($showSQL = false) {
        // Deprecation warning.
        MLog::add('MDatabase::stderr() is deprecated.', MLog::WARNING, 'deprecated');

        if ($this->errorNum != 0) {
            return MText::sprintf('MLIB_DATABASE_ERROR_FUNCTION_FAILED', $this->errorNum, $this->errorMsg)
            . ($showSQL ? "<br />SQL = <pre>$this->sql</pre>" : '');
        }
        else {
            return MText::_('MLIB_DATABASE_FUNCTION_NOERROR');
        }
    }
}