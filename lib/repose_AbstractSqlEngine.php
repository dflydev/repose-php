<?php
/**
 * Abstract SQL Engine.
 * @package repose
 */

require_once('repose_IEngine.php');

/**
 * Abstract SQL Engine.
 * @package repose
 */
abstract class repose_AbstractSqlEngine implements repose_IEngine {

    /**
     * Simplify SQL?
     *
     * Generated SQL should be converted from using name binding to
     * placeholder binding.
     * 
     * @var bool
     */
    protected $simplify = false;

    /**
     * Persist a proxy
     *
     * Think INSERT.
     *
     * @param repose_Session $session Session
     * @param repose_IProxy $proxy Proxy
     */
    public function persist(repose_Session $session, repose_IProxy $proxy) {
        $mappedClass = $proxy->___repose_mappedClass();
        $data = $this->normalizeColumnData(
            $session,
            $mappedClass,
            $proxy->___repose_currentData()
        );
        return $this->sqlInsert($mappedClass->tableName(), $data);
    }

    /**
     * Update a persisted proxy
     *
     * Think UPDATE WHERE.
     *
     * @param repose_Session $session Session
     * @param repose_IProxy $proxy Proxy
     */
    public function update(repose_Session $session, repose_IProxy $proxy) {
        $mappedClass = $proxy->___repose_mappedClass();
        $data = $this->normalizeColumnData(
            $session,
            $mappedClass,
            $proxy->___repose_changedData()
        );
        $pkData = $this->normalizeColumnData(
            $session,
            $mappedClass,
            $proxy->___repose_primaryKeyData()
        );
        return $this->sqlUpdate($mappedClass->tableName(), $data, $pkData);
    }

    /**
     * Delete a persisted proxy
     *
     * Think DELETE WHERE.
     *
     * @param repose_Session $session Session
     * @param repose_IProxy $proxy Proxy
     */
    public function delete(repose_Session $session, repose_IProxy $proxy) {
        $mappedClass = $proxy->___repose_mappedClass();
        $pkData = $this->normalizeColumnData(
            $session,
            $mappedClass,
            $proxy->___repose_primaryKeyData()
        );
        return $this->sqlDelete($mappedClass->tableName(), $pkData);
    }

    /**
     * Perform a query
     *
     * Think SELECT WHERE.
     *
     * @param repose_Session $session Session
     * @param string $queryString Query string
     * @param array $params Query params
     * @return array
     */
    public function query(repose_Session $session, $queryString, $params = null) {
        if ( ! is_null($params) ) {
            foreach ( $params as $idx => $value ) {
                if ( $value !== null and $value instanceof repose_IProxy ) {
                    $params[$idx] = $value->___repose_primaryKeyValue();
                }
            }
        }
        return $this->sqlSelect($queryString, $params);
    }

    /**
     * Normalize instance data to column data
     * @param repose_Session $session Session
     * @param repose_MappedClass $mappedClass Mapped Class
     * @param array $data Associative array
     * @return array
     */
    protected function normalizeColumnData($session, $mappedClass, $data) {
        $columnData = array();
        foreach ( $data as $name => $value ) {
            $property = $mappedClass->mappedClassProperty($name);
            $columnName = $property->columnName($session->mapping());
            if ( $property->isObject() ) {
                if ( $value === null ) {
                    $columnData[$columnName] = null;
                } else {
                    $columnData[$columnName] = $value->___repose_primaryKeyValue();
                }
            } else {
                $columnData[$columnName] = $value;
            }
        }
        return $columnData;
    }


    /**
     * Insert data into a table
     * @param string $tableName Table nane
     * @param array $data Associative array
     */
    protected function sqlInsert($tableName, $data) {
        $columns = array();
        $placeholders = array();
        $values = array();
        foreach ( $data as $columnName => $value ) {
            $columns[] = $columnName;
            $values[$columnName] = $value;
            $placeholders[] = ':' . $columnName;
        }
        $sql = 'INSERT INTO ' . $tableName . ' (' . implode(',', $columns) . ') VALUES (' . implode(',', $placeholders) . ')';
        if ( $this->simplify ) {
            list($sql, $values) = $this->simplifySql($sql, $values);
        }
        return $this->doInsert($sql, $values);
    }

    /**
     * Update data in a table
     * @param string $tableName Table nane
     * @param array $data Associative array with updated data
     * @param array $where Associative array containing WHERE information
     */
    protected function sqlUpdate($tableName, $data, $where) {
        $sets = array();
        $wheres = array();
        $values = array();
        foreach ( $data as $columnName => $value ) {
            $sets[] = $columnName . ' = :' . $columnName;
            $values[$columnName] = $value;
        }
        foreach ( $where as $columnName => $value ) {
            $wheres[] = $columnName . ' = :where_' . $columnName;
            $values['where_' . $columnName] = $value;
        }
        $sql = 'UPDATE ' . $tableName . ' SET ' . implode(', ', $sets) . ' WHERE ' . implode(' AND ', $wheres);
        if ( $this->simplify ) {
            list($sql, $values) = $this->simplifySql($sql, $values);
        }
        return $this->doUpdate($sql, $values);
    }

    /**
     * Delete data from a table
     * @param string $tableName Table name
     * @param array $where Associative array containing WHERE information
     */
    protected function sqlDelete($tableName, $where) {
        $wheres = array();
        $values = array();
        foreach ( $where as $columnName => $value ) {
            $wheres[] = $columnName . ' = :where_' . $columnName;
            $values['where_' . $columnName] = $value;
        }
        $sql = 'DELETE FROM ' . $tableName . ' WHERE ' . implode(' AND ', $wheres);
        if ( $this->simplify ) {
            list($sql, $values) = $this->simplifySql($sql, $values);
        }
        return $this->doDelete($sql, $values);
    }

    /**
     * Select data
     * @param string $selectQuery Select query
     * @param array $params Associative array with bind params
     * @return array
     */
    protected function sqlSelect($selectQuery, $params = null) {
        $rows = array();
        if ( $this->simplify ) {
            list($selectQuery, $params) = $this->simplifySql($selectQuery, $params);
        } else {
            if ( ! is_array($params) ) $params = array();
        }
        return $this->doSelect($selectQuery, $params);
    }

    /**
     * Simplify the incoming SQL
     * @param string $sql SQL
     * @param array $params Params
     * @return array
     */
    protected function simplifySql($sql, $params = null) {
        if ( is_null($params) ) $params = array();
        // should never happen
        if ( ! is_array($params) ) $params = array($params);
        $values = array();
        while (preg_match('/:([\w]+)\b/', $sql, $matches)) {
            $name = $matches[1];
            $values[] = isset($params[$name]) ? $params[$name] : null;
            $sql = preg_replace('/:' . $name . '(\W|$)/', '?$1', $sql);
        }
        return array($sql, $values);
    }

    /**
     * Insert data into a table
     * @param string $sql SQL
     * @param array $data Associative array
     */
    abstract protected function doInsert($sql, array $params);

    /**
     * Update data in a table
     * @param string $sql SQL
     * @param array $data Associative array
     */
    abstract protected function doUpdate($sql, array $params);

    /**
     * Delete data from a table
     * @param string $sql SQL
     * @param array $data Associative array
     */
    abstract protected function doDelete($sql, array $params);

    /**
     * Select data
     * @param string $sql SQL
     * @param array $data Associative array
     * @return array
     */
    abstract protected function doSelect($sql, array $params);
    
}
?>
