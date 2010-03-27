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
     * Persist a proxy
     *
     * Think INSERT.
     *
     * @param repose_Session $session Session
     * @param repose_IProxy $proxy Proxy
     */
    public function persist(repose_Session $session, repose_IProxy $proxy) {
        $mappedClass = $proxy->___repose_mappedClass($session);
        $data = $this->normalizeColumnData(
            $session,
            $mappedClass,
            $proxy->___repose_currentData($session)
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
        $mappedClass = $proxy->___repose_mappedClass($session);
        $data = $this->normalizeColumnData(
            $session,
            $mappedClass,
            $proxy->___repose_changedData($session)
        );
        $pkData = $this->normalizeColumnData(
            $session,
            $mappedClass,
            $proxy->___repose_primaryKeyData($session)
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
                $columnData[$columnName] = $value->___repose_primaryKeyValue($session);
            } else {
                $columnData[$columnName] = $value;
            }
        }
        return $columnData;
    }

    /**
     * Insert data into a table
     * @param string $tableName Table name
     * @param array $data Associative array
     */
    abstract protected function sqlInsert($tableName, $data);

    /**
     * Update data in a table
     * @param string $tableName Table name
     * @param array $data Associative array with updated data
     * @param array $where Associative array containing WHERE information
     */
    abstract protected function sqlUpdate($tableName, $data, $where);

    /**
     * Select data
     * @param string $selectQuery Select query
     * @param array $params Associative array with bind params
     * @return array
     */
    abstract protected function sqlSelect($selectQuery, $params = null);


}
?>
