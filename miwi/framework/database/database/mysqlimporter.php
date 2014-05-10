<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/
defined('MIWI') or die('MIWI');

class MDatabaseImporterMySQL {

    protected $cache = array();
    protected $db = null;
    protected $from = array();
    protected $asFormat = 'xml';
    protected $options = null;

    public function __construct() {
        $this->options = new MObject;

        $this->cache = array('columns' => array(), 'keys' => array());

        // Set up the class defaults:

        // Import with only structure
        $this->withStructure();

        // Export as XML.
        $this->asXml();

    }

    public function asXml() {
        $this->asFormat = 'xml';

        return $this;
    }

    public function check() {
        // Check if the db connector has been set.
        if (!($this->db instanceof MDatabaseMySql)) {
            throw new Exception('MPLATFORM_ERROR_DATABASE_CONNECTOR_WRONG_TYPE');
        }

        // Check if the tables have been specified.
        if (empty($this->from)) {
            throw new Exception('MPLATFORM_ERROR_NO_TABLES_SPECIFIED');
        }

        return $this;
    }

    public function from($from) {
        $this->from = $from;

        return $this;
    }

    protected function getAddColumnSQL($table, SimpleXMLElement $field) {
        $sql = 'ALTER TABLE ' . $this->db->quoteName($table) . ' ADD COLUMN ' . $this->getColumnSQL($field);

        return $sql;
    }

    protected function getAddKeySQL($table, $keys) {
        $sql = 'ALTER TABLE ' . $this->db->quoteName($table) . ' ADD ' . $this->getKeySQL($keys);

        return $sql;
    }

    protected function getAlterTableSQL(SimpleXMLElement $structure) {
        // Initialise variables.
        $table     = $this->getRealTableName($structure['name']);
        $oldFields = $this->db->getTableColumns($table);
        $oldKeys   = $this->db->getTableKeys($table);
        $alters    = array();

        // Get the fields and keys from the XML that we are aiming for.
        $newFields = $structure->xpath('field');
        $newKeys   = $structure->xpath('key');

        // Loop through each field in the new structure.
        foreach ($newFields as $field) {
            $fName = (string)$field['Field'];

            if (isset($oldFields[$fName])) {
                // The field exists, check it's the same.
                $column = $oldFields[$fName];

                // Test whether there is a change.
                $change = ((string)$field['Type'] != $column->Type) || ((string)$field['Null'] != $column->Null)
                    || ((string)$field['Default'] != $column->Default) || ((string)$field['Extra'] != $column->Extra);

                if ($change) {
                    $alters[] = $this->getChangeColumnSQL($table, $field);
                }

                // Unset this field so that what we have left are fields that need to be removed.
                unset($oldFields[$fName]);
            }
            else {
                // The field is new.
                $alters[] = $this->getAddColumnSQL($table, $field);
            }
        }

        // Any columns left are orphans
        foreach ($oldFields as $name => $column) {
            // Delete the column.
            $alters[] = $this->getDropColumnSQL($table, $name);
        }

        // Get the lookups for the old and new keys.
        $oldLookup = $this->getKeyLookup($oldKeys);
        $newLookup = $this->getKeyLookup($newKeys);

        // Loop through each key in the new structure.
        foreach ($newLookup as $name => $keys) {
            // Check if there are keys on this field in the existing table.
            if (isset($oldLookup[$name])) {
                $same     = true;
                $newCount = count($newLookup[$name]);
                $oldCount = count($oldLookup[$name]);

                // There is a key on this field in the old and new tables. Are they the same?
                if ($newCount == $oldCount) {
                    // Need to loop through each key and do a fine grained check.
                    for ($i = 0; $i < $newCount; $i++) {
                        $same = (((string)$newLookup[$name][$i]['Non_unique'] == $oldLookup[$name][$i]->Non_unique)
                            && ((string)$newLookup[$name][$i]['Column_name'] == $oldLookup[$name][$i]->Column_name)
                            && ((string)$newLookup[$name][$i]['Seq_in_index'] == $oldLookup[$name][$i]->Seq_in_index)
                            && ((string)$newLookup[$name][$i]['Collation'] == $oldLookup[$name][$i]->Collation)
                            && ((string)$newLookup[$name][$i]['Index_type'] == $oldLookup[$name][$i]->Index_type));

                        if (!$same) {
                            // Break out of the loop. No need to check further.
                            break;
                        }
                    }
                }
                else {
                    // Count is different, just drop and add.
                    $same = false;
                }

                if (!$same) {
                    $alters[] = $this->getDropKeySQL($table, $name);
                    $alters[] = $this->getAddKeySQL($table, $keys);
                }

                // Unset this field so that what we have left are fields that need to be removed.
                unset($oldLookup[$name]);
            }
            else {
                // This is a new key.
                $alters[] = $this->getAddKeySQL($table, $keys);
            }
        }

        // Any keys left are orphans.
        foreach ($oldLookup as $name => $keys) {
            if (strtoupper($name) == 'PRIMARY') {
                $alters[] = $this->getDropPrimaryKeySQL($table);
            }
            else {
                $alters[] = $this->getDropKeySQL($table, $name);
            }
        }

        return $alters;
    }

    protected function getChangeColumnSQL($table, SimpleXMLElement $field) {
        $sql = 'ALTER TABLE ' . $this->db->quoteName($table) . ' CHANGE COLUMN ' . $this->db->quoteName((string)$field['Field']) . ' '
            . $this->getColumnSQL($field);

        return $sql;
    }

    protected function getColumnSQL(SimpleXMLElement $field) {
        // Initialise variables.
        // TODO Incorporate into parent class and use $this.
        $blobs = array('text', 'smalltext', 'mediumtext', 'largetext');

        $fName    = (string)$field['Field'];
        $fType    = (string)$field['Type'];
        $fNull    = (string)$field['Null'];
        $fDefault = isset($field['Default']) ? (string)$field['Default'] : null;
        $fExtra   = (string)$field['Extra'];

        $sql = $this->db->quoteName($fName) . ' ' . $fType;

        if ($fNull == 'NO') {
            if (in_array($fType, $blobs) || $fDefault === null) {
                $sql .= ' NOT NULL';
            }
            else {
                // TODO Don't quote numeric values.
                $sql .= ' NOT NULL DEFAULT ' . $this->db->quote($fDefault);
            }
        }
        else {
            if ($fDefault === null) {
                $sql .= ' DEFAULT NULL';
            }
            else {
                // TODO Don't quote numeric values.
                $sql .= ' DEFAULT ' . $this->db->quote($fDefault);
            }
        }

        if ($fExtra) {
            $sql .= ' ' . strtoupper($fExtra);
        }

        return $sql;
    }

    protected function getDropColumnSQL($table, $name) {
        $sql = 'ALTER TABLE ' . $this->db->quoteName($table) . ' DROP COLUMN ' . $this->db->quoteName($name);

        return $sql;
    }

    protected function getDropKeySQL($table, $name) {
        $sql = 'ALTER TABLE ' . $this->db->quoteName($table) . ' DROP KEY ' . $this->db->quoteName($name);

        return $sql;
    }

    protected function getDropPrimaryKeySQL($table) {
        $sql = 'ALTER TABLE ' . $this->db->quoteName($table) . ' DROP PRIMARY KEY';

        return $sql;
    }

    protected function getKeyLookup($keys) {
        // First pass, create a lookup of the keys.
        $lookup = array();
        foreach ($keys as $key) {
            if ($key instanceof SimpleXMLElement) {
                $kName = (string)$key['Key_name'];
            }
            else {
                $kName = $key->Key_name;
            }
            if (empty($lookup[$kName])) {
                $lookup[$kName] = array();
            }
            $lookup[$kName][] = $key;
        }

        return $lookup;
    }

    protected function getKeySQL($columns) {
        // TODO Error checking on array and element types.

        $kNonUnique = (string)$columns[0]['Non_unique'];
        $kName      = (string)$columns[0]['Key_name'];
        $kColumn    = (string)$columns[0]['Column_name'];

        $prefix = '';
        if ($kName == 'PRIMARY') {
            $prefix = 'PRIMARY ';
        }
        elseif ($kNonUnique == 0) {
            $prefix = 'UNIQUE ';
        }

        $nColumns = count($columns);
        $kColumns = array();

        if ($nColumns == 1) {
            $kColumns[] = $this->db->quoteName($kColumn);
        }
        else {
            foreach ($columns as $column) {
                $kColumns[] = (string)$column['Column_name'];
            }
        }

        $sql = $prefix . 'KEY ' . ($kName != 'PRIMARY' ? $this->db->quoteName($kName) : '') . ' (' . implode(',', $kColumns) . ')';

        return $sql;
    }

    protected function getRealTableName($table) {
        // TODO Incorporate into parent class and use $this.
        $prefix = $this->db->getPrefix();

        // Replace the magic prefix if found.
        $table = preg_replace('|^#__|', $prefix, $table);

        return $table;
    }

    protected function mergeStructure() {
        // Initialise variables.
        $prefix = $this->db->getPrefix();
        $tables = $this->db->getTableList();

        if ($this->from instanceof SimpleXMLElement) {
            $xml = $this->from;
        }
        else {
            $xml = new SimpleXMLElement($this->from);
        }

        // Get all the table definitions.
        $xmlTables = $xml->xpath('database/table_structure');

        foreach ($xmlTables as $table) {
            // Convert the magic prefix into the real table name.
            $tableName = (string)$table['name'];
            $tableName = preg_replace('|^#__|', $prefix, $tableName);

            if (in_array($tableName, $tables)) {
                // The table already exists. Now check if there is any difference.
                if ($queries = $this->getAlterTableSQL($xml->database->table_structure)) {
                    // Run the queries to upgrade the data structure.
                    foreach ($queries as $query) {
                        $this->db->setQuery((string)$query);
                        if (!$this->db->execute()) {
                            $this->addLog('Fail: ' . $this->db->getQuery());
                            throw new Exception($this->db->getErrorMsg());
                        }
                        else {
                            $this->addLog('Pass: ' . $this->db->getQuery());
                        }
                    }

                }
            }
            else {
                // This is a new table.
                $sql = $this->xmlToCreate($table);

                $this->db->setQuery((string)$sql);
                if (!$this->db->execute()) {
                    $this->addLog('Fail: ' . $this->db->getQuery());
                    throw new Exception($this->db->getErrorMsg());
                }
                else {
                    $this->addLog('Pass: ' . $this->db->getQuery());
                }
            }
        }
    }

    public function setDbo(MDatabaseMySQL $db) {
        $this->db = $db;

        return $this;
    }

    public function withStructure($setting = true) {
        $this->options->set('with-structure', (boolean)$setting);

        return $this;
    }
}