<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MDatabaseQueryElement {

    protected $name = null;
    protected $elements = null;
    protected $glue = null;

    public function __construct($name, $elements, $glue = ',') {
        $this->elements = array();
        $this->name     = $name;
        $this->glue     = $glue;

        $this->append($elements);
    }

    public function __toString() {
        if (substr($this->name, -2) == '()') {
            return PHP_EOL . substr($this->name, 0, -2) . '(' . implode($this->glue, $this->elements) . ')';
        }
        else {
            return PHP_EOL . $this->name . ' ' . implode($this->glue, $this->elements);
        }
    }

    public function append($elements) {
        if (is_array($elements)) {
            $this->elements = array_merge($this->elements, $elements);
        }
        else {
            $this->elements = array_merge($this->elements, array($elements));
        }
    }

    public function getElements() {
        return $this->elements;
    }

    public function __clone() {
        foreach ($this as $k => $v) {
            if (is_object($v) || is_array($v)) {
                $this->{$k} = unserialize(serialize($v));
            }
        }
    }
}

abstract class MDatabaseQuery {

    protected $db = null;
    protected $type = '';
    protected $element = null;
    protected $select = null;
    protected $delete = null;
    protected $update = null;
    protected $insert = null;
    protected $from = null;
    protected $join = null;
    protected $set = null;
    protected $where = null;
    protected $group = null;
    protected $having = null;
    protected $columns = null;
    protected $values = null;
    protected $order = null;
    protected $union = null;
    protected $autoIncrementField = null;

    public function __call($method, $args) {
        if (empty($args)) {
            return;
        }

        switch ($method) {
            case 'q':
                return $this->quote($args[0], isset($args[1]) ? $args[1] : true);
                break;

            case 'qn':
                return $this->quoteName($args[0]);
                break;

            case 'e':
                return $this->escape($args[0], isset($args[1]) ? $args[1] : false);
                break;
        }
    }

    public function __construct(MDatabase $db = null) {
        $this->db = $db;
    }

    public function __toString() {
        $query = '';

        switch ($this->type) {
            case 'element':
                $query .= (string)$this->element;
                break;

            case 'select':
                $query .= (string)$this->select;
                $query .= (string)$this->from;
                if ($this->join) {
                    // special case for joins
                    foreach ($this->join as $join) {
                        $query .= (string)$join;
                    }
                }

                if ($this->where) {
                    $query .= (string)$this->where;
                }

                if ($this->group) {
                    $query .= (string)$this->group;
                }

                if ($this->having) {
                    $query .= (string)$this->having;
                }

                if ($this->order) {
                    $query .= (string)$this->order;
                }

                break;

            case 'union':
                $query .= (string)$this->union;
                break;

            case 'delete':
                $query .= (string)$this->delete;
                $query .= (string)$this->from;

                if ($this->join) {
                    // special case for joins
                    foreach ($this->join as $join) {
                        $query .= (string)$join;
                    }
                }

                if ($this->where) {
                    $query .= (string)$this->where;
                }

                break;

            case 'update':
                $query .= (string)$this->update;

                if ($this->join) {
                    // special case for joins
                    foreach ($this->join as $join) {
                        $query .= (string)$join;
                    }
                }

                $query .= (string)$this->set;

                if ($this->where) {
                    $query .= (string)$this->where;
                }

                break;

            case 'insert':
                $query .= (string)$this->insert;

                // Set method
                if ($this->set) {
                    $query .= (string)$this->set;
                }
                // Columns-Values method
                elseif ($this->values) {
                    if ($this->columns) {
                        $query .= (string)$this->columns;
                    }

                    $query .= ' VALUES ';
                    $query .= (string)$this->values;
                }

                break;
        }

        return $query;
    }

    public function __get($name) {
        return isset($this->$name) ? $this->$name : null;
    }

    public function castAsChar($value) {
        return $value;
    }

    public function charLength($field) {
        return 'CHAR_LENGTH(' . $field . ')';
    }

    public function clear($clause = null) {
        switch ($clause) {
            case 'select':
                $this->select = null;
                $this->type   = null;
                break;

            case 'delete':
                $this->delete = null;
                $this->type   = null;
                break;

            case 'update':
                $this->update = null;
                $this->type   = null;
                break;

            case 'insert':
                $this->insert             = null;
                $this->type               = null;
                $this->autoIncrementField = null;
                break;

            case 'from':
                $this->from = null;
                break;

            case 'join':
                $this->join = null;
                break;

            case 'set':
                $this->set = null;
                break;

            case 'where':
                $this->where = null;
                break;

            case 'group':
                $this->group = null;
                break;

            case 'having':
                $this->having = null;
                break;

            case 'order':
                $this->order = null;
                break;

            case 'columns':
                $this->columns = null;
                break;

            case 'values':
                $this->values = null;
                break;

            default:
                $this->type               = null;
                $this->select             = null;
                $this->delete             = null;
                $this->update             = null;
                $this->insert             = null;
                $this->from               = null;
                $this->join               = null;
                $this->set                = null;
                $this->where              = null;
                $this->group              = null;
                $this->having             = null;
                $this->order              = null;
                $this->columns            = null;
                $this->values             = null;
                $this->autoIncrementField = null;
                break;
        }

        return $this;
    }

    public function columns($columns) {
        if (is_null($this->columns)) {
            $this->columns = new MDatabaseQueryElement('()', $columns);
        }
        else {
            $this->columns->append($columns);
        }

        return $this;
    }

    public function concatenate($values, $separator = null) {
        if ($separator) {
            return 'CONCATENATE(' . implode(' || ' . $this->quote($separator) . ' || ', $values) . ')';
        }
        else {
            return 'CONCATENATE(' . implode(' || ', $values) . ')';
        }
    }

    public function currentTimestamp() {
        return 'CURRENT_TIMESTAMP()';
    }

    public function dateFormat() {
        if (!($this->db instanceof MDatabase)) {
            throw new MDatabaseException('MLIB_DATABASE_ERROR_INVALID_DB_OBJECT');
        }

        return $this->db->getDateFormat();
    }

    public function dump() {
        return '<pre class="jdatabasequery">' . str_replace('#__', $this->db->getPrefix(), $this) . '</pre>';
    }

    public function delete($table = null) {
        $this->type   = 'delete';
        $this->delete = new MDatabaseQueryElement('DELETE', null);

        if (!empty($table)) {
            $this->from($table);
        }

        return $this;
    }

    public function escape($text, $extra = false) {
        if (!($this->db instanceof MDatabase)) {
            throw new MDatabaseException('MLIB_DATABASE_ERROR_INVALID_DB_OBJECT');
        }

        return $this->db->escape($text, $extra);
    }

    public function from($tables) {
        if (is_null($this->from)) {
            $this->from = new MDatabaseQueryElement('FROM', $tables);
        }
        else {
            $this->from->append($tables);
        }

        return $this;
    }

    public function group($columns) {
        if (is_null($this->group)) {
            $this->group = new MDatabaseQueryElement('GROUP BY', $columns);
        }
        else {
            $this->group->append($columns);
        }

        return $this;
    }

    public function having($conditions, $glue = 'AND') {
        if (is_null($this->having)) {
            $glue         = strtoupper($glue);
            $this->having = new MDatabaseQueryElement('HAVING', $conditions, " $glue ");
        }
        else {
            $this->having->append($conditions);
        }

        return $this;
    }

    public function innerJoin($condition) {
        $this->join('INNER', $condition);

        return $this;
    }

    public function insert($table, $incrementField = false) {
        $this->type               = 'insert';
        $this->insert             = new MDatabaseQueryElement('INSERT INTO', $table);
        $this->autoIncrementField = $incrementField;

        return $this;
    }

    public function join($type, $conditions) {
        if (is_null($this->join)) {
            $this->join = array();
        }
        $this->join[] = new MDatabaseQueryElement(strtoupper($type) . ' JOIN', $conditions);

        return $this;
    }

    public function leftJoin($condition) {
        $this->join('LEFT', $condition);

        return $this;
    }

    public function length($value) {
        return 'LENGTH(' . $value . ')';
    }

    public function nullDate($quoted = true) {
        if (!($this->db instanceof MDatabase)) {
            throw new MDatabaseException('MLIB_DATABASE_ERROR_INVALID_DB_OBJECT');
        }

        $result = $this->db->getNullDate($quoted);

        if ($quoted) {
            return $this->db->quote($result);
        }

        return $result;
    }

    public function order($columns) {
        if (is_null($this->order)) {
            $this->order = new MDatabaseQueryElement('ORDER BY', $columns);
        }
        else {
            $this->order->append($columns);
        }

        return $this;
    }

    public function outerJoin($condition) {
        $this->join('OUTER', $condition);

        return $this;
    }

    public function quote($text, $escape = true) {
        if (!($this->db instanceof MDatabase)) {
            throw new MDatabaseException('MLIB_DATABASE_ERROR_INVALID_DB_OBJECT');
        }

        return $this->db->quote(($escape ? $this->db->escape($text) : $text));
    }

    public function quoteName($name) {
        if (!($this->db instanceof MDatabase)) {
            throw new MDatabaseException('MLIB_DATABASE_ERROR_INVALID_DB_OBJECT');
        }

        return $this->db->quoteName($name);
    }

    public function rightJoin($condition) {
        $this->join('RIGHT', $condition);

        return $this;
    }

    public function select($columns) {
        $this->type = 'select';

        if (is_null($this->select)) {
            $this->select = new MDatabaseQueryElement('SELECT', $columns);
        }
        else {
            $this->select->append($columns);
        }

        return $this;
    }

    public function set($conditions, $glue = ',') {
        if (is_null($this->set)) {
            $glue      = strtoupper($glue);
            $this->set = new MDatabaseQueryElement('SET', $conditions, "\n\t$glue ");
        }
        else {
            $this->set->append($conditions);
        }

        return $this;
    }

    public function update($table) {
        $this->type   = 'update';
        $this->update = new MDatabaseQueryElement('UPDATE', $table);

        return $this;
    }

    public function values($values) {
        if (is_null($this->values)) {
            $this->values = new MDatabaseQueryElement('()', $values, '),(');
        }
        else {
            $this->values->append($values);
        }

        return $this;
    }

    public function where($conditions, $glue = 'AND') {
        if (is_null($this->where)) {
            $glue        = strtoupper($glue);
            $this->where = new MDatabaseQueryElement('WHERE', $conditions, " $glue ");
        }
        else {
            $this->where->append($conditions);
        }

        return $this;
    }

    public function __clone() {
        foreach ($this as $k => $v) {
            if ($k === 'db') {
                continue;
            }

            if (is_object($v) || is_array($v)) {
                $this->$k = unserialize(serialize($v));
            }
        }
    }

    public function union($query, $distinct = false, $glue = '') {

        // Clear any ORDER BY clause in UNION query
        // See http://dev.mysql.com/doc/refman/5.0/en/union.html
        if (!is_null($this->order)) {
            $this->clear('order');
        }

        // Set up the DISTINCT flag, the name with parentheses, and the glue.
        if ($distinct) {
            $name = 'UNION DISTINCT ()';
            $glue = ')' . PHP_EOL . 'UNION DISTINCT (';
        }
        else {
            $glue = ')' . PHP_EOL . 'UNION (';
            $name = 'UNION ()';

        }
        // Get the MDatabaseQueryElement if it does not exist
        if (is_null($this->union)) {
            $this->union = new MDatabaseQueryElement($name, $query, "$glue");
        }
        // Otherwise append the second UNION.
        else {
            $glue = '';
            $this->union->append($query);
        }

        return $this;
    }

    public function unionDistinct($query, $glue = '') {
        $distinct = true;

        // Apply the distinct flag to the union.
        return $this->union($query, $distinct, $glue);
    }
}