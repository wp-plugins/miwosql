<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

abstract class MTable extends MObject {

    protected $_tbl = '';
    protected $_tbl_key = '';
    protected $_db;
    protected $_trackAssets = false;
    protected $_rules;
    protected $_locked = false;

    public function __construct($table, $key, &$db) {
        // Set internal variables.
        $this->_tbl     = $table;
        $this->_tbl_key = $key;
        $this->_db      = & $db;

        // Initialise the table properties.
        if ($fields = $this->getFields()) {
            foreach ($fields as $name => $v) {
                // Add the field if it is not already present.
                if (!property_exists($this, $name)) {
                    $this->$name = null;
                }
            }
        }

        // If we are tracking assets, make sure an access field exists and initially set the default.
        if (property_exists($this, 'asset_id')) {
            //$this->_trackAssets = true;
        }

        // If the access property exists, set the default.
        if (property_exists($this, 'access')) {
            $this->access = (int)MFactory::getConfig()->get('access');
        }
    }

    public function getFields() {
        static $cache = null;

        if ($cache === null) {
            // Lookup the fields for this table only once.
            $name   = $this->_tbl;
            $fields = $this->_db->getTableColumns($name, false);

            if (empty($fields)) {
                $e = new MException(MText::_('MLIB_DATABASE_ERROR_COLUMNS_NOT_FOUND'));
                $this->setError($e);

                return false;
            }
            $cache = $fields;
        }

        return $cache;
    }

    public static function getInstance($type, $prefix = 'MTable', $config = array()) {
        // Sanitize and prepare the table class name.
        $type       = preg_replace('/[^A-Z0-9_\.-]/i', '', $type);
        $tableClass = $prefix . ucfirst($type);

        // Only try to load the class if it doesn't already exist.
        if (!class_exists($tableClass)) {
            // Search for the class file in the MTable include paths.
            mimport('framework.filesystem.path');

            MTable::addIncludePath(MPATH_MIWI.'/proxy/database/table');
            if ($path = MPath::find(MTable::addIncludePath(), strtolower($type) . '.php')) {
                // Import the class file.
                include_once $path;

                // If we were unable to load the proper class, raise a warning and return false.
                if (!class_exists($tableClass)) {
                    MError::raiseWarning(0, MText::sprintf('MLIB_DATABASE_ERROR_CLASS_NOT_FOUND_IN_FILE', $tableClass));

                    return false;
                }
            }
            else {
                // If we were unable to find the class file in the MTable include paths, raise a warning and return false.
                MError::raiseWarning(0, MText::sprintf('MLIB_DATABASE_ERROR_NOT_SUPPORTED_FILE_NOT_FOUND', $type));

                return false;
            }
        }

        // If a database object was passed in the configuration array use it, otherwise get the global one from MFactory.
        $db = isset($config['dbo']) ? $config['dbo'] : MFactory::getDbo();

        // Instantiate a new table class and return it.
        return new $tableClass($db);
    }

    public static function addIncludePath($path = null) {
        // Declare the internal paths as a static variable.
        static $_paths;

        // If the internal paths have not been initialised, do so with the base table path.
        if (!isset($_paths)) {
            $_paths = array(dirname(__FILE__) . '/table');
        }

        // Convert the passed path(s) to add to an array.
        settype($path, 'array');

        // If we have new paths to add, do so.
        if (!empty($path) && !in_array($path, $_paths)) {
            // Check and add each individual new path.
            foreach ($path as $dir) {
                // Sanitize path.
                $dir = trim($dir);

                // Add to the front of the list so that custom paths are searched first.
                array_unshift($_paths, $dir);
            }
        }

        return $_paths;
    }

    protected function _getAssetName() {
        $k = $this->_tbl_key;

        return $this->_tbl . '.' . (int)$this->$k;
    }

    protected function _getAssetTitle() {
        return $this->_getAssetName();
    }

    protected function _getAssetParentId($table = null, $id = null) {
        // For simple cases, parent to the asset root.
        $assets = self::getInstance('Asset', 'MTable', array('dbo' => $this->getDbo()));
        $rootId = $assets->getRootId();
        if (!empty($rootId)) {
            return $rootId;
        }

        return 1;
    }

    public function getTableName() {
        return $this->_tbl;
    }

    public function getKeyName() {
        return $this->_tbl_key;
    }

    public function getDbo() {
        return $this->_db;
    }

    public function setDBO(&$db) {
        // Make sure the new database object is a MDatabase.
        if (!($db instanceof MDatabase)) {
            return false;
        }

        $this->_db = & $db;

        return true;
    }

    public function setRules($input) {
        if ($input instanceof MAccessRules) {
            $this->_rules = $input;
        }
        else {
            $this->_rules = new MAccessRules($input);
        }
    }

    public function getRules() {
        return $this->_rules;
    }

    public function reset() {
        // Get the default values for the class from the table.
        foreach ($this->getFields() as $k => $v) {
            // If the property is not the primary key or private, reset it.
            if ($k != $this->_tbl_key && (strpos($k, '_') !== 0)) {
                $this->$k = $v->Default;
            }
        }
    }

    public function bind($src, $ignore = array()) {
        // If the source value is not an array or object return false.
        if (!is_object($src) && !is_array($src)) {
            $e = new MException(MText::sprintf('MLIB_DATABASE_ERROR_BIND_FAILED_INVALID_SOURCE_ARGUMENT', get_class($this)));
            $this->setError($e);

            return false;
        }

        // If the source value is an object, get its accessible properties.
        if (is_object($src)) {
            $src = get_object_vars($src);
        }

        // If the ignore value is a string, explode it over spaces.
        if (!is_array($ignore)) {
            $ignore = explode(' ', $ignore);
        }

        // Bind the source value, excluding the ignored fields.
        foreach ($this->getProperties() as $k => $v) {
            // Only process fields not in the ignore array.
            if (!in_array($k, $ignore)) {
                if (isset($src[$k])) {
                    $this->$k = $src[$k];
                }
            }
        }

        return true;
    }

    public function load($keys = null, $reset = true) {
        if (empty($keys)) {
            // If empty, use the value of the current key
            $keyName  = $this->_tbl_key;
            $keyValue = $this->$keyName;

            // If empty primary key there's is no need to load anything
            if (empty($keyValue)) {
                return true;
            }

            $keys = array($keyName => $keyValue);
        }
        elseif (!is_array($keys)) {
            // Load by primary key.
            $keys = array($this->_tbl_key => $keys);
        }

        if ($reset) {
            $this->reset();
        }

        // Initialise the query.
        $query = $this->_db->getQuery(true);
        $query->select('*');
        $query->from($this->_tbl);
        $fields = array_keys($this->getProperties());

        foreach ($keys as $field => $value) {
            // Check that $field is in the table.
            if (!in_array($field, $fields)) {
                $e = new MException(MText::sprintf('MLIB_DATABASE_ERROR_CLASS_IS_MISSING_FIELD', get_class($this), $field));
                $this->setError($e);

                return false;
            }
            // Add the search tuple to the query.
            $query->where($this->_db->quoteName($field) . ' = ' . $this->_db->quote($value));
        }

        $this->_db->setQuery($query);

        try {
            $row = $this->_db->loadAssoc();
        } catch (RuntimeException $e) {
            $je = new MException($e->getMessage());
            $this->setError($je);

            return false;
        }

        // Legacy error handling switch based on the MError::$legacy switch.
        // @deprecated  12.1
        if (MError::$legacy && $this->_db->getErrorNum()) {
            $e = new MException($this->_db->getErrorMsg());
            $this->setError($e);

            return false;
        }

        // Check that we have a result.
        if (empty($row)) {
            $e = new MException(MText::_('MLIB_DATABASE_ERROR_EMPTY_ROW_RETURNED'));
            $this->setError($e);

            return false;
        }

        // Bind the object with the row and return.
        return $this->bind($row);
    }

    public function check() {
        return true;
    }

    public function store($updateNulls = false) {
        // Initialise variables.
        $k = $this->_tbl_key;
        if (!empty($this->asset_id)) {
            $currentAssetId = $this->asset_id;
        }

        // The asset id field is managed privately by this class.
        if ($this->_trackAssets) {
            unset($this->asset_id);
        }

        // If a primary key exists update the object, otherwise insert it.
        if ($this->$k) {
            $stored = $this->_db->updateObject($this->_tbl, $this, $this->_tbl_key, $updateNulls);
        }
        else {
            $stored = $this->_db->insertObject($this->_tbl, $this, $this->_tbl_key);
        }

        // If the store failed return false.
        if (!$stored) {
            $e = new MException(MText::sprintf('MLIB_DATABASE_ERROR_STORE_FAILED', get_class($this), $this->_db->getErrorMsg()));
            $this->setError($e);

            return false;
        }

        // If the table is not set to track assets return true.
        if (!$this->_trackAssets) {
            return true;
        }

        if ($this->_locked) {
            $this->_unlock();
        }

        $parentId = $this->_getAssetParentId();
        $name     = $this->_getAssetName();
        $title    = $this->_getAssetTitle();

        $asset = MTable::getInstance('Asset', 'MTable', array('dbo' => $this->getDbo()));
        $asset->loadByName($name);

        // Re-inject the asset id.
        $this->asset_id = $asset->id;

        // Check for an error.
        if ($error = $asset->getError()) {
            $this->setError($error);

            return false;
        }

        // Specify how a new or moved node asset is inserted into the tree.
        if (empty($this->asset_id) || $asset->parent_id != $parentId) {
            $asset->setLocation($parentId, 'last-child');
        }

        // Prepare the asset to be stored.
        $asset->parent_id = $parentId;
        $asset->name      = $name;
        $asset->title     = $title;

        if ($this->_rules instanceof JAccessRules) {
            $asset->rules = (string)$this->_rules;
        }

        if (!$asset->check() || !$asset->store($updateNulls)) {
            $this->setError($asset->getError());

            return false;
        }

        // Create an asset_id or heal one that is corrupted.
        if (empty($this->asset_id) || ($currentAssetId != $this->asset_id && !empty($this->asset_id))) {
            // Update the asset_id field in this table.
            $this->asset_id = (int)$asset->id;

            $query = $this->_db->getQuery(true);
            $query->update($this->_db->quoteName($this->_tbl));
            $query->set('asset_id = ' . (int)$this->asset_id);
            $query->where($this->_db->quoteName($k) . ' = ' . (int)$this->$k);
            $this->_db->setQuery($query);

            if (!$this->_db->execute()) {
                $e = new MException(MText::sprintf('MLIB_DATABASE_ERROR_STORE_FAILED_UPDATE_ASSET_ID', $this->_db->getErrorMsg()));
                $this->setError($e);

                return false;
            }
        }

        return true;
    }

    public function save($src, $orderingFilter = '', $ignore = '') {
        // Attempt to bind the source to the instance.
        if (!$this->bind($src, $ignore)) {
            return false;
        }

        // Run any sanity checks on the instance and verify that it is ready for storage.
        if (!$this->check()) {
            return false;
        }

        // Attempt to store the properties to the database table.
        if (!$this->store()) {
            return false;
        }

        // Attempt to check the row in, just in case it was checked out.
        if (!$this->checkin()) {
            return false;
        }

        // If an ordering filter is set, attempt reorder the rows in the table based on the filter and value.
        if ($orderingFilter) {
            $filterValue = $this->$orderingFilter;
            $this->reorder($orderingFilter ? $this->_db->quoteName($orderingFilter) . ' = ' . $this->_db->Quote($filterValue) : '');
        }

        // Set the error to empty and return true.
        $this->setError('');

        return true;
    }

    public function delete($pk = null) {
        // Initialise variables.
        $k  = $this->_tbl_key;
        $pk = (is_null($pk)) ? $this->$k : $pk;

        // If no primary key is given, return false.
        if ($pk === null) {
            $e = new MException(MText::_('MLIB_DATABASE_ERROR_NULL_PRIMARY_KEY'));
            $this->setError($e);

            return false;
        }

        // If tracking assets, remove the asset first.
        if ($this->_trackAssets) {
            // Get and the asset name.
            $this->$k = $pk;
            $name     = $this->_getAssetName();
            $asset    = MTable::getInstance('Asset');

            if ($asset->loadByName($name)) {
                if (!$asset->delete()) {
                    $this->setError($asset->getError());

                    return false;
                }
            }
            else {
                $this->setError($asset->getError());

                return false;
            }
        }

        // Delete the row by primary key.
        $query = $this->_db->getQuery(true);
        $query->delete();
        $query->from($this->_tbl);
        $query->where($this->_tbl_key . ' = ' . $this->_db->quote($pk));
        $this->_db->setQuery($query);

        // Check for a database error.
        if (!$this->_db->execute()) {
            $e = new MException(MText::sprintf('MLIB_DATABASE_ERROR_DELETE_FAILED', get_class($this), $this->_db->getErrorMsg()));
            $this->setError($e);

            return false;
        }

        return true;
    }

    public function checkOut($userId, $pk = null) {
        // If there is no checked_out or checked_out_time field, just return true.
        if (!property_exists($this, 'checked_out') || !property_exists($this, 'checked_out_time')) {
            return true;
        }

        // Initialise variables.
        $k  = $this->_tbl_key;
        $pk = (is_null($pk)) ? $this->$k : $pk;

        // If no primary key is given, return false.
        if ($pk === null) {
            $e = new MException(MText::_('MLIB_DATABASE_ERROR_NULL_PRIMARY_KEY'));
            $this->setError($e);

            return false;
        }

        // Get the current time in MySQL format.
        $time = MFactory::getDate()->toSql();

        // Check the row out by primary key.
        $query = $this->_db->getQuery(true);
        $query->update($this->_tbl);
        $query->set($this->_db->quoteName('checked_out') . ' = ' . (int)$userId);
        $query->set($this->_db->quoteName('checked_out_time') . ' = ' . $this->_db->quote($time));
        $query->where($this->_tbl_key . ' = ' . $this->_db->quote($pk));
        $this->_db->setQuery($query);

        if (!$this->_db->execute()) {
            $e = new MException(MText::sprintf('MLIB_DATABASE_ERROR_CHECKOUT_FAILED', get_class($this), $this->_db->getErrorMsg()));
            $this->setError($e);

            return false;
        }

        // Set table values in the object.
        $this->checked_out      = (int)$userId;
        $this->checked_out_time = $time;

        return true;
    }

    public function checkIn($pk = null) {
        // If there is no checked_out or checked_out_time field, just return true.
        if (!property_exists($this, 'checked_out') || !property_exists($this, 'checked_out_time')) {
            return true;
        }

        // Initialise variables.
        $k  = $this->_tbl_key;
        $pk = (is_null($pk)) ? $this->$k : $pk;

        // If no primary key is given, return false.
        if ($pk === null) {
            $e = new MException(MText::_('MLIB_DATABASE_ERROR_NULL_PRIMARY_KEY'));
            $this->setError($e);

            return false;
        }

        // Check the row in by primary key.
        $query = $this->_db->getQuery(true);
        $query->update($this->_tbl);
        $query->set($this->_db->quoteName('checked_out') . ' = 0');
        $query->set($this->_db->quoteName('checked_out_time') . ' = ' . $this->_db->quote($this->_db->getNullDate()));
        $query->where($this->_tbl_key . ' = ' . $this->_db->quote($pk));
        $this->_db->setQuery($query);

        // Check for a database error.
        if (!$this->_db->execute()) {
            $e = new MException(MText::sprintf('MLIB_DATABASE_ERROR_CHECKIN_FAILED', get_class($this), $this->_db->getErrorMsg()));
            $this->setError($e);

            return false;
        }

        // Set table values in the object.
        $this->checked_out      = 0;
        $this->checked_out_time = '';

        return true;
    }

    public function hit($pk = null) {
        // If there is no hits field, just return true.
        if (!property_exists($this, 'hits')) {
            return true;
        }

        // Initialise variables.
        $k  = $this->_tbl_key;
        $pk = (is_null($pk)) ? $this->$k : $pk;

        // If no primary key is given, return false.
        if ($pk === null) {
            return false;
        }

        // Check the row in by primary key.
        $query = $this->_db->getQuery(true);
        $query->update($this->_tbl);
        $query->set($this->_db->quoteName('hits') . ' = (' . $this->_db->quoteName('hits') . ' + 1)');
        $query->where($this->_tbl_key . ' = ' . $this->_db->quote($pk));
        $this->_db->setQuery($query);

        // Check for a database error.
        if (!$this->_db->execute()) {
            $e = new MException(MText::sprintf('MLIB_DATABASE_ERROR_HIT_FAILED', get_class($this), $this->_db->getErrorMsg()));
            $this->setError($e);

            return false;
        }

        // Set table values in the object.
        $this->hits++;

        return true;
    }

    public function isCheckedOut($with = 0, $against = null) {
        // Handle the non-static case.
        if (isset($this) && ($this instanceof MTable) && is_null($against)) {
            $against = $this->get('checked_out');
        }

        // The item is not checked out or is checked out by the same user.
        if (!$against || ($against == $with)) {
            return false;
        }

        $db = MFactory::getDBO();
        $db->setQuery('SELECT COUNT(userid)' . ' FROM ' . $db->quoteName('#__session') . ' WHERE ' . $db->quoteName('userid') . ' = ' . (int)$against);
        $checkedOut = (boolean)$db->loadResult();

        // If a session exists for the user then it is checked out.
        return $checkedOut;
    }

    public function getNextOrder($where = '') {
        // If there is no ordering field set an error and return false.
        if (!property_exists($this, 'ordering')) {
            $e = new MException(MText::sprintf('MLIB_DATABASE_ERROR_CLASS_DOES_NOT_SUPPORT_ORDERING', get_class($this)));
            $this->setError($e);

            return false;
        }

        // Get the largest ordering value for a given where clause.
        $query = $this->_db->getQuery(true);
        $query->select('MAX(ordering)');
        $query->from($this->_tbl);

        if ($where) {
            $query->where($where);
        }

        $this->_db->setQuery($query);
        $max = (int)$this->_db->loadResult();

        // Check for a database error.
        if ($this->_db->getErrorNum()) {
            $e = new MException(MText::sprintf('MLIB_DATABASE_ERROR_GET_NEXT_ORDER_FAILED', get_class($this), $this->_db->getErrorMsg()));
            $this->setError($e);

            return false;
        }

        // Return the largest ordering value + 1.
        return ($max + 1);
    }

    public function reorder($where = '') {
        // If there is no ordering field set an error and return false.
        if (!property_exists($this, 'ordering')) {
            $e = new MException(MText::sprintf('MLIB_DATABASE_ERROR_CLASS_DOES_NOT_SUPPORT_ORDERING', get_class($this)));
            $this->setError($e);

            return false;
        }

        // Initialise variables.
        $k = $this->_tbl_key;

        // Get the primary keys and ordering values for the selection.
        $query = $this->_db->getQuery(true);
        $query->select($this->_tbl_key . ', ordering');
        $query->from($this->_tbl);
        $query->where('ordering >= 0');
        $query->order('ordering');

        // Setup the extra where and ordering clause data.
        if ($where) {
            $query->where($where);
        }

        $this->_db->setQuery($query);
        $rows = $this->_db->loadObjectList();

        // Check for a database error.
        if ($this->_db->getErrorNum()) {
            $e = new MException(MText::sprintf('MLIB_DATABASE_ERROR_REORDER_FAILED', get_class($this), $this->_db->getErrorMsg()));
            $this->setError($e);

            return false;
        }

        // Compact the ordering values.
        foreach ($rows as $i => $row) {
            // Make sure the ordering is a positive integer.
            if ($row->ordering >= 0) {
                // Only update rows that are necessary.
                if ($row->ordering != $i + 1) {
                    // Update the row ordering field.
                    $query = $this->_db->getQuery(true);
                    $query->update($this->_tbl);
                    $query->set('ordering = ' . ($i + 1));
                    $query->where($this->_tbl_key . ' = ' . $this->_db->quote($row->$k));
                    $this->_db->setQuery($query);

                    // Check for a database error.
                    if (!$this->_db->execute()) {
                        $e = new MException(
                            MText::sprintf('MLIB_DATABASE_ERROR_REORDER_UPDATE_ROW_FAILED', get_class($this), $i, $this->_db->getErrorMsg())
                        );
                        $this->setError($e);

                        return false;
                    }
                }
            }
        }

        return true;
    }

    public function move($delta, $where = '') {
        // If there is no ordering field set an error and return false.
        if (!property_exists($this, 'ordering')) {
            $e = new MException(MText::sprintf('MLIB_DATABASE_ERROR_CLASS_DOES_NOT_SUPPORT_ORDERING', get_class($this)));
            $this->setError($e);

            return false;
        }

        // If the change is none, do nothing.
        if (empty($delta)) {
            return true;
        }

        // Initialise variables.
        $k     = $this->_tbl_key;
        $row   = null;
        $query = $this->_db->getQuery(true);

        // Select the primary key and ordering values from the table.
        $query->select($this->_tbl_key . ', ordering');
        $query->from($this->_tbl);

        // If the movement delta is negative move the row up.
        if ($delta < 0) {
            $query->where('ordering < ' . (int)$this->ordering);
            $query->order('ordering DESC');
        }
        // If the movement delta is positive move the row down.
        elseif ($delta > 0) {
            $query->where('ordering > ' . (int)$this->ordering);
            $query->order('ordering ASC');
        }

        // Add the custom WHERE clause if set.
        if ($where) {
            $query->where($where);
        }

        // Select the first row with the criteria.
        $this->_db->setQuery($query, 0, 1);
        $row = $this->_db->loadObject();

        // If a row is found, move the item.
        if (!empty($row)) {
            // Update the ordering field for this instance to the row's ordering value.
            $query = $this->_db->getQuery(true);
            $query->update($this->_tbl);
            $query->set('ordering = ' . (int)$row->ordering);
            $query->where($this->_tbl_key . ' = ' . $this->_db->quote($this->$k));
            $this->_db->setQuery($query);

            // Check for a database error.
            if (!$this->_db->execute()) {
                $e = new MException(MText::sprintf('MLIB_DATABASE_ERROR_MOVE_FAILED', get_class($this), $this->_db->getErrorMsg()));
                $this->setError($e);

                return false;
            }

            // Update the ordering field for the row to this instance's ordering value.
            $query = $this->_db->getQuery(true);
            $query->update($this->_tbl);
            $query->set('ordering = ' . (int)$this->ordering);
            $query->where($this->_tbl_key . ' = ' . $this->_db->quote($row->$k));
            $this->_db->setQuery($query);

            // Check for a database error.
            if (!$this->_db->execute()) {
                $e = new MException(MText::sprintf('MLIB_DATABASE_ERROR_MOVE_FAILED', get_class($this), $this->_db->getErrorMsg()));
                $this->setError($e);

                return false;
            }

            // Update the instance value.
            $this->ordering = $row->ordering;
        }
        else {
            // Update the ordering field for this instance.
            $query = $this->_db->getQuery(true);
            $query->update($this->_tbl);
            $query->set('ordering = ' . (int)$this->ordering);
            $query->where($this->_tbl_key . ' = ' . $this->_db->quote($this->$k));
            $this->_db->setQuery($query);

            // Check for a database error.
            if (!$this->_db->execute()) {
                $e = new MException(MText::sprintf('MLIB_DATABASE_ERROR_MOVE_FAILED', get_class($this), $this->_db->getErrorMsg()));
                $this->setError($e);

                return false;
            }
        }

        return true;
    }

    public function publish($pks = null, $state = 1, $userId = 0) {
        // Initialise variables.
        $k = $this->_tbl_key;

        // Sanitize input.
        MArrayHelper::toInteger($pks);
        $userId = (int)$userId;
        $state  = (int)$state;

        // If there are no primary keys set check to see if the instance key is set.
        if (empty($pks)) {
            if ($this->$k) {
                $pks = array($this->$k);
            }
            // Nothing to set publishing state on, return false.
            else {
                $e = new MException(MText::_('MLIB_DATABASE_ERROR_NO_ROWS_SELECTED'));
                $this->setError($e);

                return false;
            }
        }

        // Update the publishing state for rows with the given primary keys.
        $query = $this->_db->getQuery(true);
        $query->update($this->_tbl);
        $query->set('published = ' . (int)$state);

        // Determine if there is checkin support for the table.
        if (property_exists($this, 'checked_out') || property_exists($this, 'checked_out_time')) {
            $query->where('(checked_out = 0 OR checked_out = ' . (int)$userId . ')');
            $checkin = true;
        }
        else {
            $checkin = false;
        }

        // Build the WHERE clause for the primary keys.
        $query->where($k . ' = ' . implode(' OR ' . $k . ' = ', $pks));

        $this->_db->setQuery($query);

        // Check for a database error.
        if (!$this->_db->execute()) {
            $e = new MException(MText::sprintf('MLIB_DATABASE_ERROR_PUBLISH_FAILED', get_class($this), $this->_db->getErrorMsg()));
            $this->setError($e);

            return false;
        }

        // If checkin is supported and all rows were adjusted, check them in.
        if ($checkin && (count($pks) == $this->_db->getAffectedRows())) {
            // Checkin the rows.
            foreach ($pks as $pk) {
                $this->checkin($pk);
            }
        }

        // If the MTable instance value is in the list of primary keys that were set, set the instance.
        if (in_array($this->$k, $pks)) {
            $this->published = $state;
        }

        $this->setError('');

        return true;
    }

    public function canDelete($pk = null, $joins = null) {
        // Deprecation warning.
        MLog::add('MTable::canDelete() is deprecated.', MLog::WARNING, 'deprecated');

        // Initialise variables.
        $k  = $this->_tbl_key;
        $pk = (is_null($pk)) ? $this->$k : $pk;

        // If no primary key is given, return false.
        if ($pk === null) {
            return false;
        }

        if (is_array($joins)) {
            // Get a query object.
            $query = $this->_db->getQuery(true);

            // Setup the basic query.
            $query->select($this->_db->quoteName($this->_tbl_key));
            $query->from($this->_db->quoteName($this->_tbl));
            $query->where($this->_db->quoteName($this->_tbl_key) . ' = ' . $this->_db->quote($this->$k));
            $query->group($this->_db->quoteName($this->_tbl_key));

            // For each join add the select and join clauses to the query object.
            foreach ($joins as $table) {
                $query->select('COUNT(DISTINCT ' . $table['idfield'] . ') AS ' . $table['idfield']);
                $query->join('LEFT', $table['name'] . ' ON ' . $table['joinfield'] . ' = ' . $k);
            }

            // Get the row object from the query.
            $this->_db->setQuery((string)$query, 0, 1);
            $row = $this->_db->loadObject();

            // Check for a database error.
            if ($this->_db->getErrorNum()) {
                $this->setError($this->_db->getErrorMsg());

                return false;
            }

            $msg = array();
            $i   = 0;

            foreach ($joins as $table) {
                $k = $table['idfield'] . $i;

                if ($row->$k) {
                    $msg[] = MText::_($table['label']);
                }

                $i++;
            }

            if (count($msg)) {
                $this->setError("noDeleteRecord" . ": " . implode(', ', $msg));

                return false;
            }
            else {
                return true;
            }
        }

        return true;
    }

    public function toXML($mapKeysToText = false) {
        // Deprecation warning.
        MLog::add('MTable::toXML() is deprecated.', MLog::WARNING, 'deprecated');

        // Initialise variables.
        $xml = array();
        $map = $mapKeysToText ? ' mapkeystotext="true"' : '';

        // Open root node.
        $xml[] = '<record table="' . $this->_tbl . '"' . $map . '>';

        // Get the publicly accessible instance properties.
        foreach (get_object_vars($this) as $k => $v) {
            // If the value is null or non-scalar, or the field is internal ignore it.
            if (!is_scalar($v) || ($v === null) || ($k[0] == '_')) {
                continue;
            }

            $xml[] = '	<' . $k . '><![CDATA[' . $v . ']]></' . $k . '>';
        }

        // Close root node.
        $xml[] = '</record>';

        // Return the XML array imploded over new lines.
        return implode("\n", $xml);
    }

    protected function _lock() {
        $this->_db->lockTable($this->_tbl);
        $this->_locked = true;

        return true;
    }

    protected function _unlock() {
        $this->_db->unlockTables();
        $this->_locked = false;

        return true;
    }
}